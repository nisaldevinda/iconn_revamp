<?php

namespace App\Http\Middleware;

use App\Library\Redis;
use App\Library\Session;
use Closure;
use App\Traits\JWTUtil;
use App\Traits\ApiResponser;

class JWTMiddleware
{

    protected $redis;

    protected $session;

    public function __construct(Redis $redis, Session $session)
    {
        $this->redis = $redis;
        $this->session = $session;
    }

    use ApiResponser, JWTUtil;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $authToken = $request->cookie('hrm-session') ?? $this->getAuthToken($request->header('Authorization'));
        $response = $this->decodeAuthHeader($authToken);

        if ($response["error"]) {
            return $this->jsonResponse($response);
        }

        $tokenPayload = $response["data"];
        // get user session data from redis
        $sessionData = $this->redis->getUserSession($tokenPayload->jti);

        if (is_null($sessionData)) {
            return $this->jsonResponse(['statusCode' => 401, 'message' => "Invalid authorization header.", 'data' => null]);
        }

        // set session data
        $this->session->setUser($sessionData->user);
        $this->session->setEmployee($sessionData->employee);
        $this->session->setCompany($sessionData->company);

        return $next($request);
    }
}
