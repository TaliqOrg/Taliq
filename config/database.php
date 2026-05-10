<?php
class Database {
     private $host = "localhost";
     private $user = "root";
     private $pass = "";
     private $dbname = "taleeq_db";
     private $conn;
     public function getConnection() {
          try {
               $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbname, $this->user, $this->pass);
               $this->conn->exec("set names utf8mb4");
          }catch(PDOException $e) {
               echo "Error: " . $e->getMessage();
          }

          return $this->conn;
     }
}

?>
