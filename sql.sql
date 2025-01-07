-- Configuración inicial
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Users table (artistas y oyentes)
CREATE TABLE `users` (
    `id` VARCHAR(36) NOT NULL,
    `username` VARCHAR(255) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(255),
    `artist_name` VARCHAR(255),
    `bio` TEXT,
    `avatar_url` VARCHAR(512),
    `banner_url` VARCHAR(512),
    `website` VARCHAR(255),
    `paypal_email` VARCHAR(255),
    `stripe_account_id` VARCHAR(255),
    `instagram_id` VARCHAR(255),
    `verified_artist` BOOLEAN DEFAULT FALSE,
    `country` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE songs
ADD COLUMN genre VARCHAR(100) DEFAULT NULL AFTER title;

CREATE TABLE `topics` (
  `id` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `posts`
ADD COLUMN `topic_id` varchar(36) DEFAULT NULL,
ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`);
ALTER TABLE `posts`
ADD COLUMN `topic_id` varchar(36) DEFAULT NULL,
ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`);


-- Roles table
CREATE TABLE `roles` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User roles junction
CREATE TABLE `user_roles` (
    `user_id` VARCHAR(36) NOT NULL,
    `role_id` INT NOT NULL,
    PRIMARY KEY (`user_id`, `role_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Géneros musicales
CREATE TABLE `genres` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Albums
CREATE TABLE `albums` (
    `id` VARCHAR(36) NOT NULL,
    `artist_id` VARCHAR(36) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `cover_art_url` VARCHAR(512),
    `release_date` DATE,
    `type` ENUM('album', 'ep', 'single', 'demo') NOT NULL,
    `price` DECIMAL(10,2),
    `is_demo` BOOLEAN DEFAULT FALSE,
    `demo_duration` INT, -- duración en segundos para demos
    `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`artist_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Album géneros
CREATE TABLE `album_genres` (
    `album_id` VARCHAR(36) NOT NULL,
    `genre_id` INT NOT NULL,
    PRIMARY KEY (`album_id`, `genre_id`),
    FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Canciones
CREATE TABLE `songs` (
    `id` VARCHAR(36) NOT NULL,
    `album_id` VARCHAR(36),
    `artist_id` VARCHAR(36) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `duration` INT NOT NULL, -- duración en segundos
    `file_url` VARCHAR(512) NOT NULL,
    `demo_file_url` VARCHAR(512), -- URL para versión demo
    `cover_art_url` VARCHAR(512),
    `price` DECIMAL(10,2),
    `is_demo_available` BOOLEAN DEFAULT FALSE,
    `demo_duration` INT, -- duración en segundos para demos
    `lyrics` TEXT,
    `track_number` INT,
    `plays_count` INT DEFAULT 0,
    `downloads_count` INT DEFAULT 0,
    `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`artist_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Géneros de canciones
CREATE TABLE `song_genres` (
    `song_id` VARCHAR(36) NOT NULL,
    `genre_id` INT NOT NULL,
    PRIMARY KEY (`song_id`, `genre_id`),
    FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Licencias de música
CREATE TABLE `licenses` (
    `id` VARCHAR(36) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `rights_description` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ventas de canciones y licencias
CREATE TABLE `sales` (
    `id` VARCHAR(36) NOT NULL,
    `buyer_id` VARCHAR(36) NOT NULL,
    `song_id` VARCHAR(36),
    `album_id` VARCHAR(36),
    `license_id` VARCHAR(36),
    `amount` DECIMAL(10,2) NOT NULL,
    `commission_amount` DECIMAL(10,2) NOT NULL,
    `artist_amount` DECIMAL(10,2) NOT NULL,
    `transaction_id` VARCHAR(255),
    `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`license_id`) REFERENCES `licenses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Playlists
CREATE TABLE `playlists` (
    `id` VARCHAR(36) NOT NULL,
    `user_id` VARCHAR(36) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `cover_art_url` VARCHAR(512),
    `is_public` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Canciones en playlists
CREATE TABLE `playlist_songs` (
    `playlist_id` VARCHAR(36) NOT NULL,
    `song_id` VARCHAR(36) NOT NULL,
    `position` INT NOT NULL,
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`playlist_id`, `song_id`),
    FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentarios
CREATE TABLE `comments` (
    `id` VARCHAR(36) NOT NULL,
    `user_id` VARCHAR(36) NOT NULL,
    `song_id` VARCHAR(36),
    `album_id` VARCHAR(36),
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estadísticas de reproducción
CREATE TABLE `play_statistics` (
    `id` VARCHAR(36) NOT NULL,
    `song_id` VARCHAR(36) NOT NULL,
    `user_id` VARCHAR(36),
    `ip_address` VARCHAR(45),
    `country` VARCHAR(2),
    `device_type` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pagos a artistas
CREATE TABLE `artist_payments` (
    `id` VARCHAR(36) NOT NULL,
    `artist_id` VARCHAR(36) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `status` ENUM('pending', 'processed', 'paid') NOT NULL,
    `payment_method` VARCHAR(50),
    `transaction_id` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`artist_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notificaciones
CREATE TABLE `notifications` (
    `id` VARCHAR(36) NOT NULL,
    `user_id` VARCHAR(36) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reportes y análisis
CREATE TABLE `analytics` (
    `id` VARCHAR(36) NOT NULL,
    `entity_type` ENUM('song', 'album', 'artist', 'playlist') NOT NULL,
    `entity_id` VARCHAR(36) NOT NULL,
    `metric_type` VARCHAR(50) NOT NULL,
    `value` INT NOT NULL,
    `date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para optimización
CREATE INDEX `idx_songs_artist` ON `songs` (`artist_id`);
CREATE INDEX `idx_songs_album` ON `songs` (`album_id`);
CREATE INDEX `idx_sales_buyer` ON `sales` (`buyer_id`);
CREATE INDEX `idx_play_stats_song` ON `play_statistics` (`song_id`);
CREATE INDEX `idx_analytics_entity` ON `analytics` (`entity_type`, `entity_id`);
CREATE INDEX `idx_notifications_user` ON `notifications` (`user_id`);

-- Roles iniciales
INSERT INTO `roles` (`name`, `description`) VALUES
    ('admin', 'Administrador del sistema'),a
    ('artist', 'Músico o banda'),
    ('listener', 'Usuario regular'),
    ('moderator', 'Moderador de contenido');

-- Géneros iniciales
INSERT INTO `genres` (`name`, `description`) VALUES
    ('Rock', 'Género musical caracterizado por el uso de guitarras eléctricas'),
    ('Pop', 'Música popular contemporánea'),
    ('Hip Hop', 'Género musical que incorpora rap, DJing y producción de beats'),
    ('Jazz', 'Género musical caracterizado por la improvisación'),
    ('Electronic', 'Música producida principalmente con instrumentos electrónicos');

-- Licencias iniciales
INSERT INTO `licenses` (`id`, `name`, `description`, `price`, `rights_description`) VALUES
    (UUID(), 'Básica', 'Licencia para uso personal', 9.99, 'Solo uso personal, sin derechos comerciales'),
    (UUID(), 'Comercial', 'Licencia para uso comercial', 49.99, 'Uso comercial permitido, sin exclusividad'),
    (UUID(), 'Exclusiva', 'Licencia exclusiva', 499.99, 'Derechos exclusivos de uso comercial');

SET FOREIGN_KEY_CHECKS = 1;

ALTER TABLE `songs`
MODIFY `title` VARCHAR(255) NULL,
MODIFY `description` TEXT NULL,
ADD COLUMN `file_path` VARCHAR(512) NOT NULL AFTER `artist_id`,
MODIFY `status` ENUM('draft', 'pending_info', 'published', 'archived') DEFAULT 'pending_info';

ALTER TABLE songs
ADD COLUMN user_id VARCHAR(36) NOT NULL,
ADD CONSTRAINT fk_songs_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Remove the user_id column from the songs table as it's redundant with artist_id
ALTER TABLE songs DROP COLUMN user_id;

-- Modify the id column of the songs table to VARCHAR(36)
ALTER TABLE songs MODIFY COLUMN id VARCHAR(36) NOT NULL;

-- Add the upload_date column to the songs table
ALTER TABLE songs ADD COLUMN upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER file_path;

-- Ensure the file_path column exists and is named correctly
ALTER TABLE songs CHANGE COLUMN file_url file_path VARCHAR(512) NOT NULL;

-- Update the status enum to include 'pending_info'
ALTER TABLE songs MODIFY COLUMN status ENUM('draft', 'pending_info', 'published', 'archived') DEFAULT 'pending_info';

-- Make title and description nullable
ALTER TABLE songs MODIFY COLUMN title VARCHAR(255) NULL;
ALTER TABLE songs MODIFY COLUMN description TEXT NULL;