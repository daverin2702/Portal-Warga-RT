<?php require 'config.php';
// Cadangan: database.sql sudah berisi akun contoh. File ini hanya berguna jika tabel users masih kosong.
if (q('SELECT COUNT(*) FROM users')->fetchColumn() > 0) { echo "Akun sudah ada (admin, rt, budi). Tidak ada yang perlu dilakukan. Hapus setup.php.\n"; exit; }
$sql = file_get_contents(__DIR__.'/seed.sql');
$sql = preg_replace('/^USE .*;$/m', '', $sql);
$pdo->exec($sql);
echo "Data awal dimasukkan. Akun: admin/admin123, rt/rt12345, budi/warga123. Hapus setup.php dan ganti semua kata sandi.\n";
