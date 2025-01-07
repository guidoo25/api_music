<?php
namespace App\Services;

use App\Database\MySQLDatabase;

class LikeService {
    private $db;

    public function __construct(MySQLDatabase $db) {
        $this->db = $db;
    }

    public function createLike($userId, $postId = null, $commentId = null) {
        try {
            $id = $this->generateUUID();
            $query = "INSERT INTO likes (id, user_id, post_id, comment_id) VALUES (?, ?, ?, ?)";
            $params = [$id, $userId, $postId, $commentId];
            
            $result = $this->db->query($query, $params);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Like created successfully',
                    'like_id' => $id
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to create like'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function deleteLike($userId, $likeId) {
        try {
            $query = "DELETE FROM likes WHERE id = ? AND user_id = ?";
            $params = [$likeId, $userId];
            
            $result = $this->db->query($query, $params);
            
            if ($this->db->affectedRows() > 0) {
                return [
                    'success' => true,
                    'message' => 'Like removed successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to remove like or unauthorized'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function getLikes($postId = null, $commentId = null, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT l.*, u.username, u.avatar_url 
                     FROM likes l 
                     JOIN users u ON l.user_id = u.id 
                     WHERE " . ($postId ? "l.post_id = ?" : "l.comment_id = ?") . "
                     ORDER BY l.created_at DESC 
                     LIMIT ? OFFSET ?";
            
            $params = [$postId ?? $commentId, $limit, $offset];
            
            $result = $this->db->query($query, $params);
            $likes = [];
            
            while ($row = $this->db->fetchAssoc($result)) {
                $likes[] = $row;
            }
            
            return [
                'success' => true,
                'data' => $likes
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