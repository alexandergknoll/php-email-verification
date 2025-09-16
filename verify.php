<?php
/**
 * Email Verification Handler
 *
 * This script verifies user email addresses through a unique token system.
 * Users receive this URL via email after registration.
 *
 * Verification Process:
 * 1. Validates presence of token parameter
 * 2. Checks database for matching user record by token
 * 3. Ensures email hasn't already been verified
 * 4. Updates user's validation status
 *
 * Security Features:
 * - Uses only cryptographically secure token for verification
 * - Prevents duplicate verification
 * - Sanitizes all user inputs
 * - Uses parameterized queries
 *
 * URL Format: verify.php?t=TOKEN (no user ID needed)
 */

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/error_handler.php';

/**
 * Load environment configuration
 *
 * Required for database credentials
 */
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize Database connection
require __DIR__ . '/db.php';

?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body>
    <?php
    /**
     * Process email verification request
     */
    if (isset($_GET['t'])) {
        /**
         * Extract and sanitize verification token
         *
         * @var string $token The verification token (64 hex characters)
         */
        $token = trim(stripslashes($_GET['t']));

        /**
         * Query database for matching user by token only
         *
         * Since tokens are cryptographically secure and unique,
         * we don't need the user ID, preventing enumeration attacks
         */
        $sql = "SELECT * FROM users WHERE token = :token";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':token', $token);
            $stmt->execute();

            /**
             * Fetch user record if exists
             * @var array|false $result User data or false if not found
             */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                /**
                 * Check if email is already verified
                 *
                 * Prevents users from re-verifying and potentially
                 * triggering duplicate actions
                 */
                if ($result['validated']) {
                  echo "Email address has already been validated";
                } else {
                  /**
                   * Mark email as verified
                   *
                   * Updates the validated flag to prevent re-verification
                   * This is the point where you might trigger additional actions:
                   * - Send welcome email
                   * - Create user session
                   * - Redirect to login/dashboard
                   * - Trigger webhook notifications
                   */
                  $sql = "UPDATE users SET validated = 1 WHERE token = :token";
                  try {
                      $stmt = $db->prepare($sql);
                      $stmt->bindParam(':token', $token);
                      $stmt->execute();

                      echo "Email address successfully validated";

                      /**
                       * Post-verification actions can be added here
                       *
                       * Examples:
                       * - $_SESSION['verified_user'] = $id;
                       * - header('Location: /welcome.php');
                       * - sendWelcomeEmail($result['email']);
                       */

                  }
                  catch (PDOException $e) {
                      handleDatabaseError($e, 'verification');
                  }
                }
            } else {
                /**
                 * Verification failed
                 *
                 * Token doesn't exist or is invalid
                 * Generic message prevents information disclosure
                 */
                echo "Invalid or expired verification link";
            }
        }
        catch (PDOException $e) {
            handleDatabaseError($e, 'verification');
        }

    } else {
        /**
         * Missing required parameters
         *
         * User accessed verify.php without token parameter
         * This might happen if:
         * - User manually typed the URL
         * - Email client corrupted the link
         * - Link was partially copied
         */
        echo "Invalid verification link. Please check your email for the correct link.";
    }
    ?>
    </body>
</html>
