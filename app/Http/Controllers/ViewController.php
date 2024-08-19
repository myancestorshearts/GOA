<?php namespace App\Http\Controllers;

use Auth;
use Redirect;


use App\Common\Validator;

use Illuminate\Http\Request;
use App\Http\Controllers\Response;


use Artisan;

class ViewController extends Controller {

    /**purpose
     *   shows view to authentiate (register, login, verify email, reset password)
     * args
     *   (none)
     * returns
     *   authenticate view
     */
    public function showAuthenticate() {
        //if (Auth::check()) return Redirect::to('/portal');
        return view('authenticate');
    }

    /**purpose 
     *   shows user portal view (customer interacts with site)
     * args
     *   (none)
     * returns
     *   user portal view
     */
    public function showPortal() {
        //if (!Auth::check()) return Redirect::to('/');
        return view('portal');
    }

    /**purpose
     *   shows admin portal view (admins/customer support interacts with site)
     * args
     *   (none)
     * returns
     *   admin portal view
     */
    public function showAdmin() {
        //if (!Auth::check() || !Validator::validateBoolean(Auth::user()->admin)) return Redirect::to('/');
        return view('admin');
    }

    /**purpose
     *   run migrations
     * args
     *   (none)
     * returns 
     *   result
     */
	public function doMigrate(Request $request)
	{
		set_time_limit(60);
		Artisan::call("migrate", ['--force' => 'default']);
		$response = new Response;
		return $response->jsonSuccess();
	}


    /**purpose
     *   show embed calculator
     * args
     *   (none)
     * returns
     *   embed calculator view
     */
    public function showEmbedCalculator() {
        return view('embed-calculator');
    }

}