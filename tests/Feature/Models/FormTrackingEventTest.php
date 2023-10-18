<?php

namespace Tests\Feature\Models;

use App\Enums\Form\FormTrackingEventTypesEnum;
use App\Form;
use App\Models\Form\FormTrackingEvent;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestHelpers;

class FormTrackingEventTest extends TestCase
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

        $this->assertSame(
            count(FormTrackingEventTypesEnum::getConstants()),
            count(FormTrackingEvent::make()->getEvents($formStub))
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function itShouldReturnTrackingEventsForAlreadyCreatedForm()
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

        $events = FormTrackingEvent::make()->getEvents($formStub);

        foreach ($events as $event) {
            if ($event['event'] !== FormTrackingEventTypesEnum::INTERACTED) {
                continue;
            }

            $this->assertSame("console.log('hello')", $event['script']);
            $this->assertSame(1, $event['active']);
        }
    }
}
