<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LeadNotificationSetting;

class LeadNotificationSettingController extends Controller
{
    public function __construct(LeadNotificationSetting $leadNotificationSetting)
    {
        $this->middleware('jwt.auth');
        $this->leadNotificationSettingModel = $leadNotificationSetting;
    }

    public function showOptional($id = null)
    {
        if (!empty($id)) {
            $leadnotification = $this->leadNotificationSettingModel::findOrFail($id);
            return $this->apiResponse(200, $leadnotification->toArray());
        }

        $user = Auth::user();
        return $this->apiResponse(200, $user->leadNotificationSetting->toArray());
    }

    public function update(Request $request, LeadNotificationSetting $leadnotification)
    {
        $leadnotification->enabled = !empty($request->input('enabled'));
        $leadnotification->notification_frequency = $request->input('notification_frequency');
        $leadnotification->save();
        return $this->apiResponse(200, $leadnotification->toArray());
    }
}
