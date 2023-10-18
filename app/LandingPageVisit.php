<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LandingPageVisit extends Model
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
        'ip',
        'device_type',
        'os',
        'browser',
        'source_url',
        'user_agent',
        'device_name',
        'robot_name',
        'is_robot'
    ];

    public function visitor()
    {
        return $this->belongsTo('App\Visitor');
    }
}
