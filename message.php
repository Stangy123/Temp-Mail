<?php
declare(strict_types=1);

$config = require __DIR__.'/config.php';
session_name($config['session_name']);
session_start();

require __DIR__.'/MailTmClient.php';

if (empty($_SESSION['mailtm']['token'])) {
  header('Location: index.php');
  exit;
}

$id = (string)($_GET['id'] ?? '');
if ($id === '') {
  header('Location: inbox.php');
  exit;
}

$api = new MailTmClient($config['base_url']);
$token = (string)$_SESSION['mailtm']['token'];

$error = null;
try {
  $msg = $api->getMessage($token, $id);
} catch (Throwable $e) {
  $msg = null;
  $error = $e->getMessage();
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Message</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:system-ui,Arial;max-width:900px;margin:40px auto;padding:0 16px;}
    pre{white-space:pre-wrap;word-wrap:break-word;background:#f6f6f6;padding:14px;border-radius:12px;}
    .muted{color:#666}
    .err{color:#b00020;margin:10px 0;}
  </style>
</head>
<body>
  <p><a href="inbox.php">← Back</a></p>

  <?php if ($error): ?>
    <div class="err"><?= htmlspecialchars($error) ?></div>
  <?php else: ?>
    <h2><?= htmlspecialchars((string)($msg['subject'] ?? '(no subject)')) ?></h2>
    <div class="muted">
      From: <?= htmlspecialchars((string)($msg['from']['address'] ?? '')) ?>
      • To: <?= htmlspecialchars((string)($msg['to'][0]['address'] ?? '')) ?>
    </div>

    <h3>Text</h3>
    <pre><?= htmlspecialchars((string)($msg['text'] ?? '')) ?></pre>

    <h3>HTML (raw)</h3>
    <pre><?= htmlspecialchars((string)($msg['html'][0] ?? '')) ?></pre>
  <?php endif; ?>
</body>
</html>
