<?php

namespace App\Events;

use App\Form;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\FormVariant;

class FormVariantUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @var FormVariant
     */
    public $variant;

    /**
     * @var Form
     */
    public $form;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(FormVariant $variant, Form $form)
    {
        $this->form = $form;
        $this->variant = $variant;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
