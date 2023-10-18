<?php

namespace App\Services\Lists;

use App\Form;
use App\LeadProof;
use App\FormQuestion;
use App\FormVariant;
use App\Enums\TimePeriodsEnum;
use App\Modules\Security\Services\AuthService;
use Illuminate\Support\Facades\DB;

class LeadProofListService
{
    /**
     * @var Form
     */
    protected $formModel;

    /**
     * @var LeadProof
     */
    protected $leadProofModel;

    /**
     * @var FormQuestion
     */
    protected $formQuestionModel;

    /**
     * @var FormVariant
     */
    protected $formVariantModel;

    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * records per page
     */
    protected const PER_PAGE = 15;

    /**
     * LeadProofListService constructor.
     * @param Form $form
     * @param LeadProof $leadProof
     * @param FormVariant $formVariant,
     * @param FormQuestion $formQuestion
     * @param AuthService $authService
     */
    public function __construct(
        Form $form,
        LeadProof $leadProof,
        FormVariant $formVariant,
        FormQuestion $formQuestion,
        AuthService $authService
    ) {
        $this->formModel = $form;
        $this->leadProofModel = $leadProof;
        $this->formVariantModel = $formVariant;
        $this->formQuestionModel = $formQuestion;
        $this->authService = $authService;
    }

    /**
     * Get all lead proofs.
     * @param array $data
     * @return null|array
     */
    public function getList(array $data): array
    {
        $authUser = $this->authService->getUser();
        $forms = Form::where('created_by', $authUser->id)->pluck('id');

        $proofs = DB::table('lead_proofs')
            ->join('form_variants', 'lead_proofs.form_variant_id', '=', 'form_variants.id')
            ->select('lead_proofs.*')
            ->whereIn('form_variants.form_id', $forms->toArray());

        $sortField = $data['sortField'];
        $sortDirection = $data['sortDirection'];

        foreach ($data['search'] as $key => $value) {
            if (isset($key) && !empty($value)) {
                $pagination = $proofs->where('lead_proofs.title', 'LIKE', '%' . $value . '%');
            }
        }

        $pagination = $proofs
            ->orderBy($sortField, $sortDirection)
            ->paginate(LeadProofListService::PER_PAGE, ['*'], 'page', $data['page']);

        $proofs = $pagination->items();
        $pagination = $pagination->toArray();

        foreach ($proofs as &$leadProof) {
            $formVariant = $this->formVariantModel->find($leadProof->form_variant_id);
            $leadProof->form = $formVariant->form;
            $leadProof->form_variant = $formVariant;
            $leadProof->form_question = $this->formQuestionModel->find($leadProof->form_question_id);
        }
        unset($pagination['data']);

        return  [
            'data' => $proofs,
            'pagination' => $pagination
        ];
    }

    /**
     * Get counts for different time periods.
     *
     * @return null|array
     */
    public function getLeadProofCounts(): array
    {
        $authUser = $this->authService->getUser();

        $counts = [];
        $timePeriods = [
            TimePeriodsEnum::MONTHLY,
            TimePeriodsEnum::YEARLY,
            TimePeriodsEnum::AS_PER_PLAN,
            TimePeriodsEnum::NONE
        ];

        foreach ($timePeriods as $timePeriod) {
            $counts[$timePeriod] = $authUser->leadProofsCount($timePeriod);
        }

        return  [
            'data' => $counts
        ];
    }
}
