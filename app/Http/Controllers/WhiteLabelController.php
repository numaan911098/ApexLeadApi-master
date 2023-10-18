<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\WhiteLabel;
use Auth;
use Log;

class WhiteLabelController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    public function showOptional($id = null)
    {
        if (!empty($id)) {
            $whitelabel = WhiteLabel::findOrFail($id);

            return $this->apiResponse(200, $whitelabel->toArray());
        }

        $user = Auth::user();

        return $this->apiResponse(200, $user->whitelabel->toArray());
    }

    public function update(Request $request, WhiteLabel $whitelabel)
    {
        $whitelabel->enabled = !empty($request->input('enabled'));
        $whitelabel->save();

        return $this->apiResponse(200, $whitelabel->toArray());
    }
}
