```sql
-- Core User Management
CREATE TABLE users (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(50) UNIQUE NOT NULL,
	email VARCHAR(100) UNIQUE NOT NULL,
	password_hash VARCHAR(255) NOT NULL,
	user_type ENUM('Administrator', 'Venue Owner', 'Promoter', 'Venue Staff', 'End User') NOT NULL,
	status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'pending',
	email_verified BOOLEAN DEFAULT FALSE,
	two_factor_enabled BOOLEAN DEFAULT FALSE,
	last_login TIMESTAMP NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	INDEX idx_user_status (status),
	INDEX idx_user_type (user_type)
) ENGINE=InnoDB;

-- User Profile Data
CREATE TABLE user_profiles (
	user_id BIGINT PRIMARY KEY,
	first_name VARCHAR(50),
	last_name VARCHAR(50),
	phone_number VARCHAR(20),
	address TEXT,
	city VARCHAR(100),
	state VARCHAR(50),
	country VARCHAR(50),
	timezone VARCHAR(50),
	avatar_url VARCHAR(255),
	bio TEXT,
	preferences JSON,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Roles and Permissions
CREATE TABLE roles (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(50) UNIQUE NOT NULL,
	description TEXT,
	level INT NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	INDEX idx_role_level (level)
) ENGINE=InnoDB;

CREATE TABLE permissions (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100) UNIQUE NOT NULL,
	description TEXT,
	category VARCHAR(50) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_permission_category (category)
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
	role_id BIGINT NOT NULL,
	permission_id BIGINT NOT NULL,
	granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	granted_by BIGINT NOT NULL,
	PRIMARY KEY (role_id, permission_id),
	FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
	FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
	FOREIGN KEY (granted_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE user_roles (
	user_id BIGINT NOT NULL,
	role_id BIGINT NOT NULL,
	assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	assigned_by BIGINT NOT NULL,
	PRIMARY KEY (user_id, role_id),
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
	FOREIGN KEY (assigned_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Venues Management
CREATE TABLE venues (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100) NOT NULL,
	description TEXT,
	address TEXT NOT NULL,
	city VARCHAR(100) NOT NULL,
	state VARCHAR(50) NOT NULL,
	country VARCHAR(50) NOT NULL,
	capacity INT,
	contact_email VARCHAR(100),
	contact_phone VARCHAR(20),
	website_url VARCHAR(255),
	status ENUM('active', 'inactive', 'pending', 'suspended') DEFAULT 'pending',
	owner_id BIGINT NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (owner_id) REFERENCES users(id),
	INDEX idx_venue_status (status),
	INDEX idx_venue_location (city, state, country)
) ENGINE=InnoDB;

CREATE TABLE venue_staff (
	venue_id BIGINT NOT NULL,
	user_id BIGINT NOT NULL,
	role VARCHAR(50) NOT NULL,
	can_edit_events BOOLEAN DEFAULT FALSE,
	can_manage_staff BOOLEAN DEFAULT FALSE,
	assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	assigned_by BIGINT NOT NULL,
	PRIMARY KEY (venue_id, user_id),
	FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	FOREIGN KEY (assigned_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Events Management
CREATE TABLE events (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	title VARCHAR(255) NOT NULL,
	description TEXT,
	venue_id BIGINT NOT NULL,
	start_datetime DATETIME NOT NULL,
	end_datetime DATETIME NOT NULL,
	status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
	type VARCHAR(50) NOT NULL,
	age_limit INT,
	capacity INT,
	ticket_url VARCHAR(255),
	cover_charge DECIMAL(10,2),
	is_recurring BOOLEAN DEFAULT FALSE,
	recurrence_pattern JSON,
	created_by BIGINT NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (venue_id) REFERENCES venues(id),
	FOREIGN KEY (created_by) REFERENCES users(id),
	INDEX idx_event_dates (start_datetime, end_datetime),
	INDEX idx_event_status (status)
) ENGINE=InnoDB;

CREATE TABLE event_tags (
	event_id BIGINT NOT NULL,
	tag VARCHAR(50) NOT NULL,
	PRIMARY KEY (event_id, tag),
	FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
	INDEX idx_tag (tag)
) ENGINE=InnoDB;

CREATE TABLE event_attendees (
	event_id BIGINT NOT NULL,
	user_id BIGINT NOT NULL,
	status ENUM('interested', 'going', 'not_going') NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (event_id, user_id),
	FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Media Management
CREATE TABLE media (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	type ENUM('image', 'video', 'document') NOT NULL,
	file_path VARCHAR(255) NOT NULL,
	mime_type VARCHAR(100) NOT NULL,
	file_size INT NOT NULL,
	dimensions JSON,
	metadata JSON,
	uploader_id BIGINT NOT NULL,
	upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	status ENUM('pending', 'active', 'removed') DEFAULT 'pending',
	FOREIGN KEY (uploader_id) REFERENCES users(id),
	INDEX idx_media_type (type),
	INDEX idx_media_status (status)
) ENGINE=InnoDB;

CREATE TABLE media_relations (
	media_id BIGINT NOT NULL,
	entity_type ENUM('event', 'venue', 'user') NOT NULL,
	entity_id BIGINT NOT NULL,
	sort_order INT DEFAULT 0,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (media_id, entity_type, entity_id),
	FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE,
	INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB;

-- Security and Audit
CREATE TABLE security_logs (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	user_id BIGINT,
	action VARCHAR(100) NOT NULL,
	ip_address VARCHAR(45) NOT NULL,
	user_agent TEXT,
	details JSON,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (user_id) REFERENCES users(id),
	INDEX idx_security_action (action),
	INDEX idx_security_date (created_at)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	entity_type VARCHAR(50) NOT NULL,
	entity_id BIGINT NOT NULL,
	action VARCHAR(50) NOT NULL,
	old_values JSON,
	new_values JSON,
	performed_by BIGINT NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (performed_by) REFERENCES users(id),
	INDEX idx_audit_entity (entity_type, entity_id),
	INDEX idx_audit_date (created_at)
) ENGINE=InnoDB;