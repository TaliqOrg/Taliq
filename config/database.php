<?php
/*
 * Task 1:  Database Design     | Author: Abdullah Al Tamh     | Ticket: #30
 * Task 16: Efficiency          | Author: Moamen Rabah         | Ticket: #30
 */
require_once 'config.inc.php';

try {
    $pdo = new PDO(DBCONNSTRING, DBUSER, DBPASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
