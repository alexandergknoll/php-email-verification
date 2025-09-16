<?php

/**
 * Error handling utilities
 */

/**
 * Log error details for debugging while showing generic message to user
 *
 * @param Exception $e The exception to handle
 * @param string $userMessage The message to display to the user
 * @param bool $return Whether to return the message instead of echoing
 * @return string|void
 */
function handleError($e, $userMessage = 'An error occurred. Please try again later.', $return = false) {
    // Log the actual error for debugging
    // In production, this should go to a proper logging system
    error_log(sprintf(
        "[%s] Error in %s:%d - %s\nTrace:\n%s\n",
        date('Y-m-d H:i:s'),
        $e->getFile(),
        $e->getLine(),
        $e->getMessage(),
        $e->getTraceAsString()
    ));

    // Show generic message to user
    $safeMessage = htmlspecialchars($userMessage, ENT_QUOTES, 'UTF-8');

    if ($return) {
        return $safeMessage;
    }

    echo $safeMessage;
}

/**
 * Check if we're in development mode
 *
 * @return bool
 */
function isDevelopment() {
    return isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development';
}

/**
 * Handle database errors
 *
 * @param PDOException $e
 * @param string $context Optional context for better error messages
 * @return void
 */
function handleDatabaseError($e, $context = '') {
    if (isDevelopment()) {
        // In development, show actual error
        echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    } else {
        // In production, show generic message and log the error
        $userMessage = 'A database error occurred. Please try again later.';
        if ($context === 'registration') {
            $userMessage = 'Registration failed. Please try again later.';
        } elseif ($context === 'verification') {
            $userMessage = 'Email verification failed. Please try again later.';
        }
        handleError($e, $userMessage);
    }
}

/**
 * Handle mail errors
 *
 * @param string $errorInfo The mail error information
 * @return void
 */
function handleMailError($errorInfo) {
    // Log the actual error
    error_log(sprintf(
        "[%s] Mail Error: %s\n",
        date('Y-m-d H:i:s'),
        $errorInfo
    ));

    if (isDevelopment()) {
        echo 'Mail Error: ' . htmlspecialchars($errorInfo, ENT_QUOTES, 'UTF-8');
    } else {
        echo 'Failed to send email. Please contact support if this issue persists.';
    }
}

?>