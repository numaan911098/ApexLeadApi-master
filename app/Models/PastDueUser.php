<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PastDueUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'send_at',
        'subscription_type'
    ];

    /**
     * @param int $userId
     * @param string $subscriptionType
     * @return PastDueUser|null
     */
    public function savePastDueUser($userId, $subscriptionType)
    {
        return $this->firstOrCreate([
            'user_id' =>  $userId,
        ], [
            'send_at' =>  Carbon::now()->addDays(config('leadgen.past_due_user.email')),
            'subscription_type' => $subscriptionType
        ]);
    }

    /**
     * @param int $userId
     * @return PastDueUser|null
     */
    public function getSavedPastDueUser($userId)
    {
        return $this->where('user_id', $userId)->first();
    }

    /**
     * @param int $userId
     * @return boolean
     */
    public function deletePastDueUser($userId): bool
    {
        return $this->where('user_id', $userId)->delete();
    }
}
