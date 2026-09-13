<?php
/**
 * This script creates the course_materials table
 * Run this once to set up the database table
 */

$conn = new mysqli("localhost", "root", "", "23UCS105");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS `course_materials` (
  `material_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int(11) NOT NULL,
  `uploaded_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`material_id`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table `course_materials` created successfully!";
    
    // Create uploads directory if it doesn't exist
    if (!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
    }
    
    if (!is_dir('uploads/materials')) {
        mkdir('uploads/materials', 0755, true);
    }
    
    echo "<br>Directories created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
