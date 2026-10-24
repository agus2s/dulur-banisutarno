-- © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
-- Blog Dulur — skema MySQL (alternatif import manual via phpMyAdmin)
CREATE DATABASE IF NOT EXISTS blog_dulur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE blog_dulur;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  display_name VARCHAR(100) NOT NULL DEFAULT '',
  email VARCHAR(160) NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  excerpt TEXT NULL,
  cover VARCHAR(500) NULL,
  content_md MEDIUMTEXT NOT NULL COMMENT 'Markdown mentah',
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  views INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FULLTEXT KEY ft_search (title, content_md)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  parent_id INT NULL COMMENT 'NULL = komentar utama, terisi = balasan',
  author VARCHAR(80) NOT NULL,
  email VARCHAR(160) NULL,
  content TEXT NOT NULL,
  status ENUM('pending','approved','spam') NOT NULL DEFAULT 'pending',
  ip_hash CHAR(64) NULL COMMENT 'SHA-256 IP+UA untuk anti-spam, tanpa simpan IP mentah',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_comments_post (post_id, status, created_at),
  KEY idx_comments_parent (parent_id),
  CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_likes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  visitor_hash CHAR(64) NOT NULL COMMENT 'SHA-256 fingerprint pengunjung, unik per artikel',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_like (post_id, visitor_hash),
  KEY idx_likes_post (post_id),
  CONSTRAINT fk_likes_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  content_md MEDIUMTEXT NOT NULL COMMENT 'Markdown mentah',
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FULLTEXT KEY ft_search (title, content_md)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  `k` VARCHAR(50) NOT NULL PRIMARY KEY,
  `v` TEXT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO settings (`k`, `v`) VALUES
('site_name', 'Blog Dulur'),
('site_desc', 'Blog sederhana untuk berbagi tulisan dan kabar'),
('footer_text', 'Blog Dulur • Berbagi tulisan dan kabar'),
('show_source', '1');

-- password = admin123 (hash bcrypt valid; install.php memakai password_hash saat runtime)
INSERT INTO users (username, password) VALUES
('admin', '$2y$12$AKRjntyDAurwQYF9i4Co7.f0lIliDurOiGWrcpRlagyHoMNmezcwO')
ON DUPLICATE KEY UPDATE username=VALUES(username);
