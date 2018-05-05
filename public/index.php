<?php
/*
 * Front controller
 */



/*
 * Twig
 */
require '../vendor/autoload.php';
Twig_Autoloader::register();

/**
 * Autoloader
 */
spl_autoload_register(function($class) {
    $root = dirname(__DIR__); // get parent dir (root of the site)
    $file = $root.'/'.str_replace('\\', '/', $class) . '.php';
    if (is_readable($file)) {
        require $root . '/' . str_replace('\\', '/', $class) . '.php';
    }
});

/**
 * Error and Exception handling
 */
set_error_handler('Core\Error::errorHandler');
set_exception_handler('Core\Error::exceptionHandler');

$router = new Core\Router();

// Add routes
$router->add('', ['controller' => 'Home', 'action'=>'index']);
$router->add('index', ['controller' => 'Home', 'action'=>'index']);
$router->add('addfile', ['controller' => 'Addfile', 'action'=>'index']);
$router->add('downloadfile', ['controller' => 'Downloadfile', 'action'=>'index']);
// in case our app grows
$router->add('{controller}/{action}');


$router->dispatch($_SERVER['QUERY_STRING']);