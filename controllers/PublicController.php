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
}
