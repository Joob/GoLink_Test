<?php
namespace App\Users\Models;

use App\Users\Notifications\AuthOtpCode;
use ByteUnits\Metric;
use Illuminate\Support\Str;
use BadMethodCallException;
use Domain\Files\Models\File;
use Domain\Folders\Models\Folder;
use Laravel\Sanctum\HasApiTokens;
use Domain\Traffic\Models\Traffic;
use Illuminate\Support\Facades\DB;
use Database\Factories\UserFactory;
use Domain\Settings\Models\Setting;
use Kyslik\ColumnSortable\Sortable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use App\Users\Notifications\ResetPassword;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Domain\UploadRequest\Models\UploadRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Users\Restrictions\RestrictionsManager;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use phpDocumentor\Reflection\Types\Integer;
use VueFileManager\Subscription\App\User\Traits\Billable;

/**
 * @property string id
 * @property Setting settings
 * @property string email
 * @property mixed favouriteFolders
 * @property string role
 * @property string email_verified_at
 * @property string|null otp_code
 * @property \Illuminate\Support\Carbon|null otp_expiration
 * @method static count()
 * @method static sortable(string[] $array)
 * @method static forceCreate(array $array)
 * @method static where(string $string, string $string1)
 * @method static create(array $array)
 * @method static find(mixed $email)
 * @method canUpload(int $size)
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use TwoFactorAuthenticatable;
    use HasApiTokens;
    use Notifiable;
    use HasFactory;
    use Sortable;
    use Billable;

    protected $guarded = [
        'id',
        'role',
    ];

    protected $fillable = [
        'email',
        'password',
        'last_login_at',
        'oauth_provider',
        'otp_code',
        'otp_expiration',
        'otp_attempts_used',
        'last_otp_sent_at',
        'otp_cooldown_start_at',
    ];

    protected $dates = [
        'last_otp_sent_at',
        'otp_cooldown_start_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
        'otp_expiration',
    ];

    protected $casts = [
        'id'                => 'string',
        'email_verified_at' => 'datetime',
        'otp_expiration'    => 'datetime',
        'last_login_at'     => 'datetime',
    ];

    protected $appends = [
        'usedCapacity',
        'storage',
    ];

    public $sortable = [
        'id',
        'name',
        'role',
        'created_at',
        'last_login_at',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function preferredLocale(): string
    {
        return get_settings('language') ?? 'en';
    }

    /**
     * Get user used storage details
     */
    public function getStorageAttribute(): array
    {
        $is_storage_limit = get_settings('storage_limitation') ?? 1;

        if (! $is_storage_limit) {
            return [
                'used'           => $this->usedCapacity,
                'used_formatted' => Metric::bytes($this->usedCapacity)->format(),
            ];
        }

        return [
            'used'               => (float) get_storage_percentage($this->usedCapacity, $this->limitations->max_storage_amount),
            'used_formatted'     => get_storage_percentage($this->usedCapacity, $this->limitations->max_storage_amount) . '%',
            'capacity'           => $this->limitations->max_storage_amount,
            'capacity_formatted' => toGigabytes($this->limitations->max_storage_amount),
        ];
    }

    /**
     * Get user used storage capacity in bytes
     */
    public function getUsedCapacityAttribute(): int
    {
        return DB::table('files')
            ->where('user_id', $this->id)
            ->sum('filesize');
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function isInOtpCooldown(): bool
    {
        if (!$this->otp_cooldown_start_at) {
            return false;
        }

        $cooldownDuration = 120; // 2 minutes in seconds
        return $this->otp_cooldown_start_at->addSeconds($cooldownDuration)->isFuture();
    }

    public function getRemainingCooldownTime(): int
    {
        if (!$this->isInOtpCooldown()) {
            return 0;
        }

        $cooldownDuration = 120; // 2 minutes
        $elapsed = now()->diffInSeconds($this->otp_cooldown_start_at);
        return max(0, $cooldownDuration - $elapsed);
    }

    public function canSendOtp(): bool
    {
        // If in cooldown period
        if ($this->isInOtpCooldown()) {
            return false;
        }

        // Reset attempts if cooldown period is over
        if ($this->otp_cooldown_start_at && !$this->isInOtpCooldown()) {
            $this->resetOtpAttempts();
        }

        // Check if reached maximum attempts
        if ($this->otp_attempts_used >= 5) {
            $this->startOtpCooldown();
            return false;
        }

        // Basic rate limiting between attempts (30 seconds)
        if ($this->last_otp_sent_at) {
            $minimumInterval = 30; // 30 seconds
            return $this->last_otp_sent_at->addSeconds($minimumInterval)->isPast();
        }

        return true;
    }

    public function isOtpStillValid(): bool
    {
        if (!$this->otp_code || !$this->last_otp_sent_at) {
            return false;
        }

        $validityDuration = 300; // 5 minutes
        return $this->last_otp_sent_at->addSeconds($validityDuration)->isFuture();
    }

    /**
     * Start cooldown period
     */
    public function startOtpCooldown(): void
    {
        $this->update([
            'otp_cooldown_start_at' => now()
        ]);
    }

    /**
     * Reset OTP attempts
     */
    public function resetOtpAttempts(): void
    {
        $this->update([
            'otp_attempts_used' => 0,
            'otp_cooldown_start_at' => null,
        ]);
    }

    /**
     * Full reset for new login session
     */
    public function fullResetOtpAttempts(): void
    {
        $this->update([
            'otp_attempts_used' => 0,
            'last_otp_sent_at' => null,
            'otp_cooldown_start_at' => null,
        ]);
    }

    /**
     * Increment OTP attempts
     */
    public function incrementOtpAttempts(): void
    {
        $this->increment('otp_attempts_used');
        
        // Start cooldown if reached 5 attempts
        if ($this->otp_attempts_used >= 5) {
            $this->startOtpCooldown();
        }
    }

    /**
     * Send new OTP code with rate limiting
     */
    public function sendNewOtpCodeWithRateLimit(): array
    {
        // Check if OTP is still valid
        if ($this->isOtpStillValid()) {
            $remainingTime = $this->getRemainingOtpValidity();
            
            return [
                'success' => false,
                'error' => 'valid_otp_exists',
                'message' => "You already have a valid OTP code. Time remaining: {$remainingTime} seconds.",
                'remaining_validity' => $this->getRemainingOtpValidity()
            ];
        }

        // Check if can send OTP
        if (!$this->canSendOtp()) {
            if ($this->isInOtpCooldown()) {
                return [
                    'success' => false,
                    'error' => 'cooldown_active',
                    'message' => 'You have exceeded the limit of 5 attempts.',
                    'remaining_cooldown' => $this->getRemainingCooldownTime()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'rate_limited',
                    'message' => 'Please wait before requesting a new code.',
                    'remaining_wait' => $this->getRemainingWaitTime()
                ];
            }
        }

        // Send OTP
        $this->sendNewOtpCode();
        $this->update(['last_otp_sent_at' => now()]);
        $this->incrementOtpAttempts();

        return [
            'success' => true,
            'message' => 'OTP code sent successfully.'
        ];
    }

    /**
     * Get remaining OTP validity time in seconds
     */
    public function getRemainingOtpValidity(): int
    {
        if (!$this->isOtpStillValid()) {
            return 0;
        }

        $validityDuration = 300; // 5 minutes
        $elapsed = now()->diffInSeconds($this->last_otp_sent_at);
        return max(0, $validityDuration - $elapsed);
    }

    /**
     * Get remaining wait time before next OTP request
     */
    public function getRemainingWaitTime(): int
    {
        if (!$this->last_otp_sent_at) {
            return 0;
        }

        $minimumInterval = 30; // 30 seconds
        $elapsed = now()->diffInSeconds($this->last_otp_sent_at);
        return max(0, $minimumInterval - $elapsed);
    }

    protected function generateOtpCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function otpCodeIsExpired()
    {
        return empty($this->otp_expiration) || $this->otp_expiration < now();
    }

    public function sendNewOtpCode()
    {
        // No matter what had been sent we will regenerate the code on each next request.
        $this->otp_code = $this->generateOtpCode();
        $this->otp_expiration = now()->addMinutes(20);
        $this->save();

        $this->notify(new AuthOtpCode());
    }

    public function resetOtpCode()
    {
        $this->otp_code = null;
        $this->otp_expiration = null;
        $this->save();
    }

    /**
     * Verify if provided code is valid and not expired!
     *
     * @param $code
     * @return bool
     */
    public function verifyOtpCode($code): bool
    {
        return $this->otp_code === $code && $this->otp_expiration >= now();
    }

    public function limitations(): HasOne
    {
        return $this->hasOne(UserLimitation::class);
    }

    /**
     * Get user favourites folder
     */
    public function favouriteFolders(): BelongsToMany
    {
        return $this->belongsToMany(Folder::class, 'favourite_folder', 'user_id', 'parent_id', 'id', 'id')
            ->where('team_folder', false);
    }

    /**
     * Get all user files
     */
    public function filesWithTrashed(): HasMany
    {
        return $this->hasMany(File::class)
            ->withTrashed();
    }

    /**
     * Get 5 latest uploads
     */
    public function latestUploads(): HasMany
    {
        return $this->hasMany(File::class)
            ->with([
                'parent:id,name',
                'shared:token,id,item_id,permission,is_protected,expire_in,allow_showpassword_in',
            ]);
    }

    /**
     * Get all user files
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public function traffics(): HasMany
    {
        return $this->hasMany(Traffic::class);
    }

    public function uploadRequest(): HasOne
    {
        return $this->hasOne(UploadRequest::class);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }

    public function __call($method, $parameters)
    {
        try {
            if (str_starts_with($method, 'can') || str_starts_with($method, 'get')) {
                return resolve(RestrictionsManager::class)
                    ->driver()
                    ->$method($this, ...$parameters);
            }
        } catch (BadMethodCallException $e) {
            return parent::__call($method, $parameters);
        }

        return parent::__call($method, $parameters);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->id = Str::uuid();

            // Create default limitations
            $user->limitations()->create([
                'max_storage_amount' => get_settings('default_max_storage_amount') ?? 1,
                'max_team_members'   => get_settings('default_max_team_member') ?? 10,
            ]);

            // Create user directory for his files
            Storage::makeDirectory("files/$user->id");
        });

        static::updating(function ($user) {
            // Prevent to set 2fa in demo mode
            if (config('vuefilemanager.is_demo') && $user->email === 'howdy@hi5ve') {
                $user->two_factor_secret = null;
                $user->two_factor_recovery_codes = null;
            }
        });
    }
}