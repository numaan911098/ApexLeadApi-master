<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\Icon\IconManager;

class IconController extends Controller
{
    /**
     * Icon Manager instance.
     *
     * @var IconManager
     */
    protected $iconMgr;

    public function __construct(IconManager $iconMgr)
    {
        $this->middleware('jwt.auth');

        $this->iconMgr = $iconMgr;
    }

    public function getIcons(string $library, string $variation = '')
    {
        return $this->managerResponse($this->iconMgr->getIcons($library, $variation));
    }
}
