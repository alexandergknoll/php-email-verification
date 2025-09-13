<?php

$db = new PDO(
    'mysql:dbname='.$_ENV['DB_NAME']
        .';host='.$_ENV['DB_HOST']
        .';port=3306;charset=utf8',
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize with 'users' table if it doesn't already exist
$sql = "CREATE TABLE IF NOT EXISTS users ("
      ."id int NOT NULL AUTO_INCREMENT,"
      ."email varchar(255) NOT NULL,"
      ."validated bool NOT NULL DEFAULT 0,"
      ."token varchar(255) NOT NULL,"
      ."ipaddress varchar(255),"
      ."name varchar(255),"
      ."subscribe bool,"
      ."primary key (id)"
      .")";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute();
}
catch (PDOException $e) {
    echo $e->getMessage();
}

?>
