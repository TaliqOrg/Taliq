<?php
/**
 * Database Configuration
 *
 * Defines the database connection constants used throughout the application.
 * These values are consumed by the database.php bootstrap to establish a
 * PDO connection to the MySQL server.
 *
 * @package    Taliq\Config
 * @subpackage Database
 * @version    1.0.0
 */

define('DBHOST', 'localhost');
define('DBNAME', 'taleeq_db');
define('DBUSER', 'root');
define('DBPASS', '');
define('DBCHARSET', 'utf8mb4');
define('DBCONNSTRING', "mysql:host=" . DBHOST . ";dbname=" . DBNAME . ";charset=" . DBCHARSET);
