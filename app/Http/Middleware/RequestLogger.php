<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class RequestLogger
{
    public function handle(Request $request, Closure $next)
    {
        // originally from https://github.com/spatie/laravel-http-logger/blob/master/src/DefaultLogWriter.php
        $method = strtoupper($request->getMethod());

        $uri = $request->getPathInfo();

        $bodyAsJson = json_encode($request->except(['password']));

        $headersAsJson = json_encode($request->headers->all());

        $message = "{$method} {$uri} - Headers: {$headersAsJson} - Body: {$bodyAsJson} -";

        Log::channel('request')->info($message);

        return $next($request);
    }
}
