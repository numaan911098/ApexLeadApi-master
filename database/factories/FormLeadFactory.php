<?php

namespace Database\Factories;

use App\FormLead;
use App\{Form, FormExperiment, FormVariant, FormVisit};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FormLeadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FormLead::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'reference_no' => Str::random(32),
            'form_variant_id' =>  function () {
                return FormVariant::factory()->create()->id;
            },
            'form_visit_id' => function () {
                FormVisit::factory()->create()->id;
            },
            'form_id' => function () {
                return Form::factory()->create()->id;
            },
            'form_experiment_id' => function () {
                return FormExperiment::factory()->create()->id;
            },
        ];
    }
}
