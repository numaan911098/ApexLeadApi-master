<?php

namespace App\Services;

use App\Mail\TwoFactorAuthMail;
use App\Models\UserDevice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Facades\App\Services\Util;
use Jenssegers\Agent\Facades\Agent;
use Illuminate\Support\Facades\Request;
use Sentry;

class TwoFactorAuthService
{
    protected $userDeviceModel;

    /**
     * UserDevice constructor.
     * @param UserDevice $userDevice
     */
    public function __construct(UserDevice $userDevice)
    {
        $this->userDeviceModel = $userDevice;
    }

    /**
     * @param int $id
     * @param string $device
     * @return UserDevice|null
     */
    public function checkIfAuthenticated(int $id, string $device): ?UserDevice
    {
        $check = $this->userDeviceModel
            ->where('user_id', "=", $id)
            ->where('device_id', "=", $device)
            ->where('verified_at', ">=", Carbon::now()->subDays(60)->toDateTimeString())
            ->first();

        return $check;
    }

    /**
     * @param int $id
     * @param string $email
     * @param string $device
     */
    public function generateTwoFactor(int $id, string $email, string $device)
    {
        $code = random_int(100000, 999999);
        $this->userDeviceModel->updateOrCreate(
            [
                'user_id'   => $id,
                'device_id' => $device
            ],
            ['verification_code'   => $code]
        );

        $geolocation = Util::geolocation(Request::ip());
        $twoFactorDetails = [
            'account' => $email,
            'date' => Carbon::now()->toDateTimeString(),
            'location' => $geolocation['geoplugin_countryName'],
            'ip' => Request::ip(),
            'os' => Agent::platform(),
            'browser' => Agent::browser(),
            'device' => Util::deviceType(),
            'code' => $code
        ];

        try {
            Mail::to($email)->send(new TwoFactorAuthMail($twoFactorDetails));
        } catch (\Exception $exception) {
            Sentry\captureException($exception);
        }
    }

    /**
     * @param int $id
     * @param string $device
     * @param array $data
     * @return UserDevice|null
     */
    public function verifyTwoFactor(int $id, string $device, array $data): ?UserDevice
    {
        $check = $this->userDeviceModel
            ->where('user_id', $id)
            ->where('device_id', $device)
            ->where('verification_code', $data['authenticateCode'])
            ->first();

        if (empty($check)) {
            return null;
        }

        if ($data['isTrustedDevice']) {
            $check->verified_at = Carbon::now();
            $check->save();
        }

        return $check;
    }
}
