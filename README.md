# 🇩🇯 Plateforme de Recrutement Djibouti

## 📌 Description
Plateforme web de recrutement connectant les candidats et les recruteurs à Djibouti.  
L'objectif est de simplifier le processus de recrutement grâce à une interface intuitive.

## ✨ Fonctionnalités

### 👤 Candidats
- Inscription et création de profil
- Recherche d'offres avec filtres
- Postulation en 1 clic
- Suivi des candidatures
- Gestion des favoris
- Alertes emploi personnalisées

### 🏢 Recruteurs
- Inscription avec validation administrative
- Publication d'offres d'emploi
- Gestion des candidatures reçues
- Visualisation des CV
- Planification d'entretiens
- Statistiques sur les offres

### 👑 Administrateurs
- Validation des comptes recruteurs
- Gestion des utilisateurs
- Gestion des offres
- Statistiques globales

## 🛠 Technologies utilisées

| Technologie | Version |
|-------------|---------|
| PHP | 8.x |
| MySQL | 5.7+ |
| HTML5/CSS3 | - |
| JavaScript | ES6 |
| Bootstrap | 5.3 |

## 📁 Structure du projet
recrutement-djibouti/
├── config/ # Configuration (base de données, email)
├── includes/ # Fichiers inclus (header, footer, auth)
├── pages/ # Pages par rôle (candidat, recruteur, admin)
├── assets/ # CSS, JS, images
├── uploads/ # Fichiers uploadés (CV, documents)
├── sql/ # Scripts SQL
├── index.php # Page d'accueil
├── connexion.php # Connexion
├── inscription.php # Inscription
└── README.md # Documentation

## 🚀 Installation

### Prérequis
- WampServer / XAMPP / MAMP
- PHP 8.x
- MySQL 5.7+

### Étapes

1. **Copier le dossier** dans `C:\wamp64\www\`
2. **Importer la base de données** via phpMyAdmin (fichier `sql/database.sql`)
3. **Configurer** `config/database.php` avec vos identifiants
4. **Accéder au site** : `http://localhost/recrutement-djibouti`

## 🔐 Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| **Candidat** | candidat@test.dj | test123 |
| **Recruteur** | recruteur@test.dj | test123 |
| **Administrateur** | kaderyoussoufelmi@gmail.com | 20232024 |

## 👨‍💻 Auteur
**Kader Youssouf Elmi**

## 📅 Version
1.0.0 - Mars 2026
