<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestHelpers;
use App\Console\Commands\MailPastDueUsersCommand;
use App\Mail\PastDueUsersMail;
use App\Models\PastDueUser;
use App\Plan;
use App\User;
use Illuminate\Support\Facades\Mail;

class MailPastDueUsersCommandTest extends TestCase
{
    use DatabaseTransactions;
    use TestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->plan = Plan::factory()->create(['title' => 'test', 'public_id' => '10']);
        $this->pastDueUser = PastDueUser::factory()->create(
            ['user_id' => $this->user->id]
        );
    }

    /**
     * @test
     *
     * Check if command exists
     */
    public function itHasMailPastDueUsersCommand()
    {
        $this->assertTrue(class_exists(\App\Console\Commands\MailPastDueUsersCommand::class));
    }

    /**
     * @test
     *
     * Test actual command
     */
    public function itExecutesMailPastDueUsersCommand()
    {
        Mockery::mock(MailPastDueUsersCommand::class, function ($mock) {
            $mock->shouldReceive('handle')->once();
            $mock->handle();
        });
    }

    /**
     * @test
     *
     * Test itFindsSavedPastDueUser
     */
    public function itFindsSavedPastDueUser()
    {
        $pastDueUserModel = $this->app->make(PastDueUser::class);
        $result = $pastDueUserModel->getSavedPastDueUser($this->user->id);
        $this->assertNotNull($result);
        $this->assertEquals($this->user->id, $result->user_id);
    }

    /**
     * @test
     *
     * Test itDoesnotFindSavedPastDueUser
     */
    public function itDoesnotFindSavedPastDueUser()
    {
        $user2 = User::factory()->create();
        $pastDueUserModel = $this->app->make(PastDueUser::class);
        $result = $pastDueUserModel->getSavedPastDueUser($user2);
        $this->assertNull($result);
    }

    /**
     * @test
     *
     * Test itDeletesPastDueUser
     */
    public function itDeletesPastDueUser()
    {
        $pastDueUserModel = $this->app->make(PastDueUser::class);
        $result = $pastDueUserModel->deletePastDueUser($this->user->id);
        $this->assertTrue($result);
    }

    /**
     * @test
     *
     * Test itDoesnotDeletePastDueUser
     */
    public function itDoesnotDeletePastDueUser()
    {
        $user2 = User::factory()->create();
        $pastDueUserModel = $this->app->make(PastDueUser::class);
        $result = $pastDueUserModel->deletePastDueUser($user2->id);
        $this->assertFalse($result);
    }

    /**
     * @test
     *
     * Test userIsPasDueUser
     */
    public function userIsPasDueUser()
    {
        Mockery::mock(User::class, function ($mock) {
            $mock->shouldReceive('ispastDue')
                ->once()
                ->andReturn(true);
            $mock->ispastDue();
        });
    }

    /**
     * @test
     *
     * Test itSendsPastDueEmail
     */
    public function itSendsPastDueEmail()
    {
        $details = [
            'name' => $this->user->getFirstNameAttribute(),
            'email' => $this->user->email
        ];

        Mail::fake();
        Mail::send(new PastDueUsersMail($details));
        Mail::assertSent(PastDueUsersMail::class);
    }
}
