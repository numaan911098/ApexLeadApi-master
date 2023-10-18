<?php

namespace App\Listeners;

use App\Events\ProUserRegistered;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\ConvertKit;
use App\User;
use Log;

class TagProInConvertKit
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
     * @param  ProUserRegistered  $event
     * @return void
     */
    public function handle(ProUserRegistered $event)
    {
        $this->subscribeToConverkit($event->user);
    }

    /**
     * Subscribe to ConvertKit.
     *
     * @param User $user
     * @return void
     */
    private function subscribeToConverkit(User $user)
    {
        $enabled = config('leadgen.convert_kit_enabled');
        $sequenceId = config('leadgen.convert_kit_sequence_id');
        $tagId = config('leadgen.convert_kit_pro_tag_id');

        if (!$enabled || !$user->subscribe_newsletter || empty($sequenceId)) {
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
