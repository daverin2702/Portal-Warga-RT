<?php require 'config.php'; require 'layout.php'; need(['admin','rt','warga']);
$u = user(); $uid = $u['id'];
$kel = $u['warga_id'] ? q('SELECT * FROM warga WHERE id=?', [$u['warga_id']])->fetch() : null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $akun = q('SELECT * FROM users WHERE id=?', [$uid])->fetch();
  if (isset($_POST['simpan_akun'])) {
    $nama = trim($_POST['nama']);
    if ($nama === '') flash('Nama tidak boleh kosong.', 'danger');
    else { q('UPDATE users SET nama=?, foto=? WHERE id=?', [$nama, upload_img('foto', $akun['foto'] ?? '') ?: null, $uid]); flash('Profil diperbarui.'); }
  } elseif (isset($_POST['ganti_sandi'])) {
    $baru = $_POST['baru'] ?? '';
    if (!password_verify($_POST['lama'] ?? '', $akun['password'])) flash('Kata sandi saat ini salah.', 'danger');
    elseif (strlen($baru) < 6 || $baru !== ($_POST['ulang'] ?? '')) flash('Kata sandi baru minimal 6 karakter dan harus sama dengan pengulangannya.', 'danger');
    else { q('UPDATE users SET password=?, ganti_sandi=0 WHERE id=?', [password_hash($baru, PASSWORD_DEFAULT), $uid]); flash('Kata sandi diganti.'); }
  } elseif (isset($_POST['simpan_data']) && $kel) {
    $kunci = ['telp','email','tempat_lahir','tgl_lahir','jenis_kelamin','agama','pekerjaan','jml_anggota','status_hunian','darurat_nama','darurat_telp'];
    if (empty($kel['no_kk'])) $kunci[] = 'no_kk';
    if (empty($kel['nik_kepala'])) $kunci[] = 'nik_kepala';
    $v = array_map(fn($k) => ($x = trim($_POST[$k] ?? '')) === '' ? null : $x, $kunci);
    try { q('UPDATE warga SET '.implode('=?,', $kunci).'=? WHERE id=?', [...$v, $kel['id']]); flash('Data keluarga diperbarui.'); }
    catch (PDOException $x) { flash('Gagal menyimpan. Nomor KK mungkin sudah terdaftar.', 'danger'); }
  }
  header('Location: profil_saya.php'); exit;
}
$akun = q('SELECT * FROM users WHERE id=?', [$uid])->fetch(); $f = foto($akun['foto']);
function fld($k, $l, $kel, $span = 6, $type = 'text', $kunci = false) {
  $ro = $kunci && !empty($kel[$k]); echo "<div class='col-md-$span'><label class='form-label'>$l</label><input type='$type' name='$k' class='form-control' value='".e($kel[$k] ?? '')."' ".($ro ? 'disabled' : '').">".($ro ? '' : '')."</div>";
}
head('Profil Saya', 'Perbarui foto, data diri, dan kata sandi Anda.'); ?>
<?php if (!empty($_GET['ganti'])): ?><div class="alert alert-warning">Demi keamanan, ganti kata sandi bawaan Anda di bagian &ldquo;Ganti kata sandi&rdquo; di bawah.</div><?php endif; ?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="panel mb-3"><h2 class="h5 mb-3">Akun &amp; foto</h2>
      <form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
        <div class="d-flex align-items-center gap-3 mb-3"><?php if ($f): ?><img src="<?= e($f) ?>" class="avatar" style="width:84px;height:84px" alt=""><?php else: ?><div class="avatar" style="width:84px;height:84px;font-size:1.5rem"><?= e(inisial($akun['nama'])) ?></div><?php endif; ?>
          <div class="flex-grow-1"><label class="form-label">Ganti foto (JPG/PNG/WEBP, maks. 2 MB)</label><input type="file" name="foto" accept="image/*" class="form-control"></div></div>
        <label class="form-label">Nama</label><input name="nama" class="form-control mb-2" value="<?= e($akun['nama']) ?>" required>
        <div class="small text-secondary mb-3">Username: <strong><?= e($akun['username']) ?></strong><?= $akun['jabatan'] !== 'warga' ? ' &middot; Jabatan: <strong>'.e(JABATAN[$akun['jabatan']] ?? '').'</strong>' : '' ?></div>
        <button name="simpan_akun" value="1" class="btn btn-primary">Simpan</button></form></div>
    <div class="panel" id="sandi"><h2 class="h5 mb-3">Ganti kata sandi</h2>
      <form method="post"><?= csrf_field() ?>
        <input type="password" name="lama" class="form-control mb-2" placeholder="Kata sandi saat ini" required>
        <input type="password" name="baru" class="form-control mb-2" placeholder="Kata sandi baru (min. 6 karakter)" minlength="6" required>
        <input type="password" name="ulang" class="form-control mb-3" placeholder="Ulangi kata sandi baru" required>
        <button name="ganti_sandi" value="1" class="btn btn-primary">Ganti kata sandi</button></form></div>
  </div>
  <div class="col-lg-7">
    <?php if ($kel): ?>
    <div class="panel"><h2 class="h5 mb-1">Data keluarga</h2>
      <p class="small text-secondary">Nama kepala keluarga, alamat, blok, dan nomor rumah dikunci karena dipakai di surat resmi. Hubungi Sekretaris jika perlu mengubahnya.</p>
      <div class="row g-2 mb-3"><div class="col-md-6"><label class="form-label">Kepala keluarga</label><input class="form-control" value="<?= e($kel['nama_kk']) ?>" disabled></div>
        <div class="col-md-6"><label class="form-label">Alamat</label><input class="form-control" disabled value="<?= e(trim($kel['alamat'].($kel['blok'] ? ', Blok '.$kel['blok'] : '').($kel['no_rumah'] ? ' No. '.$kel['no_rumah'] : ''))) ?>"></div></div>
      <form method="post" class="row g-2"><?= csrf_field() ?>
        <?php fld('no_kk','No. KK',$kel,6,'text',true); fld('nik_kepala','NIK kepala keluarga',$kel,6,'text',true);
              fld('telp','Telepon / HP',$kel,6); fld('email','Email',$kel,6,'email');
              fld('tempat_lahir','Tempat lahir',$kel,6); fld('tgl_lahir','Tanggal lahir',$kel,6,'date'); ?>
        <div class="col-md-4"><label class="form-label">Jenis kelamin</label><select name="jenis_kelamin" class="form-select"><option value="">-</option><?php foreach (['Laki-laki','Perempuan'] as $o) echo "<option".(($kel['jenis_kelamin'] ?? '')===$o?' selected':'').">$o</option>"; ?></select></div>
        <div class="col-md-4"><label class="form-label">Agama</label><select name="agama" class="form-select"><option value="">-</option><?php foreach (['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $o) echo "<option".(($kel['agama'] ?? '')===$o?' selected':'').">$o</option>"; ?></select></div>
        <?php fld('pekerjaan','Pekerjaan',$kel,4); ?>
        <div class="col-md-4"><label class="form-label">Jumlah anggota</label><input type="number" min="1" name="jml_anggota" class="form-control" value="<?= e($kel['jml_anggota']) ?>"></div>
        <div class="col-md-4"><label class="form-label">Status hunian</label><select name="status_hunian" class="form-select"><?php foreach (['pemilik','penyewa'] as $o) echo "<option value='$o'".($kel['status_hunian']===$o?' selected':'').">".ucfirst($o)."</option>"; ?></select></div>
        <div class="col-md-4"></div>
        <?php fld('darurat_nama','Kontak darurat (nama)',$kel,6); fld('darurat_telp','Kontak darurat (telepon)',$kel,6); ?>
        <div class="col-12"><button name="simpan_data" value="1" class="btn btn-primary">Simpan data keluarga</button></div>
      </form></div>
    <?php else: ?><div class="panel text-secondary">Akun ini tidak terhubung dengan data keluarga. Pengurus atau admin dapat menautkannya lewat menu Akun Pengguna.</div><?php endif; ?>
  </div>
</div>
<?php foot();
