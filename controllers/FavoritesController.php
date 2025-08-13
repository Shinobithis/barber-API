<?php
class FavoritesController
{
    private $favoritesModel;
    private $db;

    public function __construct(Favorites $favoritesModel, $db)
    {
        $this->favoritesModel = $favoritesModel;
        $this->db = $db;
    }

    public function getFavorites($type) {
        $userData = AuthMiddleware::requireUser();
        
        $favorites = $this->favoritesModel->getFavorites($userData['id'], $type);

        if ($favorites === false) {
            Response::error("Invalid favorite type specified.", 400);
        }

        Response::success(['favorites' => $favorites]);
    }

    public function addFavorite($type, $item_id) {
        $userData = AuthMiddleware::requireUser();
        $user_id = $userData['id'];

        if (!in_array($type, ['shop', 'service', 'professional'])) {
            Response::error("Invalid favorite type specified.", 400);
        }

        if (!$this->itemExists($item_id, $type)) {
            Response::notFound(ucfirst($type) . " not found.");
        }

        if ($this->favoritesModel->addFavorite($user_id, $item_id, $type)) {
            Response::success(null, 201, "Favorite added successfully.");
        } else {
            Response::error("This item is already in your favorites.", 409);
        }
    }

    public function removeFavorite($type, $item_id) {
        $userData = AuthMiddleware::requireUser();
        $user_id = $userData['id'];
        
        if ($this->favoritesModel->removeFavorite($user_id, $item_id, $type)) {
            Response::noContent();
        } else {
            Response::error("Failed to remove favorite.", 500);
        }
    }

    /**
     * A private helper method to check if an item exists in the database.
     * This keeps the controller's main methods clean.
     */
    private function itemExists($id, $type) {
        $tableName = $type . "s";
        
        $query = "SELECT COUNT(*) FROM " . $tableName . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() > 0;
    }
}