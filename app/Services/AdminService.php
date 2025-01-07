<?php
// app/Services/AdminService.php
namespace App\Services;

use App\Database\MySQLDatabase;

class AdminService
{
    private $db;

    public function __construct(MySQLDatabase $db)
    {
        $this->db = $db;
    }

    public function getDashboardStats()
    {
        $stats = [
            'total_users' => $this->getTotalUsers(),
            'total_songs' => $this->getTotalSongs(),
            'total_sales' => $this->getTotalSales()
        ];
        return $stats;
    }

    public function listUsers($filters) {
        try {
            $sql = "SELECT id, username, email, full_name, artist_name, bio, role, created_at, updated_at FROM users WHERE 1=1";
    
            $params = [];
    
            // Aplicar filtros a la consulta SQL
            if (isset($filters['role']) && $filters['role'] !== 'all') {
                $sql .= " AND role = ?";
                $params[] = $filters['role'];
            }
            if (isset($filters['search'])) {
                $sql .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
    
            $result = $this->db->query($sql, $params);
            $users = $this->db->fetchAll($result);
    
            return [
                'success' => true,
                'data' => $users
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al listar usuarios: ' . $e->getMessage()
            ];
        }
    }

    public function updateVerifiedArtistStatus($userId, $status) {
        try {
            $sql = "UPDATE users SET verified_artist = ? WHERE id = ?";
            $result = $this->db->query($sql, [$status, $userId]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User status updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No user found with the given ID'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function deleteUser($userId) {
        return $this->updateVerifiedArtistStatus($userId, 3);
    }

    private function getTotalUsers()
    {
        $sql = "SELECT COUNT(*) as count FROM users";
        $result = $this->db->query($sql);
        $row = $this->db->fetchAssoc($result);
        return $row['count'];
    }

    private function getTotalSongs()
    {
        $sql = "SELECT COUNT(*) as count FROM songs";
        $result = $this->db->query($sql);
        $row = $this->db->fetchAssoc($result);
        return $row['count'];
    }

    private function getTotalSales()
    {
        $sql = "SELECT SUM(amount) as total FROM sales";
        $result = $this->db->query($sql);
        $row = $this->db->fetchAssoc($result);
        return $row['total'] ?? 0;
    }
}