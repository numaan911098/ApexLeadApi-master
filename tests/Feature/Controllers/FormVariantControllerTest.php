<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\{FormVariantType, FormVariant, FormQuestion, Form, FormStep};
use JWTAuth;

class FormVariantControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     *
     *  Test: Get /api/forms/{form}/variants/{variant}/duplicate
     *
     * @return void
     */
    public function itShouldCloneFormVariant()
    {
        $form = Form::factory()->create();

        $variant = FormVariant::factory()->create([
            'form_id' => $form->id
        ]);

        $response = $this->get(
            '/api/forms/' . $form->id . '/variants/' . $variant->id . '/duplicate',
            $this->headers($form->createdBy)
        );

        $this->assertSame(2, $form->formVariants->count());

        $response->assertStatus(200);
    }


    /**
     * @test
     *
     *  Test: Get /api/forms/{form}/variants/{variant}/duplicate
     *
     * @return void
     */
    public function itShouldCloneFormVariantWithSteps()
    {
        $form = Form::factory()->create();
        $champion = (new FormVariantType())->champion();
        $challenger = (new FormVariantType())->challenger();

        //duplicate variant 1
        $variant1 = FormVariant::factory()->create([
            'form_id' => $form->id,
            'form_variant_type_id' => $champion->id
        ]);

        $response = $this->get(
            '/api/forms/' . $form->id . '/variants/' . $variant1->id . '/duplicate',
            $this->headers($form->createdBy)
        );
        $content = $response->getOriginalContent();
        $duplicateVariant = FormVariant::find($content['data']['id']);

        $response->assertStatus(200);
        $this->assertSame(0, $duplicateVariant->formSteps->count());

        //duplicate variant 2
        $variant2 = FormVariant::factory()->create([
            'form_id' => $form->id,
            'form_variant_type_id' => $challenger->id
        ]);

        $variant2Steps = FormStep::factory()->count(2)->create([
            'form_variant_id' => $variant2->id,
            'form_id' => $form->id
        ]);

        $response = $this->get(
            '/api/forms/' . $form->id . '/variants/' . $variant2->id . '/duplicate',
            $this->headers($form->createdBy)
        );
        $content = $response->getOriginalContent();
        $duplicateVariant = FormVariant::find($content['data']['id']);

        $response->assertStatus(200);
        $this->assertSame(2, $duplicateVariant->formSteps->count());

        //duplicate variant 3
        $variant3 = FormVariant::factory()->create([
            'form_id' => $form->id,
            'form_variant_type_id' => $challenger->id
        ]);
        $variant3Steps = FormStep::factory()->count(4)->create([
            'form_variant_id' => $variant3->id,
            'form_id' => $form->id
        ]);

        $response = $this->get(
            '/api/forms/' . $form->id . '/variants/' . $variant3->id . '/duplicate',
            $this->headers($form->createdBy)
        );
        $content = $response->getOriginalContent();
        $duplicateVariant = FormVariant::find($content['data']['id']);

        $response->assertStatus(200);
        $this->assertSame(4, $duplicateVariant->formSteps->count());

        $this->assertSame(6, $form->variants->count());
    }


    /**
     * @test
     *
     *  Test: Get /api/forms/{form}/variants/{variant}/duplicate
     *
     * @return void
     */
    public function itShouldCloneFormVariantWithStepsQuestions()
    {
        $form = Form::factory()->create();
        $champion = (new FormVariantType())->champion();
        $challenger = (new FormVariantType())->challenger();

        //duplicate variant 1
        $variant1 = FormVariant::factory()->create([
            'form_id' => $form->id,
            'form_variant_type_id' => $champion->id
        ]);

        $response = $this->get(
            '/api/forms/' . $form->id . '/variants/' . $variant1->id . '/duplicate',
            $this->headers($form->createdBy)
        );
        $content = $response->getOriginalContent();
        $duplicateVariant = FormVariant::find($content['data']['id']);

        $response->assertStatus(200);
        $this->assertSame(0, $duplicateVariant->formSteps->count());
        foreach ($duplicateVariant->formSteps as $formStep) {
            $this->assertSame(0, $formStep->formQuestions->count());
        }

        //duplicate variant 2
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

        $response = $this->get(
            '/api/forms/' . $form->id . '/variants/' . $variant2->id . '/duplicate',
            $this->headers($form->createdBy)
        );
        $content = $response->getOriginalContent();
        $duplicateVariant = FormVariant::find($content['data']['id']);

        $response->assertStatus(200);
        $this->assertSame(2, $duplicateVariant->formSteps->count());
        foreach ($duplicateVariant->formSteps as $formStep) {
            $this->assertSame(2, $formStep->formQuestions->count());
        }

        //duplicate variant 3
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

        $response = $this->get(
            '/api/forms/' . $form->id . '/variants/' . $variant3->id . '/duplicate',
            $this->headers($form->createdBy)
        );
        $content = $response->getOriginalContent();
        $duplicateVariant = FormVariant::find($content['data']['id']);

        $response->assertStatus(200);
        $this->assertSame(4, $duplicateVariant->formSteps->count());
        foreach ($duplicateVariant->formSteps as $formStep) {
            $this->assertSame(3, $formStep->formQuestions->count());
        }

        $this->assertSame(6, $form->variants->count());
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
}
