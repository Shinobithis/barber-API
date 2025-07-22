<?php 
class User {
    private $conn;
    private $table = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . 
            " (name, email, phone, password_hash) 
            VALUES (:name, :email, :phone, :password_hash)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":email", $data['email']);
        $stmt->bindParam(":phone", $data['phone']);
        $stmt->bindParam(":password_hash", $data['password_hash']);

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
        $query = "SELECT name, email, phone, role, avatar_url, created_at FROM ". $this->table . 
            " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ["name", "email", "phone", "avatar_url"])) {
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

    public function getAll($limit = 50, $offset = 0) {
        $query = "SELECT id, name, email, phone, role, created_at FROM " . $this->table . 
            " ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}