<?php
namespace App\Controllers;

use App\Services\UserService;

class UserController {
    private $userService;

    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }

    public function getUserWithSongs() {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['userId'];
        $status = $data['status'];

        $result = $this->userService->getUserWithSongs($userId, $status);

        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode($result);
        }
    }
}
