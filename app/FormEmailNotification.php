<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormEmailNotification extends Model
{
    use HasFactory;

    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'subject',
        'to',
        'cc',
        'bcc',
        'from_name',
        'form_id',
        'reply_to',
    ];

    public function form()
    {
        return $this->belongsTo('App\Form');
    }

    public function toArr()
    {
        if (empty($this->to)) {
            return [];
        }

        return explode(',', $this->to);
    }

    public function ccArr()
    {
        if (empty($this->cc)) {
            return [];
        }

        return explode(',', $this->cc);
    }

    public function bccArr()
    {
        if (empty($this->bcc)) {
            return [];
        }

        return explode(',', $this->bcc);
    }
}
