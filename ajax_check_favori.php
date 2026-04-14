<?php
// ajax_check_favori.php - Vérifie si une annonce est en favori
session_start();

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['is_fav' => false]);
    exit;
}

$host = 'localhost';
$dbname = 'le_bon_coin';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['is_fav' => false]);
    exit;
}

$annonce_id = isset($_GET['annonce_id']) ? (int)$_GET['annonce_id'] : 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM favoris WHERE user_id = :user_id AND annonce_id = :annonce_id");
$stmt->execute([':user_id' => $user_id, ':annonce_id' => $annonce_id]);
$existe = $stmt->fetch();

echo json_encode(['is_fav' => $existe ? true : false]);
?>