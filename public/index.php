<?php
/**
 * Main API router
 */

require_once __DIR__ . '/../config/Cors.php';
require_once __DIR__ . '/../config/autoloader.php';
Cors::handle();

$database = new Database;
$db = $database->getConnection();

$user = new User($db);

$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}

$path = trim(str_replace('/barber_api', '', $uri), '/');

$parts = explode('/', $path);

$method = $_SERVER['REQUEST_METHOD'];
$resource = $parts[0] ?? '';

switch($resource) {
    case 'auth':
        $controller = new AuthController($user, $db);
        $action = $parts[1] ?? '';

        switch($action) {

            case 'register':
                if ($method === 'POST') {
                    $controller->register();
                } else {
                    Response::error("Method not allowed", 405);
                }
                break;
            
            case 'login':
                if ($method === 'POST') {
                    $controller->login();
                } else {
                    Response::error("Method not allowed", 405);
                }
                break;
            
            case 'me':
                if ($method === 'GET') {
                    $controller->me();
                } else {
                    Response::error("Method not allowed", 405);
                }
                break;
            
            case 'update':
                if ($method === 'POST') {
                    $controller->updateProfile();
                } else {
                    Response::error("Method not allowed", 405);
                }
                break;

            case 'logout':
                if ($method === 'POST') {
                    $controller->logout();
                } else {
                    Response::error("Method not allowed", 405);
                }
                break;
            
            default:
                Response::notFound("Authentication endpoint not found.");
                break;
        }
        break;
    
    default:
        Response::notFound("Resource not found.");
        break;
}