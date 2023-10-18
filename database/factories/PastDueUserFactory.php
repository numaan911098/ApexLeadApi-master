<?php

namespace Database\Factories;

use App\Models\PastDueUser;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class PastDueUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PastDueUser::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => function () {
                return User::factory()->create()->id;
            },
            'send_at' =>  Carbon::now()->addDays(config('leadgen.past_due_user.email'))
        ];
    }
}
