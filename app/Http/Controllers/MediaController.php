<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\Media\MediaManager;
use App\Enums\ErrorTypesEnum;
use App\Media;
use App\Enums\MediaTypesEnum;
use DB;
use Log;
use Auth;
use Storage;

class MediaController extends Controller
{
    /**
     * Media manager instance.
     *
     * @var MediaManager
     */
    protected $mediaMgr;


    public function __construct(MediaManager $mediaMgr)
    {
        $this->middleware('jwt.auth', ['except' => ['publicMedia', 'media']]);
        $this->mediaMgr = $mediaMgr;
    }

    public function index()
    {
        return $this->managerResponse($this->mediaMgr->index());
    }

    public function show($filename)
    {
        return $this->mediaMgr->show($filename);
    }

    public function media($refId, $filename)
    {
        return $this->mediaMgr->media($refId, $filename);
    }

    public function protectedMedia($refId, $filename)
    {
        return $this->mediaMgr->protectedMedia($refId, $filename);
    }

    public function publicMedia($filename)
    {
        return $this->mediaMgr->publicMedia($filename);
    }

    public function store(Request $request)
    {
        return $this->managerResponse($this->mediaMgr->store($request));
    }

    public function destroy(Media $medium)
    {
        $this->authorize('delete', $medium);

        $response = $this->mediaMgr->destroy($medium);

        return $this->managerResponse($response);
    }
}
