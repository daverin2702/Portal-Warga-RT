<?php require 'config.php';
if (user() && !(user()['role'] === 'admin' && isset($_GET['pratinjau']))) { header('Location: dashboard.php'); exit; }
try { $kk = q('SELECT COUNT(*) FROM warga')->fetchColumn(); $jiwa = q('SELECT COALESCE(SUM(jml_anggota),0) FROM warga')->fetchColumn(); $nblok = q("SELECT COUNT(DISTINCT blok) FROM warga WHERE blok<>''")->fetchColumn(); } catch (PDOException $x) { $kk = $jiwa = $nblok = 0; }
function kartu_profil($jabatan, $nama, $masa, $pesan, $foto) { ?>
  <div class="profil-card text-center">
    <?php if ($foto): ?><img class="avatar mx-auto" src="<?= e($foto) ?>" alt="<?= e($nama) ?>"><?php else: ?><div class="avatar mx-auto"><?= e(inisial($nama)) ?></div><?php endif; ?>
    <div class="text-uppercase small fw-bold text-secondary mt-3" style="letter-spacing:.08em"><?= $jabatan ?></div>
    <h3 class="h5 fw-bold mb-1"><?= e($nama) ?></h3>
    <div class="small text-secondary mb-3"><?= e($masa) ?></div>
    <?php if ($pesan): ?><p class="mb-0 text-secondary">&ldquo;<?= e($pesan) ?>&rdquo;</p><?php endif; ?>
  </div>
<?php }
$logo = foto(cfg('logo')); $heroImg = foto(cfg('hero_gambar')); ?>
<!doctype html>
<html lang="id"<?= tema_attr() ?>><head>
<meta charset="utf-8"><?= head_extra() ?><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(nama_situs()) ?> &middot; <?= nama_rt() ?></title>
<?= aset_css() ?></head><body>
<?php if (user()): ?><div class="bg-warning-subtle text-center small py-1">Mode pratinjau admin. <a href="pengaturan.php">Kembali ke Pengaturan Situs</a></div><?php endif; ?>
<header class="topnav"><div class="container d-flex justify-content-between align-items-center py-2">
  <a href="index.php" class="d-flex align-items-center gap-2 text-decoration-none text-reset">
    <?php if ($logo): ?><img src="<?= e($logo) ?>" width="40" height="40" class="rounded-3" style="object-fit:cover" alt=""><?php else: ?><span class="plate" style="font-size:.8rem">RT</span><?php endif; ?>
    <span class="lh-sm"><strong class="d-block"><?= e(nama_situs()) ?></strong><span class="text-secondary small"><?= e(brand_sub()) ?></span></span></a>
  <div class="dropdown">
    <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">Masuk</button>
    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius:12px">
      <li><a class="dropdown-item" href="login.php?role=warga"><i class="bi bi-house-door me-2"></i>Warga</a></li>
      <li><a class="dropdown-item" href="login.php?role=rt"><i class="bi bi-person-badge me-2"></i>Pengurus RT</a></li>
      <li><a class="dropdown-item" href="login.php?role=admin"><i class="bi bi-shield-lock me-2"></i>Administrator</a></li>
    </ul>
  </div>
</div></header>

<section class="hero"><div class="container py-5">
  <div class="row align-items-center g-4 py-lg-4">
    <div class="<?= $heroImg ? 'col-lg-6' : 'col-lg-8' ?>">
      <span class="plate mb-3"><?= e(cfg('hero_badge') ?: nama_rt()) ?></span>
      <h1 class="display-5 fw-bold mt-3"><?= e(cfg('hero_judul')) ?></h1>
      <p class="lead text-secondary"><?= e(cfg('hero_lead')) ?></p>
      <div class="d-flex flex-wrap gap-2 mt-4">
        <?php if (cfg('hero_btn1')): ?><a href="login.php?role=warga" class="btn btn-primary btn-lg px-4"><?= e(cfg('hero_btn1')) ?></a><?php endif; ?>
        <?php if (cfg('hero_btn2')): ?><a href="#pengurus" class="btn btn-light btn-lg px-4"><?= e(cfg('hero_btn2')) ?></a><?php endif; ?>
      </div>
      <div class="text-secondary small mt-3"><i class="bi bi-geo-alt me-1"></i><?= e(lokasi()) ?></div>
    </div>
    <?php if ($heroImg): ?><div class="col-lg-6"><img src="<?= e($heroImg) ?>" class="hero-img" alt=""></div><?php endif; ?>
  </div>
</div></section>

<?php if (cfg('tampil_statistik') === '1'): ?>
<section class="container" style="margin-top:-1.5rem;position:relative;z-index:2"><div class="row g-3 justify-content-center">
  <?php foreach ([['Kepala Keluarga', $kk, 'bi-house-heart'], ['Jumlah Jiwa', $jiwa, 'bi-people'], ['Blok', $nblok, 'bi-signpost-split']] as [$l, $n, $i]): ?>
  <div class="col-4 col-md-3"><div class="stat text-center"><i class="bi <?= $i ?> fs-4" style="color:var(--brand)"></i><div class="fs-3 fw-bold lh-1 mt-1"><?= number_format((float)$n, 0, ',', '.') ?></div><div class="small text-secondary"><?= $l ?></div></div></div>
  <?php endforeach; ?>
