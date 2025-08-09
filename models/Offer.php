<?php
class Offer {
    private $conn;
    private $table = 'offers';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . 
            " (professional_id, service_id, name, price, duration_min, description) 
            VALUES (:professional_id, :service_id, :name, :price, :duration_min, :description)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':professional_id', $data['professional_id'], PDO::PARAM_INT);
        $stmt->bindParam(':service_id', $data['service_id'], PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':price', $data['price'], PDO::PARAM_STR);
        $stmt->bindParam(':duration_min', $data['duration_min'], PDO::PARAM_INT);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['service_id', 'name', 'price', 'duration_min', 'description'])) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute($params);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function getOffersForProfessional($professional_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE professional_id = :professional_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':professional_id', $professional_id, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return false;
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}