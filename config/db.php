<?php
$host = 'localhost';
$db_name = 'blackbox_admin';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // For setup purposes, if DB doesn't exist, we might want to handle it, 
    // but for now let's assume the user creates it or we instruct them.
    echo "Connection error: " . $e->getMessage();
    die();
}
?>
