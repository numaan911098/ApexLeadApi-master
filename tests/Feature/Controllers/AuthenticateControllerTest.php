<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Models\TwoFactorSetting;
use App\Models\UserDevice;
use Tests\TestHelpers;
use App\User;
use App\Plan;

class AuthenticateControllerTest extends TestCase
{
    use DatabaseTransactions;
    use TestHelpers;

    /**
     * @test
     *
     * Test: POST /api/authenticate.
     */
    public function itShouldBeUnverifiedAccount()
    {
        $user = User::factory()->create(['password' => bcrypt('foo')]);
        $plan = Plan::factory()->create(['title' => 'test', 'public_id' => '10']);
        $userDevice = UserDevice::factory()->create(['user_id' => $user->id, 'device_id' => '123']);
        $twoFactor = TwoFactorSetting::factory()->create(['user_id' => $user->id]);
        $user->default_plan_id = $plan->id;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer null',
            'User-Agent' => '123'
        ])->json('POST', '/api/authenticate', ['email' => $user->email, 'password' => 'foo']);

        $content = $response->getOriginalContent();

        $this->assertSame(0, $content['data']['user']['verified']);
    }

    /**
     * @test
     *
     * Test: POST /api/authenticate.
     */
    public function itShouldReturnToken()
    {
        $user = User::factory()->create(['password' => bcrypt('foo')]);
        $plan = Plan::factory()->create(['title' => 'test', 'public_id' => '10']);
        $userDevice = UserDevice::factory()->create(['user_id' => $user->id, 'device_id' => '123']);
        $user->default_plan_id = $plan->id;
        $user->verified = true;
        $user->active = true;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer null',
            'User-Agent' => '123'
        ])->json('POST', '/api/authenticate', ['email' => $user->email, 'password' => 'foo']);

        $response->assertStatus(200);
    }


    /**
     * @test
     *
     * Test: POST /api/authenticate.
     */
    public function itShouldBeSuspendedAccount()
    {
        $user = User::factory()->inActive()->create(['password' => bcrypt('foo')]);
        $user->verified = true;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer null',
        ])->json('POST', '/api/authenticate', ['email' => $user->email, 'password' => 'foo']);
        $content = $response->getOriginalContent();

        $this
            ->assertSame(ErrorTypes::SUSPENDED_ACCOUNT, $content['meta']['error_type']);
    }


    /**
     * @test
     *
     * Test: POST /api/authenticate.
     */
    public function itShouldNotAllowInvalidDataOnAuthenticate()
    {
        $user = User::factory()->create(['password' => bcrypt('foo')]);
        $user->verified = true;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer null',
        ])->json('POST', '/api/authenticate', ['password' => 'foo']);
        $content = $response->getOriginalContent();

        $this
            ->assertSame(ErrorTypes::INVALID_DATA, $content['meta']['error_type']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer null',
        ])->json('POST', '/api/authenticate', ['email' => $user->email]);
        $content = $response->getOriginalContent();

        $this
            ->assertSame(ErrorTypes::INVALID_DATA, $content['meta']['error_type']);


        $response = $this->withHeaders([
            'Authorization' => 'Bearer null',
        ])->json('POST', '/api/authenticate', []);
        $content = $response->getOriginalContent();

        $this
            ->assertSame(ErrorTypes::INVALID_DATA, $content['meta']['error_type']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer null',
        ])->json('POST', '/api/authenticate', ['email' => 'bar', 'password' => 'foo']);
        $content = $response->getOriginalContent();

        $this
            ->assertSame(ErrorTypes::INVALID_DATA, $content['meta']['error_type']);
    }


    /**
     * @test
     *
     * Test: POST /api/authenticate.
     */
    public function itShouldCheckUnknowEmail()
    {
        $user = User::factory()->create(['password' => bcrypt('foo')]);
        $user->verified = true;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer null',
        ])->json('POST', '/api/authenticate', ['email' => $user->email . 'a', 'password' => 'foo']);
        $content = $response->getOriginalContent();

        $this
            ->assertSame(ErrorTypes::NON_EXISTENCE_EMAIL, $content['meta']['error_type']);
    }


    /**
     * @test
     *
     * Test: POST /api/authenticate.
     */
    public function itShouldCheckInvalidCredentials()
    {
        $user = User::factory()->create(['password' => bcrypt('foo')]);
        $plan = Plan::factory()->create(['title' => 'test', 'public_id' => '10']);
        $user->default_plan_id = $plan->id;
        $user->verified = true;
        $user->active = true;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer null',
        ])->json('POST', '/api/authenticate', ['email' => $user->email, 'password' => 'bar']);
        $content = $response->getOriginalContent();

        $this
            ->assertSame(ErrorTypes::INVALID_LOGIN_CREDENTIALS, $content['meta']['error_type']);
    }


    /**
     * @test
     *
     * It should get currently authenticated user
     *
     * Test: GET /api/me
     */
    public function itShouldGetAuthenticatedUser()
    {
        $user = User::factory()->create(['password' => bcrypt('foo')]);
        $user->verified = true;
        $user->active = true;
        $user->save();

        $response = $this->get('/api/me', $this->apiHeaders([], null, $user));

        $response->assertStatus(200);
    }

    /**
     * @test
     *
     * It should not get currently authenticated user
     *
     * Test: GET /api/me
     */
    public function itShouldNotGetAuthenticatedUser()
    {
        $response = $this->get('/api/me', $this->apiHeaders([
            'Authorization' => 'Bearer dfdf'
        ]));

        $content = $response->getOriginalContent();

        $response->assertStatus(401);
    }

    /**
     * @test
     *
     * Test: GET /api/logout
     */
    public function itShouldLogoutUser()
    {
        $user = User::factory()->create(['password' => bcrypt('foo')]);
        $plan = Plan::factory()->create(['title' => 'test', 'public_id' => '10']);
        $userDevice = UserDevice::factory()->create(['user_id' => $user->id, 'device_id' => '123']);
        $user->default_plan_id = $plan->id;
        $user->verified = true;
        $user->active = true;
        $user->save();

        //get  token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer null',
            'User-Agent' => '123'
        ])->json('POST', '/api/authenticate', ['email' => $user->email, 'password' => 'foo']);
        $content = $response->getOriginalContent();

        // get user from  token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $content['data']['token'],
        ])->get('/api/me');
        $response->assertStatus(200);

        //invalidate token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $content['data']['token'],
        ])->get('/api/logout');
        sleep(1);
        //validate invalid token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $content['data']['token'],
        ])->get('/api/me');
        $response->assertStatus(401);
    }

    /**
     * @test
     *
     * Test: POST /api/authenticate.
     */
    public function itShouldBeVerifiedAccount()
    {
        $user = User::factory()->verified()->create(['password' => bcrypt('foo')]);
        $plan = Plan::factory()->create(['title' => 'test', 'public_id' => '10']);
        $userDevice = UserDevice::factory()->create(['user_id' => $user->id, 'device_id' => '123']);
        $twoFactor = TwoFactorSetting::factory()->create(['user_id' => $user->id]);
        $user->default_plan_id = $plan->id;
        $user->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer null',
            'User-Agent' => '123'
        ])->json('POST', '/api/authenticate', ['email' => $user->email, 'password' => 'foo']);

        $content = $response->getOriginalContent();

        $this->assertSame(1, $content['data']['user']['verified']);
    }
}
