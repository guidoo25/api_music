<?php
namespace App\Services;

use App\Database\MySQLDatabase;

class UserService {
    private $db;

    public function __construct(MySQLDatabase $db) {
        $this->db = $db;
    }

    public function getUserWithSongs($userId, $status) {
        try {
            // Obtener información del usuario
            $userQuery = "SELECT * FROM users WHERE id = ?";
            $userResult = $this->db->query($userQuery, [$userId]);
            $user = $this->db->fetchAssoc($userResult);
         
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ];
            }

            // Obtener canciones del usuario
            if ($status === 'all') {
                $songsQuery = "SELECT * FROM songs WHERE artist_id = ?";
                $songsResult = $this->db->query($songsQuery, [$userId]);
            } else {
                $songsQuery = "SELECT * FROM songs WHERE artist_id = ? and status = ?";
                $songsResult = $this->db->query($songsQuery, [$userId, $status]);
            }
            $songs = $this->db->fetchAll($songsResult);

            return [
                'success' => true,
                'data' => [
                    'user' => $user,
                    'songs' => $songs
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}
