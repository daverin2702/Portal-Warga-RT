<?php require 'config.php'; require 'layout.php'; need(['admin','rt']); izin('profil_kop');
$teks = ['rt_nomor','rw_nomor','kelurahan','kecamatan','kota','kop1','kop2','kop3','kop4','kop_alamat','motto','rekening','base_url'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $v = [];
  foreach ($teks as $k) $v[$k] = trim($_POST[$k] ?? '');
  foreach (['logo_kanan'] as $k) $v[$k] = upload_img($k, cfg_raw($k));
  foreach ($v as $k => $x) q('INSERT INTO pengaturan (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)', [$k, $x]);
  flash('Profil dan kop surat disimpan.'); header('Location: profil.php'); exit;
}
function f($k, $l, $span = 6) { echo "<div class='col-md-$span'><label class='form-label'>$l</label><input name='$k' class='form-control' value='".e(cfg($k))."'></div>"; }
head('Profil & Kop Surat', 'Data ini tampil di halaman depan dan pada kop setiap surat.'); ?>
<form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
<div class="row g-3">
  <div class="col-xl-7">
    <div class="panel mb-3"><h2 class="h5 mb-3">Wilayah</h2><div class="row g-2">
      <?php f('rt_nomor','Nomor RT',3); f('rw_nomor','Nomor RW',3); f('kelurahan','Kelurahan / Desa',6); f('kecamatan','Kecamatan',6); f('kota','Kota / Kabupaten',6); ?>
    </div></div>
    <div class="panel mb-3"><h2 class="h5 mb-3">Kop surat</h2><div class="row g-2">
      <?php f('kop1','Baris 1 (instansi)',12); f('kop2','Baris 2 (kecamatan)',12); f('kop3','Baris 3 (kelurahan)',12); f('kop4','Baris 4 (RT / RW)',12); f('kop_alamat','Alamat / kontak sekretariat',12); ?>
      <?php f('motto','Moto RT (tampil di footer setiap surat)',12); ?>
      <div class="col-md-6"><label class="form-label">Logo kiri</label><div class="form-control bg-light text-secondary small">Memakai logo RT. Admin mengubahnya di Pengaturan Situs.</div></div>
      <div class="col-md-6"><label class="form-label">Logo kanan (opsional)</label><input type="file" name="logo_kanan" accept="image/*" class="form-control"></div>
      <?php f('base_url','Alamat situs publik untuk QR (opsional, mis. https://rt05.example.id)',12); ?>
    </div></div>
    <div class="panel mb-3"><h2 class="h5 mb-3">Info pembayaran untuk warga</h2>
      <label class="form-label">Rekening / e-wallet tujuan transfer (tampil di dasbor warga)</label><textarea name="rekening" rows="3" class="form-control"><?= e(cfg('rekening')) ?></textarea></div>
    <div class="alert alert-light border small">Profil Ketua dan Wakil di halaman depan kini diatur admin di menu Pengaturan Situs.</div>
    <button class="btn btn-primary btn-lg">Simpan perubahan</button>
  </div>
  <div class="col-xl-5"><div class="panel" style="position:sticky;top:1rem"><h2 class="h5 mb-3">Pratinjau kop (tersimpan)</h2>
    <div class="a4-stage rounded-3"><div class="a4" style="zoom:.45;min-height:0;padding:12mm"><?= kop_html() ?></div></div>
    <a class="d-block mt-3" href="index.php?pratinjau=1" target="_blank">Lihat halaman depan <i class="bi bi-box-arrow-up-right"></i></a>
  </div></div>
</div></form>
<?php foot();
