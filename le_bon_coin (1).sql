-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mer. 15 avr. 2026 à 21:44
-- Version du serveur : 5.7.24
-- Version de PHP : 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `le_bon_coin`
--

-- --------------------------------------------------------

--
-- Structure de la table `annonces`
--

CREATE TABLE `annonces` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nom_annonce` varchar(255) NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `categorie` varchar(100) DEFAULT NULL,
  `photo` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `annonces`
--

INSERT INTO `annonces` (`id`, `user_id`, `nom_annonce`, `prix`, `description`, `categorie`, `photo`, `created_at`) VALUES
(22, 8, 'iPhone 12 en bon état', '280.00', 'iPhone 12 64Go, noir, fonctionne parfaitement. Quelques micro-rayures sur l’écran, batterie en bon état. Vendu avec chargeur.', 'Électronique', 'uploads/annonces/69de31b286565.jpg', '2026-04-14 12:23:14'),
(23, 8, 'Veste en cuir noir', '80.00', 'Veste en cuir véritable de couleur noire, taille M. Elle a déjà été portée mais reste en très bon état général. Le cuir est encore souple, sans déchirures ni défauts majeurs. Idéale pour un style casual ou habillé, parfaite pour l’automne et l’hiver.', 'Mode', 'uploads/annonces/69de333c1334b.jpg', '2026-04-14 12:29:48'),
(24, 8, 'Table basse moderne', '70.00', 'Table basse moderne combinant bois et métal avec un style industriel très tendance. Solide et stable, elle s’intègre parfaitement dans un salon contemporain. Peu utilisée, elle est en très bon état sans rayures importantes.', 'Maison', 'uploads/annonces/69de33af4ed36.jpg', '2026-04-14 12:31:43'),
(25, 8, 'Peugeot 208 2015', '6800.00', 'Peugeot 208 de 2015 en excellent état général avec 120 000 km au compteur. Véhicule bien entretenu, révisions effectuées régulièrement avec factures à l’appui. Aucun frais à prévoir, conduite agréable et économique. Idéale pour jeune conducteur ou usage quotidien.', 'Véhicules', 'uploads/annonces/69de33f61a3aa.jpg', '2026-04-14 12:32:54'),
(26, 8, 'Studio à louer centre-ville', '700.00', 'Studio meublé de 25 m² situé en centre-ville, proche de toutes commodités (transports, commerces, écoles). Logement lumineux et fonctionnel, équipé d’une kitchenette, salle de bain et espace de vie optimisé. Idéal pour étudiant ou jeune actif.', 'Maison', 'uploads/annonces/69de3458d63d8.jpg', '2026-04-14 12:34:32'),
(27, 8, 'Service de ménage à domicile', '15.00', '15euro/h Personne sérieuse et expérimentée propose ses services de ménage à domicile. Nettoyage complet, repassage, entretien des pièces de vie et des sanitaires. Travail soigné et ponctuel, possibilité d’interventions régulières ou ponctuelles.', 'Services', 'uploads/annonces/69de34d5ce709.jpg', '2026-04-14 12:36:37'),
(28, 8, 'Casque audio Bluetooth', '40.00', 'Casque audio sans fil Bluetooth offrant une bonne qualité sonore et une autonomie d’environ 10 heures. Confortable à porter grâce à ses coussinets rembourrés. Compatible avec smartphone, tablette et ordinateur. Très bon état.', 'Électronique', 'uploads/annonces/69de3531ae1e2.jpg', '2026-04-14 12:38:09'),
(29, 8, 'Adidas sneakers taille 43', '70.00', 'Baskets Adidas taille 43, déjà portées mais bien entretenues. Confortables et résistantes, elles conviennent aussi bien pour un usage quotidien que pour le sport. Quelques légères traces d’usure mais restent en très bon état général.', 'Mode', 'uploads/annonces/69de35e834fb9.jpg', '2026-04-14 12:41:12'),
(30, 8, 'Chaise de bureau ergonomique', '90.00', 'Chaise de bureau ergonomique avec réglage de la hauteur et support lombaire intégré. Très confortable pour le travail prolongé, idéale pour télétravail ou études. Structure solide et bon état général.', 'Maison', 'uploads/annonces/69de369f0330f.jpg', '2026-04-14 12:44:15'),
(31, 8, 'Réparation ordinateur à domicile', '40.00', 'A partir de 40 euro Négociable selonService de dépannage informatique à domicile : réparation de PC, installation de logiciels, suppression de virus et optimisation du système. Intervention rapide et efficace, adaptée aux particuliers comme aux professionnels.', 'Services', 'uploads/annonces/69de374679ecf.jpg', '2026-04-14 12:47:02'),
(32, 14, 'test', '10.00', 'addidas', 'Mode', 'uploads/annonces/69de7b098d67b.jpg', '2026-04-14 17:36:09'),
(33, 15, 'gshgfjsrhfj', '50.00', 'jrthgjdrth', 'Électronique', '', '2026-04-14 21:22:49'),
(34, 13, 'chien', '150.00', 'adorable, vacciné et en bonne santé. Cherche une famille aimante.', 'Autre', 'uploads/annonces/69deb378f3e00.jpg', '2026-04-14 21:36:57'),
(35, 13, 'Jeu FIFA 23 PS4', '60.00', 'Jeu en excellent état, complet avec boîte. Aucun défaut.', 'Autre', 'uploads/annonces/69deb43be98bd.jpg', '2026-04-14 21:40:11'),
(36, 13, 'PC Portable HP Pavilion', '550.00', 'Ordinateur rapide, parfait pour travail et multimédia. Batterie en bon état.', 'Électronique', 'uploads/annonces/69deb4910b9af.jpg', '2026-04-14 21:41:37'),
(37, 13, 'Vélo VTT Rockrider', '140.00', 'Vélo robuste, pneus récents, prêt à rouler. Idéal pour chemins et route.', 'Autre', 'uploads/annonces/69deb4e9b151c.jpg', '2026-04-14 21:43:05'),
(38, 13, 'Veste Nike noire taille M', '70.00', 'Veste légère, portée quelques fois. Très bon état.', 'Mode', 'uploads/annonces/69deb53a233e4.jpg', '2026-04-14 21:44:26');

-- --------------------------------------------------------

--
-- Structure de la table `favoris`
--

CREATE TABLE `favoris` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `annonce_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `favoris`
--

