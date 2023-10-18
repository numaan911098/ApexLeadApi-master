<?php

namespace App\Services\Lists;

use App\Services\FormTemplateBuilderService;
use App\Services\PackageBuilderService;
use App\Services\LeadgenReportService;
use App\Enums\ListTypeEnum;

class GeneralListService
{
    /**
     * @var FormListService
     */
    protected $formListService;

    /**
     * @var UserListService
     */
    protected $userListService;

    /**
     * @var LeadProofListService
     */
    protected $leadProofListService;

    /**
     * @var ExternalCheckoutListService
     */
    protected $externalCheckoutListService;

    /**
     * @var FormTemplateBuilderService
     */
    protected $formTemplateListService;

    /**
     * @var PackageBuilderService
     */
    protected $packageBuilderService;

    /**
     * @var LeadgenReportService
     */
    protected $leadgenReportService;

    /**
     * @param FormListService $formListService
     * @param UserListService $userListService
     * @param FormTemplateBuilderService $formTemplateListService
     * @param ExternalCheckoutListService $externalCheckoutListService
     * @param LeadProofListService $leadProofListService
     * @param PackageBuilderService $packageBuilderService
     * @param LeadgenReportService $leadgenReportService
     */
    public function __construct(
        FormListService $formListService,
        UserListService $userListService,
        FormTemplateBuilderService $formTemplateListService,
        ExternalCheckoutListService $externalCheckoutListService,
        LeadProofListService $leadProofListService,
        PackageBuilderService $packageBuilderService,
        LeadgenReportService $leadgenReportService
    ) {
        $this->formListService = $formListService;
        $this->userListService = $userListService;
        $this->leadProofListService = $leadProofListService;
        $this->formTemplateListService = $formTemplateListService;
        $this->externalCheckoutListService = $externalCheckoutListService;
        $this->packageBuilderService = $packageBuilderService;
        $this->leadgenReportService = $leadgenReportService;
    }

    /**
     * @param array $listParams [
     *   'resource' => 'forms|users|proofs|external-checkout|templates|package-builder|user_form_report',
     *   'search' => ['name' => 'hello', 'email' => 'hello@gmail.com'],
     *   'page' => 1,
     *    sortDirection' => 'desc|asc',
     *   'sortField' => 'created_at|title|email..'
     * ]
     **/
    public function getLists(array $listParams): ?array
    {
        if ($listParams['resource'] === ListTypeEnum::FORM) {
            return $this->formListService->getList($listParams);
        }

        if ($listParams['resource'] === ListTypeEnum::USER) {
            return $this->userListService->getList($listParams);
        }

        if ($listParams['resource'] === ListTypeEnum::LEAD_PROOF) {
            return $this->leadProofListService->getList($listParams);
        }

        if ($listParams['resource'] === ListTypeEnum::EXTERNAL_CHECKOUT) {
            return $this->externalCheckoutListService->getList($listParams);
        }

        if ($listParams['resource'] === ListTypeEnum::FORM_TEMPLATE) {
            return $this->formTemplateListService->getList($listParams);
        }

        if ($listParams['resource'] === ListTypeEnum::PACKAGE_BUILDER) {
            return $this->packageBuilderService->getList($listParams);
        }

        if ($listParams['resource'] === ListTypeEnum::USER_FORM_REPORT) {
            return $this->leadgenReportService->getList($listParams);
        }

        return null;
    }
}
