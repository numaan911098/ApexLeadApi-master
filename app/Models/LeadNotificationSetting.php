<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadNotificationSetting extends Model
{
    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'enabled',
        'user_id',
        'notification_frequency'
    ];
}
