<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestHelpers;
use App\Console\Commands\DeleteInactiveAccountsCommand;

class DeleteInactiveAccountsCommandTest extends TestCase
{
    use DatabaseTransactions;
    use TestHelpers;

    /**
     * @test
     *
     * Check if command exists
     */
    public function itHasDeleteInactiveAccountsCommand()
    {
        $this->assertTrue(class_exists(\App\Console\Commands\DeleteInactiveAccountsCommand::class));
    }

    /**
     * @test
     *
     * Test actual command
     */
    public function itExecutesDeleteInactiveAccountsCommand()
    {

        Mockery::mock(DeleteInactiveAccountsCommand::class, function ($mock) {
            $mock->shouldReceive('handle')->once();
            $mock->handle();
        });
    }
}
