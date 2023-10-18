<?php

namespace Database\Factories;

use App\FormExperiment;
use App\FormExperimentType;
use App\Form;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormExperimentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FormExperiment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' =>  $this->faker->sentence(2),
            'form_id' => function () {
                return Form::factory()->create()->id;
            },
            'form_experiment_type_id' => function () {
                return FormExperimentType::inRandomOrder()->first()->id;
            }
        ];
    }
}
