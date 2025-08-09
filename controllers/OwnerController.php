<?php
/**
 * Authentication Controller
 */

class OwnerController {
    private $db;
    private $owner;

    public function __construct(ShopOwner $owner, $db) {
        $this->owner = $owner;
        $this->db = $db;
    }

    public function register() {
        $data = json_decode(file_get_contents("php://input"), true);

        $validator = new Validator;
        $validator
            ->required('name', $data['name'] ?? null)
            ->required('email', $data['email'] ?? null)
            ->minLength('password', 8, $data['password'] ?? null)
            ->email('email', $data['email'] ?? null)
            ->maxLength('name', 50, $data['name'] ?? null)
            ->minLength('name', 3, $data['name'] ?? null)
            ->unique('owners', $this->db, $data['email'] ?? null, 'email', 'email', null, "This email address is already taken.");

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $password_hash = $this->owner->hashPassword($data['password']);

        $owner_data = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $password_hash
        ];

        $owner_id = $this->owner->create($owner_data);

        if ($owner_id) {
            $owner = $this->owner->findById($owner_id);
            $token = JWTHelper::generateToken($owner, 'owner');

            return Response::success([
                'owner' => $owner,
                'token' => $token,
            ], 201, "owner created successfuly.");
        } else {
            Response::error("Failed to create owner", 500);
        }
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);

        $validator = new Validator;
        $validator
            ->required('email', $data['email'] ?? null)
            ->required('password', $data['password'] ?? null)
            ->minLength('password', 8, $data['password'] ?? null)
            ->email('email', $data['email'] ?? null);
        
        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $owner  = $this->owner->findByEmail($data['email']);

        if (!$owner || !$this->owner->verifyPassword($data['password'], $owner['password_hash'])) {
            Response::error("Invalid email or password", 401);
        }

        unset($owner['password_hash']);

        $token = JWTHelper::generateToken($owner, 'owner');
        Response::success([
            'owner' => $owner,
            'token' => $token
        ], 200, "Login successful");
    }

    public function me() {
        $owner_data = AuthMiddleware::requireOwner();
        $owner = $this->owner->findById($owner_data['id']);

        if (!$owner) {
            Response::notFound("owner not found!");
        }

        Response::success($owner);
    }

    public function logout() {
        Response::success(null, 200, "Logged out successfully");
    }
}