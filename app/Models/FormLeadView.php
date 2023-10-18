<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormLeadView extends Model
{
    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'user_id',
        'lead_id',
        'viewed'
    ];
}
