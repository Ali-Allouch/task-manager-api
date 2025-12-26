<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ListTasksPerUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:count';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all users with their total number of tasks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::withCount('tasks')->get();

        if ($users->isEmpty()) {
            $this->warn('No users found in the system.');
            return;
        }

        $this->newLine();
        $this->info('--- User Task Statistics Report ---');

        $headers = ['User Name', 'Email', 'Tasks Count'];
        $data = $users->map(fn($user) => [
            $user->name,
            $user->email,
            $user->tasks_count
        ]);

        $this->table($headers, $data);

        $this->info('Done!');
        $this->newLine();
    }
}
