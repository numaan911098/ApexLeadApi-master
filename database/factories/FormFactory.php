<?php

namespace Database\Factories;

use App\{User, Form};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FormFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Form::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence(2),
            'key' => Str::random(32),
            'current_experiment_id' => null,
            'created_by' => function () {
                return User::factory()->create()->id;
            },
            'created_at' => $this->faker->dateTime('now'),
            'updated_at' => $this->faker->dateTime('now')
        ];
    }
}
