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
    } elseif ($action === 'edit_book') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? ''); $author = trim($_POST['author'] ?? '');
        $st = db()->prepare('SELECT filename FROM books WHERE id = ?'); $st->execute([$id]);
        $old = $st->fetch();
        $f = $_FILES['pdf'] ?? null;
        $hasNew = $f && $f['error'] !== UPLOAD_ERR_NO_FILE;
        if (!$old) { $err = 'ไม่พบหนังสือ'; }
        elseif ($title === '') { $err = 'กรุณากรอกชื่อหนังสือ'; }
        elseif ($hasNew && $f['error'] !== UPLOAD_ERR_OK) { $err = 'อัปโหลดไฟล์ไม่สำเร็จ (ไฟล์อาจใหญ่เกินที่ server กำหนด)'; }
        elseif ($hasNew && (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) !== 'application/pdf') { $err = 'รับเฉพาะไฟล์ PDF เท่านั้น'; }
        else {
            $filename = $old['filename'];
            if ($hasNew) {
                $name = bin2hex(random_bytes(16)) . '.pdf';
                if (move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/' . $name)) {
                    make_cover($name);
                    @unlink(__DIR__ . '/uploads/' . basename($old['filename']));
                    @unlink(__DIR__ . '/covers/' . basename($old['filename'], '.pdf') . '.jpg');
                    $filename = $name;
                } else { $err = 'บันทึกไฟล์ไม่สำเร็จ (เช็กสิทธิ์โฟลเดอร์ uploads)'; }
            }
            if (!$err) {
                db()->prepare('UPDATE books SET title = ?, author = ?, filename = ? WHERE id = ?')
                    ->execute([$title, $author, $filename, $id]);
                $msg = 'แก้ไขหนังสือแล้ว';
                $_GET['edit'] = null;
            } else { $_GET['edit'] = $id; }
        }
    } elseif ($action === 'toggle_book') {
        $id = (int)($_POST['id'] ?? 0);
        $hide = ($_POST['hide'] ?? '') === '1' ? 1 : 0;
        db()->prepare('UPDATE books SET hidden = ? WHERE id = ?')->execute([$hide, $id]);
        $msg = $hide ? 'ซ่อนหนังสือแล้ว (สมาชิกจะไม่เห็น)' : 'แสดงหนังสือแล้ว';
    } elseif ($action === 'delete_book') {
        $id = (int)($_POST['id'] ?? 0);
        $st = db()->prepare('SELECT filename, hidden FROM books WHERE id = ?'); $st->execute([$id]);
        if (($b = $st->fetch()) && !$b['hidden']) { $err = 'ต้องซ่อนหนังสือก่อน จึงจะลบถาวรได้'; }
        elseif ($b) {
            db()->prepare('DELETE FROM books WHERE id = ?')->execute([$id]);
            @unlink(__DIR__ . '/uploads/' . basename($b['filename']));
            @unlink(__DIR__ . '/covers/' . basename($b['filename'], '.pdf') . '.jpg');
            $msg = 'ลบหนังสือแล้ว';
        } else { $err = 'ไม่พบหนังสือ'; }
    }
}
$books = db()->query('SELECT id, title, author, hidden, created_at FROM books ORDER BY id DESC')->fetchAll();
$editing = null;
if (!empty($_GET['edit'])) {
    $st = db()->prepare('SELECT id, title, author FROM books WHERE id = ?'); $st->execute([(int)$_GET['edit']]);
    $editing = $st->fetch() ?: null;
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
<?php if ($editing): ?>
<fieldset><legend>แก้ไขหนังสือ</legend>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="edit_book"><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
  <input name="title" value="<?= e($editing['title']) ?>" placeholder="ชื่อหนังสือ" required>
  <input name="author" value="<?= e($editing['author']) ?>" placeholder="ผู้แต่ง">
  <label class="hint">เปลี่ยนไฟล์ PDF (ไม่บังคับ — ถ้าไม่เลือกจะใช้ไฟล์เดิม)</label>
  <input name="pdf" type="file" accept="application/pdf">
  <button>บันทึก</button> <a class="cancel" href="admin.php">ยกเลิก</a></form></fieldset>
<?php endif; ?>
<h3>หนังสือทั้งหมด (<?= count($books) ?>)</h3>
<?php if (!$books): ?><p>ยังไม่มีหนังสือ</p><?php else: ?>
<table><tr><th></th><th>ชื่อหนังสือ</th><th>ผู้แต่ง</th><th></th></tr>
<?php foreach ($books as $b): ?><tr class="<?= $b['hidden'] ? 'is-hidden' : '' ?>">
  <td class="thumb"><img src="cover.php?id=<?= (int)$b['id'] ?>" alt=""></td>
  <td><?= e($b['title']) ?><?php if ($b['hidden']): ?><span class="badge">ซ่อนอยู่</span><?php endif; ?></td><td><?= e($b['author']) ?></td>
  <td class="actions">
    <a href="admin.php?edit=<?= (int)$b['id'] ?>">แก้ไข</a>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="toggle_book"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="hide" value="<?= $b['hidden'] ? 0 : 1 ?>">
      <button class="soft"><?= $b['hidden'] ? 'แสดง' : 'ซ่อน' ?></button></form>
    <?php if ($b['hidden']): ?>
    <form method="post" onsubmit="return confirm('ลบ &quot;<?= e(addslashes($b['title'])) ?>&quot; ถาวร? ลบแล้วกู้คืนไม่ได้');">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="delete_book"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
      <button class="danger">ลบถาวร</button></form>
    <?php endif; ?>
  </td></tr><?php endforeach; ?>
</table>
<?php endif; ?>
<h3>สมาชิกทั้งหมด</h3>
<table><tr><th>ชื่อผู้ใช้</th><th>สิทธิ์</th><th>สร้างเมื่อ</th></tr>
<?php foreach ($users as $u): ?><tr><td><?= e($u['username']) ?></td><td><?= e($u['role']) ?></td><td><?= e($u['created_at']) ?></td></tr><?php endforeach; ?>
</table>
<?php page_end();
