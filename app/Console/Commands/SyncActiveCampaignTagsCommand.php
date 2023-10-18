<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncActiveCampaignTagsJob;
use App\User;
use Illuminate\Support\Facades\App;

class SyncActiveCampaignTagsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leadgen:syncActiveCampaignTags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update ActiveCampaign Tags after every hour';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = App::make(User::class);
        $allUsers = $users->all();
        foreach ($allUsers as $user) {
            dispatch(new SyncActiveCampaignTagsJob($user));
        }
    }
}
