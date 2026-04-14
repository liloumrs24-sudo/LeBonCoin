<?php
// auth.php - Page d'authentification
session_start();

// Activation des erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration de la base de données
$host = 'localhost';
$dbname = 'le_bon_coin';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$error = '';
$success = '';

// Traitement de l'inscription
if(isset($_POST['register'])) {
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $date_de_naissance = $_POST['date_de_naissance'];
    $sexe = $_POST['sexe'];
    $animal_prefere = htmlspecialchars(trim($_POST['animal_prefere']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($date_de_naissance) || empty($sexe) || empty($animal_prefere)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif(strlen($nom) < 2) {
        $error = "Le nom doit contenir au moins 2 caractères.";
    } elseif(strlen($prenom) < 2) {
        $error = "Le prénom doit contenir au moins 2 caractères.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } elseif(strlen($password) < 10) {
        $error = "Le mot de passe doit contenir au moins 10 caractères.";
    } elseif($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $stmt = $pdo->prepare("SELECT id_letim FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        if($stmt->fetch()) {
            $error = "Cet email est déjà utilisé. Connectez-vous plutôt !";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, password, sexe, animal_prefere, date_de_naissance, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            if($stmt->execute([$nom, $prenom, $email, $hashed_password, $sexe, $animal_prefere, $date_de_naissance])) {
                $success = "✨ Inscription réussie ! Bienvenue $prenom ! Vous pouvez maintenant vous connecter.";
                echo '<script>setTimeout(function(){ window.location.href = "auth.php"; }, 2000);</script>';
            } else {
                $error = "Une erreur est survenue lors de l'inscription.";
            }
        }
    }
}

// Traitement de la connexion
if(isset($_POST['login'])) {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if(empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT id_letim, email, password, prenom, nom FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id_letim'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_nom'] = $user['nom'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}

$show_register = isset($_GET['register']) && $_GET['register'] == 1;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion & Inscription - Le Bon Coin</title>
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

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }

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

        .auth-container {
            max-width: 580px;
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
            position: relative;
            z-index: 1;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-header {
            background: var(--bg2);
            border-bottom: 1px solid var(--border);
            padding: 2rem;
            text-align: center;
        }

        .auth-header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .auth-header h1 i {
            color: var(--accent);
        }

        .auth-header p {
            color: var(--text2);
            font-size: 0.9rem;
        }

        .auth-tabs {
            display: flex;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
        }

        .tab-btn {
            flex: 1;
            padding: 1.2rem;
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--text2);
            font-family: 'DM Sans', sans-serif;
            position: relative;
        }

        .tab-btn i { margin-right: 0.5rem; }
        
        .tab-btn.active {
            color: var(--accent);
            background: var(--accent-soft);
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--accent);
            box-shadow: 0 -2px 10px var(--accent-glow);
        }

        .tab-btn:hover:not(.active) {
            background: var(--bg3);
            color: var(--text);
        }

        .tab-content {
            padding: 2.5rem 2rem;
            display: none;
            animation: fadeIn 0.4s ease-out;
        }

        .tab-content.active { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text2);
            font-size: 0.85rem;
            letter-spacing: 0.02em;
        }
        
        label i { margin-right: 0.3rem; color: var(--accent); }
        
        .input-group { position: relative; }
        .input-group i {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text3);
            transition: color 0.3s;
        }
        
        input, select {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 3.2rem;
            background: var(--bg3);
            border: 1px solid var(--border2);
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            color: var(--text);
            transition: all 0.3s;
            outline: none;
            -webkit-appearance: none;
        }
        
        select { cursor: pointer; }
        select option { background: var(--bg3); }
        
        input::placeholder { color: var(--text3); }

        input:focus, select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        
        /* Highlight icon correctly */
        .input-group:focus-within i.fa-envelope,
        .input-group:focus-within i.fa-lock,
        .input-group:focus-within i.fa-user,
        .input-group:focus-within i.fa-user-circle,
        .input-group:focus-within i.fa-calendar-alt,
        .input-group:focus-within i.fa-venus-mars,
        .input-group:focus-within i.fa-paw,
        .input-group:focus-within i.fa-check-circle {
            color: var(--accent);
        }

        .submit-btn {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, var(--accent), #e55a28);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'DM Sans', sans-serif;
            margin-top: 0.5rem;
            box-shadow: 0 4px 12px var(--accent-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,107,53,0.4);
        }

        .alert {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.2rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            animation: slideDown 0.3s ease;
        }
        
        .alert-error {
            background: rgba(255,77,109,0.1);
            border: 1px solid rgba(255,77,109,0.25);
            color: var(--danger);
        }
        
        .alert-success {
            background: rgba(34,211,165,0.1);
            border: 1px solid rgba(34,211,165,0.25);
            color: var(--success);
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .password-hint {
            font-size: 0.75rem;
            color: var(--text3);
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .back-link {
            text-align: center;
            margin-top: 1.8rem;
            padding-top: 1.2rem;
            border-top: 1px solid var(--border);
        }

        .back-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        
        .back-link a:hover {
            color: var(--accent2);
            gap: 0.7rem;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border);
        }

        .divider span {
            margin: 0 1rem;
            font-size: 0.8rem;
            color: var(--text3);
        }

        @media (max-width: 640px) {
            body { padding: 1rem; }
            .tab-content { padding: 1.5rem; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1><i class="fas fa-handshake"></i> Le Bon Coin</h1>
            <p>Rejoignez la communauté des bonnes affaires</p>
        </div>

        <div class="auth-tabs">
            <button class="tab-btn <?php echo !$show_register ? 'active' : ''; ?>" onclick="switchTab('login')">
                <i class="fas fa-sign-in-alt"></i> Connexion
            </button>
            <button class="tab-btn <?php echo $show_register ? 'active' : ''; ?>" onclick="switchTab('register')">
                <i class="fas fa-user-plus"></i> Inscription
            </button>
        </div>

        <!-- Formulaire de connexion -->
        <div id="login-tab" class="tab-content <?php echo !$show_register ? 'active' : ''; ?>">
            <?php if($error && !isset($_POST['register'])): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="login_email"><i class="fas fa-envelope"></i> Email</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="login_email" name="email" required placeholder="votre@email.com">
                    </div>
                </div>

                <div class="form-group">
                    <label for="login_password"><i class="fas fa-lock"></i> Mot de passe</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="login_password" name="password" required placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" name="login" class="submit-btn">
                    <i class="fas fa-arrow-right"></i> Se connecter
                </button>
            </form>
            
            <div class="divider"><span>ou</span></div>

            <div class="back-link">
                <a href="../index.php"><i class="fas fa-home"></i> Retour à l'accueil</a>
            </div>
        </div>

        <!-- Formulaire d'inscription -->
        <div id="register-tab" class="tab-content <?php echo $show_register ? 'active' : ''; ?>">
            <?php if($error && isset($_POST['register'])): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom"><i class="fas fa-user"></i> Nom</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="nom" name="nom" required placeholder="Votre nom">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="prenom"><i class="fas fa-user-circle"></i> Prénom</label>
                        <div class="input-group">
                            <i class="fas fa-user-circle"></i>
                            <input type="text" id="prenom" name="prenom" required placeholder="Votre prénom">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="register_email"><i class="fas fa-envelope"></i> Email</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="register_email" name="email" required placeholder="votre@email.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="date_de_naissance"><i class="fas fa-calendar-alt"></i> Date de naissance</label>
                        <div class="input-group">
                            <i class="fas fa-calendar-alt"></i>
                            <input type="date" id="date_de_naissance" name="date_de_naissance" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="sexe"><i class="fas fa-venus-mars"></i> Sexe</label>
                        <div class="input-group">
                            <i class="fas fa-venus-mars"></i>
                            <select id="sexe" name="sexe" required>
                                <option value="">Sélectionnez</option>
                                <option value="Homme">Homme</option>
                                <option value="Femme">Femme</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="animal_prefere"><i class="fas fa-paw"></i> Animal préféré</label>
                    <div class="input-group">
                        <i class="fas fa-paw"></i>
                        <select id="animal_prefere" name="animal_prefere" required>
                            <option value="">Sélectionnez votre animal préféré</option>
                            <option value="Chien">🐕 Chien</option>
                            <option value="Chat">🐈 Chat</option>
                            <option value="Oiseau">🐦 Oiseau</option>
                            <option value="Poisson">🐟 Poisson</option>
                            <option value="Lapin">🐰 Lapin</option>
                            <option value="Cheval">🐴 Cheval</option>
                            <option value="Hamster">🐹 Hamster</option>
                            <option value="Autre">🦄 Autre</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="register_password"><i class="fas fa-lock"></i> Mot de passe</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="register_password" name="password" required placeholder="Minimum 10 caractères">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-check-circle"></i> Confirmer</label>
                        <div class="input-group">
                            <i class="fas fa-check-circle"></i>
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Retapez le mot de passe">
                        </div>
                    </div>
                </div>

                <div class="password-hint">
                    <i class="fas fa-info-circle"></i> Le mot de passe doit contenir au moins 10 caractères
                </div>

                <button type="submit" name="register" class="submit-btn">
                    <i class="fas fa-user-plus"></i> Créer mon compte
                </button>
            </form>
            
            <div class="divider"><span>ou</span></div>

            <div class="back-link">
                <a href="../index.php"><i class="fas fa-home"></i> Retour à l'accueil</a>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const loginBtn = document.querySelectorAll('.tab-btn')[0];
            const registerBtn = document.querySelectorAll('.tab-btn')[1];
            const loginTab = document.getElementById('login-tab');
            const registerTab = document.getElementById('register-tab');
            
            if(tab === 'login') {
                loginBtn.classList.add('active');
                registerBtn.classList.remove('active');
                loginTab.classList.add('active');
                registerTab.classList.remove('active');
                const url = new URL(window.location.href);
                url.searchParams.delete('register');
                window.history.pushState({}, '', url);
            } else {
                loginBtn.classList.remove('active');
                registerBtn.classList.add('active');
                loginTab.classList.remove('active');
                registerTab.classList.add('active');
                const url = new URL(window.location.href);
                url.searchParams.set('register', '1');
                window.history.pushState({}, '', url);
            }
        }

        window.addEventListener('popstate', function() {
            const showRegister = new URLSearchParams(window.location.search).get('register') === '1';
            switchTab(showRegister ? 'register' : 'login');
        });
    </script>
</body>
</html>