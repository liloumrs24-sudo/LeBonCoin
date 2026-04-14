<?php
// dashboard.php - Tableau de bord utilisateur avec gestion complète des favoris
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
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
    die("Erreur de connexion : " . $e->getMessage());
}

// Ajouter la colonne avatar si elle n'existe pas encore
$columnCheck = $pdo->query("SHOW COLUMNS FROM utilisateur LIKE 'avatar'");
if(!$columnCheck->fetch()) {
    $pdo->exec("ALTER TABLE utilisateur ADD COLUMN avatar VARCHAR(255) NULL DEFAULT NULL");
}

$user_id = $_SESSION['user_id'];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$message = '';
$error = '';

// ========== RÉCUPÉRATION DU PROFIL UTILISATEUR ==========
$stmt = $pdo->prepare("SELECT nom, prenom, email, sexe, animal_prefere, date_de_naissance, avatar FROM utilisateur WHERE id_letim = ?");
$stmt->execute([$user_id]);
$user_profile = $stmt->fetch(PDO::FETCH_ASSOC);

// ========== MISE À JOUR DU PROFIL ==========
if(isset($_POST['update_profile'])) {
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $date_de_naissance = trim($_POST['date_de_naissance']);
    $sexe = isset($_POST['sexe']) ? $_POST['sexe'] : null;
    $animal_prefere = htmlspecialchars(trim($_POST['animal_prefere']));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $avatar_path = $user_profile['avatar'] ?? '';

    if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)) {
            $new_filename = uniqid('avatar_') . '.' . $ext;
            $upload_path = 'uploads/avatars/' . $new_filename;
            if(!file_exists('uploads/avatars/')) {
                mkdir('uploads/avatars/', 0777, true);
            }
            if(move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                if($avatar_path && file_exists($avatar_path)) {
                    unlink($avatar_path);
                }
                $avatar_path = $upload_path;
            }
        }
    }

    if(empty($nom) || empty($prenom) || empty($email)) {
        $error = "Veuillez remplir les champs Nom, Prénom et Email.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } elseif(!empty($password) && strlen($password) < 10) {
        $error = "Le mot de passe doit contenir au moins 10 caractères.";
    } elseif(!empty($password) && $password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $stmt = $pdo->prepare("SELECT id_letim FROM utilisateur WHERE email = ? AND id_letim != ?");
        $stmt->execute([$email, $user_id]);
        if($stmt->fetch()) {
            $error = "Cet email est déjà utilisé par un autre compte.";
        } else {
            if(!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE utilisateur SET nom = ?, prenom = ?, email = ?, sexe = ?, animal_prefere = ?, date_de_naissance = ?, password = ?, avatar = ? WHERE id_letim = ?");
                $success = $stmt->execute([$nom, $prenom, $email, $sexe, $animal_prefere, $date_de_naissance, $hashed_password, $avatar_path, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE utilisateur SET nom = ?, prenom = ?, email = ?, sexe = ?, animal_prefere = ?, date_de_naissance = ?, avatar = ? WHERE id_letim = ?");
                $success = $stmt->execute([$nom, $prenom, $email, $sexe, $animal_prefere, $date_de_naissance, $avatar_path, $user_id]);
            }

            if($success) {
                $message = "Profil mis à jour avec succès !";
                $_SESSION['user_prenom'] = $prenom;
                $_SESSION['user_nom'] = $nom;
                $_SESSION['user_email'] = $email;
                $stmt = $pdo->prepare("SELECT nom, prenom, email, sexe, animal_prefere, date_de_naissance, avatar FROM utilisateur WHERE id_letim = ?");
                $stmt->execute([$user_id]);
                $user_profile = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Erreur lors de la mise à jour du profil.";
            }
        }
    }
}

// ========== CRÉER UNE ANNONCE ==========
if(isset($_POST['create_annonce'])) {
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prix = floatval($_POST['prix']);
    $description = htmlspecialchars(trim($_POST['description']));
    $categorie = htmlspecialchars(trim($_POST['categorie']));
    $photo_path = '';
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = 'uploads/annonces/' . $new_filename;
            if(!file_exists('uploads/annonces/')) mkdir('uploads/annonces/', 0777, true);
            if(move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) $photo_path = $upload_path;
        }
    }
    if(empty($nom) || empty($prix) || empty($description)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO annonces (user_id, nom_annonce, prix, description, categorie, photo, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if($stmt->execute([$user_id, $nom, $prix, $description, $categorie, $photo_path])) {
            $message = "Annonce créée avec succès !";
        } else {
            $error = "Erreur lors de la création de l'annonce.";
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
                    if($photo_path && file_exists($photo_path)) unlink($photo_path);
                    $photo_path = $upload_path;
                }
            }
        }
        $stmt = $pdo->prepare("UPDATE annonces SET nom_annonce = ?, prix = ?, description = ?, categorie = ?, photo = ? WHERE id = ? AND user_id = ?");
        if($stmt->execute([$nom, $prix, $description, $categorie, $photo_path, $annonce_id, $user_id])) {
            $message = "Annonce modifiée avec succès !";
        } else {
            $error = "Erreur lors de la modification.";
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
        if($annonce['photo'] && file_exists($annonce['photo'])) unlink($annonce['photo']);
        $stmt = $pdo->prepare("DELETE FROM favoris WHERE annonce_id = ?");
        $stmt->execute([$annonce_id]);
        $stmt = $pdo->prepare("DELETE FROM messages WHERE annonce_id = ?");
        $stmt->execute([$annonce_id]);
        $stmt = $pdo->prepare("DELETE FROM annonces WHERE id = ? AND user_id = ?");
        $stmt->execute([$annonce_id, $user_id]);
        $message = "Annonce supprimée avec succès !";
    }
    header("Location: dashboard.php?tab=my_ads");
    exit();
}

// ========== ENVOYER UN MESSAGE ==========
if(isset($_POST['send_message'])) {
    $annonce_id = $_POST['annonce_id'];
    $receiver_id = isset($_POST['receiver_id']) && $_POST['receiver_id'] !== '' ? intval($_POST['receiver_id']) : null;
    $contenu = htmlspecialchars(trim($_POST['message']));
    $parent_id = isset($_POST['parent_message_id']) ? $_POST['parent_message_id'] : null;
    $type = isset($_POST['message_type']) ? $_POST['message_type'] : 'message';
    
    // Générer un sujet standardisé pour toutes les conversations de cette annonce
    $stmt = $pdo->prepare("SELECT nom_annonce FROM annonces WHERE id = ?");
    $stmt->execute([$annonce_id]);
    $annonce = $stmt->fetch();
    $sujet = "Conversation concernant : " . ($annonce ? $annonce['nom_annonce'] : "Annonce #" . $annonce_id);
    
    // Gestion des pièces jointes - SUPPRIMÉ
    $piece_jointe = null;
    
    if(empty($receiver_id)) {
        $error = "Destinataire introuvable pour ce message.";
    } elseif(empty($contenu)) {
        $error = "Le message ne peut pas être vide.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO messages (annonce_id, sender_id, receiver_id, contenu, parent_message_id, sujet, type, piece_jointe, statut, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'envoye', NOW(), NOW())");
        if($stmt->execute([$annonce_id, $user_id, $receiver_id, $contenu, $parent_id, $sujet, $type, $piece_jointe])) {
            $message = "Message envoyé avec succès !";
        } else {
            $error = "Erreur lors de l'envoi du message.";
        }
    }
}

