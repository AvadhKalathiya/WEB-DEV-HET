<?php
/**
 * Practical 8: Database Connection Module (PDO & MySQLi Support)
 * Course: Web Development Frameworks (ITUE203) - CHARUSAT
 */

// Database Credentials
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'charusat_portal');
define('DB_USER', 'root');
define('DB_PASS', 'root'); // Standard local password configured in environment
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a robust PDO instance with exception handling and prepared statement emulations disabled.
 *
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}

/**
 * Returns a MySQLi connection instance for legacy/alternative MySQLi demonstrations.
 *
 * @return mysqli
 */
function getMySQLiConnection(): mysqli {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
    if ($mysqli->connect_error) {
        die("MySQLi connection failed: " . $mysqli->connect_error);
    }
    $mysqli->set_charset(DB_CHARSET);
    return $mysqli;
}
