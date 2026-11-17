<?php
require __DIR__ . '/config.php';
if (user()) { header('Location: library.php'); exit; }
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $un = trim($_POST['username'] ?? '');
    $pw = $_POST['password'] ?? '';
    $pw2 = $_POST['password2'] ?? '';
    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $un)) {
        $err = 'ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษ/ตัวเลข ยาว 3-30 ตัวอักษร';
    } elseif (strlen($pw) < 8 || strlen($pw) > 72) {
        $err = 'รหัสผ่านต้องยาว 8-72 ตัวอักษร';
    } elseif ($pw !== $pw2) {
        $err = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
    } else {
        $pdo = db();
        $inUsers = $pdo->prepare('SELECT 1 FROM users WHERE username = ?');
        $inUsers->execute([$un]);
        $inReq = $pdo->prepare('SELECT 1 FROM signup_requests WHERE username = ?');
        $inReq->execute([$un]);
        $pending = (int)$pdo->query('SELECT COUNT(*) FROM signup_requests')->fetchColumn();
        if ($inUsers->fetch() || $inReq->fetch()) {
            $err = 'ชื่อผู้ใช้นี้ถูกใช้หรือมีคำขอค้างอยู่แล้ว กรุณาเลือกชื่ออื่น';
        } elseif ($pending >= 50) {
            $err = 'มีคำขอรออนุมัติมากเกินไป ลองใหม่ภายหลัง';
        } else {
            $pdo->prepare('INSERT INTO signup_requests (username, password_hash) VALUES (?, ?)')
                ->execute([$un, password_hash($pw, PASSWORD_DEFAULT)]);
            $msg = 'ส่งคำขอแล้ว กรุณารอ admin อนุมัติ จากนั้นเข้าสู่ระบบด้วยชื่อและรหัสผ่านที่ตั้งไว้';
        }
    }
}
page_start('ขอใช้งาน'); ?>
<h2>ขอใช้งาน (New user)</h2>
<?php if ($msg) echo '<p class="ok">' . e($msg) . '</p>'; if ($err) echo '<p class="err">' . e($err) . '</p>'; ?>
<?php if (!$msg): ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
  <input name="username" placeholder="ชื่อผู้ใช้ที่ต้องการ (อังกฤษ/ตัวเลข)" required>
  <input name="password" type="password" placeholder="รหัสผ่าน (8 ตัวขึ้นไป)" required>
  <input name="password2" type="password" placeholder="ยืนยันรหัสผ่าน" required>
  <button>ส่งคำขอ</button>
</form>
<?php endif; ?>
<p style="margin-top:1rem"><a href="index.php" style="color:var(--c)">← กลับหน้าเข้าสู่ระบบ</a></p>
<?php page_end();
