<?php

namespace App\Services;

use App\Enums\BlacklistIp\IpMatchOperatorEnum;
use App\Models\BlacklistIp;

class BlacklistIpService
{
    /**
     * @var BlacklistIp
     */
    private BlacklistIp $blacklistIp;

    /**
     * BlackListIpService constructor.
     * @param BlacklistIp $blacklistIp
     */
    public function __construct(BlacklistIp $blacklistIp)
    {
        $this->blacklistIp = $blacklistIp;
    }

    /**
     * @param string $ip
     * @return BlacklistIp|null
     */
    public function isIpBlocked(string $ip): ?BlacklistIp
    {
        $blacklistIp = $this->blacklistIp
            ->where('ip', $ip)
            ->where('operator', IpMatchOperatorEnum::EQUAL)
            ->first();

        if ($blacklistIp) {
            return $blacklistIp;
        }

        $blacklistIp = $this->blacklistIp
            ->where('operator', IpMatchOperatorEnum::CONTAINS)
            ->where('ip', 'like', "%{$ip}%")
            ->first();

        return $blacklistIp;
    }
}
