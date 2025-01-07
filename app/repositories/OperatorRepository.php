<?php
namespace App\Repositories;

class OperatorRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findByEmail($email)
    {
        $result = $this->db->query("SELECT * FROM teleoperadores WHERE email = ?", [$email]);
        return $this->db->fetchAssoc($result);
    }

    public function createOrUpdateSession($operatorId, $token, $expiresAt)
    {
        $sql = "INSERT INTO operator_sessions (operator_id, token, expires_at)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
        return $this->db->query($sql, [$operatorId, $token, $expiresAt]);
    }

    public function updateOperatorStatus($operatorId, $status, $lastLogin = null)
    {
        $sql = "UPDATE teleoperadores SET estado = ?";
        $params = [$status];

        if ($lastLogin) {
            $sql .= ", ultimo_login = ?";
            $params[] = $lastLogin;
        }

        $sql .= " WHERE id = ?";
        $params[] = $operatorId;

        return $this->db->query($sql, $params);
    }

    public function invalidateSession($operatorId)
    {
        return $this->db->query("DELETE FROM operator_sessions WHERE operator_id = ?", [$operatorId]);
    }

    public function storeDeviceToken($operatorId, $token, $platform)
    {
        $sql = "INSERT INTO device_tokens (user_id, token, platform)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = VALUES(token), platform = VALUES(platform)";
        return $this->db->query($sql, [$operatorId, $token, $platform]);
    }
}

