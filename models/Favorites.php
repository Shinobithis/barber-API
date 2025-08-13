<?php
class Favorites {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function addFavorite($user_id, $item_id, $type) {
        if (!$type || !$user_id) {
            return false;
        }

        $tableName = "favorite_" . $type . "s";
        $columnName = $type . "_id";

        $query = "INSERT INTO " . $tableName . " (user_id, " . $columnName . ") VALUES (:user_id, :item_id)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':item_id', $item_id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function removeFavorite($user_id, $item_id, $type) {
        if (!$type || !$user_id) {
            return false;
        }

        $tableName = "favorite_" . $type . "s";
        $columnName = $type . "_id";

        $query = "DELETE FROM " . $tableName . " WHERE user_id = :user_id AND " . $columnName . " = :item_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':item_id', $item_id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function getFavorites($user_id, $type) {
        if (!$type || !$user_id) {
            return false;
        }
        if (!in_array($type, ['shop', 'service', 'professional'])) {
            return false;
        }

        $query = "";
        switch ($type) {
            case 'shop':
                $query = "SELECT s.* 
                        FROM favorite_shops fs
                        JOIN shops s ON fs.shop_id = s.id
                        WHERE fs.user_id = :user_id";
                break;

            case 'service':
                $query = "SELECT sv.* 
                        FROM favorite_services fs
                        JOIN services sv ON fs.service_id = sv.id
                        WHERE fs.user_id = :user_id";
                break;

            case 'professional':
                $query = "SELECT p.* 
                        FROM favorite_professionals fp
                        JOIN professionals p ON fp.professional_id = p.id
                        WHERE fp.user_id = :user_id";
                break;

            default:
                return false;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}