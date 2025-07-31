<?php
class Barbershop {
    private $conn;
    private $table = "barbershops";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getTopRatedBarbers() {
        $querry = "SELECT " . $this->table . ".id " . $this->table . ".name " . $this->table . ".address " . $this->table . ".city 
            FROM " . $this->table .
            "INNER JOIN barbers ON " . $this->table . ".BarberID=barber.id";
        
        $stmt = $this->conn->prepare($querry);

        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }

    public function getAvailableNow() {
        $querry = "SELECT * FROM " . $this->table . " WHERE is_available = true";

        $stmt = $this->conn->prepare($querry);

        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }

    public function getAllShops() {
        $querry = "SELECT * FROM " . $this->table . 
            " ORDER BY rating DESC 
            LIMIT 10 OFFSET 10";
        
        $stmt = $this->conn->prepare($querry);

        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
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
        $querry = "SELECT barber.name FROM barber 
            INNER JOIN barber.id=barber.barbershopID WHERE id = :id"; 
        
        $stmt = $this->conn->prepare($querry);
        $stmt->bindParam(':id', $shop_id);
        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function getServicesForShop($shop_id) {
        $querry = "SELECT barber.services FROM barber " .
            "INNER JOIN barber.barbershopID=" . $this->table . ".:id";
            
        $stmt = $this->conn->prepare($querry);
        $stmt->bindParam(':id', $shop_id);
        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false; 
    }

    public function getScheduleForBarber($barber_id, $date) {

    }
}