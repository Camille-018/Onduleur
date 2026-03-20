# 🔌 Projet CIEL2 – Suivi d’Onduleurs (UPS)

## 📌 Description

Application web pour le **suivi et la supervision des onduleurs (UPS)** du CEREEP.  

Fonctionnalités principales :  

- Collecte automatique des données depuis Raspberry Pi / ESP32  
- Historique et dashboard interactif  
- Filtrage avancé des données  
- Système d’alertes configurable (email)  
- Gestion des utilisateurs : inscription, connexion, mot de passe oublié  

---

## ⚙️ Fonctionnalités

### 1. Collecte des données
- Serveur NUT sur Raspberry Pi  
- Envoi des données au format JSON vers le serveur web  
- Script `collector/auto_collect.php` pour insertion automatique en base  
- Multi-onduleur supporté  

### 2. Dashboard et Historique
- Affichage des mesures : charge batterie, tension, statut, etc.  
- Graphes merge input/output voltage  
- Filtrage avancé : `=`, `>`, `<`, `LIKE`, `BETWEEN`  
- Pagination : 15 lignes par page  

### 3. Système d’alertes
- Seuils définis dans `config/config_seuils.json`  
- Script `alerte/verifierAlerte.php` analyse les données et envoie email  
- Envoi **1 mail par collecte**, même si plusieurs alertes  
- Types : surcharge, batterie faible, coupure, OFF, Bypass  

### 4. Gestion des utilisateurs
- Rôles : `admin`, `technicien`, `guest`  
- Status : `pending`, `active`, `refused`  
- Authentification sécurisée  
- MDP oublié : token limité dans le temps, vérification `used_at` et `expires_at`  
- Auto logout après 10 minutes d’inactivité  

---

## 🗂️ Structure du projet
_PROJET/
│
├── index.php # Dashboard
├── ups.php # Vue onduleurs
│
├── _sujetProjet/ # Documents projet
│
├── alerte/ # Gestion alertes
│ ├── alerte.php
│ ├── verifierAlerte.php
│ └── changerSeuils.php
│
├── auth/ # Authentification
│ ├── login.php
│ ├── logout.php
│ ├── sInscrire.php
│ ├── forgotPassword.php
│ └── authCheck.php
│
├── BDD/ # Base de données
│ ├── script SQL.sql
│ └── MCD
│
├── collector/ # Collecte des données UPS
│ └── auto_collect.php
│
├── config/ # Config et seuils
│ ├── config.php
│ └── config_seuils.json
│
├── historique/ # Historique & filtres
│ ├── historique.php
│ └── valeurSpecifique.php
│
├── style/ # CSS et navbar
│ ├── style.css
│ ├── auth.css
│ └── navbar.php
│
└── PHPMailer/ # Librairie pour envoi mails


---

## 🗄️ Base de données

Tables principales :  

| Table | Description |
|-------|-------------|
| `ups` | Infos onduleurs (id, device_serial, device_model) |
| `users` | Authentification (id, username, password, mail, role, status) |
| `password_resets` | Tokens pour mot de passe oublié (id, user_id, token_hash, created_at, expires_at, used_at) |
| `ups_history` | Historique des mesures UPS (id, ups_id, battery_charge, battery_runtime, input_voltage, output_voltage, ups_load, ups_status, timestamp) |
| `alertes` | Alertes détectées (idAlerte, idCollecte, type, message, heureAlerte) |

> ⚠️ PHP en UTC, BDD en UTC+1  

---

## 🔄 Collecte et API

- Collecte automatique via `collector/auto_collect.php`  
- API JSON disponible sur Raspberry Pi / ESP32  
- Multi-onduleur pris en charge  

---

## 🚨 Alertes et emails

- Alertes définies par seuils (`config/config_seuils.json`)  
- Emails envoyés aux admins et techniciens  
- PHPMailer avec logo intégré  
- Vérification anti-doublons : un seul mail par collecte  

---

## 🛠️ Installation

1. Cloner le projet  
2. Importer la BDD (`BDD/script SQL.sql`)  
3. Configurer la connexion dans `config/config.php`  
4. Lancer serveur local (XAMPP / WAMP)  
5. Accéder à : `http://localhost/_PROJET`  

---

## 🎯 Objectifs pédagogiques

- Développement web PHP / SQL  
- Gestion base de données relationnelle  
- Application complète avec collecte et supervision  
- Gestion multi-utilisateurs et rôles  

---

## 👤 Auteurs

| Étudiant | Rôle |
|----------|------|
| Hélène   | Raspberry Pi, collecte de données |
| Quentin  | API / Interface Web, Dashboard & alertes |
| Camille  | Site web historique, alertes, gestion utilisateurs |

