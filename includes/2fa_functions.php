<?php
/**
 * 2FA Helper Functions
 * Provides reusable functions for Two-Factor Authentication
 */

require_once 'vendor/autoload.php';

/**
 * Generate a secure remember token for 30-day 2FA bypass
 */
function generate_2fa_remember_token() {
    return bin2hex(random_bytes(32));
}

/**
 * Check if user has valid 2FA remember token
 */
function has_valid_2fa_remember($PDO, $user_id) {
    try {
        $stmt = $PDO->prepare("
            SELECT two_factor_remember_token, two_factor_remember_expires 
            FROM users 
            WHERE id = ? AND two_factor_remember_token IS NOT NULL
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || empty($user['two_factor_remember_token'])) {
            return false;
        }
        
        // Check if token has expired
        if (strtotime($user['two_factor_remember_expires']) < time()) {
            // Clear expired token
            clear_2fa_remember_token($PDO, $user_id);
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("2FA remember check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Set 2FA remember token for 30 days
 */
function set_2fa_remember_token($PDO, $user_id, $remember_days = 30) {
    try {
        $token = generate_2fa_remember_token();
        $expires = date('Y-m-d H:i:s', strtotime("+{$remember_days} days"));
        
        // Debug log
        error_log("Setting 2FA remember token for user $user_id, expires: $expires");
        
        $stmt = $PDO->prepare("
            UPDATE users 
            SET two_factor_remember_token = ?, two_factor_remember_expires = ?
            WHERE id = ?
        ");
        $stmt->execute([$token, $expires, $user_id]);
        
        // Set cookie for 30 days (expires in seconds)
        $cookieExpiry = time() + ($remember_days * 24 * 60 * 60);
        setcookie('2fa_remember', $token, $cookieExpiry, '/', '', true, true);
        
        // Also set in localStorage for JavaScript access
        echo "<script>localStorage.setItem('2fa_remember', '$token');</script>";
        
        error_log("2FA remember token set successfully for user $user_id");
        return true;
    } catch (Exception $e) {
        error_log("2FA remember token set error: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear 2FA remember token
 */
function clear_2fa_remember_token($PDO, $user_id) {
    try {
        $stmt = $PDO->prepare("
            UPDATE users 
            SET two_factor_remember_token = NULL, two_factor_remember_expires = NULL
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        
        // Clear cookie
        setcookie('2fa_remember', '', time() - 3600, '/', '', true, true);
        
        return true;
    } catch (Exception $e) {
        error_log("2FA remember token clear error: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate 2FA code
 */
function validate_2fa_code($secret, $code) {
    try {
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        return $google2fa->verifyKey($secret, $code);
    } catch (Exception $e) {
        error_log("2FA code validation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if 2FA is mandatory for a user (either user-enabled or admin-forced)
 */
function is_2fa_required($PDO, $user_id) {
    try {
        // Check system-wide 2FA mandate
        $stmt = $PDO->prepare("SELECT setting_value FROM system_settings WHERE setting_key = '2fa_mandatory'");
        $stmt->execute();
        $system_setting = $stmt->fetch();
        
        $system_mandatory = ($system_setting && $system_setting['setting_value'] == '1');
        
        // Check user-specific mandatory setting
        $stmt = $PDO->prepare("SELECT two_factor_enabled, two_factor_mandatory FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // 2FA is required if:
        // 1. User has enabled 2FA
        // 2. Admin has forced 2FA for this user
        // 3. System-wide 2FA is mandatory
        return ($user['two_factor_enabled'] == 1 || 
                $user['two_factor_mandatory'] == 1 || 
                $system_mandatory);
    } catch (Exception $e) {
        error_log("2FA requirement check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if 2FA needs renewal (after 30 days)
 */
function needs_2fa_renewal($PDO, $user_id) {
    try {
        $stmt = $PDO->prepare("SELECT two_factor_last_verified FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || empty($user['two_factor_last_verified'])) {
            // Never verified, needs verification
            return true;
        }
        
        // Check if 30 days have passed since last verification
        $last_verified = strtotime($user['two_factor_last_verified']);
        $days_since = (time() - $last_verified) / (24 * 60 * 60);
        
        return ($days_since > 30);
    } catch (Exception $e) {
        error_log("2FA renewal check error: " . $e->getMessage());
        return true;
    }
}

/**
 * Update 2FA last verified timestamp
 */
function update_2fa_verified_time($PDO, $user_id) {
    try {
        $stmt = $PDO->prepare("UPDATE users SET two_factor_last_verified = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$user_id]);
        return true;
    } catch (Exception $e) {
        error_log("2FA verified time update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Initialize system settings table for 2FA
 */
function init_2fa_system_settings($PDO) {
    try {
        // Create system_settings table if not exists
        $PDO->exec("CREATE TABLE IF NOT EXISTS system_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE NOT NULL,
            setting_value TEXT,
            description TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Add default 2FA settings
        $defaults = [
            ['2fa_mandatory', '0', 'Ob 2FA systemweit verpflichtend ist'],
            ['2fa_remember_days', '30', 'Tage die 2FA gespeichert bleiben soll'],
            ['2fa_renewal_days', '30', 'Tage bis 2FA erneuert werden muss']
        ];
        
        foreach ($defaults as $default) {
            $stmt = $PDO->prepare("INSERT OR IGNORE INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
            $stmt->execute($default);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("2FA system settings init error: " . $e->getMessage());
        return false;
    }
}

/**
 * Add required columns to users table
 */
function add_2fa_user_columns($PDO) {
    try {
        // Add new columns to users table
        $columns = [
            "ALTER TABLE users ADD COLUMN two_factor_remember_token TEXT",
            "ALTER TABLE users ADD COLUMN two_factor_remember_expires DATETIME",
            "ALTER TABLE users ADD COLUMN two_factor_mandatory INTEGER DEFAULT 0",
            "ALTER TABLE users ADD COLUMN two_factor_last_verified DATETIME"
        ];
        
        foreach ($columns as $column_sql) {
            try {
                $PDO->exec($column_sql);
            } catch (Exception $e) {
                // Column already exists, ignore
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("2FA user columns add error: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean up expired 2FA remember tokens (older than 30 days)
 * Call this during login to ensure expired tokens are removed
 */
function cleanup_expired_2fa_tokens($PDO) {
    try {
        // Delete tokens that have expired more than 30 days ago
        $stmt = $PDO->prepare("
            UPDATE users 
            SET two_factor_remember_token = NULL, 
                two_factor_remember_expires = NULL 
            WHERE two_factor_remember_expires IS NOT NULL 
            AND two_factor_remember_expires < datetime('now', '-30 days')
        ");
        $stmt->execute();
        
        $deleted = $stmt->rowCount();
        if ($deleted > 0) {
            error_log("Cleaned up $deleted expired 2FA remember tokens");
        }
        
        return $deleted;
    } catch (Exception $e) {
        error_log("2FA token cleanup error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Check and force 2FA renewal if last verification was more than 30 days ago
 * Returns true if renewal is needed
 */
function check_and_force_2fa_renewal($PDO, $user_id) {
    try {
        $stmt = $PDO->prepare("
            SELECT two_factor_last_verified 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return true; // User not found, require 2FA
        }
        
        // If never verified, require verification
        if (empty($user['two_factor_last_verified'])) {
            return true;
        }
        
        // Check if 30 days have passed
        $last_verified = strtotime($user['two_factor_last_verified']);
        $days_since = (time() - $last_verified) / (24 * 60 * 60);
        
        if ($days_since > 30) {
            // Clear any existing remember tokens to force renewal
            $update = $PDO->prepare("
                UPDATE users 
                SET two_factor_remember_token = NULL, 
                    two_factor_remember_expires = NULL 
                WHERE id = ?
            ");
            $update->execute([$user_id]);
            
            // Update last_verified to NULL to force complete re-verification
            $update2 = $PDO->prepare("UPDATE users SET two_factor_last_verified = NULL WHERE id = ?");
            $update2->execute([$user_id]);
            
            return true;
        }
        
        return false; // No renewal needed
    } catch (Exception $e) {
        error_log("2FA renewal check error: " . $e->getMessage());
        return true; // On error, require verification
    }
}

