<?php

namespace App\Http\Controllers;

use App\Enums\DisksEnum;
use Illuminate\Http\Request;
use View;
use Storage;

class CssController extends Controller
{
    public function leadgenForm()
    {
        $style  = Storage::disk(DisksEnum::RESOURCES)->get('leadgenform/libs/keenui/keen-ui.min.css');
        $style .= Storage::disk(DisksEnum::RESOURCES)->get('leadgenform/css/quill-core.css');

        return response($style)->header('Content-Type', 'text/css');
    }
}
