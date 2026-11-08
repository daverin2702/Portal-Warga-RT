<?php require 'config.php'; require 'layout.php'; need(['admin']);
function proses_impor($rows) {
  global $pdo;
  $peta = ['nama'=>'nama','namalengkap'=>'nama','namakepalakeluarga'=>'nama','blok'=>'blok','norumah'=>'no_rumah','nomorrumah'=>'no_rumah','no'=>'no_rumah',
    'nohp'=>'hp','nomorhp'=>'hp','hp'=>'hp','telepon'=>'hp','telp'=>'hp','notelp'=>'hp','nohandphone'=>'hp','whatsapp'=>'hp','wa'=>'hp','email'=>'email',
    'nokk'=>'no_kk','nomorkk'=>'no_kk','kk'=>'no_kk','nik'=>'nik','alamat'=>'alamat','jalan'=>'alamat'];
  $hdr = array_shift($rows); $kol = [];
  foreach ((array)$hdr as $i => $h) { $k = $peta[preg_replace('/[^a-z0-9]/', '', strtolower((string)$h))] ?? null; if ($k) $kol[$i] = $k; }
  if (!in_array('nama', $kol) || !in_array('no_rumah', $kol)) return ['err' => 'Baris judul tidak dikenali. Pakai templat yang disediakan (kolom Nama, Blok, No Rumah, No HP, Email).'];
  $baru = []; $lewat = []; $no = 1;
  foreach ($rows as $r) {
    $no++; $d = [];
    foreach ($kol as $i => $k) $d[$k] = trim((string)($r[$i] ?? ''));
    if (!array_filter($d)) continue;
    if (stripos($d['nama'] ?? '', 'CONTOH') === 0) continue;
    if (($d['nama'] ?? '') === '' || ($d['no_rumah'] ?? '') === '') { $lewat[] = [$no, $d['nama'] ?? '-', 'Nama atau No. rumah kosong']; continue; }
    $blok = strtoupper($d['blok'] ?? ''); $hp = normal_hp($d['hp'] ?? '');
    $email = filter_var($d['email'] ?? '', FILTER_VALIDATE_EMAIL) ? $d['email'] : null;
    if (q('SELECT COUNT(*) FROM warga WHERE nama_kk=? AND IFNULL(blok,"")=? AND no_rumah=?', [$d['nama'], $blok, $d['no_rumah']])->fetchColumn()) { $lewat[] = [$no, $d['nama'], 'Sudah terdaftar']; continue; }
    $dasar = $hp ?: ($email ? strtolower(strstr($email, '@', true)) : strtolower(preg_replace('/[^a-z0-9]/i', '', $d['nama'])).strtolower($blok).$d['no_rumah']);
    $user = $dasar; $n = 1;
    while (q('SELECT COUNT(*) FROM users WHERE username=?', [$user])->fetchColumn()) { $n++; $user = $dasar.$n; }
    try {
      $pdo->beginTransaction();
      q('INSERT INTO warga (nama_kk,blok,no_rumah,telp,email,no_kk,nik_kepala,alamat,jml_anggota,status_hunian) VALUES (?,?,?,?,?,?,?,?,1,\'pemilik\')',
        [$d['nama'], $blok ?: null, $d['no_rumah'], $hp ?: null, $email, ($d['no_kk'] ?? '') ?: null, ($d['nik'] ?? '') ?: null, ($d['alamat'] ?? '') ?: null]);
      $wid = $pdo->lastInsertId();
      q("INSERT INTO users (username,password,nama,role,warga_id,jabatan,ganti_sandi) VALUES (?,?,?,'warga',?,'warga',1)",
        [$user, password_hash('warga123', PASSWORD_DEFAULT, ['cost' => 9]), $d['nama'], $wid]);
      $pdo->commit(); $baru[] = [$d['nama'], $blok, $d['no_rumah'], $user];
    } catch (PDOException $x) { if ($pdo->inTransaction()) $pdo->rollBack(); $lewat[] = [$no, $d['nama'], 'Gagal disimpan (No. KK atau NIK mungkin ganda)']; }
  }
  return compact('baru', 'lewat');
}
$hasil = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check(); $f = $_FILES['berkas'] ?? null;
  if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2*1024*1024) flash('Pilih berkas .xlsx atau .csv, maksimal 2 MB.', 'danger');
  else {
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $rows = $ext === 'xlsx' ? baca_xlsx($f['tmp_name']) : ($ext === 'csv' ? baca_csv($f['tmp_name']) : null);
    if (!$rows) flash('Berkas tidak bisa dibaca. Pastikan formatnya .xlsx (ekstensi zip PHP aktif) atau .csv.', 'danger');
    else { set_time_limit(120); $hasil = proses_impor($rows); }
  }
}
head('Impor Data Warga', 'Unggah templat Excel untuk membuat data warga dan akun login sekaligus.'); ?>
<div class="row g-3"><div class="col-lg-5"><div class="panel">
  <h2 class="h5">1. Unduh templat</h2>
  <p class="text-secondary">Kolom wajib: <strong>Nama, Blok, No Rumah, No HP, Email</strong>. Kolom opsional: No KK, NIK, Alamat.</p>
  <a class="btn btn-light mb-4" href="template_data_warga.xlsx" download><i class="bi bi-file-earmark-excel me-1"></i>Unduh templat Excel</a>
  <h2 class="h5">2. Unggah berkas</h2>
  <form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
    <input type="file" name="berkas" accept=".xlsx,.csv" class="form-control mb-3" required>
    <button class="btn btn-primary"><i class="bi bi-upload me-1"></i>Impor sekarang</button></form>
  <div class="alert alert-light border small mt-3 mb-0">Setiap warga otomatis mendapat akun dengan kata sandi <code>warga123</code>. Username diambil dari nomor HP, lalu email, lalu nama dan nomor rumah. Pengguna diminta mengganti sandi saat masuk. Data yang sama persis (nama, blok, nomor rumah) dilewati.</div>
