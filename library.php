<?php
require __DIR__ . '/config.php';
require_login();

// หมวดหมู่ทั้งหมด พร้อมจำนวนหนังสือในแต่ละหมวด
$cats = db()->query('SELECT c.id, c.name, COUNT(b.id) AS n
    FROM categories c LEFT JOIN books b ON b.category_id = c.id AND b.hidden = 0
    GROUP BY c.id, c.name ORDER BY c.name')->fetchAll();
$total = (int)db()->query('SELECT COUNT(*) FROM books WHERE hidden = 0')->fetchColumn();
$uncat = (int)db()->query('SELECT COUNT(*) FROM books WHERE hidden = 0 AND (category_id IS NULL OR category_id NOT IN (SELECT id FROM categories))')->fetchColumn();

// ตัวกรอง: all | none | <id>
$sel = $_GET['cat'] ?? 'all';
$title = 'หนังสือทั้งหมด';
$sql = 'SELECT b.id, b.title, b.author, c.name AS cat_name
        FROM books b LEFT JOIN categories c ON c.id = b.category_id
        WHERE b.hidden = 0';
$args = [];
if ($sel === 'none') {
    $sql .= ' AND c.id IS NULL'; $title = 'ไม่ระบุหมวด';
} elseif (ctype_digit((string)$sel)) {
    foreach ($cats as $c) { if ((int)$c['id'] === (int)$sel) { $title = $c['name']; } }
    $sql .= ' AND b.category_id = ?'; $args[] = (int)$sel;
} else { $sel = 'all'; }
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $like = '%' . addcslashes($q, '\\%_') . '%';
    $sql .= ' AND (b.title LIKE ? OR b.author LIKE ?)';
    $args[] = $like; $args[] = $like;
}
$sql .= ' ORDER BY b.id DESC';
$st = db()->prepare($sql); $st->execute($args);
$books = $st->fetchAll();

function chip(string $key, string $label, int $n, string $sel, string $q): string {
    $p = [];
    if ($key !== 'all') $p['cat'] = $key;
    if ($q !== '') $p['q'] = $q;
    $href = 'library.php' . ($p ? '?' . http_build_query($p) : '');
    return '<a class="chip' . ($sel === $key ? ' active' : '') . '" href="' . e($href) . '">'
         . e($label) . ' <span class="n">' . $n . '</span></a>';
}

page_start($title); ?>
<h2><?= e($title) ?></h2>
<?php if ($cats) { ?>
<div class="chips">
<?= chip('all', 'ทั้งหมด', $total, $sel, $q) ?>
<?php foreach ($cats as $c) { echo chip((string)$c['id'], $c['name'], (int)$c['n'], $sel, $q); } ?>
<?php if ($uncat > 0) { echo chip('none', 'ไม่ระบุหมวด', $uncat, $sel, $q); } ?>
</div>
<?php } ?>
<form method="get" class="search">
  <?php if ($sel !== 'all') { ?><input type="hidden" name="cat" value="<?= e($sel) ?>"><?php } ?>
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="🔍 ค้นหาชื่อหนังสือหรือผู้แต่ง">
  <button>ค้นหา</button>
  <?php if ($q !== '') { ?><a class="cancel" href="library.php<?= $sel !== 'all' ? '?cat=' . e(urlencode($sel)) : '' ?>">ล้าง</a><?php } ?>
</form>
<?php if (!$books) { echo '<p>' . ($q !== '' ? 'ไม่พบหนังสือที่ค้นหา' : 'ยังไม่มีหนังสือในหมวดนี้') . '</p>'; } else { ?>
<div class="grid">
<?php foreach ($books as $b): ?>
  <div class="book">
    <img src="cover.php?id=<?= (int)$b['id'] ?>" alt="ปก <?= e($b['title']) ?>" loading="lazy">
    <div class="info">
      <?php if ($sel === 'all' && $b['cat_name']) { ?><span class="cat-tag"><?= e($b['cat_name']) ?></span><?php } ?>
      <div class="title"><?= e($b['title']) ?></div>
      <div class="author"><?= e($b['author']) ?></div>
      <a class="btn" href="read.php?id=<?= (int)$b['id'] ?>" target="_blank">อ่าน</a>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php } page_end();
