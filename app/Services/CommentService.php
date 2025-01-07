<?php
namespace App\Services;

use App\Database\MySQLDatabase;

class CommentService {
    private $db;

    public function __construct(MySQLDatabase $db) {
        $this->db = $db;
    }

    public function createComment($userId, $postId, $content) {
        try {
            $id = $this->generateUUID();
            $query = "INSERT INTO comments (id, user_id, post_id, content) VALUES (?, ?, ?, ?)";
            $params = [$id, $userId, $postId, $content];
            
            $result = $this->db->query($query, $params);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Comment created successfully',
                    'comment_id' => $id
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to create comment'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function getComments($postId, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "
            SELECT c.*, u.username, u.avatar_url, 
                   (SELECT COUNT(*) FROM likes WHERE comment_id = c.id) as likes_count
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            JOIN posts p ON c.post_id = p.id
            WHERE c.post_id = ? AND p.status = 'active'
            ORDER BY c.created_at DESC 
            LIMIT ? OFFSET ?
        ";
            
            $result = $this->db->query($query, [$postId, $limit, $offset]);
            $comments = [];
            
            while ($row = $this->db->fetchAssoc($result)) {
                $comments[] = $row;
            }
            
            return [
                'success' => true,
                'data' => $comments
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function updateComment($userId, $commentId, $content) {
        try {
            $query = "UPDATE comments SET content = ? WHERE id = ? AND user_id = ?";
            $params = [$content, $commentId, $userId];
            
            $result = $this->db->query($query, $params);
            
            if ($this->db->affectedRows() > 0) {
                return [
                    'success' => true,
                    'message' => 'Comment updated successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to update comment or unauthorized'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function deleteComment($userId, $commentId) {
        try {
            $query = "UPDATE comments SET status = 'deleted' WHERE id = ? AND user_id = ?";
            $params = [$commentId, $userId];
            
            $result = $this->db->query($query, $params);
            
            if ($this->db->affectedRows() > 0) {
                return [
                    'success' => true,
                    'message' => 'Comment deleted successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to delete comment or unauthorized'
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