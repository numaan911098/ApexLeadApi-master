<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\User;
use App\Services\ActiveCampaign;
use Exception;
use Sentry;
use App\Enums\Paddle\PaddleSubscriptionStatusEnum;
use Carbon\Carbon;

class SyncActiveCampaignTagsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var User
     */
    protected $user;

    /**
     * ActiveCampaign instance.
     *
     * @var ActiveCampaign
     */
    protected $activeCampaign;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user =  $user;
    }

    /**
     * Execute the job.
     *
     * @param  App\Services\ActiveCampaign  $activeCampaign
     * @return void
     */
    public function handle(ActiveCampaign $activeCampaign)
    {
        $this->activeCampaign = $activeCampaign;
        $userPlan = $this->user->plan();
        $subscription = $this->user->getSubscription();

        if (($userPlan->isFreeTrialPlan()) && !$userPlan->in_trial) {
            $userPlanTagId = config('leadgen.active_campaign_plan_trial_end');
        } elseif ($this->user->hasSubscriptionOnGracePeriod()) {
            if (
                $userPlan->isProTypePlan() &&
                ($subscription->status === PaddleSubscriptionStatusEnum::PAUSED)
            ) {
                $userPlanTagId = config('leadgen.active_campaign_pro_paused_tag_id');
            } elseif (
                $userPlan->isScaleTypePlan() &&
                ($subscription->status === PaddleSubscriptionStatusEnum::PAUSED)
            ) {
                $userPlanTagId = config('leadgen.active_campaign_scale_paused_tag_id');
            } else {
                $userPlanTagId = config('leadgen.active_campaign_plan_cancelled');
            }
        } elseif ((!empty($subscription)) && ($userPlan->isProTrialPlan())) {
            $dateDiffrence = Carbon::now()->diffInDays(Carbon::parse($subscription->updated_at), true);
            if ($dateDiffrence > 14) {
                $userPlanTagId = config('leadgen.active_campaign_pro_tag_id');
            } else {
                $userPlanTagId = $userPlan->tag_id;
            }
        } else {
            $userPlanTagId = $userPlan->tag_id;
        }

        $getContactId = $this->getContactId($this->user);

        $getCurrentTagId = $this->getCurrentTagId($this->user);

        if (isset($userPlanTagId) && isset($getContactId) && isset($getCurrentTagId)) {
            //remove tag first
            $this->removeCurrentTag($getCurrentTagId, $getContactId);
            //apply tag
            $this->activeCampaign->applyTag($userPlanTagId, $getContactId);
        }
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed($exception)
    {
        Sentry\captureException($exception);
    }

    /**
     * Get Active Campaign Contact Id.
     */
    private function getContactId($user): ?int
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
     * Get Active Campaign User's Current Tag Id.
     */
    private function getCurrentTagId($user): ?int
    {
        $enabled = config('leadgen.active_campaign_enabled');

        $userNewsletter = $user->newsletter()->where('user_id', $user->id)->first();
        if (!$enabled || !$userNewsletter) {
            return null;
        }

        $contactId = $this->getContactId($user);

        if (is_null($contactId)) {
            return null;
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

        return intval($tag['contactTags'][0]['tag']);
    }


    /**
     * Remove Active Campaign User's Current Tag.
     */
    private function removeCurrentTag(int $tagId, int $contactId)
    {
        $contactTagId = $this->activeCampaign->getContactTagId($tagId, $contactId);

        if (is_null($contactTagId)) {
            return;
        }

        $this->activeCampaign->removeTag($contactTagId);
    }
}
