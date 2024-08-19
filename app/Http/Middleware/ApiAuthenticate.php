<?php
namespace App\Http\Middleware;

use Closure;

use App\Http\Controllers\Response;
use App\Common\Validator;
use ApiAuth;

class ApiAuthenticate
{
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
	public function handle($request, Closure $next)
    {   
        // ignore urls login and register these do not need to be authenticated
        $path = $request->path();
        if ($path == 'api/register' || 
            $path == 'api/login' ||
            $path == 'api/verify/email' ||
            $path == 'api/password/request' ||
            $path == 'api/password/set' || 
            $path == 'api/oauth/connect' ||
            $path == 'api/oauth/install' ||
            $path == 'api/integration/install/shopify'||
            $path == 'api/integration/install/wix' || 
            str_starts_with($path, 'api/integration/purchase') 
        ) return $next($request);

        // check for token refresh
        if ($path == 'api/token/refresh') {
            if (!ApiAuth::validateRefreshToken($request->header('authorization'))) {
                $response = new Response;
                return $response->jsonFailure('Not authorized');
            }
        }
        else {
            // check token for profile verification
            if (!ApiAuth::validateToken($request->header('authorization'))) {
                $response = new Response;
                return $response->jsonFailure('Not authorized');
            }
        }

        // check to make user is active 
        if (!Validator::validateBoolean(ApiAuth::user()->active)) {
            $response = new Response;
            return $response->jsonFailure('Not authorized');
        }


        // validation success proceed to next middleware
        return $next($request);
    }
}
