<?php

namespace Database\Factories;

use App\{FormSetting, Form};
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\QuestionTypesEnum;

class FormSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FormSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        static $number = 0;

        return [
            'email_notifications' => $this->faker->boolean(50),
            'accept_responses' => $this->faker->boolean(50),
            'form_id' => function () {
                Form::factory()->create()->id;
            },
            'domains' => implode(',', [$this->faker->domainName, $this->faker->domainName]),
            'steps_summary' => $this->faker->boolean(50),
            'enable_thankyou_url' => $this->faker->boolean(50),
            'thankyou_message' => $this->faker->sentence(10),
            'thankyou_url' => $this->faker->url,
            'response_limit' => $this->faker->biasedNumberBetween(1, 100),
            'enable_google_recaptcha' => $this->faker->boolean(50)
        ];
    }
}
