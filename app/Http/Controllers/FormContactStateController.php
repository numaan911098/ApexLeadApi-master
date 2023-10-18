<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreContactState;
use App\Models\ContactState;
use App\Form;
use App\FormVariant;
use Illuminate\Support\Facades\Log;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Modules\Security\Services\AuthService;

class FormContactStateController extends Controller
{
    /**
     * AuthService instance.
    */
    protected AuthService $authService;
    protected $form;
    protected ContactState $contactState;
    public function __construct(
        Form $form,
        ContactState $contactState,
        AuthService $authService
    ) {
        $this->formModel = $form;
        $this->contactState = $contactState;
        $this->authService = $authService;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreContactState $request)
    {
        $variant = FormVariant::find($request->input('form_variant_id'));
        $data = $request->all();
        if ($data['enable']) {
            $checkIfAlreadyTrue = $this->checkIfAlreadyTrue($data['form_id']);
            if ($checkIfAlreadyTrue) {
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypes::CONTACT_STATE_EXISTS,
                    'There is already an enabled contact state for this form.'
                );
            }
        }

        $data['user_id'] = $this->authService->getUserId();
        $contactState = $this->contactState->create($data);
        $contactState->form_variant = $variant;

        return $this->apiResponse(200, $contactState->toArray());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, $connectionId)
    {
        $fetchedContactState = $this->contactState->where('id', $connectionId)->first();
        return $this->apiResponse(200, $fetchedContactState->toArray(), '', '', []);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreContactState $request, $id)
    {
        if ($request->input('contactstate')['enable']) {
            $checkIfAlreadyTrue = $this->checkIfAlreadyTrue($request->input('contactstate')['form_id'], $id);
            if ($checkIfAlreadyTrue) {
                return $this->apiResponse(
                    400,
                    [],
                    ErrorTypes::CONTACT_STATE_EXISTS,
                    'There is already an enabled contact state for this form.'
                );
            }
        }
        $res = $this->contactState->find($id);
        $res->title = $request->input('contactstate')['title'];
        $res->enable = $request->input('contactstate')['enable'];
        $res->landingpage_id =  $request->input('contactstate')['landingpage_id'];
        $res->form_id = $request->input('contactstate')['form_id'];
        $res->user_id = $this->authService->getUserId();
        if (empty($request->input('contactstate')['form_variant_id'])) {
            $res->form_variant_id = null;
            $res->save();
        } else {
            $variant = FormVariant::findOrFail($request->input('contactstate')['form_variant_id']);
            $res->form_variant_id = $variant->id;
            $res->save();
            $res->form_variant = $variant;
        }

        return $this->apiResponse(200, $res->toArray());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Form $form)
    {
        $this->contactState->where('id', $id)->delete();
        return $this->apiResponse(200);
    }
     /**
     * Check if there is already an enabled contact state.
     *
     * @param  int  $id
     * @return boolean
     */

    public function checkIfAlreadyTrue($formId, $connectionId = null)
    {
        $result = $this->contactState->where('form_id', $formId)->where('id', '!=', $connectionId)->get();
        foreach ($result as $res) {
            if ($res->enable) {
                return true;
            }
        }
        return false;
    }
}
