<?php 

namespace App\Libraries;
use App\Http\Controllers\Response;
use App\Models\Dynamo;


class Goa {

	private $key;
	private $password;
	private $endpoint;
    private $access_token;
    private $refresh_token;

    const TOKEN_KEY='GAO';

	/**purpose
	 *   constructs class from environment variables
	 * args
	 *   (none)
	 * returns
	 *   (none)
	 */
	function __construct() {
		$this->key = env('GOA_KEY', '');
		$this->password = env('GOA_PASSWORD', '');
		$this->endpoint = env('GOA_ENDPOINT', '');
	}

	/**purpose
	 *   get a access token for pitney
	 * args
	 *   (none)
	 * returns
	 *   access_token
	 *   clientID
	 */
	private function getAccessToken() {

        // first check stored access token
		// get token
		$tokens = Dynamo\Tokens::findOrCreate(Goa::TOKEN_KEY);

        // check token for validity
        if (isset($tokens->access)) {

            // if token is still valid then return token
			if (time() < $tokens->access_expires - 100) return decrypt($tokens->access);

            // if token is expired then we need to refresh token
			$refresh_response = $this->refreshTokens(decrypt($tokens->refresh));
			if ($refresh_response->result == 'success') {
				$tokens->access = encrypt($refresh_response->data->model->access->token);
				$tokens->access_expires = $refresh_response->data->model->access->expires;
				$tokens->refresh = encrypt($refresh_response->data->model->refresh->token);
				$tokens->updateItem();
				return $refresh_response->data->model->access->token;
			}
        }  

        // fail save to get access token by generating from key and password
		$generate_response = $this->generateTokens();
		// create token from response
		if ($generate_response->result == 'success') {
			$tokens->access = encrypt($generate_response->data->model->access->token);
			$tokens->access_expires = $generate_response->data->model->access->expires;
			$tokens->refresh = encrypt($generate_response->data->model->refresh->token);
			$tokens->updateItem();
			return $generate_response->data->model->access->token;
		}
		else return null;
	}

    /**purpose 
     *   generate an access token from goa api
     * args
     *   (none) 
     * returns
     *   tokens
     */
    private function generateTokens() {

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $this->endpoint . '/restapi/tokens/generate');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	
		$headers = [
			'Authorization: Basic ' . base64_encode($this->key . ':' . $this->password),
			'Content-Type: application/x-www-form-urlencoded'
		];
		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_POST, true);                                     
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(['grant_type' => 'client_credentials']));   
	
		$server_output = curl_exec($curl);
		$server_decoded = json_decode($server_output);

		curl_close($curl);
		return $server_decoded;
    }

    /**purpose
     *   refresh tokens from goa api
     * args
     *   (none)
     * returns
     *   tokens
     */
    private function refreshTokens($refresh_token) {
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $this->endpoint . '/restapi/tokens/refresh');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$headers = [
			'Authorization: Bearer ' . $refresh_token
		];   
		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		curl_setopt($curl, CURLOPT_POST, true);                                      
		
		$server_output = curl_exec($curl);
		$server_decoded = json_decode($server_output);

		curl_close($curl);
		return $server_decoded;
    }

	/**purpose
	 *   create curl for Pitney call includes access token
	 * args
	 *   api
	 * returns 
	 *   curl object ready for api call
	 */
	private function createCurl($api) {

		$access_token = $this->getAccessToken();

		$this->curl = curl_init();

		curl_setopt($this->curl, CURLOPT_URL, $this->endpoint . $api);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

		$headers = [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $access_token
		];

		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
    }

	/**purpose
	 *   call a post for pitney that has auth token
	 * args
	 *   api
	 *   data
	 * returns
	 *   decoded response
	 */
	public function callPost($api, $data) {

		$this->createCurl($api);
		
		$data_string = json_encode(json_decode(json_encode($data)));
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data_string); 
		curl_setopt($this->curl, CURLOPT_POST, 1);

		$server_output = curl_exec($this->curl);
		$server_decoded = json_decode($server_output);

		curl_close($this->curl);
		return $server_decoded;
	}

	
	/**purpose
	 *   call a post for pitney that has auth token
	 * args
	 *   api
	 *   data
	 * returns
	 *   decoded response
	 */
	public function callGet($api, $data) {

        $data_string = http_build_query($data);
        $this->createCurl($api . '?' . $data_string);

		$server_output = curl_exec($this->curl);
		$server_decoded = json_decode($server_output);

		curl_close($this->curl);
		return $server_decoded;
	}
}