// ========== RÉCUPÉRATION DES DONNÉES ==========
$stmt = $pdo->prepare("SELECT * FROM annonces WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$user_annonces = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT a.*, f.created_at as fav_date, u.prenom, u.nom, u.avatar as seller_avatar,
    (SELECT COUNT(*) FROM favoris WHERE annonce_id = a.id) as total_favs
    FROM annonces a 
    INNER JOIN favoris f ON a.id = f.annonce_id 
    INNER JOIN utilisateur u ON a.user_id = u.id_letim
    WHERE f.user_id = ? 
    ORDER BY f.created_at DESC
");
$stmt->execute([$user_id]);
$favoris = $stmt->fetchAll();

// ========== RÉCUPÉRATION DES CONVERSATIONS ==========
$stmt = $pdo->prepare("
    SELECT conv.annonce_id,
           (SELECT sujet FROM messages WHERE annonce_id = conv.annonce_id AND (sender_id = ? OR receiver_id = ?) AND statut != 'supprime' ORDER BY created_at DESC LIMIT 1) AS sujet,
           a.nom_annonce,
           (SELECT MAX(created_at) FROM messages WHERE annonce_id = conv.annonce_id AND statut != 'supprime') AS last_message_date,
           (SELECT COUNT(*) FROM messages WHERE annonce_id = conv.annonce_id AND statut != 'supprime' AND receiver_id = ? AND lu = 0) AS unread_count,
           (SELECT COUNT(*) FROM messages WHERE annonce_id = conv.annonce_id AND statut != 'supprime') AS total_messages,
           (SELECT contenu FROM messages WHERE annonce_id = conv.annonce_id AND statut != 'supprime' ORDER BY created_at DESC LIMIT 1) AS last_message,
           (SELECT prenom FROM utilisateur WHERE id_letim = (SELECT sender_id FROM messages WHERE annonce_id = conv.annonce_id AND statut != 'supprime' ORDER BY created_at DESC LIMIT 1)) AS last_sender_prenom,
           (SELECT nom FROM utilisateur WHERE id_letim = (SELECT sender_id FROM messages WHERE annonce_id = conv.annonce_id AND statut != 'supprime' ORDER BY created_at DESC LIMIT 1)) AS last_sender_nom
    FROM (
        SELECT DISTINCT annonce_id
        FROM messages
        WHERE (sender_id = ? OR receiver_id = ?) AND statut != 'supprime'
    ) conv
    INNER JOIN annonces a ON conv.annonce_id = a.id
    ORDER BY last_message_date DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll();

// ========== RÉCUPÉRATION DES MESSAGES D'UNE CONVERSATION SPÉCIFIQUE ==========
$conversation_messages = [];
$current_conversation = null;
if(isset($_GET['conversation'])) {
    $conversation_id = $_GET['conversation'];
    $current_conversation = $conversation_id;
    
    $stmt = $pdo->prepare("
        SELECT m.*, 
               u.prenom as sender_prenom, u.nom as sender_nom,
               r.prenom as receiver_prenom, r.nom as receiver_nom,
               a.nom_annonce
        FROM messages m 
        INNER JOIN utilisateur u ON m.sender_id = u.id_letim 
        LEFT JOIN utilisateur r ON m.receiver_id = r.id_letim 
        INNER JOIN annonces a ON m.annonce_id = a.id 
        WHERE m.annonce_id = ? AND (m.sender_id = ? OR m.receiver_id = ?) AND m.statut != 'supprime'
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversation_id, $user_id, $user_id]);
    $conversation_messages = $stmt->fetchAll();
    
    // Marquer comme lus
    $stmt = $pdo->prepare("UPDATE messages SET lu = 1, statut = 'lu' WHERE annonce_id = ? AND receiver_id = ? AND lu = 0");
    $stmt->execute([$conversation_id, $user_id]);
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND lu = 0 AND statut != 'supprime'");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT annonce_id FROM favoris WHERE user_id = ?");
$stmt->execute([$user_id]);
$favorite_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord — Le Bon Coin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            --radius: 16px;
            --radius-sm: 10px;
            --shadow: 0 4px 24px rgba(0,0,0,0.4);
            --shadow-lg: 0 12px 48px rgba(0,0,0,0.5);
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--surface2); border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent); }

        /* ── Background mesh ── */
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

        /* ══════════════════════════════
           HEADER
        ══════════════════════════════ */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            height: 64px;
            background: rgba(13,14,18,0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
        }

        .topbar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--text);
            text-decoration: none;
        }

        .topbar-logo .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: white;
            box-shadow: 0 0 16px var(--accent-glow);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 0.4rem 1rem 0.4rem 0.5rem;
            border-radius: 99px;
            font-size: 0.85rem;
            color: var(--text2);
        }

        .user-avatar {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            color: white;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .seller-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--surface);
            border: 1px solid var(--border);
            flex-shrink: 0;
        }

        .seller-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .user-chip strong { color: var(--text); font-weight: 500; }

        .logout-link {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 1rem;
            background: rgba(255,77,109,0.1);
            color: var(--danger);
            border-radius: 99px;
            font-size: 0.82rem;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid rgba(255,77,109,0.2);
            transition: all 0.2s;
        }
        .logout-link:hover { background: rgba(255,77,109,0.2); }

        /* ══════════════════════════════
           LAYOUT
        ══════════════════════════════ */
        .layout {
            display: flex;
            min-height: calc(100vh - 64px);
            position: relative;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: 240px;
            flex-shrink: 0;
            background: var(--bg2);
            border-right: 1px solid var(--border);
            padding: 1.5rem 0.75rem;
            position: sticky;
            top: 64px;
            height: calc(100vh - 64px);
            overflow-y: auto;
        }

        .nav-section-label {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text3);
            padding: 0.5rem 0.75rem 0.5rem;
            margin-top: 0.5rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.75rem;
            border-radius: var(--radius-sm);
            color: var(--text2);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 400;
            transition: all 0.18s;
            position: relative;
            margin-bottom: 2px;
        }

        .nav-item:hover {
            background: var(--surface);
            color: var(--text);
        }

        .nav-item.active {
            background: var(--accent-soft);
            color: var(--accent);
            font-weight: 500;
        }

        .nav-item .nav-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            background: var(--surface);
            transition: all 0.18s;
        }

        .nav-item.active .nav-icon {
            background: var(--accent);
            color: white;
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .nav-badge {
            margin-left: auto;
            background: var(--accent);
            color: white;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 99px;
            min-width: 20px;
            text-align: center;
        }

        .nav-badge.unread { background: var(--danger); }

        /* ── Main ── */
        .main {
            flex: 1;
            padding: 2rem;
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        /* ══════════════════════════════
           ALERTS
        ══════════════════════════════ */
        .alert {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.9rem 1.2rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            animation: slideDown 0.3s ease;
        }
        .alert-success { background: rgba(34,211,165,0.1); border: 1px solid rgba(34,211,165,0.25); color: var(--success); }
        .alert-error   { background: rgba(255,77,109,0.1); border: 1px solid rgba(255,77,109,0.25); color: var(--danger); }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ══════════════════════════════
           PAGE HEADER
        ══════════════════════════════ */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text);
        }

        .page-header p {
            color: var(--text3);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* ══════════════════════════════
           STATS GRID
        ══════════════════════════════ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.25s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--accent-soft), transparent);
            opacity: 0;
            transition: opacity 0.25s;
        }

        .stat-card:hover { transform: translateY(-3px); border-color: var(--border2); box-shadow: var(--shadow); }
        .stat-card:hover::after { opacity: 1; }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--accent-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text3);
        }

        /* ══════════════════════════════
           SECTION CARD
        ══════════════════════════════ */
        .section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.75rem;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .section-title i {
            color: var(--accent);
            font-size: 0.9rem;
        }

        .section-title .count-pill {
            margin-left: auto;
            background: var(--accent);
            color: white;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 9px;
            border-radius: 99px;
        }

        /* ══════════════════════════════
           FORM STYLES
        ══════════════════════════════ */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-grid .full { grid-column: 1 / -1; }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-group label {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text2);
            letter-spacing: 0.02em;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            background: var(--bg3);
            border: 1px solid var(--border2);
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            color: var(--text);
            transition: all 0.2s;
            outline: none;
            -webkit-appearance: none;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder { color: var(--text3); }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .form-group select option { background: var(--bg3); }

        .form-group textarea {
            resize: vertical;
            min-height: 110px;
        }

        /* File input */
        .file-input-wrapper {
            background: var(--bg3);
            border: 1px dashed var(--border2);
            border-radius: var(--radius-sm);
            padding: 1.2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text3);
            font-size: 0.85rem;
        }

        .file-input-wrapper:hover { border-color: var(--accent); color: var(--accent); }
        .file-input-wrapper input { display: none; }
        .file-input-wrapper i { display: block; font-size: 1.5rem; margin-bottom: 0.4rem; }

        /* ══════════════════════════════
           BUTTONS
        ══════════════════════════════ */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.65rem 1.3rem;
            border-radius: var(--radius-sm);
            border: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #e55a28);
            color: white;
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255,107,53,0.4);
        }

        .btn-ghost {
            background: var(--surface2);
            color: var(--text2);
            border: 1px solid var(--border);
        }

        .btn-ghost:hover { background: var(--bg3); color: var(--text); }

        .btn-danger {
            background: rgba(255,77,109,0.12);
            color: var(--danger);
            border: 1px solid rgba(255,77,109,0.2);
        }

        .btn-danger:hover { background: rgba(255,77,109,0.22); }

        .btn-fav {
            background: rgba(255,77,109,0.08);
            color: var(--danger);
            border: 1px solid rgba(255,77,109,0.15);
        }

        .btn-fav.active {
            background: var(--danger);
            color: white;
            border-color: var(--danger);
        }

        .btn-fav:hover { background: rgba(255,77,109,0.2); }
        .btn-fav.active:hover { background: #e0304d; }

        .btn-sm { padding: 0.45rem 0.85rem; font-size: 0.78rem; border-radius: 8px; }

        /* ══════════════════════════════
           ADS GRID
        ══════════════════════════════ */
        .ads-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 1.25rem;
        }

        .ad-card {
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            transition: all 0.25s;
            position: relative;
        }

        .ad-card:hover {
            border-color: var(--border2);
            transform: translateY(-4px);
            box-shadow: var(--shadow);
        }

        .ad-thumb {
            height: 180px;
            background: var(--surface);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ad-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
        }

        .ad-card:hover .ad-thumb img { transform: scale(1.04); }

        .ad-thumb .no-img {
            color: var(--surface2);
            font-size: 2.5rem;
        }

        .ad-thumb-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 50%, rgba(0,0,0,0.6));
            opacity: 0;
            transition: opacity 0.3s;
        }

        .ad-card:hover .ad-thumb-overlay { opacity: 1; }

        .fav-counter {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(6px);
            color: white;
            border-radius: 99px;
            padding: 3px 8px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            gap: 4px;
            z-index: 2;
        }

        .fav-counter i { color: var(--danger); }

        .fav-pill {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--danger);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            cursor: pointer;
            z-index: 3;
            box-shadow: 0 3px 8px rgba(255,77,109,0.4);
            transition: transform 0.2s;
        }

        .fav-pill:hover { transform: scale(1.15); }

        .ad-body { padding: 1rem; }

        .ad-category-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.7rem;
            color: var(--text3);
            background: var(--surface2);
            padding: 3px 8px;
            border-radius: 99px;
            margin-bottom: 0.6rem;
            font-weight: 500;
        }

        .ad-title {
            font-family: 'Syne', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text);
            margin-bottom: 0.4rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ad-price {
            font-family: 'Syne', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 0.75rem;
        }

        .ad-meta {
            font-size: 0.75rem;
            color: var(--text3);
            margin-bottom: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .ad-actions {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        /* ══════════════════════════════
           MESSAGES
        ══════════════════════════════ */
        .msg-list { display: flex; flex-direction: column; gap: 0.75rem; }

        .msg-item {
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 1rem 1.25rem;
            cursor: pointer;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .msg-item:hover { transform: translateX(4px); border-color: var(--border2); }
        .msg-item.unread { border-left-color: var(--accent); background: rgba(255,107,53,0.04); }
        .msg-item.active { border-left-color: var(--accent); background: rgba(255,107,53,0.08); }

        .msg-meta { display: flex; justify-content: space-between; margin-bottom: 0.4rem; }
        .msg-sender { font-weight: 600; font-size: 0.875rem; color: var(--text); }
        .msg-date { font-size: 0.75rem; color: var(--text3); }
        .msg-preview { font-size: 0.85rem; color: var(--text2); margin-bottom: 0.4rem; }
        .msg-tag { font-size: 0.75rem; color: var(--accent); display: flex; align-items: center; gap: 4px; }

        .unread-badge {
            background: var(--accent);
            color: white;
            border-radius: 99px;
            padding: 0.2rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: auto;
        }

        /* ══════════════════════════════
           MESSAGES LAYOUT
        ══════════════════════════════ */
        .messages-layout {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1.5rem;
            min-height: 600px;
        }

        .conversations-list { grid-column: 1; }
        .conversation-view { grid-column: 2; }

        .conversation-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        .conversation-header h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem;
            margin: 0;
        }

        .conversation-actions { display: flex; gap: 0.5rem; }

        .messages-thread {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 0;
            max-height: 400px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            padding: 1rem;
            border-radius: var(--radius-sm);
            max-width: 80%;
        }

        .message.sent {
            background: var(--accent);
            color: white;
            align-self: flex-end;
            margin-left: auto;
        }

        .message.received {
            background: var(--bg3);
            border: 1px solid var(--border);
            align-self: flex-start;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
        }

        .message.sent .message-header { color: rgba(255,255,255,0.8); }
        .message.received .message-header { color: var(--text3); }

        .message-content {
            line-height: 1.4;
            word-wrap: break-word;
        }

        .message-type {
            margin-top: 0.5rem;
            font-size: 0.75rem;
            font-style: italic;
            opacity: 0.8;
        }

        .message-form {
            border-top: 1px solid var(--border);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .message-form .form-group { margin-bottom: 1rem; }

        .message-form textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg2);
            color: var(--text);
            resize: vertical;
            font-family: inherit;
        }

        .form-row {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .file-label {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text2);
        }

        .file-label:hover {
            background: var(--surface2);
            border-color: var(--border2);
        }

        .file-label input[type="file"] { display: none; }

        .file-info {
            display: block;
            font-size: 0.75rem;
            color: var(--text3);
            margin-top: 0.25rem;
        }

        .empty-conversation {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text3);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .empty-conversation .empty-icon {
            width: 72px;
            height: 72px;
            background: var(--surface2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: var(--text3);
            margin: 0 auto 1.25rem;
        }

        .empty-conversation h3 {
            font-family: 'Syne', sans-serif;
            color: var(--text2);
            margin-bottom: 0.4rem;
        }

        .empty-conversation p {
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
        }

        /* ══════════════════════════════
           FILTER BAR
        ══════════════════════════════ */
        .filter-bar {
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-bar .form-group { flex: 1; min-width: 130px; margin-bottom: 0; }

        /* ══════════════════════════════
           EMPTY STATE
        ══════════════════════════════ */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text3);
        }

        .empty-icon {
            width: 72px;
            height: 72px;
            background: var(--surface2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: var(--text3);
            margin: 0 auto 1.25rem;
        }

        .empty-state h3 { font-family: 'Syne', sans-serif; color: var(--text2); margin-bottom: 0.4rem; }
        .empty-state p  { font-size: 0.875rem; margin-bottom: 1.25rem; }

        /* ══════════════════════════════
           MODALS
        ══════════════════════════════ */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(4px);
            z-index: 500;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-overlay.active { display: flex; }

        .modal-box {
            background: var(--bg2);
            border: 1px solid var(--border2);
            border-radius: 20px;
            padding: 2rem;
            width: 100%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .modal-box.wide { max-width: 620px; }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.92) translateY(20px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .modal-close {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: var(--surface);
            border: none;
            color: var(--text2);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .modal-close:hover { background: rgba(255,77,109,0.15); color: var(--danger); }

        .modal-footer {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
        }

        /* ══════════════════════════════
           TOAST
        ══════════════════════════════ */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--bg2);
            border: 1px solid var(--border2);
            border-radius: var(--radius-sm);
            padding: 0.85rem 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.85rem;
            font-weight: 500;
            z-index: 9999;
            box-shadow: var(--shadow-lg);
            animation: toastIn 0.3s ease;
            min-width: 240px;
        }

        @keyframes toastIn {
            from { opacity: 0; transform: translateY(16px) scale(0.96); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .toast.success { border-left: 3px solid var(--success); }
        .toast.info    { border-left: 3px solid var(--info); }
        .toast.error   { border-left: 3px solid var(--danger); }

        /* ══════════════════════════════
           LOADING SPINNER
        ══════════════════════════════ */
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
            display: inline-block;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ══════════════════════════════
           MOBILE SIDEBAR TOGGLE
        ══════════════════════════════ */
        .mobile-nav-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text2);
            cursor: pointer;
            font-size: 0.9rem;
        }

        /* ══════════════════════════════
           DETAIL IMAGE
        ══════════════════════════════ */
        .detail-img-wrap {
            width: 100%;
            height: 200px;
            background: var(--surface);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .detail-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .detail-row {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            margin-bottom: 0.65rem;
            font-size: 0.875rem;
        }

        .detail-row strong { color: var(--text2); min-width: 100px; }
        .detail-row span   { color: var(--text); }

        .detail-desc {
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 1rem;
            font-size: 0.875rem;
            color: var(--text2);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        /* ══════════════════════════════
           RESPONSIVE
        ══════════════════════════════ */
        @media (max-width: 900px) {
            .sidebar {
                position: fixed;
                left: -260px;
                top: 64px;
                height: calc(100vh - 64px);
                z-index: 200;
                transition: left 0.3s cubic-bezier(0.4,0,0.2,1);
                box-shadow: var(--shadow-lg);
            }
            .sidebar.open { left: 0; }
            .mobile-nav-toggle { display: flex; }
            .main { padding: 1.25rem; }
            .form-grid { grid-template-columns: 1fr; }
            .ads-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
        }

        @media (max-width: 600px) {
            .topbar { padding: 0 1rem; }
            .user-chip { display: none; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .filter-bar .form-group { min-width: 100%; }
            .ads-grid { grid-template-columns: 1fr; }
            .modal-box { padding: 1.5rem; }
        }

        /* Sidebar overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 150;
            top: 64px;
        }

        .sidebar-overlay.active { display: block; }
    </style>
</head>
<body>

<!-- ══════ TOPBAR ══════ -->
<header class="topbar">
    <div style="display:flex;align-items:center;gap:1rem;">
        <button class="mobile-nav-toggle" onclick="toggleSidebar()" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </button>
        <a href="#" class="topbar-logo">
            <div class="logo-icon"><i class="fas fa-handshake"></i></div>
            Le Bon Coin
        </a>
    </div>
    <div class="topbar-right">
        <div class="user-chip">
            <div class="user-avatar">
                <?php if(!empty($user_profile['avatar']) && file_exists($user_profile['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($user_profile['avatar']); ?>" alt="Avatar de <?php echo htmlspecialchars($_SESSION['user_prenom']); ?>">
                <?php else: ?>
                    <?php echo strtoupper(substr($_SESSION['user_prenom'], 0, 1) . substr($_SESSION['user_nom'], 0, 1)); ?>
                <?php endif; ?>
            </div>
            <strong><?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?></strong>
        </div>
        <a href="logout.php" class="logout-link"><i class="fas fa-arrow-right-from-bracket"></i> Déconnexion</a>
    </div>
</header>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="layout">

    <!-- ══════ SIDEBAR ══════ -->
    <aside class="sidebar" id="sidebar">
        <div class="nav-section-label">Navigation</div>
        <a href="?tab=dashboard" class="nav-item <?php echo $active_tab=='dashboard'?'active':''; ?>">
            <span class="nav-icon"><i class="fas fa-house"></i></span>Tableau de bord
        </a>
        <a href="?tab=profile" class="nav-item <?php echo $active_tab=='profile'?'active':''; ?>">
            <span class="nav-icon"><i class="fas fa-user"></i></span>Mon profil
        </a>
        <a href="?tab=create_ad" class="nav-item <?php echo $active_tab=='create_ad'?'active':''; ?>">
            <span class="nav-icon"><i class="fas fa-plus"></i></span>Créer une annonce
        </a>

        <div class="nav-section-label" style="margin-top:1rem;">Mes contenus</div>
        <a href="?tab=my_ads" class="nav-item <?php echo $active_tab=='my_ads'?'active':''; ?>">
            <span class="nav-icon"><i class="fas fa-rectangle-list"></i></span>Mes annonces
            <?php if(count($user_annonces)): ?><span class="nav-badge fav-count-nav"><?php echo count($user_annonces); ?></span><?php endif; ?>
        </a>
        <a href="?tab=favorites" class="nav-item <?php echo $active_tab=='favorites'?'active':''; ?>">
            <span class="nav-icon"><i class="fas fa-heart"></i></span>Mes favoris
            <span class="nav-badge fav-count"><?php echo count($favoris); ?></span>
        </a>
        <a href="?tab=messages" class="nav-item <?php echo $active_tab=='messages'?'active':''; ?>">
            <span class="nav-icon"><i class="fas fa-envelope"></i></span>Messages
            <?php if($unread_count > 0): ?><span class="nav-badge unread"><?php echo $unread_count; ?></span><?php endif; ?>
        </a>

        <div class="nav-section-label" style="margin-top:1rem;">Explorer</div>
        <a href="?tab=all_ads" class="nav-item <?php echo $active_tab=='all_ads'?'active':''; ?>">
            <span class="nav-icon"><i class="fas fa-magnifying-glass"></i></span>Toutes les annonces
        </a>
    </aside>

    <!-- ══════ MAIN ══════ -->
    <main class="main">

        <?php if($message): ?>
            <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- ════ DASHBOARD ════ -->
        <?php if($active_tab == 'dashboard'): ?>
            <div class="page-header">
                <h1>Bonjour, <?php echo htmlspecialchars($_SESSION['user_prenom']); ?> 👋</h1>
                <p>Voici un aperçu de votre activité sur Le Bon Coin.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card" onclick="window.location.href='?tab=my_ads'">
                    <div class="stat-icon"><i class="fas fa-rectangle-list"></i></div>
                    <div class="stat-value"><?php echo count($user_annonces); ?></div>
                    <div class="stat-label">Mes annonces</div>
                </div>
                <div class="stat-card" onclick="window.location.href='?tab=favorites'">
                    <div class="stat-icon" style="background:rgba(255,77,109,0.12);color:var(--danger)"><i class="fas fa-heart"></i></div>
                    <div class="stat-value fav-count"><?php echo count($favoris); ?></div>
                    <div class="stat-label">Favoris sauvegardés</div>
                </div>
                <div class="stat-card" onclick="window.location.href='?tab=messages'">
                    <div class="stat-icon" style="background:rgba(77,159,255,0.12);color:var(--info)"><i class="fas fa-envelope"></i></div>
                    <div class="stat-value"><?php echo count($conversations); ?></div>
                    <div class="stat-label">Conversations actives</div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-clock-rotate-left"></i> Dernières annonces publiées</div>
                <?php if(count($user_annonces) > 0): ?>
                    <div class="ads-grid">
                        <?php foreach(array_slice($user_annonces, 0, 3) as $a): ?>
                            <div class="ad-card">
                                <div class="ad-thumb">
                                    <?php if($a['photo'] && file_exists($a['photo'])): ?>
                                        <img src="<?php echo $a['photo']; ?>" alt="">
                                    <?php else: ?>
                                        <i class="fas fa-image no-img"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="ad-body">
                                    <?php if($a['categorie']): ?>
                                        <span class="ad-category-tag"><i class="fas fa-tag"></i><?php echo htmlspecialchars($a['categorie']); ?></span>
                                    <?php endif; ?>
                                    <div class="ad-title"><?php echo htmlspecialchars($a['nom_annonce']); ?></div>
                                    <div class="ad-price"><?php echo number_format($a['prix'], 0, ',', ' '); ?> €</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-box-open"></i></div>
                        <h3>Aucune annonce</h3>
                        <p>Commencez dès maintenant à publier vos articles.</p>
                        <a href="?tab=create_ad" class="btn btn-primary"><i class="fas fa-plus"></i> Créer une annonce</a>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

        <!-- ════ MON PROFIL ════ -->
        <?php if($active_tab == 'profile'): ?>
            <div class="page-header">
                <h1>Mon profil</h1>
                <p>Modifiez vos informations personnelles et changez votre mot de passe si besoin.</p>
            </div>
            <div class="section-card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Photo de profil</label>
                            <?php if(!empty($user_profile['avatar']) && file_exists($user_profile['avatar'])): ?>
                                <div style="margin-bottom:0.75rem; width:72px; height:72px; border-radius:50%; overflow:hidden; border:1px solid rgba(255,255,255,0.12);">
                                    <img src="<?php echo htmlspecialchars($user_profile['avatar']); ?>" alt="Photo de profil" style="width:100%; height:100%; object-fit:cover;">
                                </div>
                            <?php endif; ?>
                            <label class="file-input-wrapper" id="avatarLabel">
                                <i class="fas fa-camera"></i>
                                <span id="avatarFileName">Sélectionner une photo</span>
                                <input type="file" name="avatar" accept="image/*" onchange="updateFileName(this)">
                            </label>
                        </div>
                        <div class="form-group">
                            <label>Nom *</label>
                            <input type="text" name="nom" required value="<?php echo htmlspecialchars($user_profile['nom'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Prénom *</label>
                            <input type="text" name="prenom" required value="<?php echo htmlspecialchars($user_profile['prenom'] ?? ''); ?>">
                        </div>
                        <div class="form-group full">
                            <label>Email *</label>
                            <input type="email" name="email" required value="<?php echo htmlspecialchars($user_profile['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Date de naissance</label>
                            <input type="date" name="date_de_naissance" value="<?php echo htmlspecialchars($user_profile['date_de_naissance'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Sexe</label>
                            <select name="sexe">
                                <option value="">Sélectionnez</option>
                                <option value="Homme" <?php echo (isset($user_profile['sexe']) && $user_profile['sexe'] === 'Homme') ? 'selected' : ''; ?>>Homme</option>
                                <option value="Femme" <?php echo (isset($user_profile['sexe']) && $user_profile['sexe'] === 'Femme') ? 'selected' : ''; ?>>Femme</option>
                                <option value="Autre" <?php echo (isset($user_profile['sexe']) && $user_profile['sexe'] === 'Autre') ? 'selected' : ''; ?>>Autre</option>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label>Animal préféré</label>
                            <input type="text" name="animal_prefere" value="<?php echo htmlspecialchars($user_profile['animal_prefere'] ?? ''); ?>">
                        </div>
                        <div class="form-group full">
                            <label>Nouveau mot de passe</label>
                            <input type="password" name="password" placeholder="Laissez vide pour conserver l'actuel">
                        </div>
                        <div class="form-group full">
                            <label>Confirmer le mot de passe</label>
                            <input type="password" name="confirm_password" placeholder="Confirmez le nouveau mot de passe">
                        </div>
                    </div>
                    <div style="margin-top:1.5rem; display:flex; gap:0.75rem; flex-wrap:wrap; align-items:center;">
                        <button type="submit" name="update_profile" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                        <span style="color:var(--text3); font-size:0.9rem;">Le mot de passe est facultatif.</span>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- ════ CRÉER ANNONCE ════ -->
        <?php if($active_tab == 'create_ad'): ?>
            <div class="page-header">
                <h1>Nouvelle annonce</h1>
                <p>Remplissez les informations de votre article à vendre.</p>
            </div>
            <div class="section-card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Titre de l'annonce *</label>
                            <input type="text" name="nom" required placeholder="Ex: iPhone 13 Pro, Canapé 3 places…">
                        </div>
                        <div class="form-group">
                            <label>Prix (€) *</label>
                            <input type="number" name="prix" step="0.01" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Catégorie</label>
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
                        <div class="form-group full">
                            <label>Description *</label>
                            <textarea name="description" required placeholder="Décrivez l'état, les caractéristiques, les conditions de vente…"></textarea>
                        </div>
                        <div class="form-group full">
                            <label>Photo</label>
                            <label class="file-input-wrapper" id="fileLabel">
                                <i class="fas fa-cloud-arrow-up"></i>
                                <span id="fileName">Cliquez pour ajouter une photo</span>
                                <input type="file" name="photo" accept="image/*" onchange="updateFileName(this)">
                            </label>
                        </div>
                    </div>
                    <div style="margin-top:1.5rem;">
                        <button type="submit" name="create_annonce" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Publier l'annonce</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- ════ MES ANNONCES ════ -->
        <?php if($active_tab == 'my_ads'): ?>
            <div class="page-header">
                <h1>Mes annonces</h1>
                <p><?php echo count($user_annonces); ?> annonce<?php echo count($user_annonces)!=1?'s':''; ?> publiée<?php echo count($user_annonces)!=1?'s':''; ?></p>
            </div>
            <div class="section-card">
                <?php if(count($user_annonces) > 0): ?>
                    <div class="ads-grid">
                        <?php foreach($user_annonces as $a): ?>
                            <div class="ad-card">
                                <div class="ad-thumb">
                                    <?php if($a['photo'] && file_exists($a['photo'])): ?>
                                        <img src="<?php echo $a['photo']; ?>" alt="">
                                    <?php else: ?>
                                        <i class="fas fa-image no-img"></i>
                                    <?php endif; ?>
                                    <div class="ad-thumb-overlay"></div>
                                </div>
                                <div class="ad-body">
                                    <?php if($a['categorie']): ?>
                                        <span class="ad-category-tag"><i class="fas fa-tag"></i><?php echo htmlspecialchars($a['categorie']); ?></span>
                                    <?php endif; ?>
                                    <div class="ad-title"><?php echo htmlspecialchars($a['nom_annonce']); ?></div>
                                    <div class="ad-price"><?php echo number_format($a['prix'], 0, ',', ' '); ?> €</div>
                                    <div class="ad-actions">
                                        <button class="btn btn-ghost btn-sm" onclick="openEditModal(<?php echo $a['id']; ?>, '<?php echo addslashes($a['nom_annonce']); ?>', <?php echo $a['prix']; ?>, '<?php echo addslashes($a['description']); ?>', '<?php echo addslashes($a['categorie']); ?>')">
                                            <i class="fas fa-pen"></i> Modifier
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $a['id']; ?>, '<?php echo addslashes($a['nom_annonce']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-box-open"></i></div>
                        <h3>Aucune annonce</h3>
                        <p>Vous n'avez pas encore publié d'annonces.</p>
                        <a href="?tab=create_ad" class="btn btn-primary"><i class="fas fa-plus"></i> Créer une annonce</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ════ FAVORIS ════ -->
        <?php if($active_tab == 'favorites'): ?>
            <div class="page-header">
                <h1>Mes favoris</h1>
                <p><?php echo count($favoris); ?> annonce<?php echo count($favoris)!=1?'s':'' ;?> sauvegardée<?php echo count($favoris)!=1?'s':''; ?></p>
            </div>
            <div class="section-card">
                <?php if(count($favoris) > 0): ?>
                    <div class="ads-grid">
                        <?php foreach($favoris as $a): ?>
                            <div class="ad-card" data-ad-id="<?php echo $a['id']; ?>">
                                <div class="ad-thumb">
                                    <?php if($a['photo'] && file_exists($a['photo'])): ?>
                                        <img src="<?php echo $a['photo']; ?>" alt="">
                                    <?php else: ?>
                                        <i class="fas fa-image no-img"></i>
                                    <?php endif; ?>
                                    <div class="fav-counter"><i class="fas fa-heart"></i> <?php echo $a['total_favs']; ?></div>
                                </div>
                                <div class="ad-body">
                                    <?php if($a['categorie']): ?>
                                        <span class="ad-category-tag"><i class="fas fa-tag"></i><?php echo htmlspecialchars($a['categorie']); ?></span>
                                    <?php endif; ?>
                                    <div class="ad-title"><?php echo htmlspecialchars($a['nom_annonce']); ?></div>
                                    <div class="ad-price"><?php echo number_format($a['prix'], 0, ',', ' '); ?> €</div>
                                    <div class="ad-meta">
                                        <div class="seller-avatar">
                                            <?php if(!empty($a['seller_avatar']) && file_exists($a['seller_avatar'])): ?>
                                                <img src="<?php echo htmlspecialchars($a['seller_avatar']); ?>" alt="Avatar">
                                            <?php else: ?>
                                                <i class="fas fa-user"></i>
                                            <?php endif; ?>
                                        </div>
                                        <?php echo htmlspecialchars($a['prenom'] . ' ' . $a['nom']); ?>
                                    </div>
                                    <div class="ad-actions">
                                        <button onclick="toggleFavorite(<?php echo $a['id']; ?>, this)" class="btn btn-fav active btn-sm favorite-btn">
                                            <i class="fas fa-heart"></i> Retirer
                                        </button>
                                        <button class="btn btn-ghost btn-sm" onclick="openDetailModal(<?php echo $a['id']; ?>)">
                                            <i class="fas fa-eye"></i> Voir
                                        </button>
                                        <button class="btn btn-ghost btn-sm" onclick="openMessageModal(<?php echo $a['id']; ?>, <?php echo $a['user_id']; ?>, '<?php echo addslashes($a['nom_annonce']); ?>')">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-heart-crack"></i></div>
                        <h3>Aucun favori</h3>
                        <p>Sauvegardez des annonces pour les retrouver facilement ici.</p>
                        <a href="?tab=all_ads" class="btn btn-primary"><i class="fas fa-magnifying-glass"></i> Parcourir les annonces</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ════ MESSAGES ════ -->
        <?php if($active_tab == 'messages'): ?>
            <div class="page-header">
                <h1>Messagerie</h1>
                <p><?php echo $unread_count > 0 ? "$unread_count message".($unread_count>1?'s':'')." non lu".($unread_count>1?'s':'') : 'Tous vos messages sont lus'; ?></p>
            </div>

            <div class="messages-layout">
                <!-- Liste des conversations -->
                <div class="conversations-list">
                    <div class="section-card">
                        <div class="section-title"><i class="fas fa-comments"></i> Conversations</div>
                        <?php if(count($conversations) > 0): ?>
                            <div class="msg-list">
                                <?php foreach($conversations as $conv): ?>
                                    <div class="msg-item <?php echo $conv['unread_count']>0?'unread':''; ?> <?php echo $current_conversation==$conv['annonce_id']?'active':''; ?>"
                                         onclick="openConversation(<?php echo $conv['annonce_id']; ?>)">
                                        <div class="msg-meta">
                                            <span class="msg-sender"><?php echo htmlspecialchars($conv['sujet'] ?? 'Conversation'); ?></span>
                                            <span class="msg-date"><?php echo date('d/m/Y H:i', strtotime($conv['last_message_date'])); ?></span>
                                        </div>
                                        <div class="msg-preview">
                                            <strong><?php echo htmlspecialchars(($conv['last_sender_prenom'] ?? 'Utilisateur') . ' ' . ($conv['last_sender_nom'] ?? 'Inconnu')); ?>:</strong>
                                            <?php echo htmlspecialchars(substr($conv['last_message'] ?? '', 0, 80)).(strlen($conv['last_message'] ?? '')>80?'…':''); ?>
                                        </div>
                                        <div class="msg-tag">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($conv['nom_annonce'] ?? 'Annonce inconnue'); ?>
                                            <?php if($conv['unread_count'] > 0): ?>
                                                <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                                <h3>Aucune conversation</h3>
                                <p>Vous n'avez pas encore de conversations. Commencez par contacter un vendeur depuis une annonce !</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Zone de conversation -->
                <div class="conversation-view">
                    <?php if($current_conversation): ?>
                        <div class="section-card conversation-card">
                            <div class="conversation-header">
                                <h3><?php echo htmlspecialchars($conversations[array_search($current_conversation, array_column($conversations, 'annonce_id'))]['sujet'] ?? 'Conversation'); ?></h3>
                                <div class="conversation-actions">
                                    <button onclick="archiveConversation(<?php echo $current_conversation; ?>)" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-archive"></i> Archiver
                                    </button>
                                </div>
                            </div>

                            <div class="messages-thread" id="messages-thread">
                                <?php foreach($conversation_messages as $msg): ?>
                                    <div class="message <?php echo $msg['sender_id']==$user_id?'sent':'received'; ?>">
                                        <div class="message-header">
                                            <strong><?php echo htmlspecialchars($msg['sender_prenom'] . ' ' . $msg['sender_nom']); ?></strong>
                                            <span class="message-date"><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></span>
                                        </div>
                                        <div class="message-content">
                                            <?php echo nl2br(htmlspecialchars($msg['contenu'])); ?>
                                        </div>
                                        <?php if($msg['type'] != 'message'): ?>
                                            <div class="message-type"><?php echo htmlspecialchars($msg['type']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="message-form">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="annonce_id" value="<?php echo $current_conversation; ?>">
                                    <input type="hidden" name="receiver_id" value="<?php
                                        $receiver_id_value = null;
                                        foreach($conversation_messages as $msg) {
                                            if($msg['sender_id'] != $user_id) {
                                                $receiver_id_value = $msg['sender_id'];
                                                break;
                                            }
                                            if($msg['receiver_id'] != $user_id) {
                                                $receiver_id_value = $msg['receiver_id'];
                                                break;
                                            }
                                        }
                                        echo $receiver_id_value ? intval($receiver_id_value) : '';
                                    ?>">
                                    <input type="hidden" name="parent_message_id" value="<?php echo end($conversation_messages)['id'] ?? null; ?>">

                                    <div class="form-group">
                                        <textarea name="message" placeholder="Tapez votre message..." required rows="3"></textarea>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <select name="message_type">
                                                <option value="message">Message normal</option>
                                                <option value="question">Question</option>
                                                <option value="offre">Offre</option>
                                                <option value="information">Information</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" name="send_message" class="btn btn-primary">
                                                <i class="fas fa-paper-plane"></i> Envoyer
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-conversation">
                            <div class="empty-icon"><i class="fas fa-comments"></i></div>
                            <h3>Sélectionnez une conversation</h3>
                            <p>Cliquez sur une conversation dans la liste pour voir les messages et répondre.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ════ TOUTES LES ANNONCES ════ -->
        <?php if($active_tab == 'all_ads'):
            $sql = "SELECT a.*, u.prenom, u.nom, u.avatar,
                    (SELECT COUNT(*) FROM favoris WHERE annonce_id = a.id) as total_favs
                    FROM annonces a 
                    INNER JOIN utilisateur u ON a.user_id = u.id_letim 
                    WHERE 1=1";
            $params = [];
            if(!empty($_GET['search'])) {
                $keywords = explode(' ', trim($_GET['search']));
                foreach($keywords as $kw) {
                    $kw = trim($kw);
                    if(!empty($kw)) {
                        $encoded = htmlspecialchars($kw);
                        $sql .= " AND (LOWER(a.nom_annonce) LIKE LOWER(?) OR LOWER(a.description) LIKE LOWER(?) OR LOWER(a.nom_annonce) LIKE LOWER(?) OR LOWER(a.description) LIKE LOWER(?))";
                        $params[] = '%'.$kw.'%';
                        $params[] = '%'.$kw.'%';
                        $params[] = '%'.$encoded.'%';
                        $params[] = '%'.$encoded.'%';
                    }
                }
            }
            if(!empty($_GET['categorie'])) { $sql .= " AND a.categorie = ?"; $params[] = $_GET['categorie']; }
            if(!empty($_GET['prix_min'])) { $sql .= " AND a.prix >= ?"; $params[] = $_GET['prix_min']; }
            if(!empty($_GET['prix_max'])) { $sql .= " AND a.prix <= ?"; $params[] = $_GET['prix_max']; }
            $sql .= " ORDER BY a.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $all_annonces = $stmt->fetchAll();
        ?>
            <div class="page-header">
                <h1>Explorer les annonces</h1>
                <p><?php echo count($all_annonces); ?> résultat<?php echo count($all_annonces)!=1?'s':''; ?></p>
            </div>

            <form method="GET" class="filter-bar">
                <input type="hidden" name="tab" value="all_ads">
                <div class="form-group">
                    <label>Recherche</label>
                    <input type="text" name="search" placeholder="Mot-clé…" value="<?php echo htmlspecialchars($_GET['search']??''); ?>">
                </div>
                <div class="form-group">
                    <label>Catégorie</label>
                    <select name="categorie">
                        <option value="">Toutes</option>
                        <?php foreach(['Électronique','Mode','Maison','Véhicules','Immobilier','Services'] as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo (($_GET['categorie']??'')==$cat)?'selected':''; ?>><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Prix min (€)</label>
                    <input type="number" name="prix_min" step="1" value="<?php echo htmlspecialchars($_GET['prix_min']??''); ?>" placeholder="0">
                </div>
                <div class="form-group">
                    <label>Prix max (€)</label>
                    <input type="number" name="prix_max" step="1" value="<?php echo htmlspecialchars($_GET['prix_max']??''); ?>" placeholder="∞">
                </div>
                <div class="form-group" style="min-width:auto;flex:0;">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrer</button>
                </div>
            </form>

            <div class="section-card">
                <?php if(count($all_annonces) > 0): ?>
                    <div class="ads-grid">
                        <?php foreach($all_annonces as $a):
                            $is_fav = in_array($a['id'], $favorite_ids);
                        ?>
                            <div class="ad-card" data-ad-id="<?php echo $a['id']; ?>">
                                <div class="ad-thumb">
                                    <?php if($a['photo'] && file_exists($a['photo'])): ?>
                                        <img src="<?php echo $a['photo']; ?>" alt="">
                                    <?php else: ?>
                                        <i class="fas fa-image no-img"></i>
                                    <?php endif; ?>
                                    <div class="ad-thumb-overlay"></div>
                                    <div class="fav-counter"><i class="fas fa-heart"></i> <?php echo $a['total_favs']; ?></div>
                                    <?php if($is_fav): ?>
                                        <div class="fav-pill" onclick="event.stopPropagation();toggleFavorite(<?php echo $a['id']; ?>, this)">
                                            <i class="fas fa-heart"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ad-body">
                                    <?php if($a['categorie']): ?>
                                        <span class="ad-category-tag"><i class="fas fa-tag"></i><?php echo htmlspecialchars($a['categorie']); ?></span>
                                    <?php endif; ?>
                                    <div class="ad-title"><?php echo htmlspecialchars($a['nom_annonce']); ?></div>
                                    <div class="ad-price"><?php echo number_format($a['prix'], 0, ',', ' '); ?> €</div>
                                    <div class="ad-meta">
                                        <span class="seller-avatar">
                                            <?php
                                                $sellerAvatar = $a['avatar'] ?? null;
                                                if($sellerAvatar && (file_exists($sellerAvatar) || file_exists('Views/' . $sellerAvatar))):
                                                    $avatarUrl = file_exists($sellerAvatar) ? $sellerAvatar : 'Views/' . $sellerAvatar;
                                            ?>
                                                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar <?php echo htmlspecialchars($a['prenom'] . ' ' . $a['nom']); ?>">
                                            <?php else: ?>
                                                <i class="fas fa-user"></i>
                                            <?php endif; ?>
                                        </span>
                                        <span><?php echo htmlspecialchars($a['prenom'].' '.$a['nom']); ?></span>
                                    </div>
                                    <div class="ad-actions">
                                        <button onclick="toggleFavorite(<?php echo $a['id']; ?>, this)" class="btn btn-fav <?php echo $is_fav?'active':''; ?> btn-sm favorite-btn">
                                            <i class="fas fa-heart"></i> <?php echo $is_fav?'Retirer':'Favoris'; ?>
                                        </button>
                                        <button class="btn btn-ghost btn-sm" onclick="openDetailModal(<?php echo $a['id']; ?>)">
                                            <i class="fas fa-eye"></i> Voir
                                        </button>
                                        <?php if($a['user_id'] != $user_id): ?>
                                            <button class="btn btn-ghost btn-sm" onclick="openMessageModal(<?php echo $a['id']; ?>, <?php echo $a['user_id']; ?>, '<?php echo addslashes($a['nom_annonce']); ?>')">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-magnifying-glass"></i></div>
                        <h3>Aucun résultat</h3>
                        <p>Essayez d'autres mots-clés ou critères de filtre.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- ════ MODAL : Modifier annonce ════ -->
<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-pen" style="color:var(--accent);margin-right:.5rem"></i> Modifier l'annonce</h3>
            <button class="modal-close" onclick="closeEditModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="annonce_id" id="edit_id">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Titre *</label>
                    <input type="text" name="nom" id="edit_nom" required>
                </div>
                <div class="form-group">
                    <label>Prix (€) *</label>
                    <input type="number" name="prix" step="0.01" id="edit_prix" required>
                </div>
                <div class="form-group">
                    <label>Catégorie</label>
                    <select name="categorie" id="edit_categorie">
                        <option value="">Sélectionnez</option>
                        <?php foreach(['Électronique','Mode','Maison','Véhicules','Immobilier','Services','Autre'] as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Description *</label>
                    <textarea name="description" id="edit_description" required></textarea>
                </div>
                <div class="form-group full">
                    <label>Nouvelle photo (optionnel)</label>
                    <label class="file-input-wrapper">
                        <i class="fas fa-cloud-arrow-up"></i>
                        <span>Cliquer pour changer la photo</span>
                        <input type="file" name="photo" accept="image/*">
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeEditModal()">Annuler</button>
                <button type="submit" name="edit_annonce" class="btn btn-primary"><i class="fas fa-check"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- ════ MODAL : Détail annonce ════ -->
<div id="detailModal" class="modal-overlay">
    <div class="modal-box wide">
        <div class="modal-header">
            <h3 id="detail_title"></h3>
            <button class="modal-close" onclick="closeDetailModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="detail-img-wrap" id="detail_image"></div>
        <div class="detail-row"><strong>Prix</strong><span id="detail_price" style="color:var(--accent);font-size:1.2rem;font-weight:700;font-family:'Syne',sans-serif;"></span></div>
        <div class="detail-row"><strong>Catégorie</strong><span id="detail_category"></span></div>
        <div class="detail-row" style="flex-direction:column;align-items:flex-start"><strong style="margin-bottom:.5rem">Description</strong><div class="detail-desc" id="detail_description"></div></div>
        <div class="detail-row"><strong>Vendeur</strong><div id="detail_seller" style="display:flex;align-items:center;gap:0.5rem;"></div></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeDetailModal()">Fermer</button>
            <button type="button" class="btn btn-fav" id="detail_fav_btn"></button>
        </div>
    </div>
</div>

<!-- ════ MODAL : Envoyer message ════ -->
<div id="messageModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-envelope" style="color:var(--accent);margin-right:.5rem"></i> Contacter le vendeur</h3>
            <button class="modal-close" onclick="closeMessageModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <p style="font-size:.85rem;color:var(--text3);margin-bottom:1.25rem;">
            À propos de : <strong id="message_annonce_title" style="color:var(--accent)"></strong>
        </p>
        <form method="POST">
            <input type="hidden" name="annonce_id" id="message_annonce_id">
            <input type="hidden" name="receiver_id" id="message_receiver_id">
            <div class="form-group">
                <label>Votre message</label>
                <textarea name="message" rows="5" required placeholder="Bonjour, je suis intéressé(e) par votre annonce…"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeMessageModal()">Annuler</button>
                <button type="submit" name="send_message" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Envoyer</button>
            </div>
        </form>
    </div>
</div>

<!-- ════ MODAL : Supprimer annonce ════ -->
<div id="deleteModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 400px; text-align: center;">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(255,77,109,0.1); color: var(--danger); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1.2rem auto;">
            <i class="fas fa-trash-can"></i>
        </div>
        <h3 style="font-family: 'Syne', sans-serif; font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text)">Supprimer l'annonce</h3>
        <p style="color: var(--text2); font-size: 0.9rem; margin-bottom: 1.5rem; line-height: 1.5;">
            Êtes-vous sûr de vouloir supprimer <br><strong id="delete_annonce_title" style="color: var(--text);"></strong> ?<br>Cette action est irréversible.
        </p>
        <div style="display: flex; gap: 0.75rem; justify-content: center;">
            <button type="button" class="btn btn-ghost" onclick="closeDeleteModal()" style="flex: 1; justify-content: center;">Annuler</button>
            <button type="button" class="btn btn-danger" id="confirm_delete_btn" style="flex: 1; justify-content: center;"><i class="fas fa-trash"></i> Supprimer</button>
        </div>
    </div>
</div>

<script>
    // ── SIDEBAR MOBILE ──
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }

    // ── FILE INPUT LABEL ──
    function updateFileName(input) {
        const label = input.closest('.file-input-wrapper').querySelector('span');
        label.textContent = input.files[0] ? input.files[0].name : 'Cliquez pour ajouter une photo';
    }

    // ── FAVORIS AJAX ──
    function toggleFavorite(annonceId, btn) {
        const orig = btn.innerHTML;
        btn.innerHTML = '<span class="spinner"></span>';
        btn.disabled = true;

        fetch(`toggle_favorite.php?id=${annonceId}`, { headers: {'X-Requested-With':'XMLHttpRequest'} })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                if(data.success) {
                    if(data.is_favorite) {
                        btn.classList.add('active');
                        btn.innerHTML = '<i class="fas fa-heart"></i> Retirer';
                        showToast('Ajouté aux favoris', 'success');
                    } else {
                        btn.classList.remove('active');
                        btn.innerHTML = '<i class="fas fa-heart"></i> Favoris';
                        showToast('Retiré des favoris', 'info');
                        // Remove card on favorites page
                        if(window.location.href.includes('tab=favorites')) {
                            const card = document.querySelector(`.ad-card[data-ad-id="${annonceId}"]`);
                            if(card) { card.style.opacity='0'; card.style.transform='scale(0.9)'; card.style.transition='all .3s'; setTimeout(()=>card.remove(), 300); }
                        }
                    }
                    updateFavCount();
                    updateFavCountOnCard(annonceId, data.is_favorite);
                }
            })
            .catch(() => { btn.innerHTML = orig; btn.disabled = false; showToast('Une erreur est survenue', 'error'); });
    }

    function updateFavCountOnCard(id, adding) {
        const card = document.querySelector(`.ad-card[data-ad-id="${id}"] .fav-counter`);
        if(card) {
            const n = parseInt(card.textContent.replace(/\D/g,''));
            card.innerHTML = `<i class="fas fa-heart"></i> ${adding ? n+1 : Math.max(0,n-1)}`;
        }
    }

    function updateFavCount() {
        fetch('get_favorite_count.php')
            .then(r => r.json())
            .then(data => {
                document.querySelectorAll('.fav-count').forEach(el => { el.textContent = data.count; });
            });
    }

    // ── TOAST ──
    function showToast(msg, type = 'success') {
        document.querySelectorAll('.toast').forEach(t => t.remove());
        const icons = { success: 'fa-heart', info: 'fa-star', error: 'fa-exclamation-circle' };
        const colors = { success: 'var(--success)', info: 'var(--info)', error: 'var(--danger)' };
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        t.innerHTML = `<i class="fas ${icons[type]}" style="color:${colors[type]}"></i><span>${msg}</span>`;
        document.body.appendChild(t);
        setTimeout(() => { t.style.transition='all .3s'; t.style.opacity='0'; t.style.transform='translateY(12px)'; setTimeout(()=>t.remove(),300); }, 3000);
    }

    // ── DELETE CONFIRM ──
    function confirmDelete(id, nom) {
        document.getElementById('delete_annonce_title').textContent = '"' + nom + '"';
        document.getElementById('confirm_delete_btn').onclick = function() {
            window.location.href = `?delete_id=${id}&tab=my_ads`;
        };
        document.getElementById('deleteModal').classList.add('active');
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
    }

    // ── EDIT MODAL ──
    function openEditModal(id, nom, prix, desc, cat) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nom').value = nom;
        document.getElementById('edit_prix').value = prix;
        document.getElementById('edit_description').value = desc;
        document.getElementById('edit_categorie').value = cat || '';
        document.getElementById('editModal').classList.add('active');
    }
    function closeEditModal() { document.getElementById('editModal').classList.remove('active'); }

    // ── DETAIL MODAL ──
    function openDetailModal(id) {
        fetch(`get_annonce.php?id=${id}`)
            .then(r => r.json())
            .then(d => {
                document.getElementById('detail_title').textContent = d.nom;
                document.getElementById('detail_price').textContent = new Intl.NumberFormat('fr-FR').format(d.prix) + ' €';
                document.getElementById('detail_category').textContent = d.categorie || 'Non catégorisé';
                document.getElementById('detail_description').textContent = d.description;
                
                // Seller with avatar
                const sellerEl = document.getElementById('detail_seller');
                let sellerHtml = '';
                if(d.seller_avatar && (d.seller_avatar.startsWith('uploads/') || d.seller_avatar.startsWith('Views/'))) {
                    sellerHtml += `<span class="seller-avatar"><img src="${d.seller_avatar}" alt="Avatar ${d.seller}"></span>`;
                } else {
                    sellerHtml += `<span class="seller-avatar"><i class="fas fa-user"></i></span>`;
                }
                sellerHtml += `<span>${d.seller}</span>`;
                sellerEl.innerHTML = sellerHtml;
                
                const favBtn = document.getElementById('detail_fav_btn');
                favBtn.innerHTML = d.is_favorite ? '<i class="fas fa-heart"></i> Retirer des favoris' : '<i class="fas fa-heart"></i> Ajouter aux favoris';
                favBtn.className = `btn btn-fav btn-sm ${d.is_favorite ? 'active' : ''}`;
                favBtn.onclick = () => { toggleFavorite(id, favBtn); setTimeout(()=>openDetailModal(id), 500); };
                document.getElementById('detail_image').innerHTML = d.photo
                    ? `<img src="${d.photo}" style="width:100%;height:100%;object-fit:cover;">`
                    : `<i class="fas fa-image" style="font-size:3rem;color:var(--surface2)"></i>`;
                document.getElementById('detailModal').classList.add('active');
            });
    }
    function closeDetailModal() { document.getElementById('detailModal').classList.remove('active'); }

    // ── MESSAGE MODAL ──
    function openMessageModal(aid, rid, title) {
        document.getElementById('message_annonce_id').value = aid;
        document.getElementById('message_receiver_id').value = rid;
        document.getElementById('message_annonce_title').textContent = title;
        document.getElementById('messageModal').classList.add('active');
    }
    function closeMessageModal() { document.getElementById('messageModal').classList.remove('active'); }

    function viewMessage(id) { window.location.href = `?tab=messages&read_msg=${id}`; }

    // ── CONVERSATIONS ──
    function openConversation(annonceId) {
        window.location.href = `?tab=messages&conversation=${annonceId}`;
    }

    function archiveConversation(annonceId) {
        if(confirm('Êtes-vous sûr de vouloir archiver cette conversation ?')) {
            fetch(`ajax_archive_conversation.php?annonce_id=${annonceId}`, {
                method: 'POST',
                headers: {'X-Requested-With':'XMLHttpRequest'}
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    showToast('Conversation archivée', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Erreur lors de l\'archivage', 'error');
                }
            })
            .catch(() => showToast('Une erreur est survenue', 'error'));
        }
    }

    // Close modals on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', e => { if(e.target === m) m.classList.remove('active'); });
    });

    // Refresh fav count every 30s
    setInterval(updateFavCount, 30000);
</script>
</body>
</html>