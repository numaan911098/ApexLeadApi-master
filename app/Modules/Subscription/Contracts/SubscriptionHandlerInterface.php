<?php

namespace App\Modules\Subscription\Contracts;

interface SubscriptionHandlerInterface
{
    /**
     * Pause current subscription.
     *
     * @param integer $id Subscription id.
     */
    public function pause($id);

    /**
     * Resume paused subscription.
     *
     * @param integer $id Subscription id.
     */
    public function resume($id);

    /**
     * Cancel subscription.
     *
     * @param integer $id Subscription id.
     */
    public function cancel($id);

    /**
     * Past due subscription.
     *
     * @param integer $id Subscription id.
     */
    public function getPastDueUser($id);

    /**
     * List Payment subscription.
     *
     * @param integer $id Subscription id.
     */
    public function listPayment($id);

    /**
     *Rreschedule subscription details.
     *
     * @param integer $id Subscription id.
     * @param integer $id payment id.
     */
    public function reschedulePayment($id, $paymentId);

    /**
     * List Transaction.
     *
     * @param integer $id Subscription id.
     */
    public function listTransaction($id);
}
