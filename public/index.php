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
$barbershop = new Barbershop($db);

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
    
    case 'shops' :
        $controller = new BarberShopController($barbershop, $db);
        $action = $parts[1] ?? '';

        if (isset($parts[1]) && is_numeric($parts[1])) {

            $id = (int)$parts[1];
            if ($method === 'GET') {
                $controller->getShopById($id);
            } else {
                Response::error("Method not allowed", 405);
            }

        } else if (isset($parts[1]) && $parts[1] === 'top-rated') {

            if ($method === 'GET') {
                $controller->getTopBarbers();
            } else {
                Response::error("Method not allowed", 405);
            }

        } else if (isset($parts[1]) && $parts[1] === 'available') {

            if ($method === 'GET') {
                $controller->getAvailableBarbers();
            } else {
                Response::error("Method not allowed", 405);
            }

        } else if (!isset($parts[1])) {

            if ($method === 'GET') {
                $controller->getAllShops();
            } else {
                Response::error("Method not allowed", 405);
            } 
        } else {
            Response::notFound("Barbershop endpoint not found.");
        }
        break;

    case 'barbers' :
        $controller = new BarberShopController($barbershop, $db);

        if (isset($parts[1]) && is_numeric($parts[1]) && isset($parts[2]) && $parts[2] === 'schedule' && isset($parts[3])) {
            $barber_id = (int)$parts[1];
            $date = $parts[3];
            if ($method === 'GET') {
                $controller->getBarberSchedule($barber_id, $date);
            } else {
                Response::error("Method not allowed", 405);
            }
        } else {
            Response::notFound("Barber endpoint not found.");
        }
        break;

    case 'appointments':
        $appointmentModel = new Appointment($db);
        $controller = new AppointmentController($appointmentModel, $db);

        if (isset($parts[1]) && is_numeric($parts[1])) {
            $id = (int)$parts[1];

            switch ($method) {
                case 'GET':
                    $controller->getAppointment($id);
                    break;
                case 'POST':
                    $controller->editAppointment($id);
                    break;
                case 'DELETE':
                    $controller->cancelAppointment($id);
                    break;
                default:
                    Response::error("Method not allowed for this endpoint.", 405);
                    break;
            }
        } 
        else if (!isset($parts[1])) {
            if ($method === 'POST') {
                $controller->createAppointment();
            } else {
                Response::error("Method not allowed. Use POST to create an appointment.", 405);
            }
        }
        else {
            Response::notFound("Appointment endpoint not found.");
        }
        break;
    
    case 'owner':
        $sub_resource = $parts[1] ?? '';
        if ($sub_resource === 'auth') {
            $ownerModel = new ShopOwner($db);
            $controller = new OwnerController($ownerModel, $db);

            $action = $parts[2] ?? '';

            switch ($action) {
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
                
                case 'logout':
                     if ($method === 'POST') {
                        $controller->logout();
                    } else {
                        Response::error("Method not allowed", 405);
                    }
                    break;

                default:
                    Response::notFound("Owner authentication endpoint not found.");
                    break;
            }
        } else if ($sub_resource === 'shops') {
            $shopModel = new Shop($db);
            $controller = new ShopController($shopModel, $db);

            if (!isset($parts[2])) {
                switch ($method) {
                    case 'GET':
                        $controller->getMyShops();
                        break;
                    case 'POST':
                        $controller->createShop();
                        break;
                    default:
                        Response::error("Method not allowed.", 405);
                        break;
                }
            } else if (isset($parts[2]) && is_numeric($parts[2])) {
                $shop_id = (int)$parts[2];
                switch ($method) {
                    case 'GET':
                        $controller->getShop($shop_id);
                        break;
                    case 'POST':
                        $controller->updateShop($shop_id);
                        break;
                    default:
                        Response::error("Method not allowed.", 405);
                        break;
                }
            } else {
                Response::notFound("Shop management endpoint not found.");
            }
        } else {
            Response::notFound("Owner resource not found.");
        }
        break;

    case 'professional':
        $sub_resource = $parts[1] ?? '';
        if ($sub_resource === 'auth') {
            
            $professionalModel = new Professional($db);
            $controller = new ProfessionalController($professionalModel, $db);

            $action = $parts[2] ?? '';

            switch ($action) {
                case 'register':
                    if ($method === 'POST') {
                        $controller->register();
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
                
                case 'logout':
                     if ($method === 'POST') {
                        $controller->logout();
                    } else {
                        Response::error("Method not allowed", 405);
                    }
                    break;

                default:
                    Response::notFound("Owner authentication endpoint not found.");
                    break;
            }
        } else {
            Response::notFound("Owner resource not found.");
        }
        break;
        
    default:
        Response::notFound("Resource not found.");
        break;
}