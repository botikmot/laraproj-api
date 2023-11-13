<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Events\UserInactive;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $timeoutThreshold = now()->subMinutes(1);//->subMinutes(5); // Adjust the timeout threshold as needed
            $users = User::where('last_seen', '<', $timeoutThreshold)->get();
            foreach ($users as $user) {
                $user->update(['last_seen' => null,'online' => false]);
                $user->load('profile');
                event(new UserInactive($user, 'offline'));
            }
        })->everyMinute(); //->everyFiveMinutes(); // Adjust the frequency as needed
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
