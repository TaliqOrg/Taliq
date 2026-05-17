<?php
/**
 * Database Connection Bootstrap
 *
 * Establishes a PDO connection to the MySQL database using the credentials
 * defined in config.inc.php. Sets the error mode to exceptions for
 * consistent error handling. The resulting $pdo instance is available
 * globally to all scripts that include this file.
 *
 * @package    Taliq\Config
 * @subpackage Database
 * @version    1.0.0
 *
 * @global PDO $pdo The active database connection instance.
 */

require_once 'config.inc.php';

try {
    $pdo = new PDO(DBCONNSTRING, DBUSER, DBPASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
