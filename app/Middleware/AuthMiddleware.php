<?php
namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware {
    private $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    public function handle() {
        $token = $this->getTokenFromRequest();

        if (!$token) {
            return $this->respondUnauthorized('Token no proporcionado');
        }

        $payload = $this->authService->verifyToken($token);

        if (!$payload) {
            return $this->respondUnauthorized('Token inválido o expirado');
        }

        // Agregar información del usuario al request
        $_REQUEST['user'] = $payload;
        return $payload;
    }

    private function getTokenFromRequest() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            return str_replace('Bearer ', '', $headers['Authorization']);
        }
        return null;
    }

    private function respondUnauthorized($message) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit();
    }
}

