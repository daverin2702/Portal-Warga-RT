<?php
session_start();
require_once __DIR__.'/aset.php';
// Pengaturan koneksi dibuat oleh installer (../install.php) di config.local.php.
// Di Codespaces/Docker, koneksi boleh diberikan lewat variabel lingkungan DB_HOST dst.
$DBC = is_file(__DIR__.'/config.local.php') ? include __DIR__.'/config.local.php' : null;
if (!is_array($DBC) && getenv('DB_HOST')) $DBC = ['host'=>getenv('DB_HOST'), 'port'=>getenv('DB_PORT') ?: 3306, 'name'=>getenv('DB_NAME') ?: 'db_rt',
  'user'=>getenv('DB_USER') ?: 'root', 'pass'=>getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''];
function ke_installer($pesan) {
  if (PHP_SAPI === 'cli') { fwrite(STDERR, $pesan."\n"); exit(1); }
  if (is_file(dirname(__DIR__).'/install.php')) { header('Location: ../install.php'); exit; }
  http_response_code(503); die(htmlspecialchars($pesan).' Unggah ulang install.php ke folder utama lalu jalankan.');
}
if (!is_array($DBC)) ke_installer('Website belum dipasang.');
try {
  $pdo = new PDO('mysql:host='.$DBC['host'].';port='.(int)($DBC['port'] ?? 3306).';dbname='.$DBC['name'].';charset=utf8mb4', $DBC['user'], $DBC['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
  $pdo->query('SELECT 1 FROM users LIMIT 1');
} catch (PDOException $x) {
  ke_installer('Database belum siap atau pengaturan koneksi berubah.');
}
function q($sql, $p = []) { global $pdo; $s = $pdo->prepare($sql); $s->execute($p); return $s; }
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp($n) { return 'Rp '.number_format((float)$n, 0, ',', '.'); }
function tgl($d) { return $d ? date('d M Y', strtotime($d)) : '-'; }
function user() { return $_SESSION['user'] ?? null; }
function need($roles) {
  if (!user() || !in_array(user()['role'], $roles)) { header('Location: index.php'); exit; }
}
function flash($m, $t = 'success') { $_SESSION['f'] = [$m, $t]; }
function flash_show() {
  if (!isset($_SESSION['f'])) return '';
  [$m, $t] = $_SESSION['f']; unset($_SESSION['f']);
  return "<div class='alert alert-$t alert-dismissible'>".e($m)."<button class='btn-close' data-bs-dismiss='alert'></button></div>";
}
function csrf() { return $_SESSION['t'] ??= bin2hex(random_bytes(16)); }
function csrf_field() { return "<input type='hidden' name='t' value='".csrf()."'>"; }
function csrf_check() { if (($_POST['t'] ?? '') !== ($_SESSION['t'] ?? '-')) die('Token tidak valid. Muat ulang halaman.'); }
function badge($s) {
  $c = ['lunas'=>'success','disetujui'=>'success','selesai'=>'success','terbit'=>'success','belum'=>'warning','diajukan'=>'warning','baru'=>'warning','draf'=>'warning','menunggu'=>'info','diteruskan'=>'info','diperiksa'=>'primary','diproses'=>'info','ditolak'=>'danger'];
  return "<span class='badge text-bg-".($c[$s] ?? 'secondary')."'>".e(ucfirst($s))."</span>";
}
function saldo() { return q("SELECT COALESCE(SUM(IF(tipe='masuk',nominal,-nominal)),0) FROM kas WHERE status='disetujui'")->fetchColumn(); }
function wid() { return user()['warga_id'] ?? 0; }
function pengurus() { return in_array(user()['role'], ['admin','rt']); }

// ====== Pengaturan, profil, dan surat ======
function cfg($k = null) {
  static $c = null;
  if ($c === null) {
    $c = ['rt_nomor'=>'05','rw_nomor'=>'02','kelurahan'=>'Kelurahan Contoh','kecamatan'=>'Kecamatan Contoh','kota'=>'Kota Surabaya',
      'kop1'=>'PEMERINTAH KOTA SURABAYA','kop2'=>'KECAMATAN CONTOH','kop3'=>'KELURAHAN CONTOH','kop4'=>'RUKUN TETANGGA 05 / RUKUN WARGA 02',
      'kop_alamat'=>'Sekretariat: Jl. Mawar No. 1, Surabaya','logo'=>'','logo_kanan'=>'','base_url'=>'','nama_situs'=>'Portal Warga','brand_sub'=>'','favicon'=>'','tema'=>'teal',
      'hero_badge'=>'','hero_judul'=>'Lingkungan yang tertata, urusan warga jadi lebih ringan.',
      'hero_lead'=>'Bayar iuran, ajukan surat pengantar, baca pengumuman, dan lihat ke mana dana kas dipakai, semuanya dari rumah.',
      'hero_btn1'=>'Masuk sebagai warga','hero_btn2'=>'Kenali pengurus','hero_gambar'=>'','tampil_statistik'=>'1',
      'pengurus_judul'=>'','pengurus_sub'=>'Siap melayani dan menampung aspirasi warga.','profil_sumber'=>'akun','fitur_json'=>'',
      'footer_kiri'=>'','footer_kanan'=>'Surat resmi dapat diverifikasi lewat kode QR pada dokumen.','motto'=>'Rukun, Aman, dan Guyub','rekening'=>"Transfer: BCA 1234567890 a.n. Bendahara RT\nE-wallet: DANA / OVO 0812-0000-0000",
      'ketua_nama'=>'Nama Ketua RT','ketua_masa'=>'Periode 2024–2027','ketua_sambutan'=>'Selamat datang di portal warga. Mari bersama menjaga lingkungan yang aman, bersih, dan guyub.','ketua_foto'=>'',
      'wakil_nama'=>'Nama Wakil Ketua RT','wakil_masa'=>'Periode 2024–2027','wakil_sambutan'=>'Kami siap membantu kebutuhan administrasi dan menampung aspirasi warga.','wakil_foto'=>''];
    try { foreach (q('SELECT k,v FROM pengaturan') as $r) if ($r['v'] !== '') $c[$r['k']] = $r['v']; } catch (PDOException $x) {}
    // Nama dan foto Ketua/Wakil diambil dari akun yang memegang jabatan tersebut (jika ada)
    if ($c['profil_sumber'] === 'akun') try { foreach (['ketua','wakil'] as $j) { $r = q('SELECT nama,foto FROM users WHERE jabatan=? LIMIT 1', [$j])->fetch();
      if ($r) { $c[$j.'_nama'] = $r['nama']; if ($r['foto']) $c[$j.'_foto'] = $r['foto']; } } } catch (PDOException $x) {}
  }
  return $k === null ? $c : ($c[$k] ?? '');
}
function nama_rt() { return 'RT '.cfg('rt_nomor').' / RW '.cfg('rw_nomor'); }
function lokasi() { return cfg('kelurahan').', '.cfg('kota'); }
function foto($n) { return ($n && is_file(__DIR__.'/uploads/'.basename($n))) ? 'uploads/'.rawurlencode(basename($n)) : ''; }
function upload_img($field, $old = '') {
  $f = $_FILES[$field] ?? null;
  if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2*1024*1024) return $old;
  $i = @getimagesize($f['tmp_name']);
  $ext = [IMAGETYPE_JPEG=>'jpg', IMAGETYPE_PNG=>'png', IMAGETYPE_WEBP=>'webp'][$i[2] ?? 0] ?? null;
  if (!$ext) return $old;
  if (!is_dir(__DIR__.'/uploads')) mkdir(__DIR__.'/uploads', 0755, true);
  $n = bin2hex(random_bytes(8)).'.'.$ext;
  if (!move_uploaded_file($f['tmp_name'], __DIR__.'/uploads/'.$n)) return $old;
  if ($old && is_file(__DIR__.'/uploads/'.basename($old))) @unlink(__DIR__.'/uploads/'.basename($old));
  return $n;
}
function inisial($n) { $p = preg_split('/\s+/', trim($n)); return strtoupper(mb_substr($p[0] ?? '?', 0, 1).mb_substr($p[1] ?? '', 0, 1)); }
function tgl_id($d) {
  $b = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  $t = strtotime($d); return $t ? date('j', $t).' '.$b[(int)date('n', $t)].' '.date('Y', $t) : '';
}
function base_url() {
  if (cfg('base_url')) return rtrim(cfg('base_url'), '/');
  $s = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
  $h = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
  return $s.'://'.$h.rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
}
function kop_html() {
  $l = foto(cfg('logo')); $r = foto(cfg('logo_kanan')); $dua = $l || $r;
  $h = '<div class="kop">';
  if ($dua) $h .= $l ? '<img src="'.e($l).'" alt="">' : '<span class="kop-sp"></span>';
  $h .= '<div class="kop-t">';
  foreach (['kop1','kop2','kop3','kop4'] as $i => $k) if (cfg($k)) $h .= '<div class="k'.($i+1).'">'.e(cfg($k)).'</div>';
  if (cfg('kop_alamat')) $h .= '<div class="ka">'.e(cfg('kop_alamat')).'</div>';
  $h .= '</div>';
  if ($dua) $h .= $r ? '<img src="'.e($r).'" alt="">' : '<span class="kop-sp"></span>';
  return $h.'</div><div class="kop-line"></div>';
}
function footer_html() { return cfg('motto') ? '<div class="a4-foot">'.e(cfg('motto')).'</div>' : ''; }
function nama_situs() { return cfg('nama_situs') ?: 'Portal Warga'; }
function data_surat($w, $extra = []) {
  $c = cfg();
  $alm = trim(($w['alamat'] ?? '').(!empty($w['blok']) ? ', Blok '.$w['blok'] : '').(!empty($w['no_rumah']) ? ' No. '.$w['no_rumah'] : ''));
  return array_merge([
    'nama'=>$w['nama_kk'] ?? '', 'nik'=>$w['nik_kepala'] ?? '', 'no_kk'=>$w['no_kk'] ?? '',
    'tempat_lahir'=>$w['tempat_lahir'] ?? '', 'tgl_lahir'=>!empty($w['tgl_lahir']) ? tgl_id($w['tgl_lahir']) : '',
    'jenis_kelamin'=>$w['jenis_kelamin'] ?? '', 'agama'=>$w['agama'] ?? '', 'pekerjaan'=>$w['pekerjaan'] ?? '',
    'alamat'=>$alm, 'blok'=>$w['blok'] ?? '', 'no_rumah'=>$w['no_rumah'] ?? '', 'status_hunian'=>ucfirst($w['status_hunian'] ?? ''), 'telp'=>$w['telp'] ?? '',
    'rt'=>$c['rt_nomor'], 'rw'=>$c['rw_nomor'], 'kelurahan'=>$c['kelurahan'], 'kecamatan'=>$c['kecamatan'], 'kota'=>$c['kota'],
    'nama_ketua'=>$c['ketua_nama'], 'tanggal_surat'=>tgl_id(date('Y-m-d')),
  ], $extra);
}
function render_vars($html, $d) {
  return preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function ($m) use ($d) {
    $k = strtolower($m[1]);
    return (isset($d[$k]) && $d[$k] !== '' && $k !== 'qr_ttd') ? e($d[$k]) : $m[0];
  }, $html);
}
function olah_input($defs, $src) {
  $o = [];
  foreach ($defs as $f) { $v = trim((string)($src[$f['key']] ?? '')); if ($f['type'] === 'date' && $v) $v = tgl_id($v); $o[$f['key']] = $v; }
  return $o;
}
function bersih_html($h) {
  $d = new DOMDocument(); libxml_use_internal_errors(true);
  $d->loadHTML('<?xml encoding="utf-8"?><div id="r">'.$h.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); libxml_clear_errors();
  $x = new DOMXPath($d);
  foreach ($x->query('//script|//iframe|//object|//embed|//link|//meta|//form|//base|//style|//input|//button|//textarea|//select') as $n) $n->parentNode->removeChild($n);
  foreach ($x->query('//*') as $n) foreach (iterator_to_array($n->attributes) as $a)
    if (stripos($a->name, 'on') === 0 || preg_match('/^\s*(javascript|data|vbscript):/i', $a->value)) $n->removeAttribute($a->name);
  $r = $d->getElementById('r'); $o = '';
  if ($r) foreach ($r->childNodes as $c) $o .= $d->saveHTML($c);
  return $o;
}
function nomor_berikut() {
  $n = q('SELECT COUNT(*) FROM surat_terbit WHERE YEAR(created_at)=YEAR(CURDATE())')->fetchColumn() + 1;
  $r = ['','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
  return sprintf('%03d/RT.%s/RW.%s/%s/%s', $n, cfg('rt_nomor'), cfg('rw_nomor'), $r[(int)date('n')], date('Y'));
}
function terbitkan($t, $w, $extra = [], $surat_id = null, $nomor = '', $ttd_qr = 1) {
  global $pdo;
  $nomor = $nomor ?: nomor_berikut();
  $isi = bersih_html(render_vars($t['isi'], data_surat($w, $extra + ['nomor_surat' => $nomor])));
  q('INSERT INTO surat_terbit (templat_id,surat_id,warga_id,nomor,judul,isi,token,penandatangan,ttd_qr,keperluan) VALUES (?,?,?,?,?,?,?,?,?,?)',
    [$t['id'], $surat_id, $w['id'] ?? null, $nomor, $t['nama'], $isi, bin2hex(random_bytes(12)), cfg('ketua_nama'), $ttd_qr ? 1 : 0, ($extra['keperluan'] ?? '') ?: null]);
  return $pdo->lastInsertId();
}

const JABATAN = ['ketua'=>'Ketua RT','wakil'=>'Wakil Ketua RT','sekretaris'=>'Sekretaris','wakil_sekretaris'=>'Wakil Sekretaris','bendahara'=>'Bendahara',
  'seksi_pembangunan'=>'Seksi Pembangunan & Kesejahteraan Sosial','seksi_keamanan'=>'Seksi Keamanan','tim_keamanan'=>'Tim Keamanan',
  'seksi_kebersihan'=>'Seksi Kebersihan & Lingkungan Hidup','tim_kebersihan'=>'Tim Kebersihan',
  'seksi_pkk'=>'Seksi Pemberdayaan Perempuan & Tata Laksana Rumah Tangga','seksi_pemuda'=>'Seksi Pemuda, Olahraga & Seni Budaya','warga'=>'Warga'];
const JABATAN_TUNGGAL = ['ketua','wakil','sekretaris','wakil_sekretaris','bendahara'];
// Memberi jabatan pada akun. Jabatan tunggal (ketua, wakil, sekretaris, dst.) otomatis dilepas dari pemegang lama.
function set_jabatan($uid, $jab) {
  if (!array_key_exists($jab, JABATAN)) return;
  $u = q('SELECT * FROM users WHERE id=?', [$uid])->fetch(); if (!$u) return;
  if (in_array($jab, JABATAN_TUNGGAL))
    foreach (q('SELECT * FROM users WHERE jabatan=? AND id<>?', [$jab, $uid])->fetchAll() as $o)
      q("UPDATE users SET jabatan='warga', role=? WHERE id=?", [($o['role'] === 'rt' && $o['warga_id']) ? 'warga' : $o['role'], $o['id']]);
  $role = $u['role'];
  if ($role === 'warga' && in_array($jab, JABATAN_TUNGGAL)) $role = 'rt';
  elseif ($role === 'rt' && $jab === 'warga' && $u['warga_id']) $role = 'warga';
  q('UPDATE users SET jabatan=?, role=? WHERE id=?', [$jab, $role, $uid]);
  if (user() && user()['id'] == $uid) $_SESSION['user']['role'] = $role;
}

// ====== Hak akses per jabatan, alur persetujuan, bukti bayar, impor ======
const CAPS = [
  'warga_kelola'=>['sekretaris','wakil_sekretaris'], 'surat_buat'=>['sekretaris','wakil_sekretaris'], 'templat'=>['sekretaris','wakil_sekretaris'],
  'surat_teruskan'=>['sekretaris','wakil_sekretaris'], 'surat_periksa'=>['wakil'], 'surat_setujui'=>['ketua'],
  'tagihan_buat'=>['bendahara'], 'bayar_verifikasi'=>['bendahara'],
  'kas_catat'=>['bendahara'], 'kas_periksa'=>['wakil'], 'kas_setujui'=>['ketua'],
  'pengumuman'=>['ketua','wakil','sekretaris','wakil_sekretaris'], 'aduan'=>['ketua','wakil','sekretaris','wakil_sekretaris'],
  'profil_kop'=>['ketua','sekretaris','wakil_sekretaris','bendahara'],
];
const METODE_BAYAR = ['Transfer BCA','Transfer Mandiri','Transfer BRI','Transfer BNI','Transfer bank lain','GoPay','OVO','DANA','ShopeePay'];
function bisa($cap) {
  $u = user(); if (!$u) return false;
  if ($u['role'] === 'admin') return true;
  return $u['role'] === 'rt' && in_array($u['jabatan'] ?? 'warga', CAPS[$cap] ?? []);
}
function izin($cap) { if (!bisa($cap)) { flash('Anda tidak memiliki wewenang untuk halaman itu.', 'danger'); header('Location: dashboard.php'); exit; } }
function catat_alur($lama, $teks) { return trim(($lama ? $lama."\n" : '').date('d/m/Y H:i').' - '.$teks); }
function aksi_persetujuan($tipe, $id, $aksi, $catatan = '', $opsi = []) {
  $peta = [
    'surat' => ['tabel'=>'surat', 'tolak'=>['diajukan'=>'surat_teruskan','diteruskan'=>'surat_periksa','diperiksa'=>'surat_setujui'],
      'maju'=>['teruskan'=>['diajukan','diteruskan','surat_teruskan'],'periksa'=>['diteruskan','diperiksa','surat_periksa'],'setujui'=>['diperiksa','disetujui','surat_setujui']]],
    'draf'  => ['tabel'=>'surat_terbit', 'tolak'=>['draf'=>'surat_periksa','diperiksa'=>'surat_setujui'],
      'maju'=>['periksa'=>['draf','diperiksa','surat_periksa'],'setujui'=>['diperiksa','terbit','surat_setujui']]],
    'kas'   => ['tabel'=>'kas', 'tolak'=>['diajukan'=>'kas_periksa','diperiksa'=>'kas_setujui'],
      'maju'=>['periksa'=>['diajukan','diperiksa','kas_periksa'],'setujui'=>['diperiksa','disetujui','kas_setujui']]],
  ];
  $p = $peta[$tipe] ?? null; if (!$p) return [false, 'Jenis data tidak dikenal.'];
  $r = q("SELECT * FROM {$p['tabel']} WHERE id=?", [$id])->fetch(); if (!$r) return [false, 'Data tidak ditemukan.'];
  $u = user(); $siapa = $u['nama'].' ('.($u['role'] === 'admin' ? 'Administrator' : (JABATAN[$u['jabatan'] ?? 'warga'] ?? 'Pengurus')).')';
  if ($aksi === 'tolak') {
    $cap = $p['tolak'][$r['status']] ?? null;
    if (!$cap || !bisa($cap)) return [false, 'Anda tidak berwenang menolak pada tahap ini.'];
    $baru = 'ditolak'; $log = 'Ditolak oleh '.$siapa;
  } else {
    $m = $p['maju'][$aksi] ?? null;
    if (!$m || $r['status'] !== $m[0]) return [false, 'Tahap tidak sesuai atau sudah diproses orang lain.'];
    if (!bisa($m[2])) return [false, 'Anda tidak berwenang pada tahap ini.'];
    $baru = $m[1]; $log = ['teruskan'=>'Diteruskan','periksa'=>'Diperiksa','setujui'=>'Disetujui'][$aksi].' oleh '.$siapa;
  }
  if ($catatan !== '') $log .= ': '.$catatan;
  q("UPDATE {$p['tabel']} SET status=?, alur=? WHERE id=?", [$baru, catat_alur($r['alur'] ?? '', $log), $id]);
  $pesan = 'Tersimpan.';
  $judul_item = $tipe === 'kas' ? 'Pengeluaran: '.($r['keterangan'] ?: $r['kategori']).' ('.rp($r['nominal']).')' : ($tipe === 'surat' ? $r['jenis'] : $r['judul']);
  $ke = [
    'surat' => ['diteruskan'=>[uid_cap('surat_periksa'), 'Surat perlu diperiksa'], 'diperiksa'=>[uid_cap('surat_setujui'), 'Surat menunggu tanda tangan Anda'],
                'disetujui'=>[uid_warga($r['warga_id'] ?? 0), 'Surat Anda disetujui'], 'ditolak'=>[uid_warga($r['warga_id'] ?? 0), 'Pengajuan surat ditolak']],
    'draf'  => ['diperiksa'=>[uid_cap('surat_setujui'), 'Draf surat menunggu tanda tangan Anda'], 'terbit'=>[uid_cap('surat_buat'), 'Draf surat sudah ditandatangani'], 'ditolak'=>[uid_cap('surat_buat'), 'Draf surat ditolak']],
    'kas'   => ['diperiksa'=>[uid_cap('kas_setujui'), 'Pengeluaran menunggu persetujuan Anda'], 'disetujui'=>[uid_cap('kas_catat'), 'Pengeluaran disetujui'], 'ditolak'=>[uid_cap('kas_catat'), 'Pengeluaran ditolak']],
  ][$tipe][$baru] ?? null;
  if ($ke) notif($ke[0], 'persetujuan', $ke[1], $judul_item.($catatan !== '' ? ' - '.$catatan : ''), ($tipe === 'surat' && in_array($baru, ['disetujui','ditolak'])) ? 'surat.php' : 'persetujuan.php');
  if ($tipe === 'surat') {
    if ($catatan !== '') q('UPDATE surat SET catatan=? WHERE id=?', [$catatan, $id]);
    if ($baru === 'disetujui') {
      $t = q('SELECT * FROM templat_surat WHERE nama=?', [$r['jenis']])->fetch();
      $w = q('SELECT * FROM warga WHERE id=?', [$r['warga_id']])->fetch();
      if ($t && $w) {
        $extra = olah_input(json_decode($t['input_json'], true) ?: [], json_decode($r['data_json'] ?? '[]', true) ?: []) + ['keperluan' => $r['keperluan']];
        $tid = terbitkan($t, $w, $extra, $id, '', $opsi['ttd_qr'] ?? 1);
        q('UPDATE surat SET no_surat=(SELECT nomor FROM surat_terbit WHERE id=?) WHERE id=?', [$tid, $id]);
        $pesan = 'Disetujui dan surat berQR sudah terbit.';
      } else $pesan = 'Disetujui, tetapi templat untuk jenis surat ini tidak ditemukan. Buat suratnya lewat menu Buat Surat.';
    }
  }
  if ($tipe === 'draf' && $baru === 'terbit') {
    if (isset($opsi['ttd_qr'])) q('UPDATE surat_terbit SET ttd_qr=? WHERE id=?', [$opsi['ttd_qr'] ? 1 : 0, $id]);
    q('UPDATE surat_terbit SET penandatangan=? WHERE id=?', [cfg('ketua_nama'), $id]);
    $pesan = ($opsi['ttd_qr'] ?? 1) ? 'Disetujui. QR tanda tangan kini valid.' : 'Disetujui. Surat siap dicetak untuk ditandatangani basah.';
  }
  if ($tipe === 'kas' && $baru === 'disetujui') $pesan = 'Pengeluaran disetujui dan masuk ke laporan kas.';
  return [true, $baru === 'ditolak' ? 'Permintaan ditolak.' : $pesan];
}
function tugas_saya() {
  $t = [];
  $n = fn($sql) => (int)q($sql)->fetchColumn();
  if (bisa('surat_teruskan')) $t[] = ['Pengajuan surat perlu diteruskan', $n("SELECT COUNT(*) FROM surat WHERE status='diajukan'"), 'persetujuan.php'];
  if (bisa('surat_periksa')) {
    $t[] = ['Pengajuan surat perlu diperiksa', $n("SELECT COUNT(*) FROM surat WHERE status='diteruskan'"), 'persetujuan.php'];
    $t[] = ['Draf surat manual perlu diperiksa', $n("SELECT COUNT(*) FROM surat_terbit WHERE status='draf'"), 'persetujuan.php'];
    $t[] = ['Pengeluaran kas perlu diperiksa', $n("SELECT COUNT(*) FROM kas WHERE status='diajukan'"), 'persetujuan.php'];
  }
  if (bisa('surat_setujui')) {
    $t[] = ['Pengajuan surat menunggu persetujuan', $n("SELECT COUNT(*) FROM surat WHERE status='diperiksa'"), 'persetujuan.php'];
    $t[] = ['Draf surat manual menunggu persetujuan', $n("SELECT COUNT(*) FROM surat_terbit WHERE status='diperiksa'"), 'persetujuan.php'];
    $t[] = ['Pengeluaran kas menunggu persetujuan', $n("SELECT COUNT(*) FROM kas WHERE status='diperiksa'"), 'persetujuan.php'];
  }
  if (bisa('bayar_verifikasi')) $t[] = ['Bukti bayar perlu diverifikasi', $n("SELECT COUNT(*) FROM tagihan WHERE status='menunggu'"), 'tagihan.php'];
  return $t;
}
function simpan_bukti($field) {
  $f = $_FILES[$field] ?? null;
  if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 3*1024*1024) return '';
  $i = @getimagesize($f['tmp_name']);
  $ext = [IMAGETYPE_JPEG=>'jpg', IMAGETYPE_PNG=>'png', IMAGETYPE_WEBP=>'webp'][$i[2] ?? 0] ?? null;
  if (!$ext && file_get_contents($f['tmp_name'], false, null, 0, 4) === '%PDF') $ext = 'pdf';
  if (!$ext) return '';
  $d = __DIR__.'/uploads/bukti'; if (!is_dir($d)) mkdir($d, 0755, true);
  if (!is_file($d.'/.htaccess')) file_put_contents($d.'/.htaccess', "Require all denied\n");
  $n = bin2hex(random_bytes(10)).'.'.$ext;
  return move_uploaded_file($f['tmp_name'], "$d/$n") ? $n : '';
}
function normal_hp($v) {
  $v = trim((string)$v);
  if (preg_match('/^[0-9.]+E\+?[0-9]+$/i', $v)) $v = sprintf('%.0f', (float)$v);
  $v = preg_replace('/\D/', '', $v);
  if ($v === '') return '';
  if (strpos($v, '62') === 0) $v = '0'.substr($v, 2);
  elseif ($v[0] === '8') $v = '0'.$v;
  return $v;
}
function baca_csv($path) {
  $h = fopen($path, 'r'); if (!$h) return null;
  $baris = fgets($h); rewind($h); $sep = substr_count($baris, ';') > substr_count($baris, ',') ? ';' : ',';
  $rows = []; while (($r = fgetcsv($h, 0, $sep)) !== false) { if ($rows === [] && isset($r[0])) $r[0] = preg_replace('/^\xEF\xBB\xBF/', '', $r[0]); $rows[] = $r; }
  fclose($h); return $rows;
}
function baca_xlsx($path) {
  if (!class_exists('ZipArchive')) return null;
  $z = new ZipArchive; if ($z->open($path) !== true) return null;
  $ss = [];
  if (($x = $z->getFromName('xl/sharedStrings.xml')) !== false) {
    $sx = @simplexml_load_string($x);
    if ($sx) foreach ($sx->si as $si) { if (isset($si->t)) $ss[] = (string)$si->t; else { $t = ''; foreach ($si->r as $r) $t .= (string)$r->t; $ss[] = $t; } }
  }
  $sheet = $z->getFromName('xl/worksheets/sheet1.xml'); $z->close();
  $sx = $sheet ? @simplexml_load_string($sheet) : null; if (!$sx) return null;
  $rows = [];
  foreach ($sx->sheetData->row as $row) {
    $r = [];
    foreach ($row->c as $c) {
      preg_match('/^([A-Z]+)/', (string)$c['r'], $m); $idx = 0;
      foreach (str_split($m[1] ?? 'A') as $ch) $idx = $idx * 26 + (ord($ch) - 64);
      $t = (string)$c['t']; $v = (string)$c->v;
      if ($t === 's') $v = $ss[(int)$v] ?? ''; elseif ($t === 'inlineStr') $v = (string)$c->is->t;
      $r[$idx - 1] = $v;
    }
    if ($r) { $max = max(array_keys($r)); $o = []; for ($i = 0; $i <= $max; $i++) $o[$i] = $r[$i] ?? ''; $rows[] = $o; }
  }
  return $rows;
}


