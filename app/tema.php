<?php require 'config.php'; require 'layout.php'; need(['admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  if (array_key_exists($_POST['tema'] ?? '', TEMA)) { q('INSERT INTO pengaturan (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)', ['tema', $_POST['tema']]); flash('Tema "'.TEMA[$_POST['tema']][0].'" diterapkan ke seluruh website.'); }
  header('Location: tema.php'); exit;
}
$aktif = cfg('tema');
head('Tema Website', 'Pilih tampilan untuk seluruh halaman. Kop dan isi surat tetap memakai format resmi.'); ?>
<div class="row g-3">
<?php foreach (TEMA as $k => [$nama, $desk, $w]): $on = $aktif === $k; $dark = $k === 'biru'; ?>
  <div class="col-md-6 col-xl-4"><div class="tema-card <?= $on ? 'aktif' : '' ?>">
    <div class="d-flex justify-content-between align-items-start"><h2 class="h5 fw-bold mb-1"><?= e($nama) ?></h2><?php if ($on) echo '<span class="badge text-bg-success">Aktif</span>'; ?></div>
    <p class="small text-secondary mb-2"><?= e($desk) ?></p>
    <div class="swatch"><?php foreach ($w as $c) echo "<span style='background:$c' title='$c'></span>"; ?></div>
    <div class="mini" style="background:<?= $w[3] ?>">
      <div class="sb" style="background:<?= $dark ? '#132F52' : '#fff' ?>;border-right:1px solid #e5e9f0">
        <div class="ln" style="width:60%;background:<?= $w[2] ?>"></div>
        <div class="ln" style="background:<?= $dark ? 'rgba(255,255,255,.18)' : $w[1] ?>"></div>
        <div class="ln" style="width:80%;background:<?= $dark ? 'rgba(255,255,255,.1)' : '#eef1f5' ?>"></div>
        <div class="ln" style="width:70%;background:<?= $dark ? 'rgba(255,255,255,.1)' : '#eef1f5' ?>"></div>
      </div>
      <div class="ct">
        <div class="ln" style="width:45%;height:9px;background:#334155"></div>
        <div class="d-flex gap-1 mb-2"><?php for ($i = 0; $i < 3; $i++) echo "<div style='flex:1;height:30px;background:#fff;border-radius:".($k === 'terakota' ? 10 : ($k === 'biru' ? 5 : 8))."px'></div>"; ?></div>
        <div style="width:38%;height:14px;border-radius:<?= $k === 'terakota' ? 99 : 5 ?>px;background:<?= $w[0] ?>"></div>
      </div>
    </div>
    <div class="d-flex gap-2">
      <form method="post"><?= csrf_field() ?><button name="tema" value="<?= $k ?>" class="btn btn-primary btn-sm" <?= $on ? 'disabled' : '' ?>><?= $on ? 'Sedang dipakai' : 'Terapkan' ?></button></form>
      <a class="btn btn-light btn-sm" target="_blank" href="dashboard.php?pratinjau_tema=<?= $k ?>">Pratinjau dasbor</a>
      <a class="btn btn-light btn-sm" target="_blank" href="index.php?pratinjau=1&amp;pratinjau_tema=<?= $k ?>">Pratinjau beranda</a>
    </div>
  </div></div>
<?php endforeach; ?>
</div>
<div class="small text-secondary mt-3">Pratinjau hanya terlihat oleh Anda dan tidak mengubah tampilan warga sampai tombol Terapkan ditekan.</div>
<?php foot();
