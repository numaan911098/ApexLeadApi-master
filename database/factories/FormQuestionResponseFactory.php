<?php

namespace Database\Factories;

use App\FormLead;
use App\FormQuestion;
use App\FormQuestionResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormQuestionResponseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FormQuestionResponse::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'response' => $this->faker->email,
            'form_question_id' => function () {
                return FormQuestion::factory()->create()->id;
            },
            'form_lead_id' => function () {
                return FormLead::factory()->create()->id;
            }
        ];
    }
}
