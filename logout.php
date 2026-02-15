<?php
declare(strict_types=1);

$config = require __DIR__.'/config.php';
session_name($config['session_name']);
session_start();

session_destroy();
header('Location: index.php');
exit;
