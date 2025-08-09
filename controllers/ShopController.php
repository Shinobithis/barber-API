<?php
class ShopController {
    private $db;
    private $shopModel;

    public function __construct(Shop $shopModel, $db) {
        $this->db = $db;
        $this->shopModel = $shopModel;
    }

    public function createShop() {
        $data = json_decode(file_get_contents("php://input"), true);
        $user_data = AuthMiddleware::requireOwner();

        $validator = new Validator;
        $validator
            ->required('name', $data['name'] ?? null)
            ->required('address', $data['address'] ?? null)
            ->required('city', $data['city'] ?? null)
            ->required('phone', $data['phone'] ?? null)
            ->required('latitude', $data['latitude'] ?? null)
            ->required('longitude', $data['longitude'] ?? null)
            ->required('shop_type', $data['shop_type'] ?? null)
            ->required('is_vip', $data['is_vip'] ?? null)
            ->required('opens_at', $data['opens_at'] ?? null)
            ->required('closes_at', $data['closes_at'] ?? null)
            ->maxLength('name', 50, $data['name'] ?? null)
            ->maxLength('address', 200, $data['address'] ?? null)
            ->maxLength('city', 50, $data['city'] ?? null)
            ->minLength('name', 3, $data['name'] ?? null)
            ->exactLength('phone', 10, $data['phone'] ?? null)    
            ->numeric('phone', $data['phone'] ?? null);

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $data['owner_id'] = $user_data['id']; 

        $user_id = $this->shopModel->create($data);

        if ($user_id) {
            $shop = $this->shopModel->findById($user_id);
            Response::success([
                'shop' => $shop,
            ], 201, "Shop created successfully.");
        } else {
            Response::error("Failed to create shop", 500);
        }
    }

    public function updateShop($shop_id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $user_data = AuthMiddleware::requireOwner();

        $shop = $this->shopModel->findById($shop_id);
        if (!$shop || $shop['owner_id'] !== $user_data['id']) {
            Response::error("Shop not found or you do not have permission to update this shop", 404);
        }

        $validator = new Validator;
        $validator
            ->required('name', $data['name'] ?? null)
            ->required('address', $data['address'] ?? null)
            ->required('city', $data['city'] ?? null)
            ->required('phone', $data['phone'] ?? null)
            ->required('latitude', $data['latitude'] ?? null)
            ->required('longitude', $data['longitude'] ?? null)
            ->required('shop_type', $data['shop_type'] ?? null)
            ->required('is_vip', $data['is_vip'] ?? null)
            ->required('opens_at', $data['opens_at'] ?? null)
            ->required('closes_at', $data['closes_at'] ?? null)
            ->maxLength('name', 50, $data['name'] ?? null)
            ->maxLength('address', 200, $data['address'] ?? null)
            ->maxLength('city', 50, $data['city'] ?? null)
            ->minLength('name', 3, $data['name'] ?? null)
            ->exactLength('phone', 10, $data['phone'] ?? null)    
            ->numeric('phone', $data['phone'] ?? null);

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        if ($this->shopModel->update($shop_id, $data)) {
            $updatedShop = $this->shopModel->findById($shop_id);
            Response::success(['shop' => $updatedShop], 200, "Shop updated successfully.");
        } else {
            Response::error("Failed to update shop", 500);
        }
    }

    public function getShop($shop_id) {
        $shop = $this->shopModel->findById($shop_id);

        if ($shop) {
            Response::success(['shop' => $shop]);
        } else {
            Response::error("Shop not found");
        }
    }

    public function getMyShops() {
        $userData = AuthMiddleware::requireOwner();
        $shops = $this->shopModel->findByOwnerId($userData['id']);
        Response::success(['shops' => $shops]);
    }
}