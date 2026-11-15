<?php
session_start();
$local = __DIR__ . '/config.local.php';
if (!file_exists($local)) { die('ไม่พบไฟล์ config.local.php (ดู README.md)'); }
$cfg = require $local;

function db(): PDO {
    static $pdo = null;
    global $cfg;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host={$cfg['host']};dbname={$cfg['name']};charset=utf8mb4",
            $cfg['user'], $cfg['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function user() { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!user()) { header('Location: index.php'); exit; } }
function require_admin(): void {
    require_login();
    if (user()['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad request'); }
}
function page_start(string $title): void {
    $u = user();
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($title) . '</title>'
       . '<style>body{font-family:sans-serif;max-width:820px;margin:2rem auto;padding:0 1rem}'
       . 'nav{display:flex;gap:1rem;margin-bottom:1.5rem;align-items:center}'
       . 'table{border-collapse:collapse;width:100%}td,th{border-bottom:1px solid #ddd;padding:.5rem;text-align:left}'
       . 'input,select,button{padding:.5rem;margin:.25rem 0}.err{color:#b00}.ok{color:#080}'
       . 'fieldset{margin-bottom:1.5rem}</style></head><body><nav><strong>ห้องสมุดออนไลน์</strong>';
    if ($u) {
        echo '<a href="library.php">หนังสือ</a>';
        if ($u['role'] === 'admin') echo '<a href="admin.php">จัดการระบบ</a>';
        echo '<a href="logout.php">ออกจากระบบ (' . e($u['username']) . ')</a>';
    }
    echo '</nav>';
}
function page_end(): void { echo '</body></html>'; }
