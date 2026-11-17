<?php
require __DIR__ . '/config.php';
if (user()) { header('Location: library.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $st = db()->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([trim($_POST['username'] ?? '')]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $u['id'], 'username' => $u['username'], 'role' => $u['role']];
        header('Location: library.php'); exit;
    }
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
}
page_start('เข้าสู่ระบบ'); ?>
<h2>เข้าสู่ระบบ</h2>
<?php if ($error) echo '<p class="err">' . e($error) . '</p>'; ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
  <div><input name="username" placeholder="ชื่อผู้ใช้" required></div>
  <div><input name="password" type="password" placeholder="รหัสผ่าน" required></div>
  <button>เข้าสู่ระบบ</button>
</form>
<p style="margin-top:1rem"><a href="signup.php" style="color:var(--c)">ขอใช้งาน (New user)</a></p>
<?php page_end();
