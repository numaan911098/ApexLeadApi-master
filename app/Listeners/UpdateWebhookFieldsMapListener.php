<?php

namespace App\Listeners;

use App\Events\FormVariantUpdated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\FormVariant;
use App\FormWebhook;
use Log;
use App\Enums\QuestionTypesEnum;

class UpdateWebhookFieldsMapListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  FormVariantUpdated  $event
     * @return void
     */
    public function handle(FormVariantUpdated $event)
    {
        $this->updateWebhooksFieldMap($event->variant);
    }

    private function updateWebhooksFieldMap(FormVariant $variant)
    {
        $variant = FormVariant::find($variant->id);

        $webhooks = FormWebhook::where('form_variant_id', $variant->id)->get();

        $variantState = $variant->buildState();

        $newFieldsMap = collect();
        $sIndex = 1;
        foreach ($variantState['steps'] as $step) {
            if (empty($step['questions'])) {
                $sIndex++;
                continue;
            }
            $qIndex = 1;
            foreach ($step['questions'] as $question) {
                if ($question['type'] === QuestionTypesEnum::GDPR && !$question['enabled']) {
                    $qIndex++;
                    continue;
                }
                $newFieldsMap->push([
                    'from' => 'S' . $sIndex . '_Q' . $qIndex,
                    'to' => empty($question['field_name']) ? 'S' . $sIndex . '_Q' . $qIndex : $question['field_name'],
                    'questionId' => $question['dbId']
                ]);
                $qIndex++;
            }
            $sIndex++;
        }

        $hiddenFields = $variant->formHiddenFields->toArray();
        foreach ($hiddenFields as $hiddenField) {
            $newFieldsMap->push([
                'from' => $hiddenField['name'],
                'to' => $hiddenField['name'],
                'hiddenFieldId' => $hiddenField['id']
            ]);
        }

        foreach ($webhooks as $webhook) {
            $fieldsMap = $webhook->fields_map;
            $existingMaps = collect();
            $existingQuestionMaps = collect();
            // preserve existing maps
            foreach ($fieldsMap as $fieldMap) {
                if (!empty($fieldMap['questionId'])) {
                    $map = $newFieldsMap->where('questionId', $fieldMap['questionId'])->first();
                } elseif (!empty($fieldMap['hiddenFieldId'])) {
                    $map = $newFieldsMap->where('hiddenFieldId', $fieldMap['hiddenFieldId'])->first();
                }

                if (!empty($map)) {
                    $map['to'] = $fieldMap['to'];
                    $existingMaps->push($map);
                    if (!empty($fieldMap['questionId'])) {
                        $existingQuestionMaps->push($map);
                    }
                }
            }

            // override new maps with existin maps
            $newFieldsMap = $newFieldsMap
                ->map(function ($map) use ($existingMaps, $existingQuestionMaps) {
                    if (!empty($map['questionId'])) {
                        $m = $existingMaps
                                ->where('questionId', $map['questionId'])
                                ->first();
                    } elseif (!empty($map['hiddenFieldId'])) {
                        $hasDupQuestionMap = $existingQuestionMaps
                            ->where('to', $map['to'])
                            ->first() !== null;
                        $m = $existingMaps
                            ->where('hiddenFieldId', $map['hiddenFieldId'])
                            ->first();
                        if (!empty($m) && $hasDupQuestionMap) {
                            $m['to'] = $map['to'] . '__hiddenfield';
                        }
                        if ($hasDupQuestionMap) {
                            $map['to'] = $map['to'] . '__hiddenfield';
                        }
                    }
                    if (!empty($m)) {
                        $map['to'] = $m['to'];
                    }
                    return $map;
                });

            $webhook->fields_map = json_encode($newFieldsMap->toArray());
            $webhook->save();
        }
    }
}
