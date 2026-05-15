


<?php 

    class Course {
        /**
         * @var PDO $conn The database connection object
        */
        private $conn;
        
        /**
         * @var string $table_name The name of the database table
         */
        private $table_name = "Course";

        // constrcutor 
        public function __construct() {
            global $pdo;
            $this->conn = $pdo;
        }

        /**
         * Creates a new course record in the database.
         *
         * @param array $data An associative array containing sanitized course details
         * @return bool Returns true if the database insertion was successful, false otherwise.
         */
        public function create($data) {

            $query = "INSERT INTO " . $this->table_name . "
                (CategoryId, Title, Description, Price, DurationHours, Level, Language, ThumbnailUrl, IsPublished)
                VALUES
                (:category_id, :title, :description, :price, :duration_hours, :level, :language, :thumbnail_url, :is_published)
            ";

            // prepare & bind params
            $statement = $this->conn->prepare($query);
            
            $statement->bindParam(':category_id', $data['CategoryId']);
            $statement->bindParam(':title', $data['Title']);
            $statement->bindParam(':description', $data['Description']);
            $statement->bindParam(':price', $data['Price']);
            $statement->bindParam(':duration_hours', $data['DurationHours']);
            $statement->bindParam(':level', $data['Level']);
            $statement->bindParam(':language', $data['Language']);
            $statement->bindParam(':thumbnail_url', $data['Thumbnail']);
            $statement->bindParam(':is_published', $data['IsPublished'], PDO::PARAM_INT);

            // execute query & check if successful
            if ($statement->execute()) {
                return true;
            }
            return false;
        }

        public function getAll() {

            $query = "SELECT * FROM " . $this->table_name;

            $statement = $this->conn->prepare($query);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getById($id) {
            $query = "SELECT * FROM " . $this->table_name . " WHERE CourseId = :id LIMIT 1";
            $statement = $this->conn->prepare($query);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            $statement->execute();
            return $statement->fetch(PDO::FETCH_ASSOC);
        }

        public function delete($id) {
    
            $query = "DELETE FROM " . $this->table_name . " WHERE CourseId = :id";
            
            $statement = $this->conn->prepare($query);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            
            // Execute and return true if successful
            if ($statement->execute()) {
                return true;
            }
            return false;
        }

        
    }


?>