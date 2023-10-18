<?php

namespace App\Modules\Dashboard;

use App\Modules\Base\BaseManager;
use App\Modules\Dashboard\Widgets\GeneralWidget;

class DashboardManager extends BaseManager
{
    /**
     * GeneralWidget instance.
     *
     * @var GeneralWidget
     */
    private $generalWidget;

    /**
     * Dashboard manager constructor.
     *
     * @param GeneralWidget $generalWidget
     */
    public function __construct(GeneralWidget $generalWidget)
    {
        $this->generalWidget = $generalWidget;
    }

    /**
     * General widget handler.
     *
     * @param array $params
     * @return array
     */
    public function generalWidget(array $params)
    {
        $this->addResponse('data', $this->generalWidget->handler($params));
        return $this->response();
    }
}
