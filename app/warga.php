<?php require 'config.php'; require 'layout.php'; need(['admin','rt']);
$F = ['no_kk','nik_kepala','nama_kk','tempat_lahir','tgl_lahir','jenis_kelamin','agama','pekerjaan','alamat','blok','no_rumah','jml_anggota','status_hunian','telp','email','darurat_nama','darurat_telp'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  if (!bisa('warga_kelola')) { flash('Hanya Sekretaris yang dapat mengubah data warga.', 'danger'); header('Location: warga.php'); exit; }
  if (isset($_POST['hapus'])) { q('DELETE FROM warga WHERE id=?', [$_POST['hapus']]); flash('Data warga dihapus.'); }
  else {
    $v = array_map(fn($k) => ($x = trim($_POST[$k] ?? '')) === '' ? null : $x, $F);
    try {
      if (!empty($_POST['id'])) { q('UPDATE warga SET '.implode('=?,', $F).'=? WHERE id=?', [...$v, $_POST['id']]); flash('Data diperbarui.'); }
      else { q('INSERT INTO warga ('.implode(',', $F).') VALUES ('.rtrim(str_repeat('?,', count($F)), ',').')', $v); flash('Data warga ditambahkan.'); }
    } catch (PDOException $x) { flash('Gagal menyimpan. Periksa isian wajib, dan pastikan nomor KK belum terdaftar.', 'danger'); }
  }
  header('Location: warga.php'); exit;
}
$ed = isset($_GET['edit']) ? (q('SELECT * FROM warga WHERE id=?', [$_GET['edit']])->fetch() ?: []) : [];
function inp($k, $l, $span = 4, $type = 'text', $req = true) { global $ed; echo "<div class='col-md-$span'><label class='form-label'>$l</label><input type='$type' name='$k' class='form-control' value='".e($ed[$k] ?? '')."' ".($req ? 'required' : '')."></div>"; }
function sel($k, $l, $opts, $span = 3) { global $ed; echo "<div class='col-md-$span'><label class='form-label'>$l</label><select name='$k' class='form-select'><option value=''>-</option>"; foreach ($opts as $o) echo "<option value='".e($o)."'".(strcasecmp($ed[$k] ?? '', $o)===0?' selected':'').">".e($o)."</option>"; echo "</select></div>"; }
head('Data Warga', 'Database kependudukan RT: kepala keluarga, anggota, hunian, dan kontak darurat.'); ?>
<?php if (bisa('warga_kelola')): ?><div class="panel mb-4"><h2 class="h5 mb-3"><?= $ed ? 'Ubah data keluarga' : 'Tambah keluarga' ?></h2>
<form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($ed['id'] ?? '') ?>">
  <?php inp('no_kk','No. KK'); inp('nik_kepala','NIK kepala keluarga'); inp('nama_kk','Nama kepala keluarga');
        inp('tempat_lahir','Tempat lahir',3,'text',false); inp('tgl_lahir','Tanggal lahir',3,'date',false);
        sel('jenis_kelamin','Jenis kelamin',['Laki-laki','Perempuan']); sel('agama','Agama',['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu']);
        inp('pekerjaan','Pekerjaan',4,'text',false); inp('alamat','Alamat / jalan',6); inp('blok','Blok',3,'text',false); inp('no_rumah','No. rumah',3); ?>
  <div class="col-md-2"><label class="form-label">Jumlah anggota</label><input type="number" min="1" name="jml_anggota" class="form-control" value="<?= e($ed['jml_anggota'] ?? 1) ?>" required></div>
  <div class="col-md-2"><label class="form-label">Status hunian</label><select name="status_hunian" class="form-select"><?php foreach (['pemilik','penyewa'] as $s) echo "<option value='$s'".(($ed['status_hunian'] ?? '')===$s?' selected':'').">".ucfirst($s)."</option>"; ?></select></div>
  <?php inp('telp','Telepon / HP',3,'text',false); inp('email','Email',3,'email',false); inp('darurat_nama','Kontak darurat (nama)',3,'text',false); inp('darurat_telp','Kontak darurat (telepon)',3,'text',false); ?>
  <div class="col-12"><button class="btn btn-primary">Simpan</button> <?php if ($ed): ?><a href="warga.php" class="btn btn-link">Batal</a><?php endif; ?></div>
</form></div><?php endif; ?>
<div class="panel table-responsive"><table class="table align-middle mb-0">
<thead><tr><th>Kepala keluarga</th><th>Alamat</th><th>Anggota</th><th>Hunian</th><th>Kontak darurat</th><th></th></tr></thead><tbody>
<?php foreach (q('SELECT * FROM warga ORDER BY blok, CAST(no_rumah AS UNSIGNED), nama_kk') as $w): ?>
<tr><td><strong><?= e($w['nama_kk']) ?></strong><div class="small text-secondary">KK <?= e($w['no_kk']) ?> &middot; <?= e($w['telp']) ?></div></td>
<td><?= e($w['alamat']) ?><div class="small text-secondary"><?= $w['blok'] ? 'Blok '.e($w['blok']).' ' : '' ?><?= $w['no_rumah'] ? 'No. '.e($w['no_rumah']) : '' ?></div></td><td><?= $w['jml_anggota'] ?></td><td><?= ucfirst($w['status_hunian']) ?></td>
<td><?= e($w['darurat_nama']) ?><div class="small text-secondary"><?= e($w['darurat_telp']) ?></div></td>
<td class="text-nowrap"><?php if (bisa('warga_kelola')): ?><a class="btn btn-sm btn-light" href="?edit=<?= $w['id'] ?>">Ubah</a>
<form method="post" class="d-inline" onsubmit="return confirm('Hapus keluarga ini beserta tagihan, surat, aduan, dan akunnya?')"><?= csrf_field() ?><button name="hapus" value="<?= $w['id'] ?>" class="btn btn-sm btn-light text-danger">Hapus</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php foot();
