<?php

namespace App\Events;

use App\FormVariant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Form;

class FormCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @var Form
     */
    public $form;

    /**
     * @var FormVariant
     */
    public $variant;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Form $form, FormVariant $variant)
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