</div></div>
<div class="col-lg-7"><?php if ($hasil): ?>
  <?php if (isset($hasil['err'])): ?><div class="alert alert-danger"><?= e($hasil['err']) ?></div><?php else: ?>
  <div class="panel mb-3"><h2 class="h5"><i class="bi bi-check-circle text-success me-1"></i><?= count($hasil['baru']) ?> akun dibuat</h2>
    <?php if ($hasil['baru']): ?><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Nama</th><th>Blok</th><th>No.</th><th>Username</th></tr></thead><tbody>
    <?php foreach ($hasil['baru'] as $b) echo '<tr><td>'.e($b[0]).'</td><td>'.e($b[1]).'</td><td>'.e($b[2]).'</td><td><code>'.e($b[3]).'</code></td></tr>'; ?></tbody></table></div>
    <div class="small text-secondary mt-2">Kata sandi semua akun di atas: <code>warga123</code>.</div><?php endif; ?></div>
  <?php if ($hasil['lewat']): ?><div class="panel"><h2 class="h5"><i class="bi bi-exclamation-triangle text-warning me-1"></i><?= count($hasil['lewat']) ?> baris dilewati</h2>
    <table class="table table-sm mb-0"><thead><tr><th>Baris</th><th>Nama</th><th>Alasan</th></tr></thead><tbody>
    <?php foreach ($hasil['lewat'] as $b) echo '<tr><td>'.(int)$b[0].'</td><td>'.e($b[1]).'</td><td>'.e($b[2]).'</td></tr>'; ?></tbody></table></div><?php endif; ?>
  <?php endif; ?>
<?php else: ?><div class="panel text-secondary">Hasil impor akan tampil di sini.</div><?php endif; ?></div></div>
<?php foot();
