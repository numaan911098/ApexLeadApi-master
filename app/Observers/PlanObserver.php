<?php

namespace App\Observers;

use App\Models\PlanLog;
use App\Plan;
use Illuminate\Support\Facades\Auth;
use Sentry;

class PlanObserver
{
    /**
     * Handle the plan "created" event.
     *
     * @param  \App\Plan  $plan
     * @return void
     */
    public function created($plan)
    {
        try {
            $createdPlan = Plan::find($plan->id);
            if (empty($createdPlan)) {
                return;
            }
            PlanLog::create([
                'to' => $createdPlan->toJson(),
                'created_by' => Auth::id()
            ]);
        } catch (\Exception $e) {
            Sentry\captureException($e);
        }
    }

    /**
     * Handle the plan "updated" event.
     *
     * @param  \App\Plan  $plan
     * @return void
     */
    public function updating(Plan $newPlan)
    {
        try {
            $existingPlan = Plan::find($newPlan->id);
            PlanLog::create([
                'from' => $existingPlan->toJson(),
                'to' => $newPlan->toJson(),
                'created_by' => Auth::id()
            ]);
        } catch (\Exception $e) {
            Sentry\captureException($e);
        }
    }
}
