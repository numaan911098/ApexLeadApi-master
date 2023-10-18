<?php

namespace App\Services;

use App\FormLead;
use App\Models\FormLeadView;
use App\Enums\SpecialDatesEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class FormLeadViewService
{

    protected $formLeadModel;
    protected $formLeadViewModel;

    public function __construct(FormLead $formLead, FormLeadView $formLeadView)
    {
        $this->formLeadModel = $formLead;
        $this->formLeadViewModel = $formLeadView;
    }

    public function markViewed(?array $formIds, ?string $formVariantId)
    {
        if (!$formIds) {
            $leads = $this->formLeadModel::select('id')->where('form_variant_id', $formVariantId)
            ->whereDate('created_at', '>=', SpecialDatesEnum::UNREAD_LEADS_FROM)
            ->get();
        } else {
            $leads = $this->formLeadModel::select('id')->whereIn('form_id', $formIds)
            ->whereDate('created_at', '>=', SpecialDatesEnum::UNREAD_LEADS_FROM)
            ->get();
        }
        $data = [];
        foreach ($leads as $lead) {
            $viewedLeads = $this->formLeadViewModel::where('lead_id', '=', $lead->id)->first();
            if (!$viewedLeads) {
                $data[] = [
                    'user_id' => Auth::id(),
                    'lead_id' => $lead->id,
                    'viewed' => true,
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString()
                ];
            }
        }
        $this->formLeadViewModel->insert($data);
        return $data;
    }
}
