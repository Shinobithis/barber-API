<?php
/**
 * Authentication Controller
 */

class AuthController {
    private $db;
    private $user;

    public function __construct(User $user, $db) {
        $this->user = $user;
        $this->db = $db;
    }

    public function register() {
        $data = json_decode(file_get_contents("php://input"), true);

        $validator = new Validator;
        $validator
            ->required('name', $data['name'] ?? null)
            ->required('email', $data['email'] ?? null)
            ->required('phone', $data['phone'] ?? null)
            ->minLength('password', 8, $data['password'] ?? null)
            ->email('email', $data['email'] ?? null)
            ->maxLength('name', 50, $data['name'] ?? null)
            ->minLength('name', 3, $data['name'] ?? null)
            ->exactLength('phone', 10, $data['phone'] ?? null)    
            ->numeric('phone', $data['phone'] ?? null)
            ->unique('users', $this->db, $data['email'] ?? null, 'email', 'email', null, "This email address is already taken.");

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $password_hash = $this->user->hashPassword($data['password']);

        $user_data = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password_hash' => $password_hash
        ];

        $user_id = $this->user->create($user_data);

        if ($user_id) {
            $user = $this->user->findById($user_id);
            $token = JWTHelper::generateToken($user);

            return Response::success([
                'user' => $user,
                'token' => $token,
            ], 201, "user created successfuly.");
        } else {
            Response::error("Failed to create user", 500);
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

        $user  = $this->user->findByEmail($data['email']);

        if (!$user || !$this->user->verifyPassword($data['password'], $user['password_hash'])) {
            Response::error("Invalid email or password", 401);
        }

        unset($user['password_hash']);

        $token = JWTHelper::generateToken($user);
        Response::success([
            'user' => $user,
            'token' => $token
        ], 200, "Login successful");
    }

    public function me() {
        $user_data = AuthMiddleware::authenticate();
        $user = $this->user->findById($user_data['id']);

        if (!$user) {
            Response::notFound("User not found!");
        }

        Response::success($user);
    }

    public function updateProfile() {
        $user_data = AuthMiddleware::authenticate();
        $data = json_decode(file_get_contents("php://input"), true);

        $validator = new Validator;
        $validator
            ->required('name', $data['name'] ?? null)
            ->required('phone', $data['phone'] ?? null)
            ->maxLength('name', 50, $data['name'] ?? null)
            ->minLength('name', 3, $data['name'] ?? null)
            ->maxLength('phone', 10, $data['phone'] ?? null)
            ->minLength('phone', 10, $data['phone'] ?? null)    
            ->numeric('phone', $data['phone'] ?? null);

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $updated_data = [
            'name' => $data['name'],
            'phone' => $data['phone'],
        ];

        if ($this->user->update($user_data['id'], $updated_data)) {
            $updated_user = $this->user->findById($user_data['id']);
            Response::success($updated_user, 200, "Profile updated successfully");
        } else {
            Response::error("Failed to update profile", 500);
        }
    }

    public function logout() {
        Response::success(null, 200, "Logged out successfully");
    }
}