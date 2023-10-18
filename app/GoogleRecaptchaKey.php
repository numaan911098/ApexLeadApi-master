<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoogleRecaptchaKey extends Model
{
    use SoftDeletes;

    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'title',
        'site_key',
        'secret_key',
        'type',
        'created_by'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function scopeKeysExist(
        $query,
        $site_key = '',
        $secret_key = '',
        array $ignore = []
    ) {
        return $query->whereNotIn('id', $ignore)
        ->where('site_key', $site_key)
        ->OrWhere('secret_key', $secret_key)
        ->count() > 0;
    }

    public function formSettings()
    {
        return $this->hasMany('App\FormSetting');
    }

    public function formCount()
    {
        return $this->formSettings->count();
    }
}
