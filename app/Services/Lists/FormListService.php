<?php

namespace App\Services\Lists;

use App\Form;
use App\User;
use App\Modules\Security\Services\AuthService;

class FormListService
{
    /**
     * @var Form
     */
    protected Form $formModel;

    /**
     * @var User
     */
    protected User $userModel;

    /**
     * AuthService instance.
     */
    protected AuthService $authService;

    /**
     * records per page
     */
    protected const PER_PAGE = 15;

    /**
     * FormListService constructor.
     * @param User $user
     * @param Form $form
     * @param AuthService $authService
     */
    public function __construct(
        User $user,
        Form $form,
        AuthService $authService
    ) {
        $this->userModel = $user;
        $this->formModel = $form;
        $this->authService = $authService;
    }

    /**
     * List all forms.
     * @param array $data
     * @return array
     */
    public function getList(array $data): array
    {
        $forms = $this->formModel
            ->where('created_by', $this->authService->getUserId())
            ->withCount(['formLeads', 'viewedLeads', 'formTotalLeads', 'formLeadsPartial']);

        $forms = $forms->when($data['search']['title'], function ($query, $value) {
            return $query->where('title', 'LIKE', '%' . $value . '%');
        });

        $sortField = $data['sortField'];
        $sortDirection = $data['sortDirection'];

        $formsPagination = $forms
            ->orderBy($sortField, $sortDirection)
            ->paginate(FormListService::PER_PAGE, ['*'], 'page', $data['page']);

        $forms = $formsPagination->items();

        foreach ($forms as $form) {
            $form->conversion_rate = $form->conversionRate();
            $form->blocked = $form->isBlocked();
            $form->unread_leads = $form->form_total_leads_count - $form->viewed_leads_count;
        }

        $pagination = $formsPagination->toArray();
        unset($pagination['data']);

        return [
            'data' => $forms,
            'pagination' => $pagination
        ];
    }

    /**
     * Bulk delete forms.
     * @param object $form
     * @param string $formKeys
     * @return boolean
     */
    public function massDestroy(Form $form, string $formKeys): bool
    {
        $formsArray = explode(',', $formKeys);
        return $form->whereKey($formsArray)->delete();
    }

    /**
     * Bulk duplicate forms.
     * @param string $formKeys
     * @return void
     */
    public function massDuplicate(string $formKeys): void
    {
        $duplicateForms = explode(',', $formKeys);
        $forms = $this->formModel->findOrFail($duplicateForms);
        foreach ($forms as $formItem) {
            $duplicatedForm =  $formItem->duplicate();
            $duplicatedForm->save();
        }
    }
}
