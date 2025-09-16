<?php
/**
 * Email Verification Handler
 *
 * This script verifies user email addresses through a unique token system.
 * Users receive this URL via email after registration.
 *
 * Verification Process:
 * 1. Validates presence of token and ID parameters
 * 2. Checks database for matching user record
 * 3. Ensures email hasn't already been verified
 * 4. Updates user's validation status
 *
 * Security Features:
 * - Requires both token AND user ID for verification
 * - Prevents duplicate verification
 * - Sanitizes all user inputs
 * - Uses parameterized queries
 *
 * URL Format: verify.php?t=TOKEN&id=USER_ID
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
    if (isset($_GET['t']) && isset($_GET['id'])) {
        /**
         * Extract and sanitize verification parameters
         *
         * @var string $token The verification token (64 hex characters)
         * @var string $id The user ID from the database
         */
        $token = trim(stripslashes($_GET['t']));
        $id = trim(stripslashes($_GET['id']));

        /**
         * Query database for matching user
         *
         * Requires both correct token AND matching user ID
         * This dual requirement prevents token enumeration attacks
         */
        $sql = "SELECT * FROM users WHERE id = :id AND token = :token";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
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
                  $sql = "UPDATE users SET validated = 1 WHERE id = :id";
                  try {
                      $stmt = $db->prepare($sql);
                      $stmt->bindParam(':id', $id);
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
                 * Either token doesn't exist or ID doesn't match
                 * Generic message prevents information disclosure
                 */
                echo "Something didn't match";
            }
        }
        catch (PDOException $e) {
            handleDatabaseError($e, 'verification');
        }

    } else {
        /**
         * Missing required parameters
         *
         * User accessed verify.php without proper token/id parameters
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
