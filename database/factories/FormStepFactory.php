<?php

namespace Database\Factories;

use App\{Form, FormVariant, FormStep};
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\QuestionTypesEnum;

class FormStepFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FormStep::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        static $number = 0;

        return [
            'number' => $number++,
            'form_id' => function () {
                return Form::factory()->create()->id;
            },
            'form_variant_id' => function () {
                return FormVariant::factory()->create()->id;
            }
        ];
    }
}
