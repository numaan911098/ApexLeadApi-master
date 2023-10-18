<?php

namespace App\Listeners\Paddle;

use App\PaddleSubscription;
use App\User;
use App\Plan;
use App\Enums\Paddle\PaddleAlertTypesEnum;
use Log;

class BaseListener
{

    protected $user;

    protected $plan;

    protected $metadata;

    protected function validateWebhook($event, string $type): bool
    {
        if ($event->alert_name !== $type) {
            return false;
        }

        if (empty($event->passthrough)) {
            return false;
        }

        $this->metadata = $event->passthrough;

        if (!is_array($this->metadata) || empty($this->metadata['user_id'])) {
            return false;
        }

        $this->user = User::find($this->metadata['user_id']);

        if (empty($this->user)) {
            return false;
        }

        $this->plan = Plan::where('paddle_plan_id', $event->subscription_plan_id)->first();

        if (empty($this->plan)) {
            return false;
        }

        return true;
    }
}
