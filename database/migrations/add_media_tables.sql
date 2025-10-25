-- Add media tables if they don't exist

CREATE TABLE IF NOT EXISTS media (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('image', 'video', 'document') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    dimensions JSON,
    metadata JSON,
    uploader_id BIGINT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'active', 'removed') DEFAULT 'active',
    INDEX idx_media_type (type),
    INDEX idx_media_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS media_relations (
    media_id BIGINT NOT NULL,
    entity_type ENUM('event', 'venue', 'user') NOT NULL,
    entity_id BIGINT NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (media_id, entity_type, entity_id),
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE,
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB;
