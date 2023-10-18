<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandingPage extends Model
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
        'keywords',
        'description',
        'config',
        'created_by',
        'landing_page_template_id',
        'slug'
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

    public function landingPageTemplate()
    {
        return $this->belongsTo('App\LandingPageTemplate');
    }
}
