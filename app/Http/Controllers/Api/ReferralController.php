<?php namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Auth;
use Redirect;
use App\Http\Controllers\Response;
use Illuminate\Http\Request;
use Hash;

use App\Libraries;

use App\Models\Dynamo;
use App\Models\Mysql;


use App\Common\Validator;
use App\Common\Functions;

use App\Integrations;

use ApiAuth;

use DB;

class ReferralController extends Controller {


	/**purpose
	 *   export all labels printed
	 * args
	 *   start (required)
     *   end (required)
     *   user_id (required)
	 * returns 
	 *   export.csv
	 */
	public function getLabelsExport(Request $request) {

		// create response 
		$response = new Response;

		// check required
		if (!$response->hasRequired($request, ['start', 'end', 'user_id'])) return $response->jsonFailure('Missing required fields', 'MISSING_FIELDS');

        // check user id
        $referred_user = Mysql\User::find($request->get('user_id'));
        if (!isset($referred_user)) return $response->jsonFailure('Invalid user id');

        // check to make sure we referred the user
        if (Mysql\Referral::where([
            ['user_id', '=', ApiAuth::user()->id],
            ['referred_user_id', '=', $referred_user->id]
        ])->count() == 0) return $response->jsonFailure('Not authorized');
		
		// get all wallet transactions
		$models = Mysql\Label::join('shipments', 'shipments.id', '=', 'labels.shipment_id', 'left')
		->join('packages', 'packages.id', '=', 'shipments.package_id', 'left')
        ->where([
			['labels.user_id', '=', $referred_user->id],
			['labels.created_at', '>', $request->get('start')],
			['labels.created_at', '<', $request->get('end')]
		])
		->orderBy('labels.created_at')
		->select([
            'labels.tracking as tracking',
            'labels.service as service',
            'labels.url as label_url',
            'labels.weight as weight',
            'packages.length as package_length',
            'packages.width as package_width',
            'packages.height as package_height',
            'packages.type as package_type',
            'labels.created_at as date',
		])
		->get();

		$map = [
			'Tracking' => 'tracking',
			'Service' => 'service',
            'Package Type' => 'package_type',
			'Label Url' => 'label_url',
            'Weight (oz)' => 'weight',
            'Length (in)' => 'package_length',
            'Width (in)' => 'package_width',
            'Height (in)' => 'package_height',
			'Date' => 'date'
		];

 
		$datas = [];

		$headers = [];
		foreach ($map as $key => $value) {
			$headers[] = $key;
		}
		$datas[] = $headers;

		foreach($models as $model) {
			$row = [];
			foreach ($map as $key => $value) {
				$row[] = (string) $model->{$value};
			}
			$datas[] = $row;
		}


		// create temp filename
		$filename = tempnam('/tmp', 'export');

		// open csv
        $handle = fopen($filename, 'w');

		// write to csv
        foreach ($datas as $data) {            
            fputcsv($handle, $data);
        }

		// close file
    	fclose($handle);

		// return file
        return response()->download($filename, 'Labels.csv');
	}

    

	/**purpose
	 *   export latest urls labels of each service
	 * args
     *   user_id (required)
	 * returns 
	 *   export.csv
	 */
	public function getLatestLabelsExport(Request $request) {

		// create response 
		$response = new Response;

		// check required
		if (!$response->hasRequired($request, ['user_id'])) return $response->jsonFailure('Missing required fields', 'MISSING_FIELDS');

        // check user id
        $referred_user = Mysql\User::find($request->get('user_id'));
        if (!isset($referred_user)) return $response->jsonFailure('Invalid user id');

        // check to make sure we referred the user
        if (Mysql\Referral::where([
            ['user_id', '=', ApiAuth::user()->id],
            ['referred_user_id', '=', $referred_user->id]
        ])->count() == 0) return $response->jsonFailure('Not authorized');
		
		// get all wallet transactions
		$models = Mysql\Label::join('shipments', 'shipments.id', '=', 'labels.shipment_id', 'left')
		->join('packages', 'packages.id', '=', 'shipments.package_id', 'left')
        ->where([
			['labels.user_id', '=', $referred_user->id]
		])
		->orderByRaw('labels.created_at DESC')
		->select([
            'labels.tracking as tracking',
            'labels.service as service',
            'labels.url as label_url',
            'labels.created_at as date',
            'packages.type as package_type'
		])
        ->groupBy(['labels.service', 'packages.type'])
        ->get();

		$map = [
			'Tracking' => 'tracking',
			'Service' => 'service',
            'Package Type' => 'package_type',
			'Label Url' => 'label_url',
			'Date' => 'date'
		];

		$datas = [];

		$headers = [];
		foreach ($map as $key => $value) {
			$headers[] = $key;
		}
		$datas[] = $headers;

		foreach($models as $model) {
			$row = [];
			foreach ($map as $key => $value) {
				$row[] = (string) $model->{$value};
			}
			$datas[] = $row;
		}


		// create temp filename
		$filename = tempnam('/tmp', 'export');

		// open csv
        $handle = fopen($filename, 'w');

		// write to csv
        foreach ($datas as $data) {            
            fputcsv($handle, $data);
        }

		// close file
    	fclose($handle);

		// return file
        return response()->download($filename, 'LabelsLatest.csv');
	}


	/**purpose
	 *   export total charges for each service
	 * args
	 *   start (required)
     *   end (required)
     *   user_id (required)
	 * returns 
	 *   export.csv
	 */
	public function getTotalsExport(Request $request) {

		
		// create response 
		$response = new Response;

		// check required
		if (!$response->hasRequired($request, ['user_id'])) return $response->jsonFailure('Missing required fields', 'MISSING_FIELDS');

        // check user id
        $referred_user = Mysql\User::find($request->get('user_id'));
        if (!isset($referred_user)) return $response->jsonFailure('Invalid user id');

        // check to make sure we referred the user
        if (Mysql\Referral::where([
            ['user_id', '=', ApiAuth::user()->id],
            ['referred_user_id', '=', $referred_user->id]
        ])->count() == 0) return $response->jsonFailure('Not authorized');
		
		// get all wallet transactions
		$models = Mysql\Label::join('wallet_transactions', 'wallet_transactions.label_id', '=', 'labels.id')
        ->where([
			['labels.user_id', '=', $referred_user->id],
			['labels.created_at', '>', $request->get('start')],
			['labels.created_at', '<', $request->get('end')]
		])
		->select([
            'labels.service as service'
		])
        ->selectRaw(
            '(SUM(wallet_transactions.amount) * -1) as total'
        )
        ->groupBy('labels.service')
		->get();

		$map = [
			'Service' => 'service',
			'Total Spend' => 'total'
		];

		$datas = [];

		$headers = [];
		foreach ($map as $key => $value) {
			$headers[] = $key;
		}
		$datas[] = $headers;

		foreach($models as $model) {
			$row = [];
			foreach ($map as $key => $value) {
				$row[] = (string) $model->{$value};
			}
			$datas[] = $row;
		}


		// create temp filename
		$filename = tempnam('/tmp', 'export');

		// open csv
        $handle = fopen($filename, 'w');

		// write to csv
        foreach ($datas as $data) {            
            fputcsv($handle, $data);
        }

		// close file
    	fclose($handle);

		// return file
        return response()->download($filename, 'Revenue.csv');
	}
}