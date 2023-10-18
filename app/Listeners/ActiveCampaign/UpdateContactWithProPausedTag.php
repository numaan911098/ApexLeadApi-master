<?php

namespace App\Listeners\ActiveCampaign;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\ProUserCancelled;
use App\Services\ActiveCampaign;
use App\User;
use Log;

class UpdateContactWithProPausedTag
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
    public function handle(ProUserCancelled $event)
    {
        $this->updateContactTag($event->user);
    }
    /**
     * Update Contact to Pro and remove Free tag in Active Campaign.
     *
     * @param User $user
     * @return void
     */
    private function updateContactTag(User $user)
    {
        $enabled = config('leadgen.active_campaign_enabled');

        if (!$enabled || !$user->subscribe_newsletter) {
            return;
        }

        $contactId = $this->getContactId($user);

        if (is_null($contactId)) {
            return;
        }

        $this->removeProTag($contactId, $user);

        $this->applyProPausedTag($contactId, $user);
    }
    /**
     * Get Active Campaign Contact Id.
     *
     * @param User $user User instance.
     * @return integer|null
     */
    private function getContactId(User $user): ?int
    {
        $response = $this->activeCampaign->getContacts([
            'email' => $user->email,
        ]);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $contacts = json_decode($response->getBody(), true);

        if (
            empty($contacts) ||
            empty($contacts['contacts']) ||
            count($contacts['contacts']) === 0
        ) {
            return null;
        }

        return intval($contacts['contacts'][0]['id']);
    }
    /**
     * Remove Pro tag in Active campaign.
     *
     * @param integer $contactId Active campaign contact Id.
     * @return void
     */
    private function removeProTag(int $contactId, User $user)
    {
        $tagId = intval(config('leadgen.active_campaign_pro_tag_id'));

        $contactTagId = $this->activeCampaign->getContactTagId($tagId, $contactId);

        if (is_null($contactTagId)) {
            return;
        }

        $this->activeCampaign->removeTag($contactTagId);
    }
     /**
     * Apply ProPaused tag.
     *
     * @param integer $contactId Active campaign Contact Id.
     * @return void
     */
    private function applyProPausedTag(int $contactId, User $user)
    {
        $tagId = intval(config('leadgen.active_campaign_pro_paused_tag_id'));

        $this->activeCampaign->applyTag($tagId, $contactId);
    }
}
