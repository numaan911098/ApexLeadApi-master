<?php

namespace App\Observers;

use App\Models\PaddleSubscriptionLog;
use App\PaddleSubscription;
use Illuminate\Support\Facades\Auth;
use Sentry;

class PaddleSubscriptionObserver
{
    /**
     * Handle the paddle subscription "created" event.
     *
     * @param  \App\PaddleSubscription  $paddleSubscription
     * @return void
     */
    public function created(PaddleSubscription $paddleSubscription)
    {
        try {
            $createdPaddleSubscription = PaddleSubscription::find($paddleSubscription->id);
            if (empty($createdPaddleSubscription)) {
                return;
            }
            PaddleSubscriptionLog::create([
                'to' => $createdPaddleSubscription->toJson(),
                'created_by' => Auth::id()
            ]);
        } catch (\Exception $e) {
            Sentry\captureException($e);
        }
    }

    /**
     * Handle the paddle subscription "updating" event.
     *
     * @param  \App\PaddleSubscription  $paddleSubscription
     * @return void
     */
    public function updating(PaddleSubscription $newPaddleSubscription)
    {
        try {
            $existingPaddleSubscription = PaddleSubscription::find($newPaddleSubscription->id);
            PaddleSubscriptionLog::create([
                'from' => $existingPaddleSubscription->toJson(),
                'to' => $newPaddleSubscription->toJson(),
                'created_by' => Auth::id()
            ]);
        } catch (\Exception $e) {
            Sentry\captureException($e);
        }
    }
}
