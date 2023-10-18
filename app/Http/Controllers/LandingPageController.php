<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\StoreLandingPageTemplate1Request;
use App\LandingPageTemplate;
use App\LandingPage;
use App\Visitor;
use App\LandingPageVisit;
use App\LandingPageOptin;
use App\FormSetting;
use App\Form;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorType;
use App\Enums\LandingPageTemplateCodesEnum as TemplateCodes;
use Log;
use Auth;
use Validator;
use DB;
use Storage;

class LandingPageController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth')->except(['showPublic', 'showBySlug']);
        $this->middleware('stripe.subscription')->only(['storeTPL1']);
    }

    public function index()
    {
        $landingPages =  LandingPage::where('created_by', Auth::id());

        $landingPages = $landingPages->paginate();
        $pages = $landingPages->items();
        $pagination = $landingPages->toArray();

        unset($pagination['data']);

        foreach ($landingPages as $landingPage) {
            $landingPage->visitor_count = LandingPageVisit::where(
                'landing_page_id',
                $landingPage->id
            )
            ->select('visitor_id')
            ->groupBy('visitor_id')
            ->get()
            ->count();
            $landingPage->optin_count = LandingPageOptin::where(
                'landing_page_id',
                $landingPage->id
            )
            ->select('visitor_id')
            ->groupBy('visitor_id')
            ->get()
            ->count();
        }

        return $this->apiResponse(200, $pages, '', '', [], $pagination);
    }

    public function show(LandingPage $landingpage)
    {
        $this->authorize('view', $landingpage);

        return $this->apiResponse(200, $landingpage->toArray());
    }

    public function showBySlug($slug)
    {
        $style  = Storage::get('leadgenform/css/keenui.css');
        $style .= Storage::get('landingpage/css/quill.css');

        $page = LandingPage::where('slug', $slug)->firstOrFail();

        return view('pages.index', compact('page', 'style'));
    }

    public function showPublic(LandingPage $landingpage)
    {
        $landingpage->load('landingPageTemplate');

        return $this->apiResponse(200, $landingpage->toArray());
    }

    public function storeTPL1(StoreLandingPageTemplate1Request $request)
    {
        try {
            DB::beginTransaction();
            $tpl = LandingPageTemplate::where('code', TemplateCodes::TPL1)->first();
            $requestData = $request->all();
            $tplData = $requestData['template'];
            $config = $this->fillTPL1Config($request);
            $lp = LandingPage::create([
                'title' => $requestData['title'],
                'description' => $requestData['description'],
                'keywords' => $requestData['keywords'],
                'config' => json_encode($config),
                'created_by' => Auth::id(),
                'landing_page_template_id' => $tpl->id
            ]);
            $slug = Str::slug($requestData['title']);
            $hasSlug = $lp->withTrashed()->where('slug', $slug)->count() > 0;
            if ($hasSlug) {
                $slug = $slug . '-' . $lp->id;
            }
            $lp->slug = $slug;
            $lp->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
        }

        return $this->apiResponse(201, $lp->toArray());
    }

    public function updateTPL1(
        StoreLandingPageTemplate1Request $request,
        LandingPage $landingpage
    ) {
        $this->authorize('update', $landingpage);

        $tpl = LandingPageTemplate::where('code', TemplateCodes::TPL1)->first();
        $requestData = $request->all();
        $tplData = $requestData['template'];
        $config = $this->fillTPL1Config($request);

        $landingpage->title = $requestData['title'];
        $landingpage->description = $requestData['description'];
        $landingpage->keywords = $requestData['keywords'];
        $landingpage->config = json_encode($config);
        $landingpage->save();

        return $this->apiResponse(200, $landingpage->toArray());
    }

    private function fillTPL1Config(Request $request)
    {
        $tpl = LandingPageTemplate::where('code', TemplateCodes::TPL1)->first();
        $config = $tpl->config;
        $requestData = $request->all();
        $tplData = $requestData['template'];
        $tplConfig = $tplData['config'];

        $config['title'] = $tplConfig['title'];
        $config['description'] = $tplConfig['description'];

        // colors
        $config['colors']['body_bg']['value'] = $tplConfig['colors']['body_bg']['value'];
        $config['colors']['cta1_bg']['value'] = $tplConfig['colors']['cta1_bg']['value'];
        $config['colors']['cta1_color']['value'] = $tplConfig['colors']['cta1_color']['value'];

        // cta
        if ($tplConfig['visibility']['show_cta1']['value']) {
            $config['cta']['cta_text'] = $tplConfig['cta']['cta_text'];
            if (strlen($tplConfig['cta']['url']) === 0) {
                $config['cta']['leadgen_form_id'] = $tplConfig['cta']['leadgen_form_id'];
                $config['cta']['url'] = '';
            } else {
                $config['cta']['url'] = $tplConfig['cta']['url'];
                $config['cta']['leadgen_form_id'] = '';
            }
            $config['cta']['cta_size'] = $tplConfig['cta']['cta_size'];
            $config['cta']['cta_fullwidth'] = $tplConfig['cta']['cta_fullwidth'];
        } else {
            $config['cta']['url'] = '';
            $config['cta']['leadgen_form_id'] = '';
            $config['cta']['cta_size'] = 'small';
            $config['cta']['cta_fullwidth'] = 0;
        }

        // media
        if ($tplConfig['visibility']['show_media']['value']) {
            $config['media_type']['image_url'] = $tplConfig['media_type']['image_url'] ;
            $config['media_type']['video_url'] = $tplConfig['media_type']['video_url'];
            $config['media_type']['source'] = $tplConfig['media_type']['source'];
            $config['media_type']['type'] = $tplConfig['media_type']['type'];
            $config['media_type']['position'] = $tplConfig['media_type']['position'];
            $config['media_type']['is_youtube_video'] = $tplConfig['media_type']['is_youtube_video'];
        } else {
            $config['media_type']['image_url'] = '';
            $config['media_type']['video_url'] = '';
            $config['media_type']['source'] = '';
            $config['media_type']['type'] = '';
            $config['media_type']['position'] = '';
            $config['media_type']['is_youtube_video'] = false;
        }

        //tracking
        if ($tplConfig['tracking']['enable']) {
            $config['tracking']['enable'] = true;
            foreach ($tplConfig['tracking']['scripts'] as $index => $script) {
                $config['tracking']['scripts'][$index]['tag'] = $script['tag'];
                $config['tracking']['scripts'][$index]['position'] = $script['position'];
                $config['tracking']['scripts'][$index]['url'] = $script['url'];
                $config['tracking']['scripts'][$index]['content'] = $script['content'];
                $config['tracking']['scripts'][$index]['order'] = $script['order'];
                $config['tracking']['scripts'][$index]['async'] = $script['async'];
            }
        } else {
            $config['tracking']['enable'] = false;
        }

        // visibility
        $config['visibility']['show_cta1']['value'] = $tplConfig['visibility']['show_cta1']['value'];
        $config['visibility']['show_description']['value'] = $tplConfig['visibility']['show_description']['value'];
        $config['visibility']['show_headline']['value'] = $tplConfig['visibility']['show_headline']['value'];
        $config['visibility']['show_media']['value'] = $tplConfig['visibility']['show_media']['value'];

        return $config;
    }

    public function updateSlug(Request $request, LandingPage $landingpage)
    {
        $this->authorize('update', $landingpage);
        $validator = Validator::make($request->all(), [
            'slug' => 'required|regex:/^[a-zA-Z0-9\-]{1,}$/'
        ]);
        if ($validator->fails()) {
            return $this->apiResponse(
                400,
                [],
                ErrorType::INVALID_DATA,
                'slug field is not valid',
                $validator->errors()->toArray()
            );
        }
        $slugFound = LandingPage::withTrashed()->where([
            ['slug', '=', $request->input('slug')],
            ['id', '!=', $landingpage->id]
        ])->count() > 0;

        if ($slugFound) {
            return $this->apiResponse(
                400,
                [],
                ErrorType::PAGE_SLUG_FOUND,
                'slug already exists please choose another one.'
            );
        }

        $landingpage->slug = $request->input('slug');
        $landingpage->save();

        return $this->apiResponse(200, $landingpage->toArray());
    }

    public function destroyAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'array|required'
        ]);
        if ($validator->fails()) {
            return $this->apiResponse(400, [], ErrorType::INVALID_DATA, 'ids fields must be array of page id.');
        }
        $ids = $request->input('ids');
        $landingpages = [];
        foreach ($ids as $i => $id) {
            $landingpages[$i] = LandingPage::findOrFail($id);
            $this->authorize('delete', $landingpages[$i]);
        }
        foreach ($landingpages as $landingpage) {
            $landingpage->delete();
        }
        return $this->apiResponse(200);
    }
}
