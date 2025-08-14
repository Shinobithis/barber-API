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

    public function updateAvatar() {
        $professionalData = AuthMiddleware::requireProfessional();
        $id = $professionalData['id'];

        if (empty($_FILES['avatar'])) {
            Response::error("No avatar file uploaded.", 400);
        }

        $file = $_FILES['avatar'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::error("An error occurred during file upload.", 500);
        }

        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            Response::error("File is too large. Maximum size is 5MB.", 400);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            Response::error("Invalid file type. Only JPG, PNG, and GIF are allowed.", 400);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = uniqid('avatar_', true) . '.' . $extension;

        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $uploadPath = $uploadDir . $newFilename;

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            Response::error("Failed to save the uploaded file.", 500);
        }

        $dbPath = '/uploads/avatars/' . $newFilename;
        
        if ($this->professional->update($id, ['profile_image' => $dbPath])) {
            Response::success(['avatar_url' => $dbPath], 200, "Avatar updated successfully.");
        } else {
            unlink($uploadPath);
            Response::error("Failed to update profile with new avatar.", 500);
        }
    }

    public function logout() {
        return Response::success([], 200, "Logout successful.");
    }
}