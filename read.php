<?php
require __DIR__ . '/config.php';
require_login();
$st = db()->prepare('SELECT * FROM books WHERE id = ?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$b = $st->fetch();
$path = $b ? __DIR__ . '/uploads/' . basename($b['filename']) : '';
if (!$b || !is_file($path)) { http_response_code(404); exit('ไม่พบหนังสือ'); }
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="book-' . (int)$b['id'] . '.pdf"');
header('Content-Length: ' . filesize($path));
readfile($path);
