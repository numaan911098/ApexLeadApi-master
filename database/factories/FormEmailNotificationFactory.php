<?php

namespace Database\Factories;

use App\{FormEmailNotification, Form};
use Illuminate\Database\Eloquent\Factories\Factory;

class FormEmailNotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FormEmailNotification::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'subject' => $this->faker->sentence(10),
            'to' => $this->faker->safeEmail,
            'cc' => implode(
                ',',
                [
                    $this->faker->safeEmail,
                    $this->faker->safeEmail,
                    $this->faker->safeEmail
                ]
            ),
            'bcc' => implode(
                ',',
                [
                    $this->faker->safeEmail,
                    $this->faker->safeEmail
                ]
            ),
            'from_name' => $this->faker->name,
            'form_id' => function () {
                Form::factory()->create()->id;
            }
        ];
    }
}
