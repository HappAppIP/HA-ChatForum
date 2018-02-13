<?php
/**
 * Entry point of the API.
 *
 * 1.) Bootstrap inlcudepath, openbasedir and autoloader
 * 2.) Translate request url too controller action
 * 3.) Load the controller action.
 * 4.) Execute the action.
 *
 */

require __DIR__ . '/../vendor/autoload.php';

require_once('../Includes/Config/Constants.php');
Lib\Bootstrap::bootstrap();

$dispatcher = Lib\Dispatcher::getInstance();
try {

    $dispatcher->setRequest($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], DEBUG)
        ->loadController()
        ->checkAuth()
        ->execute()
        ->sendHeaders()
        ->sendBody();

}catch(Exception $e){

    switch($e->getCode()){
        case(422):
            header('HTTP/1.0 422 Unprocessable Entity', true, 422);
            header('Content-Type: application/json');
             echo $e->getMessage();
             break;
        case(404):
            header('HTTP/1.0 404 Not Found', true, 404);
            echo '<h1>Page not found</h1><p>' . $e->getMessage(). '</p>';
            break;
        case(403):
            header('HTTP/1.0 403 Permission denied', true, 403);
            echo '<h1>Permission denied</h1><p>' . $e->getMessage() . '</p>';
            break;
        default:
            header('HTTP/1.0 500 Internal server error', true, 500);
            echo '<h1>Internal server error</h1><p>' . $e->getMessage(). '</p>';
            break;
    }
}





