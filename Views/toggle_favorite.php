<?php
// toggle_favorite.php - AJAX pour ajouter/retirer des favoris
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit();
}

$host = 'localhost';
$dbname = 'le_bon_coin';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit();
}

$user_id = $_SESSION['user_id'];
$annonce_id = $_GET['id'] ?? 0;

if(!$annonce_id) {
    echo json_encode(['success' => false, 'error' => 'ID manquant']);
    exit();
}

// Vérifier si déjà en favori
$stmt = $pdo->prepare("SELECT id FROM favoris WHERE user_id = ? AND annonce_id = ?");
$stmt->execute([$user_id, $annonce_id]);
$is_favorite = $stmt->fetch();

if($is_favorite) {
    // Retirer des favoris
    $stmt = $pdo->prepare("DELETE FROM favoris WHERE user_id = ? AND annonce_id = ?");
    $stmt->execute([$user_id, $annonce_id]);
    echo json_encode(['success' => true, 'is_favorite' => false, 'message' => 'Retiré des favoris']);
} else {
    // Ajouter aux favoris
    $stmt = $pdo->prepare("INSERT INTO favoris (user_id, annonce_id, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, $annonce_id]);
    echo json_encode(['success' => true, 'is_favorite' => true, 'message' => 'Ajouté aux favoris']);
}
?>