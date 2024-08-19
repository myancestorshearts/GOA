<?php

namespace App\Models\Mysql;

use ApiAuth;

class OauthState extends Base
{
    public $table = 'oauth_states';

    protected $hidden = [
        'id',
        'user_id',
        'key'
    ];

    /**purpose
     *   generate state by key
     * args
     *   key
     * returns
     *   oauthstate instance
     */
    public static function generateState($key, $name) {

        // create oauth state
        $oauth_state = new OauthState;

        // get user from api
        $oauth_state->user_id = ApiAuth::user()->id;
        
        // set key
        $oauth_state->key = $key;
        $oauth_state->name = trim($name);


        
        $random = bin2hex(openssl_random_pseudo_bytes(32));
        $oauth_state->verifier = OauthState::base64urlEncode(pack('H*', $random));
        $oauth_state->challenge = OauthState::base64urlEncode(pack('H*', hash('sha256', $oauth_state->verifier)));

        // save and return state;
        $oauth_state->save();
        return $oauth_state;
    }
    
    /**purpose
     *   get base 64 url encode value
     * args
     *   string
     * returns
     *   encoded string
     */
    private static function base64urlEncode($plainText)
    {
        $base64 = base64_encode($plainText);
        $base64 = trim($base64, "=");
        $base64url = strtr($base64, '+/', '-_');
        return ($base64url);
    }

    
}
