<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLimitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lead_limit_reached',
        'lead_limit_email_sent'
    ];
}
