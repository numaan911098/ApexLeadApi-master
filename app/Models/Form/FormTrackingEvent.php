<?php

namespace App\Models\Form;

use App\Enums\Form\FormTrackingEventTypesEnum;
use App\Form;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class FormTrackingEvent extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'event',
        'form_id',
        'active',
        'configured',
        'script',
    ];

    /**
     * @param Form $form
     * @return Collection
     */
    public function getEvents(Form $form): Collection
    {
        $eventTypes = FormTrackingEventTypesEnum::getConstants();

        $events = collect([]);
        foreach ($eventTypes as $eventType) {
            $event = $this
                ->where('event', $eventType)
                ->where('form_id', $form->id)
                ->first();

            if (!$event) {
                $event = $this->create([
                    'title' => ucwords(str_replace('_', ' ', $eventType)),
                    'form_id' => $form->id,
                    'event' => $eventType,
                    'active' => false,
                    'configured' => false,
                    'script' => '',
                ]);
            }

            $events->push($event);
        }

        return $events;
    }

    /**
     * @return BelongsTo
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
