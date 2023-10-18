<?php

namespace Database\Factories;

use App\{Form, FormVariant, FormVariantType};
use Illuminate\Database\Eloquent\Factories\Factory;

class FormVariantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FormVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence(2),
            'form_id' => function () {
                return Form::factory()->create()->id;
            },
            'form_variant_type_id' => function () {
                return FormVariantType::inRandomOrder()->first()->id;
            }
        ];
    }
}
