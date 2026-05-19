<?php
// ═══════════════════════════════════════════════
//  CONFIGURATION — Modifiez uniquement ce fichier
// ═══════════════════════════════════════════════

// Adresse qui recevra les messages du formulaire
define('MAIL_TO',      'nohamchamplon1@gmail.com');

// Nom affiché comme destinataire
define('MAIL_TO_NAME', 'noham CHAMPLON');

// Préfixe des sujets reçus
define('MAIL_SUBJECT_PREFIX', '[Portfolio] ');

// Adresse "From" des emails envoyés par le serveur
// → Doit correspondre au domaine de votre hébergeur
define('MAIL_FROM',      'no-reply@votredomaine.com');
define('MAIL_FROM_NAME', 'Formulaire Portfolio');

// Origines autorisées (votre domaine en prod, * en dev local)
// Ex : 'https://www.monportfolio.com'
define('ALLOWED_ORIGIN', '*');

// Limite anti-spam : nombre max de soumissions par IP par heure
define('RATE_LIMIT', 5);

// Dossier où stocker les logs de rate-limit (doit être accessible en écriture)
define('RATE_LIMIT_DIR', __DIR__ . '/rate_limit/');
