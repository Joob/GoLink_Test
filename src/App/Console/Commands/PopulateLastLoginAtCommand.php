<?php

namespace App\Console\Commands;

use App\Users\Models\User;
use Illuminate\Console\Command;

class PopulateLastLoginAtCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:populate-last-login 
                            {--dry-run : Show what would be updated without making changes}
                            {--force : Run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate null last_login_at values with created_at date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Populating last_login_at for existing users...');
        $this->newLine();

        // Get users with null last_login_at
        $usersToUpdate = User::whereNull('last_login_at')->get();
        
        if ($usersToUpdate->isEmpty()) {
            $this->info('✅ No users need updating - all have last_login_at values');
            return;
        }

        $count = $usersToUpdate->count();
        $this->info("📊 Found {$count} users with null last_login_at values");

        if ($this->option('dry-run')) {
            $this->info('🔍 DRY RUN - showing what would be updated:');
            $this->newLine();
            
            foreach ($usersToUpdate->take(10) as $user) {
                $this->line("   User: {$user->email} - would set last_login_at to: {$user->created_at}");
            }
            
            if ($count > 10) {
                $this->line("   ... and " . ($count - 10) . " more users");
            }
            
            $this->newLine();
            $this->info('💡 Run without --dry-run to apply changes');
            return;
        }

        // Confirm before making changes
        if (!$this->option('force') && !$this->confirm("Update {$count} users' last_login_at to their created_at date?")) {
            $this->info('❌ Operation cancelled');
            return;
        }

        // Update users
        $this->info('🚀 Updating users...');
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $updated = 0;
        foreach ($usersToUpdate as $user) {
            try {
                $user->update(['last_login_at' => $user->created_at]);
                $updated++;
            } catch (\Exception $e) {
                $this->error("Failed to update user {$user->email}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Successfully updated {$updated} out of {$count} users");
        
        if ($updated < $count) {
            $this->warn("⚠️  " . ($count - $updated) . " users failed to update - check logs for details");
        }

        $this->newLine();
        $this->info('🎯 Next steps:');
        $this->info('   1. Test the admin panel to see if last_login_at values appear');
        $this->info('   2. Test login functionality to ensure new logins update the field');
        $this->info('   3. Check application logs for any authentication errors');
    }
}