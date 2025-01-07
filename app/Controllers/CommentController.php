<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\CommentService;

class CommentController {
    private $commentModel;
    private $authMiddleware;

    public function __construct(CommentService $commentModel, AuthMiddleware $authMiddleware) {
        $this->commentModel = $commentModel;
        $this->authMiddleware = $authMiddleware;
    }

    public function createComment() {
        try {
            $user = $this->authMiddleware->handle();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['post_id']) || !isset($data['content'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Post ID and content are required'
                ]);
                return;
            }

            $result = $this->commentModel->createComment($user['user_id'], $data['post_id'], $data['content']);

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

public function getComments() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        $postId = isset($data['postId']) ? $data['postId'] : null;
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 10;

        if ($postId === null) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'postId is required'
            ]);
            return;
        }

        $result = $this->commentModel->getComments($postId, $page, $limit);

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

    public function updateComment($commentId) {
        try {
            $user = $this->authMiddleware->handle();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['content'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Content is required'
                ]);
                return;
            }

            $result = $this->commentModel->updateComment($user['user_id'], $commentId, $data['content']);

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

    public function deleteComment($commentId) {
        try {
            $user = $this->authMiddleware->handle();
            
            $result = $this->commentModel->deleteComment($user['user_id'], $commentId);

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