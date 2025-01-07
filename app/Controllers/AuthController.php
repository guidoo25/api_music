<?php
// app/Controllers/AuthController.php
namespace App\Controllers;

use App\Services\AuthService;

class AuthController {
    private $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Email y contraseña son requeridos'
            ]);
            return;
        }

        $result = $this->authService->login($data['email'], $data['password']);

        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(401);
            echo json_encode($result);
        }
    }

    public function register() {
        $data = json_decode(file_get_contents('php://input'), true);
    
        $requiredFields = ['email', 'password', 'username', 'full_name', 'role'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => "El campo '$field' es requerido"
                ]);
                return;
            }
        }

        $validRoles = ['artist', 'listener', 'admin', 'moderator'];
        if (!in_array($data['role'], $validRoles)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Rol inválido'
            ]);
            return;
        }

        $result = $this->authService->register($data);

        if ($result['success']) {
            http_response_code(201); // Created
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
    }


}

