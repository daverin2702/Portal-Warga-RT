<?php require 'config.php'; require 'layout.php'; need(['admin','rt','warga']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  if (bisa('aduan') && isset($_POST['id'])) { q('UPDATE aduan SET status=?, tanggapan=? WHERE id=?', [$_POST['status'], trim($_POST['tanggapan']), $_POST['id']]); flash('Aduan diperbarui.'); $ad = q('SELECT * FROM aduan WHERE id=?', [$_POST['id']])->fetch(); if ($ad) notif(uid_warga($ad['warga_id']), 'aduan', 'Aduan Anda '.$ad['status'], $ad['judul'].(trim($_POST['tanggapan']) ? ': '.trim($_POST['tanggapan']) : ''), 'aduan.php'); }
  elseif (!pengurus()) { q('INSERT INTO aduan (warga_id,kategori,judul,deskripsi) VALUES (?,?,?,?)', [wid(), $_POST['kategori'], trim($_POST['judul']), trim($_POST['deskripsi'])]); notif(uid_cap('aduan'), 'aduan', 'Aduan baru: '.$_POST['kategori'], user()['nama'].': '.trim($_POST['judul']), 'aduan.php'); flash('Aduan terkirim. Pantau statusnya di halaman ini.'); }
  header('Location: aduan.php'); exit;
}
head('Aduan & Aspirasi'); ?>
<?php if (!pengurus()): ?>
<div class="panel mb-4"><h2 class="h5">Sampaikan aduan</h2>
<form method="post" class="row g-2"><?= csrf_field() ?>
  <div class="col-md-3"><select name="kategori" class="form-select"><?php foreach (['Lampu jalan','Jalan rusak','Drainase','Sampah','Keamanan','Usulan kegiatan','Lainnya'] as $k) echo "<option>$k</option>"; ?></select></div>
  <div class="col-md-9"><input name="judul" class="form-control" placeholder="Judul singkat, mis. Lampu jalan depan No. 12 mati" required></div>
  <div class="col-12"><textarea name="deskripsi" rows="3" class="form-control" placeholder="Jelaskan lokasi dan kondisinya" required></textarea></div>
  <div class="col-12"><button class="btn btn-primary">Kirim aduan</button></div>
</form></div>
<?php endif; ?>
<?php $rows = pengurus() ? q('SELECT a.*, w.nama_kk FROM aduan a JOIN warga w ON w.id=a.warga_id ORDER BY a.id DESC') : q('SELECT a.*, "" nama_kk FROM aduan a WHERE warga_id=? ORDER BY a.id DESC', [wid()]);
foreach ($rows as $a): ?>
<div class="panel mb-3"><div class="d-flex justify-content-between"><div><span class="badge text-bg-secondary"><?= e($a['kategori']) ?></span> <small class="text-secondary"><?= tgl($a['created_at']) ?><?= $a['nama_kk'] ? ' &middot; '.e($a['nama_kk']) : '' ?></small></div><?= badge($a['status']) ?></div>
<h2 class="h6 mt-2"><?= e($a['judul']) ?></h2><p class="mb-2"><?= nl2br(e($a['deskripsi'])) ?></p>
<?php if ($a['tanggapan'] && !pengurus()) echo '<div class="alert alert-light border mb-0"><strong>Tanggapan pengurus:</strong> '.e($a['tanggapan']).'</div>'; ?>
<?php if (bisa('aduan')): ?><form method="post" class="d-flex gap-2"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $a['id'] ?>">
<select name="status" class="form-select w-auto"><?php foreach (['baru','diproses','selesai'] as $s) echo "<option".($a['status']===$s?' selected':'').">$s</option>"; ?></select>
<input name="tanggapan" class="form-control" placeholder="Tanggapan untuk warga" value="<?= e($a['tanggapan']) ?>"><button class="btn btn-primary">Simpan</button></form><?php endif; ?></div>
<?php endforeach; ?>
<?php foot();
