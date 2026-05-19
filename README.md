# Portfolio — Guide d'installation

## Structure des fichiers

```
portfolio/
├── index.html          ← Page principale (frontend)
├── api/
│   ├── config.php      ← ⚙️  Configuration (à modifier)
│   ├── contact.php     ← Endpoint d'envoi de mail
│   └── .htaccess       ← Protection du dossier api/
└── README.md
```

---

## ⚙️ Configuration (1 seul fichier à modifier)

Ouvrez `api/config.php` et renseignez :

```php
define('MAIL_TO',      'votre@email.com');       // Votre email de réception
define('MAIL_TO_NAME', 'Votre Nom');
define('MAIL_FROM',    'no-reply@votredomaine.com'); // Doit matcher votre hébergeur
define('ALLOWED_ORIGIN', 'https://www.monportfolio.com'); // Votre domaine en prod
```

---

## 🚀 Déploiement

### Option 1 — Hébergement mutualisé (OVH, o2switch, Infomaniak…)
C'est la méthode la plus simple. PHP et `mail()` sont disponibles nativement.

1. Uploadez **tous les fichiers** via FTP/SFTP à la racine de votre hébergement
2. Modifiez `api/config.php` avec votre email
3. C'est tout — le formulaire fonctionne immédiatement

### Option 2 — VPS / Serveur dédié
Assurez-vous d'avoir un serveur mail configuré (Postfix ou autre).
Un MTA mal configuré fera passer vos mails en spam.
Préférez alors la **méthode SMTP** ci-dessous.

### Option 3 — SMTP (recommandé pour la délivrabilité)

Remplacez `mail()` dans `api/contact.php` par **PHPMailer** pour utiliser
Gmail, Brevo (ex-Sendinblue), Mailgun, etc. :

```bash
composer require phpmailer/phpmailer
```

```php
// Dans contact.php, remplacez la section "Envoi" par :
use PHPMailer\PHPMailer\PHPMailer;
require 'vendor/autoload.php';

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';   // ou smtp.brevo.com, etc.
$mail->SMTPAuth   = true;
$mail->Username   = 'votrecompte@gmail.com';
$mail->Password   = 'votre_mot_de_passe_application'; // Mot de passe d'app Google
$mail->SMTPSecure = 'tls';
$mail->Port       = 587;

$mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
$mail->addAddress(MAIL_TO, MAIL_TO_NAME);
$mail->addReplyTo($email, $name);
$mail->Subject  = $subj;
$mail->isHTML(true);
$mail->Body     = $html_body;
$mail->AltBody  = $text_body;
$mail->send();
```

> Pour Gmail : activez la validation en 2 étapes puis créez un
> "Mot de passe d'application" dans votre compte Google.

---

## 🛡️ Sécurité incluse

- **Validation serveur** : tous les champs sont vérifiés côté PHP
- **Sanitisation** : `strip_tags()` sur toutes les entrées
- **Rate limiting** : max 5 messages par IP par heure (configurable)
- **CORS** : origine configurable via `ALLOWED_ORIGIN`
- **Protection `.htaccess`** : `config.php` inaccessible depuis le navigateur
- **Headers multipart** : emails compatibles HTML + texte brut

---

## 🧪 Tester en local

```bash
# Avec PHP intégré (PHP 7.4+)
php -S localhost:8000

# Ouvrir http://localhost:8000
```

> Note : `mail()` ne fonctionne pas en local sans serveur mail.
> Pour tester localement, utilisez **Mailpit** ou **MailHog** :
> ```bash
> # Mailpit (recommandé)
> brew install mailpit   # macOS
> mailpit                # Lance sur http://localhost:8025
> ```
> Puis dans `php.ini` : `sendmail_path = /usr/local/bin/mailpit --smtp-port 1025`
