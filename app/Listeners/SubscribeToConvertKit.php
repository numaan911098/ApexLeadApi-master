<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\UserRegistered;
use App\Services\ConvertKit;
use Log;

class SubscribeToConvertKit
{
    /**
     * ConvertKit instance.
     *
     * @var ConvertKit
     */
    protected $convertKit;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(ConvertKit $convertKit)
    {
        $this->convertKit = $convertKit;
    }

    /**
     * Handle the event.
     *
     * @param  UserRegistered  $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
        $user = $event->user;

        $enabled =  config('leadgen.convert_kit_enabled');
        $sequenceId = config('leadgen.convert_kit_sequence_id');
        $tagId = config('leadgen.convert_kit_free_tag_id');

        if (!$enabled || !$user->subscribe_newsletter) {
            return;
        }

        if (empty($sequenceId)) {
            return;
        }

        $this->convertKit->addSubscriberToSequence($sequenceId, [
            'email' => $user->email,
            'name' => $user->name,
            'first_name' => explode(' ', $user->name)[0],
            'tags' => [
                $tagId
            ]
        ]);
    }
}
