<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\FeatureProperty;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feature extends Model
{
    use HasFactory;

    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'title',
        'slug',
    ];

    /**
     * Get feature properties.
     *
     * @return HasMany
     */
    public function featureProperties(): HasMany
    {
        return $this->hasMany(FeatureProperty::class, 'feature_id');
    }
}
