<?php
class Service {
    private $conn;
    private $table = 'services';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function findById($service_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $service_id);

        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }
}