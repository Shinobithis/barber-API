<?php
class Barbershop {
    private $conn;
    private $table = "barbershops";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getTopRatedBarbers() {
        $querry = "SELECT b.id, b.name, AVG(r.rating) as average_rating, COUNT(r.id) as review_count 
            FROM barbers b
            LEFT JOIN reviews r ON b.id=r.barber_id
            GROUP BY b.id
            ORDER BY average_rating DESC LIMIT 10";
        
        $stmt = $this->conn->prepare($querry);

        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return false;
    }

    public function getAvailableNow() {
        $querry = "SELECT * FROM barbers WHERE is_available = true";

        $stmt = $this->conn->prepare($querry);

        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return false;
    }

    public function getAllShops($limit = 10, $offset) {
        $querry = "SELECT * FROM " . $this->table .  
            " LIMIT :limit 10 OFFSET :offset 10";
        
        $stmt = $this->conn->prepare($querry);

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return false;
    }
    
    public function findById($shop_id) {
        $querry = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($querry);
        
        $stmt->bindParam(':id', $shop_id);
        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function getBarbersForShop($shop_id) {
        $querry = "SELECT id, name, profile_image, is_available 
            FROM barbers 
            WHERE barbershop_id = :shop_id"; 
        
        $stmt = $this->conn->prepare($querry);
        $stmt->bindParam(':shop_id', $shop_id);
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function getServicesForBarber($barber_id) {
        $querry = "SELECT s.name, bs.price, bs.duration_min
            FROM barber_services bs 
            JOIN services s ON bs.service_id = s.id
            WHERE bs.barber_id = :barber_id";
            
        $stmt = $this->conn->prepare($querry);
        $stmt->bindParam(':barber_id', $barber_id);
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return false; 
    }

    public function getScheduleForBarber($barber_id, $date) {
        $querry = "SELECT * FROM appointments WHERE barber_id = :barber_id 
        AND date = :date ORDER BY time_start ASC";

        $stmt = $this->conn->prepare($querry);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':barber_id', $barber_id);
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return false; 
    }

    public function findBarberById($barber_id)
    {
        $query = "SELECT * FROM barbers WHERE id = :barber_id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':barber_id', $barber_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isServiceOfferedByBarber($service_id, $barber_id)
    {
        $query = "SELECT COUNT(*) FROM barber_services
                WHERE service_id = :service_id
                AND barber_id = :barber_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
        $stmt->bindParam(':barber_id', $barber_id, PDO::PARAM_INT);

        $stmt->execute();

        $count = $stmt->fetchColumn();

        return $count > 0;
    }
}