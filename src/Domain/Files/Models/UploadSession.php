<?php
namespace Domain\Files\Models;

use App\Users\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string $filename
 * @property int $filesize
 * @property string|null $mimetype
 * @property string|null $parent_id
 * @property string|null $path
 * @property int $chunks_uploaded
 * @property int $total_chunks
 * @property Carbon $expires_at
 * @property string $status
 * @property string|null $file_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class UploadSession extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'filename',
        'filesize',
        'mimetype',
        'parent_id',
        'path',
        'chunks_uploaded',
        'total_chunks',
        'expires_at',
        'status',
        'file_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'filesize' => 'integer',
        'chunks_uploaded' => 'integer',
        'total_chunks' => 'integer',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_chunks === 0) {
            return 0;
        }

        return ($this->chunks_uploaded / $this->total_chunks) * 100;
    }
}