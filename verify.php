<?php
require __DIR__ . '/vendor/autoload.php';

// Initialize PHP environment variables
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// Initialize Database
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
    // Make sure that our query string parameters exist
    if (isset($_GET['t']) && isset($_GET['id'])) {
        $token = trim(stripslashes($_GET['t']));
        $id = trim(stripslashes($_GET['id']));

        // Construct SQL Query to locate user by ID and token match
        $sql = "SELECT * FROM users WHERE id = :id AND token = :token";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':token', $token);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                // If the email address has already been validated, don't proceed
                if ($result['validated']) {
                  echo "Email address has already been validated";
                } else {
                  // Construct SQL Query to set user's 'validated' field to true
                  // to prevent duplicate validation
                  $sql = "UPDATE users SET validated = 1 WHERE id = :id";
                  try {
                      $stmt = $db->prepare($sql);
                      $stmt->bindParam(':id', $id);
                      $stmt->execute();

                      echo "Email address successfully validated";

                      // Now, do stuff here...

                  }
                  catch (PDOException $e) {
                      echo $e->getMessage();
                  }
                }
            } else {
                echo "Something didn't match";
            }
        }
        catch (PDOException $e) {
            echo $e->getMessage();
        }

    }
    ?>
    </body>
</html>
