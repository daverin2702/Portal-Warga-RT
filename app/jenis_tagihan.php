<?php require 'config.php'; require 'layout.php'; need(['admin','rt']); izin('tagihan_buat');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  if (isset($_POST['toggle'])) { q('UPDATE jenis_tagihan SET aktif=1-aktif WHERE id=?', [$_POST['toggle']]); flash('Status jenis tagihan diubah.'); }
  else {
    $nama = trim($_POST['nama'] ?? ''); $nom = max(0, (int)($_POST['nominal'] ?? 0));
    $v = [$nama, trim($_POST['keterangan'] ?? ''), $nom, trim($_POST['alokasi'] ?? '') ?: 'Kas umum', isset($_POST['wajib']) ? 1 : 0];
    if ($nama === '') flash('Nama jenis tagihan wajib diisi.', 'danger');
    else try {
      if (!empty($_POST['id'])) { q('UPDATE jenis_tagihan SET nama=?, keterangan=?, nominal=?, alokasi=?, wajib=? WHERE id=?', [...$v, $_POST['id']]); flash('Jenis tagihan diperbarui. Tagihan yang sudah terbit tidak berubah.'); }
      else { q('INSERT INTO jenis_tagihan (nama,keterangan,nominal,alokasi,wajib) VALUES (?,?,?,?,?)', $v); flash('Jenis tagihan ditambahkan.'); }
    } catch (PDOException $x) { flash('Nama jenis tagihan sudah dipakai.', 'danger'); }
  }
  header('Location: jenis_tagihan.php'); exit;
}
$ed = isset($_GET['edit']) ? (q('SELECT * FROM jenis_tagihan WHERE id=?', [$_GET['edit']])->fetch() ?: []) : [];
head('Jenis Tagihan', 'Atur jenis iuran yang ditagihkan Bendahara. Jenis bertanda wajib ikut terbit saat tombol "Terbitkan tagihan wajib" ditekan.'); ?>
<div class="panel mb-4"><h2 class="h5 mb-3"><?= $ed ? 'Ubah jenis tagihan' : 'Tambah jenis tagihan' ?></h2>
<form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($ed['id'] ?? '') ?>">
  <div class="col-md-3"><label class="form-label">Nama</label><input name="nama" class="form-control" value="<?= e($ed['nama'] ?? '') ?>" placeholder="mis. Iuran Sampah" required></div>
  <div class="col-md-2"><label class="form-label">Nominal bawaan (Rp)</label><input type="number" name="nominal" min="0" class="form-control" value="<?= e($ed['nominal'] ?? '') ?>" required></div>
  <div class="col-md-3"><label class="form-label">Masuk ke alokasi kas</label><input name="alokasi" list="al" class="form-control" value="<?= e($ed['alokasi'] ?? 'Kas umum') ?>">
    <datalist id="al"><option>Kas umum<option>IPL<option>Kebersihan<option>Keamanan<option>Pembangunan saung<option>Kegiatan 17 Agustus</datalist></div>
  <div class="col-md-4"><label class="form-label">Keterangan</label><input name="keterangan" class="form-control" value="<?= e($ed['keterangan'] ?? '') ?>"></div>
  <div class="col-12"><label class="form-check"><input type="checkbox" class="form-check-input" name="wajib" value="1" <?= !empty($ed['wajib']) ? 'checked' : '' ?>> Wajib (ditagihkan ke semua keluarga setiap periode)</label></div>
  <div class="col-12"><button class="btn btn-primary">Simpan</button> <?php if ($ed): ?><a href="jenis_tagihan.php" class="btn btn-link">Batal</a><?php endif; ?></div>
</form></div>
<div class="panel table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Jenis</th><th>Nominal</th><th>Alokasi</th><th>Sifat</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach (q('SELECT * FROM jenis_tagihan ORDER BY aktif DESC, wajib DESC, nama') as $j): ?>
<tr class="<?= $j['aktif'] ? '' : 'text-secondary' ?>"><td><strong><?= e($j['nama']) ?></strong><div class="small text-secondary"><?= e($j['keterangan']) ?></div></td>
<td><?= rp($j['nominal']) ?></td><td><?= e($j['alokasi']) ?></td>
<td><?= $j['wajib'] ? '<span class="badge text-bg-primary">Wajib</span>' : '<span class="badge text-bg-light border">Opsional</span>' ?></td>
<td><?= $j['aktif'] ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' ?></td>
<td class="text-nowrap"><a class="btn btn-sm btn-light" href="?edit=<?= $j['id'] ?>">Ubah</a>
<form method="post" class="d-inline"><?= csrf_field() ?><button name="toggle" value="<?= $j['id'] ?>" class="btn btn-sm btn-light"><?= $j['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php foot();
