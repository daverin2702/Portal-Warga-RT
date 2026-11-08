<?php
function head($title, $sub = '') {
  $u = user(); $cur = basename($_SERVER['SCRIPT_NAME']);
  $menu = [
    ['dashboard.php','Dasbor','bi-grid-1x2','all'],
    ['persetujuan.php','Persetujuan','bi-check2-square','pengurus'],
    ['pengurus.php','Struktur Pengurus','bi-diagram-3','all'],
    ['pengumuman.php','Pengumuman','bi-megaphone','all'],
    ['kas.php','Kas RT','bi-bank','all'],
    ['laporan.php','Laporan Keuangan','bi-file-earmark-bar-graph','all'],
    ['#','Layanan','','all'],
    ['surat.php','Surat Pengantar','bi-envelope-paper','all'],
    ['tagihan.php','Iuran & Pembayaran','bi-wallet2','all'],
    ['aduan.php','Aduan & Aspirasi','bi-chat-left-text','all'],
    ['profil_saya.php','Profil Saya','bi-person-circle','all'],
    ['#','Kepengurusan','','pengurus'],
    ['warga.php','Data Warga','bi-people','pengurus'],
    ['buat_surat.php','Buat Surat','bi-pencil-square','cap:surat_buat'],
    ['templat.php','Templat Surat','bi-file-earmark-richtext','cap:templat'],
    ['arsip.php','Arsip Surat','bi-archive','pengurus'],
    ['jenis_tagihan.php','Jenis Tagihan','bi-tags','cap:tagihan_buat'],
    ['profil.php','Profil & Kop Surat','bi-building','cap:profil_kop'],
    ['#','Administrator','','admin'],
    ['akun.php','Akun Pengguna','bi-person-lock','admin'],
    ['impor.php','Impor Data Warga','bi-file-earmark-excel','admin'],
    ['pengaturan.php','Pengaturan Situs','bi-gear','admin'],
    ['tema.php','Tema Website','bi-palette','admin'],
    ['aset_offline.php','Aset Offline','bi-cloud-download','admin'],
  ];
  $label = ['admin'=>'Administrator','rt'=>'Pengurus RT','warga'=>'Warga'][$u['role']];
  $jb = $u['jabatan'] ?? 'warga'; if ($jb !== 'warga') $label = JABATAN[$jb] ?? $label;
  $logo = foto(cfg('logo'));
?>
<!doctype html>
<html lang="id"<?= tema_attr() ?>><head>
<meta charset="utf-8"><?= head_extra() ?><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | <?= e(nama_situs()) ?></title>
<?= aset_css() ?></head><body>
<div class="d-flex">
  <aside class="offcanvas-md offcanvas-start sidebar no-print" id="sb" tabindex="-1">
    <div class="brand">
      <?php if ($logo): ?><img src="<?= e($logo) ?>" alt=""><?php else: ?><div class="logo-ph">RT</div><?php endif; ?>
      <div><div class="fw-bold lh-1"><?= e(nama_situs()) ?></div><div class="small text-secondary"><?= e(brand_sub()) ?></div></div>
    </div>
    <nav class="nav flex-column">
      <?php foreach ($menu as [$f,$t,$i,$r]) if (menu_ok($r)):
        if ($f === '#') { echo "<div class='nav-label'>$t</div>"; continue; } ?>
        <a class="nav-link <?= $cur === $f ? 'active' : '' ?>" href="<?= $f ?>"><i class="bi <?= $i ?>"></i><?= $t ?></a>
      <?php endif; ?>
    </nav>
    <div class="me"><div class="fw-semibold"><?= e($u['nama']) ?></div><div class="text-secondary"><?= $label ?></div>
      <a href="logout.php" class="d-inline-block mt-2"><i class="bi bi-box-arrow-left me-1"></i>Keluar</a></div>
  </aside>
  <main class="flex-grow-1 p-3 p-md-4" style="min-width:0">
    <div class="topbar no-print">
      <button class="btn btn-light d-md-none" data-bs-toggle="offcanvas" data-bs-target="#sb" aria-label="Menu"><i class="bi bi-list"></i></button>
      <div class="flex-grow-1"></div>
      <button class="btn btn-danger btn-panic" data-bs-toggle="modal" data-bs-target="#mPanic" title="Tombol darurat"><i class="bi bi-exclamation-octagon-fill"></i><span class="d-none d-sm-inline ms-1">Darurat</span></button>
      <button class="btn btn-light position-relative" id="btnChat" title="Chat" aria-label="Chat"><i class="bi bi-chat-dots"></i><span class="dot d-none" id="chatBadge"></span></button>
      <div class="dropdown">
        <button class="btn btn-light position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" id="btnBell" aria-label="Notifikasi"><i class="bi bi-bell"></i><span class="dot d-none" id="bellBadge"></span></button>
        <div class="dropdown-menu dropdown-menu-end notif-menu p-0">
          <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom"><strong>Notifikasi</strong><button class="btn btn-link btn-sm p-0" id="bacaSemua">Tandai semua dibaca</button></div>
          <div id="notifList"><div class="p-3 small text-secondary">Memuat...</div></div>
          <div class="px-3 py-2 border-top small"><a href="#" id="izinBrowser">Aktifkan notifikasi browser</a></div>
        </div>
      </div>
    </div>
    <div class="mb-4 no-print"><h1 class="h3 fw-bold mb-1"><?= e($title) ?></h1><?php if ($sub) echo '<div class="text-secondary">'.e($sub).'</div>'; ?></div>
    <?php if (!empty($u['ganti_sandi']) && $cur !== 'profil_saya.php'): ?><div class="alert alert-warning no-print"><i class="bi bi-shield-exclamation me-1"></i>Anda masih memakai kata sandi bawaan. <a href="profil_saya.php?ganti=1" class="fw-semibold">Ganti sekarang</a></div><?php endif; ?>
    <?= flash_show() ?>
<?php }
function foot() { $u = user(); ?>
  </main>
</div>
<!-- Tombol darurat -->
<div class="modal fade no-print" id="mPanic" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:18px">
  <div class="modal-header border-0 pb-0"><h2 class="modal-title h5 fw-bold text-danger"><i class="bi bi-exclamation-octagon-fill me-1"></i>Kirim sinyal darurat</h2><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <p class="small text-secondary">Sinyal langsung muncul di layar semua pengurus dan warga yang sedang membuka website, lengkap dengan nama dan alamat Anda. Gunakan hanya untuk keadaan darurat sungguhan.</p>
    <div class="d-flex flex-wrap gap-2 mb-3" id="jenisDarurat"><?php foreach (JENIS_DARURAT as $i => $j) echo '<input type="radio" class="btn-check" name="jd" id="jd'.$i.'" value="'.e($j).'"'.($i ? '' : ' checked').'><label class="btn btn-outline-danger btn-sm" for="jd'.$i.'">'.e($j).'</label>'; ?></div>
    <textarea class="form-control" id="pesanDarurat" rows="2" maxlength="300" placeholder="Keterangan singkat (opsional), mis. asap dari rumah sebelah"></textarea>
    <div class="alert alert-light border small mt-3 mb-0">Untuk kebakaran, kecelakaan, atau kejahatan, hubungi juga <strong>112</strong>.</div>
  </div>
  <div class="modal-footer border-0"><button class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger fw-bold" id="kirimDarurat">Kirim sinyal darurat</button></div>
</div></div></div>
<!-- Peringatan darurat masuk -->
<div class="sos-layer d-none" id="sosLayer"><div class="sos-box"><div class="sos-ico"><i class="bi bi-exclamation-octagon-fill"></i></div><div id="sosIsi"></div></div></div>
<!-- Chat pop-up -->
<div class="chat-pop d-none no-print" id="chatPop">
  <div class="chat-head"><button class="btn btn-sm text-white d-none" id="chatBack" aria-label="Kembali"><i class="bi bi-arrow-left"></i></button><strong class="flex-grow-1" id="chatTitle">Chat</strong><button class="btn btn-sm text-white" id="chatClose" aria-label="Tutup"><i class="bi bi-x-lg"></i></button></div>
  <div class="chat-body" id="chatBody"></div>
  <form class="chat-form d-none" id="chatForm"><input class="form-control" id="chatInput" maxlength="1000" placeholder="Tulis pesan..." autocomplete="off"><button class="btn btn-primary" aria-label="Kirim"><i class="bi bi-send-fill"></i></button></form>
</div>
<script src="<?= aset('bootstrap.js') ?>"></script>
<script>window.RT=<?= json_encode(['csrf' => csrf(), 'me' => (int)$u['id'], 'pengurus' => pengurus()]) ?>;</script>
<script src="assets/app.js"></script>
</body></html>
<?php }
function kartu($label, $val, $href = '#') { ?>
  <div class="col-6 col-lg-3"><a href="<?= $href ?>" class="stat text-decoration-none">
    <div class="small text-secondary"><?= e($label) ?></div><div class="fs-4 fw-bold"><?= $val ?></div></a></div>
<?php }
function toolbar() { ?>
<div class="ed-bar no-print">
  <button type="button" class="btn btn-sm" data-cmd="undo" title="Urungkan"><i class="bi bi-arrow-counterclockwise"></i></button>
  <button type="button" class="btn btn-sm" data-cmd="redo" title="Ulangi"><i class="bi bi-arrow-clockwise"></i></button><span class="sep"></span>
  <button type="button" class="btn btn-sm" data-cmd="formatBlock" data-val="h3" title="Judul"><i class="bi bi-type-h3"></i></button>
  <button type="button" class="btn btn-sm" data-cmd="formatBlock" data-val="p" title="Paragraf"><i class="bi bi-paragraph"></i></button><span class="sep"></span>
  <button type="button" class="btn btn-sm" data-cmd="bold" title="Tebal"><i class="bi bi-type-bold"></i></button>
  <button type="button" class="btn btn-sm" data-cmd="italic" title="Miring"><i class="bi bi-type-italic"></i></button>
  <button type="button" class="btn btn-sm" data-cmd="underline" title="Garis bawah"><i class="bi bi-type-underline"></i></button><span class="sep"></span>
  <button type="button" class="btn btn-sm" data-cmd="justifyLeft"><i class="bi bi-text-left"></i></button>
  <button type="button" class="btn btn-sm" data-cmd="justifyCenter"><i class="bi bi-text-center"></i></button>
  <button type="button" class="btn btn-sm" data-cmd="justifyRight"><i class="bi bi-text-right"></i></button>
  <button type="button" class="btn btn-sm" data-cmd="justifyFull"><i class="bi bi-justify"></i></button><span class="sep"></span>
  <button type="button" class="btn btn-sm" data-cmd="insertUnorderedList"><i class="bi bi-list-ul"></i></button>
  <button type="button" class="btn btn-sm" data-cmd="insertOrderedList"><i class="bi bi-list-ol"></i></button>
  <button type="button" class="btn btn-sm" data-cmd="outdent"><i class="bi bi-text-indent-right"></i></button>
  <button type="button" class="btn btn-sm" data-cmd="indent"><i class="bi bi-text-indent-left"></i></button><span class="sep"></span>
  <button type="button" class="btn btn-sm" id="btnTable" title="Sisipkan tabel"><i class="bi bi-table"></i></button>
  <button type="button" class="btn btn-sm" data-cmd="insertHorizontalRule" title="Garis"><i class="bi bi-hr"></i></button>
  <button type="button" class="btn btn-sm" data-cmd="removeFormat" title="Hapus format"><i class="bi bi-eraser"></i></button>
</div>
<?php }

function menu_ok($r) {
  $u = user();
  return $r === 'all' || ($r === 'pengurus' && pengurus()) || ($r === 'admin' && $u['role'] === 'admin') || (strncmp($r, 'cap:', 4) === 0 && bisa(substr($r, 4)));
}
// Formulir unggah bukti bayar (dipakai di dasbor warga dan halaman tagihan)
function form_bukti($t, $back) { ?>
  <form method="post" action="tagihan.php" enctype="multipart/form-data" class="row g-2 align-items-end"><?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
    <div class="col-md-4"><label class="form-label">Metode</label><select name="metode" class="form-select form-select-sm"><?php foreach (METODE_BAYAR as $m) echo '<option>'.e($m).'</option>'; ?></select></div>
    <div class="col-md-5"><label class="form-label">Bukti bayar (foto/PDF, maks. 3 MB)</label><input type="file" name="bukti" accept="image/*,.pdf" class="form-control form-control-sm" required></div>
    <div class="col-md-3"><button name="upload" value="1" class="btn btn-sm btn-primary w-100">Kirim bukti</button></div>
  </form>
<?php }
