<?php
// DB.php - connexion à la base de données

$host = "localhost";
$user = "root";
$pass = "root";
$dbname = "le bon coin";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connexion échouée");
}

echo "Connexion réussie";

?>