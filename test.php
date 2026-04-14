<?php
// dashboard.php - Tableau de bord utilisateur avec gestion complète des favoris
session_start();

// Vérifier si l'utilisateur est connecté
if(!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

// Configuration de la base de données
$host = 'localhost';
$dbname = 'le_bon_coin';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$message = '';
$error = '';

// ========== CRÉER UNE ANNONCE ==========
if(isset($_POST['create_annonce'])) {
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prix = floatval($_POST['prix']);
    $description = htmlspecialchars(trim($_POST['description']));
    $categorie = htmlspecialchars(trim($_POST['categorie']));
    
    // Gestion de l'upload de photo
    $photo_path = '';
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = 'uploads/annonces/' . $new_filename;
            
            if(!file_exists('uploads/annonces/')) {
                mkdir('uploads/annonces/', 0777, true);
            }
            
            if(move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $photo_path = $upload_path;
            }
        }
    }
    
    if(empty($nom) || empty($prix) || empty($description)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO annonces (user_id, nom, prix, description, categorie, photo, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if($stmt->execute([$user_id, $nom, $prix, $description, $categorie, $photo_path])) {
            $message = "✅ Annonce créée avec succès !";
        } else {
            $error = "❌ Erreur lors de la création de l'annonce.";
        }
    }
}

// ========== MODIFIER UNE ANNONCE ==========
if(isset($_POST['edit_annonce'])) {
    $annonce_id = $_POST['annonce_id'];
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prix = floatval($_POST['prix']);
    $description = htmlspecialchars(trim($_POST['description']));
    $categorie = htmlspecialchars(trim($_POST['categorie']));
    
    $stmt = $pdo->prepare("SELECT photo FROM annonces WHERE id = ? AND user_id = ?");
    $stmt->execute([$annonce_id, $user_id]);
    $current = $stmt->fetch();
    
    if($current) {
        $photo_path = $current['photo'];
        
        if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if(in_array($ext, $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_path = 'uploads/annonces/' . $new_filename;
                
                if(move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                    if($photo_path && file_exists($photo_path)) {
                        unlink($photo_path);
                    }
                    $photo_path = $upload_path;
                }
            }
        }
        
        $stmt = $pdo->prepare("UPDATE annonces SET nom = ?, prix = ?, description = ?, categorie = ?, photo = ? WHERE id = ? AND user_id = ?");
        if($stmt->execute([$nom, $prix, $description, $categorie, $photo_path, $annonce_id, $user_id])) {
            $message = "✅ Annonce modifiée avec succès !";
        } else {
            $error = "❌ Erreur lors de la modification.";
        }
    }
}

// ========== SUPPRIMER UNE ANNONCE ==========
if(isset($_GET['delete_id'])) {
    $annonce_id = $_GET['delete_id'];
    
    $stmt = $pdo->prepare("SELECT photo FROM annonces WHERE id = ? AND user_id = ?");
    $stmt->execute([$annonce_id, $user_id]);
    $annonce = $stmt->fetch();
    
    if($annonce) {
        if($annonce['photo'] && file_exists($annonce['photo'])) {
            unlink($annonce['photo']);
        }
        
        // Supprimer aussi les favoris liés
        $stmt = $pdo->prepare("DELETE FROM favoris WHERE annonce_id = ?");
        $stmt->execute([$annonce_id]);
        
        // Supprimer les messages liés
        $stmt = $pdo->prepare("DELETE FROM messages WHERE annonce_id = ?");
        $stmt->execute([$annonce_id]);
        
        // Supprimer l'annonce
        $stmt = $pdo->prepare("DELETE FROM annonces WHERE id = ? AND user_id = ?");
        $stmt->execute([$annonce_id, $user_id]);
        
        $message = "✅ Annonce supprimée avec succès !";
    }
    header("Location: dashboard.php?tab=my_ads");
    exit();
}

// ========== ENVOYER UN MESSAGE ==========
if(isset($_POST['send_message'])) {
    $annonce_id = $_POST['annonce_id'];
    $receiver_id = $_POST['receiver_id'];
    $contenu = htmlspecialchars(trim($_POST['message']));
    
    if(empty($contenu)) {
        $error = "Le message ne peut pas être vide.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO messages (annonce_id, sender_id, receiver_id, contenu, created_at, lu) VALUES (?, ?, ?, ?, NOW(), 0)");
        if($stmt->execute([$annonce_id, $user_id, $receiver_id, $contenu])) {
            $message = "📨 Message envoyé avec succès !";
        } else {
            $error = "❌ Erreur lors de l'envoi du message.";
        }
    }
}

