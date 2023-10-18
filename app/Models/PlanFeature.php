<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanFeature extends Model
{
    use HasFactory;

    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'plan_id',
        'feature_id',
    ];
}
