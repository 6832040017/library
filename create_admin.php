<?php
// ใช้ผ่าน terminal เท่านั้น: php create_admin.php ชื่อผู้ใช้ 'รหัสผ่าน'
if (PHP_SAPI !== 'cli') { exit('CLI only'); }
require __DIR__ . '/config.php';
[$s, $un, $pw] = $argv + [null, null, null];
if (!$un || !$pw) { exit("วิธีใช้: php create_admin.php ชื่อผู้ใช้ 'รหัสผ่าน'\n"); }
db()->prepare('INSERT INTO users (username, password_hash, role) VALUES (?,?,"admin")')
    ->execute([$un, password_hash($pw, PASSWORD_DEFAULT)]);
echo "สร้าง admin '$un' แล้ว\n";
