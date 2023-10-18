<?php

namespace Database\Factories\Form;

use App\{Enums\Form\FormTrackingEventTypesEnum, Models\Form\FormTrackingEvent};
use Illuminate\Database\Eloquent\Factories\Factory;

class FormTrackingEventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FormTrackingEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => FormTrackingEventTypesEnum::INTERACTED,
            'script' => null,
            'active' => 0,
            'configured' => 0,
            'form_id' => 0,
            'created_at' => $this->faker->dateTime('now'),
            'updated_at' => $this->faker->dateTime('now')
        ];
    }
}