</div></section>
<?php endif; ?>

<section class="container py-5" id="pengurus">
  <div class="text-center mb-4"><h2 class="fw-bold"><?= e(cfg('pengurus_judul') ?: 'Pengurus '.nama_rt()) ?></h2><?php if (cfg('pengurus_sub')): ?><p class="text-secondary"><?= e(cfg('pengurus_sub')) ?></p><?php endif; ?></div>
  <div class="row g-4 justify-content-center">
    <div class="col-md-6 col-lg-4"><?php kartu_profil('Ketua RT', cfg('ketua_nama'), cfg('ketua_masa'), cfg('ketua_sambutan'), foto(cfg('ketua_foto'))); ?></div>
    <div class="col-md-6 col-lg-4"><?php kartu_profil('Wakil Ketua RT', cfg('wakil_nama'), cfg('wakil_masa'), cfg('wakil_sambutan'), foto(cfg('wakil_foto'))); ?></div>
  </div>
  <div class="text-center mt-4"><a href="login.php?role=warga" class="small">Lihat struktur pengurus lengkap setelah masuk</a></div>
</section>

<?php $fitur = fitur_list(); if ($fitur): ?>
<section class="container pb-5"><div class="row g-4">
  <?php foreach ($fitur as $f): ?>
  <div class="col-md-6 col-lg-4 d-flex gap-3"><div class="ficon"><i class="bi <?= e(in_array($f['i'] ?? '', IKON_FITUR) ? $f['i'] : 'bi-star') ?>"></i></div><div><h3 class="h6 fw-bold mb-1"><?= e($f['j'] ?? '') ?></h3><p class="text-secondary mb-0"><?= e($f['d'] ?? '') ?></p></div></div>
  <?php endforeach; ?>
</div></section>
<?php endif; ?>
<footer class="border-top bg-white"><div class="container py-3 small text-secondary d-flex justify-content-between flex-wrap gap-2">
  <span><?php if (cfg('footer_kiri')): echo e(cfg('footer_kiri')); else: ?><?= nama_rt() ?> &middot; <?= e(lokasi()) ?><?= cfg('motto') ? ' &middot; <em>'.e(cfg('motto')).'</em>' : '' ?><?php endif; ?></span>
  <span><?= e(cfg('footer_kanan')) ?></span></div></footer>
<!-- Pop-up cek keaslian surat -->
<button class="cek-fab" id="cekFab" aria-label="Cek keaslian surat"><i class="bi bi-qr-code-scan"></i><span>Cek Surat</span></button>
<div class="cek-pop d-none" id="cekPop" role="dialog" aria-label="Cek keaslian surat">
  <div class="chat-head"><strong class="flex-grow-1"><i class="bi bi-patch-check me-1"></i>Cek keaslian surat</strong><button class="btn btn-sm text-white" id="cekClose" aria-label="Tutup"><i class="bi bi-x-lg"></i></button></div>
  <div class="p-3">
    <ul class="nav nav-pills nav-fill mb-3 small"><li class="nav-item"><button class="nav-link active" data-cek="scan"><i class="bi bi-camera me-1"></i>Scan QR</button></li><li class="nav-item"><button class="nav-link" data-cek="manual"><i class="bi bi-keyboard me-1"></i>Input manual</button></li></ul>
    <div id="cekScan">
      <div id="cekReader" class="cek-reader"><div class="text-center text-secondary small p-4"><i class="bi bi-qr-code fs-1 d-block mb-2"></i>Arahkan kamera ke kode QR di bagian tanda tangan surat.</div></div>
      <div class="d-flex gap-2 mt-2"><button class="btn btn-primary flex-grow-1" id="cekMulai"><i class="bi bi-camera-video me-1"></i>Nyalakan kamera</button>
        <label class="btn btn-light" title="Pindai dari foto"><i class="bi bi-image me-1"></i>Foto<input type="file" accept="image/*" id="cekFoto" hidden></label></div>
      <select class="form-select form-select-sm mt-2 d-none" id="cekKamera" aria-label="Pilih kamera"></select>
      <div class="form-text">Kamera hanya berfungsi di alamat https atau localhost. Tidak ada kamera? Pilih foto kode QR atau pakai Input manual.</div>
    </div>
    <form id="cekManual" class="d-none">
      <label class="form-label">Nomor surat</label><input class="form-control mb-2" id="cekNomor" placeholder="mis. 001/RT.05/RW.02/XI/2026" required>
      <label class="form-label">Tanggal pembuatan surat</label><input type="date" class="form-control mb-3" id="cekTgl" required>
      <button class="btn btn-primary w-100">Cek surat</button>
    </form>
    <div id="cekHasil" class="mt-3"></div>
  </div>
</div>
<script src="<?= aset('bootstrap.js') ?>"></script>
<script>window.ASET_QR=<?= json_encode(aset('html5-qrcode.js')) ?>;</script>
<script src="assets/cek.js"></script>
</body></html>
