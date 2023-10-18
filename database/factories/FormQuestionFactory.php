<?php

namespace Database\Factories;

use App\{FormQuestion, FormQuestionType, FormStep};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Enums\QuestionTypesEnum;

class FormQuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FormQuestion::class;

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
            'form_question_type_id' => function () {
                return FormQuestionType::inRandomOrder()->first()->id;
            },
            'form_step_id' => function () {
                return FormStep::factory()->create()->id;
            },
            'config' => function (array $question) {
                $questionType = FormQuestionType::find($question['form_question_type_id'])
                ->type;

                $config = [
                    'id' => $question['form_step_id'],
                    'stepId' => $question['form_step_id'],
                    'type' => $questionType,
                    'required' => $this->faker->boolean,
                    'placeholder' => $this->faker->sentence,
                    'title' => $this->faker->sentence,
                    'valid' => $this->faker->boolean
                ];

                if (
                    $questionType === QuestionTypesEnum::SINGLE_CHOICE ||
                    $questionType === QuestionTypesEnum::MULTIPLE_CHOICE
                ) {
                    $config['choices'] = [
                        'choice 1', 'choice 2', 'choice 3'
                    ];
                }

                return json_encode($config);
            }
        ];
    }
}
