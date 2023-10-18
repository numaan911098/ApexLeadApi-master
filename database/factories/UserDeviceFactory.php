<?php

namespace Database\Factories;

use App\Models\UserDevice;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserDeviceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserDevice::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'device_id' => 'Symphony',
            'verification_code' => null,
            'verified_at' => Carbon::now(),
        ];
    }
}
