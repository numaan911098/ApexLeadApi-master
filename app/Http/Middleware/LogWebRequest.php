<?php

namespace App\Http\Middleware;

use Log;

class LogWebRequest
{

    public function handle($request, \Closure $next)
    {
        Log::info($request->url());
        return $next($request);
    }
}
