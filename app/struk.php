<?php require 'config.php'; need(['admin','rt','warga']);
$t = q("SELECT t.*, w.nama_kk, w.alamat FROM tagihan t JOIN warga w ON w.id=t.warga_id WHERE t.id=? AND t.status='lunas'", [$_GET['id'] ?? 0])->fetch();
if (!$t || (!pengurus() && $t['warga_id'] != wid())) die('Struk tidak ditemukan.'); ?>
<!doctype html><html lang="id"<?= tema_attr() ?>><head><meta charset="utf-8"><?= head_extra() ?><title>Struk <?= e($t['no_struk']) ?></title>
<style>body{font-family:monospace;max-width:360px;margin:30px auto}hr{border:0;border-top:1px dashed #000}td{padding:2px 0}</style></head><body>
<h3 style="text-align:center;margin:0"><?= nama_rt() ?></h3><div style="text-align:center"><?= lokasi() ?></div><hr>
<table width="100%"><tr><td>No. struk</td><td align="right"><?= e($t['no_struk']) ?></td></tr>
<tr><td>Tanggal</td><td align="right"><?= date('d/m/Y H:i', strtotime($t['tgl_bayar'])) ?></td></tr>
<tr><td>Keluarga</td><td align="right"><?= e($t['nama_kk']) ?></td></tr>
<tr><td>Tagihan</td><td align="right"><?= e($t['jenis']) ?> <?= e($t['periode']) ?></td></tr>
<tr><td>Alokasi</td><td align="right"><?= e($t['alokasi']) ?></td></tr>
<tr><td>Metode</td><td align="right"><?= e($t['metode']) ?></td></tr></table><hr>
<table width="100%"><tr><td><strong>TOTAL</strong></td><td align="right"><strong><?= rp($t['nominal']) ?></strong></td></tr></table><hr>
<div style="text-align:center">LUNAS. Terima kasih.</div>
<p style="text-align:center"><button onclick="print()">Cetak</button></p></body></html>
