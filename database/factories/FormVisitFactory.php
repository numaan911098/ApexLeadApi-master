<?php

namespace Database\Factories;

use App\FormVisit;
use App\{Form, FormExperiment, FormVariant};
use Illuminate\Database\Eloquent\Factories\Factory;

class FormVisitFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FormVisit::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'ip' => $this->faker->ipv4,
            'form_id' => function () {
                return Form::factory()->create()->id;
            },
            'os' => 'Windows',
            'device_type' => 'Desktop',
            'browser' => $this->faker->chrome,
            'source_url' => $this->faker->url,
            'user_agent' => $this->faker->userAgent,
            'form_experiment_id' => function () {
                return FormExperiment::factory()->create()->id;
            },
            'device_name' => 'WebKit',
            'form_variant_id' =>  function () {
                return FormVariant::factory()->create()->id;
            },
        ];
    }
}
