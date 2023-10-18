<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeadProof extends Model
{
    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'title',
        'ref_id',
        'description',
        'count',
        'delay',
        'show_firstpart_only',
        'show_timestamp',
        'show_country',
        'latest',
        'form_variant_id',
        'form_question_id',
    ];

    public function formVariant()
    {
        return $this->belongsTo('App\FormVariant');
    }
}
