<?php

namespace App\Services;

use App\Enums\ConfigKeyEnum;
use App\FormVariant;
use App\Mail\PhishingFormDetected;
use Mail;
use Sentry;
use Http;

class PhishingFormService
{
    /**
     * @param FormVariant $variant
     * @return string|null
     */
    public function hasPhishingContent(FormVariant $variant): ?string
    {
        $variantState = $variant->buildState();

        foreach ($variantState['steps'] as $step) {
            foreach ($step['questions'] as $question) {
                if (isset($question['title']) && $this->isPhishingWord($question['title'])) {
                    return $question['title'];
                }

                if (isset($question['placeholder']) && $this->isPhishingWord($question['placeholder'])) {
                    return $question['placeholder'];
                }

                if (isset($question['description']) && $this->isPhishingWord($question['description'])) {
                    return $question['description'];
                }

                if (isset($question['choices'])) {
                    foreach ($question['choices'] as $choice) {
                        if (is_array($choice)) {
                            if ($this->isPhishingWord($choice['label'])) {
                                return $choice['label'];
                            }
                        } elseif ($this->isPhishingWord($choice)) {
                            return $choice;
                        }
                    }
                }
            }

            foreach ($step['elements'] as $element) {
                if (isset($element['content']) && $this->isPhishingWord($element['content'])) {
                    return $element['content'];
                }
            }
        }

        return null;
    }

    /**
     * @param string $content
     * @return bool
     */
    public function isPhishingWord(string $content): bool
    {
        $phishingWords = [
            'password',
            'passw0rd',
            'login',
            'l0gin',
            'log1n',
            'l0g1n',
            'credential',
        ];

        $content = strtolower($content);
        foreach ($phishingWords as $phishingWord) {
            if (strpos($content, $phishingWord) !== false) {
                return true;
            }
        }

        $content = str_replace(
            [' ', '(', ')', '*', '"', "'", "\t", '[', ']', '-', '_'],
            '',
            $content
        );
        foreach ($phishingWords as $phishingWord) {
            if (strpos($content, $phishingWord) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param FormVariant $variant
     * @param string $phishingContent
     */
    public function reportPhishingContent(FormVariant $variant, string $phishingContent)
    {
        try {
            $mail = Mail::to(config('leadgen.emails.leadgen.hello.email'));
            $mail->queue(new PhishingFormDetected($variant, $phishingContent));
        } catch (\Exception $e) {
            \Log::error($e);
            Sentry\captureException($e);
        }

        try {
            $slackUrl = config(ConfigKeyEnum::LEADGEN_SLACK_PHISHING_FORM_REPORT_CHANNEL);

            $formUrl = sprintf(
                "https://%s/preview/forms/%s/variants/%s",
                config('leadgen.forms_domain'),
                $variant->form->id,
                $variant->id
            );

            Http::post($slackUrl, [
                'username' => 'Leadgen BOT',
                'attachments' => [
                    [
                        'pretext' => 'Phishing Form Detected',
                        'title' => 'Phising Forms Report',
                        'color' => '#ee6e73',
                        'title_link' => 'https://leadgenapp.io',
                        'fallback' => 'Phising Forms Report',
                        'fields' => [
                            [
                                'title' => 'Form Details',
                                'value' => implode("\n", [
                                    "Form: {$formUrl}",
                                    "Variant: {$variant->title} ({$variant->id})",
                                    "Customer: {$variant->form->createdBy->email}",
                                    "Phishing Content: {$phishingContent}",
                                ]),
                                'short' => false,
                            ],
                        ],
                        'footer' => 'Powered by leadgen',
                        'ts' => time(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            Sentry\captureException($e);
        }
    }
}
