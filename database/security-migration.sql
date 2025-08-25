-- Security Enhancement Migration
-- Add security-related columns and tables

-- Add security columns to users table
ALTER TABLE `users` 
ADD COLUMN `failed_login_attempts` INT DEFAULT 0 AFTER `status`,
ADD COLUMN `locked_until` DATETIME NULL AFTER `failed_login_attempts`,
ADD COLUMN `last_login` DATETIME NULL AFTER `locked_until`,
ADD COLUMN `password_changed_at` DATETIME NULL AFTER `last_login`;

-- Create activity_logs table if it doesn't exist
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create security_events table for tracking security incidents
CREATE TABLE IF NOT EXISTS `security_events` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `request_uri` varchar(500) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `event_data` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create system_settings table for application configuration
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json','encrypted') DEFAULT 'string',
  `category` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting_key` (`setting_key`),
  KEY `idx_category` (`category`),
  KEY `idx_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default system settings
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_public`) VALUES
('app_name', 'School CRM', 'string', 'general', 'Application name', 1),
('app_version', '1.2.0', 'string', 'general', 'Application version', 1),
('maintenance_mode', '0', 'boolean', 'system', 'Maintenance mode status', 0),
('max_login_attempts', '5', 'integer', 'security', 'Maximum login attempts before lockout', 0),
('login_lockout_duration', '1800', 'integer', 'security', 'Lockout duration in seconds', 0),
('session_timeout', '86400', 'integer', 'security', 'Session timeout in seconds', 0),
('enable_2fa', '0', 'boolean', 'security', 'Enable two-factor authentication', 0),
('smtp_host', '', 'string', 'email', 'SMTP server host', 0),
('smtp_port', '587', 'integer', 'email', 'SMTP server port', 0),
('smtp_username', '', 'encrypted', 'email', 'SMTP username', 0),
('smtp_password', '', 'encrypted', 'email', 'SMTP password', 0),
('backup_frequency', 'daily', 'string', 'backup', 'Backup frequency (daily, weekly, monthly)', 0),
('backup_retention_days', '30', 'integer', 'backup', 'Number of days to keep backups', 0);

-- Create file_uploads table to track uploaded files
CREATE TABLE IF NOT EXISTS `file_uploads` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) UNSIGNED NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_hash` varchar(64) NOT NULL,
  `uploaded_by` int(11) UNSIGNED NOT NULL,
  `related_table` varchar(100) DEFAULT NULL,
  `related_id` int(11) UNSIGNED DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_related` (`related_table`, `related_id`),
  KEY `idx_file_hash` (`file_hash`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create backup_logs table to track backup operations
CREATE TABLE IF NOT EXISTS `backup_logs` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `backup_type` enum('manual','scheduled','system') DEFAULT 'manual',
  `backup_name` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `tables_included` text DEFAULT NULL,
  `status` enum('started','completed','failed') DEFAULT 'started',
  `error_message` text DEFAULT NULL,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_backup_type` (`backup_type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_created_by` (`created_by`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create notification_templates table
CREATE TABLE IF NOT EXISTS `notification_templates` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_name` varchar(100) NOT NULL UNIQUE,
  `template_type` enum('email','sms','in_app','push') DEFAULT 'email',
  `subject` varchar(255) DEFAULT NULL,
  `body_template` text NOT NULL,
  `variables` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_template_name` (`template_name`),
  KEY `idx_template_type` (`template_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default notification templates
INSERT IGNORE INTO `notification_templates` (`template_name`, `template_type`, `subject`, `body_template`, `variables`) VALUES
('security_alert', 'email', 'Security Alert - {{app_name}}', 
 'Dear {{user_name}},\n\nA security event has been detected on your account:\n\nEvent: {{event_type}}\nTime: {{event_time}}\nIP Address: {{ip_address}}\n\nIf this was not you, please contact support immediately.\n\nBest regards,\n{{app_name}} Security Team',
 '["app_name", "user_name", "event_type", "event_time", "ip_address"]'),
('login_notification', 'email', 'Login Notification - {{app_name}}',
 'Dear {{user_name}},\n\nYou have successfully logged into your account.\n\nTime: {{login_time}}\nIP Address: {{ip_address}}\nDevice: {{user_agent}}\n\nIf this was not you, please contact support immediately.\n\nBest regards,\n{{app_name}} Team',
 '["app_name", "user_name", "login_time", "ip_address", "user_agent"]');

-- Add indexes for better performance
ALTER TABLE `users` ADD INDEX `idx_email_status` (`email`, `status`);
ALTER TABLE `users` ADD INDEX `idx_failed_attempts` (`failed_login_attempts`);
ALTER TABLE `users` ADD INDEX `idx_locked_until` (`locked_until`);

-- Add indexes to existing tables if they don't exist
ALTER TABLE `fee_payments` ADD INDEX IF NOT EXISTS `idx_payment_date` (`payment_date`);
ALTER TABLE `fee_payments` ADD INDEX IF NOT EXISTS `idx_status` (`status`);
ALTER TABLE `invoices` ADD INDEX IF NOT EXISTS `idx_status_due_date` (`status`, `due_date`);
ALTER TABLE `students` ADD INDEX IF NOT EXISTS `idx_admission_number` (`admission_number`);

-- Update existing users table to add password_changed_at for existing records
UPDATE `users` SET `password_changed_at` = `created_at` WHERE `password_changed_at` IS NULL;

-- Create a view for user sessions (for monitoring active sessions)
CREATE OR REPLACE VIEW `active_sessions` AS
SELECT 
    al.user_id,
    u.name,
    u.email,
    u.role,
    MAX(al.created_at) as last_activity,
    al.ip_address,
    COUNT(*) as activity_count
FROM activity_logs al
JOIN users u ON al.user_id = u.id
WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
AND al.action IN ('login', 'page_view', 'action')
GROUP BY al.user_id, al.ip_address
ORDER BY last_activity DESC;

-- Create triggers for security auditing
DELIMITER $$

-- Trigger for users table changes
CREATE TRIGGER IF NOT EXISTS `users_audit_trigger` 
AFTER UPDATE ON `users` 
FOR EACH ROW 
BEGIN
    IF OLD.password != NEW.password THEN
        INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
        VALUES (NEW.id, 'password_changed', 'User password was changed', @user_ip, NOW());
    END IF;
    
    IF OLD.role != NEW.role THEN
        INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
        VALUES (NEW.id, 'role_changed', CONCAT('User role changed from ', OLD.role, ' to ', NEW.role), @user_ip, NOW());
    END IF;
    
    IF OLD.status != NEW.status THEN
        INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
        VALUES (NEW.id, 'status_changed', CONCAT('User status changed from ', OLD.status, ' to ', NEW.status), @user_ip, NOW());
    END IF;
END$$

DELIMITER ;

-- Create stored procedures for common security operations

DELIMITER $$

-- Procedure to clean old logs
CREATE PROCEDURE IF NOT EXISTS `CleanOldLogs`(IN days_to_keep INT)
BEGIN
    DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    DELETE FROM security_events WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
END$$

-- Procedure to get security metrics
CREATE PROCEDURE IF NOT EXISTS `GetSecurityMetrics`(IN days_back INT)
BEGIN
    SELECT 
        'Failed Login Attempts' as metric,
        COUNT(*) as value,
        'last_' + CAST(days_back as CHAR) + '_days' as period
    FROM activity_logs 
    WHERE action = 'failed_login' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL days_back DAY)
    
    UNION ALL
    
    SELECT 
        'Security Events' as metric,
        COUNT(*) as value,
        'last_' + CAST(days_back as CHAR) + '_days' as period
    FROM security_events 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL days_back DAY)
    
    UNION ALL
    
    SELECT 
        'Unique Users Active' as metric,
        COUNT(DISTINCT user_id) as value,
        'last_' + CAST(days_back as CHAR) + '_days' as period
    FROM activity_logs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL days_back DAY);
END$$

DELIMITER ;

-- Grant necessary permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON school_crm.* TO 'crm_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE school_crm.CleanOldLogs TO 'crm_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE school_crm.GetSecurityMetrics TO 'crm_user'@'localhost';

-- Add comments to tables for documentation
ALTER TABLE `users` COMMENT = 'System users with enhanced security features';
ALTER TABLE `activity_logs` COMMENT = 'User activity tracking for audit purposes';
ALTER TABLE `security_events` COMMENT = 'Security incidents and events log';
ALTER TABLE `system_settings` COMMENT = 'Application configuration settings';
ALTER TABLE `file_uploads` COMMENT = 'File upload tracking and management';
ALTER TABLE `backup_logs` COMMENT = 'Database backup operation logs';
ALTER TABLE `notification_templates` COMMENT = 'Email and notification templates';

-- Migration completed successfully
-- Next steps:
-- 1. Run this migration on your database
-- 2. Test the new security features
-- 3. Configure notification templates as needed
-- 4. Set up automated backup schedule
-- 5. Monitor security logs regularly