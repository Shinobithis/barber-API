<?php
/**
 * Authentication Controller
 */

class ProfessionalController {
    private $db;
    private $professional;

    public function __construct(Professional $professional, $db) {
        $this->professional = $professional;
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
            ->unique('professionals', $this->db, $data['email'] ?? null, 'email', 'email', null, "This email address is already taken.");

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $password_hash = $this->professional->hashPassword($data['password']);

        $professional_data = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password_hash' => $password_hash
        ];

        $professional_id = $this->professional->create($professional_data);

        if ($professional_id) {
            $professional = $this->professional->findById($professional_id);
            unset($professional['password_hash']);
            
            $token = JWTHelper::generateToken($professional, 'professional');

            return Response::success([
                'professional' => $professional,
                'token' => $token,
            ], 201, "Professional created successfully.");
        } else {
            Response::error("Failed to create professional", 500);
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

        $professional = $this->professional->findByEmail($data['email']);

        if (!$professional || !$this->professional->verifyPassword($data['password'], $professional['password_hash'])) {
            Response::error("Invalid email or password", 401);
        }

        unset($professional['password_hash']);
        $token = JWTHelper::generateToken($professional, 'professional');

        Response::success([
            'professional' => $professional,
            'token' => $token,
        ], 200, "Login successful.");
    }

    public function me() {
        $professional_data = AuthMiddleware::requireProfessional();
        $professional = $this->professional->findById($professional_data['id']);

        if (!$professional) {
            Response::error("Professional not found", 404);
        }

        unset($professional['password_hash']);
        Response::success($professional, 200, "Professional data retrieved successfully.");
    }

    public function updateProfile() {
        $professional_data = AuthMiddleware::requireProfessional();
        $id = $professional_data['id'];

        $data = json_decode(file_get_contents("php://input"), true);

        $validator = new Validator;
        $validator
            ->required('name', $data['name'] ?? null)
            ->email('email', $data['email'] ?? null)
            ->maxLength('name', 50, $data['name'] ?? null)
            ->minLength('name', 3, $data['name'] ?? null)
            ->exactLength('phone', 10, $data['phone'] ?? null)
            ->numeric('phone', $data['phone'] ?? null)
            ->minLength('bio', 10, $data['bio'] ?? null)
            ->maxLength('bio', 500, $data['bio'] ?? null)
            ->unique('professionals', $this->db, $data['email'] ?? null, 'email', 'email', $id, "This email address is already taken.");
        
        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        if ($this->professional->update($id, $data)) {
            $updated_professional = $this->professional->findById($id);
            unset($updated_professional['password_hash']);
            Response::success($updated_professional, 200, "Profile updated successfully.");
        } else {
            Response::error("Failed to update profile or no changes were made.", 500);
        }
    }

    public function logout() {
        return Response::success([], 200, "Logout successful.");
    }
}