<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandingPageTemplate extends Model
{
    use SoftDeletes;

    /**
    * attributes that are mass assignable
     *
     * @var array
     *
     */
    protected $fillable = [
        'title',
        'config',
        'code'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function getConfigAttribute($value)
    {
        return json_decode($value, true);
    }
}
