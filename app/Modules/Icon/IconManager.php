<?php

namespace App\Modules\Icon;

use App\Modules\Base\BaseManager;
use App\Modules\Icon\Services\IconService;

class IconManager extends BaseManager
{
    /**
     * IconService instance.
     *
     * @var IconService
     */
    private $iconService;

    /**
     * Icon manager constructor.
     *
     * @param IconService $iconService
     */
    public function __construct(IconService $iconService)
    {
        $this->iconService = $iconService;
    }

    /**
     * Get library icons.
     *
     * @param array $library
     * @param array $variation
     *
     * @return array
     */
    public function getIcons(string $library, string $variation = ''): array
    {
        $this->addResponse('data', $this->iconService->getSvgIcons($library, $variation));

        return $this->response();
    }
}
