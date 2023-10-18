<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalPartialLeadSetting extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'enabled',
        'user_id',
        'consent_type',
        'limit_reached'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
