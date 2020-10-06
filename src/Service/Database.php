<?php

namespace Cvd\Service;
use Cvd\Config\Settings;

final class Database {
    private function __construct() {}

    private function __clone() {}

    private function __wakeup() {}

    public static function getConnection() {
        $config = Settings::load();

        $db = new \PDO('mysql:host='.$config['db']['host'].';dbname='.$config['db']['database'],$config['db']['username'],$config['db']['password']);
        return $db;
    }

}



