<?php
class ProfessionalServices {
    private $conn;
    private $table = 'professional_services';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function addServiceToProfessional($professional_id, $service_id) {
        $query = "INSERT INTO " . $this->table . " (professional_id, service_id) VALUES (:professional_id, :service_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':professional_id', $professional_id);
        $stmt->bindParam(':service_id', $service_id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function removeServiceFromProfessional($professional_id, $service_id) {
        $query = "DELETE FROM " . $this->table . " WHERE professional_id = :professional_id AND service_id = :service_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':professional_id', $professional_id);
        $stmt->bindParam(':service_id', $service_id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function getServicesForProfessional($professional_id) {
        $query = "SELECT s.id, s.name 
            FROM services s
            JOIN " . $this->table . " ps ON s.id = ps.service_id 
            WHERE ps.professional_id = :professional_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':professional_id', $professional_id);
        
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function checkIfServiceIsAdded($professional_id, $service_id) {
        $query = "SELECT COUNT(*) FROM " . $this->table . " WHERE professional_id = :professional_id AND service_id = :service_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':professional_id', $professional_id);
        $stmt->bindParam(':service_id', $service_id);

        if ($stmt->execute()) {
            return $stmt->fetchColumn() > 0;
        }
        return false;
    }
}