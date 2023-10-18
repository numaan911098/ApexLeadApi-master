<?php

namespace App\Http\Controllers;

use App\Models\FormPartialLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\User;
use App\Form;
use App\Models\GlobalPartialLeadSetting;
use Illuminate\Support\Facades\DB;
use Sentry;

class FormPartialLeadController extends Controller
{
    /**
     * @var User
    */
    protected $userModel;

    /**
     * @var Form
    */
    protected $formModel;

    /**
     * @var FormPartialLead
    */
    protected $formPartialLeadModel;

     /**
     * Constructor.
     *
     * @param User $user
     * @param FormPartialLead $formPartialLead
     * @param Form $formModel
     */
    public function __construct(User $user, FormPartialLead $formPartialLead, Form $form)
    {
        $this->userModel = $user;
        $this->formPartialLeadModel = $formPartialLead;
        $this->formModel = $form;
    }

    /**
     * Store Global Partial Settings
     * @param $request
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = $this->userModel->findOrFail($request->user_id);
            $formIds = $this->formModel->where('created_by', $request->user_id)->pluck('id');
            $user->globalPartialLeadSetting->updateOrCreate(
                ['user_id' => $request->user_id],
                [
                'enabled' => $request->enabled,
                'user_id' => $request->user_id,
                'consent_type' => $request->consentType
                ]
            );
            if (empty($formIds)) {
                return;
            }
            foreach ($formIds as $formId) {
                $this->formPartialLeadModel->updateOrInsert(
                    ['form_id' => $formId],
                    [
                        'enabled' => $request->enabled,
                        'consent_type' => $request->consentType,
                    ]
                );
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Sentry\captureException($e);
        }
    }

    /**
     * Get Global Partial Settings
     * @param int $id
     * @return array
     */
    public function getGlobalPartialSetting($id)
    {
        $user = $this->userModel->findOrFail($id);
        return $this->apiResponse(200, $user->globalPartialLeadSetting->toArray());
    }
}