// ====== Konten beranda, tema, favicon ======
const TEMA = [
  'teal'     => ['Teal Segar', 'Bersih dan ringan, cocok untuk perumahan modern.', ['#0E7C86','#E6F4F5','#F2B705','#F5F7FA']],
  'biru'     => ['Biru Resmi', 'Formal dan tepercaya, sidebar gelap ala kantor kelurahan.', ['#1D4E89','#E7EFF9','#E3B23C','#F4F6FA']],
  'terakota' => ['Hangat Terakota', 'Hangat dan guyub, judul berhuruf serif.', ['#B5532F','#FBEDE6','#E9B949','#FBF7F2']],
];
const IKON_FITUR = ['bi-bank','bi-envelope-paper','bi-megaphone','bi-wallet2','bi-people','bi-chat-left-text','bi-shield-check','bi-tree','bi-heart','bi-calendar-event','bi-house-heart','bi-trophy','bi-lightning','bi-droplet','bi-camera','bi-geo-alt','bi-bicycle','bi-basket'];
function cfg_raw($k) { try { $v = q('SELECT v FROM pengaturan WHERE k=?', [$k])->fetchColumn(); return $v === false ? '' : $v; } catch (PDOException $x) { return ''; } }
function brand_sub() { return cfg('brand_sub') ?: nama_rt().' · '.cfg('kelurahan'); }
function fitur_list() {
  $a = json_decode(cfg('fitur_json'), true);
  if (is_array($a) && $a) return $a;
  return [
    ['i'=>'bi-bank','j'=>'Kas RT yang terbuka','d'=>'Pemasukan, pengeluaran, dan dana khusus seperti saung atau playground bisa dilihat semua warga.'],
    ['i'=>'bi-envelope-paper','j'=>'Surat pengantar mandiri','d'=>'Ajukan surat domisili, KTP/KK, SKTM, hingga keterangan usaha tanpa datang ke rumah pengurus.'],
    ['i'=>'bi-megaphone','j'=>'Pengumuman & siskamling','d'=>'Jadwal kerja bakti, piket ronda, dan laporan keamanan dalam satu papan.'],
    ['i'=>'bi-wallet2','j'=>'Bayar iuran online','d'=>'Transfer bank atau e-wallet, unggah bukti, dan dapatkan struk digital.'],
    ['i'=>'bi-people','j'=>'Data warga terpusat','d'=>'Kepala keluarga, anggota, status hunian, dan kontak darurat tercatat rapi.'],
    ['i'=>'bi-chat-left-text','j'=>'Aduan & aspirasi','d'=>'Laporkan lampu jalan mati atau jalan rusak, lalu pantau statusnya.'],
  ];
}
function tema_aktif() {
  $t = cfg('tema');
  if (user() && user()['role'] === 'admin' && isset($_GET['pratinjau_tema'])) $t = $_GET['pratinjau_tema'];
  return array_key_exists($t, TEMA) ? $t : 'teal';
}
function tema_attr() { return ' data-theme="'.tema_aktif().'"'; }
function head_extra() {
  $h = '';
  $fav = foto(cfg('favicon')) ?: foto(cfg('logo'));
  if ($fav) {
    $tp = ['ico'=>'image/x-icon','png'=>'image/png','jpg'=>'image/jpeg','webp'=>'image/webp'][strtolower(pathinfo($fav, PATHINFO_EXTENSION))] ?? 'image/png';
    $h .= '<link rel="icon" type="'.$tp.'" href="'.e($fav).'">';
  }
  $font = ['biru'=>'Inter:wght@400;500;600;700;800', 'terakota'=>'Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800'][tema_aktif()] ?? '';
  if ($font && !aset_lokal('fonts.css')) $h .= '<link href="https://fonts.googleapis.com/css2?family='.$font.'&display=swap" rel="stylesheet">';
  return $h;
}
function upload_ico($field, $old = '') {
  $f = $_FILES[$field] ?? null;
  if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 512*1024) return $old;
  $ext = file_get_contents($f['tmp_name'], false, null, 0, 4) === "\x00\x00\x01\x00" ? 'ico' : null;
  if (!$ext) { $i = @getimagesize($f['tmp_name']); $ext = ($i[2] ?? 0) === IMAGETYPE_PNG ? 'png' : null; }
  if (!$ext) return $old;
  if (!is_dir(__DIR__.'/uploads')) mkdir(__DIR__.'/uploads', 0755, true);
  $n = 'fav-'.bin2hex(random_bytes(6)).'.'.$ext;
  if (!move_uploaded_file($f['tmp_name'], __DIR__.'/uploads/'.$n)) return $old;
  if ($old && is_file(__DIR__.'/uploads/'.basename($old))) @unlink(__DIR__.'/uploads/'.basename($old));
  return $n;
}


