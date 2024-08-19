<?php

namespace App\Models\Mysql;

use Auth;
use App\Http\Controllers\Response;
use App\Common\Validator;
use App\Models\Dynamo;

class ApiCallback extends Base
{
    public $table = 'api_callbacks';

    // blocks sub users from accessing this api call
    public CONST SUB_USER_BLOCK = true;


    const TYPE_CANCELLED_LABEL_USED = 'CANCELLED_LABEL_USED';
    const TYPE_PRICE_CORRECTION = 'PRICE_CORRECTION';
    const TYPE_RETURN_LABEL_USED = 'RETURN_LABEL_USED';
    const TYPE_TRACKING = 'TRACKING';


    private static function isValidType($type) {
        return in_array($type, [
            ApiCallback::TYPE_TRACKING,
            ApiCallback::TYPE_PRICE_CORRECTION,
            ApiCallback::TYPE_RETURN_LABEL_USED,
            ApiCallback::TYPE_CANCELLED_LABEL_USED
        ]);
    }

	/** 
	 * purpose
	 *   add a callback
	 * args
	 *   type (required) (PRICE_ADJUSTMENT)
	 *   callback_url (required)
	 * returns
	 *   model
	 */
    public static function create($model_data, $user) {
        
        // create response
        $response = new Response;

        // create address model
        $model = new ApiCallback;
        $model->user_id = $user->id;
        

        if (!isset($model_data->type)) return $response->setFailure('Invalid type', 'INVALID_PROPERTY');
        if (!ApiCallback::isValidType($model_data->type)) return $response->setFailure('Invalid type', 'INVALID_PROPERTY');
        $model->type = $model_data->type;
        
        if (!isset($model_data->callback_url)) return $response->setFailure('Invalid url', 'INVALID_PROPERTY');
        $validated_url = Validator::validateUrl($model_data->callback_url);

        if (!isset($validated_url)) return $response->setFailure('Invalid url', 'INVALID_PROPERTY');
        $model->callback_url = $validated_url;
        $model->active = 1;
        $model->save();

        $response->set('model', $model);

        return $response->setSuccess();

    }

    /**
     * purpose
     *   test an api callback
     * args
     *   (none)
     * returns
     *   response
     */
    public function test() {


        // create response
        $response = new Response;

        if ($this->type == ApiCallback::TYPE_TRACKING) {
            
            $label = Label::where('user_id', '=', $this->user_id)->limit(1)->get()->first();

            if (!isset($label)) return $response->setFailure('Must have at least one label purchased in order to test this api callback');

            $callback_instance = new ApiCallbackInstance;
            $callback_instance->user_id = $this->user_id;
            $callback_instance->api_callback_id = $this->id;
            $callback_instance->type = $this->type;
            $callback_instance->model_id = $label->id;
            $callback_instance->callback_url = $this->callback_url;
            $callback_instance->status = 'DELIVERED';
            $callback_instance->active = 1;
            $callback_instance->save();

            // init curl
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $this->callback_url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $api_callback_headers = Dynamo\CallbackHeaders::findOrCreate($this->id);

            // set access token header
            $headers = isset($api_callback_headers['headers']) ? $api_callback_headers['headers'] : [];

            $curl_headers = [];
            foreach($headers as $key => $value) {
                $curl_headers[] = $key . ': ' . $value;
            }

            curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_headers);

            curl_setopt($curl, CURLOPT_POST, 1);

            $body = [
                'model' => [
                    'id' => $callback_instance->id,
                    'label_id' => $callback_instance->model_id,
                    'status' => $callback_instance->status,
                    'created_at' => $callback_instance->created_at
                ],
                'type' => $callback_instance->type
            ];

            $data_string = json_encode(json_decode(json_encode($body)));

            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string); 
    
            $server_output = curl_exec($curl);
            
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		    curl_close($curl);

            $response->set('code', $http_code);
            $response->set('response', $server_output);
            $response->set('headers', $headers);
            $response->set('body', $body);

            return $response->setSuccess();
        };

        return $response->setFailure('Can only test tracking callbacks right now');
    }


    

    // api key parameters
    public static $search_parameters = [
        [
            'argument' => 'active',
            'column' => 'active', 
            'type' => 'EQUAL',
            'default' => 1
        ]
    ];
}
