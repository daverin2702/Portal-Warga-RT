<?php
/* =====================================================================
   Installer Website RT  (mirip instalasi WordPress)
   Langkah: 1 Persyaratan  2 Database  3 Tabel  4 Situs & admin  5 Selesai
   ===================================================================== */
session_start();
const APP   = __DIR__.'/app';
const KONF  = APP.'/config.local.php';
const KUNCI = APP.'/install.lock';
const SQLF  = __DIR__.'/database.sql';
if (function_exists('mysqli_report')) mysqli_report(MYSQLI_REPORT_OFF);

require_once APP.'/aset.php';
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
if (!isset($_SESSION['ins']) || !is_array($_SESSION['ins'])) $_SESSION['ins'] = [];
$S = &$_SESSION['ins'];
if (empty($_SESSION['ins_t'])) $_SESSION['ins_t'] = bin2hex(random_bytes(16));
$TOK = $_SESSION['ins_t'];
function cek_tok() { global $TOK; if (($_POST['t'] ?? '') !== $TOK) die('Sesi kedaluwarsa. Muat ulang halaman installer.'); }
function tok() { global $TOK; return '<input type="hidden" name="t" value="'.$TOK.'">'; }
function ke($n) { header('Location: install.php?langkah='.$n); exit; }

function koneksi($c, $pakaiDb = true) {
  $m = @new mysqli($c['host'], $c['user'], $c['pass'], $pakaiDb ? $c['name'] : '', (int)$c['port']);
  if ($m->connect_errno) return $m->connect_error;
  $m->set_charset('utf8mb4');
  return $m;
}
function konf_tersimpan() {
  if (is_file(KONF)) { $c = include KONF; if (is_array($c)) return $c; }
  if (getenv('DB_HOST')) return ['host'=>getenv('DB_HOST'), 'port'=>getenv('DB_PORT') ?: 3306, 'name'=>getenv('DB_NAME') ?: 'db_rt', 'user'=>getenv('DB_USER') ?: 'root', 'pass'=>getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''];
  return null;
}
function sudah_terpasang() {
  if (is_file(KUNCI)) return true;
  $c = konf_tersimpan(); if (!$c) return false;
  $m = koneksi($c); if (!is_object($m)) return false;
  $r = @$m->query("SELECT v FROM pengaturan WHERE k='terpasang'");
  return $r && $r->num_rows > 0;
}
// Menjalankan banyak pernyataan SQL; mengembalikan '' jika sukses atau pesan error.
function jalankan_sql($m, $sql) {
  $sql = preg_replace('/^\s*(CREATE\s+DATABASE|USE)\b[^;]*;\s*$/mi', '', $sql);
  if (!$m->multi_query($sql)) return 'Pernyataan ke-1 gagal: '.$m->error;
  $i = 1;
  while (true) {
    if ($r = $m->store_result()) $r->free();
    if (!$m->more_results()) break;
    if (!$m->next_result()) return 'Pernyataan ke-'.($i + 1).' gagal: '.$m->error;
    $i++;
  }
  return '';
}
function daftar_tabel($m) { $t = []; if ($r = $m->query('SHOW TABLES')) while ($x = $r->fetch_row()) $t[] = $x[0]; return $t; }
function ada_kolom($m, $tabel, $kolom) { $r = @$m->query("SHOW COLUMNS FROM `$tabel` LIKE '".$m->real_escape_string($kolom)."'"); return $r && $r->num_rows > 0; }
function versi_db($m, $tabel) {
  if (!in_array('templat_surat', $tabel)) return 1;
  if (!ada_kolom($m, 'users', 'jabatan')) return 2;
  if (!ada_kolom($m, 'users', 'ganti_sandi')) return 3;
  if (!in_array('jenis_tagihan', $tabel)) return 4;
  if (!in_array('notifikasi', $tabel)) return 5;
  if (!ada_kolom($m, 'surat_terbit', 'ttd_qr')) return 6;
  return 7;
}
function unggah($field, $ico = false) {
  $f = $_FILES[$field] ?? null;
  if (!$f || $f['error'] !== UPLOAD_ERR_OK) return '';
  if ($f['size'] > ($ico ? 512 * 1024 : 2 * 1024 * 1024)) return '';
  $ext = null;
  if ($ico && file_get_contents($f['tmp_name'], false, null, 0, 4) === "\x00\x00\x01\x00") $ext = 'ico';
  if (!$ext) {
    $i = @getimagesize($f['tmp_name']);
    $peta = $ico ? [IMAGETYPE_PNG => 'png'] : [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    $ext = $peta[$i[2] ?? 0] ?? null;
  }
  if (!$ext) return '';
  if (!is_dir(APP.'/uploads')) @mkdir(APP.'/uploads', 0755, true);
  $n = ($ico ? 'fav-' : '').bin2hex(random_bytes(8)).'.'.$ext;
  return @move_uploaded_file($f['tmp_name'], APP.'/uploads/'.$n) ? $n : '';
}

$langkah = (int)($_GET['langkah'] ?? 1);
if ($langkah > 1 && !extension_loaded('mysqli')) $langkah = 1;
$err = ''; $info = '';

/* ---------- sudah terpasang: kunci installer ---------- */
if (sudah_terpasang() && $langkah !== 5) $langkah = 0;
if ($langkah === 5 && empty($S['selesai']) && !sudah_terpasang()) ke(1);

/* ---------- hapus installer (langkah 5) ---------- */
if ($langkah === 5 && isset($_POST['hapus_installer'])) {
  cek_tok();
  $ok = @unlink(__FILE__);
  unset($_SESSION['ins'], $_SESSION['ins_t']);
  header('Location: '.($ok ? 'app/' : 'install.php?langkah=5&gagal_hapus=1')); exit;
}

/* ---------- unduh aset offline (langkah 1 dan 5) ---------- */
$hasil_aset = null;
if (isset($_POST['unduh_aset'])) { cek_tok(); $hasil_aset = aset_unduh_semua(); }

/* ---------- LANGKAH 1: persyaratan ---------- */
$syarat = [
  ['PHP versi 7.4 atau lebih baru (terpasang '.PHP_VERSION.')', version_compare(PHP_VERSION, '7.4', '>='), true],
  ['Ekstensi pdo_mysql', extension_loaded('pdo_mysql'), true],
  ['Ekstensi mysqli', extension_loaded('mysqli'), true],
  ['Berkas database.sql ada di folder utama', is_file(SQLF), true],
  ['Folder app/ bisa ditulis (untuk membuat config.local.php)', is_writable(APP), false],
  ['Folder app/uploads bisa ditulis (logo, foto, bukti bayar)', is_dir(APP.'/uploads') && is_writable(APP.'/uploads'), false],
  ['Ekstensi zip (untuk impor data warga dari Excel)', class_exists('ZipArchive'), false],
];
$syarat_ok = !array_filter($syarat, fn($x) => $x[2] && !$x[1]);

/* ---------- LANGKAH 2: database ---------- */
if ($langkah === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
  cek_tok();
  if (isset($_POST['cek_manual'])) {
    if (is_file(KONF)) ke(3);
    $err = 'Berkas app/config.local.php belum ditemukan. Buat berkasnya dengan isi di bawah, lalu klik tombol lagi.';
  } else {
    $c = ['host'=>trim($_POST['host'] ?: 'localhost'), 'port'=>(int)($_POST['port'] ?: 3306), 'name'=>trim($_POST['name']), 'user'=>trim($_POST['user']), 'pass'=>(string)$_POST['pass']];
    $S['form_db'] = array_diff_key($c, ['pass' => 1]);
    if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $c['name'])) $err = 'Nama database hanya boleh huruf, angka, dan garis bawah.';
    else {
      $m = koneksi($c, false);
      if (!is_object($m)) $err = 'Tidak bisa terhubung ke server database: '.$m.'. Periksa host, port, username, dan kata sandi, dan pastikan MySQL sudah berjalan.';
      else {
        if (!$m->select_db($c['name'])) {
          if (!$m->query('CREATE DATABASE `'.$c['name'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'))
            $err = 'Database "'.$c['name'].'" belum ada dan tidak bisa dibuat otomatis ('.$m->error.'). Buat database lewat cPanel/phpMyAdmin, lalu coba lagi.';
          else $info = 'Database "'.$c['name'].'" dibuat.';
        }
        if (!$err) {
          $S['db'] = $c;
          $isi = "<?php\n// Dibuat otomatis oleh installer pada ".date('Y-m-d H:i').". Jangan bagikan berkas ini.\nreturn ".var_export($c, true).";\n";
          if (@file_put_contents(KONF, $isi) === false) { $S['konf_manual'] = $isi; $err = 'manual'; }
          else { unset($S['konf_manual']); ke(3); }
        }
      }
    }
  }
}

/* ---------- LANGKAH 3: tabel ---------- */
if ($langkah >= 3 && $langkah <= 4 && empty($S['db'])) { $c = konf_tersimpan(); if ($c) $S['db'] = $c; else ke(2); }
$m3 = null; $tabel = []; $jml_user = 0; $versi = 0;
if ($langkah === 3) {
  $m3 = koneksi($S['db']);
  if (!is_object($m3)) { $err = 'Koneksi gagal: '.$m3; }
  else {
    $tabel = daftar_tabel($m3);
    if (in_array('users', $tabel)) { $r = $m3->query('SELECT COUNT(*) FROM users'); $jml_user = (int)$r->fetch_row()[0]; $versi = versi_db($m3, $tabel); }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      cek_tok();
      $aksi = $_POST['aksi'] ?? '';
      if ($aksi === 'pakai') {
        for ($v = $versi + 1; $v <= 7; $v++) {
          $f = APP."/sql/update_v$v.sql";
          if (!is_file($f)) { $err = "Berkas pembaruan app/sql/update_v$v.sql tidak ditemukan."; break; }
          if ($e = jalankan_sql($m3, file_get_contents($f))) { $err = "Pembaruan ke versi $v gagal. $e"; break; }
        }
        if (!$err) { $S['tabel'] = 1; $S['pakai_lama'] = 1; $S['demo'] = 0; ke(4); }
      } elseif ($aksi === 'pasang' || $aksi === 'ulang') {
        if ($aksi === 'ulang' && empty($_POST['yakin'])) $err = 'Centang kotak konfirmasi untuk menghapus semua tabel lama.';
        else {
          if ($aksi === 'ulang') { $m3->query('SET FOREIGN_KEY_CHECKS=0'); foreach ($tabel as $t) $m3->query('DROP TABLE IF EXISTS `'.$t.'`'); $m3->query('SET FOREIGN_KEY_CHECKS=1'); }
          $e = jalankan_sql($m3, file_get_contents(SQLF));
          if ($e) $err = 'Impor database.sql gagal. '.$e;
          else {
            $m3 = koneksi($S['db']);
            $demo = !empty($_POST['demo']);
            if (!$demo) {
              $m3->query('SET FOREIGN_KEY_CHECKS=0');
              foreach (['surat_terbit','surat','aduan','tagihan','kas','pengumuman','users','warga'] as $t) $m3->query("DELETE FROM `$t`");
              $m3->query('SET FOREIGN_KEY_CHECKS=1');
            }
            $S['tabel'] = 1; $S['pakai_lama'] = 0; $S['demo'] = $demo ? 1 : 0; ke(4);
          }
        }
      }
    }
  }
}

/* ---------- LANGKAH 4: situs & admin ---------- */
if ($langkah === 4 && empty($S['tabel'])) ke(3);
$P = [];
if ($langkah === 4) {
  $m4 = koneksi($S['db']);
  if (!is_object($m4)) $err = 'Koneksi gagal: '.$m4;
  else {
    if ($r = @$m4->query('SELECT k,v FROM pengaturan')) while ($x = $r->fetch_row()) $P[$x[0]] = $x[1];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      cek_tok();
      $v = fn($k, $d = '') => trim((string)($_POST[$k] ?? $d));
      $adm_user = $v('adm_user'); $adm_pw = (string)($_POST['adm_pw'] ?? ''); $adm_nama = $v('adm_nama');
      $wajib_admin = empty($S['pakai_lama']);
      if ($v('nama_situs') === '' || $v('rt_nomor') === '' || $v('rw_nomor') === '') $err = 'Nama website, nomor RT, dan nomor RW wajib diisi.';
      elseif ($wajib_admin && ($adm_user === '' || $adm_nama === '')) $err = 'Lengkapi nama dan username administrator.';
      elseif ($adm_user !== '' && !preg_match('/^[A-Za-z0-9_.]{3,50}$/', $adm_user)) $err = 'Username minimal 3 karakter: huruf, angka, titik, atau garis bawah.';
      elseif ($adm_user !== '' && (strlen($adm_pw) < 8 || $adm_pw !== ($_POST['adm_pw2'] ?? ''))) $err = 'Kata sandi admin minimal 8 karakter dan harus sama dengan pengulangannya.';
      else {
        $simpan = function ($k, $val, $timpa = true) use ($m4) {
          $s = $m4->prepare('INSERT '.($timpa ? '' : 'IGNORE ').'INTO pengaturan (k,v) VALUES (?,?)'.($timpa ? ' ON DUPLICATE KEY UPDATE v=VALUES(v)' : ''));
          $s->bind_param('ss', $k, $val); $s->execute();
        };
        foreach (['nama_situs','rt_nomor','rw_nomor','kelurahan','kecamatan','kota','kop_alamat','motto','ketua_nama'] as $k) $simpan($k, $v($k));
        $simpan('tema', in_array($_POST['tema'] ?? '', ['teal','biru','terakota']) ? $_POST['tema'] : 'teal');
        if ($v('kota') !== '') $simpan('kop1', strtoupper('Pemerintah '.$v('kota')), false);
        if ($v('kecamatan') !== '') $simpan('kop2', strtoupper($v('kecamatan')), false);
        if ($v('kelurahan') !== '') $simpan('kop3', strtoupper($v('kelurahan')), false);
        $simpan('kop4', 'RUKUN TETANGGA '.$v('rt_nomor').' / RUKUN WARGA '.$v('rw_nomor'), false);
        if ($n = unggah('logo')) $simpan('logo', $n);
        if ($n = unggah('favicon', true)) $simpan('favicon', $n);
        if ($adm_user !== '') {
          $hash = password_hash($adm_pw, PASSWORD_DEFAULT);
          if (!empty($S['demo']) && strtolower($adm_user) !== 'admin') $m4->query("DELETE FROM users WHERE username='admin' AND role='admin'");
          $s = $m4->prepare('SELECT id FROM users WHERE username=?'); $s->bind_param('s', $adm_user); $s->execute(); $ada = $s->get_result()->fetch_row();
          if ($ada) { $s = $m4->prepare("UPDATE users SET password=?, nama=?, role='admin', ganti_sandi=0 WHERE id=?"); $s->bind_param('ssi', $hash, $adm_nama, $ada[0]); }
          else { $s = $m4->prepare("INSERT INTO users (username,password,nama,role,jabatan,ganti_sandi) VALUES (?,?,?,'admin','warga',0)"); $s->bind_param('sss', $adm_user, $hash, $adm_nama); }
          $s->execute();
        }
        $simpan('terpasang', date('Y-m-d H:i:s'));
        @file_put_contents(KUNCI, 'Terpasang '.date('Y-m-d H:i:s')."\nHapus berkas ini untuk menjalankan installer lagi.\n");
        $S['selesai'] = 1; $S['admin'] = $adm_user; ke(5);
      }
    }
  }
}
$val = fn($k, $d = '') => h($_POST[$k] ?? ($P[$k] ?? $d));
$LANGKAH = [1 => 'Persyaratan', 2 => 'Database', 3 => 'Tabel', 4 => 'Situs & admin', 5 => 'Selesai'];
?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pemasangan Website RT</title>
<?= aset_css('app/', false) ?>
<style>
:root{--brand:#0E7C86;--brand-d:#0B5F67;--brand-l:#E6F4F5}
body{font-family:"Plus Jakarta Sans",system-ui,sans-serif;background:radial-gradient(900px 400px at 80% 0,#c9eef0 0,transparent 60%),#F5F7FA;min-height:100vh;color:#16212B}
.wrap{max-width:760px;margin:0 auto;padding:2.5rem 1rem}
.card-i{background:#fff;border-radius:20px;padding:2rem;box-shadow:0 1px 2px rgba(16,24,40,.04),0 12px 32px rgba(16,24,40,.07)}
.plate{display:inline-block;background:#F2B705;font-weight:800;padding:.2rem .7rem;border-radius:8px;border:2.5px solid #16212B}
.steps{display:flex;gap:.4rem;margin:1.2rem 0 1.5rem;flex-wrap:wrap}
.steps span{flex:1;min-width:100px;font-size:.78rem;font-weight:600;color:#94a3b8;border-top:4px solid #e2e8f0;padding-top:.4rem}
.steps span.on{color:var(--brand-d);border-color:var(--brand)}.steps span.done{color:#475569;border-color:#94d3d8}
.btn-primary{--bs-btn-bg:var(--brand);--bs-btn-border-color:var(--brand);--bs-btn-hover-bg:var(--brand-d);--bs-btn-hover-border-color:var(--brand-d);--bs-btn-active-bg:var(--brand-d)}
.btn{border-radius:10px;font-weight:600}.form-control,.form-select{border-radius:10px}
.form-label{font-weight:600;font-size:.82rem;color:#475569}
h2.sec{font-size:1rem;font-weight:700;margin:1.4rem 0 .8rem;padding-top:1rem;border-top:1px solid #eef1f5}
.tema{border:2px solid #e5e9f0;border-radius:14px;padding:.7rem;cursor:pointer;display:block}
.tema input{margin-right:.4rem}.tema .sw{display:flex;gap:4px;margin-top:.4rem}.tema .sw i{width:22px;height:22px;border-radius:6px;display:block}
.tema:has(input:checked){border-color:var(--brand);box-shadow:0 0 0 3px var(--brand-l)}
pre.cfg{background:#0f172a;color:#e2e8f0;border-radius:12px;padding:1rem;font-size:.8rem}
</style></head><body>
<div class="wrap">
  <div class="text-center mb-2"><span class="plate">RT</span><h1 class="h3 fw-bold mt-3 mb-0">Pemasangan Website RT</h1></div>
  <?php if ($langkah >= 1): ?><div class="steps"><?php foreach ($LANGKAH as $n => $l) echo '<span class="'.($n === $langkah ? 'on' : ($n < $langkah ? 'done' : '')).'">'.$n.'. '.$l.'</span>'; ?></div><?php endif; ?>
  <div class="card-i">
  <?php if ($err && $err !== 'manual'): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>
  <?php if ($info): ?><div class="alert alert-success"><?= h($info) ?></div><?php endif; ?>

  <?php if ($langkah === 0): ?>
    <h2 class="h4 fw-bold"><i class="bi bi-shield-lock text-success me-1"></i>Website sudah terpasang</h2>
    <p class="text-secondary">Installer dikunci agar data yang ada tidak tertimpa. Untuk memasang ulang, hapus berkas <code>app/install.lock</code> lewat file manager, lalu buka halaman ini lagi.</p>
    <p class="text-secondary">Demi keamanan, sebaiknya hapus juga <code>install.php</code> dari server.</p>
    <a href="app/" class="btn btn-primary">Buka website</a>

  <?php elseif ($langkah === 1): ?>
    <h2 class="h4 fw-bold">Selamat datang</h2>
    <p class="text-secondary">Installer ini akan menghubungkan website ke database, membuat tabel, lalu mengatur nama, logo, dan akun administrator. Siapkan data berikut:</p>
    <ul class="text-secondary"><li>Host, nama, username, dan kata sandi database MySQL/MariaDB</li><li>Nama website, nomor RT/RW, dan wilayah</li><li>Logo RT dan favicon (opsional)</li></ul>
    <h2 class="sec">Pemeriksaan server</h2>
    <ul class="list-unstyled mb-4">
      <?php foreach ($syarat as [$l, $ok, $wajib]): ?>
        <li class="py-1"><i class="bi <?= $ok ? 'bi-check-circle-fill text-success' : ($wajib ? 'bi-x-circle-fill text-danger' : 'bi-exclamation-triangle-fill text-warning') ?> me-2"></i><?= h($l) ?><?= !$ok && !$wajib ? ' <span class="small text-secondary">(disarankan)</span>' : '' ?></li>
      <?php endforeach; ?>
    </ul>
    <h2 class="sec">Mode offline (opsional)</h2>
    <p class="small text-secondary">Unduh Bootstrap, ikon, dan font ke server agar website tetap rapi tanpa internet. Bisa juga dilakukan nanti di menu admin Aset Offline.</p>
    <?php if ($hasil_aset): $ok = count(array_filter($hasil_aset, fn($x) => $x[1])); ?><div class="alert <?= $ok === count($hasil_aset) ? 'alert-success' : 'alert-warning' ?> small"><?= $ok ?> dari <?= count($hasil_aset) ?> aset tersimpan.<?php foreach ($hasil_aset as [$n, $b, $k]) if (!$b) echo '<br>'.h($n).': '.h($k); ?></div><?php endif; ?>
    <form method="post" class="mb-4"><?= tok() ?><button name="unduh_aset" value="1" class="btn btn-light btn-sm" <?= aset_offline_lengkap() ? 'disabled' : '' ?>><i class="bi bi-cloud-download me-1"></i><?= aset_offline_lengkap() ? 'Aset offline sudah lengkap' : 'Unduh aset offline' ?></button></form>
    <?php if ($syarat_ok): ?><a href="install.php?langkah=2" class="btn btn-primary btn-lg">Mulai pemasangan</a>
    <?php else: ?><div class="alert alert-danger mb-0">Perbaiki persyaratan bertanda merah dulu, lalu muat ulang halaman ini.</div><?php endif; ?>

  <?php elseif ($langkah === 2): $f = $S['form_db'] ?? konf_tersimpan() ?? []; ?>
    <?php if ($err === 'manual'): ?>
      <h2 class="h4 fw-bold">Buat berkas konfigurasi secara manual</h2>
      <p class="text-secondary">Koneksi berhasil, tetapi installer tidak punya izin menulis ke folder <code>app/</code>. Buat berkas <code>app/config.local.php</code> lewat file manager dengan isi berikut, lalu klik tombol di bawah.</p>
      <pre class="cfg"><?= h($S['konf_manual']) ?></pre>
      <form method="post"><?= tok() ?><button name="cek_manual" value="1" class="btn btn-primary">Saya sudah membuat berkasnya</button></form>
    <?php else: ?>
      <h2 class="h4 fw-bold">Hubungkan database</h2>
      <p class="text-secondary">Di XAMPP biasanya host <code>localhost</code>, username <code>root</code>, kata sandi kosong. Di hosting, lihat menu MySQL Databases di cPanel.</p>
      <form method="post" class="row g-3"><?= tok() ?>
        <div class="col-md-8"><label class="form-label">Host database</label><input name="host" class="form-control" value="<?= h($f['host'] ?? 'localhost') ?>" required></div>
        <div class="col-md-4"><label class="form-label">Port</label><input name="port" type="number" class="form-control" value="<?= h($f['port'] ?? 3306) ?>"></div>
        <div class="col-md-12"><label class="form-label">Nama database</label><input name="name" class="form-control" value="<?= h($f['name'] ?? 'db_rt') ?>" required><div class="form-text">Jika belum ada, installer mencoba membuatnya. Di hosting, buat dulu lewat cPanel.</div></div>
        <div class="col-md-6"><label class="form-label">Username database</label><input name="user" class="form-control" value="<?= h($f['user'] ?? 'root') ?>" required autocomplete="off"></div>
        <div class="col-md-6"><label class="form-label">Kata sandi database</label><input name="pass" type="password" class="form-control" autocomplete="new-password"></div>
        <div class="col-12 d-flex justify-content-between"><a href="install.php?langkah=1" class="btn btn-light">Kembali</a><button class="btn btn-primary">Uji koneksi &amp; simpan</button></div>
      </form>
    <?php endif; ?>

  <?php elseif ($langkah === 3): ?>
    <h2 class="h4 fw-bold">Siapkan tabel</h2>
    <p class="text-secondary">Terhubung ke database <strong><?= h($S['db']['name']) ?></strong> di <?= h($S['db']['host']) ?>. Berkas konfigurasi sudah tersimpan.</p>
    <?php if (is_object($m3) && $jml_user > 0): ?>
      <div class="alert alert-warning">Database ini sudah berisi website RT (<?= count($tabel) ?> tabel, <?= $jml_user ?> akun, versi <?= $versi ?>).</div>
      <form method="post" class="mb-4"><?= tok() ?>
        <h2 class="sec mt-0 border-0 pt-0">Pakai data yang ada (disarankan)</h2>
        <p class="small text-secondary">Data warga, kas, dan surat tetap aman.<?= $versi < 7 ? ' Struktur tabel akan diperbarui otomatis dari versi '.$versi.' ke versi 7.' : '' ?></p>
        <button name="aksi" value="pakai" class="btn btn-primary">Pakai data yang ada</button>
      </form>
      <form method="post" onsubmit="return confirm('Semua data di database ini akan dihapus permanen. Lanjutkan?')"><?= tok() ?>
        <h2 class="sec">Hapus semua dan pasang ulang</h2>
        <label class="form-check mb-2"><input type="checkbox" class="form-check-input" name="demo" value="1"> Sertakan data contoh</label>
        <label class="form-check mb-3 text-danger"><input type="checkbox" class="form-check-input" name="yakin" value="1"> Saya paham semua data lama akan terhapus</label>
        <button name="aksi" value="ulang" class="btn btn-outline-danger">Hapus &amp; pasang ulang</button>
      </form>
    <?php elseif (is_object($m3)): ?>
      <?php if ($tabel): ?><div class="alert alert-warning small">Ada <?= count($tabel) ?> tabel tanpa akun pengguna (kemungkinan impor sebelumnya gagal). Tabel tersebut akan dihapus dan dibuat ulang.</div><?php endif; ?>
      <form method="post"><?= tok() ?><input type="hidden" name="yakin" value="1">
        <label class="form-check mb-1"><input type="checkbox" class="form-check-input" name="demo" value="1"> Sertakan data contoh</label>
        <div class="form-text mb-3">Data contoh berisi 2 keluarga, pengumuman, kas, tagihan, serta akun uji rt, wakil, sekretaris, bendahara, dan budi. Cocok untuk mencoba; jangan dipakai untuk website sungguhan. Templat surat selalu dipasang.</div>
        <div class="d-flex justify-content-between"><a href="install.php?langkah=2" class="btn btn-light">Kembali</a><button name="aksi" value="<?= $tabel ? 'ulang' : 'pasang' ?>" class="btn btn-primary">Buat tabel</button></div>
      </form>
    <?php else: ?><a href="install.php?langkah=2" class="btn btn-light">Kembali ke pengaturan database</a><?php endif; ?>

  <?php elseif ($langkah === 4): $lama = !empty($S['pakai_lama']); ?>
    <h2 class="h4 fw-bold">Identitas website &amp; administrator</h2>
    <p class="text-secondary">Semua ini masih bisa diubah nanti di menu Pengaturan Situs, Tema Website, dan Profil &amp; Kop Surat.</p>
    <form method="post" enctype="multipart/form-data" class="row g-3"><?= tok() ?>
      <div class="col-md-12"><label class="form-label">Nama website *</label><input name="nama_situs" class="form-control" value="<?= $val('nama_situs', 'Portal Warga') ?>" required></div>
      <div class="col-md-3"><label class="form-label">Nomor RT *</label><input name="rt_nomor" class="form-control" value="<?= $val('rt_nomor', '05') ?>" required></div>
      <div class="col-md-3"><label class="form-label">Nomor RW *</label><input name="rw_nomor" class="form-control" value="<?= $val('rw_nomor', '02') ?>" required></div>
      <div class="col-md-6"><label class="form-label">Kelurahan / Desa</label><input name="kelurahan" class="form-control" value="<?= $val('kelurahan') ?>" placeholder="Kelurahan Sukamaju"></div>
      <div class="col-md-6"><label class="form-label">Kecamatan</label><input name="kecamatan" class="form-control" value="<?= $val('kecamatan') ?>" placeholder="Kecamatan Sukasari"></div>
      <div class="col-md-6"><label class="form-label">Kota / Kabupaten</label><input name="kota" class="form-control" value="<?= $val('kota') ?>" placeholder="Kota Surabaya"></div>
      <div class="col-md-6"><label class="form-label">Alamat sekretariat</label><input name="kop_alamat" class="form-control" value="<?= $val('kop_alamat') ?>" placeholder="Jl. Mawar No. 1"></div>
      <div class="col-md-6"><label class="form-label">Moto RT</label><input name="motto" class="form-control" value="<?= $val('motto', 'Rukun, Aman, dan Guyub') ?>"></div>
      <div class="col-md-6"><label class="form-label">Nama Ketua RT</label><input name="ketua_nama" class="form-control" value="<?= $val('ketua_nama') ?>"><div class="form-text">Untuk tanda tangan surat bila belum ada akun berjabatan Ketua.</div></div>
      <div class="col-md-6"><label class="form-label">Logo RT</label><input type="file" name="logo" accept="image/*" class="form-control"><div class="form-text">JPG/PNG/WEBP, maks. 2 MB.<?= !empty($P['logo']) ? ' Kosongkan untuk mempertahankan logo lama.' : '' ?></div></div>
      <div class="col-md-6"><label class="form-label">Favicon (ikon tab browser)</label><input type="file" name="favicon" accept=".ico,image/x-icon,image/png" class="form-control"><div class="form-text">.ico atau .png persegi, maks. 512 KB.</div></div>
      <div class="col-12"><label class="form-label">Tema</label><div class="row g-2">
        <?php foreach (['teal'=>['Teal Segar',['#0E7C86','#E6F4F5','#F2B705']], 'biru'=>['Biru Resmi',['#1D4E89','#132F52','#E3B23C']], 'terakota'=>['Hangat Terakota',['#B5532F','#FBEDE6','#E9B949']]] as $k => [$n, $w]): ?>
          <div class="col-md-4"><label class="tema"><input type="radio" name="tema" value="<?= $k ?>" <?= ($_POST['tema'] ?? ($P['tema'] ?? 'teal')) === $k ? 'checked' : '' ?>><strong><?= $n ?></strong><span class="sw"><?php foreach ($w as $c) echo "<i style='background:$c'></i>"; ?></span></label></div>
        <?php endforeach; ?></div></div>

      <div class="col-12"><h2 class="sec">Akun administrator</h2>
        <?php if ($lama): ?><p class="small text-secondary mb-0">Opsional. Kosongkan untuk tetap memakai akun admin yang sudah ada. Jika username sudah terdaftar, kata sandinya akan diganti.</p>
        <?php elseif (!empty($S['demo'])): ?><p class="small text-secondary mb-0">Akun admin contoh (<code>admin/admin123</code>) akan diganti dengan akun ini.</p><?php endif; ?></div>
      <div class="col-md-6"><label class="form-label">Nama lengkap<?= $lama ? '' : ' *' ?></label><input name="adm_nama" class="form-control" value="<?= h($_POST['adm_nama'] ?? '') ?>" <?= $lama ? '' : 'required' ?>></div>
      <div class="col-md-6"><label class="form-label">Username<?= $lama ? '' : ' *' ?></label><input name="adm_user" class="form-control" value="<?= h($_POST['adm_user'] ?? '') ?>" <?= $lama ? '' : 'required' ?> autocomplete="off"></div>
      <div class="col-md-6"><label class="form-label">Kata sandi (min. 8 karakter)</label><input name="adm_pw" type="password" minlength="8" class="form-control" <?= $lama ? '' : 'required' ?> autocomplete="new-password"></div>
      <div class="col-md-6"><label class="form-label">Ulangi kata sandi</label><input name="adm_pw2" type="password" class="form-control" <?= $lama ? '' : 'required' ?> autocomplete="new-password"></div>
      <div class="col-12 d-flex justify-content-between mt-4"><a href="install.php?langkah=3" class="btn btn-light">Kembali</a><button class="btn btn-primary btn-lg">Pasang website</button></div>
    </form>

  <?php elseif ($langkah === 5): ?>
    <div class="text-center">
      <div style="font-size:3rem;color:#1a8f5a"><i class="bi bi-patch-check-fill"></i></div>
      <h2 class="h3 fw-bold">Website berhasil dipasang</h2>
      <p class="text-secondary">Konfigurasi tersimpan di <code>app/config.local.php</code> dan installer kini terkunci.</p>
    </div>
    <?php if (!empty($S['admin'])): ?><div class="alert alert-light border">Masuk sebagai administrator dengan username <strong><?= h($S['admin']) ?></strong> dan kata sandi yang baru Anda buat.</div><?php endif; ?>
    <?php if (!empty($S['demo'])): ?><div class="alert alert-warning small">Data contoh terpasang. Akun uji: rt/rt12345, wakil/wakil123, sekretaris/sekretaris123, bendahara/bendahara123, budi/warga123. Hapus atau ganti sandinya sebelum website dipakai warga.</div><?php endif; ?>
    <?php if (!empty($_GET['gagal_hapus'])): ?><div class="alert alert-danger small">install.php tidak bisa dihapus otomatis. Hapus manual lewat file manager.</div><?php endif; ?>
    <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
      <a href="app/login.php?role=admin" class="btn btn-primary btn-lg">Masuk sebagai admin</a>
      <a href="app/" class="btn btn-light btn-lg">Lihat halaman depan</a>
    </div>
    <?php if (is_file(__FILE__)): ?>
    <form method="post" class="text-center mt-4" onsubmit="return confirm('Hapus install.php dari server?')"><?= tok() ?>
      <button name="hapus_installer" value="1" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus install.php (disarankan)</button></form>
    <?php endif; ?>
  <?php endif; ?>
  </div>
  <p class="text-center small text-secondary mt-3">Installer Website RT</p>
</div></body></html>
