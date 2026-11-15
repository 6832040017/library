<?php
require __DIR__ . '/config.php';
require_admin();
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add_user') {
        $un = trim($_POST['username'] ?? ''); $pw = $_POST['password'] ?? '';
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'member';
        if ($un === '' || strlen($pw) < 8) { $err = 'ต้องมีชื่อผู้ใช้ และรหัสผ่านอย่างน้อย 8 ตัวอักษร'; }
        else {
            try {
                db()->prepare('INSERT INTO users (username, password_hash, role) VALUES (?,?,?)')
                    ->execute([$un, password_hash($pw, PASSWORD_DEFAULT), $role]);
                $msg = 'เพิ่มสมาชิกแล้ว';
            } catch (PDOException $ex) { $err = 'ชื่อผู้ใช้นี้ถูกใช้แล้ว'; }
        }
    } elseif ($action === 'add_book') {
        $title = trim($_POST['title'] ?? ''); $author = trim($_POST['author'] ?? '');
        $f = $_FILES['pdf'] ?? null;
        if ($title === '' || !$f || $f['error'] !== UPLOAD_ERR_OK) { $err = 'กรอกชื่อหนังสือและเลือกไฟล์ PDF (ไฟล์อาจใหญ่เกินที่ server กำหนด)'; }
        elseif ((new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) !== 'application/pdf') { $err = 'รับเฉพาะไฟล์ PDF เท่านั้น'; }
        else {
            $name = bin2hex(random_bytes(16)) . '.pdf';
            if (move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/' . $name)) {
                make_cover($name);
                db()->prepare('INSERT INTO books (title, author, filename) VALUES (?,?,?)')->execute([$title, $author, $name]);
                $msg = 'เพิ่มหนังสือแล้ว';
            } else { $err = 'บันทึกไฟล์ไม่สำเร็จ (เช็กสิทธิ์โฟลเดอร์ uploads)'; }
        }
    }
}
$users = db()->query('SELECT username, role, created_at FROM users ORDER BY id')->fetchAll();
page_start('จัดการระบบ'); ?>
<h2>จัดการระบบ</h2>
<?php if ($msg) echo '<p class="ok">' . e($msg) . '</p>'; if ($err) echo '<p class="err">' . e($err) . '</p>'; ?>
<fieldset><legend>เพิ่มสมาชิก</legend>
<form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="add_user">
  <input name="username" placeholder="ชื่อผู้ใช้" required>
  <input name="password" type="password" placeholder="รหัสผ่าน (8 ตัวขึ้นไป)" required>
  <select name="role"><option value="member">member</option><option value="admin">admin</option></select>
  <button>เพิ่ม</button></form></fieldset>
<fieldset><legend>เพิ่มหนังสือ (PDF)</legend>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="add_book">
  <input name="title" placeholder="ชื่อหนังสือ" required>
  <input name="author" placeholder="ผู้แต่ง">
  <input name="pdf" type="file" accept="application/pdf" required>
  <button>อัปโหลด</button></form></fieldset>
<h3>สมาชิกทั้งหมด</h3>
<table><tr><th>ชื่อผู้ใช้</th><th>สิทธิ์</th><th>สร้างเมื่อ</th></tr>
<?php foreach ($users as $u): ?><tr><td><?= e($u['username']) ?></td><td><?= e($u['role']) ?></td><td><?= e($u['created_at']) ?></td></tr><?php endforeach; ?>
</table>
<?php page_end();
