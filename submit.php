<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

// Initialize PHP environment variables with PHP dotenv
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
    // If the form submission includes the "g-captcha-response" field
    // Create an instance of the ReCaptcha service with secret
    if (isset($_POST['g-recaptcha-response'])) {
        $recaptcha = new \ReCaptcha\ReCaptcha($_ENV['RECAPTCHA_SECRET']);

        // Capture IP address--this is passed on to Google ReCaptcha
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Make the call to verify the response and also pass the user's IP address
        $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $ip);

        if ($resp->isSuccess()) {

            // Initialize array of inputs.  We can add additional inputs as needed here
            $inputs = array(
                'email'     => isset($_POST['email']) ? $_POST['email'] : '',
                'name'      => isset($_POST['name']) ? $_POST['name'] : '',
                'subscribe' => isset($_POST['subscribe']) ? true : false //Boolean for checkbox
            );

            // Create a cryptographically secure unique token.
            // Using random_bytes(32) for 256 bits of entropy (64 hex characters)
            $token = bin2hex(random_bytes(32));

            // Sanitize input
            $sanitized_inputs = array();
            foreach ($inputs as $key => $value) {
                $sanitized_inputs[$key] = trim(stripslashes($value));
            }

            // Construct SQL Query
            $sql = "INSERT INTO users (token, ipaddress, email, name, subscribe) "
                  ."VALUES (:token, :ipaddress, :email, :name, :subscribe)";

            try {
              $stmt = $db->prepare($sql);
              $stmt->bindParam(':token', $token, PDO::PARAM_STR);
              $stmt->bindParam(':ipaddress', $ip, PDO::PARAM_STR);
              $stmt->bindParam(':email', $sanitized_inputs['email'], PDO::PARAM_STR);
              $stmt->bindParam(':name', $sanitized_inputs['name'], PDO::PARAM_STR);
              $stmt->bindParam(':subscribe', $sanitized_inputs['subscribe'], PDO::PARAM_BOOL);
              $stmt->execute();

              // Get the unique user ID of the user that has just registered
              $user_id = $db->lastInsertId();

              // Construct validation URL
              $url = $_ENV['URL']."/verify.php?t=$token&id=$user_id";

              $mail = new PHPMailer();

              // $mail->isSMTP();                                      // Set mailer to use SMTP
              $mail->Host = $_ENV['EMAIL_HOST'];                     // Specify main and backup SMTP servers
              if ($_ENV['SMTP_AUTH'] == 'true'):
                  $mail->SMTPAuth = true;                             // Enable SMTP authentication
                  $mail->SMTPSecure = $_ENV['SMTP_SECURE'];           // SMTP Encryption
                  $mail->Username = $_ENV['SMTP_USERNAME'];           // SMTP username
                  $mail->Password = $_ENV['SMTP_PASSWORD'];           // SMTP password
              endif;

              $mail->From = $_ENV['EMAIL_FROM'];
              $mail->FromName = $_ENV['EMAIL_FROM_NAME'];
              $mail->addAddress($sanitized_inputs['email']);        // Add a recipient

              $mail->WordWrap = 50;                                 // Set word wrap to 50 characters

              $mail->Subject = $_ENV['EMAIL_SUBJECT'];
              $mail->Body    = $_ENV['EMAIL_BODY'].' '.$url;

              if(!$mail->send()) {
                  echo 'Message could not be sent.';
                  echo 'Mailer Error: ' . htmlspecialchars($mail->ErrorInfo, ENT_QUOTES, 'UTF-8');
              } else {
                  echo 'Email has been sent; please validate your email before continuing';
              }
            }
            catch (PDOException $e) {
                echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }

        } else {
            // Gather errors
            foreach ($resp->getErrorCodes() as $code) {
                echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
            }
        }

    // If no ReCaptcha, render the form
    } else {
    ?>
        <form method="POST">
            <label for="name">Name:</label>
            <input id="name" name="name" type="text" required>
            <label for="email">Email:</label>
            <input id="email" name="email" type="email" required>
            <label>
                <input id="subscribe" name="subscribe" type="checkbox" required>
                Subscribe
            </label>
            <div class="g-recaptcha" data-sitekey="<?= $_ENV['RECAPTCHA_SITEKEY'] ?>"></div>
            <input type="submit">
        </form>
    <?php
    }
    ?>
    </body>
</html>
