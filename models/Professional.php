<?php 
class Professional {
    private $conn;
    private $table = "professionals";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . 
            " (name, email, password_hash, phone) 
            VALUES (:name, :email, :password_hash, :phone)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":email", $data['email']);
        $stmt->bindParam(":password_hash", $data['password_hash']);
        $stmt->bindParam(":phone", $data['phone']);


        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM ". $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT id, name, email, bio, profile_image, phone, is_available, created_at FROM ". $this->table . 
            " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    public function emailExists($email, $exclude_id = null) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email";
        $param = [':email' => $email];

        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
            $param[':exclude_id'] = $exclude_id; 
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($param);

        return $stmt->fetchColumn() > 0;
    }

    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        $allowed_fields = ["name", "phone", "bio", "profile_image", "is_available"];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            } 
        }

        if (empty($fields)) {
            return false;
        }

        $query = "UPDATE " . $this->table . " SET " . implode(", ", $fields) . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        return $stmt->execute($params);
    }

    public function findByShopId($shop_id) {
        $query = "SELECT p.id, p.name, p.bio, p.profile_image, p.is_available
                FROM " . $this->table . " p
                INNER JOIN shop_professionals sp ON p.id = sp.professional_id
                WHERE sp.shop_id = :shop_id AND sp.status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":shop_id", $shop_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function findTopRated($filters = []) {
        $query = "SELECT
                    p.id,
                    p.name,
                    p.profile_image,
                    AVG(r.rating) as average_rating,
                    COUNT(r.id) as review_count
                FROM " . $this->table . " p
                LEFT JOIN reviews r ON p.id = r.professional_id
                INNER JOIN professional_skills ps ON p.id = ps.professional_id
                INNER JOIN services s ON ps.service_id = s.id";

        $params = [];
        $whereClauses = ["p.id IS NOT NULL"];


        if (!empty($filters['gender_scope']) && in_array($filters['gender_scope'], ['men', 'women'])) {
            $whereClauses[] = "(s.gender_scope = :gender_scope OR s.gender_scope = 'unisex')";
            $params[':gender_scope'] = $filters['gender_scope'];
        }

        if (!empty($whereClauses)) {
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $query .= " GROUP BY p.id ORDER BY average_rating DESC, review_count DESC LIMIT 10";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}