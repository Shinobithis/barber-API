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

    public function findProfileById($id) {
        $profile = [];

        $stmt = $this->conn->prepare("SELECT id, name, email, phone, bio, profile_image, specialty FROM " . $this->table . " WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $profile['details'] = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile['details']) {
            return false;
        }

        $stmt = $this->conn->prepare("
            SELECT s.address, s.city, s.is_vip, s.accepts_card, s.kids_friendly, s.open_early, s.open_late
            FROM shops s
            JOIN shop_professionals sp ON s.id = sp.shop_id
            WHERE sp.professional_id = :id AND sp.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $profile['shop_info'] = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->conn->prepare("SELECT image_url FROM professional_photos WHERE professional_id = :id");
        $stmt->execute([':id' => $id]);

        $profile['gallery_images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $this->conn->prepare("SELECT id, name, description, price, duration_min FROM offers WHERE professional_id = :id");
        $stmt->execute([':id' => $id]);
        $profile['services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->conn->prepare("SELECT day_of_week, start_time, end_time FROM schedules WHERE professional_id = :id ORDER BY day_of_week, start_time");
        $stmt->execute([':id' => $id]);
        $profile['schedule'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->conn->prepare("
            SELECT r.rating, r.comment, r.created_at, u.name as reviewer_name, u.avatar_url as reviewer_avatar
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.professional_id = :id
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([':id' => $id]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $profile['reviews'] = $reviews;

        $totalRating = 0;
        foreach ($reviews as $review) {
            $totalRating += $review['rating'];
        }
        $profile['review_count'] = count($reviews);
        $profile['average_rating'] = $profile['review_count'] > 0 ? round($totalRating / $profile['review_count'], 1) : 0;
        return $profile;
    }

    public function findAll($filters = []) {
        $params = [];
        $whereClauses = [];
        $mainImageSubquery = "(SELECT pp.image_url 
                           FROM professional_photos pp 
                           WHERE pp.professional_id = p.id AND pp.is_main_work_image = 1 
                           LIMIT 1)";

        $query = "SELECT
                    p.id,
                    p.name,
                    p.profile_image AS image,
                    p.specialty,
                    p.is_available,
                    s.is_vip,
                    AVG(r.rating) AS rating,
                    COUNT(DISTINCT r.id) AS reviewCount,
                    {$mainImageSubquery} AS mainWorkImage
                FROM " . $this->table . " p
                LEFT JOIN reviews r ON p.id = r.professional_id
                LEFT JOIN shop_professionals sp ON p.id = sp.professional_id AND sp.status = 'active'
                LEFT JOIN shops s ON sp.shop_id = s.id";

        if (!empty($filters['gender_scope']) && in_array($filters['gender_scope'], ['men', 'women'])) {
            $query .= " INNER JOIN professional_skills ps ON p.id = ps.professional_id
                        INNER JOIN services serv ON ps.service_id = serv.id";
            $whereClauses[] = "(serv.gender_scope = :gender_scope OR serv.gender_scope = 'unisex')";
            $params[':gender_scope'] = $filters['gender_scope'];
        }

        if (isset($filters['is_available']) && $filters['is_available'] == 'true') {
            $whereClauses[] = "p.is_available = 1";
        }

        if (!empty($whereClauses)) {
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }


        $query .= " GROUP BY p.id";

        $orderBy = "ORDER BY rating DESC, reviewCount DESC";

        $query .= " " . $orderBy;

        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 20;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
        $query .= " LIMIT " . $limit . " OFFSET " . $offset;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addPortfolioImage($professional_id, $image_url) {
        $query = "INSERT INTO professional_photos (professional_id, image_url) VALUES (:professional_id, :image_url)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':professional_id', $professional_id);
        $stmt->bindParam(':image_url', $image_url);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
}