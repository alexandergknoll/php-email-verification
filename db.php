<?php

/**
 * Database Connection and Initialization
 *
 * This file establishes the database connection and ensures the required
 * database schema exists. It's included by other files that need database access.
 *
 * Required Environment Variables:
 * - DB_NAME: Database name
 * - DB_HOST: Database host
 * - DB_USER: Database username
 * - DB_PASS: Database password
 */

require_once __DIR__ . '/error_handler.php';

/**
 * Initialize PDO database connection
 *
 * Configuration:
 * - Uses MySQL driver with UTF-8 charset
 * - Disables emulated prepares for better security
 * - Sets error mode to exceptions for proper error handling
 */
$db = new PDO(
    'mysql:dbname='.$_ENV['DB_NAME']
        .';host='.$_ENV['DB_HOST']
        .';port=3306;charset=utf8',
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/**
 * Create users table if it doesn't exist
 *
 * Table Schema:
 * - id: Auto-incrementing primary key (internal use only)
 * - email: User's email address
 * - validated: Boolean flag for email verification status (default: 0)
 * - token: Unique verification token for email validation
 * - ipaddress: IP address of the user during registration
 * - name: User's name
 * - subscribe: Newsletter subscription preference
 */
$sql = "CREATE TABLE IF NOT EXISTS users ("
      ."id int NOT NULL AUTO_INCREMENT,"
      ."email varchar(255) NOT NULL,"
      ."validated bool NOT NULL DEFAULT 0,"
      ."token varchar(255) NOT NULL UNIQUE,"
      ."ipaddress varchar(255),"
      ."name varchar(255),"
      ."subscribe bool,"
      ."primary key (id),"
      ."UNIQUE KEY token_unique (token)"
      .")";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute();
}
catch (PDOException $e) {
    handleDatabaseError($e, 'initialization');
}

?>
