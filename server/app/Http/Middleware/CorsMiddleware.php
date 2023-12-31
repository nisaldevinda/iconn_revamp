<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
{
    $headers = [
        'Access-Control-Allow-Origin'       => config('app.allow_domain'),
        'Access-Control-Allow-Methods'      => 'HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS',
        'Access-Control-Allow-Credentials'  => 'true',
        'Access-Control-Max-Age'            => '86400',
        'Access-Control-Allow-Headers'      => 'Origin, Content-Type, X-Auth-Token,  Accept, Authorization, X-Requested-With, Application'
    ];


   
    if ($request->isMethod('OPTIONS')){
        return response()->json(['method' => 'OPTIONS'], 200, $headers);
    }

    $response = $next($request);
    foreach($headers as $key => $value) {
        $response->headers->set($key, $value);
    }

    return $response;
}
}
