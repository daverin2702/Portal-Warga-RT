<?php require 'config.php'; need(['admin','rt','warga']);
$t = q('SELECT * FROM tagihan WHERE id=?', [$_GET['id'] ?? 0])->fetch();
if (!$t || !$t['bukti'] || (!pengurus() && $t['warga_id'] != wid())) { http_response_code(404); die('Bukti tidak ditemukan.'); }
$f = __DIR__.'/uploads/bukti/'.basename($t['bukti']);
if (!is_file($f)) { http_response_code(404); die('Berkas tidak ada.'); }
$tipe = ['jpg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','pdf'=>'application/pdf'][strtolower(pathinfo($f, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
header('Content-Type: '.$tipe); header('X-Content-Type-Options: nosniff'); header('Content-Length: '.filesize($f)); readfile($f);
