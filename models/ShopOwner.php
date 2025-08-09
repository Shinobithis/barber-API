<?php 
class ShopOwner {
    private $conn;
    private $table = "shop_owners";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . 
            " (name, email, password_hash) 
            VALUES (:name, :email, :password_hash)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":email", $data['email']);
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
        $query = "SELECT name, email, created_at FROM ". $this->table . 
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
}