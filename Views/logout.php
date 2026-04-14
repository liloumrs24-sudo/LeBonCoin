<?php
// logout.php - Déconnexion avec animation panda triste
session_start();

// Vérifier si la déconnexion est confirmée
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] == 'yes';

if($confirmed) {
    // Détruire toutes les variables de session
    $_SESSION = array();
    
    // Supprimer le cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Détruire la session
    session_destroy();
    
    // Rediriger vers la page de connexion après 3 secondes
    header("Refresh: 3; url=auth.php");
    $show_confirmation = false;
} else {
    $show_confirmation = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - Le Bon Coin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Animations des flocons/étoiles */
        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle at 20% 40%, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s linear infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0);
            }
            100% {
                transform: translateY(-100px);
            }
        }

        .container {
            max-width: 500px;
            width: 90%;
            text-align: center;
            animation: slideIn 0.6s ease-out;
            position: relative;
            z-index: 1;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Panda Card */
        .panda-card {
            background: white;
            border-radius: 2rem;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        /* Panda SVG Animation */
        .panda-container {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .panda {
            width: 200px;
            height: 200px;
            margin: 0 auto;
            position: relative;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(5deg); }
            75% { transform: rotate(-5deg); }
        }

        /* Panda SVG */
        .panda svg {
            width: 100%;
            height: 100%;
        }

        /* Larmes qui tombent */
        .tear {
            position: absolute;
            width: 8px;
            height: 12px;
            background: #60a5fa;
            border-radius: 50%;
            animation: fall 1.5s ease-in infinite;
            opacity: 0;
        }

        .tear1 { left: 85px; top: 110px; animation-delay: 0s; }
        .tear2 { left: 115px; top: 110px; animation-delay: 0.3s; }
        .tear3 { left: 100px; top: 115px; animation-delay: 0.6s; }

        @keyframes fall {
            0% {
                opacity: 0;
                transform: translateY(0) scale(0);
            }
            20% {
                opacity: 0.8;
            }
            100% {
                opacity: 0;
                transform: translateY(60px) scale(0.5);
            }
        }

        .message {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 1.5rem 0;
            color: #2d3748;
        }

        .sub-message {
            color: #718096;
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        .buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 2rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-no {
            background: #dc2626;
            color: white;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }

        .btn-no:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
        }

        .btn-yes {
            background: #10b981;
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-yes:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-ok {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
            font-size: 1.2rem;
            padding: 0.8rem 2rem;
        }

        .btn-ok:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.4);
        }

        .success-message {
            font-size: 1.8rem;
            font-weight: 800;
            color: #10b981;
            margin: 1rem 0;
            animation: pulse 1s ease-in-out;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .redirect-message {
            margin-top: 1.5rem;
            color: #718096;
            font-size: 0.9rem;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Panda heureux (pour la confirmation) */
        .happy-panda svg {
            animation: bounce 1s ease-in-out;
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f97316;
            position: absolute;
            animation: confetti 3s ease-in-out infinite;
        }

        @keyframes confetti {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(500px) rotate(360deg);
                opacity: 0;
            }
        }

        .panda-thinking {
            position: relative;
        }

        .thought-bubble {
            position: absolute;
            top: -30px;
            right: -30px;
            background: white;
            border-radius: 50%;
            padding: 0.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            animation: bounce 1s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if($show_confirmation): ?>
            <!-- Panda triste qui demande confirmation -->
            <div class="panda-card">
                <div class="panda-container">
                    <div class="panda">
                        <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Corps -->
                            <ellipse cx="100" cy="120" rx="60" ry="50" fill="white" stroke="#333" stroke-width="2"/>
                            <!-- Tête -->
                            <circle cx="100" cy="80" r="45" fill="white" stroke="#333" stroke-width="2"/>
                            <!-- Oreilles -->
                            <circle cx="65" cy="45" r="20" fill="#333"/>
                            <circle cx="135" cy="45" r="20" fill="#333"/>
                            <!-- Yeux tristes -->
                            <ellipse cx="80" cy="75" rx="8" ry="12" fill="#333"/>
                            <ellipse cx="120" cy="75" rx="8" ry="12" fill="#333"/>
                            <!-- Reflets des yeux -->
                            <circle cx="77" cy="70" r="3" fill="white"/>
                            <circle cx="117" cy="70" r="3" fill="white"/>
                            <!-- Larmes -->
                            <ellipse cx="80" cy="95" rx="3" ry="6" fill="#60a5fa" opacity="0.8"/>
                            <ellipse cx="120" cy="95" rx="3" ry="6" fill="#60a5fa" opacity="0.8"/>
                            <!-- Nez triste -->
                            <ellipse cx="100" cy="85" rx="5" ry="4" fill="#333"/>
                            <!-- Bouche triste (en bas) -->
                            <path d="M 85 100 Q 100 90 115 100" stroke="#333" stroke-width="2" fill="none"/>
                            <!-- Rougissements -->
                            <ellipse cx="70" cy="90" rx="8" ry="5" fill="#ffb3ba" opacity="0.5"/>
                            <ellipse cx="130" cy="90" rx="8" ry="5" fill="#ffb3ba" opacity="0.5"/>
                        </svg>
                    </div>
                    <div class="tear tear1"></div>
                    <div class="tear tear2"></div>
                    <div class="tear tear3"></div>
                </div>

                <div class="message">
                    😢 Pourquoi tu pars ? 😢
                </div>
                <div class="sub-message">
                    Tu es sûr(e) de vouloir te déconnecter ?<br>
                    On va tellement s'ennuyer sans toi... 🐼
                </div>

                <div class="buttons">
                    <a href="?confirm=yes" class="btn btn-no">
                        <i class="fas fa-sign-out-alt"></i> Oui, je pars
                    </a>
                    <a href="dashboard.php" class="btn btn-yes">
                        <i class="fas fa-heart"></i> Non, je reste !
                    </a>
                </div>

                <div class="redirect-message">
                    <small>⚠️ Si tu pars, tes annonces resteront mais tu ne pourras plus poster de messages</small>
                </div>
            </div>
        <?php else: ?>
            <!-- Panda content qui dit oke -->
            <div class="panda-card">
                <div class="panda-container">
                    <div class="panda happy-panda">
                        <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Corps -->
                            <ellipse cx="100" cy="120" rx="60" ry="50" fill="white" stroke="#333" stroke-width="2"/>
                            <!-- Tête -->
                            <circle cx="100" cy="80" r="45" fill="white" stroke="#333" stroke-width="2"/>
                            <!-- Oreilles -->
                            <circle cx="65" cy="45" r="20" fill="#333"/>
                            <circle cx="135" cy="45" r="20" fill="#333"/>
                            <!-- Yeux contents (plissés) -->
                            <path d="M 70 75 Q 80 65 90 75" stroke="#333" stroke-width="3" fill="none" stroke-linecap="round"/>
                            <path d="M 110 75 Q 120 65 130 75" stroke="#333" stroke-width="3" fill="none" stroke-linecap="round"/>
                            <!-- Nez -->
                            <ellipse cx="100" cy="85" rx="5" ry="4" fill="#333"/>
                            <!-- Bouche heureuse (sourire) -->
                            <path d="M 85 95 Q 100 115 115 95" stroke="#333" stroke-width="2" fill="none"/>
                            <!-- Rougissements -->
                            <ellipse cx="70" cy="90" rx="8" ry="5" fill="#ffb3ba" opacity="0.5"/>
                            <ellipse cx="130" cy="90" rx="8" ry="5" fill="#ffb3ba" opacity="0.5"/>
                            <!-- Petit cœur -->
                            <path d="M 155 40 C 155 35, 165 30, 170 40 C 175 30, 185 35, 185 40 C 185 50, 170 60, 170 60 C 170 60, 155 50, 155 40 Z" fill="#ff4d4d" opacity="0.8"/>
                        </svg>
                    </div>
                    <div class="thought-bubble">
                        <span style="font-size: 1.5rem;">👋</span>
                    </div>
                </div>

                <div class="success-message">
                    🐼 OKÉ ! 🐼
                </div>
                <div class="message" style="font-size: 1.2rem;">
                    À très bientôt j'espère !
                </div>
                <div class="sub-message">
                    N'oublie pas de revenir nous voir<br>
                    On t'attendra avec impatience 💕
                </div>

                <div class="buttons">
                    <a href="auth.php" class="btn btn-ok">
                        <i class="fas fa-check-circle"></i> OKÉ !
                    </a>
                </div>

                <div class="redirect-message">
                    Redirection automatique dans <span id="countdown">3</span> secondes...
                </div>
            </div>

            <script>
                // Compte à rebours
                let seconds = 3;
                const countdownElement = document.getElementById('countdown');
                
                const interval = setInterval(() => {
                    seconds--;
                    countdownElement.textContent = seconds;
                    if(seconds <= 0) {
                        clearInterval(interval);
                        window.location.href = 'auth.php';
                    }
                }, 1000);
            </script>
        <?php endif; ?>
    </div>

    <!-- Animation des confettis après confirmation -->
    <?php if(!$show_confirmation): ?>
    <script>
        // Créer des confettis
        for(let i = 0; i < 50; i++) {
            const confetti = document.createElement('div');
            confetti.classList.add('confetti');
            confetti.style.left = Math.random() * window.innerWidth + 'px';
            confetti.style.animationDelay = Math.random() * 2 + 's';
            confetti.style.animationDuration = Math.random() * 2 + 2 + 's';
            confetti.style.backgroundColor = `hsl(${Math.random() * 360}, 70%, 50%)`;
            document.body.appendChild(confetti);
            
            // Supprimer après animation
            setTimeout(() => confetti.remove(), 4000);
        }
    </script>
    <?php endif; ?>

    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</body>
</html>