# Le Bon Coin Clone

Projet PHP/MySQL de petites annonces locales avec système de favoris et messagerie interne.

## Description

Ce projet reproduit un mini-site de type Le Bon Coin :
- Page d'accueil avec annonces, filtres et carrousel
- Authentification utilisateur (inscription / connexion)
- Tableau de bord utilisateur
- Création, modification et suppression d'annonces
- Gestion de favoris
- Messagerie entre utilisateurs pour une annonce spécifique
- Téléchargement de photos et pièces jointes aux messages

## Fonctionnalités principales

- Authentification sécurisée avec mot de passe hashé
- Publication et édition d'annonces
- Suppression d'annonces avec suppression des favoris et messages associés
- Navigation par onglets dans le dashboard
- Favoris gérés en AJAX
- Messagerie conversationnelle liée à une annonce
- Comptage des messages non lus
- Vue conversation avec historique de discussion
- Archivage de conversation via AJAX

## Architecture

- `index.php` : page d'accueil publique et affichage des annonces
- `Views/auth.php` : page de connexion / inscription
- `Views/dashboard.php` : tableau de bord de l'utilisateur et interface de gestion
- `ajax_favoris.php` : gestion AJAX des favoris
- `ajax_check_favori.php` : vérification AJAX du statut favoris
- `ajax_archive_conversation.php` : archivage AJAX des conversations
- `DataBase/DB.php` : connexion MySQL de secours
- `uploads/annonces/` : stockage des images d'annonces
- `uploads/messages/` : stockage des pièces jointes de messages

## Prérequis

- PHP 7.4+ ou PHP 8.x
- MySQL / MariaDB
- MAMP / WAMP / XAMPP ou autre serveur local
- Navigateur moderne

## Installation

1. Copier le projet dans votre dossier de serveur local (`htdocs` ou `www`).
2. Créer une base de données MySQL.
3. Mettre à jour les informations de connexion si nécessaire dans :
   - `Views/auth.php`
   - `Views/dashboard.php`
   - `ajax_favoris.php`
   - `ajax_check_favori.php`
   - `ajax_archive_conversation.php`
   - `DataBase/DB.php`

4. Créer les dossiers de stockage si nécessaire :
   - `uploads/annonces/`
   - `uploads/messages/`

5. Importer la structure de la base de données.

> Le projet ne contient pas de script SQL d'origine, il faudra créer les tables suivantes selon le code : `utilisateur`, `annonces`, `favoris`, `messages`.

## Schéma de base de données attendu

### `utilisateur`
- `id_letim` INT AUTO_INCREMENT PRIMARY KEY
- `nom` VARCHAR
- `prenom` VARCHAR
- `email` VARCHAR UNIQUE
- `password` VARCHAR
- `sexe` VARCHAR
- `animal_prefere` VARCHAR
- `date_de_naissance` DATE
- `created_at` DATETIME

### `annonces`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `user_id` INT
- `nom_annonce` VARCHAR
- `prix` DECIMAL
- `description` TEXT
- `categorie` VARCHAR
- `photo` VARCHAR
- `created_at` DATETIME

### `favoris`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `user_id` INT
- `annonce_id` INT
- `created_at` DATETIME

### `messages`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `annonce_id` INT
- `sender_id` INT
- `receiver_id` INT
- `contenu` TEXT
- `parent_message_id` INT NULL
- `sujet` VARCHAR NULL
- `type` VARCHAR DEFAULT 'message'
- `piece_jointe` VARCHAR NULL
- `statut` VARCHAR DEFAULT 'envoye'
- `lu` TINYINT DEFAULT 0
- `created_at` DATETIME
- `updated_at` DATETIME

## Utilisation

- Ouvrir `Views/auth.php` dans votre navigateur.
- Créer un compte ou se connecter.
- Depuis le dashboard, publier une annonce, gérer les favorites et envoyer des messages.
- Le bouton `Messages` ouvre la messagerie avec les conversations liées aux annonces.

## Points de configuration

- Le nom de la base de données utilisé dans plusieurs fichiers est `le_bon_coin`.
- Le fichier `DataBase/DB.php` utilise `le bon coin`, ce qui semble incohérent. Vérifiez et alignez le nom de la base de données selon votre environnement.
- Les requêtes PDO sont utilisées pour la plupart des interactions en `Views/*.php`.

## Bonnes pratiques

- Ne pas exécuter ce projet en production sans sécuriser les entrées et vérifier les droits d'accès.
- Éviter l'affichage des erreurs en production (`display_errors` devrait être désactivé).
- Ajouter des protections CSRF pour les formulaires.
- Ajouter un `config.php` unique pour centraliser les paramètres de connexion.

## Fichiers à vérifier

- `index.php`
- `Views/auth.php`
- `Views/dashboard.php`
- `ajax_favoris.php`
- `ajax_check_favori.php`
- `ajax_archive_conversation.php`
- `DataBase/DB.php`

## Licence

Ce projet est un prototype de démonstration et n'est pas destiné à un usage en production sans adaptation.
