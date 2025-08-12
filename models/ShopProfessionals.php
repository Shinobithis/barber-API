<?php
class ShopProfessionals {
    private $conn;
    private $table = 'shop_professionals';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createRequest($shop_id, $professional_id) {
        $query = "INSERT INTO " . $this->table . " (shop_id, professional_id) VALUES (:shop_id, :professional_id)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':shop_id', $shop_id);
        $stmt->bindParam(':professional_id', $professional_id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    public function updateRequestStatus($request_id, $status) {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :request_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':request_id', $request_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getPendingRequestsForProfessional($professional_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE professional_id = :professional_id AND status = 'pending'";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':professional_id', $professional_id);

        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return false;
    }

    public function findRequestById($request_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :request_id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':request_id', $request_id);

        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }
}