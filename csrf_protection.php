<?php

/**
 * CSRF Protection Utilities
 *
 * Provides functions for generating and validating CSRF tokens
 * to prevent Cross-Site Request Forgery attacks.
 *
 * Usage:
 * 1. Call generateCSRFToken() when displaying a form
 * 2. Include the token as a hidden field in the form
 * 3. Call validateCSRFToken() when processing form submission
 */

/**
 * Start session if not already started
 *
 * Ensures session is available for CSRF token storage
 * Uses secure session settings
 */
function ensureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configure secure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');

        // Enable secure cookie flag if using HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }

        session_start();
    }
}

/**
 * Generate a new CSRF token
 *
 * Creates a cryptographically secure token and stores it in the session.
 * Each token is unique per session and regenerated on each form display.
 *
 * @param string $form_name Optional form identifier for multiple forms
 * @return string The generated CSRF token
 */
function generateCSRFToken($form_name = 'default') {
    ensureSession();

    // Generate a new token using cryptographically secure random bytes
    $token = bin2hex(random_bytes(32));

    // Store token in session with form-specific key
    $_SESSION['csrf_tokens'][$form_name] = [
        'token' => $token,
        'time' => time()
    ];

    return $token;
}

/**
 * Validate a CSRF token
 *
 * Checks if the provided token matches the one stored in session.
 * Tokens expire after 1 hour for additional security.
 *
 * @param string $token The token to validate
 * @param string $form_name Optional form identifier
 * @param bool $remove_after_use Remove token after successful validation
 * @return bool True if token is valid, false otherwise
 */
function validateCSRFToken($token, $form_name = 'default', $remove_after_use = true) {
    ensureSession();

    // Check if token exists in session
    if (!isset($_SESSION['csrf_tokens'][$form_name])) {
        return false;
    }

    $stored_data = $_SESSION['csrf_tokens'][$form_name];

    // Check token expiration (1 hour)
    if (time() - $stored_data['time'] > 3600) {
        unset($_SESSION['csrf_tokens'][$form_name]);
        return false;
    }

    // Validate token using timing-safe comparison
    $is_valid = hash_equals($stored_data['token'], $token);

    // Remove token after successful validation to prevent reuse
    if ($is_valid && $remove_after_use) {
        unset($_SESSION['csrf_tokens'][$form_name]);
    }

    return $is_valid;
}

/**
 * Get CSRF token field HTML
 *
 * Returns a hidden input field with the CSRF token.
 * Convenience function for form generation.
 *
 * @param string $form_name Optional form identifier
 * @return string HTML hidden input field
 */
function getCSRFTokenField($form_name = 'default') {
    $token = generateCSRFToken($form_name);
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF token from POST request
 *
 * Convenience function to validate token from $_POST data.
 * Logs failures for security monitoring.
 *
 * @param string $form_name Optional form identifier
 * @return bool True if token is valid, false otherwise
 */
function verifyCSRFTokenFromPost($form_name = 'default') {
    if (!isset($_POST['csrf_token'])) {
        error_log(sprintf(
            "[%s] CSRF token missing from POST request - IP: %s, Form: %s",
            date('Y-m-d H:i:s'),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $form_name
        ));
        return false;
    }

    $is_valid = validateCSRFToken($_POST['csrf_token'], $form_name);

    if (!$is_valid) {
        error_log(sprintf(
            "[%s] CSRF token validation failed - IP: %s, Form: %s",
            date('Y-m-d H:i:s'),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $form_name
        ));
    }

    return $is_valid;
}

/**
 * Clean up expired CSRF tokens
 *
 * Removes all expired tokens from session.
 * Should be called periodically to prevent session bloat.
 */
function cleanupExpiredCSRFTokens() {
    ensureSession();

    if (!isset($_SESSION['csrf_tokens'])) {
        return;
    }

    $current_time = time();
    foreach ($_SESSION['csrf_tokens'] as $form_name => $data) {
        if ($current_time - $data['time'] > 3600) {
            unset($_SESSION['csrf_tokens'][$form_name]);
        }
    }
}

?>