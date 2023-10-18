<?php

namespace Tests\Feature\Services\User;

use App\Enums\RolesEnum;
use App\Mail\UserEmailVerification;
use App\Models\TwoFactorSetting;
use App\Modules\Security\Services\AuthService;
use App\Role;
use App\Services\User\UserService;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\TestHelpers;
use Mail;

class UserServiceTest extends TestCase
{
    use DatabaseTransactions;
    use TestHelpers;

    private $authServiceMock;
    private Role $adminRole;
    private Role $customerRole;
    private User $user1Stub;
    private User $user2Stub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authServiceMock = \Mockery::mock(AuthService::class);
        $this->app->instance(AuthService::class, $this->authServiceMock);

        $this->user1Stub = User::factory()->create();
        $this->user2Stub = User::factory()->create(['name' => 'aqib', 'email' => 'aqib@gmail.com']);
        $this->user3Stub = User::factory()->create(['name' => 'aqib', 'email' => 'aqib3@gmail.com']);
        $this->adminRole = Role::where('name', RolesEnum::ADMIN)->first();
        $this->customerRole = Role::where('name', RolesEnum::CUSTOMER)->first();
        $this->twoFactor = TwoFactorSetting::factory()->create(['user_id' => $this->user2Stub->id]);
    }

    public function testItDoesntAllowUserToUpdateOtherUserBasicDetails()
    {
        $this->authServiceMock->shouldReceive('getUser')
            ->andReturn($this->user1Stub);
        $this->user1Stub->roles()->attach($this->customerRole);

        $userService = $this->app->make(UserService::class);

        $result = $userService
            ->updateBasicDetails(
                $this->user2Stub,
                ['name' => 'aqib 1', 'email' => 'aqib2@gmail.com']
            );

        $this->assertFalse($result->success);
    }

    public function testItAllowsAdminUserToUpdateOtherUserBasicDetails()
    {
        Mail::fake();
        $this->authServiceMock->shouldReceive('getUser')
            ->andReturn($this->user1Stub);
        $this->user1Stub->roles()->attach($this->adminRole);

        $userService = $this->app->make(UserService::class);

        $result = $userService
            ->updateBasicDetails(
                $this->user2Stub,
                ['name' => 'aqib 1', 'email' => 'aqib2@gmail.com', 'twoFactor' => true]
            );

        $this->assertTrue($result->success);
    }

    public function testItDoesntAllowsAdminUserToUpdateOtherAdminBasicDetails()
    {
        $this->authServiceMock->shouldReceive('getUser')
            ->andReturn($this->user1Stub);
        $this->user1Stub->roles()->attach($this->adminRole);
        $this->user2Stub->roles()->attach($this->adminRole);

        $userService = $this->app->make(UserService::class);

        $result = $userService
            ->updateBasicDetails(
                $this->user2Stub,
                ['name' => 'aqib 1', 'email' => 'aqib2@gmail.com']
            );

        $this->assertFalse($result->success);
        $this->assertSame('You are not Authorized for this action', $result->error);
    }

    public function testItSendsEmailOnChangingEmail()
    {
        Mail::fake();

        $this->authServiceMock->shouldReceive('getUser')
            ->andReturn($this->user1Stub);
        $this->user1Stub->roles()->attach($this->adminRole);

        $userService = $this->app->make(UserService::class);

        $result = $userService
            ->updateBasicDetails(
                $this->user2Stub,
                ['name' => 'aqib 1', 'email' => 'aqib2@gmail.com', 'twoFactor' => true]
            );

        $this->assertTrue($result->success);

        Mail::assertSent(UserEmailVerification::class);
    }

    public function testItDoesntAllowEmailChangingIfEmailAlreadyExists()
    {
        $this->authServiceMock->shouldReceive('getUser')
            ->andReturn($this->user1Stub);
        $this->user1Stub->roles()->attach($this->adminRole);

        $userService = $this->app->make(UserService::class);

        $result = $userService
            ->updateBasicDetails(
                $this->user2Stub,
                ['name' => 'aqib 1', 'email' => 'aqib3@gmail.com', 'twoFactor' => false]
            );

        $this->assertFalse($result->success);
        $this->assertSame('You are not Authorized to use this email', $result->error);
    }
}