INSERT INTO `favoris` (`id`, `user_id`, `annonce_id`, `created_at`) VALUES
(1, 14, 31, '2026-04-14 17:48:16'),
(2, 14, 32, '2026-04-14 17:51:18'),
(3, 15, 31, '2026-04-14 21:23:40'),
(4, 13, 25, '2026-04-14 21:46:09'),
(7, 16, 29, '2026-04-14 22:48:56');

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `annonce_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lu` tinyint(1) DEFAULT '0',
  `parent_message_id` int(11) DEFAULT NULL,
  `sujet` varchar(255) DEFAULT NULL,
  `type` enum('message','offre','question','reponse','systeme') DEFAULT 'message',
  `piece_jointe` varchar(500) DEFAULT NULL,
  `statut` enum('envoye','lu','archive','supprime') DEFAULT 'envoye',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO `messages` (`id`, `annonce_id`, `sender_id`, `receiver_id`, `contenu`, `created_at`, `lu`, `parent_message_id`, `sujet`, `type`, `piece_jointe`, `statut`, `updated_at`) VALUES
(1, 27, 14, 8, 'bonjour firts message', '2026-04-14 18:18:42', 0, NULL, 'Conversation concernant : Service de ménage à domicile', 'message', NULL, 'envoye', '2026-04-14 20:18:42'),
(2, 27, 14, 8, 'salut second message', '2026-04-14 18:18:58', 0, 1, NULL, 'message', NULL, 'envoye', '2026-04-14 20:18:58'),
(3, 27, 14, 8, 'salut therd messgae', '2026-04-14 18:19:18', 0, 2, NULL, 'message', NULL, 'envoye', '2026-04-14 20:19:18'),
(4, 27, 14, 8, 'fourht message', '2026-04-14 18:19:32', 0, 3, NULL, 'message', NULL, 'envoye', '2026-04-14 20:19:32'),
(5, 25, 14, 8, 'bonjour first message avec leticia sur 208', '2026-04-14 18:22:54', 0, NULL, 'Conversation concernant : Peugeot 208 2015', 'message', NULL, 'envoye', '2026-04-14 20:22:54'),
(6, 25, 14, 8, 'cc deuxieeme message sur 208 avec leticia', '2026-04-14 18:23:20', 0, 5, NULL, 'message', NULL, 'envoye', '2026-04-14 20:23:20'),
(7, 31, 15, 8, 'Cc', '2026-04-14 21:23:52', 0, NULL, 'Conversation concernant : Réparation ordinateur à domicile', 'message', NULL, 'envoye', '2026-04-14 23:23:52'),
(8, 25, 13, 8, 'HTGDUJ', '2026-04-15 16:42:55', 0, NULL, 'Conversation concernant : Peugeot 208 2015', 'message', NULL, 'envoye', '2026-04-15 18:42:55');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_letim` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `sexe` enum('Homme','Femme','Autre') NOT NULL,
  `animal_prefere` varchar(50) NOT NULL,
  `date_de_naissance` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `avatar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id_letim`, `nom`, `prenom`, `email`, `password`, `sexe`, `animal_prefere`, `date_de_naissance`, `created_at`, `avatar`) VALUES
(8, 'Letitia', 'imad', 'letitiamors@gmail.com', '$2y$10$VikKZPJHXcmiq4PTi6TEPuYIvxIB7WmCsHDcOx3cdQv1N98Z.XyBq', 'Homme', 'Oiseau', '2000-11-11', '2026-04-07 00:43:35', 'uploads/avatars/avatar_69de78f1cc08e.jpeg'),
(13, 'yesmine', 'dellaai', 'yessminedellaai@gmail.com', '$2y$10$w18F2yGyZKfpjq8Xd.pac.ydwTgJuMTekQ5D4rRfZKkTJi9.exsa6', 'Femme', 'Cheval', '2000-02-02', '2026-04-13 13:31:43', 'uploads/avatars/avatar_69deb574df4ab.jpeg'),
(14, 'yasmine', 'dallal', 'test@gmail.com', '$2y$10$FyfXjT/w24ruvH.gI1lobOWKqAWPEFjlZZR4ExRdMwMNH1L8KtU5O', 'Femme', 'Chat', '2026-04-17', '2026-04-14 17:34:35', 'uploads/avatars/avatar_69de7b900f6d1.jpeg'),
(15, 'maghboune', 'rayane', 'rayanemaghboune@gmail.com', '$2y$10$XC1gFShscFHW5Kdz6wSyUOJtntSmywT9SlDN4AnRJekecfmAF9SjW', 'Homme', 'Poisson', '2000-03-15', '2026-04-14 21:20:21', NULL),
(16, 'uhuh', 'uuhuh', 'liloumrs24@gmail.com', '$2y$10$0TQ2tC56K6MIzWxO7wqDT.gRjI7HZJ4cKaPiVbxkcWEiTf2qhXtKK', 'Homme', 'Cheval', '2000-05-23', '2026-04-14 22:47:46', NULL),
(17, 'MAGHEBOUNE', 'RAYANE', 'rayane@gmail.com', '$2y$10$ccxhGk4JvuM8484I9NoE8uI4z7HJlTd3C7cpXqoQ/ivmZHn6Np53y', 'Homme', 'Chat', '2000-04-23', '2026-04-15 12:47:32', NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `annonces`
--
ALTER TABLE `annonces`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `favoris`
--
ALTER TABLE `favoris`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favori` (`user_id`,`annonce_id`),
  ADD KEY `annonce_id` (`annonce_id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `idx_conversation` (`annonce_id`,`sender_id`,`receiver_id`),
  ADD KEY `idx_parent` (`parent_message_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id_letim`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `annonces`
--
ALTER TABLE `annonces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT pour la table `favoris`
--
ALTER TABLE `favoris`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_letim` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `annonces`
--
ALTER TABLE `annonces`
  ADD CONSTRAINT `annonces_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateur` (`id_letim`) ON DELETE CASCADE;

--
-- Contraintes pour la table `favoris`
--
ALTER TABLE `favoris`
  ADD CONSTRAINT `favoris_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateur` (`id_letim`) ON DELETE CASCADE,
  ADD CONSTRAINT `favoris_ibfk_2` FOREIGN KEY (`annonce_id`) REFERENCES `annonces` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_parent_message` FOREIGN KEY (`parent_message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`annonce_id`) REFERENCES `annonces` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `utilisateur` (`id_letim`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `utilisateur` (`id_letim`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
