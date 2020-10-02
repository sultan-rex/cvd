<?php
require __DIR__ . '/vendor/autoload.php';

use Cvd\Config\Settings;
use Cvd\Test;
use Twig\Loader\FileSystemLoader;
use Twig\Environment;
use Cvd\Service\Database;

$db = Database::getConnection();

$loader = new FileSystemLoader('src/Template');
$twig = new Environment($loader,[
    'cache' => 'src/Cache',
    'debug' => true
]);

try {
    $route_details = _getRoute();
    $route_obj = _routeVerify($route_details['controller'],$route_details['method']);
    echo $route_obj->{$route_details['method']}($route_details['args']);    
}
catch(Exception $e) {
    echo $twig->render('404.html.twig');
}

function _getRoute(){


    $controller = $_GET['c'] ?? 'cases';
    $method = $_GET['m'] ?? 'index';

    if(empty($controller) || empty($method)) {
        throw new Exception("Error Processing Request");
    }

    array_shift($_GET);
    array_shift($_GET);
    $controller .= 'Controller';
    
    return [
        'controller' => ucfirst($controller),
        'method' => $method,
        'args' => array_values($_GET)
    ];
    
    return ;
}

function _routeVerify($controller,$method) {
    global $db;
    $controller = 'Cvd\Controller\\'.$controller;
   
    if(class_exists(''.$controller.'',true)) {
        $route_obj = new $controller($db);
        if(method_exists($route_obj,$method)) {
            return $route_obj;
        }
        else {
            throw new Exception('Error Processing Request');
            return ;
        }
    }
    else {
        throw new Exception('Error Processing Request');
    }
    return ;
    
}
