<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FormQuestionType extends Model
{
    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'type',
    ];
}
