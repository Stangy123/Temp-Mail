<?php
declare(strict_types=1);

$config = require __DIR__.'/config.php';
session_name($config['session_name']);
session_start();

require __DIR__.'/MailTmClient.php';

$api = new MailTmClient($config['base_url']);
$error = null;

try {
  $domains = $api->getDomains();
} catch (Throwable $e) {
  $domains = [];
  $error = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $local = trim((string)($_POST['local'] ?? ''));
  $domain = trim((string)($_POST['domain'] ?? ''));
  $password = (string)($_POST['password'] ?? '');

  if ($local === '' || $domain === '' || $password === '') {
    $error = 'Please fill all fields.';
  } else {
    $address = $local . '@' . $domain;

    try {
      // Try create account; if exists, ignore and just token.
      try { $api->createAccount($address, $password); } catch (Throwable $ignored) {}

      $tokenRes = $api->createToken($address, $password);
      $token = (string)($tokenRes['token'] ?? '');

      if ($token === '') throw new RuntimeException('Token missing from API response.');

      $_SESSION['mailtm'] = [
        'address' => $address,
        'token'   => $token,
      ];

      header('Location: inbox.php');
      exit;
    } catch (Throwable $e) {
      $error = $e->getMessage();
    }
  }
}

$domainItems = $domains['hydra:member'] ?? [];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Mail.tm Inbox (PHP)</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:system-ui,Arial;max-width:760px;margin:40px auto;padding:0 16px;}
    .card{border:1px solid #ddd;border-radius:12px;padding:16px;}
    label{display:block;margin:10px 0 6px;}
    input,select{width:100%;padding:10px;border:1px solid #ccc;border-radius:10px;}
    button{margin-top:14px;padding:10px 14px;border:0;border-radius:10px;cursor:pointer;}
    .err{color:#b00020;margin:10px 0;}
    small{color:#555;}
  </style>
</head>
<body>
  <h2>Create / Login Inbox</h2>

  <?php if ($error): ?>
    <div class="err"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="post">
      <label>Local part (before @)</label>
      <input name="local" placeholder="e.g. mytest123" required>

      <label>Domain</label>
      <select name="domain" required>
        <option value="">-- choose --</option>
        <?php foreach ($domainItems as $d): ?>
          <?php $dom = (string)($d['domain'] ?? ''); ?>
          <?php if ($dom !== ''): ?>
            <option value="<?= htmlspecialchars($dom) ?>"><?= htmlspecialchars($dom) ?></option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>

      <label>Password</label>
      <input name="password" type="password" placeholder="set any password" required>

      <button type="submit">Open Inbox</button>
      <p><small>Testing only. Don’t use for important accounts.</small></p>
    </form>
  </div>
</body>
</html>
