<?php
// =============================================================================
// File: config/db_connect.php
// Database connection using PDO
// =============================================================================

$host = "localhost";       // Database host
$db_name = "ibimina_db";   // Your database name
$username = "root";        // Database username
$password = "";            // Database password (XAMPP default is empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Optional: fetch results as associative arrays by default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Success message (optional for debugging)
    // echo "Database connected successfully!";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
