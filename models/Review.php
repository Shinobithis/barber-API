<?php
class Review
{
    private $conn;
    private $table = 'reviews';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . 
            " (appointment_id, user_id, professional_id, shop_id, rating, comment) 
            VALUES (:appointment_id, :user_id, :professional_id, :shop_id, :rating, :comment)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':appointment_id', $data['appointment_id']);
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':professional_id', $data['professional_id']);
        $stmt->bindParam(':shop_id', $data['shop_id']);
        $stmt->bindParam(':rating', $data['rating']);
        $stmt->bindParam(':comment', $data['comment']);;

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    public function findByProfessionalId($professional_id) {
        $query = "SELECT r.rating, r.comment, r.created_at, u.name as reviewer_name, u.avatar_url as reviewer_avatar
                  FROM " . $this->table . " r
                  JOIN users u ON r.user_id = u.id
                  WHERE r.professional_id = :professional_id
                  ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':professional_id', $professional_id);

        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return false;
    }

    public function findByShopId($shop_id) {
        $query = "SELECT r.rating, r.comment, r.created_at, u.name as reviewer_name, u.avatar_url as reviewer_avatar
                  FROM " . $this->table . " r
                  JOIN users u ON r.user_id = u.id
                  WHERE r.shop_id = :shop_id
                  ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':shop_id', $shop_id);

        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return false;
    }
}