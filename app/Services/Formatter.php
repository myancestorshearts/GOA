<?php

namespace App\Services;
use App\Common\Functions;

class Formatter { 

    protected $date_format = 'MYSQL';

    public function setDateFormat($date_format) {
        $this->date_format = $date_format;
    }

    public function getDateFormat() {
        return $this->date_format;
    }

    public function convertToDateFormat($value, $default_time = null) {
        if ($value == null) $value = $default_time;
        if ($value == null) return null;
        $time = gettype($value) == gettype(time()) ? $value : strtotime($value);
        if ($this->date_format == 'UNIX') return $time;
        else if ($this->date_format == 'MYSQL') return Functions::convertTimeToMysql($time);
    }
}