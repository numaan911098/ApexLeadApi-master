<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureProperty extends Model
{
    use HasFactory;

    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'title',
        'type',
        'unit',
        'isResetPeriod',
        'feature_id',
    ];
}
