<?php
// ═══════════════════════════════════════════════
//  api/contact.php — Endpoint d'envoi de mail
// ═══════════════════════════════════════════════

require_once __DIR__ . '/config.php';

// ── Headers CORS & JSON ──────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Méthode POST uniquement ──────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// ── Lecture du body JSON ─────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    // Fallback : données de formulaire classiques
    $data = $_POST;
}

// ── Récupération & nettoyage des champs ──────────
$name    = isset($data['name'])    ? trim(strip_tags($data['name']))    : '';
$email   = isset($data['email'])   ? trim(strip_tags($data['email']))   : '';
$subject = isset($data['subject']) ? trim(strip_tags($data['subject'])) : '';
$message = isset($data['message']) ? trim(strip_tags($data['message'])) : '';

// ── Validation ───────────────────────────────────
$errors = [];

if (empty($name) || mb_strlen($name) < 2) {
    $errors[] = 'Le nom est requis (min. 2 caractères).';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Adresse email invalide.';
}
if (empty($subject) || mb_strlen($subject) < 3) {
    $errors[] = 'Le sujet est requis (min. 3 caractères).';
}
if (empty($message) || mb_strlen($message) < 10) {
    $errors[] = 'Le message est trop court (min. 10 caractères).';
}
if (mb_strlen($message) > 5000) {
    $errors[] = 'Le message est trop long (max. 5000 caractères).';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── Rate limiting (par IP, fichier plat) ─────────
$ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ip_hash  = md5($ip); // on ne stocke pas l'IP brute
$rl_dir   = RATE_LIMIT_DIR;
$rl_file  = $rl_dir . $ip_hash . '.json';

if (!is_dir($rl_dir)) {
    mkdir($rl_dir, 0700, true);
}

$now       = time();
$window    = 3600; // 1 heure
$max       = RATE_LIMIT;
$attempts  = [];

if (file_exists($rl_file)) {
    $attempts = json_decode(file_get_contents($rl_file), true) ?? [];
    // Garder seulement les tentatives dans la fenêtre
    $attempts = array_filter($attempts, fn($t) => ($now - $t) < $window);
}

if (count($attempts) >= $max) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Trop de tentatives. Réessayez dans une heure.'
    ]);
    exit;
}

$attempts[] = $now;
file_put_contents($rl_file, json_encode(array_values($attempts)));

// ── Construction de l'email ───────────────────────
$to      = MAIL_TO;
$subj    = MAIL_SUBJECT_PREFIX . mb_substr($subject, 0, 100);
$date    = date('d/m/Y à H:i');

// Corps HTML
$html_body = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"/>
<style>
  body  { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:20px; }
  .box  { background:#fff; max-width:600px; margin:0 auto; border-radius:6px;
          border:1px solid #ddd; overflow:hidden; }
  .hdr  { background:#0D0D0D; padding:24px 30px; }
  .hdr h1 { color:#64ffda; font-size:18px; margin:0; font-family:'Courier New',monospace; }
  .body { padding:28px 30px; color:#333; }
  .row  { margin-bottom:18px; }
  .lbl  { font-size:11px; text-transform:uppercase; letter-spacing:.08em;
          color:#999; margin-bottom:4px; }
  .val  { font-size:15px; color:#111; }
  .msg  { background:#f9f9f9; border-left:3px solid #64ffda;
          padding:14px 16px; border-radius:0 4px 4px 0;
          white-space:pre-wrap; font-size:14px; line-height:1.6; }
  .ftr  { padding:16px 30px; background:#f9f9f9; border-top:1px solid #eee;
          font-size:12px; color:#aaa; }
</style></head>
<body>
<div class="box">
  <div class="hdr"><h1>// Nouveau message — Portfolio</h1></div>
  <div class="body">
    <div class="row"><div class="lbl">Nom</div><div class="val">{$name}</div></div>
    <div class="row"><div class="lbl">Email</div><div class="val"><a href="mailto:{$email}" style="color:#0066cc">{$email}</a></div></div>
    <div class="row"><div class="lbl">Sujet</div><div class="val">{$subject}</div></div>
    <div class="row"><div class="lbl">Message</div><div class="msg">{$message}</div></div>
  </div>
  <div class="ftr">Reçu le {$date} · Envoyé depuis le formulaire de contact du portfolio</div>
</div>
</body></html>
HTML;

// Corps texte brut (fallback)
$text_body = "Nouveau message depuis le portfolio\n"
           . "=====================================\n"
           . "Nom    : {$name}\n"
           . "Email  : {$email}\n"
           . "Sujet  : {$subject}\n"
           . "Date   : {$date}\n\n"
           . "Message :\n{$message}\n";

// ── Headers mail ─────────────────────────────────
$boundary = '----=_Part_' . md5(uniqid());

$headers  = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "To: " . MAIL_TO_NAME . " <{$to}>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$body  = "--{$boundary}\r\n";
$body .= "Content-Type: text/plain; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$body .= $text_body . "\r\n";
$body .= "--{$boundary}\r\n";
$body .= "Content-Type: text/html; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$body .= $html_body . "\r\n";
$body .= "--{$boundary}--";

// Encode le sujet en UTF-8 pour les caractères spéciaux
$encoded_subj = '=?UTF-8?B?' . base64_encode($subj) . '?=';

// ── Envoi ─────────────────────────────────────────
$sent = mail($to, $encoded_subj, $body, $headers);

if ($sent) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Votre message a bien été envoyé !'
    ]);
} else {
    // Erreur serveur mail — log discret
    error_log("[contact.php] mail() failed for {$ip_hash} at {$date}");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur lors de l\'envoi. Contactez-moi directement par email.'
    ]);
}
