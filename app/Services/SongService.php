<?php
namespace App\Services;

use App\Database\MySQLDatabase;

class SongService {
    private $db;
    private $uploadDir;
    private $coverArtDir;
    private $baseUrl;

    public function __construct(MySQLDatabase $db) {
        $this->db = $db;
        $this->uploadDir = __DIR__ . '/../../public/uploads/';
        $this->coverArtDir = $this->uploadDir . 'covers/';
        $this->baseUrl = $this->getBaseUrl();
        
        // Add debug logging for directory creation
        error_log("Upload directory path: " . $this->uploadDir);
        error_log("Cover art directory path: " . $this->coverArtDir);
        
        // Create directories if they don't exist
        if (!file_exists($this->uploadDir)) {
            error_log("Creating upload directory...");
            if (!mkdir($this->uploadDir, 0777, true)) {
                error_log("Failed to create upload directory");
                throw new \Exception("No se pudo crear el directorio de carga");
            }
        }
        
        if (!file_exists($this->coverArtDir)) {
            error_log("Creating cover art directory...");
            if (!mkdir($this->coverArtDir, 0777, true)) {
                error_log("Failed to create cover art directory");
                throw new \Exception("No se pudo crear el directorio de portadas");
            }
        }
        
        // Verify directory permissions
        if (!is_writable($this->uploadDir)) {
            error_log("Upload directory is not writable");
            throw new \Exception("El directorio de carga no tiene permisos de escritura");
        }
        
        if (!is_writable($this->coverArtDir)) {
            error_log("Cover art directory is not writable");
            throw new \Exception("El directorio de portadas no tiene permisos de escritura");
        }
    }

    private function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        
        // Get the directory name where the script is located
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        
        // Remove any trailing slashes
        $baseDir = rtrim($scriptDir, '/');
        
        error_log("Base URL components - Protocol: $protocol, Host: $host, Base Dir: $baseDir");
        
