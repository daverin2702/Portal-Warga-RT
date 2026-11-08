<?php require 'config.php'; need(['admin','rt','warga']);
$s = isset($_GET['surat']) ? q('SELECT * FROM surat_terbit WHERE surat_id=?', [$_GET['surat']])->fetch() : q('SELECT * FROM surat_terbit WHERE id=?', [$_GET['id'] ?? 0])->fetch();
$terbit = $s && ($s['status'] ?? 'terbit') === 'terbit';
if (!$s || (!pengurus() && ($s['warga_id'] != wid() || !$terbit))) die('Surat tidak ditemukan.');
$url = base_url().'/verifikasi.php?t='.$s['token'];
$qr = $terbit ? (!empty($s['ttd_qr']) ? '<span class="qr-ttd" data-url="'.e($url).'"></span>' : '<span style="display:inline-block;height:24mm"></span>')
  : '<span style="display:inline-block;border:1px dashed #999;padding:6px 10px;font-size:8pt;color:#777">QR tanda tangan muncul<br>setelah disetujui Ketua</span>';
$isi = preg_replace('/\{\{\s*qr_ttd\s*\}\}/i', $qr, $s['isi']);
?><!doctype html>
<html lang="id"<?= tema_attr() ?>><head><meta charset="utf-8"><?= head_extra() ?><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($s['judul']) ?> - <?= e($s['nomor']) ?></title>
<?= aset_css() ?></head><body>
<div class="container-fluid py-3 d-flex gap-2 flex-wrap align-items-center no-print">
  <a class="btn btn-light" href="<?= pengurus() ? 'arsip.php' : 'surat.php' ?>">&larr; Kembali</a>
  <button class="btn btn-primary" onclick="print()">Cetak / Simpan PDF</button>
  <?php if (!$terbit): ?><span class="badge text-bg-warning">Draf: <?= e($s['status']) ?></span><?php endif; ?>
  <span class="text-secondary small ms-2">Di jendela cetak, pilih ukuran A4 dan matikan &ldquo;Header dan footer&rdquo;.</span>
</div>
<div class="a4-stage"><div class="a4"><?php if (!$terbit) echo '<div class="wm">DRAF</div>'; ?>
  <?= $s['pakai_kop'] ? kop_html() : '' ?><div class="a4-body"><?= $isi ?></div><?php if ($terbit && empty($s['ttd_qr'])): ?><div style="font-size:8pt;color:#555;margin-top:6px">Keaslian surat dapat dicek di <?= e(base_url()) ?>/ (menu Cek Surat) dengan nomor surat dan tanggal <?= e(date('d/m/Y', strtotime($s['created_at']))) ?>.</div><?php endif; ?><?= $s['pakai_kop'] ? footer_html() : '' ?></div></div>
<script src="<?= aset('qrcode.js') ?>"></script>
<script>
document.querySelectorAll('.qr-ttd').forEach(el=>{
  new QRCode(el,{text:el.dataset.url,width:88,height:88,correctLevel:QRCode.CorrectLevel.M});
  el.insertAdjacentHTML('beforeend','<div style="font-size:7pt;margin-top:2px">Pindai untuk verifikasi</div>');
});
</script></body></html>
