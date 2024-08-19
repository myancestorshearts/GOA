<?php

namespace App\Services;

use App\Models\Mysql;

use App\Common;

class ApiAuth { 

    // stored user that we can call from multiple places in the platform
    protected $api_user;
    protected $api_key_id;
    protected $api_key;

    /**purpose
     *   validate user token
     * args
     *   token
     * returns
     *   result
     */
    public function validateToken($token) {
        $this->api_user = Mysql\User::findByAccessToken(str_replace('Bearer ', '', $token));
        return isset($this->api_user);
    }

    /**purpose
     *   validate refresh user token
     * args
     *   token
     * returns
     *   result
     */
    public function validateRefreshToken($token) {
        $this->api_user = Mysql\User::findByRefreshToken(str_replace('Bearer ', '', $token));
        return isset($this->api_user);
    }

    /**purpose
     *   validate admin token
     * args
     *   token 
     * returns
     *   result
     */
    public function validateAdminToken($token) {
        $user = Mysql\User::findByAccessToken(str_replace('Bearer ', '', $token));
        if (isset($user) && Common\Validator::validateBoolean($user->admin)) {
            $this->api_user = $user;
            return true;
        }
        return false;
    }

    /**purpose
     *   get validated user
     * args
     *   (none)
     * returns
     *   user
     */
    public function user() {
        return $this->api_user;
    }

    /**purpose
     *   set api key id
     * args
     *   api_key_id
     * returns
     *   (none)
     */
    public function setApiKeyId($api_key_id) {
        $this->api_key_id = $api_key_id;
    }

    /**purpose
     *   get api key id
     * args
     *   (none)
     * returns
     *   api_key_id
     */
    public function apiKeyId() {
        return $this->api_key_id;
    }

    /**purpose
     *   set api user from api key
     * args
     *   api_key
     * returns
     *   result
     */
    public function setApiUser($api_key) {

        $this->api_key = $api_key;
        $user = Mysql\User::find($api_key->user_id);
        if (!isset($user)) return false;
        $this->api_user = $user;
        return true;
    }

    /**purpose
     *   get an api key
     * args
     *   (none)
     * returns
     *   api_key
     */
    public function apiKey() {
        return $this->api_key;
    }


    /**purpose
     *   set api user from user
     * args
     *   user
     * returns 
     *   (none)
     */
    public function setUser($user) {
        $this->api_user = $user;
    }
}