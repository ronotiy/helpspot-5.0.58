<?php

namespace HS\Console;

use HS\Console\Commands\AutoRules;
use HS\Console\Commands\CreateRecurringRequest;
use HS\Console\Commands\EmptyTrash;
use HS\Console\Commands\CollectMail;
use HS\Console\Commands\EmailReports;
use HS\Console\Commands\CleanErrorLog;
use HS\Console\Commands\CheckReminders;
use HS\Console\Commands\DeleteSpamCommand;
use HS\Console\Commands\CollectMetaCommand;
use HS\Console\Commands\DeleteLoginHistory;
use HS\Console\Commands\CacheFiltersCommand;
use HS\Console\Commands\MailViewCacheCommand;
use HS\Console\Commands\CleanFilterPerformance;
use HS\Console\Commands\GetThermostatResponses;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /* @var DeleteSpamCommand::class */
        $schedule->command('request:delete-spam --force')
            ->monthly()
            ->withoutOverlapping();

        $schedule->command(DeleteLoginHistory::class)
            ->daily();

        $schedule->command(CacheFiltersCommand::class)
            ->daily();

        $schedule->command(CleanErrorLog::class)
            ->daily(); // Deletes older than 60 days

        $schedule->command(CleanFilterPerformance::class)
            ->daily(); // Deletes older than 15 days

        $schedule->command(DeleteLoginHistory::class)
            ->daily(); // Deletes older the 3 months

        $schedule->command(EmptyTrash::class)
            ->daily(); // Deletes older than cHD_DAYS_TO_LEAVE_TRASH

        $schedule->command(CollectMetaCommand::class)
            ->daily();

        $schedule->command(EmailReports::class)
            ->everyFifteenMinutes() // Emails based on reports schedule (increments of 15 minutes)
            ->withoutOverlapping();

        $schedule->command(MailViewCacheCommand::class)
            ->everyFiveMinutes();

        $schedule->command(CheckReminders::class)
            ->everyMinute() // Emails based on reminders schedule
            ->withoutOverlapping();

        // Note: Doesn't poll if no API key set
        $schedule->command(GetThermostatResponses::class)
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command(CollectMail::class)
            ->cron(config('helpspot.mail_cron_interval', '* * * * *'))
            ->withoutOverlapping();

        $schedule->command(AutoRules::class)
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command(CreateRecurringRequest::class)
            ->everyFifteenMinutes()
            ->withoutOverlapping();

        $schedule->command('cache:clear')
            ->daily();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
