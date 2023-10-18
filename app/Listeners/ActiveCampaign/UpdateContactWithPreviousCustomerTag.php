<?php

namespace App\Listeners\ActiveCampaign;

use App\Enums\PlansEnum;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\UserLoggedIn;
use App\Services\ActiveCampaign;
use App\User;
use Carbon\Carbon;
use Log;

class UpdateContactWithPreviousCustomerTag
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
    public function handle(UserLoggedIn $event)
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

        $response = $this->activeCampaign->contactTags($contactId);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $tag = json_decode($response->getBody(), true);

        if (
            empty($tag) ||
            empty($tag['contactTags']) ||
            count($tag['contactTags']) === 0
        ) {
            return null;
        }

        $tagId =  intval($tag['contactTags'][0]['tag']);

        if ($tagId == config('leadgen.active_campaign_pro_paused_tag_id')) {
            $subsriptionDate = $user->getPaddleSubscription();

            $now  = Carbon::now();
            $end  = $subsriptionDate['updated_at'];

            $dataeDiffrence = $now->diffInDays($end);

            if ($dataeDiffrence > 30) {
                $this->removeProPausedTag($contactId, $tagId);

                $this->applyPreviousCustomerTag($contactId, $user);
            }
        } else {
            return null;
        }
        if ($tagId == config('leadgen.active_campaign_scale_paused_tag_id')) {
            $subsriptionDate = $user->getPaddleSubscription();

            $now  = Carbon::now();
            $end  = $subsriptionDate['updated_at'];

            $dataeDiffrence = $now->diffInDays($end);

            if ($dataeDiffrence > 30) {
                $this->removeScalePausedTag($contactId, $tagId);

                $this->applyPreviousCustomerTag($contactId, $user);
            }
        } else {
            return null;
        }
        if ($tagId == config('leadgen.active_campaign_pro_trial_tag_id')) {
            $userPlan = $user->plan();
            if ($userPlan->isFreeTrialPlan() && !$userPlan->in_trial) {
                $this->removeProTrialTag($contactId, $tagId);
            }

            $subsriptionDate = $user->getPaddleSubscription();
            $now  = Carbon::now();
            $end  = $subsriptionDate['updated_at'];

            $dataeDiffrence = $now->diffInDays($end);

            if ($dataeDiffrence > 14) {
                $this->removeProTrialTag($contactId, $tagId);

                $this->applyProTag($contactId, $user);
            }
        } else {
            return null;
        }
        if ($tagId == config('leadgen.active_campaign_pro_trial_cancelled_tag_id')) {
            $subsriptionDate = $user->getPaddleSubscription();

            $now  = Carbon::now();
            $end  = $subsriptionDate['updated_at'];

            $dataeDiffrence = $now->diffInDays($end);

            if ($dataeDiffrence >= 0) {
                $this->removeProTrialTag($contactId, $tagId);

                $this->applyProCancelledTag($contactId, $user);
            }
        } else {
            return null;
        }
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
     * Remove free tag in Active campaign.
     *
     * @param integer $contactId Active campaign contact Id.
     * @return void
     */
    private function removeProPausedTag(int $contactId, int $tagId)
    {
        $contactTagId = $this->activeCampaign->getContactTagId($tagId, $contactId);

        if (is_null($contactTagId)) {
            return;
        }

        $this->activeCampaign->removeTag($contactTagId);
    }
      /**
     * Remove free tag in Active campaign.
     *
     * @param integer $contactId Active campaign contact Id.
     * @return void
     */
    private function removeScalePausedTag(int $contactId, int $tagId)
    {
        $contactTagId = $this->activeCampaign->getContactTagId($tagId, $contactId);

        if (is_null($contactTagId)) {
            return;
        }

        $this->activeCampaign->removeTag($contactTagId);
    }
      /**
     * Apply previousCustomer tag.
     *
     * @param integer $contactId Active campaign Contact Id.
     * @return void
     */
    private function applyPreviousCustomerTag(int $contactId, User $user)
    {
        $tagId = intval(config('leadgen.active_campaign_previous_customer_tag_id'));

        $this->activeCampaign->applyTag($tagId, $contactId);
    }
    /**
     * Remove Pro tag in Active campaign.
     *
     * @param integer $contactId Active campaign contact Id.
     * @return void
     */
    private function removeProTrialTag(int $contactId, User $user)
    {
        $tagId = intval(config('leadgen.active_campaign_pro_trial_tag_id'));

        $contactTagId = $this->activeCampaign->getContactTagId($tagId, $contactId);

        if (is_null($contactTagId)) {
            return;
        }

        $this->activeCampaign->removeTag($contactTagId);
    }
     /**
     * Apply ProTrialCancelled tag.
     *
     * @param integer $contactId Active campaign Contact Id.
     * @return void
     */
    private function applyProCancelledTag(int $contactId, User $user)
    {
        $tagId = intval(config('leadgen.active_campaign_free_tag_id'));

        $this->activeCampaign->applyTag($tagId, $contactId);
    }
    /**
     * Apply pro tag.
     *
     * @param integer $contactId Active campaign Contact Id.
     * @return void
     */
    private function applyProTag(int $contactId, User $user)
    {
        $tagId = intval(config('leadgen.active_campaign_pro_tag_id'));

        $this->activeCampaign->applyTag($tagId, $contactId);
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
}
