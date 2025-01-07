<?php
// app/Services/AuthService.php
// app/Services/AuthService.php
namespace App\Services;

use App\Database\MySQLDatabase;

class AuthService {
    private $db;
    private $secretKey = ""; // En producción, esto debería estar en config

    public function __construct(MySQLDatabase $db) {
        $this->db = $db;
    }

    public function login($email, $password) {
        $sql = "SELECT * FROM users WHERE email = ? AND (verified_artist = 2 OR verified_artist = 0)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Contraseña incorrecta'
            ];
        }

        // Generar token JWT
        $token = $this->generateJWT([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'username' => $user['username'],
            'role' => $user['role']
        ]);

        return [
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ]
        ];
    }

    public function listUsers() {
        try {
            $sql = "SELECT id, username, email, full_name, artist_name, bio, role, created_at, updated_at FROM users";
            $result = $this->db->query($sql);
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

    private function generateJWT($payload) {
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);

        $payload['iat'] = time(); // Tiempo de emisión
        $payload['exp'] = time() + (60 * 60 * 24); // Expira en 24 horas
        $payload = json_encode($payload);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secretKey, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function verifyToken($token) {
        try {
            $tokenParts = explode('.', $token);
            if (count($tokenParts) != 3) {
                return false;
            }

            $header = base64_decode($tokenParts[0]);
            $payload = base64_decode($tokenParts[1]);
            $signatureProvided = $tokenParts[2];

            // Recrear firma
            $base64UrlHeader = $tokenParts[0];
            $base64UrlPayload = $tokenParts[1];
            $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secretKey, true);
            $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            if ($base64UrlSignature !== $signatureProvided) {
                return false;
            }

            $payload = json_decode($payload, true);
            
            // Verificar expiración
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }

            return $payload;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function register($userData) {
        // Validate input data
        $requiredFields = ['email', 'password', 'username', 'full_name', 'role'];
        foreach ($requiredFields as $field) {
            if (!isset($userData[$field]) || empty($userData[$field])) {
                return [
                    'success' => false,
                    'message' => "El campo '$field' es requerido"
                ];
            }
        }
    
        // Check if email already exists
        $checkEmailSql = "SELECT id FROM users WHERE email = ?";
        $result = $this->db->query($checkEmailSql, [$userData['email']]);
        if ($this->db->fetchAssoc($result)) {
            return [
                'success' => false,
                'message' => 'El email ya está registrado'
            ];
        }
    
        // Check if username already exists
        $checkUsernameSql = "SELECT id FROM users WHERE username = ?";
        $result = $this->db->query($checkUsernameSql, [$userData['username']]);
        if ($this->db->fetchAssoc($result)) {
            return [
                'success' => false,
                'message' => 'El nombre de usuario ya está en uso'
            ];
        }
    
        // Validate role
        $validRoles = ['admin', 'artist', 'listener', 'moderator'];
        if (!in_array($userData['role'], $validRoles)) {
            return [
                'success' => false,
                'message' => 'Rol inválido'
            ];
        }
    
        // Hash the password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
    
        // Generate UUID for user
        $userUuid = $this->generateUuid();
    
        try {
            // Insert new user
            $insertUserSql = "INSERT INTO users (id, email, username, password_hash, full_name, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $userParams = [
                $userUuid,
                $userData['email'],
                $userData['username'],
                $hashedPassword,
                $userData['full_name'],
                $userData['role']
            ];
            $this->db->query($insertUserSql, $userParams);
    
            // Generate JWT token
            $token = $this->generateJWT([
                'user_id' => $userUuid,
                'email' => $userData['email'],
                'username' => $userData['username'],
                'role' => $userData['role']
            ]);
    
            return [
                'success' => true,
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $userUuid,
                        'email' => $userData['email'],
                        'username' => $userData['username'],
                        'full_name' => $userData['full_name'],
                        'role' => $userData['role']
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al registrar el usuario: ' . $e->getMessage()
            ];
        }
    }
    
    

    private function generateUuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function socialRegister($provider, $token) {
        // Validate provider
        $validProviders = ['google', 'apple'];
        if (!in_array($provider, $validProviders)) {
            return [
                'success' => false,
                'message' => 'Proveedor inválido'
            ];
        }

        // Verify token with the provider
        $userData = $this->verifySocialToken($provider, $token);
        if (!$userData) {
            return [
                'success' => false,
                'message' => 'Token inválido o expirado'
            ];
        }

        // Check if email already exists
        $checkEmailSql = "SELECT id FROM users WHERE email = ?";
        $result = $this->db->query($checkEmailSql, [$userData['email']]);
        if ($this->db->fetchAssoc($result)) {
            return [
                'success' => false,
                'message' => 'El email ya está registrado'
            ];
        }

        // Generate UUID for user
        $userUuid = $this->generateUuid();

        try {
            // Insert new user
            $insertUserSql = "INSERT INTO users (id, email, username, full_name, role, created_at, updated_at) VALUES (?, ?, ?, ?, 'listener', NOW(), NOW())";
            $userParams = [
                $userUuid,
                $userData['email'],
                $userData['username'],
                $userData['full_name']
            ];
            $this->db->query($insertUserSql, $userParams);

            // Generate JWT token
            $token = $this->generateJWT([
                'user_id' => $userUuid,
                'email' => $userData['email'],
                'username' => $userData['username'],
                'role' => 'listener'
            ]);

            return [
                'success' => true,
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $userUuid,
                        'email' => $userData['email'],
                        'username' => $userData['username'],
                        'full_name' => $userData['full_name'],
                        'role' => 'listener'
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al registrar el usuario: ' . $e->getMessage()
            ];
        }
    }

    private function verifySocialToken($provider, $token) {
        // Implement token verification with the provider's API
        // This is a placeholder function and should be replaced with actual API calls
        if ($provider == 'google') {
            // Verify Google token
            // ... API call to Google ...
            return [
                'email' => 'user@example.com',
                'username' => 'google_user',
                'full_name' => 'Google User'
            ];
        } elseif ($provider == 'apple') {
            // Verify Apple token
            // ... API call to Apple ...
            return [
                'email' => 'user@example.com',
                'username' => 'apple_user',
                'full_name' => 'Apple User'
            ];
        }
        return false;
    }

    // public function googleLogin($token, $email, $name) {
    //     // Verify Google token
    //     $userData = $this->verifyGoogleToken($token);
    //     if (!$userData) {
    //         return [
    //             'success' => false,
    //             'message' => 'Token inválido o expirado'
    //         ];
    //     }

    //     // Check if email already exists
    //     $checkEmailSql = "SELECT * FROM users WHERE email = ?";
    //     $stmt = $this->db->prepare($checkEmailSql);
    //     $stmt->bind_param('s', $email);
    //     $stmt->execute();
    //     $result = $stmt->get_result();
    //     $user = $result->fetch_assoc();

    //     if (!$user) {
    //         // Register new user
    //         $userUuid = $this->generateUuid();
    //         $insertUserSql = "INSERT INTO users (id, email, username, full_name, role, created_at, updated_at) VALUES (?, ?, ?, ?, 'listener', NOW(), NOW())";
    //         $stmt = $this->db->prepare($insertUserSql);
    //         $stmt->bind_param('ssss', $userUuid, $email, $name, $name);
    //         $stmt->execute();

    //         $user = [
    //             'id' => $userUuid,
    //             'email' => $email,
    //             'username' => $name,
    //             'full_name' => $name,
    //             'role' => 'listener'
    //         ];
    //     }

    //     // Generate JWT token
    //     $token = $this->generateJWT([
    //         'user_id' => $user['id'],
    //         'email' => $user['email'],
    //         'username' => $user['username'],
    //         'role' => $user['role']
    //     ]);

    //     return [
    //         'success' => true,
    //         'data' => [
    //             'token' => $token,
    //             'user' => [
    //                 'id' => $user['id'],
    //                 'email' => $user['email'],
    //                 'username' => $user['username'],
    //                 'full_name' => $user['full_name'],
    //                 'role' => $user['role']
    //             ]
    //         ]
    //     ];
    // }

    // private function verifyGoogleToken($token) {
    //     $client = new \Google_Client(['client_id' => 'YOUR_GOOGLE_CLIENT_ID']);
    //     $payload = $client->verifyIdToken($token);
    //     if ($payload) {
    //         return $payload;
    //     } else {
    //         return false;
    //     }
    // }

    // ... existing methods ...
}
