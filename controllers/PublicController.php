<?php

class PublicController {
    private $shopModel;
    private $serviceModel;
    private $professionalModel;

    public function __construct(Shop $shopModel, Professional $professionalModel, Service $serviceModel) {
        $this->shopModel = $shopModel;
        $this->professionalModel = $professionalModel;
        $this->serviceModel = $serviceModel;
    }

    public function getTopProfessionals() {
        try {
            $filters = $_GET;
            
            $data = $this->professionalModel->findTopRated($filters);
            Response::success($data);
        } catch (PDOException $e){
            error_log($e->getMessage());
            Response::error("A database error occurred.", 500);
        }
    }
    
    public function getShopById($id) {
        try {
            $shopDetails = $this->shopModel->findById($id);

            if (!$shopDetails) {
                Response::notFound("Barbershop not found!");
            }

            $professionals = $this->professionalModel->findByShopId($shopDetails['id']);

            $data = [
                'shop' => $shopDetails,
                'professionals' => $professionals
            ];
            
            Response::success($data);
        } catch (PDOException $e){
            error_log($e->getMessage());
            Response::error("A database error occurred.", 500);
        }
    }

    public function getShops() {
        $filters = $_GET;

        try {
            $data = $this->shopModel->findAll($filters);
            Response::success($data);
        } catch (PDOException $e) {
            Response::error("A database error occurred.", 500);
        }
    }

    public function getServices() {
        try {
            $data = $this->serviceModel->getAll();
            Response::success($data);
        } catch (PDOException $e) {
            Response::error("A database error occurred.", 500);
        }
    }

    public function getProfessionalById($id) {
        try {
            $profileData = $this->professionalModel->findProfileById($id);

            if (!$profileData) {
                Response::notFound("Professional not found.");
            }

            $data = [
                "id" => $profileData['details']['id'],
                "name" => $profileData['details']['name'],
                "image" => $profileData['details']['profile_image'],
                "images" => $profileData['gallery_images'],
                "specialty" => $profileData['details']['specialty'],
                "location" => $profileData['shop_info'] ? $profileData['shop_info']['address'] . ', ' . $profileData['shop_info']['city'] : 'Not currently working at a shop',
                "rating" => $profileData['average_rating'],
                "reviewCount" => $profileData['review_count'],
                "description" => $profileData['details']['bio'],
                "contact" => [
                    "phone" => $profileData['details']['phone'],
                    "email" => $profileData['details']['email']
                ],
                "workingHours" => [], 
                "services" => $profileData['services'],
                "reviews" => [], 
                "acceptsCard" => $profileData['shop_info'] ? (bool)$profileData['shop_info']['accepts_card'] : false,
                "kidsFriendly" => $profileData['shop_info'] ? (bool)$profileData['shop_info']['kids_friendly'] : false,
                "openEarly" => $profileData['shop_info'] ? (bool)$profileData['shop_info']['open_early'] : false,
                "openLate" => $profileData['shop_info'] ? (bool)$profileData['shop_info']['open_late'] : false,
                "isVip" => $profileData['shop_info'] ? (bool)$profileData['shop_info']['is_vip'] : false
            ];
            
            $dayMap = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday'];
            $scheduleByDay = [];
            foreach ($profileData['schedule'] as $block) {
                $dayName = $dayMap[$block['day_of_week']];
                $scheduleByDay[$dayName][] = substr($block['start_time'], 0, 5) . ' - ' . substr($block['end_time'], 0, 5);
            }
            foreach ($dayMap as $dayNumber => $dayName) {
                $data['workingHours'][] = [
                    'day' => $dayName,
                    'hours' => isset($scheduleByDay[$dayName]) ? implode(' & ', $scheduleByDay[$dayName]) : 'Closed'
                ];
            }

            foreach ($profileData['reviews'] as $review) {
                $data['reviews'][] = [
                    'id' => $review['id'] ?? null,
                    'userName' => $review['reviewer_name'],
                    'userImage' => $review['reviewer_avatar'],
                    'comment' => $review['comment'],
                    'date' => $review['created_at'],
                    'rating' => (int)$review['rating']
                ];
            }
            
            Response::success($data);
        } catch (PDOException $e){
            error_log($e->getMessage());
            Response::error("A database error occurred.", 500);
        }
    }
    public function getProfessionals() {
        try {
            $filters = $_GET;
            
            $data = $this->professionalModel->findAll($filters);

            foreach ($data as &$professional) {
                $professional['rating'] = (float)($professional['rating'] ?? 0);
                $professional['reviewCount'] = (int)($professional['reviewCount'] ?? 0);
                $professional['is_available'] = (bool)($professional['is_available'] ?? false);
                $professional['is_vip'] = (bool)($professional['is_vip'] ?? false);
            }

            Response::success($data);

        } catch (PDOException $e){
            error_log($e->getMessage());
            Response::error("A database error occurred.", 500);
        }
    }

}
