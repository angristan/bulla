<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Comment;
use Illuminate\Console\Command;

class ClaimAdminComments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comments:claim-admin
                            {--email= : Email address to match}
                            {--author= : Author name to match}
                            {--dry-run : Preview without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark comments as admin based on email or author name';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email');
        $author = $this->option('author');
        $dryRun = $this->option('dry-run');

        if (! $email && ! $author) {
            $this->error('Please provide --email or --author option.');

            return self::FAILURE;
        }

        $query = Comment::where('is_admin', false);

        if ($email) {
            $query->where('email', $email);
        }

        if ($author) {
            $query->where('author', $author);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No matching comments found.');

            return self::SUCCESS;
        }

        $this->info("Found {$count} comments to mark as admin.");

        if ($dryRun) {
            $this->warn('Dry run - no changes made.');
            $this->table(
                ['ID', 'Author', 'Email', 'Created'],
                $query->limit(20)->get(['id', 'author', 'email', 'created_at'])->toArray()
            );
            if ($count > 20) {
                $this->line('... and '.($count - 20).' more');
            }

            return self::SUCCESS;
        }

        if (! $this->confirm('Mark these comments as admin?')) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $updated = $query->update(['is_admin' => true]);

        $this->info("Marked {$updated} comments as admin.");

        return self::SUCCESS;
    }
}
