<?php require 'config.php'; require 'layout.php'; need(['admin','rt','warga']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && bisa('pengumuman')) {
  csrf_check();
  if (isset($_POST['hapus'])) { q('DELETE FROM pengumuman WHERE id=?', [$_POST['hapus']]); flash('Pengumuman dihapus.'); }
  else { q('INSERT INTO pengumuman (judul,isi,kategori) VALUES (?,?,?)', [trim($_POST['judul']), trim($_POST['isi']), $_POST['kategori']]); notif(uid_semua(), 'pengumuman', 'Pengumuman: '.trim($_POST['judul']), mb_substr(trim($_POST['isi']), 0, 140), 'pengumuman.php'); flash('Pengumuman diterbitkan.'); }
  header('Location: pengumuman.php'); exit;
}
head('Pengumuman'); ?>
<?php if (bisa('pengumuman')): ?>
<div class="panel mb-4"><h2 class="h5">Terbitkan pengumuman</h2>
<form method="post" class="row g-2"><?= csrf_field() ?>
  <div class="col-md-8"><input name="judul" class="form-control" placeholder="Judul" required></div>
  <div class="col-md-4"><select name="kategori" class="form-select"><?php foreach (['kegiatan','kerja bakti','siskamling','keamanan','lainnya'] as $k) echo "<option>$k</option>"; ?></select></div>
  <div class="col-12"><textarea name="isi" rows="3" class="form-control" placeholder="Isi pengumuman" required></textarea></div>
  <div class="col-12"><button class="btn btn-primary">Terbitkan</button></div>
</form></div>
<?php endif; ?>
<?php foreach (q('SELECT * FROM pengumuman ORDER BY id DESC') as $p): ?>
<div class="panel mb-3"><div class="d-flex justify-content-between"><div><span class="badge text-bg-secondary"><?= e($p['kategori']) ?></span> <small class="text-secondary"><?= tgl($p['created_at']) ?></small></div>
<?php if (bisa('pengumuman')): ?><form method="post" onsubmit="return confirm('Hapus pengumuman ini?')"><?= csrf_field() ?><button name="hapus" value="<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger">Hapus</button></form><?php endif; ?></div>
<h2 class="h5 mt-2"><?= e($p['judul']) ?></h2><p class="mb-0"><?= nl2br(e($p['isi'])) ?></p></div>
<?php endforeach; ?>
<?php foot();
