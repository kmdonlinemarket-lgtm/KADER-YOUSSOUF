
## 🚀 Installation

### Prérequis
- WampServer / XAMPP / MAMP
- PHP 8.x
- MySQL 5.7+

### Étapes

1. **Copier le dossier** dans `C:\wamp64\www\`
2. **Importer la base de données** via phpMyAdmin (fichier `sql/database.sql`)
3. **Configurer** `config/database.php` avec vos identifiants MySQL
4. **Configurer** `config/mail.php` avec vos identifiants SMTP (Gmail recommandé)
5. **Créer les dossiers** `uploads/cv/`, `uploads/documents/`, `uploads/temp/`
6. **Accéder au site** : `http://localhost/recrutement-djibouti`

### Configuration Email (Gmail)
Pour que les emails fonctionnent :
1. Activez l'authentification à deux facteurs sur votre compte Gmail
2. Générez un mot de passe d'application dans les paramètres de sécurité
3. Remplacez `smtp_user` et `smtp_pass` dans `config/mail.php`

## 🔐 Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| **Candidat** | candidat@test.dj | test123 |
| **Recruteur** | recruteur@test.dj | test123 |
| **Administrateur** | kaderyoussoufelmi@gmail.com | 20232024 |

## 📋 Fonctionnalités détaillées

### 🔔 Notifications
- Notifications en temps réel dans le tableau de bord
- Emails automatiques pour :
  - Confirmation d'inscription
  - Validation de compte recruteur
  - Nouvelle candidature reçue
  - Réponse à une candidature
  - Invitation à un entretien
  - Rappel d'entretien
  - Alertes emploi personnalisées

### 📄 Documents requis (Recruteurs)
- CNI / Passeport
- Registre de commerce / Extrait RCCM
- Attestation fiscale / Patente

### 💰 Abonnements
| Type | Offres/mois | CV/offre | Prix mensuel |
|------|-------------|----------|--------------|
| Gratuit | 3 | 50 | 0 FDJ |
| Pro | 20 | 200 | 25 000 FDJ |
| Entreprise | Illimité | Illimité | 75 000 FDJ |

## 👨‍💻 Équipe de développement

| Nom | Rôle |
|-----|------|
| **Kader Youssouf Elmi** | Chef de projet / Développeur Full Stack |
| **Hamze Idriss Guelleh** | Développeur Backend / Base de données |
| **Asma Adaweh Aouled** | Développeuse Frontend / UI/UX |
| **Marwa Charmake Hassan** | Développeuse Frontend / Intégration |

## 🙏 Remerciements

Nous tenons à exprimer notre profonde gratitude à toutes les personnes qui ont contribué à la réalisation de ce projet :

### Encadrement
- **DR Moubarki ** pour leur accompagnement, leurs conseils précieux et leur disponibilité tout au long du développement.

### Équipe technique
- **L'équipe de développement** pour leur engagement, leur travail acharné et leur passion qui ont permis de mener ce projet à bien malgré les défis techniques.

### Testeurs
- **Tous les testeurs bêta** qui ont pris le temps de tester la plateforme et de fournir des retours constructifs.

### Communauté
- **La communauté Djiboutienne** pour son soutien et son intérêt pour cette initiative locale.

### Remerciements spéciaux
- À nos familles pour leur patience et leur soutien indéfectible
- À tous ceux qui ont cru en ce projet et nous ont encouragés

*Votre confiance et vos encouragements nous motivent à continuer d'améliorer cette plateforme au service du développement professionnel à Djibouti.*

## 📅 Version
1.0.0 - Mars 2026

## 📧 Contact
77013736

---

**© 2026 Recrutement Djibouti - Tous droits réservés**
