<?php

class BarberShopController {
    private $db;
    private $barbershop;

    public function __construct(Barbershop $barbershop, $db) {
        $this->db = $db;
        $this->barbershop = $barbershop;
    }

    public function getTopBarbers() {
        try {
            $data = $this->barbershop->getTopRatedBarbers();
            Response::success($data);
        } catch (PDOException $e){
            error_log($e->getMessage());
            Response::error("A database error occurred.", 500);
        }
    }

    public function getAvailableBarbers() {
        try {
            $data = $this->barbershop->getAvailableNow();
            Response::success($data);
        } catch (PDOException $e){
            error_log($e->getMessage());
            Response::error("A database error occurred.", 500);
        }
    }

    public function getAllShops() {
        if (isset($_GET['limit']) && isset($_GET['offset'])) {
            $limit = $_GET['limit'] ?? 10;
            $offset = $_GET['offset'] ?? 0;
            try {
                $data = $this->barbershop->getAllShops((int)$limit, (int)$offset);
                Response::success($data);
            } catch (PDOException $e){
                error_log($e->getMessage());
            Response::error("A database error occurred.", 500);
            }
        }
    }

    public function getShopById($id) {
        try {
            $shopDetails = $this->barbershop->findById($id);

            if (!$shopDetails) {
                Response::notFound("Barbershop not found!");
            }

            $barbers = $this->barbershop->getBarbersForShop($id);

            $services = [];
            foreach ($barbers as $barber) {
                $barberServices = $this->barbershop->getServicesForBarber($barber['id']);
                $services = array_merge($services, $barberServices);
            }

            $data = [
                'shop' => $shopDetails,
                'barbers' => $barbers,
                'services' => array_unique($services, SORT_REGULAR)
            ];
            
            Response::success($data);
        } catch (PDOException $e){
            error_log($e->getMessage());
            Response::error("A database error occurred.", 500);
        }
    }

    public function getBarberSchedule($id, $date) {
        try {
            $data = $this->barbershop->getScheduleForBarber($id, $date);
            Response::success($data);
        } catch (PDOException $e){
            error_log($e->getMessage());
            Response::error("A database error occurred.", 500);
        }
    }
}
