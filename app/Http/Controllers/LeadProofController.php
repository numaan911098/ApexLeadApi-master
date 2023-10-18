<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Facades\App\Services\Util;
use App\Modules\LeadProof\LeadProofManager;
use App\Http\Requests\StoreLeadProofRequest;
use App\LeadProof;
use App\Form;
use App\FormVariant;
use App\FormQuestion;
use App\Enums\ErrorTypesEnum;
use App\Modules\Security\Services\AuthService;
use App\Services\Lists\GeneralListService;
use App\Services\Lists\LeadProofListService;
use Illuminate\Support\Facades\DB;
use Auth;

class LeadProofController extends Controller
{
    private GeneralListService $generalListService;

    /**
     * LeadProofManager instance.
     *
     * @var LeadProofManager
     */
    protected $proofMgr;

    /**
     * @var AuthService
     */
    private $authService;

    /**
     * @var LeadProofListService
     */
    private $leadProofService;

    /**
     * Proofs domain URL.
     *
     * @var string
     */
    protected $proofsUrl;

    /**
     * Constructor.
     * @param LeadProofManager $proofMgr
     * @param GeneralListService $generalListService
     * @param AuthService $authService
     * @param PackageBuilderService $packageBuilderService,
     * @param LeadProofListService $leadProofService
     */
    public function __construct(
        LeadProofManager $proofMgr,
        GeneralListService $generalListService,
        AuthService $authService,
        LeadProofListService $leadProofService
    ) {
        $this->middleware('jwt.auth', ['except' => ['proof', 'leads']]);

        $this->proofMgr = $proofMgr;

        $this->proofsUrl = Util::config('leadgen.proofs_domain');

        $this->generalListService = $generalListService;

        $this->authService = $authService;

        $this->leadProofService = $leadProofService;
    }

    /**
     * Get list of Lead proofs.
     *
     * @return Response
     */
    public function getLeadProofLists(Request $request)
    {
        $params = $request->query('listParams');
        $data = json_decode($params, true);
        $result = $this->generalListService->getLists($data);
        return $this->apiResponse(200, $result['data'], '', '', [], $result['pagination']);
    }

    /**
     * Get count of Lead proofs.
     *
     * @return Response
     */
    public function getLeadProofCounts()
    {
        $result = $this->leadProofService->getLeadProofCounts();
        return $this->apiResponse(200, $result['data']);
    }

    public function index()
    {
        $forms = Form::where('created_by', Auth::id())->pluck('id');

        $leadProofs = DB::table('lead_proofs')
            ->join('form_variants', 'lead_proofs.form_variant_id', '=', 'form_variants.id')
            ->select('lead_proofs.*')
            ->whereIn('form_variants.form_id', $forms->toArray());

        $paginated = $leadProofs->latest()->paginate();

        $leadProofs = $paginated->items();

        foreach ($leadProofs as &$leadProof) {
            $formVariant = FormVariant::find($leadProof->form_variant_id);

            $leadProof->form = $formVariant->form;
            $leadProof->form_variant = $formVariant;
            $leadProof->form_question = FormQuestion::find($leadProof->form_question_id);
        }

        $pagination = $paginated->toArray();
        unset($pagination['data']);

        return $this->apiResponse(200, $leadProofs, '', '', [], $pagination);
    }

    /**
     * Get Lead Proof.
     *
     * @param LeadProof $leadProof
     * @return Response
     */
    public function show(LeadProof $leadProof)
    {
        $this->authorize('view', $leadProof);

        $response = $leadProof->toArray();
        $response['form'] = $leadProof->formVariant->form;
        $response['form_variant'] = $leadProof->formVariant->buildState();
        $response['form_question'] = FormQuestion::find($leadProof->form_question_id)->buildState();

        return $this->apiResponse(200, $response);
    }

    /**
     * Store Lead Proof.
     *
     * @param StoreLeadProofRequest $request
     * @return Response
     */
    public function store(StoreLeadProofRequest $request)
    {
        $authUser = $this->authService->getUser();
        if ($authUser->canCreateLeadProof()) {
            return $this->managerResponse($this->proofMgr->store($request->all()));
        } else {
            return $this->apiResponse(
                400,
                [],
                ErrorTypesEnum::INAPPROPRIATE_PLAN,
                'Please upgrade your plan to create lead proof.'
            );
        }
    }

    /**
     * Store Lead Proof.
     *
     * @param StoreLeadProofRequest $request
     * @return Response
     */
    public function update(StoreLeadProofRequest $request, LeadProof $leadProof)
    {
        $this->authorize('update', $leadProof);

        return $this->managerResponse($this->proofMgr->update($leadProof, $request->all()));
    }

    public function proof($refId)
    {
        $proof = LeadProof::where('ref_id', $refId)->firstOrFail();

        $script = file_get_contents(public_path() . '/js/proof.js');

        $script = str_replace('API_URL', route('lead-proofs.leads', ['id' => $refId]), $script);
        $script = str_replace('DELAY', $proof->delay, $script);

        return response($script)->header('Content-Type', 'application/javascript;charset=UTF-8');
    }

    public function leads($refId)
    {
        $proof = LeadProof::where('ref_id', $refId)->firstOrFail();

        $leads = $proof->formVariant->formLeads();

        if ($proof->latest) {
            $leads = $leads->latest();
        } else {
            $leads = $leads->oldest();
        }

        $leads = $leads->take($proof->count)->get();

        $output = [];
        foreach ($leads as $lead) {
            $questionResponse = $lead
                ->questionResponses
                ->where('form_question_id', $proof->form_question_id)
                ->first();

            if (empty($questionResponse)) {
                continue;
            }

            $title = $questionResponse;

            if ($proof->show_firstpart_only) {
                $title = explode(' ', $questionResponse->response);
                $title = array_shift($title);
            }

            if ($proof->show_country && !empty($lead->formVisit->country)) {
                $title = $title . ' from ' . $lead->formVisit->country;
            }

            $leadResponse = [
                'title'       => $title,
                'description' => $proof->description,
            ];

            if ($proof->show_timestamp) {
                $leadResponse['created_at'] = $lead->created_at->diffForHumans();
            }

            $output[] = $leadResponse;
        }

        return $this->apiResponse(200, $output);
    }

    public function destroy(LeadProof $leadProof)
    {
        $this->authorize('delete', $leadProof);

        $leadProof->delete();

        return $this->apiResponse(200);
    }
}
