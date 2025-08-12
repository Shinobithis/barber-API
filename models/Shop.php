<?php
class Shop {
    private $conn;
    private $table = 'shops';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . 
            " (owner_id, name, address, city, phone, latitude, longitude, shop_type, is_vip, opens_at, closes_at) 
            VALUES (:owner_id, :name, :address, :city, :phone, :latitude, :longitude, :shop_type, :is_vip, :opens_at, :closes_at)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':owner_id', $data['owner_id']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':latitude', $data['latitude']);
        $stmt->bindParam(':longitude', $data['longitude']);
        $stmt->bindParam(':shop_type', $data['shop_type']);
        $stmt->bindParam(':is_vip', $data['is_vip']);
        $stmt->bindParam(':opens_at', $data['opens_at']);
        $stmt->bindParam(':closes_at', $data['closes_at']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    public function update($shop_id, $data) {
        $fields = [];
        $params = [':id' => $shop_id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['name', 'address', 'city', 'phone', 'latitude', 'longitude', 'shop_type', 'is_vip', 'opens_at', 'closes_at'])) {
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

    public function findById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByOwnerId($owner_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE owner_id = :owner_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':owner_id', $owner_id);
        
        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll($filters = []) {
        $query = "SELECT * FROM " . $this->table;
        $params = [];
        $whereClauses = [];

        if (!empty($filters['is_vip']) && $filters['is_vip'] == 'true') {
            $whereClauses[] = "is_vip = 1";
        }

        if (!empty($filters['shop_type'])) {
            $whereClauses[] = "shop_type = :shop_type";
            $params[':shop_type'] = $filters['shop_type'];
        }

        // Add more filters here later (e.g., opens_at, closes_at)

        // If there are any WHERE conditions, append them to the query
        if (!empty($whereClauses)) {
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }

        // Handle 'near_me' sorting (this is a simplified version)
        // A real implementation uses the Haversine formula, which is very complex.
        // For now, we can just order randomly or by creation date.
        //we will need to add here the near me complex Haversine formular

        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 10;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
        $query .= " LIMIT " . $limit . " OFFSET " . $offset;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}