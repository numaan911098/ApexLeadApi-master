<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class UserSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_verification_enabled'
    ];

    public function isEmailVerificationEnabled()
    {
        $check = UserSettings::where('id', 1)->select('email_verification_enabled')
            ->first()->email_verification_enabled;
        return $check;
    }
}
