<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Faker\Generator as Faker;
use App\Enums\QuestionTypesEnum;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use Carbon\Carbon;
use JWTAuth;
use Tests\TestHelpers;
use App\{Form, FormSetting, FormVariant, FormEmailNotification, FormExperiment,
FormLead, FormStep, FormQuestion, FormQuestionResponse, FormVisit, User, Plan};

class FormControllerTest extends TestCase
{
    use DatabaseTransactions;
    use TestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->planId = Plan::factory()->create(['title' => 'test', 'public_id' => '10']);
    }

    /**
     * @test
     *
     * It should return empty list of forms
     *
     * Test: GET /api/forms/count
     *
     * @return void
     */
    public function itShouldReturnZeroCount()
    {
        $user = User::factory()->create();

        $response =  $this->get('/api/forms/count', $this->apiHeaders([], null, $user));
        $responseContent = $response->getOriginalContent();

        $this->assertSame(0, $responseContent['data']['count']);
    }

    /**
     *
     * @test
     *
     * It should return form count by user
     *
     * Test: GET /api/forms/count
     *
     * @return void
     */
    public function itShouldReturnFormsCountByUser()
    {
        // user 1
        $user = User::factory()->create();
        $userForms = Form::factory()->count(4)->create([
            'created_by' => $user->id
        ]);

        $response = $this->get('/api/forms/count', $this->apiHeaders([], null, $user));
        $responseContent = $response->getOriginalContent();

        $this->assertSame(4, $responseContent['data']['count']);

        // user 2
        $user = User::factory()->create();
        $userForms = Form::factory()->count(14)->create([
            'created_by' => $user->id
        ]);

        $response = $this->get('/api/forms/count', $this->apiHeaders([], null, $user));
        $responseContent = $response->getOriginalContent();

        $this->assertSame(14, $responseContent['data']['count']);
    }

    /**
     *
     * @test
     *
     * It should return forms created by user
     *
     * Test: GET /api/forms
     *
     * @return void
     */
    public function itShouldReturnFormsCreatedByUser()
    {
        $user1 = User::factory()->create(['default_plan_id' => $this->planId]);
        $user2 = User::factory()->create(['default_plan_id' => $this->planId]);

        $this->actingAs($user1);
        $response = $this->get('/api/forms', $this->headers($user1));
        $response->assertStatus(200);

        $this->actingAs($user2);
        $response = $this->get('/api/forms', $this->headers($user2));
        $response->assertStatus(200);
    }

    /**
     *
     * @test
     *
     * It should return recently created forms
     *
     * Test: GET /api/forms
     *
     * @return void
     */
    public function itShouldReturnLatestFormsFirst()
    {
        $user1 = User::factory()->create(['default_plan_id' => $this->planId]);
        $user2 = User::factory()->create(['default_plan_id' => $this->planId]);
        $user1Forms = Form::factory()->count(4)->create([
            'created_by' => $user1->id
        ]);
        $user2Forms = Form::factory()->count(3)->create([
            'created_by' => $user2->id
        ]);

        $response = $this->get('/api/forms', $this->headers($user1));
        $responseContent = $response->getOriginalContent();

        $date1 = Carbon::parse($responseContent['data'][0]['created_at']);
        $date2 = Carbon::parse($responseContent['data'][2]['created_at']);
        $this->assertTrue($date1->gt($date2));

        $response = $this->get('/api/forms', $this->headers($user2));
        $responseContent = $response->getOriginalContent();

        $date1 = Carbon::parse($responseContent['data'][0]['created_at']);
        $date2 = Carbon::parse($responseContent['data'][2]['created_at']);
        $this->assertTrue($date1->gt($date2));

        $this->assertTrue(true);
    }

    /**
     * @test
     *
     * it should return 404 for unexisting form
     *
     * Test: Get /api/forms/{form}
     *
     * @return void
     */
    public function itShouldReturn404ForUnexistingForm()
    {
        $fakeUser = User::factory()->create();
        $fakeForms = Form::factory()->create([
            'created_by' => $fakeUser->id
        ]);
        $unknownId = ($fakeForms->first()->id + 1);

        $response = $this->get('/api/forms/' . $unknownId, $this->headers($fakeUser));

        $response->assertStatus(404);
    }

    /**
     * @test
     *
     * it should not authorization to other user forms
     *
     * Test: Get /api/forms/{form}
     *
     * @return void
     */
    public function itShouldNotAuthorizeToDifferentUserForms()
    {
        $fakeUser1 = User::factory()->create();
        $fakeUser2 = User::factory()->create();
        $fakeForms1 = Form::factory()->count(1)->create([
            'created_by' => $fakeUser1->id
        ]);
        $fakeForms2 = Form::factory()->count(1)->create([
            'created_by' => $fakeUser2->id
        ]);

        $response = $this->get('/api/forms/' . $fakeForms1->first()->id, $this->headers($fakeUser2));
        $response->assertStatus(403);

        $response = $this->get('/api/forms/' . $fakeForms2->first()->id, $this->headers($fakeUser2));
        $response->assertStatus(200);
    }

    /**
     *
     * Test: Get /api/forms/{form}
     *
     * it should render proper state structure
     *
     * @return void
     */
    public function itShouldRenderProperStateStructure()
    {
        $fakeUser = User::factory()->create();
        $fakeForm = Form::factory()->create([
            'created_by' => $fakeUser->id
        ]);
        FormStep::factory()->count(2)->create([
            'form_id' => $fakeForm->id
        ])->each(function ($step) {
            FormQuestion::factory()->count(2)->create([
                'form_step_id' => $step->id
            ]);
        });
        $response = $this->get('/api/forms/' . $fakeForm->id, $this->headers($fakeUser));
        $content = $response->getOriginalContent();

        $this->assertSame(2, $content['data']['lastStepId']);
        $this->assertSame(2, count($content['data']['steps'][0]['questions']));

        $lastQuestionId = $this->findLastQuestionId($content['data']['steps'][0]['questions']);
        $this->assertEquals($content['data']['lastQuestionId'], $lastQuestionId);
    }

    /**
     * @test
     *
     * It should not duplicate existing form for free user.
     *
     * Test: Get api/forms/{form}/duplicate
     *
     * @return void
     */
    public function itShouldNotCloneForm()
    {
        $user = User::factory()->create(['default_plan_id' => $this->planId]);
        $form = Form::factory()->create([
            'created_by' => $user->id
        ]);

        $response = $this->get(
            '/api/forms/' . $form->id . '/duplicate',
            $this->headers($form->createdBy)
        );

        $responseContent = $response->getOriginalContent();

        $this->assertSame(ErrorTypes::FORM_CREATE_EXCEED, $responseContent['meta']['error_type']);
        $response->assertStatus(403);
    }

    /**
     *
     * it should clone form with variants
     *
     * Test: Get api/forms/{form}/duplicate
     *
     * @return void
     */
    public function itShouldCloneFormWithVariants()
    {
        $form = Form::factory()->create();

        $variant = FormVariant::factory()->count(3)->create([
            'form_id' => $form->id
        ]);

        $response = $this->get('/api/forms/' . $form->id . '/duplicate', $this->headers($form->createdBy));
        $content = $response->getOriginalContent();

        $duplicateForm = \App\Form::find($content['data']['id']);

        $this->assertSame(2, \App\Form::count());
        $this->assertSame(3, $duplicateForm->variants->count());
        $this->assertNotEquals($form->id, $content['data']['id']);
        $response->assertStatus(200);
    }

    /**
     *
     * it should clone form with variants steps questions
     *
     * Test: Get api/forms/{form}/duplicate
     *
     * @return void
     */
    public function itShouldCloneFormWithVariantsStepsQuestions()
    {
        $form = Form::factory()->create();
        $champion = (new \App\FormVariantType())->champion();
        $challenger = (new \App\FormVariantType())->challenger();

        //variant 1
        $variant1 = FormVariant::factory()->create([
            'form_id' => $form->id,
            'form_variant_type_id' => $champion->id
        ]);

        //variant 2
        $variant2 = FormVariant::factory()->create([
            'form_id' => $form->id,
            'form_variant_type_id' => $challenger->id
        ]);
        $variant2Steps = FormStep::factory()->count(2)->create([
            'form_variant_id' => $variant2->id,
            'form_id' => $form->id
        ])->each(function ($step) {
            FormQuestion::factory()->count(2)->create([
                'form_step_id' => $step->id
            ]);
        });

        //variant 3
        $variant3 = FormVariant::factory()->create([
            'form_id' => $form->id,
            'form_variant_type_id' => $challenger->id
        ]);
        $variant3Steps = FormStep::factory()->count(4)->create([
            'form_variant_id' => $variant3->id,
            'form_id' => $form->id
        ])->each(function ($step) {
            FormQuestion::factory()->count(3)->create([
                'form_step_id' => $step->id
            ]);
        });

        $response = $this->get('/api/forms/' . $form->id . '/duplicate', $this->headers($form->createdBy));
        $content = $response->getOriginalContent();
        $duplicateForm = \App\Form::find($content['data']['id']);

        $response->assertStatus(200);
        $this->assertNotEquals($form->id, $content['data']['id']);
        $this->assertSame(2, \App\Form::count());
        $this->assertSame(6, \App\FormVariant::count());
        $this->assertSame(3, $duplicateForm->variants->count());
        foreach ($duplicateForm->variants as $variant) {
            $this->assertContains($variant->formSteps->count(), [0, 2, 4]);
            foreach ($variant->formSteps as $formStep) {
                $this->assertContains($formStep->formQuestions->count(), [0, 2, 3]);
            }
        }
        $this->assertSame(
            2,
            \App\FormVariant::where('form_variant_type_id', $champion->id)->count()
        );
        $this->assertSame(
            4,
            \App\FormVariant::where('form_variant_type_id', $challenger->id)->count()
        );
    }

    /**
     *
     * it should clone form with settings
     *
     * Test: Get api/forms/{form}/duplicate
     *
     * @return void
     */
    public function itShouldCloneFormWithSettings()
    {
        $form = Form::factory()->create();
        $formSetting = FormSetting::factory()->create([
            'form_id' => $form->id
        ]);
        $formEmailNotification = FormEmailNotification::factory()->create([
            'form_id' => $form->id
        ]);

        $response = $this->get('/api/forms/' . $form->id . '/duplicate', $this->headers($form->createdBy));
        $content = $response->getOriginalContent();
        $duplicateForm = \App\Form::find($content['data']['id']);
        $duplicateFormSetting = $duplicateForm->formSetting;
        $duplicateFormEmailNotification = $duplicateForm->formEmailNotification;

        $response->assertStatus(200);
        $this->assertNotEquals($form->id, $content['data']['id']);
        $this->assertSame(2, \App\FormSetting::count());
        $this->assertSame(2, \App\FormEmailNotification::count());

        $this->assertSame(
            $formSetting->thankyou_message,
            $duplicateFormSetting->thankyou_message
        );
        $this->assertSame(
            $formSetting->domains,
            $duplicateFormSetting->domains
        );

        $this->assertSame(
            $formEmailNotification->subject,
            $duplicateFormEmailNotification->subject
        );
        $this->assertSame(
            $formEmailNotification->from_name,
            $duplicateFormEmailNotification->from_name
        );
    }

    /**
     * @test
     *
     * it should only archive form
     *
     * Test: Delete /api/forms/{form}/archive
     *
     * @return void
     */
    public function itShouldOnlyArchiveForm()
    {
        $user = User::factory()->create();
        $forms = Form::factory()->count(2)->create([
            'created_by' => $user->id
        ]);

        $form =  $forms->first();

        $response = $this->delete('/api/forms/' . $form->id . '/archive', [], $this->headers($user));

        $response->assertStatus(200);
        $this->assertSame(
            2,
            \App\Form::withTrashed()->where('created_by', $user->id)->count()
        );
    }

    /**
     * @test
     *
     * it should not delete form setting
     *
     * Test: Delete /api/forms/{form}/archive
     *
     * @return void
     */
    public function itShouldNotDeleteFormSetting()
    {
        $form = Form::factory()->create();

        $formSetting = FormSetting::factory()->create([
            'form_id' => $form->id
        ]);

        $response = $this->delete(
            '/api/forms/' . $form->id . '/archive',
            [],
            $this->headers($form->createdBy)
        );

        $response->assertStatus(200);
        $this->assertSame(
            1,
            \App\FormSetting::where('form_id', $form->id)->count()
        );
    }

    /**
     * @test
     *
     * it should not delete form variants
     *
     * Test: Delete /api/forms/{form}/archive
     *
     * @return void
     */
    public function itShouldNotDeleteFormVariants()
    {
        $form = Form::factory()->create();

        $champion = (new \App\FormVariantType())->champion();
        $challenger = (new \App\FormVariantType())->challenger();

        FormVariant::factory()->create([
            'form_id' => $form->id,
            'form_variant_type_id' => $champion->id
        ]);

        FormVariant::factory()->create([
            'form_id' => $form->id,
            'form_variant_type_id' => $challenger->id
        ]);

        $response = $this->delete('/api/forms/' . $form->id . '/archive', [], $this->headers($form->createdBy));

        $response->assertStatus(200);
        $this->assertSame(2, FormVariant::count());
    }

    /**
     * @test
     *
     * it should not delete form email notification
     *
     * Test: Delete /api/forms/{form}/archive
     *
     * @return void
     */
    public function itShouldNotDeleteFormEmailNotification()
    {
        $form = Form::factory()->create();

        $formSetting = FormEmailNotification::factory()->create([
            'form_id' => $form->id
        ]);

        $response = $this->delete(
            '/api/forms/' . $form->id . '/archive',
            [],
            $this->headers($form->createdBy)
        );

        $response->assertStatus(200);
        $this->assertSame(
            1,
            FormEmailNotification::where('form_id', $form->id)->count()
        );
    }

    /**
     * @test
     *
     * it should not archive form created by other user
     *
     * Test: Delete /api/forms/{form}/archive
     *
     * @return void
     */
    public function itShouldNotArchiveFormCreatedByOtherUser()
    {
        $user = User::factory()->create();
        $form = Form::factory()->create();

        $response = $this->delete(
            '/api/forms/' . $form->id . '/archive',
            [],
            $this->headers($user)
        );

        $response->assertStatus(403);
    }

    /**
     * Return request headers needed to interact with the API.
     *
     * @return Array of headers.
     */
    protected function headers($user = null)
    {
        $headers = ['Accept' => 'application/json'];

        if (!is_null($user)) {
            $token = JWTAuth::fromUser($user);
            JWTAuth::setToken($token);
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    protected function findLastQuestionId(array $questions)
    {
        $lastQuestionId = -1;
        foreach ($questions as $question) {
            if ($question['id'] > $lastQuestionId) {
                $lastQuestionId = $question['id'];
            }
        }
        return $lastQuestionId;
    }

    /**
     * @test
     *
     * It should reset form status
     *
     * Test: Delete /api/forms/{form}/resetstatus
     *
     * @return void
     */
    public function itShouldResetFomStatus()
    {
        $user = User::factory()->create();
        $form = Form::factory()->create(['created_by' => $user->id]);
        $formLead = FormLead::factory()->create(['form_id' => $form->id]);
        $formVisit = FormVisit::factory()->create(
            [
                'form_id' => $form->id,
                'form_variant_id' => $formLead->form_variant_id
            ]
        );
        $formQuestion = FormQuestion::factory()->create(['form_question_type_id' => 5]);
        FormExperiment::factory()->create(['form_id' => $form->id]);
        $formQuestionResponse = FormQuestionResponse::factory()->create(['form_question_id' => $formQuestion->id]);

        $response = $this->delete(
            '/api/forms/' . $form->id . '/resetstatus',
            [],
            $this->headers($user)
        );
        $response->assertStatus(200);
        $this->assertSame(
            0,
            FormVisit::where('form_id', $form->id)->count()
        );
        $this->assertSame(
            0,
            FormLead::where('form_id', $form->id)->count()
        );
        $this->assertSame(
            1,
            FormExperiment::where('form_id', $form->id)->count()
        );
        $this->assertSame(
            0,
            FormQuestionResponse::where('form_lead_id', $formLead->id)->count()
        );
    }

    /**
     * @test
     *
     * It should only reset specific Form statistics
     *
     * Test: Delete /api/forms/{form2}/resetstatus
     *
     * @return void
     */
    public function itShouldResetSpecificFormStatus()
    {
        $user = User::factory()->create();
        $form1 = Form::factory()->create(['created_by' => $user->id]);
        $form2 = Form::factory()->create(['created_by' => $user->id]);
        $formLead = FormLead::factory()->create(['form_id' => $form1->id]);
        $formLead2 = FormLead::factory()->create(['form_id' => $form2->id]);
        FormVisit::factory()->create(
            [
                'form_id' => $form1->id,
                'form_variant_id' => $formLead->form_variant_id
            ]
        );
        FormVisit::factory()->create(
            [
                'form_id' => $form2->id,
                'form_variant_id' => $formLead2->form_variant_id
            ]
        );
        $formQuestion = FormQuestion::factory()->create(['form_question_type_id' => 5]);
        FormExperiment::factory()->create(['form_id' => $form1->id]);
        FormExperiment::factory()->create(['form_id' => $form2->id]);
        FormQuestionResponse::factory()->create(['form_question_id' => $formQuestion->id]);
        $response = $this->delete(
            '/api/forms/' . $form1->id . '/resetstatus',
            [],
            $this->headers($user)
        );
        $response->assertStatus(200);
        $this->assertSame(
            1,
            FormVisit::where('form_id', $form2->id)->count()
        );
        $this->assertSame(
            1,
            FormLead::where('form_id', $form2->id)->count()
        );
        $this->assertSame(
            1,
            FormExperiment::where('form_id', $form2->id)->count()
        );
        $this->assertSame(
            1,
            FormQuestionResponse::where('form_question_id', $formQuestion->id)->count()
        );
    }

    /**
     * @test
     *
     * It should not let unauthorised user to reset form status
     *
     * Test: Delete /api/forms/{form}/resetstatus
     *
     * @return void
     */
    public function itShoulNotdResetFomStatus()
    {
        $user = User::factory()->create();
        $user2 = User::factory()->create();
        $form = Form::factory()->create(['created_by' => $user->id]);
        $response = $this->delete(
            '/api/forms/' . $form->id . '/resetstatus',
            [],
            $this->headers($user2)
        );
        $response->assertStatus(403);
    }
}
