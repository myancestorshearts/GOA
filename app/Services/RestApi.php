<?php

namespace App\Services;

use App\Models;

class RestApi { 

    protected $api_user;
    protected $api_key;

    public function setApiUser($api_key) {

        $this->api_key = $api_key;
        $user = Models\User::find($api_key->user_id);
        if (!isset($user)) return false;
        $this->api_user = $user;
        return true;
    }

    public function user() {
        return $this->api_user;
    }

    public function key() {
        return $this->api_key;
    }

}