<?php
session_start();
require_once '../DataBase/DB.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$user_id = $_SESSION['user_id'];
$annonce_id = $_POST['annonce_id'] ?? $_GET['annonce_id'] ?? null;

if(!$annonce_id) {
    echo json_encode(['success' => false, 'message' => 'ID annonce manquant']);
    exit;
}

try {
    // Marquer tous les messages de cette conversation comme archivés
    $stmt = $pdo->prepare("UPDATE messages SET statut = 'archive', updated_at = NOW() WHERE annonce_id = ? AND (sender_id = ? OR receiver_id = ?)");
    $stmt->execute([$annonce_id, $user_id, $user_id]);

    echo json_encode(['success' => true, 'message' => 'Conversation archivée']);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'archivage']);
}
?>