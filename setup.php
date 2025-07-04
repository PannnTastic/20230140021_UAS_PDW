<?php
// Database setup script
// Run this script once to create database and tables

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "simprak";

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    $conn->exec($sql);
    echo "Database created successfully<br>";
    
    // Use database
    $conn->exec("USE $dbname");
    
    // Read and execute SQL file
    $sql_content = file_get_contents('database.sql');
    $statements = explode(';', $sql_content);
    
    foreach($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $conn->exec($statement);
        }
    }
    
    echo "Tables created successfully<br>";
    echo "Database setup completed!<br>";
    echo "<a href='index.php'>Go to Homepage</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>
