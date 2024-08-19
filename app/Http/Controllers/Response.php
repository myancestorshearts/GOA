<?php

namespace App\Http\Controllers;

class Response
{
    public static $timers = [];
    public static $previousTimer = null;

	const RESULT_SUCCESS = 'success';
	const RESULT_FAILURE = 'failure';

    public $missing_fields = [];
    public $data = [];
    public $error_code = '';
    public $error_code_unique = '';
    public $message = "";
    public $result = Response::RESULT_FAILURE;
    public $logged_timers = [];

    public function set($key, $value)
    {
    	$this->data[$key] = $value;
    }

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
    
    public function has($key)
    {
        return isset($this->data[$key]);
    }

    public function json($result = null, $message = null, $error_code = null, $error_code_unique = null)
    {
        $this->logged_timers = Response::$timers;
    	$this->setResult($result, $message, $error_code, $error_code_unique);
        if (isset($_SERVER) && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
            return response(gzencode(json_encode($this)))->header('Content-Type', 'application/json')->header('Content-Encoding', 'gzip');
        }
        if (isset($_SERVER) && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate')) {
            return response(gzdeflate(json_encode($this)))->header('Content-Type', 'application/json')->header('Content-Encoding', 'deflate');
        }
        return response()->json($this);
    }
    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function setResult($result = null, $message = null, $error_code = null, $error_code_unique = null)
    {
        if (isset($error_code)) $this->error_code = $error_code;
        if (isset($error_code_unique)) $this->error_code_unique = $error_code_unique;
        if (isset($result))
        {
            $this->result = $result;
            if ($message != null)
            {
                $this->message = $message;
            }
        }
        return $this;
    }

    public function hasRequired($request, $required_args)
    {
        $has_all_required = true;
        foreach ($required_args as $required_arg) {
            if (!$request->has($required_arg))
            {
                $this->missing_fields[] = $required_arg;
                $has_all_required = false;
                $this->error_code = 'MISSING_FIELDS';
                $this->error_code_unique = 'MISSING_FIELDS';
            }
        }
        return $has_all_required;
    }

    public function jsonFailure($message = null, $error_code = null, $error_code_unique = null){
        return $this->json(Response::RESULT_FAILURE, $message, $error_code, $error_code_unique);
    }

    public function jsonSuccess($message = null) {
        return $this->json(Response::RESULT_SUCCESS, $message);
    }

    public function setSuccess($message = null, $error_code = null, $error_code_unique = null) {
        if (isset($error_code)) $this->error_code = $error_code;
        if (isset($error_code_unique)) $this->error_code_unique = $error_code_unique;
        return $this->setResult(Response::RESULT_SUCCESS, $message);
    }

    public function setFailure($message = null, $error_code = null, $error_code_unique = null) {
        if (isset($error_code)) $this->error_code = $error_code;
        if (isset($error_code_unique)) $this->error_code_unique = $error_code_unique;
        return $this->setResult(Response::RESULT_FAILURE, $message);
    }

    public function isFailure() {
        if ($this->result == Response::RESULT_FAILURE) return true;
        return false;
    }
    
    public function isSuccess() {
        if ($this->result == Response::RESULT_SUCCESS) return true;
        return false;
    }

    public function cleanUp($models, $message = null, $code = null, $error_code_unique = null) {
        foreach($models as $model) {
            $model->delete();
        }
        return $this->setFailure($message, $code, $error_code_unique);
    }

    public static function addTimer($message, $data = []) {
        $new_timer = [
            'time' => time(), 
            'message' => $message,
            'data' => $data
        ];
        if (isset(Response::$previousTimer)) $new_timer['elapsed'] = $new_timer['time'] - Response::$previousTimer['time'];
        Response::$timers[] = $new_timer;

        Response::$previousTimer = $new_timer;
    }
}