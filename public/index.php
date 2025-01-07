<?php

use App\Middleware\AuthMiddleware;
use App\Services\CommentService;
use App\Services\LikeService;
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Controllers\AuthController;
use App\Controllers\SongController;
use App\Controllers\AdminController;
use App\Controllers\CommentController;
use App\Controllers\LikeController;
use App\Controllers\PostController;
use App\Controllers\UserController;
use App\Database\MySQLDatabase;

use App\Services\AuthService;
use App\Services\SongService;
use App\Services\AdminService;
use App\Services\PostService;
use App\Services\UserService;

require_once __DIR__ . '/../SlimPHP.php';

$dbConfig = require __DIR__ . '/../config/database.php';

$app = new SlimPHP();

try {
    $db = new MySQLDatabase(
        $dbConfig['host'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['database']
    );

    $authService = new AuthService($db, $dbConfig['token']);
    
    $authController = new AuthController(authService: $authService);

    $authMiddleware = new AuthMiddleware($authService);    


    //init adminservices
    $adminService = new AdminService($db);
    $adminController = new AdminController($adminService, $authMiddleware);

    // Initialize services
    $songService = new SongService($db);
    $adminService = new AdminService($db);
    //forum
    $postService = new PostService($db);
    $postController = new PostController($postService, $authMiddleware);
    $commentModel = new CommentService($db);
    $likeModel = new LikeService($db);
    $commentController = new CommentController($commentModel, $authMiddleware);
    $likeController = new LikeController($likeModel, $authMiddleware);
    // Initialize controllers
    $songService = new SongService($db);
    $songController = new SongController($songService, $authMiddleware);
    //  $adminController = new AdminController($adminService);

    $userService = new UserService($db);
    $userController = new UserController($userService);

    //rutas de admin panel 
    $app->post('/api/admin/list', [$adminController, 'listUsers']);
    $app->post('/api/admin/delete', [$adminController, 'deleteUser']);
    // Rutas de autenticación
    $app->post('/api/login', [$authController, 'login']);
    $app->post('/api/register', [$authController, 'register']);
    $app->post('/api/social-register', [$authController, 'socialRegister']);
    $app->post('/api/auth/google', [$authController, 'googleLogin']);
//ccanciones
    $app->post('/api/songs/upload', [$songController, 'uploadSong']);
    $app->post('/api/songs/process-ai', [$songController, 'processAudioWithAI']);
    $app->get('/api/songs', [$songController, 'listSongs']);
    $app->post('/api/songs/count', [$songController, 'playSong']);
    $app->get('/api/songs/filter', [$songController, 'filterSongs']);
    $app->post('/api/songs/update-with-file', [$songController, 'updateSongInfoWithFile']);

    //forum 
    $app->post('/api/posts', [$postController, 'createPost']);
    $app->get('/api/posts', [$postController, 'getPosts']);
    $app->get('/api/foro/topics', [$postController, 'getTopics']);
    $app->post('/api/create/topic', [$postController, 'createTopic']);
    // Comment routes
    $app->post('/api/comments', [$commentController, 'createComment']);
    $app->post('/api/posts/comments', [$commentController, 'getComments']);
    $app->put('/api/comments/{commentId}', [$commentController, 'updateComment']);
    $app->delete('/api/comments/{commentId}', [$commentController, 'deleteComment']);
    // Like routes
    $app->post('/api/likes', [$likeController, 'createLike']);
    $app->delete('/api/likes/{likeId}', [$likeController, 'deleteLike']);
    $app->get('/api/posts/{postId}/likes', [$likeController, 'getLikes']);
    $app->get('/api/comments/{commentId}/likes', [$likeController, 'getLikes']);

    // Ruta para obtener información del usuario y sus canciones
    $app->post('/api/user/songs', [$userController, 'getUserWithSongs']);

    // $app->get('/apimusic/songs', function() use ($songController, $authMiddleware) {
    //     $authMiddleware->handle();
    //     $songController->listSongs();
    // });

    // Rutas de canciones
    $app->post('/apimusic/songs', [$songController, 'registerSong']);
    $app->get('/apimusic/songs', [$songController, 'listSongs']);
    // $app->get('/apimusic/songs/{id}', callback: [$songController, 'getSong']);
    // $app->post('/api_music/songs/upload', [$songController, 'uploadSong']);
    $app->get('/api/url', [$songController, 'geturlCloudinary']);
    $app->post('/api/songs/update', [$songController, 'updateSongInfo']);
    // Rutas de admin
    // $app->get('/apimusic/admin/dashboard', [$adminController, 'getDashboardStats']);
    // $app->get('/apimusic/admin/users', [$adminController, 'listUsers']);

    $app->run();
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}