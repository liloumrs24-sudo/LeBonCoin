<?php
// ajax_annonce_detail.php
session_start();

// Activation des erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', 0); // On affiche pas les erreurs PHP directement
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$dbname = 'le_bon_coin';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur BDD: ' . $e->getMessage()]);
    exit;
}

$id_annonce = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id_annonce == 0) {
    echo json_encode(['success' => false, 'error' => 'ID manquant']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT a.*, u.prenom, u.nom, u.email, u.avatar as seller_avatar
    FROM annonces a 
    INNER JOIN utilisateur u ON a.user_id = u.id_letim                                   
    WHERE a.id = :id
");
$stmt->execute([':id' => $id_annonce]);
$annonce = $stmt->fetch(PDO::FETCH_ASSOC);

if($annonce) {
    error_log("Annonce trouvée: " . json_encode($annonce));
    echo json_encode(['success' => true, 'annonce' => $annonce]);
} else {
    error_log("Annonce non trouvée pour ID: " . $id_annonce);
    echo json_encode(['success' => false, 'error' => 'Annonce ID ' . $id_annonce . ' non trouvée']);
}
?>