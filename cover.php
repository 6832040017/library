<?php
require __DIR__ . '/config.php';
require_login();
$st = db()->prepare('SELECT filename FROM books WHERE id = ?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$b = $st->fetch();
$f = $b ? __DIR__ . '/covers/' . basename($b['filename'], '.pdf') . '.jpg' : '';
if ($b && is_file($f)) {
    header('Content-Type: image/jpeg');
    header('Cache-Control: private, max-age=86400');
    readfile($f);
    exit;
}
header('Content-Type: image/svg+xml');
echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 400"><rect width="300" height="400" fill="#e0e7ff"/>'
   . '<text x="150" y="215" font-size="64" text-anchor="middle" fill="#4f46e5" font-family="sans-serif">PDF</text></svg>';
