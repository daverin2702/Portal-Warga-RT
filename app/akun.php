<?php require 'config.php'; require 'layout.php'; need(['admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  if (isset($_POST['hapus'])) {
    if ($_POST['hapus'] == user()['id']) flash('Akun yang sedang dipakai tidak bisa dihapus.', 'danger');
    else { q('DELETE FROM users WHERE id=?', [$_POST['hapus']]); flash('Akun dihapus.'); }
  } elseif (isset($_POST['jab_id'])) {
    set_jabatan((int)$_POST['jab_id'], $_POST['jabatan'] ?? ''); flash('Jabatan diperbarui. Pemegang lama jabatan tunggal otomatis kembali menjadi warga.');
  } else {
    $jab = array_key_exists($_POST['jabatan'] ?? '', JABATAN) ? $_POST['jabatan'] : 'warga';
    $role = in_array($_POST['role'] ?? '', ['admin','rt','warga']) ? $_POST['role'] : 'warga';
    $wid = $_POST['warga_id'] ?: null; $nama = trim($_POST['nama']); $pw = $_POST['password'] ?? '';
    try {
      if (!empty($_POST['id'])) {
        $old = q('SELECT * FROM users WHERE id=?', [$_POST['id']])->fetch();
        if ($old['id'] == user()['id']) $role = $old['role'];
        q('UPDATE users SET nama=?, role=?, warga_id=?, foto=? WHERE id=?', [$nama, $role, $wid, upload_img('foto', $old['foto'] ?? '') ?: null, $old['id']]);
        if ($pw !== '') q('UPDATE users SET password=?, ganti_sandi=1 WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), $old['id']]);
        set_jabatan($old['id'], $jab); flash('Akun diperbarui.');
      } else {
        q('INSERT INTO users (username,password,nama,role,warga_id) VALUES (?,?,?,?,?)', [trim($_POST['username']), password_hash($pw, PASSWORD_DEFAULT), $nama, $role, $wid]);
        $id = $pdo->lastInsertId();
        q('UPDATE users SET foto=?, ganti_sandi=1 WHERE id=?', [upload_img('foto') ?: null, $id]);
        set_jabatan($id, $jab); flash('Akun dibuat.');
      }
    } catch (PDOException $x) { flash('Gagal menyimpan. Username mungkin sudah dipakai.', 'danger'); }
  }
  header('Location: akun.php'); exit;
}
$ed = isset($_GET['edit']) ? (q('SELECT * FROM users WHERE id=?', [$_GET['edit']])->fetch() ?: []) : [];
head('Akun Pengguna', 'Atur akses dan jabatan. Pergantian pengurus cukup mengubah jabatan akun.'); ?>
<div class="panel mb-4"><h2 class="h5 mb-3"><?= $ed ? 'Ubah akun' : 'Buat akun' ?></h2>
<form method="post" enctype="multipart/form-data" class="row g-2"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($ed['id'] ?? '') ?>">
  <div class="col-md-4"><label class="form-label">Nama lengkap</label><input name="nama" class="form-control" value="<?= e($ed['nama'] ?? '') ?>" required></div>
  <div class="col-md-3"><label class="form-label">Username</label><input name="username" class="form-control" value="<?= e($ed['username'] ?? '') ?>" <?= $ed ? 'disabled' : 'required' ?>></div>
  <div class="col-md-3"><label class="form-label">Kata sandi<?= $ed ? ' (kosongkan jika tidak diganti)' : '' ?></label><input name="password" type="password" minlength="6" class="form-control" <?= $ed ? '' : 'required' ?>></div>
  <div class="col-md-2"><label class="form-label">Hak akses</label><select name="role" class="form-select"><?php foreach (['warga'=>'Warga','rt'=>'Pengurus RT','admin'=>'Administrator'] as $k => $v) echo "<option value='$k'".(($ed['role'] ?? 'warga')===$k?' selected':'').">$v</option>"; ?></select></div>
  <div class="col-md-5"><label class="form-label">Jabatan kepengurusan</label><select name="jabatan" class="form-select"><?php foreach (JABATAN as $k => $v) echo "<option value='$k'".(($ed['jabatan'] ?? 'warga')===$k?' selected':'').">".e($v)."</option>"; ?></select></div>
  <div class="col-md-4"><label class="form-label">Keluarga terkait</label><select name="warga_id" class="form-select"><option value="">-</option>
    <?php foreach (q('SELECT id,nama_kk,blok,no_rumah FROM warga ORDER BY nama_kk') as $w) echo "<option value='{$w['id']}'".(($ed['warga_id'] ?? '')==$w['id']?' selected':'').">".e($w['nama_kk'].($w['no_rumah'] ? ' ('.trim($w['blok'].' '.$w['no_rumah']).')' : ''))."</option>"; ?></select></div>
  <div class="col-md-3"><label class="form-label">Foto (tampil di struktur pengurus)</label><input type="file" name="foto" accept="image/*" class="form-control"></div>
  <div class="col-12"><button class="btn btn-primary"><?= $ed ? 'Simpan perubahan' : 'Buat akun' ?></button> <?php if ($ed): ?><a href="akun.php" class="btn btn-link">Batal</a><?php endif; ?></div>
</form>
<div class="alert alert-light border small mt-3 mb-0">Ketua, Wakil Ketua, Sekretaris, Wakil Sekretaris, dan Bendahara hanya dipegang satu orang. Memberi jabatan itu ke akun lain otomatis melepasnya dari pemegang lama dan memberi akses pengurus ke pemegang baru. Pemegang lama harus masuk ulang agar aksesnya ikut berubah.</div></div>

<div class="panel table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Nama</th><th>Username</th><th>Hak akses</th><th>Jabatan</th><th></th></tr></thead><tbody>
<?php foreach (q('SELECT * FROM users ORDER BY FIELD(jabatan,"ketua","wakil","sekretaris","wakil_sekretaris","bendahara","seksi_pembangunan","seksi_keamanan","tim_keamanan","seksi_kebersihan","tim_kebersihan","seksi_pkk","seksi_pemuda","warga"), nama') as $a): $f = foto($a['foto']); ?>
<tr><td><div class="d-flex align-items-center gap-2"><?php if ($f): ?><img src="<?= e($f) ?>" width="36" height="36" class="rounded-circle" style="object-fit:cover" alt=""><?php else: ?><span class="rounded-circle d-inline-grid" style="width:36px;height:36px;display:inline-grid;place-items:center;background:var(--brand-l);color:var(--brand-d);font-weight:700;font-size:.8rem"><?= e(inisial($a['nama'])) ?></span><?php endif; ?><strong><?= e($a['nama']) ?></strong></div></td>
<td><?= e($a['username']) ?></td><td><?= ['admin'=>'Administrator','rt'=>'Pengurus RT','warga'=>'Warga'][$a['role']] ?></td>
<td><form method="post"><?= csrf_field() ?><input type="hidden" name="jab_id" value="<?= $a['id'] ?>"><select name="jabatan" class="form-select form-select-sm" style="min-width:230px" onchange="this.form.submit()"><?php foreach (JABATAN as $k => $v) echo "<option value='$k'".($a['jabatan']===$k?' selected':'').">".e($v)."</option>"; ?></select></form></td>
<td class="text-nowrap"><a class="btn btn-sm btn-light" href="?edit=<?= $a['id'] ?>">Ubah</a>
<form method="post" class="d-inline" onsubmit="return confirm('Hapus akun ini?')"><?= csrf_field() ?><button name="hapus" value="<?= $a['id'] ?>" class="btn btn-sm btn-light text-danger">Hapus</button></form></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php foot();
