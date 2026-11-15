<?php
require __DIR__ . '/config.php';
require_login();
$books = db()->query('SELECT id, title, author FROM books ORDER BY id DESC')->fetchAll();
page_start('หนังสือทั้งหมด'); ?>
<h2>หนังสือทั้งหมด</h2>
<?php if (!$books) echo '<p>ยังไม่มีหนังสือ</p>'; else { ?>
<table><tr><th>ชื่อหนังสือ</th><th>ผู้แต่ง</th><th></th></tr>
<?php foreach ($books as $b): ?>
<tr><td><?= e($b['title']) ?></td><td><?= e($b['author']) ?></td>
<td><a href="read.php?id=<?= (int)$b['id'] ?>" target="_blank">อ่าน</a></td></tr>
<?php endforeach; ?></table>
<?php } page_end();
