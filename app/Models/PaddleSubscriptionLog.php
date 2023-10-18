<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaddleSubscriptionLog extends Model
{
    /**
     * attributes that are mass assignable
     *
     * @var array
     *
     */
    protected $fillable = [
        'from',
        'to',
        'created_by'
    ];
}