        return $protocol . $host . $baseDir;
    }

    private function generateSecureFileName($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return bin2hex(random_bytes(16)) . '.' . $extension;
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
public function uploadSong($artistId, $songFile)
{
    try {
        error_log("Iniciando uploadSong para artistId: " . $artistId);
        
        if (!file_exists($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0777, true)) {
                throw new \Exception("No se pudo crear el directorio de carga");
            }
        }

        // Use the original file name
        $fileName = basename($songFile['name']);
        $filePath = $this->uploadDir . $fileName;

        // Move the uploaded file to the upload directory
        if (!move_uploaded_file($songFile['tmp_name'], $filePath)) {
            throw new \Exception("No se pudo mover el archivo subido");
        }

        $songId = $this->generateUUID();

        $query = "INSERT INTO songs (id, artist_id, file_path, status, upload_date, created_at, updated_at) VALUES (?, ?, ?, 'pending_info', NOW(), NOW(), NOW())";
        $params = [$songId, $artistId, $fileName];
        
        error_log("Executing query: " . $query);
        error_log("With params: " . json_encode($params));

        $result = $this->db->query($query, $params);

        if ($result === false) {
            $errorInfo = $this->db->error();
            error_log("SQL Error: " . print_r($errorInfo, true));
            // unlink(filename: $filePath);
            throw new \Exception("Error en la consulta SQL: " . json_encode($errorInfo));
        }

        error_log("Canción subida exitosamente. ID: " . $songId);
        return [
            'success' => true,
            'message' => 'Canción subida exitosamente',
            'song_id' => $songId
        ];
    } catch (\Exception $e) {
        error_log("Error en uploadSong: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al subir la canción: ' . $e->getMessage(),
            'song_id' => $songId ?? null
        ];
    }
}
        public function updateSongInfo($artistId, $data, $coverArtFile = null) {
        try {
            $coverArtUrl = null;
            
            if ($coverArtFile !== null) {
                error_log("Processing cover art file from binary data");
                $coverArtUrl = $this->handleBinaryImageUpload($coverArtFile);
            }
    
            $sql = "UPDATE songs SET 
                    title = ?, 
                    description = ?, 
                    genre = ?, 
                    status = 'published', 
                    updated_at = NOW()";
            
            $params = [$data['title'], $data['description'], $data['genre']];
    
            if ($coverArtUrl !== null) {
                $sql .= ", cover_art_url = ?";
                $params[] = $coverArtUrl;
                error_log("Adding cover art URL to database: " . $coverArtUrl);
            }
    
            $sql .= " WHERE id = ? AND artist_id = ?";
            $params[] = $data['song_id'];
            $params[] = $artistId;
    
            $result = $this->db->query($sql, $params);
    
            if ($this->db->affectedRows() === 0) {
                throw new \Exception('No se encontró la canción o no tienes permiso para actualizarla');
            }
    
            // if ($coverArtUrl !== null) {
            //     $this->deleteOldCoverArt($data['song_id']);
            // }
    
            $fullUrl = $coverArtUrl ? '/uploads/covers/' . $coverArtUrl : null;

            $updatecoverart = "UPDATE songs SET cover_art_url = ? WHERE id = ?";
            $params = [$coverArtUrl, $data['song_id']];
            $this->db->query($updatecoverart, $params);
    
            return [
                'success' => true,
                'message' => 'Información de la canción actualizada correctamente',
                'cover_art_url' => $coverArtUrl
            ];
    
        } catch (\Exception $e) {
            error_log("Error in updateSongInfo: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    
 
    private function handleBinaryImageUpload($binaryData) {
        try {
            error_log("Starting binary image upload process...");
            
            // Use fully qualified function name for GD functions
            $image = \imagecreatefromstring($binaryData);
            if ($image === false) {
                throw new \Exception("Datos de imagen inválidos");
            }

            // Generate unique filename
            $fileName = uniqid() . '.jpg';
            $filePath = "{$this->coverArtDir}{$fileName}";

            // Save as JPG with 90% quality using fully qualified function names
            if (!\imagejpeg($image, $filePath, 90)) {
                \imagedestroy($image);
                throw new \Exception("No se pudo guardar la imagen");
            }

            \imagedestroy($image);
            error_log("Image upload successful. File saved as: " . $fileName);
            return $fileName;

        } catch (\Exception $e) {
            error_log("Error in handleBinaryImageUpload: " . $e->getMessage());
            throw $e;
        }
    }



        public function updateSongInfoWithFile($artistId, $data, $coverArtFile = null) {
        try {
            $coverArtUrl = null;
            
            if ($coverArtFile !== null && $coverArtFile['error'] === UPLOAD_ERR_OK) {
                $coverArtUrl = $this->handleFileUpload($coverArtFile);
            }
    
            $sql = "UPDATE songs SET 
                    title = ?, 
                    description = ?, 
                    genre = ?, 
                    status = 'published', 
                    updated_at = NOW()";
            
            $params = [$data['title'], $data['description'], $data['genre']];
    
            if ($coverArtUrl !== null) {
                $sql .= ", cover_art_url = ?";
                $params[] = $coverArtUrl;
            }
    
            $sql .= " WHERE id = ? AND artist_id = ?";
            $params[] = $data['song_id'];
            $params[] = $artistId;
    
            $result = $this->db->query($sql, $params);
    
            if ($this->db->affectedRows() === 0) {
                throw new \Exception('No se encontró la canción o no tienes permiso para actualizarla');
            }
    
            if ($coverArtUrl !== null) {
                $this->deleteOldCoverArt($data['song_id']);
            }
    
            return [
                'success' => true,
                'message' => 'Información de la canción actualizada correctamente',
                'cover_art_url' => $coverArtUrl
            ];
    
        } catch (\Exception $e) {
            error_log("Error in updateSongInfoWithFile: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function handleFileUpload($file) {
        try {
            $allowedTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp', 'image/tiff'
            ];
            
            // Get the actual MIME type of the file
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            // Log the detected MIME type for debugging
            error_log("Detected MIME type: " . $mimeType);
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new \Exception("Tipo de archivo no permitido. Tipo detectado: " . $mimeType);
            }

            $fileName = uniqid() . '_' . basename($file['name']);
            $filePath = $this->coverArtDir . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new \Exception("Error al guardar el archivo");
            }

            return $fileName;
        } catch (\Exception $e) {
            error_log("Error in handleFileUpload: " . $e->getMessage());
            throw $e;
        }
    }

    private function handleImageUpload($imageFile) {
        try {
            error_log("Starting image upload process...");
            
            if (!isset($imageFile['tmp_name']) || !is_uploaded_file($imageFile['tmp_name'])) {
                error_log("Invalid image file upload");
                throw new \Exception("Archivo de imagen inválido");
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($imageFile['type'], $allowedTypes)) {
                error_log("Invalid file type: " . $imageFile['type']);
                throw new \Exception("Tipo de archivo no permitido. Use JPG, PNG o GIF");
            }

            $fileName = $this->generateSecureFileName($imageFile['name']);
            $filePath = $this->coverArtDir . $fileName;

            error_log("Attempting to move uploaded file to: " . $filePath);
            if (!move_uploaded_file($imageFile['tmp_name'], $filePath)) {
                error_log("Failed to move uploaded file. Upload error code: " . $imageFile['error']);
                throw new \Exception("No se pudo guardar la imagen");
            }

            error_log("Image upload successful. File saved as: " . $fileName);
            return $fileName;
        } catch (\Exception $e) {
            error_log("Error in handleImageUpload: " . $e->getMessage());
            throw $e;
        }
    }

    private function deleteOldCoverArt($songId) {
        try {
            $sql = "SELECT cover_art_url FROM songs WHERE id = ?";
            $result = $this->db->query($sql, [$songId]);
            $song = $this->db->fetchAssoc($result);

            if ($song && $song['cover_art_url']) {
                $oldFilePath = $this->coverArtDir . basename($song['cover_art_url']);
                error_log("Attempting to delete old cover art: " . $oldFilePath);
                
                if (file_exists($oldFilePath)) {
                    if (!unlink($oldFilePath)) {
                        error_log("Failed to delete old cover art file");
                    } else {
                        error_log("Successfully deleted old cover art file");
                    }
                } else {
                    error_log("Old cover art file not found: " . $oldFilePath);
                }
            }
        } catch (\Exception $e) {
            error_log("Error in deleteOldCoverArt: " . $e->getMessage());
        }
    }

    public function getCloudinaryUrl() {
        $sql = "SELECT url FROM grovehub.url_cloud LIMIT 1;";
        $result = $this->db->query($sql);
        $url = $result->fetch_assoc()['url'];
    
        return [
            'success' => true,
            'data' => $url
        ];
    }
    public function listSongs($page = 1, $limit = 10, $artistId = null) {
        try {
            $offset = ($page - 1) * $limit;
                $query = "SELECT s.*, 
                      u.username, 
                      u.banner_url, 
                      u.verified_artist,
                      u.avatar_url,
                      u.username
                      FROM songs s 
                      JOIN users u ON s.artist_id = u.id 
                      WHERE s.status = 'published'";
            $params = [];

            if ($artistId) {
                $query .= " AND s.artist_id = ?";
                $params[] = $artistId;
            }

            $query .= " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $result = $this->db->query($query, $params);
            $songs = [];
            
            while ($row = $this->db->fetchAssoc($result)) {
                $songs[] = $row;
            }
            
            // Get total songs count for pagination
            $countQuery = "SELECT COUNT(*) as total FROM songs WHERE status = 'published'";
            $countParams = [];
            if ($artistId) {
                $countQuery .= " AND artist_id = ?";
                $countParams[] = $artistId;
            }
            $countResult = $this->db->query($countQuery, $countParams);
            $totalSongs = $this->db->fetchAssoc($countResult)['total'];
            
            return [
                'success' => true,
                'data' => [
                    'songs' => $songs,
                    'total' => $totalSongs,
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_pages' => ceil($totalSongs / $limit)
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

public function getFilteredSongs($filter = 'all') {
    try {
        $query = "SELECT 
                s.title, 
                s.description, 
                s.duration, 
                s.file_path, 
                s.cover_art_url, 
                s.price, 
                s.lyrics, 
                s.plays_count, 
                s.downloads_count, 
                s.status, 
                s.upload_date,
                u.username, 
                u.banner_url, 
                u.verified_artist,
                u.avatar_url,
                u.artist_name
            FROM songs s 
            JOIN users u ON s.artist_id = u.id 
            WHERE s.status = 'published'";

    

        $query .= " ORDER BY 
                    u.verified_artist DESC,
                    s.plays_count DESC,
                    s.created_at DESC";

        $result = $this->db->query($query);
        $songs = $this->db->fetchAll($result);

        return [
            'success' => true,
            'data' => $songs
        ];

    } catch (\Exception $e) {
        error_log("Error in getFilteredSongs: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}





    public function filterSongs($filters) {
        try {
            $query = "SELECT s.*, u.artist_name, a.title as album_title 
                      FROM songs s 
                      JOIN users u ON s.artist_id = u.id 
                      LEFT JOIN albums a ON s.album_id = a.id 
                      WHERE 1=1";
            $params = [];

            if ($filters['album_id']) {
                $query .= " AND s.album_id = ?";
                $params[] = $filters['album_id'];
            }

            if ($filters['artist_id']) {
                $query .= " AND s.artist_id = ?";
                $params[] = $filters['artist_id'];
            }

            if ($filters['title']) {
                $query .= " AND s.title LIKE ?";
                $params[] = '%' . $filters['title'] . '%';
            }

            if ($filters['genre']) {
                $query .= " AND s.genre = ?";
                $params[] = $filters['genre'];
            }

            if ($filters['min_duration']) {
                $query .= " AND s.duration >= ?";
                $params[] = $filters['min_duration'];
            }

            if ($filters['max_duration']) {
                $query .= " AND s.duration <= ?";
                $params[] = $filters['max_duration'];
            }

            if ($filters['min_price']) {
                $query .= " AND s.price >= ?";
                $params[] = $filters['min_price'];
            }

            if ($filters['max_price']) {
                $query .= " AND s.price <= ?";
                $params[] = $filters['max_price'];
            }

            $query .= " AND s.status = ?";
            $params[] = $filters['status'];

            // Sorting
            $allowedSortFields = ['title', 'created_at', 'plays_count', 'downloads_count', 'price'];
            $sortBy = in_array($filters['sort_by'], $allowedSortFields) ? $filters['sort_by'] : 'created_at';
            $sortOrder = $filters['sort_order'] === 'ASC' ? 'ASC' : 'DESC';
            $query .= " ORDER BY s.$sortBy $sortOrder";

            // Pagination
            $offset = ($filters['page'] - 1) * $filters['limit'];
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $filters['limit'];
            $params[] = $offset;

            $result = $this->db->query($query, $params);
            $songs = [];
            
            while ($row = $this->db->fetchAssoc($result)) {
                $songs[] = $row;
            }
            
            // Get total songs count for pagination
            $countQuery = "SELECT COUNT(*) as total FROM songs s WHERE " . substr($query, strpos($query, 'WHERE') + 6, strpos($query, 'ORDER BY') - strpos($query, 'WHERE') - 6);
            $countParams = array_slice($params, 0, -2);
            $countResult = $this->db->query($countQuery, $countParams);
            $totalSongs = $this->db->fetchAssoc($countResult)['total'];
            
            return [
                'success' => true,
                'data' => [
                    'songs' => $songs,
                    'total' => $totalSongs,
                    'current_page' => $filters['page'],
                    'per_page' => $filters['limit'],
                    'total_pages' => ceil($totalSongs / $filters['limit'])
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    public function playSong($songId) {
        try {
            $query = "SELECT s.*, u.artist_name 
                      FROM songs s 
                      JOIN users u ON s.artist_id = u.id 
                      WHERE s.id = ? AND s.status = 'published'";
            
            $result = $this->db->query($query, [$songId]);
            $song = $this->db->fetchAssoc($result);
            
            if (!$song) {
                return [
                    'success' => false,
                    'message' => 'Canción no encontrada'
                ];
            }
            
            // Increment play count
            $this->db->query("UPDATE songs SET plays_count = plays_count + 1 WHERE id = ?", [$songId]);
            
            return [
                'success' => true,
                'data' => $song
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }







}

