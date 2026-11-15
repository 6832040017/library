<?php
require __DIR__ . '/config.php';
require_login();
$books = db()->query('SELECT id, title, author FROM books ORDER BY id DESC')->fetchAll();
page_start('หนังสือทั้งหมด'); ?>
<h2>หนังสือทั้งหมด</h2>
<?php if (!$books) { echo '<p>ยังไม่มีหนังสือ</p>'; } else { ?>
<div class="grid">
<?php foreach ($books as $b): ?>
  <div class="book">
    <img src="cover.php?id=<?= (int)$b['id'] ?>" alt="ปก <?= e($b['title']) ?>" loading="lazy">
    <div class="info">
      <div class="title"><?= e($b['title']) ?></div>
      <div class="author"><?= e($b['author']) ?></div>
      <a class="btn" href="read.php?id=<?= (int)$b['id'] ?>" target="_blank">อ่าน</a>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php } page_end();
