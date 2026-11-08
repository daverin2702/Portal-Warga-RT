<?php require 'config.php';
$s = q('SELECT * FROM surat_terbit WHERE token=?', [preg_replace('/[^a-f0-9]/', '', strtolower($_GET['t'] ?? ''))])->fetch();
$d = $s && $s['status'] === 'terbit' ? data_verifikasi($s) : null;
?><!doctype html>
<html lang="id"<?= tema_attr() ?>><head><meta charset="utf-8"><?= head_extra() ?><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verifikasi Surat | <?= e(nama_situs()) ?></title>
<?= aset_css() ?></head><body>
<div class="login-wrap"><div class="panel p-4 text-center" style="max-width:460px;width:100%;border-radius:20px">
<?php if ($d): ?>
  <div class="ficon mx-auto mb-3" style="background:#e3f6ec;color:#1a8f5a"><i class="bi bi-patch-check-fill"></i></div>
  <h1 class="h4 fw-bold">Surat asli dan berlaku</h1><p class="text-secondary">Tercatat resmi di arsip <?= nama_rt() ?>. Sebagian data disamarkan untuk melindungi privasi warga.</p>
  <table class="table table-sm text-start mb-0"><tbody>
    <tr><th>Nomor</th><td><?= e($d['nomor']) ?></td></tr><tr><th>Jenis</th><td><?= e($d['jenis']) ?></td></tr>
    <tr><th>Tanggal</th><td><?= e($d['tanggal']) ?></td></tr><tr><th>Pemohon</th><td><?= e($d['pemohon']) ?></td></tr>
    <tr><th>Tujuan</th><td><?= e($d['tujuan']) ?></td></tr><tr><th>Ditandatangani</th><td><?= e($d['penandatangan']) ?></td></tr></tbody></table>
<?php elseif ($s): ?>
  <div class="ficon mx-auto mb-3" style="background:#fff4d6;color:#b07d00"><i class="bi bi-hourglass-split"></i></div>
  <h1 class="h4 fw-bold">Surat belum berlaku</h1><p class="text-secondary mb-0">Surat ini tercatat tetapi belum disetujui Ketua RT.</p>
<?php else: ?>
  <div class="ficon mx-auto mb-3" style="background:#fde8e8;color:#c0392b"><i class="bi bi-x-octagon-fill"></i></div>
  <h1 class="h4 fw-bold">Surat tidak ditemukan</h1><p class="text-secondary mb-0">Kode tidak terdaftar atau surat sudah dicabut. Hubungi pengurus RT untuk memastikan.</p>
<?php endif; ?>
<a href="index.php" class="d-inline-block mt-3 small">Ke halaman depan <?= e(nama_situs()) ?></a>
</div></div></body></html>
