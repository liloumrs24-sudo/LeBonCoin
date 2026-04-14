<?php
// index.php - Page d'accueil avec données dynamiques de la BDD
session_start();

// Configuration de la base de données
$host = 'localhost';
$dbname = 'le_bon_coin';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // En cas d'erreur BDD, on continue sans afficher les données dynamiques
    $error_db = "Erreur de connexion : " . $e->getMessage();
}

// Récupérer les annonces pour l'affichage
if(isset($pdo)) {
    $limit = isset($_GET['show_all']) ? "" : "LIMIT 8";
    
    // Construction de la requête avec filtres
    $sql = "
        SELECT a.*, u.prenom, u.nom, u.avatar,
        (SELECT COUNT(*) FROM favoris WHERE annonce_id = a.id) as total_favs
        FROM annonces a 
        INNER JOIN utilisateur u ON a.user_id = u.id_letim 
        WHERE 1=1
    ";
    $params = [];
    
    // Filtre par mot-clé
    if(!empty($_GET['search'])) {
        $sql .= " AND (a.nom_annonce LIKE ? OR a.description LIKE ? OR a.categorie LIKE ?)";
        $searchTerm = '%' . $_GET['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Filtre par catégorie
    if(!empty($_GET['categorie'])) {
        $sql .= " AND a.categorie = ?";
        $params[] = $_GET['categorie'];
    }
    
    // Filtre par prix minimum
    if(!empty($_GET['prix_min'])) {
        $sql .= " AND a.prix >= ?";
        $params[] = $_GET['prix_min'];
    }
    
    // Filtre par prix maximum
    if(!empty($_GET['prix_max'])) {
        $sql .= " AND a.prix <= ?";
        $params[] = $_GET['prix_max'];
    }
    
    $sql .= " ORDER BY a.created_at DESC $limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $recent_annonces = $stmt->fetchAll();
    
    // Récupérer les annonces pour le carrousel (5 dernières avec photo)
    $stmt = $pdo->prepare("
        SELECT * FROM annonces 
        WHERE photo IS NOT NULL AND photo != '' 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $carousel_annonces = $stmt->fetchAll();
    
    // Statistiques dynamiques
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM annonces");
    $total_annonces = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT id_letim) as total FROM utilisateur");
    $total_vendeurs = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_f FROM favoris");
    $total_favoris = $stmt->fetch()['total_f'];
    
    // Compter les annonces par catégorie
    $stmt = $pdo->query("
        SELECT categorie, COUNT(*) as count 
        FROM annonces 
        WHERE categorie IS NOT NULL AND categorie != ''
        GROUP BY categorie 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $top_categories = $stmt->fetchAll();
} else {
    $recent_annonces = [];
    $carousel_annonces = [];
    $total_annonces = 0;
    $total_vendeurs = 0;
    $total_favoris = 0;
    $top_categories = [];
}

// Images par défaut pour le carrousel
$default_carousel = [
    ['titre' => 'Donnez une seconde vie à vos objets', 'sous_titre' => 'Rejoignez une communauté engagée et solidaire', 'image' => 'https://picsum.photos/id/169/1600/500'],
    ['titre' => 'Publiez en 30 secondes', 'sous_titre' => 'Gratuit, sans prise de tête', 'image' => 'https://picsum.photos/id/156/1600/500'],
    ['titre' => 'Favoris & messages directs', 'sous_titre' => 'Discutez facilement avec les vendeurs', 'image' => 'https://picsum.photos/id/125/1600/500'],
    ['titre' => 'Achetez en toute sécurité', 'sous_titre' => 'Paiements sécurisés et livraison garantie', 'image' => 'https://picsum.photos/id/200/1600/500'],
    ['titre' => 'Rejoignez la communauté', 'sous_titre' => 'Plus de 10 000 utilisateurs actifs', 'image' => 'https://picsum.photos/id/250/1600/500']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Annoncy - Petites annonces locales et bonnes affaires</title>
    <!-- Google Fonts & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --bg: #0d0e12;
            --bg2: #13151c;
            --bg3: #1a1d27;
            --surface: #1e2130;
            --surface2: #252840;
            --border: rgba(255,255,255,0.07);
            --border2: rgba(255,255,255,0.12);
            --accent: #ff6b35;
            --accent2: #ff9a6c;
            --accent-glow: rgba(255,107,53,0.25);
            --accent-soft: rgba(255,107,53,0.1);
            --text: #f0f1f5;
            --text2: #9499b0;
            --text3: #6b7094;
            --success: #22d3a5;
            --danger: #ff4d6d;
            --info: #4d9fff;
            
            --shadow-warm: 0 15px 35px -5px rgba(0,0,0,0.5);
            --shadow-hover: 0 25px 45px -10px rgba(0,0,0,0.7);
            --radius-xl: 1.5rem;
            --radius-2xl: 2rem;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.5;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 50% at 10% 0%, rgba(255,107,53,0.08) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 90% 100%, rgba(77,159,255,0.06) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        /* ========= HEADER ========= */
        .main-header {
            background: rgba(13,14,18,0.85);
            backdrop-filter: blur(20px);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border);
        }

        .header-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo-area {
            display: flex;
            align-items: baseline;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .logo {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-decoration: none;
            transition: opacity 0.3s;
        }

        .logo:hover { opacity: 0.8; }

        .tagline {
            font-size: 0.8rem;
            background: var(--surface2);
            padding: 0.2rem 0.8rem;
            border-radius: 40px;
            color: var(--text2);
            font-weight: 500;
            border: 1px solid var(--border);
        }

        .nav-links {
            display: flex;
            gap: 1.8rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-links a {
            text-decoration: none;
            font-weight: 500;
            color: var(--text2);
            transition: 0.2s;
            font-size: 0.95rem;
        }

        .nav-links a:hover {
            color: var(--text);
            transform: translateY(-2px);
        }

        .btn-outline {
            border: 1px solid var(--border);
            padding: 0.5rem 1.3rem;
            border-radius: 60px;
            background: var(--surface2);
            color: var(--text2) !important;
            font-weight: 500;
            display: inline-block;
        }

        .btn-outline:hover {
            background: var(--bg3);
            border-color: var(--accent);
            color: var(--text) !important;
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #e55a28);
            color: white !important;
            padding: 0.55rem 1.4rem;
            border-radius: 60px;
            box-shadow: 0 4px 12px var(--accent-glow);
            display: inline-block;
            font-weight: 600;
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,107,53,0.4);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 0.3rem 1rem 0.3rem 1.5rem;
            border-radius: 60px;
        }

        .user-info span {
            color: var(--text);
            font-weight: 500;
        }

        /* ========= SUBHEADER ========= */
        .subheader {
            background: var(--bg2);
            border-bottom: 1px solid var(--border);
            padding: 0.7rem 2rem;
            position: sticky;
            top: 73px;
            z-index: 99;
        }

        .carousel-title {
            max-width: 1280px;
            margin: 1.5rem auto 0.5rem;
            padding: 0 2rem;
            color: var(--text);
            font-family: 'Syne', sans-serif;
            font-size: 1.9rem;
            font-weight: 700;
        }

        .subheader-container {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .quick-actions {
            display: flex;
            gap: 2rem;
        }

        .quick-actions a {
            text-decoration: none;
            font-weight: 500;
            color: var(--text2);
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.9rem;
            transition: 0.2s;
        }

        .quick-actions a i {
            color: var(--accent);
            font-size: 1.1rem;
        }

        .quick-actions a:hover {
            color: var(--text);
            transform: translateY(-2px);
        }

        .search-badge {
            background: var(--surface);
            padding: 0.4rem 1.2rem;
            border-radius: 60px;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text);
            border: 1px solid var(--accent-soft);
        }

        .search-badge i { color: var(--accent); margin-right: 0.4rem; }

        /* ========= FILTER BAR ========= */
        .filter-bar {
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            margin: 2rem 0;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-bar .form-group {
            flex: 1;
            min-width: 150px;
        }

        .filter-bar label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text2);
            margin-bottom: 0.5rem;
        }

        .filter-bar input,
        .filter-bar select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
            font-size: 0.9rem;
        }

        .filter-bar input:focus,
        .filter-bar select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px var(--accent-soft);
        }

        .filter-bar .btn-filter {
            background: linear-gradient(135deg, var(--accent), #e55a28);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-bar .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255,107,53,0.4);
        }

        /* ========= CARROUSEL ========= */
        .carousel-section {
            width: 100vw;
            margin: 1rem calc(50% - 50vw) 0;
            padding: 0;
            position: relative;
            z-index: 1;
        }

        .carousel-container {
            position: relative;
            width: 100%;
            max-width: 100%;
            border-radius: 0;
            overflow: hidden;
            box-shadow: var(--shadow-warm);
            border: none;
        }

        .carousel-slides {
            display: flex;
            transition: transform 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }

        .carousel-slide {
            min-width: 100%;
            height: 560px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: flex-end;
            position: relative;
        }

        .slide-overlay {
            background: linear-gradient(0deg, rgba(13,14,18,0.95) 0%, rgba(13,14,18,0.4) 60%, transparent 100%);
            width: 100%;
            padding: 3rem 2.5rem;
            color: var(--text);
        }

        .slide-overlay h2 {
            font-family: 'Syne', sans-serif;
            font-size: 2.8rem;
            font-weight: 800;
            text-shadow: 0 4px 12px rgba(0,0,0,0.5);
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .slide-overlay p {
            font-size: 1.1rem;
            max-width: 600px;
            font-weight: 400;
            color: var(--text2);
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .slide-price {
            display: inline-block;
            background: var(--accent);
            color: white;
            padding: 0.4rem 1.2rem;
            border-radius: 60px;
            font-size: 1rem;
            font-weight: 700;
            margin-top: 1rem;
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: var(--surface2);
            backdrop-filter: blur(8px);
            width: 48px;
            height: 48px;
            border-radius: 60px;
            border: 1px solid var(--border);
            font-size: 1.4rem;
            cursor: pointer;
            color: var(--text);
            transition: 0.2s;
        }

        .carousel-btn:hover {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
            transform: translateY(-50%) scale(1.05);
        }

        .btn-prev { left: 20px; }
        .btn-next { right: 20px; }

        .dots {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .dot {
            width: 10px;
            height: 10px;
            background: var(--surface2);
            border-radius: 20px;
            cursor: pointer;
            transition: 0.2s;
            border: 1px solid var(--border);
        }

        .dot.active {
            background: var(--accent);
            border-color: var(--accent);
            width: 28px;
            box-shadow: 0 0 10px var(--accent-glow);
        }

        /* SECTIONS */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 3rem 2rem;
            position: relative;
            z-index: 1;
        }

        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 2.1rem;
            font-weight: 700;
            margin-bottom: 2rem;
            position: relative;
            display: inline-block;
            color: var(--text);
        }

        .section-title:after {
            content: '✦';
            position: absolute;
            top: -10px;
            right: -25px;
            font-size: 1.4rem;
            color: var(--accent);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .feature-card {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-warm);
            transition: all 0.3s ease;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--accent-soft), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            border-color: var(--border2);
        }
        
        .feature-card:hover::after { opacity: 1; }

        .feature-icon {
            font-size: 2.2rem;
            background: var(--bg3);
            border: 1px solid var(--border2);
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            color: var(--accent);
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 2;
        }

        .feature-card h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1.3rem;
            margin-bottom: 0.75rem;
            font-weight: 700;
            color: var(--text);
            position: relative;
            z-index: 2;
        }
        
        .feature-card p {
            color: var(--text3);
            font-size: 0.95rem;
            position: relative;
            z-index: 2;
        }

        .stats-row {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: 3rem 2rem;
            margin: 2rem 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .stats-row::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at center, var(--accent-soft), transparent 70%);
            opacity: 0.5;
        }

        .stat-item {
            position: relative;
            z-index: 2;
        }

        .stat-item h4 {
            font-family: 'Syne', sans-serif;
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--accent);
            text-shadow: 0 4px 12px rgba(0,0,0,0.5);
            margin-bottom: 0.5rem;
        }
        
        .stat-item p {
            color: var(--text2);
            font-weight: 500;
            font-size: 1.1rem;
        }

        .cards-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .annonce-card {
            background: var(--bg3);
            border-radius: var(--radius-xl);
            overflow: hidden;
            transition: all 0.25s;
            cursor: pointer;
            border: 1px solid var(--border);
            position: relative;
        }

        .annonce-card:hover {
            transform: translateY(-6px);
            border-color: var(--border2);
            box-shadow: var(--shadow-hover);
        }

        .card-img {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .card-img::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 60%, rgba(0,0,0,0.6));
        }

        .card-fav-count {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            color: white;
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 6px;
            z-index: 2;
        }
        
        .card-fav-count i { color: var(--danger); }

        .card-content {
            padding: 1.3rem;
        }

        .price {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.4rem;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }
        
        .card-content h3 {
            font-family: 'Syne', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            color: var(--text);
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .card-content p {
            color: var(--text3);
            font-size: 0.85rem;
        }

        .category-badge {
            background: var(--surface2);
            padding: 0.3rem 0.8rem;
            border-radius: 60px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text2);
            display: inline-block;
            margin-top: 0.8rem;
            border: 1px solid var(--border);
        }

        .seller-name {
            font-size: 0.85rem;
            color: var(--text3);
            margin-top: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .seller-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            border: 1px solid var(--border);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--surface);
        }

        .seller-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .btn-view-all {
            background: linear-gradient(135deg, var(--accent), #e55a28);
            color: white;
            padding: 0.8rem 2.5rem;
            border-radius: 60px;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
            box-shadow: 0 4px 12px var(--accent-glow);
            transition: all 0.3s;
        }

        .btn-view-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,107,53,0.4);
        }

        .categories-list {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .category-item {
            background: var(--surface);
            padding: 0.8rem 1.5rem;
            border-radius: 60px;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            transition: all 0.3s;
            border: 1px solid var(--border);
        }

        .category-item:hover {
            background: var(--surface2);
            border-color: var(--accent);
            transform: translateY(-2px);
            color: var(--accent);
        }
        
        .category-item span {
            background: var(--bg3);
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--text2);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--surface2);
            margin-bottom: 1rem;
        }
        
        .empty-state p { color: var(--text3); }

        /* FOOTER */
        .footer {
            background: var(--bg2);
            color: var(--text2);
            border-top: 1px solid var(--border);
            padding: 4rem 2rem 2rem;
            margin-top: 3rem;
            position: relative;
            z-index: 1;
        }

        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 2rem;
        }

        .footer-col h4 {
            font-family: 'Syne', sans-serif;
            color: var(--text);
            margin-bottom: 1.5rem;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .footer-col a, .footer-col p {
            color: var(--text3);
            text-decoration: none;
            font-size: 0.95rem;
            line-height: 2;
            transition: color 0.2s;
        }

        .footer-col a:hover {
            color: var(--accent);
        }

        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-icons a {
            background: var(--surface);
            border: 1px solid var(--border);
            width: 42px;
            height: 42px;
            border-radius: 60px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            color: var(--text2);
        }

        .social-icons a:hover {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
            transform: translateY(-3px);
        }

        /* MODAL CSS */
        .modal {
            display: flex;
            position: fixed;
            inset: 0;
            z-index: 1000;
            background: rgba(13,14,18,0.85);
            backdrop-filter: blur(10px);
            align-items: center;
            justify-content: center;
            padding: 1rem;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-body {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            transform: translateY(20px);
            transition: transform 0.3s ease;
            box-shadow: var(--shadow-warm);
        }

        .modal.active .modal-body {
            transform: translateY(0);
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: 0.2s;
        }

        .modal-close:hover {
            background: var(--danger);
            transform: scale(1.1);
        }

        .modal-content-inner {
            padding: 2rem;
        }
        
        .modal-loader {
            padding: 3rem;
            text-align: center;
            color: var(--accent);
            font-size: 2rem;
        }

        .modal-header-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .modal-title {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
        }

        .modal-price {
            font-family: 'Syne', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--accent);
            background: var(--surface2);
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
        }

        .modal-desc {
            color: var(--text2);
            font-size: 1.05rem;
            line-height: 1.6;
            margin: 1.5rem 0;
            white-space: pre-wrap;
        }

        .modal-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .modal-action {
            display: flex;
            justify-content: flex-end;
            margin-top: 2rem;
            border-top: 1px solid var(--border);
            padding-top: 1.5rem;
        }

        .copyright {
            text-align: center;
            padding-top: 2rem;
            margin-top: 3rem;
            border-top: 1px solid var(--border);
            font-size: 0.85rem;
            color: var(--text3);
        }

        @media (max-width: 780px) {
            .header-container, .subheader-container {
                flex-direction: column;
                text-align: center;
            }
            .nav-links { justify-content: center; }
            .carousel-slide { height: 350px; }
            .slide-overlay h2 { font-size: 1.8rem; }
            .section-title { font-size: 1.6rem; }
            .filter-bar {
                flex-direction: column;
                gap: 1rem;
            }
            .filter-bar .form-group {
                min-width: auto;
                width: 100%;
            }
            .filter-bar .btn-filter {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<!-- HEADER PRINCIPAL -->
<header class="main-header">
    <div class="header-container">
        <div class="logo-area">
            <a href="index.php" class="logo">Le Bon Coin<span>✦</span></a>
            <div class="tagline">L'âme des bonnes affaires</div>
        </div>
        <div class="nav-links">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="Views/dashboard.php?tab=create_ad"><i class="fas fa-plus-circle"></i> Déposer une annonce</a>
                <a href="Views/dashboard.php?tab=favorites"><i class="fas fa-heart"></i> Favoris</a>
                <div class="user-info">
                    <i class="fas fa-user-circle" style="color:#f97316; font-size:1.2rem;"></i>
                    <a href="Views/dashboard.php" style="text-decoration: none; color: inherit; font-weight: 500;"><?php echo htmlspecialchars($_SESSION['user_prenom'] ?? $_SESSION['user_email']); ?></a>
                    <a href="Views/logout.php" class="btn-outline" style="margin-left:0.5rem;">Déconnexion</a>
                </div>
            <?php else: ?>
                <a href="Views/auth.php" class="btn-outline">Connexion</a>
                <a href="Views/auth.php?register=1" class="btn-primary">Inscription</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- SUBHEADER -->
<div class="subheader">
    <div class="subheader-container">
        <div class="quick-actions">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php?tab=create_ad"><i class="fas fa-camera"></i> Publier une annonce</a>
                <a href="dashboard.php?tab=favorites"><i class="fas fa-heart"></i> Mes favoris</a>
            <?php else: ?>
                <a href="Views/auth.php?register=1"><i class="fas fa-camera"></i> Publier une annonce</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="carousel-title">Les dernières annonces publiées</div>

<!-- CARROUSEL DYNAMIQUE -->
<section class="carousel-section">
    <div class="carousel-container">
        <div class="carousel-slides" id="carouselSlides">
            <?php if(isset($carousel_annonces) && count($carousel_annonces) > 0): ?>
                <?php foreach($carousel_annonces as $annonce): ?>
                    <div class="carousel-slide" onclick="openAdModal(<?php echo $annonce['id']; ?>)" style="cursor:pointer; background-image: linear-gradient(0deg, rgba(0,0,0,0.5), rgba(0,0,0,0.1)), url('<?php 
                        if($annonce['photo'] && file_exists('Views/' . $annonce['photo'])) {
                            echo 'Views/' . $annonce['photo'];
                        } elseif($annonce['photo'] && file_exists($annonce['photo'])) {
                            echo $annonce['photo'];
                        } else {
                            echo 'https://picsum.photos/id/169/1600/500';
                        }
                    ?>');">
                        <div class="slide-overlay">
                            <h2><?php echo htmlspecialchars($annonce['nom_annonce']); ?></h2>
                            <p><?php echo htmlspecialchars(substr($annonce['description'], 0, 100)); ?>...</p>
                            <span class="slide-price"><?php echo number_format($annonce['prix'], 0, ',', ' '); ?> €</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach($default_carousel as $slide): ?>
                    <div class="carousel-slide" style="background-image: linear-gradient(0deg, rgba(0,0,0,0.5), rgba(0,0,0,0.1)), url('<?php echo $slide['image']; ?>');">
                        <div class="slide-overlay">
                            <h2><?php echo $slide['titre']; ?></h2>
                            <p><?php echo $slide['sous_titre']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
        <button class="carousel-btn btn-prev" id="prevBtn"><i class="fas fa-arrow-left"></i></button>
        <button class="carousel-btn btn-next" id="nextBtn"><i class="fas fa-arrow-right"></i></button>
        <div class="dots" id="dotsContainer"></div>
    </div>
</section>

<!-- STATISTIQUES DYNAMIQUES -->
<div class="container">
    <div class="stats-row">
        <div class="stat-item"><h4><?php echo number_format($total_annonces, 0, ',', ' '); ?></h4><p>Annonces actives</p></div>
        <div class="stat-item"><h4><?php echo number_format($total_vendeurs, 0, ',', ' '); ?></h4><p>Nombre de vendeurs</p></div>
        <div class="stat-item"><h4><?php echo number_format($total_favoris, 0, ',', ' '); ?></h4><p>Favoris ajoutés</p></div>
    </div>
</div>

<!-- SECTION FONCTIONNALITÉS PRINCIPALES -->
<div class="container">
    <h2 class="section-title">Votre espace petites annonces, réinventé</h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-lock"></i></div>
            <h3>Compte sécurisé</h3>
            <p>Mots de passe chiffrés, inscription robuste (email + mot de passe 10 caractères minimum).</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-layer-group"></i></div>
            <h3>Gestion d'annonces</h3>
            <p>Création, modification, suppression, photo à la une. Interface intuitive.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-star"></i></div>
            <h3>Favoris malins</h3>
            <p>Ajoutez des annonces à vos favoris et retrouvez-les dans votre tableau de bord.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-comment-dots"></i></div>
            <h3>Messagerie intégrée</h3>
            <p>Échangez avec les autres membres directement sur l'annonce.</p>
        </div>
    </div>
</div>

<!-- FILTRES DE RECHERCHE -->
<div class="container">
    <form method="GET" class="filter-bar">
        <div class="form-group">
            <label><i class="fas fa-search"></i> Recherche</label>
            <input type="text" name="search" placeholder="Mot-clé, titre, description…" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label><i class="fas fa-tag"></i> Catégorie</label>
            <select name="categorie">
                <option value="">Toutes les catégories</option>
                <?php 
                $categories = ['Électronique', 'Maison', 'Sport', 'Vêtements', 'Livres', 'Mode', 'Véhicules', 'Immobilier', 'Services', 'Autre'];
                foreach($categories as $cat): 
                ?>
                    <option value="<?php echo $cat; ?>" <?php echo (($_GET['categorie'] ?? '') == $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label><i class="fas fa-euro-sign"></i> Prix min (€)</label>
            <input type="number" name="prix_min" step="1" min="0" value="<?php echo htmlspecialchars($_GET['prix_min'] ?? ''); ?>" placeholder="0">
        </div>
        <div class="form-group">
            <label><i class="fas fa-euro-sign"></i> Prix max (€)</label>
            <input type="number" name="prix_max" step="1" min="0" value="<?php echo htmlspecialchars($_GET['prix_max'] ?? ''); ?>" placeholder="∞">
        </div>
        <div class="form-group" style="min-width:auto;">
            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Filtrer
            </button>
            <?php if(!empty($_GET['search']) || !empty($_GET['categorie']) || !empty($_GET['prix_min']) || !empty($_GET['prix_max'])): ?>
                <a href="index.php" class="btn-filter" style="background: var(--surface2); color: var(--text); margin-left: 0.5rem;">
                    <i class="fas fa-times"></i> Réinitialiser
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- APERÇU DES ANNONCES -->
<div class="container" id="annonces">
    <h2 class="section-title">
        <?php 
        $hasFilters = !empty($_GET['search']) || !empty($_GET['categorie']) || !empty($_GET['prix_min']) || !empty($_GET['prix_max']);
        if($hasFilters) {
            echo 'Résultats filtrés';
        } else {
            echo isset($_GET['show_all']) ? 'Toutes les annonces' : 'Annonces du moment';
        }
        ?>
        <?php if($hasFilters && isset($recent_annonces)): ?>
            <span style="font-size: 0.8rem; font-weight: 400; color: var(--text2); margin-left: 1rem;">
                (<?php echo count($recent_annonces); ?> résultat<?php echo count($recent_annonces) != 1 ? 's' : ''; ?>)
            </span>
        <?php endif; ?>
    </h2>
    <?php if(isset($recent_annonces) && count($recent_annonces) > 0): ?>
        <div class="cards-preview">
            <?php foreach($recent_annonces as $annonce): ?>
                <div class="annonce-card">
                    <div class="card-img" style="background-image: url('<?php 
                        if($annonce['photo'] && file_exists('Views/' . $annonce['photo'])) {
                            echo 'Views/' . $annonce['photo'];
                        } elseif($annonce['photo'] && file_exists($annonce['photo'])) {
                            echo $annonce['photo'];
                        } else {
                            echo 'https://picsum.photos/id/20/300/190';
                        }
                    ?>');">
                        <div class="card-fav-count">
                            <i class="fas fa-heart"></i> <?php echo $annonce['total_favs']; ?>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="price"><?php echo number_format($annonce['prix'], 0, ',', ' '); ?> €</div>
                        <h3><?php echo htmlspecialchars($annonce['nom_annonce']); ?></h3>
                        <span class="category-badge"><?php echo htmlspecialchars($annonce['categorie'] ?: 'Non catégorisé'); ?></span>
                        <div class="seller-name">
                            <?php
                                $sellerAvatar = $annonce['avatar'] ?? '';
                                if($sellerAvatar && (file_exists($sellerAvatar) || file_exists('Views/' . $sellerAvatar))):
                                    $avatarUrl = file_exists($sellerAvatar) ? $sellerAvatar : 'Views/' . $sellerAvatar;
                            ?>
                                <span class="seller-avatar"><img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar <?php echo htmlspecialchars($annonce['prenom'] . ' ' . $annonce['nom']); ?>"></span>
                            <?php else: ?>
                                <span class="seller-avatar"><i class="fas fa-user"></i></span>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($annonce['prenom'] . ' ' . $annonce['nom']); ?>
                        </div>
                        <button type="button" onclick="openAdModal(<?php echo (int)$annonce['id']; ?>)" style="width: 100%; margin-top: 1rem; border: 1px solid var(--accent); background: var(--surface2); color: var(--text); padding: 0.65rem; border-radius: 60px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem;" onmouseover="this.style.background='var(--accent)'; this.style.color='white';" onmouseout="this.style.background='var(--surface2)'; this.style.color='var(--text)';"><i class="fas fa-eye"></i> Voir détails</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if(!isset($_GET['show_all'])): ?>
        <div style="text-align: center; margin-top: 2rem;">
            <?php
            $showAllUrl = 'index.php?show_all=1';
            if(!empty($_GET['search'])) $showAllUrl .= '&search=' . urlencode($_GET['search']);
            if(!empty($_GET['categorie'])) $showAllUrl .= '&categorie=' . urlencode($_GET['categorie']);
            if(!empty($_GET['prix_min'])) $showAllUrl .= '&prix_min=' . urlencode($_GET['prix_min']);
            if(!empty($_GET['prix_max'])) $showAllUrl .= '&prix_max=' . urlencode($_GET['prix_max']);
            ?>
            <a href="<?php echo $showAllUrl; ?>#annonces" class="btn-view-all">Afficher toutes les annonces →</a>
        </div>
        <?php else: ?>
        <div style="text-align: center; margin-top: 2rem;">
            <?php
            $backUrl = 'index.php';
            if(!empty($_GET['search'])) $backUrl .= '?search=' . urlencode($_GET['search']);
            if(!empty($_GET['categorie'])) $backUrl .= (!empty($_GET['search']) ? '&' : '?') . 'categorie=' . urlencode($_GET['categorie']);
            if(!empty($_GET['prix_min'])) $backUrl .= (strpos($backUrl, '?') !== false ? '&' : '?') . 'prix_min=' . urlencode($_GET['prix_min']);
            if(!empty($_GET['prix_max'])) $backUrl .= (strpos($backUrl, '?') !== false ? '&' : '?') . 'prix_max=' . urlencode($_GET['prix_max']);
            ?>
            <a href="<?php echo $backUrl; ?>#annonces" class="btn-view-all" style="background: var(--surface2); color: var(--text);">Réduire la liste</a>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <p>Aucune annonce pour le moment.</p>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php?tab=create_ad" class="btn-view-all" style="margin-top: 1rem;">Publier la première annonce</a>
            <?php else: ?>
                <a href="Views/auth.php?register=1" class="btn-view-all" style="margin-top: 1rem;">Inscrivez-vous pour publier</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- SECTION RAPPEL DES FONCTIONNALITÉS DU PROJET -->
<div class="container">
    <h2 class="section-title">Fonctionnalités complètes (Projet Leboncoin like)</h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-user-check"></i></div>
            <h3>Inscription / Connexion</h3>
            <p>Session PHP, mot de passe hashé, validation email, sécurité renforcée.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-pen-ruler"></i></div>
            <h3>CRUD annonces</h3>
            <p>Ajouter / modifier / supprimer, photo à la une, formulaire complet.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-sliders-h"></i></div>
            <h3>Filtres dynamiques</h3>
            <p>Prix, catégorie, recherche par mot-clé.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-envelope-open-text"></i></div>
            <h3>Messagerie privée</h3>
            <p>Conversations liées aux annonces, historique complet des échanges.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-heart"></i></div>
            <h3>Système de favoris</h3>
            <p>Ajout / suppression, page dédiée dans le compte utilisateur.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-eye"></i></div>
            <h3>Détail d'annonce</h3>
            <p>Cartes responsives + page détail enrichie avec toutes les infos.</p>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-col">
            <h4>Annoncy</h4>
            <p>La plateforme chaleureuse pour échanger, vendre, donner une seconde vie.</p>
            <div class="social-icons">
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h4>Ressources</h4>
            <a href="#">Comment ça marche</a><br>
            <a href="#">Sécurité & conseils</a><br>
            <a href="dashboard.php?tab=all_ads">Toutes les annonces</a>
        </div>
        <div class="footer-col">
            <h4>Mon espace</h4>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php">Tableau de bord</a><br>
                <a href="dashboard.php?tab=my_ads">Mes annonces</a><br>
                <a href="dashboard.php?tab=favorites">Mes favoris</a><br>
                <a href="dashboard.php?tab=messages">Messagerie</a>
            <?php else: ?>
                <a href="Views/auth.php">Connexion</a><br>
                <a href="Views/auth.php?register=1">Créer un compte</a>
            <?php endif; ?>
        </div>
        <div class="footer-col">
            <h4>Légal</h4>
            <a href="#">CGU</a><br>
            <a href="#">Confidentialité</a><br>
            <a href="#">Cookies</a>
        </div>
    </div>
    <div class="copyright">
        © 2026 Annoncy – Projet web PHP/MySQL – <?php echo number_format($total_annonces, 0, ',', ' '); ?> annonces publiées
    </div>
</footer>

<!-- SCRIPT CARROUSEL -->
<script>
    const slides = document.querySelectorAll('.carousel-slide');
    const slidesContainer = document.getElementById('carouselSlides');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const dotsContainer = document.getElementById('dotsContainer');
    let currentIdx = 0;
    const total = slides.length;
    let interval;

    function buildDots() {
        if(!dotsContainer) return;
        dotsContainer.innerHTML = '';
        for (let i = 0; i < total; i++) {
            const dot = document.createElement('div');
            dot.classList.add('dot');
            if (i === currentIdx) dot.classList.add('active');
            dot.addEventListener('click', () => goToSlide(i));
            dotsContainer.appendChild(dot);
        }
    }

    function updateDotsActive() {
        document.querySelectorAll('.dot').forEach((dot, i) => {
            i === currentIdx ? dot.classList.add('active') : dot.classList.remove('active');
        });
    }

    function goToSlide(index) {
        if (index < 0) index = 0;
        if (index >= total) index = total - 1;
        currentIdx = index;
        slidesContainer.style.transform = `translateX(-${currentIdx * 100}%)`;
        updateDotsActive();
        resetAuto();
    }

    function next() { goToSlide((currentIdx + 1) % total); }
    function prev() { goToSlide((currentIdx - 1 + total) % total); }

    function startAuto() { if(total > 1) interval = setInterval(next, 5500); }
    function resetAuto() { if (interval) clearInterval(interval); startAuto(); }

    if(prevBtn && nextBtn) {
        prevBtn.addEventListener('click', () => { prev(); resetAuto(); });
        nextBtn.addEventListener('click', () => { next(); resetAuto(); });
    }

    if(total > 0) {
        buildDots();
        goToSlide(0);
        startAuto();
    }
</script>
<!-- MODAL ANNONCE -->
<div class="modal" id="adModal">
    <div class="modal-body">
        <button class="modal-close" onclick="closeAdModal()"><i class="fas fa-times"></i></button>
        <div class="modal-loader" id="adModalLoader" style="display:none;">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
        <div id="adModalContent" style="display:none;">
            <div id="modalAdPhotoBox"></div>
            <div class="modal-content-inner">
                <div class="modal-header-info">
                    <h2 class="modal-title" id="modalAdTitle">Titre de l'annonce</h2>
                    <div class="modal-price" id="modalAdPrice">0 €</div>
                </div>
                <div class="modal-meta">
                    <span class="category-badge" id="modalAdCat">Catégorie</span>
                    <div class="seller-name" id="modalAdSeller"><i class="fas fa-user"></i> Vendeur</div>
                </div>
                <div class="modal-desc" id="modalAdDesc">Description détaillée...</div>
                
                <div class="modal-action" style="margin-top:2rem; text-align:right;">
                    <a href="#" id="modalAdLink" class="btn-primary">Voir tous les détails et contacter</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openAdModal(id) {
    console.log("Ouverture modal pour l'annonce:", id);
    try {
        const modal = document.getElementById('adModal');
        const content = document.getElementById('adModalContent');
        const loader = document.getElementById('adModalLoader');
        
        if (!modal || !content || !loader) {
            console.error("Éléments de modal manquants");
            alert("Erreur: Éléments de la modal introuvables");
            return;
        }
        
        // On affiche immédiatement la modal (loader)
        modal.classList.add('active');
        content.style.display = 'none';
        loader.style.display = 'block';
        
        console.log("Appel AJAX vers: ajax_annonce_detail.php?id=" + id);
        
        fetch('ajax_annonce_detail.php?id=' + id)
            .then(res => {
                console.log("Réponse reçue, status:", res.status);
                if (!res.ok) {
                    throw new Error('HTTP error! status: ' + res.status);
                }
                return res.json();
            })
            .then(data => {
                console.log("Données reçues:", data);
                loader.style.display = 'none';
                
                if(data.success && data.annonce) {
                    const a = data.annonce;
                    content.style.display = 'block';
                    
                    // Photo
                    let photoHtml = '';
                    if(a && a.photo && typeof a.photo === 'string') {
                        let photoPath = a.photo;
                        if (!photoPath.startsWith('http') && !photoPath.startsWith('Views/')) {
                            photoPath = 'Views/' + photoPath;
                        }
                        photoHtml = `<img src="${photoPath}" alt="${a.nom_annonce || 'Annonce'}" style="width:100%; height:400px; object-fit:cover; border-bottom:1px solid var(--border);" onerror="this.onerror=null; this.src='https://picsum.photos/id/20/800/400'">`;
                    } else {
                        photoHtml = `<img src="https://picsum.photos/id/20/800/400" alt="Pas de photo" style="width:100%; height:400px; object-fit:cover; border-bottom:1px solid var(--border);">`;
                    }
                    
                    document.getElementById('modalAdPhotoBox').innerHTML = photoHtml;
                    document.getElementById('modalAdTitle').innerText = a.nom_annonce || 'Sans titre';
                    document.getElementById('modalAdPrice').innerText = new Intl.NumberFormat('fr-FR').format(a.prix || 0) + ' €';
                    document.getElementById('modalAdDesc').innerText = a.description || '';
                    document.getElementById('modalAdCat').innerText = a.categorie || 'Non catégorisé';
                    
                    let sellerHtml = '';
                    let avatarSrc = a.seller_avatar;
                    if (avatarSrc && !avatarSrc.startsWith('Views/')) {
                        avatarSrc = 'Views/' + avatarSrc;
                    }
                    if(a.seller_avatar && typeof a.seller_avatar === 'string') {
                        sellerHtml = `<span class="seller-avatar"><img src="${avatarSrc}" alt="Avatar ${a.prenom || ''} ${a.nom || ''}"></span> ${a.prenom || ''} ${a.nom || ''}`;
                    } else {
                        sellerHtml = `<span class="seller-avatar"><i class="fas fa-user"></i></span> ${a.prenom || ''} ${a.nom || ''}`;
                    }
                    document.getElementById('modalAdSeller').innerHTML = sellerHtml;
                    
                    const modalLink = document.getElementById('modalAdLink');
                    if (modalLink) {
                        modalLink.href = <?php echo isset($_SESSION['user_id']) ? "'dashboard.php?tab=all_ads&detail=' + a.id" : "'Views/auth.php'"; ?>;
                    }
                    console.log("Modal remplie avec succès");
                } else {
                    content.innerHTML = `<div style="padding:2rem; color:var(--danger)"><p>Erreur: ${data.error || 'Impossible de charger l\'annonce'}</p></div>`;
                    content.style.display = 'block';
                    console.error("Erreur de données:", data.error);
                }
            })
            .catch(err => {
                console.error("Erreur fetch détaillée:", err);
                loader.style.display = 'none';
                content.style.display = 'block';
                content.innerHTML = `<div style="padding:2rem; color:var(--danger)"><p>Erreur lors de la récupération des détails.</p><pre style="color:var(--text3); font-size:0.8rem; margin-top:1rem;">${err.message}</pre></div>`;
            });
    } catch(err) {
        console.error("Erreur générale:", err);
        alert("Erreur lors de l'ouverture: " + err.message);
    }
}

function closeAdModal() {
    const modal = document.getElementById('adModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Close on outside click
window.addEventListener('click', (e) => {
    const modal = document.getElementById('adModal');
    if(modal && e.target === modal) {
        closeAdModal();
    }
});
</script>
</body>
</html>






