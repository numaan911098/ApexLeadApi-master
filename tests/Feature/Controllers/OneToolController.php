<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestHelpers;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Enums\RolesEnum;
use App\{User, Plan};

class OneToolController extends TestCase
{
    use DatabaseTransactions;
    use TestHelpers;

    /**
     * It should create a onetool user.
     *
     * Test: POST /api/onetool/user/create.
     *
     * @test
     */
    public function itShouldCreateOneToolAccount()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser());

        $content = $response->getOriginalContent();

        $response->assertStatus(200);

        $this->assertSame(true, User::find($content['id'])->isOneToolUser());
    }

    /**
     * It shouldn't throw error if a user with the given email already exists.
     *
     * Test: POST /api/onetool/user/create.
     *
     * @test
     */
    public function itShouldCheckExistingEmail()
    {
        $user = User::factory()->create();

        $response = $this->createOneToolUser(
            $this->getSampleHeaders(),
            $this->getSampleUser(['email' => $user->email])
        );

        $content = $response->getOriginalContent();

        $response->assertStatus(400);

        $this->assertSame(1, User::count());
    }

    /**
     * It should have customer role.
     *
     * Test: POST /api/onetool/user/create.
     *
     * @test
     */
    public function itShouldHaveCustomerRoleOnly()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser());

        $content = $response->getOriginalContent();

        $response->assertStatus(200);

        $this->assertSame(true, User::find($content['id'])->hasRoleOnly(RolesEnum::CUSTOMER));
    }

    /**
     * It should verify shared secret.
     *
     * Test: POST /api/onetool/user/create.
     *
     * @test
     */
    public function itShouldVerifySharedSecretOnCreateUser()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(['api-key' => ''], false), $this->getSampleUser());

        $content = $response->getOriginalContent();

        $response->assertStatus(400);

        $this->assertSame(0, User::count());
    }

    /**
     * It should validate data properly.
     *
     * Test: POST /api/onetool/user/create.
     *
     * @test
     */
    public function itShouldValidateCreateUserEmail()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser(['email' => 'aqib']));

        $content = $response->getOriginalContent();

        $response->assertStatus(400);

        $this->assertSame(0, User::count());
    }

    /**
     * It should validate plan properly.
     *
     * Test: POST /api/onetool/user/create.
     *
     * @test
     */
    public function itShouldValidateCreateUserPlan()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser(['plan_id' => 'premium']));

        $content = $response->getOriginalContent();

        $response->assertStatus(400);

        $this->assertSame(0, User::count());

        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser(['plan_id' => 'pro']));

        $content = $response->getOriginalContent();

        $response->assertStatus(200);

        $this->assertSame(1, User::count());
    }

     /**
     * It should validate required fields.
     *
     * Test: POST /api/onetool/user/create.
     *
     * @test
     */
    public function itShouldValidateCreateUserRequiredFields()
    {
        // Case 1.
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser([
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'role_type' => '',
            'in_trial' => '',
            'active' => '',
            'plan_id' => '',
        ]));

        $content = $response->getOriginalContent();

        $response->assertStatus(400);

        $this->assertSame(6, count($content['errors']));

        $this->assertSame(0, User::count());

        // Case 2.
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser([], false));

        $content = $response->getOriginalContent();

        $response->assertStatus(400);

        $this->assertSame(7, count($content['errors']));

        $this->assertSame(0, User::count());
    }

    /**
     * It should validate missing required fields on login.
     *
     * Test: GET /api/onetool/user/login.
     *
     * @test
     */
    public function itShouldValidateLoginUserRequiredFields()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser());

        $content = $response->getOriginalContent();

        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('GET', '/api/onetool/user/login');

        $response->assertStatus(400);
    }

    /**
     * It should login onetool user.
     *
     * Test: GET /api/onetool/user/login.
     *
     * @test
     */
    public function itShouldValidateLoginOneToolUser()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser());

        $content = $response->getOriginalContent();

        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('GET', '/api/onetool/user/login?id=' . $content['id']);

        $response->assertStatus(200);
    }

    /**
     * It should not login user other than onetool users on login.
     *
     * Test: GET /api/onetool/user/login.
     *
     * @test
     */
    public function itShouldOnlyAllowLoginToOneToolUsers()
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('GET', '/api/onetool/user/login?id=' . $user->id);

        $response->assertStatus(400);
    }

    /**
     * It should not login inactive user.
     *
     * Test: GET /api/onetool/user/login.
     *
     * @test
     */
    public function itShouldNotLoginInActiveUsers()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser([
            'status' => 'inactive',
            'in_trial' => false,
        ]));

        $content = $response->getOriginalContent();

        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('GET', '/api/onetool/user/login?id=' . $content['id']);

        $response->assertStatus(400);
    }

    /**
     * It should not login invalid user id.
     *
     * Test: GET /api/onetool/user/login.
     *
     * @test
     */
    public function itShouldNotLoginInvalidUser()
    {
        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('GET', '/api/onetool/user/login?id=100');

        $response->assertStatus(404);
    }

    /**
     * It should login user in trial.
     *
     * Test: GET /api/onetool/user/login.
     *
     * @test
     */
    public function itShouldLoginUserInTrial()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser([
            'status' => 'inactive',
        ]));

        $content = $response->getOriginalContent();

        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('GET', '/api/onetool/user/login?id=' . $content['id']);

        $response->assertStatus(200);
    }

    /**
     * It should get user detail by id.
     *
     * Test: GET /api/onetool/user/get?id=123.
     *
     * @test
     */
    public function itShouldGetUserById()
    {
        $sampleUser = $this->getSampleUser();

        $response = $this->createOneToolUser($this->getSampleHeaders(), $sampleUser);

        $content = $response->getOriginalContent();

        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('GET', '/api/onetool/user/get?id=' . $content['id']);

        $content = $response->getOriginalContent();

        $response->assertStatus(200);

        $this->assertSame($sampleUser['email'], $content['email']);
    }

    /**
     * It should get user detail by email.
     *
     * Test: GET /api/onetool/user/get?email=janedoe@onetool.co
     *
     * @test
     */
    public function itShouldGetUserByEmail()
    {
        $sampleUser = $this->getSampleUser();

        $response = $this->createOneToolUser($this->getSampleHeaders(), $sampleUser);

        $content = $response->getOriginalContent();

        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('GET', '/api/onetool/user/get?email=' . $sampleUser['email']);

        $content = $response->getOriginalContent();

        $response->assertStatus(200);

        $this->assertSame($sampleUser['email'], $content['email']);
    }

    /**
     * It should throw 404 error on get user by email or id.
     *
     * Test: GET /api/onetool/user/get?(email=janedoe@onetool.co|id=123)
     *
     * @test
     */
    public function itShouldThrowNotFoundErrorOnGetUser()
    {
        // Case 1.
        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('GET', '/api/onetool/user/get?id=123');

        $content = $response->getOriginalContent();

        $response->assertStatus(404);

        // Case 2.
        $response = $this->withHeaders($this->getSampleHeaders())
        ->json('GET', '/api/onetool/user/get?email=janedoe@onetool.co');

        $content = $response->getOriginalContent();

        $response->assertStatus(404);
    }

    /**
     * It should get only onetool user.
     *
     * Test: GET /api/onetool/user/get?(email=janedoe@onetool.co|id=123)
     *
     * @test
     */
    public function itShouldGetOnlyOneToolUser()
    {
        $user = User::factory()->create();

        // Case 1.
        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('GET', '/api/onetool/user/get?id=' . $user->id);

        $response->assertStatus(401);

        // Case 2.
        $response = $this->withHeaders($this->getSampleHeaders())
        ->json('GET', '/api/onetool/user/get?email=' . $user->email);

        $response->assertStatus(401);
    }

    /**
     * It should verify shared secret.
     *
     * Test: POST /api/onetool/user/get.
     *
     * @test
     */
    public function itShouldVerifySharedSecretOnGetUser()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser());

        $content = $response->getOriginalContent();

        $response = $this->withHeaders($this->getSampleHeaders(['api-key' => ''], false))
            ->json('GET', '/api/onetool/user/get?id=' . $content['id']);

        $response->assertStatus(400);
    }

    /**
     * It should verify shared secret.
     *
     * Test: POST /api/onetool/user/get.
     *
     * @test
     */
    public function itShouldValidateRequiredParamsOnGetUser()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser());

        $content = $response->getOriginalContent();

        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('GET', '/api/onetool/user/get');

        $response->assertStatus(400);
    }

    /**
     * It should validate required fields.
     *
     * Test: POST /api/onetool/user/update.
     *
     * @test
     */
    public function itShouldValidateUpdateUserRequiredFields()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser());

        $content = $response->getOriginalContent();

        // Case 1 validate id.
        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('POST', '/api/onetool/user/update', ['plan_id' => 'pro_lite']);

        $response->assertStatus(400);

        // Case 2 validate plan_id.
        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('POST', '/api/onetool/user/update', ['id' => $content['id'], 'plan_id' => 'myplan']);

        $response->assertStatus(400);
    }

    /**
     * It should not update other accounts email.
     *
     * Test: POST /api/onetool/user/update.
     *
     * @test
     */
    public function itShouldNotUpdateOtherUsersEmail()
    {
        $user = User::factory()->create();

        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser());

        $content = $response->getOriginalContent();

        // Case 1.
        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('POST', '/api/onetool/user/update', ['id' => $content['id'], 'email' => $user->email]);

        $response->assertStatus(400);

        // Case 2.
        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('POST', '/api/onetool/user/update', ['id' => $content['id'], 'email' => 'test@gmail.com']);

        $response->assertStatus(200);

        // Case 3.
        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('POST', '/api/onetool/user/update', ['id' => $user->id, 'email' => $user->email]);

        $response->assertStatus(401);
    }

    /**
     * It should update user fields.
     *
     * Test: POST /api/onetool/user/update.
     *
     * @test
     */
    public function itShouldUpdateUserFields()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser());

        $content = $response->getOriginalContent();

        // Case 1.
        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('POST', '/api/onetool/user/update', ['id' => $content['id'], 'email' => 'test@gmail.com']);

        $response->assertStatus(200);

        $this->assertSame(User::find($content['id'])->email, 'test@gmail.com');

        // Case 2.
        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('POST', '/api/onetool/user/update', [
                'id' => $content['id'],
                'status' => 'inactive',
                'in_trial' => false,
                'role_type' => 'admin',
                'first_name' => 'Jhon',
                'last_name' => 'Doe',
                'plan_id' => 'basic_lite'
            ]);

        $oneToolUser = User::find($content['id'])->oneToolUser;

        $response->assertStatus(200);

        $this->assertSame($oneToolUser->status, 'inactive');
        $this->assertSame($oneToolUser->in_trial, 0);
        $this->assertSame($oneToolUser->role_type, 'admin');
        $this->assertSame($oneToolUser->first_name, 'Jhon');
        $this->assertSame($oneToolUser->last_name, 'Doe');
        $this->assertSame(Plan::find($oneToolUser->plan_id)->public_id, 'onetool_basic_lite');
    }

    /**
     * It should verify shared secret.
     *
     * Test: POST /api/onetool/user/update.
     *
     * @test
     */
    public function itShouldVerifySharedSecretOnUpdateUser()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser());

        $content = $response->getOriginalContent();

        $response = $this->withHeaders($this->getSampleHeaders(['api-key' => ''], false))
            ->json('POST', '/api/onetool/user/update', []);

        $response->assertStatus(400);
    }


    /**
     * It should validate required fields.
     *
     * Test: DELETE /api/onetool/user/delete.
     *
     * @test
     */
    public function itShouldValidateRequiredParamsOnDeleteUser()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser());

        $content = $response->getOriginalContent();

        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('DELETE', '/api/onetool/user/delete');

        $response->assertStatus(400);
    }

    /**
     * It should throw 404.
     *
     * Test: DELETE /api/onetool/user/delete.
     *
     * @test
     */
    public function itShouldThrow404OnDeleteUser()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser());

        $content = $response->getOriginalContent();

        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('DELETE', '/api/onetool/user/delete?id=123');

        $response->assertStatus(404);
    }

    /**
     * It should delete onetool user only.
     *
     * Test: DELETE /api/onetool/user/delete.
     *
     * @test
     */
    public function itShouldDeleteOnlyOneToolUser()
    {
        // Case 1.
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser());

        $content = $response->getOriginalContent();

        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('DELETE', '/api/onetool/user/delete?id=' . $content['id']);

        $response->assertStatus(200);

        // Case 2.
        $user = User::factory()->create();

        $response = $this->withHeaders($this->getSampleHeaders())
            ->json('DELETE', '/api/onetool/user/delete?id=' . $user->id);

        $response->assertStatus(401);

        $this->assertSame(1, User::count());
    }

    /**
     * It should verify shared secret.
     *
     * Test: DELETE /api/onetool/user/delte.
     *
     * @test
     */
    public function itShouldVerifySharedSecretOnDeleteUser()
    {
        $response = $this->createOneToolUser($this->getSampleHeaders(), $this->getSampleUser());

        $content = $response->getOriginalContent();

        $response = $this->withHeaders($this->getSampleHeaders(['api-key' => ''], false))
            ->json('DELETE', '/api/onetool/user/delete?id=' . $content['id']);

        $response->assertStatus(400);

        $this->assertSame(1, User::count());
    }

    /**
     * Create OneTool User array.
     *
     * @param array $attributes
     * @param boolean $merge
     * @return array
     */
    private function getSampleUser(array $attributes = [], bool $merge = true): array
    {
        if (!$merge) {
            return $attributes;
        }

        return array_merge([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'plan_id' => 'pro',
            'status' => 'active',
            'role_type' => 'user',
            'in_trial' => true,
            'email' => 'janedoe@onetool.co',
        ], $attributes);
    }

    /**
     * Create Headers.
     *
     * @param array $headers
     * @param boolean $merge
     * @return array
     */
    private function getSampleHeaders(array $headers = [], bool $merge = true): array
    {
        if (!$merge) {
            return $headers;
        }

        return  array_merge([
            'Authorization' => 'Bearer null',
            'api-key' => config('leadgen.onetool_secret'),
        ], $headers);
    }

    /**
     * Create OneTool User helper.
     *
     * @param array $headers
     * @param array $attributes
     *
     * @return Illuminate\Http\Response
     */
    private function createOneToolUser(array $headers, array $attributes)
    {
        return $this->withHeaders($headers)
            ->json('POST', '/api/onetool/user/create', $attributes);
    }
}
