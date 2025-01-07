<?php
namespace App\Controllers;

use App\Services\SongService;
use App\Middleware\AuthMiddleware;

class SongController {
    private $songService;
    private $authMiddleware;

    public function __construct(SongService $songService, AuthMiddleware $authMiddleware) {
        $this->songService = $songService;
        $this->authMiddleware = $authMiddleware;
    }

    public function uploadSong() {
        try {
            $user = $this->authMiddleware->handle();
            
            if ($user['role'] !== 'artist') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Solo los artistas pueden subir canciones'
                ]);
                return;
            }

            if (!isset($_FILES['song_file'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Se requiere el archivo de audio'
                ]);
                return;
            }

            $songFile = $_FILES['song_file'];
            $result = $this->songService->uploadSong($user['user_id'], $songFile);

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
                'message' => 'Error al procesar la subida: ' . $e->getMessage()
            ]);
        }
    }
public function updateSongInfo() {
    try {
        $user = $this->authMiddleware->handle();
        
        if ($user['role'] !== 'artist') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Solo los artistas pueden actualizar información de canciones'
            ]);
            return;
        }

        // Get form data
        $data = [
            'song_id' => $_POST['song_id'] ?? null,
            'title' => $_POST['title'] ?? null,
            'description' => $_POST['description'] ?? null,
            'genre' => $_POST['genre'] ?? null
        ];

        if (!$data['song_id'] || !$data['title'] || !$data['genre']) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Se requiere song_id, title y genre'
            ]);
            return;
        }

        // Get binary image data
        $coverArtFile = null;
        if (isset($_FILES['cover_art']) && $_FILES['cover_art']['error'] === UPLOAD_ERR_OK) {
            $coverArtFile = file_get_contents($_FILES['cover_art']['tmp_name']);
        }

        $result = $this->songService->updateSongInfo($user['user_id'], $data, $coverArtFile);

        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);

    } catch (\Exception $e) {
        error_log("Error in updateSongInfo: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar la información: ' . $e->getMessage()
        ]);
    }
}

    public function geturlCloudinary(){
        try {
            $result = $this->songService->getCloudinaryurl();

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
                'message' => 'Error al obtener la url de cloudinary: ' . $e->getMessage()
            ]);
        }
    }
    public function listSongs() {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $artistId = isset($_GET['artist_id']) ? $_GET['artist_id'] : null;
            
            $result = $this->songService->listSongs($page, $limit, $artistId);

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
                'message' => 'Error al listar canciones: ' . $e->getMessage()
            ]);
        }
    }
    public function filterSongs() {
        try {
            $filters = [
                'album_id' => $_GET['album_id'] ?? null,
                'artist_id' => $_GET['artist_id'] ?? null,
                'title' => $_GET['title'] ?? null,
                'genre' => $_GET['genre'] ?? null,
                'min_duration' => isset($_GET['min_duration']) ? (int)$_GET['min_duration'] : null,
                'max_duration' => isset($_GET['max_duration']) ? (int)$_GET['max_duration'] : null,
                'min_price' => isset($_GET['min_price']) ? (float)$_GET['min_price'] : null,
                'max_price' => isset($_GET['max_price']) ? (float)$_GET['max_price'] : null,
                'status' => $_GET['status'] ?? 'published',
                'sort_by' => $_GET['sort_by'] ?? 'created_at',
                'sort_order' => $_GET['sort_order'] ?? 'DESC',
                'page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
                'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : 10
            ];

            $result = $this->songService->filterSongs($filters);

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
                'message' => 'Error al filtrar canciones: ' . $e->getMessage()
            ]);
        }
    }

        public function updateSongInfoWithFile() {
        try {
            $user = $this->authMiddleware->handle();
            
            if ($user['role'] !== 'artist') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Solo los artistas pueden actualizar información de canciones'
                ]);
                return;
            }
    
            $data = [
                'song_id' => $_POST['song_id'] ?? null,
                'title' => $_POST['title'] ?? null,
                'description' => $_POST['description'] ?? null,
                'genre' => $_POST['genre'] ?? null
            ];
    
            if (!$data['song_id'] || !$data['title'] || !$data['genre']) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Se requiere song_id, title y genre'
                ]);
                return;
            }
    
            $coverArtFile = isset($_FILES['cover_art']) ? $_FILES['cover_art'] : null;
    
            $result = $this->songService->updateSongInfoWithFile($user['user_id'], $data, $coverArtFile);
    
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
                'message' => 'Error al actualizar la información: ' . $e->getMessage()
            ]);
        }
    }

    public function playSong() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['songId'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'songId es requerido'
                ]);
                return;
            }
    
            $result = $this->songService->playSong($data['songId']);
    
            if ($result['success']) {
                http_response_code(200);
            } else {
                http_response_code(404);
            }
            
            echo json_encode($result);
    
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al reproducir la canción: ' . $e->getMessage()
            ]);
        }
    }

    

    // public function getSongSuggestions($songId) {
    //     try {
    //         $user = $this->authMiddleware->handle();
            
    //         if ($user['role'] !== 'artist') {
    //             http_response_code(403);
    //             echo json_encode([
    //                 'success' => false,
    //                 'message' => 'Solo los artistas pueden obtener sugerencias'
    //             ]);
    //             return;
    //         }

    //         $result = $this->songService->getSongSuggestions($user['user_id'], $songId);

    //         if ($result['success']) {
    //             http_response_code(200);
    //         } else {
    //             http_response_code(400);
    //         }
            
    //         echo json_encode($result);

    //     } catch (\Exception $e) {
    //         http_response_code(500);
    //         echo json_encode([
    //             'success' => false,
    //             'message' => 'Error al obtener sugerencias: ' . $e->getMessage()
    //         ]);
    //     }
    // }
    
    // public function processAudioWithAI() {
    //     try {
    //         $user = $this->authMiddleware->handle();
            
    //         if ($user['role'] !== 'artist') {
    //             http_response_code(403);
    //             echo json_encode([
    //                 'success' => false,
    //                 'message' => 'Solo los artistas pueden procesar canciones con IA'
    //             ]);
    //             return;
    //         }

    //         $data = json_decode(file_get_contents('php://input'), true);
            
    //         if (!isset($data['song_id'])) {
    //             http_response_code(400);
    //             echo json_encode([
    //                 'success' => false,
    //                 'message' => 'Se requiere song_id'
    //             ]);
    //             return;
    //         }

    //         $result = $this->songService->processAudioWithAI($data['song_id']);

    //         if ($result['success']) {
    //             http_response_code(200);
    //         } else {
    //             http_response_code(400);
    //         }
            
    //         echo json_encode($result);

    //     } catch (\Exception $e) {
    //         http_response_code(500);
    //         echo json_encode([
    //             'success' => false,
    //             'message' => 'Error al procesar el audio con IA: ' . $e->getMessage()
    //         ]);
    //     }
    // }
}

