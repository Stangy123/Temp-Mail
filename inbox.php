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

$api = new MailTmClient($config['base_url']);
$token = (string)$_SESSION['mailtm']['token'];
$address = (string)$_SESSION['mailtm']['address'];

$error = null;
try {
  $messages = $api->listMessages($token);
} catch (Throwable $e) {
  $messages = [];
  $error = $e->getMessage();
}

$items = $messages['hydra:member'] ?? [];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Inbox</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:system-ui,Arial;max-width:900px;margin:40px auto;padding:0 16px;}
    a{color:inherit}
    .top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;}
    .card{border:1px solid #ddd;border-radius:12px;padding:14px;margin:12px 0;}
    .muted{color:#666}
    .err{color:#b00020;margin:10px 0;}
    button{padding:8px 12px;border:0;border-radius:10px;cursor:pointer;}
  </style>
</head>
<body>
  <div class="top">
    <div>
      <h2>Inbox</h2>
      <div class="muted">Address: <strong><?= htmlspecialchars($address) ?></strong></div>
    </div>
    <div>
      <a href="inbox.php"><button>Refresh</button></a>
      <a href="logout.php"><button>Logout</button></a>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="err"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!$items): ?>
    <p class="muted">No messages yet.</p>
  <?php endif; ?>

  <?php foreach ($items as $m): ?>
    <?php
      $id = (string)($m['id'] ?? '');
      $from = (string)($m['from']['address'] ?? '');
      $subject = (string)($m['subject'] ?? '(no subject)');
      $intro = (string)($m['intro'] ?? '');
      $seen = !empty($m['seen']);
    ?>
    <div class="card">
      <div><strong><?= htmlspecialchars($subject) ?></strong> <?= $seen ? '<span class="muted">(seen)</span>' : '' ?></div>
      <div class="muted">From: <?= htmlspecialchars($from) ?></div>
      <p><?= htmlspecialchars($intro) ?></p>
      <a href="message.php?id=<?= urlencode($id) ?>">Open</a>
    </div>
  <?php endforeach; ?>
</body>
</html>
