-- Migration: Create upload_sessions table for enhanced chunked uploads
-- This table manages upload sessions for large file chunked uploads

CREATE TABLE upload_sessions (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filesize BIGINT NOT NULL,
    mimetype VARCHAR(100) NULL,
    parent_id VARCHAR(36) NULL,
    path VARCHAR(1000) NULL,
    chunks_uploaded INTEGER DEFAULT 0,
    total_chunks INTEGER DEFAULT 0,
    expires_at TIMESTAMP NOT NULL,
    status ENUM('initialized', 'uploading', 'ready_for_merge', 'completed', 'failed', 'cancelled') DEFAULT 'initialized',
    file_id VARCHAR(36) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at),
    INDEX idx_file_id (file_id)
);

-- Add foreign key constraints if needed
-- ALTER TABLE upload_sessions ADD CONSTRAINT fk_upload_sessions_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
-- ALTER TABLE upload_sessions ADD CONSTRAINT fk_upload_sessions_file_id FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE SET NULL;

-- Cleanup query for expired sessions (can be run as a scheduled job)
-- DELETE FROM upload_sessions WHERE expires_at < NOW() AND status NOT IN ('completed');