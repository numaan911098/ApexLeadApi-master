<?php

namespace App\Modules\LeadProof;

use App\Modules\Base\BaseManager;
use Facades\App\Services\Util;
use App\LeadProof;

class LeadProofManager extends BaseManager
{
   /**
    * Create Lead Proof.
    *
    * @param array $data
    * @return array
    */
    public function store(array $data)
    {
        $data['ref_id'] = Util::uuid4();
        $this->addResponse('data', LeadProof::create($data));
        return $this->response();
    }

    /**
     * Update Lead Proof.
     *
     * @param LeadProof $leadProof
     * @param array $data
     * @return array
     */
    public function update(LeadProof $leadProof, array $data)
    {
        $leadProof->title               = $data['title'];
        $leadProof->description         = $data['description'];
        $leadProof->form_variant_id     = $data['form_variant_id'];
        $leadProof->form_question_id    = $data['form_question_id'];
        $leadProof->count               = $data['count'];
        $leadProof->delay               = $data['delay'];
        $leadProof->show_firstpart_only = $data['show_firstpart_only'];
        $leadProof->show_timestamp      = $data['show_timestamp'];
        $leadProof->show_country        = $data['show_country'];
        $leadProof->latest              = $data['latest'];
        $leadProof->save();
        $this->addResponse('data', $leadProof);
        return $this->response();
    }
}
