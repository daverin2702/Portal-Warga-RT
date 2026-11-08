<?php require 'config.php'; require 'layout.php'; need(['admin']);
$hasil = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); $hasil = aset_unduh_semua(); }
$label = ['bootstrap.css'=>'Bootstrap 5.3.8 (CSS)','bootstrap.js'=>'Bootstrap 5.3.8 (JavaScript)','icons.css'=>'Bootstrap Icons 1.13.2','fonts.css'=>'Font: Plus Jakarta Sans, Inter, Fraunces',
          'chart.js'=>'Chart.js 4.4.1 (grafik dasbor)','qrcode.js'=>'QRCode.js (QR tanda tangan surat)','html5-qrcode.js'=>'html5-qrcode (pemindai Cek Surat)'];
head('Aset Offline', 'Simpan Bootstrap, ikon, font, dan pustaka JavaScript di server agar website tetap tampil rapi tanpa internet.'); ?>
<div class="row g-3">
  <div class="col-lg-6"><div class="panel">
    <h2 class="h5 mb-3">Status</h2>
    <ul class="list-unstyled mb-3">
      <?php foreach ($label as $k => $l): $ok = aset_lokal($k); ?>
        <li class="d-flex justify-content-between border-top py-2"><span><i class="bi <?= $ok ? 'bi-check-circle-fill text-success' : 'bi-cloud text-secondary' ?> me-2"></i><?= e($l) ?></span>
        <span class="small <?= $ok ? 'text-success' : 'text-secondary' ?>"><?= $ok ? 'Lokal' : 'Dari internet (CDN)' ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php if (aset_offline_lengkap()): ?><div class="alert alert-success small">Semua aset tersimpan lokal. Website bisa dibuka tanpa internet di jaringan yang sama.</div><?php endif; ?>
    <form method="post"><?= csrf_field() ?><button class="btn btn-primary"><i class="bi bi-cloud-download me-1"></i><?= aset_offline_lengkap() ? 'Unduh ulang aset' : 'Unduh aset sekarang' ?></button></form>
    <div class="form-text">Komputer server perlu terhubung internet saat tombol ini ditekan (sekali saja). Ukuran total sekitar 1&ndash;2 MB.</div>
  </div></div>
  <div class="col-lg-6">
    <?php if ($hasil): ?><div class="panel mb-3"><h2 class="h5 mb-3">Hasil unduhan</h2>
      <?php foreach ($hasil as [$n, $ok, $ket]) echo '<div class="d-flex justify-content-between border-top py-2 small"><span><i class="bi '.($ok ? 'bi-check-lg text-success' : 'bi-x-lg text-danger').' me-1"></i>'.e($n).'</span><span class="'.($ok ? 'text-secondary' : 'text-danger').' text-end ms-2">'.e($ket).'</span></div>'; ?>
    </div><?php endif; ?>
    <div class="panel small text-secondary"><h2 class="h6 fw-bold text-body">Yang tetap butuh internet</h2>
      <ul class="mb-0 ps-3"><li>Kamera untuk Cek Surat butuh https atau localhost (bukan soal internet).</li><li>Warga yang membuka dari luar jaringan tetap butuh internet untuk menjangkau server.</li><li>Jika berkas lokal terhapus, halaman otomatis kembali memakai CDN.</li></ul></div>
  </div>
</div>
<?php foot();
