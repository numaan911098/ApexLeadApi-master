<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Enums\Paddle\PaddleSubscriptionStatusEnum;

class PaddleSubscription extends Model
{
    protected $dates = ['ends_at', 'paused_at', 'paused_from', 'next_bill_date'];

    protected $fillable = [
        'id',
        'user_id',
        'paddle_id',
        'paddle_plan',
        'quantity',
        'ends_at',
        'status',
        'paused_from',
        'next_bill_date',
        'currency',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function isActive()
    {
        return $this->status === PaddleSubscriptionStatusEnum::ACTIVE;
    }

    public function onTrial()
    {
        return $this->status === PaddleSubscriptionStatusEnum::TRIALING;
    }

    public function hasPastDueStatus(): bool
    {
        return $this->status === PaddleSubscriptionStatusEnum::PAST_DUE;
    }
}
