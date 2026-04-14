<?php
// get_annonce.php - Récupère les détails d'une annonce en JSON
session_start();
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'le_bon_coin';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $id = $_GET['id'] ?? 0;
    
    $stmt = $pdo->prepare("
        SELECT a.*, u.prenom, u.nom, u.avatar
        FROM annonces a 
        INNER JOIN utilisateur u ON a.user_id = u.id_letim 
        WHERE a.id = ?
    ");
    $stmt->execute([$id]);
    $annonce = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($annonce) {
        $is_favorite = false;
        if(isset($_SESSION['user_id'])) {
            $fav_stmt = $pdo->prepare("SELECT id FROM favoris WHERE user_id = ? AND annonce_id = ?");
            $fav_stmt->execute([$_SESSION['user_id'], $id]);
            $is_favorite = $fav_stmt->fetch() !== false;
        }
        
        echo json_encode([
            'id' => $annonce['id'],
            'nom' => $annonce['nom'],
            'prix' => $annonce['prix'],
            'description' => $annonce['description'],
            'categorie' => $annonce['categorie'],
            'photo' => $annonce['photo'],
            'seller' => $annonce['prenom'] . ' ' . $annonce['nom'],
            'seller_avatar' => $annonce['avatar'],
            'is_favorite' => $is_favorite
        ]);
    } else {
        echo json_encode(['error' => 'Annonce non trouvée']);
    }
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>