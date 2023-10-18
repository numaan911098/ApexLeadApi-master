<?php

namespace Tests\Feature\Controllers;

use App\Enums\Form\FormTrackingEventTypesEnum;
use App\Form;
use App\Models\Form\FormTrackingEvent;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestHelpers;

class FormTrackingEventsControllerTest extends TestCase
{
    use DatabaseTransactions;
    use TestHelpers;

    /**
     * @test
     *
     * @return void
     */
    public function itShouldReturnTrackingEventsForNewlyCreatedForm()
    {
        $user = User::factory()->create();
        $formStub = Form::factory()->create([
            'created_by' => $user->id,
        ]);

        $response = $this->get("/api/forms/{$formStub->id}/tracking-events", $this->apiHeaders([], null, $user));
        $responseContent = $response->getOriginalContent();

        $this->assertSame(count(FormTrackingEventTypesEnum::getConstants()), count($responseContent['data']));
    }

    /**
     * @test
     *
     * @return void
     */
    public function itShouldUpdateTrackingEvent()
    {
        $user = User::factory()->create();
        $formStub = Form::factory()->create([
            'created_by' => $user->id,
        ]);
        $formTrackingEvent = FormTrackingEvent::factory([
            'title' => FormTrackingEventTypesEnum::INTERACTED,
            'event' => FormTrackingEventTypesEnum::INTERACTED,
            'script' => "console.log('hello')",
            'active' => 1,
            'configured' => 0,
            'form_id' => $formStub->id,
        ])->create();

        $response = $this->put("/api/form-tracking-events/{$formTrackingEvent->id}", [
            'script' => "console.log('test')",
            'active' => 0,
        ], $this->apiHeaders([], null, $user));
        $responseContent = $response->getOriginalContent();

        $this->assertSame("console.log('test')", $responseContent['data']['script']);
        $this->assertSame(0, $responseContent['data']['active']);
    }
}
