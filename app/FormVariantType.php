<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Enums\FormVariantTypesEnum;

class FormVariantType extends Model
{
    /**
     * attributes that are mass assignable
     * @var array
     */
    protected $fillable = [
        'type'
    ];

    public function champion()
    {
        return $this->where('type', FormVariantTypesEnum::CHAMPION)->first();
    }

    public function challenger()
    {
        return $this->where('type', FormVariantTypesEnum::CHALLENGER)->first();
    }
}
