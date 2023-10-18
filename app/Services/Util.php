<?php

namespace App\Services;

use GuzzleHttp\Client as HttpCient;
use Ramsey\Uuid\Uuid;
use App\Enums\DisksEnum;
use App\Enums\MimeEnum;
use App\Enums\HttpVerbsEnum;
use App\Enums\QuestionTypesEnum;
use App\FormWebhook;
use App\FormWebhookRequest;
use App\FormQuestion;
use App\FormLead;
use App\Form;
use Log;
use Agent;
use Cache;
use Storage;
use Exception;
use Sentry;

class Util
{
    protected $httpClient;

    public function __construct(HttpCient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function apiCall($url, $method, array $body, array $headers = [])
    {
        try {
            if (HttpVerbsEnum::PUT || HttpVerbsEnum::POST) {
                return $this->httpClient->request(
                    $method,
                    $url,
                    $body
                );
            } else {
                return false;
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e->getResponse();
        }
    }

    public function apiResponse(
        int $code = 200,
        array $data = [],
        string $error_type = "",
        string $error_message = "",
        array $errors = [],
        array $pagination = []
    ) {
        $response = [
            'meta' => [
                'code' => $code
            ]
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        if (!empty($error_type)) {
            $response['meta']['error_type'] = $error_type;
        }

        if (!empty($error_message)) {
            $response['meta']['error_message'] = $error_message;
        }

        if (!empty($errors)) {
            $response['meta']['errors'] = $errors;
        }

        if (!empty($pagination)) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $code);
    }

    public function verifyRecaptcha($response, $secret = '')
    {

        if (empty($secret)) {
            $secret = $this->config('leadgen.google_irecaptcha_secret_key');
        }

        $result = $this->httpClient->request(
            'POST',
            'https://www.google.com/recaptcha/api/siteverify',
            [
                'form_params' => [
                    'secret' => $secret,
                    'response' => $response
                ]
            ]
        );

        $content = $result->getBody()->getContents();

        return json_decode($content, true)['success'];
    }

    public function isValidDomainName($domain_name)
    {
        return preg_match(
            "/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/", // phpcs:ignore
            $domain_name
        );
    }

    public function uuid4()
    {
        return Uuid::uuid4();
    }

    public function deviceType()
    {
        if (Agent::isMobile() && !Agent::isTablet()) {
            return 'MOBILE';
        } elseif (Agent::isTablet()) {
            return 'TABLET';
        } elseif (Agent::isDesktop()) {
            return 'DESKTOP';
        } else {
            return '';
        }
    }

    public function platform()
    {
        return Agent::platform();
    }

    public function logException($e)
    {
        Log::info("Exception class: " . get_class($e));
        Log::info("Exception message: " . $e->getMessage());
        Log::info("Exception trace: " . $e->getTraceAsString());
    }

    public function arrayMergeRecursiveDistinct(array &$array1, array &$array2)
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                if (isset($merged[$key])) {
                    $merged[$key] = $value;
                }
            }
        }
        return $merged;
    }

    public function config($key)
    {
        if (strpos(php_sapi_name(), 'cli') === false) {
            $protocol = request()->secure() ? 'https://' : 'http://';
        } else {
            $protocol = 'http://';
        }

        if (
            $key === 'leadgen.api_url' ||
            $key === 'leadgen.app_url' ||
            $key === 'leadgen.client_app_url' ||
            $key === 'leadgen.pages_domain' ||
            $key === 'leadgen.forms_domain' ||
            $key === 'leadgen.proofs_domain' ||
            $key === 'leadgen.scripts_domain' ||
            $key === 'leadgen.client_app_token_login_url'
        ) {
            return $protocol . config($key);
        }

        return config($key);
    }

    public function sendVariantWebhook(
        FormWebhook $webhook,
        $questionResponses,
        $hiddenFieldResponses,
        FormLead $lead
    ) {
        if ($questionResponses->count() === 0) {
            return;
        }

        $payload = [
            'id' => $lead->id,
            'meta_reference_no' => !is_string($lead->reference_no) ? $lead->reference_no->
            toString() : $lead->reference_no,
            'meta_device_type' => $lead->device_type,
            'meta_os' => $lead->formVisit->os,
            'meta_browser' => $lead->formVisit->browser,
            'meta_source_url' => $lead->formVisit->source_url,
            'meta_ip' => $lead->formVisit->ip,
            'meta_created_at' => $lead->created_at->toDateTimeString(),
        ];

        if (is_numeric($lead->calculator_total)) {
            $payload['meta_calculator_total'] = $lead->calculator_total;
        }

        foreach ($questionResponses as $questionResponse) {
            // filter GDPR if disabled
            $formQuestion = FormQuestion::find($questionResponse->form_question_id);
            if (
                $formQuestion->config['type'] === QuestionTypesEnum::GDPR &&
                $formQuestion->config['enabled'] === 'false'
            ) {
                continue;
            }

            foreach ($webhook->fields_map as $fieldMap) {
                if (
                    !empty($fieldMap['questionId']) &&
                    $fieldMap['questionId'] == $questionResponse->form_question_id
                ) {
                    if ($formQuestion->config['type'] === QuestionTypesEnum::ADDRESS) {
                        $addressQuestionValue = '';
                        $addressFields = $questionResponse->response;

                        $fieldsEnabled = array_reduce($formQuestion->config['fields'], function ($c, $f) {
                            if ($f['enabled'] === 'true') {
                                array_push($c, $f['id']);
                            }
                            return $c;
                        }, []);

                        if (count($fieldsEnabled) === 1) {
                            $addressQuestionValue = $addressFields[$fieldsEnabled[0]];
                        } elseif (count($fieldsEnabled) > 1) {
                            foreach ($addressFields as $addressFieldKey => $addressFieldValue) {
                                if (!in_array($addressFieldKey, $fieldsEnabled)) {
                                    continue;
                                }
                                $addressQuestionValue .= "$addressFieldKey: $addressFieldValue\n";
                            }
                        }

                        $payload[$fieldMap['to']] = $addressQuestionValue;
                    } elseif ($formQuestion->config['type'] === QuestionTypesEnum::MULTIPLE_CHOICE) {
                        if (is_array($questionResponse->response)) {
                            $payload[$fieldMap['to']] = array_map(function ($choice) {
                                return $choice['label'];
                            }, $questionResponse->response);
                            $payload[$fieldMap['to']] = implode(',', $payload[$fieldMap['to']]);
                        } else {
                            $payload[$fieldMap['to']] = $questionResponse->response;
                        }
                    } elseif ($formQuestion->config['type'] === QuestionTypesEnum::SINGLE_CHOICE) {
                        if (is_array($questionResponse->response)) {
                            $payload[$fieldMap['to']] = $questionResponse->response['label'];
                        } else {
                            $payload[$fieldMap['to']] = $questionResponse->response;
                        }
                    } elseif ($formQuestion->config['type'] === QuestionTypesEnum::GDPR) {
                        if (is_array($questionResponse->response)) {
                            if (count($questionResponse->response) > 0 && is_array($questionResponse->response[0])) {
                                $payload[$fieldMap['to']] = implode(', ', array_map(function ($choice, $index) {
                                    return 'option ' . ($index + 1);
                                }, $questionResponse->response, array_keys($questionResponse->response)));
                            } else {
                                $payload[$fieldMap['to']] = implode(', ', $questionResponse->response);
                            }
                        } else {
                            $payload[$fieldMap['to']] = $questionResponse->response;
                        }
                    } else {
                        $payload[$fieldMap['to']] = $questionResponse->response;
                    }
                    break;
                }
            }
        }

        foreach ($hiddenFieldResponses as $hiddenFieldResponse) {
            foreach ($webhook->fields_map as $fieldMap) {
                if (
                    !empty($fieldMap['hiddenFieldId']) &&
                    $fieldMap['hiddenFieldId'] == $hiddenFieldResponse->form_hidden_field_id
                ) {
                    $payload[$fieldMap['to']] = $hiddenFieldResponse->response;
                    break;
                }
            }
        }

        $variant = $lead->formVariant;
        if (!empty($variant->calculator_field_name) && is_numeric($lead->calculator_total)) {
            $payload[$variant->calculator_field_name] = $lead->calculator_total;
        }

        $this->sendWebhook($webhook, $payload, $lead);
    }

    public function sendGlobalWebhook(
        FormWebhook $webhook,
        Form $form,
        $questionResponses,
        $hiddenFieldResponses,
        FormLead $lead
    ) {
        $payload = [
            'id' => $lead->id,
            'meta_reference_no' => !is_string($lead->reference_no) ? $lead->reference_no->
            toString() : $lead->reference_no,
            'meta_device_type' => $lead->device_type,
            'meta_os' => $lead->formVisit->os,
            'meta_browser' => $lead->formVisit->browser,
            'meta_source_url' => $lead->formVisit->source_url,
            'meta_ip' => $lead->formVisit->ip,
            'meta_created_at' => $lead->created_at->toDateTimeString(),
        ];

        if (is_numeric($lead->calculator_total)) {
            $payload['meta_calculator_total'] = $lead->calculator_total;
        }

        $variant = $form->variants->where('id', $lead->form_variant_id)->first();
        $variantState = $variant->buildState();
        $sIndex = 1;
        foreach ($variantState['steps'] as $step) {
            $qIndex = 1;
            foreach ($step['questions'] as $question) {
                if (
                    $question['type'] === QuestionTypesEnum::GDPR &&
                    $question['enabled'] === false
                ) {
                    continue;
                }

                if (empty($question['field_name'])) {
                    $fieldName = 'S' . $sIndex . '_Q' . $qIndex;
                } else {
                    $fieldName = $question['field_name'];
                }

                $fieldValue = $questionResponses->where(
                    'form_question_id',
                    $question['dbId']
                )->first();

                if (!empty($fieldValue)) {
                    if ($question['type'] === QuestionTypesEnum::ADDRESS) {
                        $addressQuestionValue = '';
                        $addressFields = $fieldValue->response;

                        $fieldsEnabled = array_reduce($question['fields'], function ($c, $f) {
                            if ($f['enabled'] === true) {
                                array_push($c, $f['id']);
                            }
                            return $c;
                        }, []);

                        if (count($fieldsEnabled) === 1) {
                            $addressQuestionValue = $addressFields[$fieldsEnabled[0]];
                        } elseif (count($fieldsEnabled) > 1) {
                            foreach ($addressFields as $addressFieldKey => $addressFieldValue) {
                                if (!in_array($addressFieldKey, $fieldsEnabled)) {
                                    continue;
                                }
                                $addressQuestionValue .= "$addressFieldKey: $addressFieldValue\n";
                            }
                        }

                        $fieldValue = $addressQuestionValue;
                    } elseif ($question['type'] === QuestionTypesEnum::MULTIPLE_CHOICE) {
                        if (is_array($fieldValue->response)) {
                            $fieldValue = array_map(function ($choice) {
                                return $choice['label'];
                            }, $fieldValue->response);
                            $fieldValue = implode(',', $fieldValue);
                        } else {
                            $fieldValue = $fieldValue->response;
                        }
                    } elseif ($question['type'] === QuestionTypesEnum::SINGLE_CHOICE) {
                        if (is_array($fieldValue->response)) {
                            $fieldValue = $fieldValue->response['label'];
                        } else {
                            $fieldValue = $fieldValue->response;
                        }
                    } elseif ($question['type'] === QuestionTypesEnum::GDPR) {
                        if (is_array($fieldValue->response)) {
                            if (count($fieldValue->response) > 0 && is_array($fieldValue->response[0])) {
                                $fieldValue = implode(', ', array_map(function ($choice, $index) {
                                    return 'option ' . ($index + 1);
                                }, $fieldValue->response, array_keys($fieldValue->response)));
                            } else {
                                $fieldValue = implode(', ', $fieldValue->response);
                            }
                        } else {
                            $fieldValue = $fieldValue->response;
                        }
                    } else {
                        $fieldValue = $fieldValue->response;
                    }
                }

                $payload[$fieldName] = empty($fieldValue) ? '' : $fieldValue;
                $qIndex++;
            }
            $sIndex++;
        }

        $hiddenFields = $variant->formHiddenFields;
        foreach ($hiddenFields as $hiddenField) {
            $fieldValue = $hiddenFieldResponses->where(
                'form_hidden_field_id',
                $hiddenField->id
            )->first();

            $fieldValue = $fieldValue->response;

            $payload[$hiddenField->name] = empty($fieldValue) ? '' : $fieldValue;
        }

        if (!empty($variant->calculator_field_name) && is_numeric($lead->calculator_total)) {
            $payload[$variant->calculator_field_name] = $lead->calculator_total;
        }

        $this->sendWebhook($webhook, $payload, $lead);
    }

    public function sendWebhook(FormWebhook $webhook, array $payload, FormLead $lead)
    {
        $body = [];
        $headers = [];

        $secret = json_decode($webhook->secret, true);
        if (!empty($secret['key'])) {
            $payload[$secret['key']] = $secret['value'];
        }

        if ($webhook->format === MimeEnum::FORM_URLENCODED) {
            $body['form_params'] = $payload;
        } elseif ($webhook->format === MimeEnum::JSON) {
            $body['json'] = $payload;
        }

        $webhookHeaders = json_decode($webhook->headers, true);
        if (!empty($webhookHeaders)) {
            foreach ($webhookHeaders as $webhookHeader) {
                if (!empty($webhookHeader['key'])) {
                    $headers[$webhookHeader['key']] = $webhookHeader['value'];
                }
            }
        }

        $body['headers'] = $headers;

        try {
            $response = $this->httpClient->request(
                $webhook->method,
                $webhook->url,
                $body
            );
            FormWebhookRequest::create([
                'form_lead_id' => $lead->id,
                'form_webhook_id' => $webhook->id,
                'response_status' => $response->getStatusCode(),
                'response_content' => $response->getBody()->getContents(),
                'error' => false,
                'error_message' => null,
                'payload' => json_encode($payload),
                'form_variant_id' => $lead->form_variant_id
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            FormWebhookRequest::create([
                'form_lead_id' => $lead->id,
                'form_webhook_id' => $webhook->id,
                'response_status' => $response->getStatusCode(),
                'response_content' => $response->getBody()->getContents(),
                'error' => true,
                'error_message' => $e->getMessage(),
                'payload' => json_encode($payload),
                'form_variant_id' => $lead->form_variant_id
            ]);
        } catch (\Exception $e) {
            FormWebhookRequest::create([
                'form_lead_id' => $lead->id,
                'form_webhook_id' => $webhook->id,
                'response_status' => -1,
                'response_content' => null,
                'error' => true,
                'error_message' => $e->getMessage(),
                'payload' => json_encode($payload),
                'form_variant_id' => $lead->form_variant_id
            ]);
        }
    }

    public function getRealQuery($query)
    {
        $sql = $query->toSql();

        foreach ($query->getBindings() as $binding) {
            $sql = preg_replace('/\?/', $binding, $sql, 1);
        }

        return $sql;
    }

    /**
     * Get IP Address geolocation.
     *
     * @access public
     *
     * @param string $request IP Address.
     * @return mixed
     */
    public function geolocation($ip)
    {
        try {
            $context = stream_context_create(['http' => ['timeout' => 1]]);
            $ipInfo = file_get_contents('http://www.geoplugin.net/php.gp?ip=' . $ip, false, $context);

            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                throw new Exception("Invalid Data: IP address is invalid.");
            }

            if ($ipInfo === false) {
                throw new Exception("Timeout: Geolocation lookup timed out.");
            }

            $ipInfo = unserialize($ipInfo);

            if (empty($ipInfo)) {
                throw new Exception("Empty: Geolocation lookup failed.");
            }

            if (is_array($ipInfo) && $ipInfo['geoplugin_status'] === 429) {
                throw new Exception("Quota Exceeded: Geolocation lookup quota exceeded.");
            }

            if (is_array($ipInfo) && $ipInfo['geoplugin_status'] === 404) {
                throw new Exception("Not Found: Geolocation lookup failed.");
            }

            if (!$ipInfo) {
                throw new Exception("Undefined: Geolocation lookup has some unknown issue.");
            }

            return $ipInfo;
        } catch (Exception $e) {
            Sentry\captureException($e);
            // fallback code
            return [
                'geoplugin_request' => $ip,
                'geoplugin_status' => 404,
                'geoplugin_countryName' => 'United States',
                'geoplugin_countryCode' => 'US',
                'geoplugin_regionName' => 'California',
                'geoplugin_regionCode' => 'CA',
                'geoplugin_city' => 'San Francisco',
                'geoplugin_latitude' => '37.7749',
                'geoplugin_longitude' => '-122.4194',
                'geoplugin_currencyCode' => 'USD',
                'geoplugin_currencySymbol_UTF8' => '$',
                'geoplugin_timezone' => 'America/Los_Angeles',
            ];
        }
    }

    /**
     * Report exception to sentry.
     *
     * @param \Exception $exception
     * @return void
     */
    public static function reportSentry(\Exception $exception)
    {
        if (!app()->bound('sentry')) {
            return;
        }

        app('sentry')->captureException($exception);
    }

    public function themeDefault(): array
    {
        if (Cache::has('form_default_theme')) {
            return Cache::get('form_default_theme');
        }

        $themeDefaults = Storage::disk(DisksEnum::RESOURCES)->get('/leadgenform/js/theme-defaults.json');

        $themeDefaults = json_decode($themeDefaults, true);

        Cache::put('form_default_theme', $themeDefaults, 60 * 60);

        return $themeDefaults;
    }

    public function getCountries(): array
    {
        if (Cache::has('countries')) {
            return Cache::get('countries');
        }

        $countries = Storage::get('data/countries.json');

        $countries = json_decode($countries, true);

        Cache::put('countries', $countries, 60 * 60);

        return $countries;
    }

    public function getGoogleFonts(): array
    {
        if (Cache::has('google_fonts')) {
            return Cache::get('google_fonts');
        }

        $googleFonts = Storage::get('data/google-fonts.json');

        $googleFonts = json_decode($googleFonts, true);

        Cache::put('google_fonts', $googleFonts, 60 * 60);

        return $googleFonts;
    }

    public function getAsPrefixedTableColumns(string $model, string $prefix = ''): array
    {
        $instance = null;

        try {
            $instance = new $model();
        } catch (\Exception $e) {
            return [];
        }

        $attributes = $instance->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($instance->getTable());

        if (empty($prefix)) {
            $prefix = $instance->getTable() . '.';
        }

        $prefixed = [];

        foreach ($attributes as $attribute) {
            $col = $instance->getTable() . '.' . $attribute;
            $col_as = $prefix . $attribute;
            $prefixed[$attribute] = $col . ' as ' . $col_as;
        }

        return $prefixed;
    }

    public function isJson($value)
    {
        if (!is_string($value)) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    private function getQuestionResponse($id, $questionResponses)
    {
        foreach ($questionResponses as $questionResponse) {
            if ($questionResponse['form_question_id'] === $id) {
                return $questionResponse;
            }
        }
    }
}