// ====== Notifikasi, darurat, chat ======
const JENIS_DARURAT = ['Kebakaran','Pencurian / maling','Darurat medis','Bencana / banjir','Keributan','Lainnya'];
function notif($uids, $tipe, $judul, $isi = '', $url = '') {
  $me = user()['id'] ?? 0;
  foreach (array_unique(array_filter(array_map('intval', (array)$uids))) as $u)
    if ($u !== (int)$me || $tipe === 'darurat') q('INSERT INTO notifikasi (user_id,tipe,judul,isi,url) VALUES (?,?,?,?,?)', [$u, $tipe, mb_substr($judul, 0, 150), mb_substr($isi, 0, 300), $url]);
}
// Pemegang wewenang tertentu; jika jabatannya kosong, notifikasi jatuh ke admin.
function uid_cap($cap) {
  $j = CAPS[$cap] ?? [];
  $ids = $j ? array_column(q("SELECT id FROM users WHERE role='rt' AND jabatan IN (".implode(',', array_fill(0, count($j), '?')).")", $j)->fetchAll(), 'id') : [];
  return $ids ?: array_column(q("SELECT id FROM users WHERE role='admin'")->fetchAll(), 'id');
}
function uid_warga($wid) { return array_column(q('SELECT id FROM users WHERE warga_id=?', [$wid])->fetchAll(), 'id'); }
function uid_semua() { return array_column(q('SELECT id FROM users')->fetchAll(), 'id'); }
function boleh_chat($kunci) {
  $u = user(); if (!$u) return false;
  if ($kunci === 'umum') return true;
  if ($kunci === 'pengurus') return pengurus();
  if (preg_match('/^dm:(\d+)-(\d+)$/', $kunci, $m)) return (int)$m[1] < (int)$m[2] && in_array((int)$u['id'], [(int)$m[1], (int)$m[2]]);
  return false;
}
function kunci_dm($a, $b) { $a = (int)$a; $b = (int)$b; return 'dm:'.min($a, $b).'-'.max($a, $b); }


