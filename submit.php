<?php
/**
 * User Registration and Email Verification Submission Handler
 *
 * This script handles user registration with email verification.
 * It includes ReCaptcha validation, email sending, and database storage.
 *
 * Features:
 * - ReCaptcha v2 integration for bot protection
 * - Email verification token generation and sending
 * - Newsletter subscription option
 * - IP address logging for security
 *
 * Required Environment Variables:
 * - Database: DB_HOST, DB_NAME, DB_USER, DB_PASS
 * - Application: URL (base URL for verification links)
 * - ReCaptcha: RECAPTCHA_SITEKEY, RECAPTCHA_SECRET
 * - Email: EMAIL_HOST, SMTP_AUTH, EMAIL_FROM, EMAIL_FROM_NAME, EMAIL_SUBJECT, EMAIL_BODY
 * - Optional SMTP: SMTP_SECURE, SMTP_USERNAME, SMTP_PASSWORD (when SMTP_AUTH=true)
 */

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/csrf_protection.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Load and validate environment variables
 *
 * Uses phpdotenv to load configuration from .env file
 * Validates that all required variables are present and non-empty
 */
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required([
    'DB_HOST',
    'DB_NAME',
    'DB_USER',
    'DB_PASS',
    'URL',
    'RECAPTCHA_SITEKEY',
    'RECAPTCHA_SECRET',
    'EMAIL_HOST',
    'SMTP_AUTH',
    'EMAIL_FROM',
    'EMAIL_FROM_NAME',
    'EMAIL_SUBJECT',
    'EMAIL_BODY'
])->notEmpty();
$dotenv->required('SMTP_AUTH')->allowedValues(['true', 'false']);

// Initialize Database
require __DIR__ . '/db.php';

?>
<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src='https://www.google.com/recaptcha/api.js'></script>
    </head>
    <body>
    <?php
    /**
     * Process form submission with ReCaptcha and CSRF validation
     */
    if (isset($_POST['g-recaptcha-response'])) {
        /**
         * Verify CSRF token first
         *
         * This prevents CSRF attacks before any processing occurs
         */
        if (!verifyCSRFTokenFromPost('registration')) {
            echo "Security validation failed. Please try again.";
            exit;
        }
        // Initialize ReCaptcha service
        $recaptcha = new \ReCaptcha\ReCaptcha($_ENV['RECAPTCHA_SECRET']);

        /**
         * Capture client IP address
         *
         * Priority order:
         * 1. HTTP_CLIENT_IP - Direct client IP
         * 2. HTTP_X_FORWARDED_FOR - IP behind proxy
         * 3. REMOTE_ADDR - Standard remote address
         */
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        /**
         * Verify ReCaptcha response
         */
        $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $ip);

        if ($resp->isSuccess()) {

            /**
             * Collect and process form inputs
             *
             * @var array $inputs User form data
             * - email: User's email address
             * - name: User's full name
             * - subscribe: Boolean for newsletter subscription
             */
            $inputs = array(
                'email'     => isset($_POST['email']) ? $_POST['email'] : '',
                'name'      => isset($_POST['name']) ? $_POST['name'] : '',
                'subscribe' => isset($_POST['subscribe']) ? true : false
            );

            /**
             * Generate secure verification token
             *
             * Uses random_bytes(32) for 256 bits of entropy
             * Results in 64 hexadecimal characters
             */
            $token = bin2hex(random_bytes(32));

            /**
             * Sanitize user inputs
             *
             * Removes whitespace and backslashes
             * Note: Consider additional validation for email format
             */
            $sanitized_inputs = array();
            foreach ($inputs as $key => $value) {
                $sanitized_inputs[$key] = trim(stripslashes($value));
            }

            /**
             * Store user registration in database
             */
            $sql = "INSERT INTO users (token, ipaddress, email, name, subscribe) "
                  ."VALUES (:token, :ipaddress, :email, :name, :subscribe)";

            try {
              // Prepare and execute parameterized query for security
              $stmt = $db->prepare($sql);
              $stmt->bindParam(':token', $token, PDO::PARAM_STR);
              $stmt->bindParam(':ipaddress', $ip, PDO::PARAM_STR);
              $stmt->bindParam(':email', $sanitized_inputs['email'], PDO::PARAM_STR);
              $stmt->bindParam(':name', $sanitized_inputs['name'], PDO::PARAM_STR);
              $stmt->bindParam(':subscribe', $sanitized_inputs['subscribe'], PDO::PARAM_BOOL);
              $stmt->execute();

              /**
               * Get the auto-generated user ID
               * @var int $user_id Newly created user's ID
               */
              $user_id = $db->lastInsertId();

              /**
               * Build verification URL
               *
               * Format: https://domain.com/verify.php?t=TOKEN&id=USER_ID
               * Both token and ID are required for verification
               */
              $url = $_ENV['URL']."/verify.php?t=$token&id=$user_id";

              /**
               * Configure and send verification email
               */
              $mail = new PHPMailer();

              /**
               * SMTP Configuration
               *
               * Note: isSMTP() is commented out - uncomment for proper SMTP sending
               * Currently may use PHP mail() function as fallback
               */
              // $mail->isSMTP();                                      // Set mailer to use SMTP
              $mail->Host = $_ENV['EMAIL_HOST'];                     // Specify main and backup SMTP servers

              /**
               * Configure SMTP authentication if enabled
               */
              if ($_ENV['SMTP_AUTH'] == 'true'):
                  $mail->SMTPAuth = true;                             // Enable SMTP authentication
                  $mail->SMTPSecure = $_ENV['SMTP_SECURE'];           // SMTP Encryption (tls/ssl)
                  $mail->Username = $_ENV['SMTP_USERNAME'];           // SMTP username
                  $mail->Password = $_ENV['SMTP_PASSWORD'];           // SMTP password
              endif;

              /**
               * Set email headers and content
               */
              $mail->From = $_ENV['EMAIL_FROM'];
              $mail->FromName = $_ENV['EMAIL_FROM_NAME'];
              $mail->addAddress($sanitized_inputs['email']);        // Add recipient

              $mail->WordWrap = 50;                                 // Set word wrap to 50 characters

              $mail->Subject = $_ENV['EMAIL_SUBJECT'];
              $mail->Body    = $_ENV['EMAIL_BODY'].' '.$url;       // Append verification URL to body

              if(!$mail->send()) {
                  handleMailError($mail->ErrorInfo);
              } else {
                  echo 'Email has been sent; please validate your email before continuing';
              }
            }
            catch (PDOException $e) {
                handleDatabaseError($e, 'registration');
            }

        } else {
            /**
             * Handle ReCaptcha validation failure
             *
             * Display error codes from ReCaptcha API
             * Common codes: missing-input-response, invalid-input-response
             */
            foreach ($resp->getErrorCodes() as $code) {
                echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
            }
        }

    /**
     * Display registration form
     *
     * Shown when no POST data is present (initial page load)
     * Includes ReCaptcha widget for bot protection
     */
    } else {
    ?>
        <form method="POST">
            <!-- CSRF Token for security -->
            <?= getCSRFTokenField('registration') ?>

            <label for="name">Name:</label>
            <input id="name" name="name" type="text" required>
            <label for="email">Email:</label>
            <input id="email" name="email" type="email" required>
            <label>
                <input id="subscribe" name="subscribe" type="checkbox" required>
                Subscribe
            </label>
            <!-- ReCaptcha v2 widget -->
            <div class="g-recaptcha" data-sitekey="<?= $_ENV['RECAPTCHA_SITEKEY'] ?>"></div>
            <input type="submit">
        </form>
    <?php
    }
    ?>
    </body>
</html>
