<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class HomeControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     *
     * @return void
     */
    public function itShouldSeeWelcomePage()
    {
        $this->get('/')->assertViewIs('welcome');
    }
}