// ========== RÉCUPÉRATION DES DONNÉES ==========

// Les annonces de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM annonces WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$user_annonces = $stmt->fetchAll();

// Les favoris de l'utilisateur avec plus d'informations
$stmt = $pdo->prepare("
    SELECT a.*, f.created_at as fav_date,
    (SELECT COUNT(*) FROM favoris WHERE annonce_id = a.id) as total_favs
    FROM annonces a 
    INNER JOIN favoris f ON a.id = f.annonce_id 
    WHERE f.user_id = ? 
    ORDER BY f.created_at DESC
");
$stmt->execute([$user_id]);
$favoris = $stmt->fetchAll();

// Messages reçus
$stmt = $pdo->prepare("
    SELECT m.*, a.nom as annonce_nom, u.prenom as sender_prenom, u.nom as sender_nom 
    FROM messages m 
    INNER JOIN annonces a ON m.annonce_id = a.id 
    INNER JOIN utilisateur u ON m.sender_id = u.id_letim 
    WHERE m.receiver_id = ? 
    ORDER BY m.created_at DESC
");
$stmt->execute([$user_id]);
$messages_recus = $stmt->fetchAll();

// Compter les messages non lus
$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND lu = 0");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetchColumn();

// Marquer un message comme lu
if(isset($_GET['read_msg'])) {
    $msg_id = $_GET['read_msg'];
    $stmt = $pdo->prepare("UPDATE messages SET lu = 1 WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$msg_id, $user_id]);
}

// Récupérer tous les IDs des favoris pour l'affichage
$stmt = $pdo->prepare("SELECT annonce_id FROM favoris WHERE user_id = ?");
$stmt->execute([$user_id]);
$favorite_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Le Bon Coin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f5f5f5;
        }

        /* Header */
        .dashboard-header {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo h1 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-name {
            font-weight: 600;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Container principal */
        .dashboard-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid #e5e5e5;
            padding: 2rem 0;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1.5rem;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #fff7ed;
            color: #f97316;
            border-left-color: #f97316;
        }

        .sidebar-menu i {
            width: 24px;
        }

        .badge {
            background: #f97316;
            color: white;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 0.7rem;
            margin-left: auto;
        }

        /* Contenu principal */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-x: auto;
        }

        /* Cartes statistiques */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2rem;
            color: #f97316;
            margin-bottom: 0.5rem;
        }

        .stat-card h3 {
            font-size: 1.8rem;
            color: #1a202c;
        }

        /* Formulaires */
        .form-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .form-card h2 {
            margin-bottom: 1.5rem;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #4a5568;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249,115,22,0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Boutons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(249,115,22,0.3);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }

        /* Bouton favori avec animation */
        .favorite-btn {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .favorite-btn i {
            transition: transform 0.3s ease;
        }

        .favorite-btn:hover i {
            transform: scale(1.2);
        }

        .favorite-btn.active {
            background: #dc2626;
            color: white;
        }

        .favorite-btn.active i {
            animation: heartBeat 0.5s ease-in-out;
        }

        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }

        /* Grille d'annonces */
        .ads-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .ad-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            position: relative;
        }

        .ad-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .ad-image {
            height: 200px;
            background: #f7f7f7;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .ad-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ad-image i {
            font-size: 3rem;
            color: #cbd5e0;
        }

        /* Badge favori sur les cartes */
        .fav-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(220, 38, 38, 0.9);
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: transform 0.3s;
        }

        .fav-badge:hover {
            transform: scale(1.1);
        }

        .fav-count-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.6);
            color: white;
            border-radius: 20px;
            padding: 4px 8px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .ad-content {
            padding: 1rem;
        }

        .ad-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .ad-price {
            color: #f97316;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .ad-category {
            color: #718096;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .ad-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        /* Messages */
        .message-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message-item {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            border-left: 4px solid #cbd5e0;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .message-item:hover {
            transform: translateX(5px);
        }

        .message-item.unread {
            border-left-color: #f97316;
            background: #fff7ed;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: #718096;
        }

        .message-content {
            margin-bottom: 0.5rem;
        }

        .message-annonce {
            font-size: 0.85rem;
            color: #f97316;
        }

        /* Alertes */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #38a169;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #e53e3e;
        }

        /* Toast notification */
        .toast-notification {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 2000;
            animation: slideInRight 0.3s ease-out;
            border-left: 4px solid #f97316;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast-notification i {
            font-size: 1.2rem;
        }

        .toast-notification.success {
            border-left-color: #10b981;
        }

        .toast-notification.error {
            border-left-color: #dc2626;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        /* Filtres */
        .filter-bar {
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-bar .form-group {
            margin-bottom: 0;
            flex: 1;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #cbd5e0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #e5e5e5;
            }
            .sidebar-menu {
                display: flex;
                overflow-x: auto;
                padding: 0 1rem;
            }
            .sidebar-menu li {
                margin-bottom: 0;
            }
            .main-content {
                padding: 1rem;
            }
            .ads-grid {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(0,0,0,0.1);
            border-radius: 50%;
            border-top-color: #f97316;
            animation: spin 0.6s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <div class="logo">
            <h1><i class="fas fa-handshake"></i> Le Bon Coin</h1>
        </div>
        <div class="user-info">
            <span class="user-name"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?></span>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="?tab=dashboard" class="<?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                <li><a href="?tab=create_ad" class="<?php echo $active_tab == 'create_ad' ? 'active' : ''; ?>"><i class="fas fa-plus-circle"></i> Créer une annonce</a></li>
                <li><a href="?tab=my_ads" class="<?php echo $active_tab == 'my_ads' ? 'active' : ''; ?>"><i class="fas fa-list"></i> Mes annonces</a></li>
                <li><a href="?tab=favorites" class="<?php echo $active_tab == 'favorites' ? 'active' : ''; ?>">
                    <i class="fas fa-heart"></i> Mes favoris
                    <span class="badge fav-count"><?php echo count($favoris); ?></span>
                </a></li>
                <li><a href="?tab=messages" class="<?php echo $active_tab == 'messages' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> Mes messages
                    <?php if($unread_count > 0): ?>
                        <span class="badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="?tab=all_ads" class="<?php echo $active_tab == 'all_ads' ? 'active' : ''; ?>"><i class="fas fa-search"></i> Toutes les annonces</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <?php if($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- ========== TABLEAU DE BORD ========== -->
            <?php if($active_tab == 'dashboard'): ?>
                <div class="stats-grid">
                    <div class="stat-card" onclick="window.location.href='?tab=my_ads'">
                        <i class="fas fa-tags"></i>
                        <h3><?php echo count($user_annonces); ?></h3>
                        <p>Mes annonces</p>
                    </div>
                    <div class="stat-card" onclick="window.location.href='?tab=favorites'">
                        <i class="fas fa-heart"></i>
                        <h3 class="fav-count"><?php echo count($favoris); ?></h3>
                        <p>Favoris</p>
                    </div>
                    <div class="stat-card" onclick="window.location.href='?tab=messages'">
                        <i class="fas fa-envelope"></i>
                        <h3><?php echo count($messages_recus); ?></h3>
                        <p>Messages reçus</p>
                    </div>
                </div>

                <div class="form-card">
                    <h2><i class="fas fa-chart-line"></i> Dernières annonces</h2>
                    <?php if(count($user_annonces) > 0): ?>
                        <div class="ads-grid">
                            <?php foreach(array_slice($user_annonces, 0, 3) as $annonce): ?>
                                <div class="ad-card">
                                    <div class="ad-image">
                                        <?php if($annonce['photo'] && file_exists($annonce['photo'])): ?>
                                            <img src="<?php echo $annonce['photo']; ?>" alt="<?php echo htmlspecialchars($annonce['nom']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-image"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ad-content">
                                        <div class="ad-title"><?php echo htmlspecialchars($annonce['nom']); ?></div>
                                        <div class="ad-price"><?php echo number_format($annonce['prix'], 0, ',', ' '); ?> €</div>
                                        <div class="ad-category"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($annonce['categorie'] ?: 'Non catégorisé'); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>Aucune annonce pour le moment.</p>
                            <a href="?tab=create_ad" class="btn btn-primary" style="margin-top: 1rem;">Créer ma première annonce</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- ========== CRÉER UNE ANNONCE ========== -->
            <?php if($active_tab == 'create_ad'): ?>
                <div class="form-card">
                    <h2><i class="fas fa-plus-circle"></i> Créer une nouvelle annonce</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Nom de l'annonce *</label>
                            <input type="text" name="nom" required placeholder="Ex: iPhone 13 Pro">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-euro-sign"></i> Prix *</label>
                            <input type="number" name="prix" step="0.01" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Description *</label>
                            <textarea name="description" required placeholder="Décrivez votre produit..."></textarea>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-folder"></i> Catégorie</label>
                            <select name="categorie">
                                <option value="">Sélectionnez une catégorie</option>
                                <option value="Électronique">📱 Électronique</option>
                                <option value="Mode">👕 Mode</option>
                                <option value="Maison">🏠 Maison</option>
                                <option value="Véhicules">🚗 Véhicules</option>
                                <option value="Immobilier">🏢 Immobilier</option>
                                <option value="Services">💼 Services</option>
                                <option value="Autre">📦 Autre</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-camera"></i> Photo</label>
                            <input type="file" name="photo" accept="image/*">
                        </div>
                        <button type="submit" name="create_annonce" class="btn btn-primary">Publier l'annonce</button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- ========== MES ANNONCES ========== -->
            <?php if($active_tab == 'my_ads'): ?>
                <div class="form-card">
                    <h2><i class="fas fa-list"></i> Mes annonces</h2>
                    <?php if(count($user_annonces) > 0): ?>
                        <div class="ads-grid">
                            <?php foreach($user_annonces as $annonce): ?>
                                <div class="ad-card">
                                    <div class="ad-image">
                                        <?php if($annonce['photo'] && file_exists($annonce['photo'])): ?>
                                            <img src="<?php echo $annonce['photo']; ?>" alt="<?php echo htmlspecialchars($annonce['nom']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-image"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ad-content">
                                        <div class="ad-title"><?php echo htmlspecialchars($annonce['nom']); ?></div>
                                        <div class="ad-price"><?php echo number_format($annonce['prix'], 0, ',', ' '); ?> €</div>
                                        <div class="ad-actions">
                                            <button class="btn btn-secondary btn-sm" onclick="openEditModal(<?php echo $annonce['id']; ?>, '<?php echo addslashes($annonce['nom']); ?>', <?php echo $annonce['prix']; ?>, '<?php echo addslashes($annonce['description']); ?>', '<?php echo addslashes($annonce['categorie']); ?>')">
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $annonce['id']; ?>, '<?php echo addslashes($annonce['nom']); ?>')">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>Vous n'avez pas encore d'annonces.</p>
                            <a href="?tab=create_ad" class="btn btn-primary" style="margin-top: 1rem;">Créer ma première annonce</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- ========== MES FAVORIS ========== -->
            <?php if($active_tab == 'favorites'): ?>
                <div class="form-card">
                    <h2><i class="fas fa-heart"></i> Mes annonces favorites <span class="badge fav-count" style="background:#f97316;"><?php echo count($favoris); ?></span></h2>
                    <?php if(count($favoris) > 0): ?>
                        <div class="ads-grid">
                            <?php foreach($favoris as $annonce): ?>
                                <div class="ad-card" data-ad-id="<?php echo $annonce['id']; ?>">
                                    <div class="ad-image">
                                        <?php if($annonce['photo'] && file_exists($annonce['photo'])): ?>
                                            <img src="<?php echo $annonce['photo']; ?>" alt="<?php echo htmlspecialchars($annonce['nom']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-image"></i>
                                        <?php endif; ?>
                                        <div class="fav-count-badge">
                                            <i class="fas fa-heart"></i> <?php echo $annonce['total_favs']; ?>
                                        </div>
                                    </div>
                                    <div class="ad-content">
                                        <div class="ad-title"><?php echo htmlspecialchars($annonce['nom']); ?></div>
                                        <div class="ad-price"><?php echo number_format($annonce['prix'], 0, ',', ' '); ?> €</div>
                                        <div class="ad-category"><i class="fas fa-user"></i> Par: <?php 
                                            $stmt = $pdo->prepare("SELECT prenom, nom FROM utilisateur WHERE id_letim = ?");
                                            $stmt->execute([$annonce['user_id']]);
                                            $owner = $stmt->fetch();
                                            echo htmlspecialchars($owner['prenom'] . ' ' . $owner['nom']);
                                        ?></div>
                                        <div class="ad-actions">
                                            <button onclick="toggleFavorite(<?php echo $annonce['id']; ?>, this)" 
                                                    class="btn btn-danger btn-sm favorite-btn active">
                                                <i class="fas fa-heart"></i> Retirer
                                            </button>
                                            <button class="btn btn-primary btn-sm" onclick="openDetailModal(<?php echo $annonce['id']; ?>)">
                                                <i class="fas fa-eye"></i> Voir détail
                                            </button>
                                            <button class="btn btn-secondary btn-sm" onclick="openMessageModal(<?php echo $annonce['id']; ?>, <?php echo $annonce['user_id']; ?>, '<?php echo addslashes($annonce['nom']); ?>')">
                                                <i class="fas fa-envelope"></i> Contacter
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-heart-broken"></i>
                            <p>Vous n'avez pas encore de favoris.</p>
                            <a href="?tab=all_ads" class="btn btn-primary" style="margin-top: 1rem;">Découvrir des annonces</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- ========== MESSAGES ========== -->
            <?php if($active_tab == 'messages'): ?>
                <div class="form-card">
                    <h2><i class="fas fa-envelope"></i> Messages reçus</h2>
                    <?php if(count($messages_recus) > 0): ?>
                        <div class="message-list">
                            <?php foreach($messages_recus as $msg): ?>
                                <div class="message-item <?php echo $msg['lu'] == 0 ? 'unread' : ''; ?>" onclick="viewMessage(<?php echo $msg['id']; ?>)">
                                    <div class="message-header">
                                        <span><strong><?php echo htmlspecialchars($msg['sender_prenom'] . ' ' . $msg['sender_nom']); ?></strong></span>
                                        <span><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></span>
                                    </div>
                                    <div class="message-content">
                                        <?php echo nl2br(htmlspecialchars(substr($msg['contenu'], 0, 100))); ?>
                                        <?php if(strlen($msg['contenu']) > 100) echo '...'; ?>
                                    </div>
                                    <div class="message-annonce">
                                        <i class="fas fa-tag"></i> Annonce: <?php echo htmlspecialchars($msg['annonce_nom']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Aucun message reçu.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- ========== TOUTES LES ANNONCES AVEC FILTRES ========== -->
            <?php if($active_tab == 'all_ads'): 
                $sql = "SELECT a.*, u.prenom, u.nom, 
                        (SELECT COUNT(*) FROM favoris WHERE annonce_id = a.id) as total_favs
                        FROM annonces a 
                        INNER JOIN utilisateur u ON a.user_id = u.id_letim 
                        WHERE 1=1";
                $params = [];
                
                if(!empty($_GET['search'])) {
                    $sql .= " AND (a.nom LIKE ? OR a.description LIKE ?)";
                    $search = '%' . $_GET['search'] . '%';
                    $params[] = $search;
                    $params[] = $search;
                }
                
                if(!empty($_GET['categorie'])) {
                    $sql .= " AND a.categorie = ?";
                    $params[] = $_GET['categorie'];
                }
                
                if(!empty($_GET['prix_min'])) {
                    $sql .= " AND a.prix >= ?";
                    $params[] = $_GET['prix_min'];
                }
                
                if(!empty($_GET['prix_max'])) {
                    $sql .= " AND a.prix <= ?";
                    $params[] = $_GET['prix_max'];
                }
                
                $sql .= " ORDER BY a.created_at DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $all_annonces = $stmt->fetchAll();
            ?>
                <div class="form-card">
                    <h2><i class="fas fa-search"></i> Toutes les annonces</h2>
                    
                    <form method="GET" class="filter-bar">
                        <input type="hidden" name="tab" value="all_ads">
                        <div class="form-group">
                            <label>Recherche</label>
                            <input type="text" name="search" placeholder="Mot-clé..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Catégorie</label>
                            <select name="categorie">
                                <option value="">Toutes</option>
                                <option value="Électronique" <?php echo ($_GET['categorie'] ?? '') == 'Électronique' ? 'selected' : ''; ?>>Électronique</option>
                                <option value="Mode" <?php echo ($_GET['categorie'] ?? '') == 'Mode' ? 'selected' : ''; ?>>Mode</option>
                                <option value="Maison" <?php echo ($_GET['categorie'] ?? '') == 'Maison' ? 'selected' : ''; ?>>Maison</option>
                                <option value="Véhicules" <?php echo ($_GET['categorie'] ?? '') == 'Véhicules' ? 'selected' : ''; ?>>Véhicules</option>
                                <option value="Immobilier" <?php echo ($_GET['categorie'] ?? '') == 'Immobilier' ? 'selected' : ''; ?>>Immobilier</option>
                                <option value="Services" <?php echo ($_GET['categorie'] ?? '') == 'Services' ? 'selected' : ''; ?>>Services</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Prix min (€)</label>
                            <input type="number" name="prix_min" step="1" value="<?php echo htmlspecialchars($_GET['prix_min'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Prix max (€)</label>
                            <input type="number" name="prix_max" step="1" value="<?php echo htmlspecialchars($_GET['prix_max'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                        </div>
                    </form>

                    <?php if(count($all_annonces) > 0): ?>
                        <div class="ads-grid">
                            <?php foreach($all_annonces as $annonce): 
                                $is_favorite = in_array($annonce['id'], $favorite_ids);
                            ?>
                                <div class="ad-card" data-ad-id="<?php echo $annonce['id']; ?>">
                                    <div class="ad-image">
                                        <?php if($annonce['photo'] && file_exists($annonce['photo'])): ?>
                                            <img src="<?php echo $annonce['photo']; ?>" alt="<?php echo htmlspecialchars($annonce['nom']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-image"></i>
                                        <?php endif; ?>
                                        <div class="fav-count-badge">
                                            <i class="fas fa-heart"></i> <?php echo $annonce['total_favs']; ?>
                                        </div>
                                        <?php if($is_favorite): ?>
                                            <div class="fav-badge" onclick="event.stopPropagation(); toggleFavorite(<?php echo $annonce['id']; ?>, this)">
                                                <i class="fas fa-heart"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ad-content">
                                        <div class="ad-title"><?php echo htmlspecialchars($annonce['nom']); ?></div>
                                        <div class="ad-price"><?php echo number_format($annonce['prix'], 0, ',', ' '); ?> €</div>
                                        <div class="ad-category">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($annonce['prenom'] . ' ' . $annonce['nom']); ?>
                                        </div>
                                        <div class="ad-actions">
                                            <button onclick="toggleFavorite(<?php echo $annonce['id']; ?>, this)" 
                                                    class="btn favorite-btn <?php echo $is_favorite ? 'active' : 'btn-secondary'; ?> btn-sm">
                                                <i class="fas <?php echo $is_favorite ? 'fa-heart' : 'fa-heart'; ?>"></i> 
                                                <?php echo $is_favorite ? 'Retirer' : 'Favoris'; ?>
                                            </button>
                                            <button class="btn btn-primary btn-sm" onclick="openDetailModal(<?php echo $annonce['id']; ?>)">
                                                <i class="fas fa-eye"></i> Voir détail
                                            </button>
                                            <?php if($annonce['user_id'] != $user_id): ?>
                                                <button class="btn btn-secondary btn-sm" onclick="openMessageModal(<?php echo $annonce['id']; ?>, <?php echo $annonce['user_id']; ?>, '<?php echo addslashes($annonce['nom']); ?>')">
                                                    <i class="fas fa-envelope"></i> Contacter
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <p>Aucune annonce trouvée avec ces critères.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal Modifier annonce -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>Modifier l'annonce</h3>
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="annonce_id" id="edit_id">
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="nom" id="edit_nom" required>
                </div>
                <div class="form-group">
                    <label>Prix</label>
                    <input type="number" name="prix" step="0.01" id="edit_prix" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Catégorie</label>
                    <select name="categorie" id="edit_categorie">
                        <option value="">Sélectionnez</option>
                        <option value="Électronique">Électronique</option>
                        <option value="Mode">Mode</option>
                        <option value="Maison">Maison</option>
                        <option value="Véhicules">Véhicules</option>
                        <option value="Immobilier">Immobilier</option>
                        <option value="Services">Services</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nouvelle photo (optionnel)</label>
                    <input type="file" name="photo" accept="image/*">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Annuler</button>
                    <button type="submit" name="edit_annonce" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Détail annonce -->
    <div id="detailModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <h3 id="detail_title"></h3>
            <div id="detail_image" style="text-align: center; margin: 1rem 0;"></div>
            <p><strong>Prix:</strong> <span id="detail_price"></span> €</p>
            <p><strong>Catégorie:</strong> <span id="detail_category"></span></p>
            <p><strong>Description:</strong></p>
            <p id="detail_description" style="background: #f7f7f7; padding: 1rem; border-radius: 0.5rem;"></p>
            <p><strong>Vendeur:</strong> <span id="detail_seller"></span></p>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Fermer</button>
                <button type="button" class="btn btn-primary" id="detail_fav_btn" onclick=""></button>
            </div>
        </div>
    </div>

    <!-- Modal Envoyer message -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <h3>Contacter le vendeur</h3>
            <p>À propos de l'annonce: <strong id="message_annonce_title"></strong></p>
            <form method="POST">
                <input type="hidden" name="annonce_id" id="message_annonce_id">
                <input type="hidden" name="receiver_id" id="message_receiver_id">
                <div class="form-group">
                    <label>Votre message</label>
                    <textarea name="message" rows="5" required placeholder="Bonjour, je suis intéressé par votre annonce..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeMessageModal()">Annuler</button>
                    <button type="submit" name="send_message" class="btn btn-primary">Envoyer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ========== FONCTIONS FAVORIS AVEC AJAX ==========
        function toggleFavorite(annonceId, buttonElement) {
            // Ajouter un indicateur de chargement
            const originalText = buttonElement.innerHTML;
            buttonElement.innerHTML = '<span class="loading-spinner"></span>';
            buttonElement.disabled = true;
            
            fetch(`toggle_favorite.php?id=${annonceId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Animation sur le bouton
                    buttonElement.classList.add('heart-animation');
                    setTimeout(() => {
                        buttonElement.classList.remove('heart-animation');
                    }, 500);
                    
                    // Changer l'apparence du bouton
                    if(data.is_favorite) {
                        buttonElement.classList.remove('btn-secondary');
                        buttonElement.classList.add('active', 'btn-danger');
                        buttonElement.innerHTML = '<i class="fas fa-heart"></i> Retirer';
                        showToast('❤️ Ajouté aux favoris !', 'success');
                    } else {
                        buttonElement.classList.remove('active', 'btn-danger');
                        buttonElement.classList.add('btn-secondary');
                        buttonElement.innerHTML = '<i class="fas fa-heart"></i> Favoris';
                        showToast('⭐ Retiré des favoris', 'info');
                    }
                    
                    // Mettre à jour le compteur de favoris
                    updateFavoriteCount();
                    
                    // Mettre à jour le compteur de likes sur la carte
                    updateFavoriteCountOnCard(annonceId, data.is_favorite);
                    
                    // Si on est sur la page des favoris, supprimer la carte
                    if(window.location.href.includes('tab=favorites') && !data.is_favorite) {
                        const card = document.querySelector(`.ad-card[data-ad-id="${annonceId}"]`);
                        if(card) {
                            card.style.animation = 'fadeOut 0.3s ease-out';
                            setTimeout(() => card.remove(), 300);
                        }
                        // Recharger la page si plus de favoris
                        setTimeout(() => {
                            if(document.querySelectorAll('.ad-card').length === 0) {
                                location.reload();
                            }
                        }, 500);
                    }
                }
                buttonElement.disabled = false;
            })
            .catch(error => {
                console.error('Erreur:', error);
                buttonElement.innerHTML = originalText;
                buttonElement.disabled = false;
                showToast('❌ Une erreur est survenue', 'error');
            });
        }
        
        // Mettre à jour le compteur de likes sur une carte
        function updateFavoriteCountOnCard(annonceId, isAdding) {
            const card = document.querySelector(`.ad-card[data-ad-id="${annonceId}"]`);
            if(card) {
                const countBadge = card.querySelector('.fav-count-badge');
                if(countBadge) {
                    let currentCount = parseInt(countBadge.textContent);
                    if(isNaN(currentCount)) currentCount = 0;
                    const newCount = isAdding ? currentCount + 1 : currentCount - 1;
                    countBadge.innerHTML = `<i class="fas fa-heart"></i> ${newCount}`;
                }
            }
        }
        
        // Afficher une notification toast
        function showToast(message, type = 'success') {
            const oldToast = document.querySelector('.toast-notification');
            if(oldToast) oldToast.remove();
            
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            const icon = type === 'success' ? '❤️' : (type === 'error' ? '⚠️' : '⭐');
            toast.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-heart' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-star')}" style="color: ${type === 'success' ? '#dc2626' : (type === 'error' ? '#dc2626' : '#f59e0b')}"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Mettre à jour le compteur de favoris
        function updateFavoriteCount() {
            fetch('get_favorite_count.php')
                .then(response => response.json())
                .then(data => {
                    const favCountElements = document.querySelectorAll('.fav-count');
                    favCountElements.forEach(el => {
                        const oldCount = parseInt(el.textContent);
                        if(!isNaN(oldCount)) {
                            el.textContent = data.count;
                            if(oldCount !== data.count) {
                                el.classList.add('pulse');
                                setTimeout(() => el.classList.remove('pulse'), 500);
                            }
                        } else {
                            el.textContent = data.count;
                        }
                    });
                })
                .catch(error => console.error('Erreur:', error));
        }
        
        // Animation pulse pour les compteurs
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.2); }
            }
            .pulse { animation: pulse 0.5s ease-in-out; }
            @keyframes fadeOut {
                from { opacity: 1; transform: scale(1); }
                to { opacity: 0; transform: scale(0.8); }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .heart-animation { animation: heartBeat 0.5s ease-in-out; }
            @keyframes heartBeat {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.3); }
            }
        `;
        document.head.appendChild(style);
        
        // ========== AUTRES FONCTIONS ==========
        function confirmDelete(id, nom) {
            if(confirm(`Êtes-vous sûr de vouloir supprimer l'annonce "${nom}" ? Cette action est irréversible et supprimera également les favoris associés.`)) {
                window.location.href = `?delete_id=${id}&tab=my_ads`;
            }
        }
        
        function openEditModal(id, nom, prix, description, categorie) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nom').value = nom;
            document.getElementById('edit_prix').value = prix;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_categorie').value = categorie || '';
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        function openDetailModal(id) {
            fetch(`get_annonce.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('detail_title').innerText = data.nom;
                    document.getElementById('detail_price').innerText = new Intl.NumberFormat('fr-FR').format(data.prix);
                    document.getElementById('detail_category').innerText = data.categorie || 'Non catégorisé';
                    document.getElementById('detail_description').innerText = data.description;
                    document.getElementById('detail_seller').innerText = data.seller;
                    document.getElementById('detail_fav_btn').innerHTML = data.is_favorite ? '<i class="fas fa-heart"></i> Retirer des favoris' : '<i class="fas fa-heart"></i> Ajouter aux favoris';
                    document.getElementById('detail_fav_btn').onclick = () => {
                        toggleFavorite(id, document.getElementById('detail_fav_btn'));
                        openDetailModal(id); // Recharger le modal
                    };
                    
                    let imageHtml = '';
                    if(data.photo && data.photo !== '') {
                        imageHtml = `<img src="${data.photo}" style="max-width: 100%; max-height: 300px; border-radius: 0.5rem;">`;
                    } else {
                        imageHtml = '<i class="fas fa-image" style="font-size: 5rem; color: #cbd5e0;"></i>';
                    }
                    document.getElementById('detail_image').innerHTML = imageHtml;
                    
                    document.getElementById('detailModal').classList.add('active');
                });
        }
        
        function closeDetailModal() {
            document.getElementById('detailModal').classList.remove('active');
        }
        
        function openMessageModal(annonce_id, receiver_id, title) {
            document.getElementById('message_annonce_id').value = annonce_id;
            document.getElementById('message_receiver_id').value = receiver_id;
            document.getElementById('message_annonce_title').innerText = title;
            document.getElementById('messageModal').classList.add('active');
        }
        
        function closeMessageModal() {
            document.getElementById('messageModal').classList.remove('active');
        }
        
        function viewMessage(id) {
            window.location.href = `?tab=messages&read_msg=${id}`;
        }
        
        // Fermer les modals en cliquant en dehors
        window.onclick = function(event) {
            if(event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
        
        // Rafraîchir le compteur de favoris toutes les 30 secondes
        setInterval(updateFavoriteCount, 30000);
    </script>
</body>
</html>