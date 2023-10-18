<?php

namespace App\Services;

use App\Enums\FormVariantTypesEnum;
use App\Form;
use App\Models\FormTemplate;
use App\FormVariant;
use Facades\App\Services\Util;
use Illuminate\Support\Facades\DB;
use Sentry;

class FormTemplateBuilderService
{
    /**
     * @var FormTemplate
     */
    protected $formTemplateModel;

    /**
     * @var Form
     */
    protected $formModel;

    /**
     * @var FormVariant
     */
    protected $formVariantModel;

    /**
     * records per page
     */
    protected const PER_PAGE = 15;

    /**
     * FormTemplateBuilderService constructor.
     * @param FormTemplate $formTemplate
     * @param Form $form
     * @param FormVariant $formVariant
     */
    public function __construct(
        FormTemplate $formTemplate,
        Form $form,
        FormVariant $formVariant
    ) {
        $this->formTemplateModel = $formTemplate;
        $this->formModel = $form;
        $this->formVariantModel = $formVariant;
    }

    /**
     * Save particular template
     * @param array $data
     * @return null|array
     */
    public function createFormTemplate(array $data): ?array
    {
        try {
            //begin
            DB::beginTransaction();

            //fetching formID
            $formId = $data['form_id'];

            //fetching form variant ID
            $variantId = $this->formVariantModel->where('form_id', $formId)
                ->whereHas('formVariantType', function ($query) {
                    $query->where('type', FormVariantTypesEnum::CHAMPION);
                })->first();
            if (!empty($variantId)) {
                $form_variant_id = $variantId->id;
            }

            //save form table code and checking if form needs to be duplicated or existing
            if ($data['make_duplicate'] === true) {
                $formValue = $this->formModel->findOrFail($formId);
                $duplicateForm = $formValue->duplicate();
                $duplicateForm->title = $data['title'];
                $duplicateForm->template = true;
                $duplicateForm->save();
            } else {
                $existingForm = $this->formModel->where('id', $formId)->first();
                $existingForm->template = true;
                $existingForm->save();
            }

            //save form template table code
            $res = $this->formTemplateModel->make();
            $res->title = $data['title'];
            $res->description = $data['description'];
            $res->form_id = $data['form_id'];
            if (!empty($variantId)) {
                $res->form_variant_id = $form_variant_id;
            }
            $res->template_id = $data['template_id'];
            $res->from_user_id = $data['from_user_id'];
            $res->media_id = $data['image'];
            $res->ref_id = Util::uuid4();
            $res->save();

            //save form template category table code
            $primary = $data['primary_category'];
            $fetchedid = $primary['id'];
            $catArr =  $data['categories'];
            $temp = $data['template_id'];

            $fetchedTemplateID = $this->formTemplateModel->where('template_id', $temp)->first();

            foreach ($catArr as $catItem) {
                $this->formTemplateModel->formCategories()->attach($catItem['id'], [
                    'form_category_id' => $catItem['id'],
                    'form_template_id' => $fetchedTemplateID->id,
                    'is_primary_category' => $catItem['id'] === $fetchedid ? true : false
                ]);
            }


            //save form template industry table code
            $industries = $data['industries'];
            foreach ($industries as $industry) {
                $this->formTemplateModel->formIndustries()->attach($industry['id'], [
                    'form_industry_id' => $industry['id'],
                    'form_template_id' => $fetchedTemplateID->id,
                ]);
            }

            //commit
            DB::commit();
            return $data;
        } catch (\Exception $e) {
            DB::rollBack();
            Sentry\captureException($e);
            return null;
        }
    }

    /**
     * Get detail of particular template
     * @param int $id
     * @return null|object
     */
    public function getFormTemplate(int $id): ?FormTemplate
    {
        return $this->formTemplateModel
            ->with('formsUsers', 'templateForm', 'formCategories', 'formIndustries', 'image')->find($id);
    }

    /**
     * Update particular template details
     * @param array $data
     * @param int $id
     * @return null|array
     */
    public function updateFormTemplate(array $data, int $id): ?array
    {
        try {
            DB::beginTransaction();

            $mediaId = $this->formTemplateModel->where('id', $id)->pluck('media_id')->first();
            $res = $this->formTemplateModel->find($id);
            $res->title = $data['title'];
            $res->description = $data['description'];
            $res->media_id = $data['image'] === null ? $mediaId : $data['image'];
            $res->save();

            //update form categories
            $primary = $data['primary_category'];
            $fetchedid = $primary['id'];
            $catArr =  $data['categories'];
            $this->formTemplateModel->find($id)->formCategories()->detach();
            foreach ($catArr as $catItem) {
                $this->formTemplateModel->formCategories()->attach($catItem['id'], [
                    'form_category_id' => $catItem['id'],
                    'form_template_id' => $id,
                    'is_primary_category' => $catItem['id'] === $fetchedid
                ]);
            }

            //update form Industries
            $industries = $data['industries'];
            $this->formTemplateModel->find($id)->formIndustries()->detach();
            foreach ($industries as $industry) {
                $this->formTemplateModel->formIndustries()->attach($industry['id'], [
                    'form_industry_id' => $industry['id'],
                    'form_template_id' => $id
                ]);
            }

            //commit
            DB::commit();
            return $data;
        } catch (\Exception $e) {
            DB::rollBack();
            Sentry\captureException($e);
            return null;
        }
    }

    /**
     * Delete particular template
     * @param int $id
     * @return boolean
     */
    public function delteFormTemplate(int $id): bool
    {
        return $this->formTemplateModel->where('id', $id)->delete();
    }

    /**
     * Get all templates.
     * @param array $data
     * @return array
     */
    public function getList(array $data): array
    {
        $templatesQuery = $this->formTemplateModel;

        $sortField = $data['sortField'];
        $sortDirection = $data['sortDirection'];

        foreach ($data['search'] as $key => $value) {
            if (isset($key) && !empty($value)) {
                $templatesQuery = $templatesQuery->where($key, 'LIKE', '%' . $value . '%');
            }
        }

        $pagination = $templatesQuery
            ->orderBy($sortField, $sortDirection)
            ->paginate(FormTemplateBuilderService::PER_PAGE, ['*'], 'page', $data['page']);

        $templates = $pagination->items();
        $pagination = $pagination->toArray();
        unset($pagination['data']);

        return  [
            'data' => $templates,
            'pagination' => $pagination
        ];
    }
}
