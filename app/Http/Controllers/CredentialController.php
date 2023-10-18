<?php

namespace App\Http\Controllers;

use App\Enums\CredentialTypesEnum;
use App\Http\Requests\UpdateCredentialRequest;
use App\Modules\Security\Services\AuthService;
use App\Models\Credential;

class CredentialController extends Controller
{
    /**
     * @var Credential Model instance
     */
    private Credential $credentialModel;

    /**
     * @var AuthService Service instance.
     */
    private AuthService $authService;

    public function __construct(Credential $credentialModel, AuthService $authService)
    {
        $this->middleware('jwt.auth');

        $this->credentialModel = $credentialModel;
        $this->authService = $authService;
    }

    /**
     * Get all the credentials of authenticated account.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $authUser = $this->authService->getUser();

        $credentials = $authUser->credentials;

        foreach (CredentialTypesEnum::getConstants() as $credentialType) {
            $credential = $credentials->where('type', $credentialType)->first();

            if (!empty($credential)) {
                continue;
            }

            $data = [
                CredentialTypesEnum::GOOGLE_API_KEY => [
                    'title' => 'Google API key',
                    'type' => CredentialTypesEnum::GOOGLE_API_KEY,
                    'enable' => true,
                    'config' => [
                        'apikey' => null,
                    ],
                    'created_by' => $authUser->id,
                    'updated_by' => $authUser->id,
                ],
            ];

            $this->credentialModel->create($data[$credentialType]);
        }

        $authUser->load('credentials');

        return $this->apiResponse(200, $authUser->credentials->toArray());
    }

    /**
     * Bulk update all credentials.
     *
     * @param UpdateCredentialRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function bulkUpdate(UpdateCredentialRequest $request)
    {
        $authUser = $this->authService->getUser();

        $credentials = $request->input('credentials');

        foreach ($credentials as $credential) {
            $credentialModel = $this->credentialModel->findOrFail($credential['id']);

            $this->authorize('update', $credentialModel);

            $credentialModel->title = $credential['title'];
            $credentialModel->type = $credential['type'];
            $credentialModel->config = $credential['config'];
            $credentialModel->enabled = $credential['enabled'];
            $credentialModel->updated_by = $authUser->id;
            $credentialModel->save();
        }

        return $this->apiResponse(200, $authUser->credentials->toArray());
    }
}
