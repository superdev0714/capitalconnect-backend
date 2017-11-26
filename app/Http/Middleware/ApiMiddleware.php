<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Response;

class ApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param string $apiKey
     * @return mixed
     */
    public function handle($request, Closure $next, $apiKey)
    {
        $apiKeyValue = $request->header('X-API-KEY');
        if ($apiKeyValue != $apiKey) {
            return response('', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
