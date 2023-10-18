<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    /**
    * attributes that are mass assignable
     *
     * @var array
     *
     */
    protected $fillable = [
        'name',
        'path',
        'extension',
        'type',
        'uploaded_by',
        'public',
        'ref_id',
        'size_bytes',
    ];

    protected $perPage = 60;
}
