<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\PostService;

class PostController {
    private $postModel;
    private $authMiddleware;

    public function __construct(PostService $postModel, AuthMiddleware $authMiddleware) {
        $this->postModel = $postModel;
        $this->authMiddleware = $authMiddleware;
    }

    public function createPost() {
        try {
            $user = $this->authMiddleware->handle();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['title']) || !isset($data['content'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Title and content are required'
                ]);
                return;
            }

            $result = $this->postModel->createPost($user['user_id'], $data);

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
    

    public function getTopics() {
        try {
            $result = $this->postModel->getTopics();

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

    public function getPosts() {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $topicId = isset($_GET['topic_id']) ? $_GET['topic_id'] : null;
            
            $result = $this->postModel->getPosts($page, $limit, $topicId);

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

    public function inactivePost() {
        try {
            $user = $this->authMiddleware->handle();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['post_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Post ID is required'
                ]);
                return;
            }

            $result = $this->postModel->inactivePost( $data['post_id']);

            if ($result['success']) {
                http_response_code(200);
            } else {
                http_response_code($result['message'] === 'Unauthorized to inactive this post' ? 403 : 400);
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

    public function createTopic() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Name is required'
                ]);
                return;
            }

            $result = $this->postModel->createTopics($data['name']);

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

    public function updatePost($postId) {
        try {
            $user = $this->authMiddleware->handle();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['title']) || !isset($data['content'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Title and content are required'
                ]);
                return;
            }

            $result = $this->postModel->updatePost($user['user_id'], $postId, $data);

            if ($result['success']) {
                http_response_code(200);
            } else {
                http_response_code($result['message'] === 'Unauthorized to update this post' ? 403 : 400);
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

    public function deletePost($postId) {
        try {
            $user = $this->authMiddleware->handle();
            
            $result = $this->postModel->deletePost($user['user_id'], $postId);

            if ($result['success']) {
                http_response_code(200);
            } else {
                http_response_code($result['message'] === 'Unauthorized to delete this post' ? 403 : 400);
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