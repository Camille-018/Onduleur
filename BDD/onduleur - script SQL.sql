---- script SQL for UPS monitoring application ----
---> This SQL script creates the necessary database and tables for the UPS monitoring application.

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 02 mars 2026 à 12:35
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ups_onduleur`
--

-- --------------------------------------------------------

--
-- Structure de la table `alertes`
--

DROP TABLE IF EXISTS `alertes`;
CREATE TABLE IF NOT EXISTS `alertes` (
  `idAlerte` int NOT NULL AUTO_INCREMENT,
  `idCollecte` int NOT NULL,
  `type` varchar(55) NOT NULL,
  `message` varchar(255) NOT NULL,
  `heureAlerte` timestamp NOT NULL,
  PRIMARY KEY (`idAlerte`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- --------------------------------------------------------

--
-- Structure de la table `ups`
--

DROP TABLE IF EXISTS `ups`;
CREATE TABLE IF NOT EXISTS `ups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `device_serial` varchar(50) NOT NULL,
  `device_model` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_serial` (`device_serial`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- --------------------------------------------------------

--
-- Structure de la table `ups_history`
--

DROP TABLE IF EXISTS `ups_history`;
CREATE TABLE IF NOT EXISTS `ups_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ups_id` int NOT NULL,
  `battery_charge` int DEFAULT NULL,
  `battery_runtime` int DEFAULT NULL,
  `input_voltage` float DEFAULT '0',
  `output_voltage` float DEFAULT '0',
  `ups_load` int DEFAULT NULL,
  `ups_status` varchar(10) DEFAULT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ups_id` (`ups_id`)
) ENGINE=MyISAM AUTO_INCREMENT=154 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `mail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `status` enum('pending','active','refused') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
