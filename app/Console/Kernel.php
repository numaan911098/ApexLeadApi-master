<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SyncActiveCampaignTagsCommand::class,
        Commands\DeleteInactiveAccountsCommand::class,
        Commands\MailPastDueUsersCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('leadgen:report')
            ->dailyAt('21:00')
            ->timezone('Asia/Calcutta');

        $schedule->command('leadgen:syncActiveCampaignTags')->withoutOverlapping()
            ->twiceDaily('10:00', '22:00')
            ->timezone('Asia/Calcutta');

        $schedule->command('leadgen:deleteInactiveAccounts')->withoutOverlapping()->daily();

        $schedule->command('leadgen:deleteArchivedAccounts')->withoutOverlapping()->daily();

        $schedule->command('leadgen:mailPastDueUsers')->withoutOverlapping()->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
