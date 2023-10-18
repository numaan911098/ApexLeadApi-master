<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingData extends Model
{
    use HasFactory;

    protected $fillable = [
        'industry',
        'role',
        'goal',
        'first_heard',
        'user_id',
    ];
}
