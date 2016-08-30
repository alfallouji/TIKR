<?php
namespace Tikr\Helper;

class Type {
    public static function getType($value) {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            return 'boolean';
        } elseif (filter_var($value, FILTER_VALIDATE_INT)) { 
            return 'integer';
        } elseif (filter_var($value, FILTER_VALIDATE_FLOAT)) { 
            return 'float';
        } elseif (self::validateIso8601Date($value)) {
            return 'datetime';
        }

        return 'string';
    }

    public static function validateIso8601Date($date)
    {
        $parts = array();
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/', $date, $parts) == true) {
            $time = gmmktime($parts[4], $parts[5], $parts[6], $parts[2], $parts[3], $parts[1]);

            $input_time = strtotime($date);
            if ($input_time === false) return false;

            return $input_time == $time;
        } else {
            return false;
        }
    }
}
