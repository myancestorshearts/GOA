<?php
namespace App\Http\Middleware;

use Closure;

use App\Http\Controllers\Response;
use App\Common\Validator;
use ApiAuth;

class AdminAuthenticate
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
        // check token for profile verification
        if (!ApiAuth::validateAdminToken($request->header('authorization'))) {
            $response = new Response;
            return $response->jsonFailure('Not authorized');
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
