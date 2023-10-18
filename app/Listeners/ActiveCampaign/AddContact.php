<?php

namespace App\Listeners\ActiveCampaign;

use App\Events\UserRegistered;
use App\Events\OneToolUserRegistered;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\ActiveCampaign;
use App\User;

class AddContact
{
    /**
     * ActiveCampaign instance.
     *
     * @var ActiveCampaign
     */
    protected $activeCampaign;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(ActiveCampaign $activeCampaign)
    {
        $this->activeCampaign = $activeCampaign;
    }

    /**
     * Handle the event.
     *
     * @param  UserRegistered  $event
     * @return void
     */
    public function handle($event)
    {
        $this->addContact($event->user);
    }

    /**
     * Add Contact to Active Campaign.
     *
     * @param User $user
     * @return void
     */
    private function addContact(User $user)
    {
        $enabled = config('leadgen.active_campaign_enabled');
        $tagId = config('leadgen.active_campaign_free_tag_id');
        $tagIdProLiteTrial = config('leadgen.active_campaign_onetool_pro_lite_trial_tag_id');
        $tagIdProLite = config('leadgen.active_campaign_onetool_pro_lite_tag_id');

        $userNewsletter = $user->newsletter()->where('user_id', $user->id)->first();
        if (!$enabled || !$userNewsletter) {
            return;
        }

        $response = $this->activeCampaign->addContact($user);

        if ($response->getStatusCode() !== 201) {
            return;
        }

        $contacts = json_decode($response->getBody(), true);

        if (empty($contacts) || count($contacts) === 0) {
            return;
        }

        $contactId = intval($contacts['contact']['id']);
        $userPlan = $user->plan();

        if ($user->isOneToolUser() && $user->active && $user->oneToolUser->in_trial) {
            $this->activeCampaign->applyTag($tagId, $contactId);
            $this->activeCampaign->applyTag($tagIdProLiteTrial, $contactId);
        } elseif ($user->isOneToolUser() && $user->active && !$user->oneToolUser->in_trial) {
            $this->activeCampaign->applyTag($tagIdProLite, $contactId);
        } else {
            $this->activeCampaign->applyTag($userPlan->tag_id, $contactId);
        }
    }
}
