<?php

/**
 * Security Headers Configuration
 *
 * Sets various HTTP security headers to protect against common web vulnerabilities.
 * Include this file at the beginning of any PHP script that outputs HTML.
 *
 * Headers Set:
 * - Content-Security-Policy (CSP): Prevents XSS attacks
 * - X-Frame-Options: Prevents clickjacking
 * - X-Content-Type-Options: Prevents MIME sniffing
 * - Referrer-Policy: Controls referrer information
 * - Permissions-Policy: Controls browser features
 * - X-XSS-Protection: Legacy XSS protection for older browsers
 * - Strict-Transport-Security: Forces HTTPS (when on HTTPS)
 */

/**
 * Set security headers
 *
 * Should be called before any output is sent to the browser.
 * Headers are only set if not already sent.
 *
 * @param array $options Optional configuration options
 */
function setSecurityHeaders($options = []) {
    // Don't set headers if already sent
    if (headers_sent()) {
        return;
    }

    // Default options
    $defaults = [
        'csp_report_only' => false,  // Set to true for testing CSP without blocking
        'allow_inline_scripts' => false,  // Set to true if you need inline scripts
        'allow_inline_styles' => true,  // Set to false for stricter CSP
        'frame_ancestors' => "'none'",  // Set to specific origins if framing is needed
        'enable_hsts' => true,  // Enable HSTS on HTTPS connections
    ];

    $config = array_merge($defaults, $options);

    /**
     * Content Security Policy (CSP)
     *
     * Prevents XSS attacks by controlling what resources can be loaded.
     * Adjust directives based on your application's needs.
     */
    $csp_directives = [
        "default-src 'self'",
        "script-src 'self' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/",
        "style-src 'self' " . ($config['allow_inline_styles'] ? "'unsafe-inline'" : ""),
        "img-src 'self' data: https:",
        "font-src 'self' data:",
        "connect-src 'self'",
        "frame-src https://www.google.com/recaptcha/ https://recaptcha.google.com/recaptcha/",
        "frame-ancestors " . $config['frame_ancestors'],
        "form-action 'self'",
        "base-uri 'self'",
        "object-src 'none'",
        "upgrade-insecure-requests"
    ];

    // Add nonce for inline scripts if needed
    if ($config['allow_inline_scripts']) {
        $nonce = base64_encode(random_bytes(16));
        $_SESSION['csp_nonce'] = $nonce;
        $csp_directives[1] .= " 'nonce-" . $nonce . "'";
    }

    $csp = implode('; ', array_filter($csp_directives));

    if ($config['csp_report_only']) {
        header("Content-Security-Policy-Report-Only: " . $csp);
    } else {
        header("Content-Security-Policy: " . $csp);
    }

    /**
     * X-Frame-Options
     *
     * Prevents clickjacking attacks by controlling if/how the page can be framed.
     * DENY: Page cannot be framed
     * SAMEORIGIN: Page can only be framed by same origin
     */
    header("X-Frame-Options: DENY");

    /**
     * X-Content-Type-Options
     *
     * Prevents MIME sniffing attacks by forcing browsers to respect Content-Type.
     */
    header("X-Content-Type-Options: nosniff");

    /**
     * Referrer-Policy
     *
     * Controls how much referrer information is sent with requests.
     * strict-origin-when-cross-origin: Full URL for same-origin, only origin for cross-origin
     */
    header("Referrer-Policy: strict-origin-when-cross-origin");

    /**
     * Permissions-Policy (formerly Feature-Policy)
     *
     * Controls which browser features can be used.
     * Disables potentially dangerous features by default.
     */
    $permissions = [
        "accelerometer=()",
        "camera=()",
        "geolocation=()",
        "gyroscope=()",
        "magnetometer=()",
        "microphone=()",
        "payment=()",
        "usb=()"
    ];
    header("Permissions-Policy: " . implode(', ', $permissions));

    /**
     * X-XSS-Protection
     *
     * Legacy header for older browsers. Modern browsers use CSP instead.
     * 1; mode=block: Enable XSS filter and block page if attack detected
     */
    header("X-XSS-Protection: 1; mode=block");

    /**
     * Strict-Transport-Security (HSTS)
     *
     * Forces HTTPS connections for the specified duration.
     * Only set on HTTPS connections to avoid issues.
     */
    if ($config['enable_hsts'] && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        // max-age=31536000 (1 year)
        // includeSubDomains: Apply to all subdomains
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }

    /**
     * Additional Security Measures
     */

    // Remove PHP version from headers
    header_remove("X-Powered-By");

    // Set secure session cookie parameters if session is active
    if (session_status() === PHP_SESSION_ACTIVE) {
        $currentParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $currentParams['lifetime'],
            'path' => $currentParams['path'],
            'domain' => $currentParams['domain'],
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
}

/**
 * Get CSP nonce for inline scripts
 *
 * Returns the CSP nonce if one was generated, for use in script tags.
 * Usage: <script nonce="<?= getCSPNonce() ?>">...</script>
 *
 * @return string The CSP nonce or empty string if not set
 */
function getCSPNonce() {
    return isset($_SESSION['csp_nonce']) ? $_SESSION['csp_nonce'] : '';
}

/**
 * Set cache control headers for static content
 *
 * Use for CSS, JS, images, etc. that don't change often.
 *
 * @param int $max_age Cache duration in seconds (default: 1 day)
 */
function setStaticCacheHeaders($max_age = 86400) {
    if (headers_sent()) {
        return;
    }

    header("Cache-Control: public, max-age=" . $max_age . ", immutable");
    header("Expires: " . gmdate('D, d M Y H:i:s', time() + $max_age) . ' GMT');
}

/**
 * Set cache prevention headers for dynamic content
 *
 * Use for pages with sensitive or frequently changing content.
 */
function setNoCacheHeaders() {
    if (headers_sent()) {
        return;
    }

    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
}

?>