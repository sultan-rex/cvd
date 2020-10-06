<?php

namespace Cvd\Config;


class Settings {
    public static function load(String $db_type = null) : array {
        $config = parse_ini_file('config.ini',TRUE);
        return $config;
    }
}