<?php
$host = "localhost";
$user = "root";    
$pass = "";          
$dbname = "data_collection_wolof";

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Erreur de connexion à la base de données : " . $conn->connect_error);
}
?>
