<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ref_id',
    ];

    public function formVisit()
    {
        return $this->hasMany('App\FormVisit', 'visitor_id');
    }
}
