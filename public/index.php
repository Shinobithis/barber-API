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
$shopModel = new Shop($db);
$professionalModel = new Professional($db);
$serviceModel = new Service($db);

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
        $controller = new PublicController($shopModel, $professionalModel, $serviceModel);
        $action = $parts[1] ?? '';

        if (isset($parts[1]) && is_numeric($parts[1])) {

            $id = (int)$parts[1];
            if ($method === 'GET') {
                $controller->getShopById($id);
            } else {
                Response::error("Method not allowed", 405);
            }

        } else if (!isset($parts[1])) {
            if ($method === 'GET') {
                $controller->getShops();
            } else {
                Response::error("Method not allowed", 405);
            }
        } else if (isset($parts[1]) && is_numeric($parts[1]) && isset($parts[2]) && $parts[2] === 'reviews') {
            $shop_id = (int)$parts[1];
            $reviewModel = new Review($db);
            $appointmentModel = new Appointment($db);
            $controller = new ReviewController($reviewModel, $appointmentModel);

            if ($method === 'GET') {
                $controller->getReviewsForShop($shop_id);
            } else {
                Response::error("Method not allowed", 405);
            }
        } else {
            Response::notFound("Barbershop endpoint not found.");
        }
        break;

    case 'professionals' :
        $controller = new PublicController($shopModel, $professionalModel, $serviceModel);

        if (isset($parts[1]) && $parts[1] === 'top-rated') {
            if ($method === 'GET') {
                $controller->getTopProfessionals();
            } else {
                Response::error("Method not allowed", 405);
            }
        } else if (isset($parts[1]) && is_numeric($parts[1]) && isset($parts[2]) && $parts[2] === 'reviews') {
            $professional_id = (int)$parts[1];
            $reviewModel = new Review($db);
            $appointmentModel = new Appointment($db);
            $controller = new ReviewController($reviewModel, $appointmentModel);

            if ($method === 'GET') {
                $controller->getReviewsForProfessional($professional_id);
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
        } else if ($sub_resource === 'shops' && isset($parts[2]) && is_numeric($parts[2]) && isset($parts[3]) && $parts[3] === 'hire') {
            $shop_id = (int)$parts[2];

            $shopModel = new Shop($db);
            $professionalModel = new Professional($db);
            $shopProfessionalModel = new ShopProfessionals($db);
            $controller = new EmploymentController($shopProfessionalModel, $professionalModel, $shopModel);

            if ($method === 'POST') {
                $controller->sendHireRequest();
            } else {
                Response::error("Method not allowed.", 405);
            }
        }else {
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
        } else if ($sub_resource === 'profile') {
            $serviceModel = new Service($db);
            $offerModel = new Offer($db);
            $professionalServicesModel = new ProfessionalServices($db);
            $controller = new ProfessionalProfileController($professionalServicesModel, $serviceModel, $offerModel);

            $action = $parts[2] ?? '';

            if ($action === 'services') {
                if ($method === 'GET' && !isset($parts[3])) {
                    $controller->getMyServices();
                } else if ($method === 'POST' && !isset($parts[3])) {
                    $controller->addMyService();
                } else if ($method === 'DELETE' && isset($parts[3]) && is_numeric($parts[3])) {
                    $service_id = (int)$parts[3];
                    $controller->removeMyService($service_id);
                } else {
                    Response::notFound("Professional services endpoint not found.");
                }
            } else if ($action === 'offers') {
                if ($method === 'GET' && !isset($parts[3])) {
                    $controller->getMyOffers();
                } else if ($method === 'POST' && !isset($parts[3])) {
                    $controller->createOffer();
                } else if ($method === 'POST' && isset($parts[3]) && is_numeric($parts[3])) {
                    $id = (int)$parts[3];
                    $controller->updateOffer($id);
                } else if ($method === 'DELETE' && isset($parts[3]) && is_numeric($parts[3])) {
                    $id = (int)$parts[3];
                    $controller->deleteOffer($id);
                } else {
                    Response::notFound("Professional offers endpoint not found.");
                }
            } else {
                Response::notFound("Professional profile endpoint not found.");
            }
        }else if ($sub_resource === 'requests') {
            $shopProfessionalsModel = new ShopProfessionals($db);
            $professionalModel = new Professional($db);
            $shopModel = new Shop($db);
            $controller = new EmploymentController($shopProfessionalsModel, $professionalModel, $shopModel);

            if (!isset($parts[2])) {
                if ($method === 'GET') {
                    $controller->getMyPendingRequests();
                } else {
                    Response::error("Method not allowed.", 405);
                }
            } else if (isset($parts[2]) && is_numeric($parts[2]) && isset($parts[3]) && $parts[3] === 'respond') {
                $request_id = (int)$parts[2];
                if ($method === 'POST') {
                    $controller->respondToRequest($request_id);
                } else {
                    Response::error("Method not allowed.", 405);
                }
            }
        } else if ($sub_resource === 'me' && isset($parts[2]) && $parts[2] === 'schedule') {
            $scheduleModel = new Schedule($db);
            $controller = new ScheduleController($scheduleModel);

            if ($method === 'GET') {
                $controller->getMySchedule();
            } else if ($method === 'POST') {
                $controller->updateMySchedule();
            } else {
                Response::error("Method not allowed for this endpoint.", 405);
            }
        } else if ($sub_resource === 'me' && isset($parts[2]) && $parts[2] === 'avatar') {
            $professionalModel = new Professional($db);
            $controller = new ProfessionalController($professionalModel, $db);

            if ($method === 'POST') {
                $controller->updateAvatar();
            } else {
                Response::error("Method not allowed for this endpoint.", 405);
            }
        } else {
            Response::notFound("Owner resource not found.");
        }
        break;
    
    case 'services':
        $sub_resource = $parts[1] ?? '';
        $controller = new PublicController($shopModel, $professionalModel, $serviceModel);
        
        if ($method === 'GET') {
            $controller->getServices();
        } else {
            Response::error("Method not allowed", 405);
        }
        break;
    
    case 'favorites':
        $favoritesModel = new Favorites($db);
        $controller = new FavoritesController($favoritesModel, $db);

        $type = $parts[1] ?? '';
        $item_id = $parts[2] ?? null;

        if ($method === 'GET' && $type && !$item_id) {
            $controller->getFavorites($type);
        } else if ($method === 'POST' && $type && $item_id && is_numeric($item_id)) {
            $controller->addFavorite($type, (int)$item_id);
        } else if ($method === 'DELETE' && $type && $item_id && is_numeric($item_id)) {
            $controller->removeFavorite($type, (int)$item_id);
        } else {
            Response::notFound("Favorites endpoint not found.");
        }
        break;

    case 'reviews':
        $reviewModel = new Review($db);
        $appointmentModel = new Appointment($db);
        $controller = new ReviewController($reviewModel, $appointmentModel);

        if ($method === 'POST' && !isset($parts[1])) {
            $controller->createReview();
        } else {
            Response::notFound("Reviews endpoint not found.");
        }
        break;

    default:
        Response::notFound("Resource not found.");
        break;
}