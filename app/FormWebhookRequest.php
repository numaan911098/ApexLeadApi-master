<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FormWebhookRequest extends Model
{
    /**
    * attributes that are mass assignable
     *
     * @var array
     *
     */
    protected $fillable = [
        'form_lead_id',
        'form_webhook_id',
        'response_status',
        'response_content',
        'error',
        'error_message',
        'payload',
        'form_variant_id',
    ];
}
