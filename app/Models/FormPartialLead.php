<?php

namespace App\Models;

use App\Form;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormPartialLead extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'enabled',
        'form_id',
        'consent_type'
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
