<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LandingPageOptin extends Model
{
    /**
    * attributes that are mass assignable
     *
     * @var array
     *
     */
    protected $fillable = [
        'visitor_id',
        'landing_page_id',
        'landing_page_visit_id',
        'form_lead_id',
    ];
}
