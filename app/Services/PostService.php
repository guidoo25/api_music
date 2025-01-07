<?php
namespace App\Services;

use App\Database\MySQLDatabase;

class PostService {
    private $db;

    public function __construct(MySQLDatabase $db) {
        $this->db = $db;
    }

    public function createPost($userId, $data) {
        try {
            $id = $this->generateUUID();
            $query = "INSERT INTO posts (id, user_id, title, content, topic_id) VALUES (?, ?, ?, ?, ?)";
            $params = [$id, $userId, $data['title'], $data['content'], $data['topic_id'] ?? null];
            
            $result = $this->db->query($query, $params);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Post created successfully',
                    'post_id' => $id
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to create post'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function getTopics() {
        try {
            $query = "SELECT t.*, 
                      (SELECT COUNT(*) FROM posts WHERE topic_id = t.id AND status = 'active') as post_count 
                      FROM topics t";
            $result = $this->db->query($query);
            $topics = [];
            
            while ($row = $this->db->fetchAssoc($result)) {
                $topics[] = $row;
            }
            
            return [
                'success' => true,
                'data' => $topics
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    public function getPosts($page = 1, $limit = 10, $topicId = null) {
        try {
            $offset = ($page - 1) * $limit;
            $params = [];
            $topicCondition = "";
    
            if ($topicId) {
                $topicCondition = "AND p.topic_id = ?";
                $params[] = $topicId;
            }
            
            $params[] = $limit;
            $params[] = $offset;
            
            $query = "SELECT p.*, u.username, u.avatar_url, 
                     (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                     (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
                     FROM posts p 
                     JOIN users u ON p.user_id = u.id 
                     WHERE p.status = 'active' $topicCondition
                     ORDER BY p.created_at DESC 
                     LIMIT ? OFFSET ?";
            
            $result = $this->db->query($query, $params);
            $posts = [];
            
            while ($row = $this->db->fetchAssoc($result)) {
                $posts[] = $row;
            }
            
            // Get total posts count for pagination
            $countParams = $topicId ? [$topicId] : [];
            $countQuery = "SELECT COUNT(*) as total FROM posts p WHERE p.status = 'active' $topicCondition";
            $countResult = $this->db->query($countQuery, $countParams);
            $totalPosts = $this->db->fetchAssoc($countResult)['total'];
            
            return [
                'success' => true,
                'data' => [
                    'posts' => $posts,
                    'total' => $totalPosts,
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_pages' => ceil($totalPosts / $limit)
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function createTopics ($data) {
        try {
            $id = $this->generateUUID();
            $query = "INSERT INTO topics (id, name) VALUES (?, ?)";
            $params = [$id, $data['name']];
            
            $result = $this->db->query($query, $params);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Topic created successfully',
                    'topic_id' => $id
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to create topic'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    // public function getPost($postId) {
    //     try {
    //         $query = "SELECT p.*, u.username, u.avatar_url,
    //                  (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count
    //                  FROM posts p 
    //                  JOIN users u ON p.user_id = u.id 
    //                  WHERE p.id = ? AND p.status = 'active'";
            
    //         $result = $this->db->query($query, [$postId]);
    //         $post = $this->db->fetchAssoc($result);
            
    //         if (!$post) {
    //             return [
    //                 'success' => false,
    //                 'message' => 'Post not found'
    //             ];
    //         }
            
    //         return [
    //             'success' => true,
    //             'data' => $post
    //         ];
    //     } catch (\Exception $e) {
    //         return [
    //             'success' => false,
    //             'message' => 'Error: ' . $e->getMessage()
    //         ];
    //     }
    // }

    public function updatePost($userId, $postId, $data) {
        try {
            // Check if user owns the post
            $checkQuery = "SELECT user_id FROM posts WHERE id = ? AND status = 'active'";
            $checkResult = $this->db->query($checkQuery, [$postId]);
            $post = $this->db->fetchAssoc($checkResult);
            
            if (!$post || $post['user_id'] !== $userId) {
                return [
                    'success' => false,
                    'message' => 'Unauthorized to update this post'
                ];
            }
            
            $query = "UPDATE posts SET title = ?, content = ?, topic_id = ? WHERE id = ?";
            $params = [$data['title'], $data['content'], $data['topic_id'] ?? null, $postId];
            
            $result = $this->db->query($query, $params);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Post updated successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to update post'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function inactivepost ($postId) {
        try {
            $query = "UPDATE posts SET status = 'inactive' WHERE id = ?";
            $result = $this->db->query($query, [$postId]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Post inactive successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to inactive post'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function deletePost($userId, $postId) {
        try {
            // Check if user owns the post
            $checkQuery = "SELECT user_id FROM posts WHERE id = ? AND status = 'active'";
            $checkResult = $this->db->query($checkQuery, [$postId]);
            $post = $this->db->fetchAssoc($checkResult);
            
            if (!$post || $post['user_id'] !== $userId) {
                return [
                    'success' => false,
                    'message' => 'Unauthorized to delete this post'
                ];
            }
            
            $query = "UPDATE posts SET status = 'deleted' WHERE id = ?";
            $result = $this->db->query($query, [$postId]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Post deleted successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to delete post'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}