// ====== Sensor data untuk verifikasi publik ======
// "Budi Santoso" -> "Bu** Sa*****"; kata pendek tetap disamarkan sebagian.
function sensor($s, $buka = 2) {
  $o = [];
  foreach (preg_split('/\s+/u', trim((string)$s), -1, PREG_SPLIT_NO_EMPTY) as $k) {
    $n = mb_strlen($k); $b = $n <= 2 ? 1 : ($n <= 4 ? min($buka, 2) : $buka);
    $o[] = mb_substr($k, 0, $b).str_repeat('*', max(1, $n - $b));
  }
  return implode(' ', $o);
}
function data_verifikasi($s) {
  $pemohon = $s['warga_id'] ? q('SELECT nama_kk FROM warga WHERE id=?', [$s['warga_id']])->fetchColumn() : '';
  return [
    'nomor' => $s['nomor'], 'jenis' => $s['judul'], 'tanggal' => tgl_id($s['created_at']),
    'pemohon' => $pemohon ? sensor($pemohon) : '-', 'tujuan' => $s['keperluan'] ? sensor($s['keperluan'], 3) : '-',
    'penandatangan' => 'Ketua '.nama_rt().($s['penandatangan'] ? ', '.sensor($s['penandatangan']) : ''),
    'ttd' => !empty($s['ttd_qr']) ? 'Tanda tangan digital (QR)' : 'Tanda tangan basah',
  ];
}

// ==== sinkron sesi (hak akses selalu diambil dari database) ====
if (!empty($_SESSION['user']['id'])) {
  try {
    $r = q('SELECT id,nama,role,warga_id,jabatan,ganti_sandi FROM users WHERE id=?', [$_SESSION['user']['id']])->fetch();
    if ($r) $_SESSION['user'] = $r; else unset($_SESSION['user']);
  } catch (PDOException $x) {}
}
