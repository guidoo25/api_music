<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\LikeService;

class LikeController {
    private $likeModel;
    private $authMiddleware;

    public function __construct(LikeService $likeModel, AuthMiddleware $authMiddleware) {
        $this->likeModel = $likeModel;
        $this->authMiddleware = $authMiddleware;
    }

    public function createLike() {
        try {
            $user = $this->authMiddleware->handle();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['post_id']) && !isset($data['comment_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Post ID or Comment ID is required'
                ]);
                return;
            }

            $result = $this->likeModel->createLike($user['user_id'], $data['post_id'] ?? null, $data['comment_id'] ?? null);

            if ($result['success']) {
                http_response_code(201);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteLike($likeId) {
        try {
            $user = $this->authMiddleware->handle();
            
            $result = $this->likeModel->deleteLike($user['user_id'], $likeId);

            if ($result['success']) {
                http_response_code(200);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function getLikes($postId = null, $commentId = null) {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            $result = $this->likeModel->getLikes($postId, $commentId, $page, $limit);

            if ($result['success']) {
                http_response_code(200);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}