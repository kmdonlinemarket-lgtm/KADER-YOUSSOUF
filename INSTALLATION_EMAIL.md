# 📧 Configuration des Emails - Recrutement Djibouti

## ⚠️ Problème Actuel

Votre application essaie d'envoyer des emails mais le serveur SMTP local n'est pas disponible sur WAMP.

## ✅ Solution : Utiliser PHPMailer avec Gmail SMTP

### étape 1️⃣ : Installer Composer

1. Téléchargez **Composer** : https://getcomposer.org/download/
2. Installez-le en suivant le guide officiel

### Étape 2️⃣ : Installer PHPMailer

Ouvrez PowerShell/CMD et navigez dans le dossier du projet :

```bash
cd C:\wamp64\www\recrutement-djibouti
composer install
```

Cela va créer un dossier `vendor/` avec PHPMailer.

### Étape 3️⃣ : Configurer Gmail pour PHPMailer 

**⚠️ IMPORTANT : Gmail nécessite un mot de passe d'application, pas votre mot de passe Gmail habituel**

#### Option A : Utiliser un mot de passe d'application Gmail (RECOMMANDÉ)

1. Allez sur : https://myaccount.google.com/apppasswords
2. Sélectionnez **Mail** et **Windows Computer**
3. Google génère un mot de passe à 16 caractères
4. Remplacez dans votre fichier `config/mail.php` :

```php
private $smtp_user = 'votre_email@gmail.com';
private $smtp_pass = 'xmpo zwqp tgwm plps'; // Le mot de passe généré par Google
```

#### Option B : Activer les "Mots de passe pour applications"

Si vous n'avez pas cette option, vous devez d'abord :
- Activer la **Vérification en deux étapes** sur votre compte Google
- Alors l'option "Mot de passe pour applications" apparaîtra

### Étape 4️⃣ : Vérifier que PHPMailer est chargé

Ouvrez `config/mail.php` - le code charge automatiquement PHPMailer via `autoload.php` du vendor.

### Étape 5️⃣ : Tester l'envoi d'email

Testez en créant un fichier `test_email.php` à la racine :

```php
<?php
require 'config/mail.php';

$mailer = new Mailer();
$result = $mailer->envoyerCodeVerification('votre_email@gmail.com', 'Tester', 123456);

if ($result) {
    echo "✅ Email envoyé avec succès !";
} else {
    echo "❌ Erreur lors de l'envoi de l'email";
}
?>
```

Puis accédez à : `http://localhost/recrutement-djibouti/test_email.php`

---

## 🚀 Alternatives (Si Gmail ne fonctionne pas)

### Option 1 : Utiliser Mailtrap (Service gratuit)

1. Créez un compte sur https://mailtrap.io
2. Récupérez vos identifiants SMTP
3. Mettez à jour `config/mail.php` :

```php
private $smtp_host = 'smtp.mailtrap.io';
private $smtp_port = 587;
private $smtp_user = 'votre_username_mailtrap';
private $smtp_pass = 'votre_password_mailtrap';
```

### Option 2 : Utiliser SendGrid

Même principe que Mailtrap mais avec les identifiants SendGrid.

### Option 3 : Mercury Mail (Inclus avec WAMP)

1. Lancez Mercury Mail depuis **WAMP Control Panel**
2. Configurez `config/mail.php` :

```php
private $smtp_host = 'localhost';
private $smtp_port = 25;
```

---

## ❌ Dépannage

| Erreur | Solution |
|--------|----------|
| "Failed to connect to mailserver" | PHPMailer n'est pas installé, exécutez `composer install` |
| "SMTP connect() failed" | Vérifiez les identifiants SMTP et activez "Less secure apps" |
| "PHPMailer not found" | Vérifiez que le dossier `vendor/` existe après `composer install` |
| "Connection timeout" | Votre pare-feu bloque port 587, essayez port 25 ou 465 |

---

## 📝 Résumé des étapes

✅ `composer install` - Installe PHPMailer
✅ Configurez Gmail (mot de passe d'application)
✅ Mettez à jour `config/mail.php` avec vos identifiants
✅ Testez avec `test_email.php`

**Après cela, vos emails d'inscription et de vérification fonctionneront ! 🎉**
