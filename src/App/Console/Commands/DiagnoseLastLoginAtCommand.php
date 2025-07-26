<?php

namespace App\Console\Commands;

use App\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DiagnoseLastLoginAtCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:diagnose-last-login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose last_login_at field issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Diagnosing last_login_at field...');
        $this->newLine();

        // Check 1: Column exists in database
        $this->info('1. Checking if last_login_at column exists...');
        if (Schema::hasColumn('users', 'last_login_at')) {
            $this->info('   ✅ Column exists');
        } else {
            $this->error('   ❌ Column does not exist - migration needs to be run');
            return;
        }

        // Check 2: User model configuration
        $this->info('2. Checking User model configuration...');
        $user = new User();
        
        if (in_array('last_login_at', $user->getFillable())) {
            $this->info('   ✅ last_login_at is fillable');
        } else {
            $this->error('   ❌ last_login_at is not fillable');
        }

        if (in_array('last_login_at', $user->sortable)) {
            $this->info('   ✅ last_login_at is sortable');
        } else {
            $this->error('   ❌ last_login_at is not sortable');
        }

        // Check 3: Current data
        $this->info('3. Checking current user data...');
        $totalUsers = User::count();
        $usersWithLastLogin = User::whereNotNull('last_login_at')->count();
        $usersWithNullLastLogin = User::whereNull('last_login_at')->count();

        $this->info("   📊 Total users: {$totalUsers}");
        $this->info("   📊 Users with last_login_at: {$usersWithLastLogin}");
        $this->info("   📊 Users with null last_login_at: {$usersWithNullLastLogin}");

        if ($usersWithNullLastLogin > 0) {
            $this->warn("   ⚠️  {$usersWithNullLastLogin} users have null last_login_at values");
            $this->warn("   💡 These users need to log in again to populate the field");
        }

        // Check 4: Recent login attempts
        $this->info('4. Checking recent login activity...');
        $recentLogins = User::whereNotNull('last_login_at')
            ->where('last_login_at', '>=', now()->subDays(7))
            ->count();
        
        $this->info("   📈 Users with logins in last 7 days: {$recentLogins}");

        $this->newLine();
        $this->info('🎯 Recommendations:');
        
        if ($usersWithNullLastLogin > 0) {
            $this->info('   1. Users with null values need to log in again to populate last_login_at');
            $this->info('   2. Consider running a one-time update to set created_at as default value');
            $this->info('   3. Monitor logs during login to ensure updates are working');
        }

        if ($recentLogins === 0) {
            $this->info('   1. Test login functionality to ensure last_login_at is being updated');
            $this->info('   2. Check application logs for authentication activity');
        }

        $this->newLine();
        $this->info('✅ Diagnosis complete');
    }
}