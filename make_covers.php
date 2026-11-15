<?php
// สร้างปกให้หนังสือเดิมที่ยังไม่มีปก: php make_covers.php
if (PHP_SAPI !== 'cli') { exit('CLI only'); }
require __DIR__ . '/config.php';
foreach (db()->query('SELECT id, title, filename FROM books')->fetchAll() as $b) {
    $f = __DIR__ . '/covers/' . basename($b['filename'], '.pdf') . '.jpg';
    if (is_file($f)) { echo "มีปกแล้ว: {$b['title']}\n"; continue; }
    echo (make_cover($b['filename']) ? "สร้างปกแล้ว: " : "สร้างปกไม่สำเร็จ: ") . $b['title'] . "\n";
}
