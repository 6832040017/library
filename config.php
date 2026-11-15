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
const APP_CSS = <<<'CSS'
:root{--c:#4f46e5;--c2:#7c3aed;--bg:#f3f4f8;--tx:#1f2937}
*{box-sizing:border-box}
body{margin:0;font-family:'Prompt','Noto Sans Thai',system-ui,sans-serif;background:var(--bg);color:var(--tx)}
nav{background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;padding:.9rem 1.5rem;display:flex;gap:1rem;align-items:center;flex-wrap:wrap;box-shadow:0 2px 10px rgba(0,0,0,.15)}
nav strong{font-size:1.2rem;margin-right:auto}
nav a{color:#fff;text-decoration:none;opacity:.9;padding:.35rem .8rem;border-radius:999px}
nav a:hover{background:rgba(255,255,255,.2);opacity:1}
main{max-width:900px;margin:2rem auto;background:#fff;padding:2rem;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.06)}
.guest main{max-width:400px;margin-top:6rem}
.guest nav{justify-content:center}
.guest nav strong{margin:0}
h2{margin-top:0;color:var(--c)}
input,select{width:100%;padding:.7rem .9rem;margin:.35rem 0;border:1px solid #d1d5db;border-radius:10px;font:inherit}
input:focus,select:focus{outline:2px solid var(--c);border-color:transparent}
button{background:linear-gradient(135deg,var(--c),var(--c2));color:#fff;border:0;padding:.7rem 1.4rem;border-radius:10px;font:inherit;cursor:pointer;margin-top:.5rem}
button:hover{opacity:.9}
table{width:100%;border-collapse:collapse;margin-top:1rem}
th{text-align:left;color:#6b7280;font-weight:500;font-size:.9rem}
th,td{padding:.8rem .6rem;border-bottom:1px solid #eee}
tr:hover td{background:#f9fafb}
td a{background:#eef2ff;color:var(--c);text-decoration:none;padding:.3rem .9rem;border-radius:999px;font-size:.9rem}
td a:hover{background:var(--c);color:#fff}
fieldset{border:1px solid #e5e7eb;border-radius:12px;margin:0 0 1.5rem;padding:1rem 1.25rem}
legend{padding:0 .5rem;font-weight:600;color:var(--c)}
.err{background:#fef2f2;color:#b91c1c;padding:.7rem 1rem;border-radius:10px}
.ok{background:#f0fdf4;color:#15803d;padding:.7rem 1rem;border-radius:10px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1.25rem;margin-top:1.25rem}
.book{background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;display:flex;flex-direction:column;transition:transform .15s,box-shadow .15s}
.book:hover{transform:translateY(-4px);box-shadow:0 10px 24px rgba(79,70,229,.18)}
.book img{width:100%;aspect-ratio:3/4;object-fit:contain;background:#eef2ff;display:block}
.book .info{padding:.75rem .85rem 1rem;display:flex;flex-direction:column;gap:.25rem;flex:1}
.book .title{font-weight:600;font-size:.95rem;line-height:1.3}
.book .author{color:#6b7280;font-size:.85rem}
.btn{display:inline-block;margin-top:auto;background:#eef2ff;color:var(--c);text-decoration:none;padding:.35rem 1rem;border-radius:999px;font-size:.9rem;text-align:center}
.btn:hover{background:var(--c);color:#fff}
CSS;

function make_cover(string $pdfName): bool {
    $dir = __DIR__ . '/covers';
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
    $base = basename($pdfName, '.pdf');
    $cmd = 'pdftoppm -jpeg -r 70 -f 1 -l 1 -singlefile '
         . escapeshellarg(__DIR__ . '/uploads/' . $pdfName) . ' '
         . escapeshellarg($dir . '/' . $base) . ' 2>&1';
    @shell_exec($cmd);
    return is_file($dir . '/' . $base . '.jpg');
}

function page_start(string $title): void {
    $u = user();
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($title) . '</title>'
       . '<link rel="preconnect" href="https://fonts.googleapis.com">'
       . '<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">'
       . '<style>' . APP_CSS . '</style></head><body' . ($u ? '' : ' class="guest"') . '>'
       . '<nav><strong>📚 6832040017\'s Online Library</strong>';
    if ($u) {
        echo '<a href="library.php">หนังสือ</a>';
        if ($u['role'] === 'admin') echo '<a href="admin.php">จัดการระบบ</a>';
        echo '<a href="logout.php">ออกจากระบบ (' . e($u['username']) . ')</a>';
    }
    echo '</nav><main>';
}
function page_end(): void { echo '</main></body></html>'; }
