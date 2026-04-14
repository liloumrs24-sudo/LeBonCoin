<?php
// ajax_favoris.php - Gestion des favoris
session_start();

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
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
    echo json_encode(['success' => false]);
    exit;
}

$annonce_id = isset($_POST['annonce_id']) ? (int)$_POST['annonce_id'] : 0;
$user_id = $_SESSION['user_id'];

if($annonce_id == 0) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM favoris WHERE user_id = :user_id AND annonce_id = :annonce_id");
$stmt->execute([':user_id' => $user_id, ':annonce_id' => $annonce_id]);
$existe = $stmt->fetch();

if($existe) {
    $stmt = $pdo->prepare("DELETE FROM favoris WHERE user_id = :user_id AND annonce_id = :annonce_id");
    $stmt->execute([':user_id' => $user_id, ':annonce_id' => $annonce_id]);
    echo json_encode(['success' => true, 'action' => 'removed']);
} else {
    $stmt = $pdo->prepare("INSERT INTO favoris (user_id, annonce_id, created_at) VALUES (:user_id, :annonce_id, NOW())");
    $stmt->execute([':user_id' => $user_id, ':annonce_id' => $annonce_id]);
    echo json_encode(['success' => true, 'action' => 'added']);
}
?>