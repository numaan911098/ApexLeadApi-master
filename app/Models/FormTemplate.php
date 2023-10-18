<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
use App\Form;
use App\User;
use App\FormStep;
use App\Models\FormTemplateCategory;
use App\Models\FormTemplateIndustry;
use Facades\App\Services\Util;
use Illuminate\Support\Facades\Log;
use App\FormEmailNotification;
use App\Http\Controllers\FormTemplateIndustryController;
use App\Media;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FormTemplate extends Model
{
    use HasFactory;


    protected $fillable = [
        'title',
        'description',
        'ref_id',
        'form_id',
        'media_id',
        'from_user_id',
        'form_variant_id'
    ];

    /**
     * Get the form steps associated with the template.
     */
    public function templateSteps()
    {
        return $this->hasMany(FormStep::class, 'form_id', 'form_id');
    }

    /**
     * Get the form industries associated with the template.
     */
    public function templateIndustries()
    {
        return $this->hasManyThrough(
            FormTemplateIndustry::class,
            FormTemplateIndustryFormTemplate::class,
            'form_template_id',
            'id',
            'id',
            'form_industry_id'
        );
    }

    /**
     * Get the form categories associated with the template.
     */
    public function templateCategories()
    {
        return $this->hasManyThrough(
            FormTemplateCategory::class,
            FormTemplateCategoryFormTemplate::class,
            'form_template_id',
            'id',
            'id',
            'form_category_id'
        );
    }

    /**
     * Get the form primary category associated with the template.
     */
    public function primaryCategory()
    {
        return $this->hasOne(FormTemplateCategoryFormTemplate::class)->where('is_primary_category', '=', true);
    }

    /**
     * Get the form image associated with the template.
     */
    public function templateImage()
    {
        return $this->hasOne(Media::class, 'id', 'media_id');
    }

    /*
    * Get the form associated with the template.
    */
    public function templateForm(): HasOne
    {
        return $this->hasOne(Form::class, 'id', 'form_id');
    }

    /**
     * Get the User associated with the template.
     */
    public function formsUsers(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'from_user_id');
    }

    /**
     * Get the FormCategories associated with the template.
     */
    public function formCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            FormTemplateCategory::class,
            'form_template_category_form_templates',
            'form_template_id',
            'form_category_id'
        )->withPivot('is_primary_category');
    }

    /**
     * Get the FormCategories associated with the template.
     */
    public function formIndustries(): BelongsToMany
    {
        return $this->belongsToMany(
            FormTemplateIndustry::class,
            'form_template_industry_form_templates',
            'form_template_id',
            'form_industry_id'
        );
    }

    /**
     * Get the Media associated with the template.
     */
    public function image(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'media_id');
    }
}
