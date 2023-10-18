<?php

namespace Database\Factories;

use App\Models\InactiveUser;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class InactiveUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = InactiveUser::class;

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
            }
        ];
    }
}
