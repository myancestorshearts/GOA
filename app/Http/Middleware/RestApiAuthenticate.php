<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;


use App\Models\Mysql;
use App\Http\Controllers\Response;

use App\Common\Validator;

use ApiAuth;
use Formatter;


class RestApiAuthenticate
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
        $response = new Response;
        // get path from request
        $path = $request->path();
        
        // allows these api calls to be called from any origin
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type,Accept,Authorization');

        // if path is tokens genrate check header credentials
        if ($path == 'restapi/tokens/generate') {

            // get basic header and decode 
            $authorization_encoded = str_replace('Basic ', '', $request->header('Authorization'));
            $authorization_decoded = base64_decode($authorization_encoded);

            // split on and check validity
            $api_parts = explode(':', $authorization_decoded);
            if (count($api_parts) != 2) return $response->jsonFailure('Invalid Authorization token', 'INVALID_CREDENTIALS', 'INVALID_CREDENTIALS');

            // check actual api
            $api_key = Mysql\ApiKey::where('key', '=', $api_parts[0])->limit(1)->get()->first();

            // check to make sure api password is correct
            if (!isset($api_key) || decrypt($api_key->password) != $api_parts[1]) return $response->jsonFailure('Invalid credentials', 'INVALID_CREDENTIALS', 'INVALID_CREDENTIALS');

            // validate api key
            if (!Validator::validateBoolean($api_key->active)) return $response->jsonFailure('Invalid credentials', 'INVALID_CREDENTIALS', 'INVALID_CREDENTIALS');
    
            // set api result
            $result = ApiAuth::setApiUser($api_key);
            if (!$result) return $response->jsonFailure('Error linking key to user');
        } 
        else if ($path == 'restapi/tokens/refresh') {
            // check for token refresh
            if (!ApiAuth::validateRefreshToken($request->header('Authorization'))) return $response->jsonFailure('Not authorized', 'INVALID_CREDENTIALS', 'INVALID_REFRESH_TOKEN');
        }
        else {
            // check token for profile verification
            if (!ApiAuth::validateToken($request->header('Authorization'))) return $response->jsonFailure('Not authorized', 'INVALID_CREDENTIALS', 'INVALID_ACCESS_TOKEN');
        }

        // add middle ware for date 
        if ($request->has('date_format')) {
            if (!in_array($request->get('date_format'), ['MYSQL', 'UNIX']));
            Formatter::setDateFormat($request->get('date_format'));
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
