<?php
// app/Controllers/AdminController.php
namespace App\Controllers;

use App\Services\AdminService;
use App\Database\MySQLDatabase;
use App\Middleware\AuthMiddleware;

class AdminController
{
    private $adminService;
    private $authMiddleware;


    public function __construct( AdminService $adminService, AuthMiddleware $authMiddleware)
    {

        $this->adminService = $adminService;
        $this->authMiddleware = $authMiddleware;

    }

    public function getDashboardStats()
    {
        $user = $this->authMiddleware->handle();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            return;
        }

        $stats = $this->adminService->getDashboardStats();
        echo json_encode($stats);
    }

    public function listUsers()
    {
        $user = $this->authMiddleware->handle();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $filters = $data['filters'] ?? [];

        $users = $this->adminService->listUsers($filters);
        echo json_encode($users);
    }

    public function deleteUser()
    {
        $user = $this->authMiddleware->handle();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['user_id'] ?? null;
        if ($userId === null) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'User ID is required'
            ]);
            return;
        }
        $result = $this->adminService->deleteUser($userId);
        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }
        echo json_encode($result);
    }
}
