<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(InitSeeder::class);
        $this->call(LandingPageTemplatesTableSeeder::class);
        $this->call(FormThemeTemplateTableSeeder::class);
    }
}
