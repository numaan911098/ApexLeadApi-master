<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Enums\FormExperimentTypesEnum;

class FormExperimentType extends Model
{
    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'type',
    ];

    public function ab()
    {
        return $this->where('type', FormExperimentTypesEnum::AB)->first();
    }

    public function multiVariant()
    {
        return $this->where('type', FormExperimentTypesEnum::MULTI_VARIANT)->first();
    }
}
