<?php

namespace App\Models;

use App\Plan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Package extends Model
{
    use HasFactory;

    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'plan_id'
    ];

    /**
     * get plan details associated with particular package
     * @return BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
