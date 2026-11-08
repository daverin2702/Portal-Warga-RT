<?php require 'config.php';
// Endpoint JSON untuk notifikasi, tombol darurat, dan chat (dipanggil oleh assets/app.js)
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function keluar($d, $kode = 200) { http_response_code($kode); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
$u = user();
if (!$u) keluar(['error' => 'Sesi berakhir. Silakan masuk lagi.'], 401);
$me = (int)$u['id'];
$aksi = $_GET['aksi'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SERVER['HTTP_X_CSRF'] ?? '') !== csrf()) keluar(['error' => 'Token tidak valid. Muat ulang halaman.'], 403);
$in = $_SERVER['REQUEST_METHOD'] === 'POST' ? (json_decode(file_get_contents('php://input'), true) ?: []) : [];

function nama_lokasi($uid) {
  $r = q('SELECT u.nama, w.alamat, w.blok, w.no_rumah FROM users u LEFT JOIN warga w ON w.id=u.warga_id WHERE u.id=?', [$uid])->fetch();
  if (!$r) return ['-', ''];
  $l = trim(($r['blok'] ? 'Blok '.$r['blok'] : '').($r['no_rumah'] ? ' No. '.$r['no_rumah'] : ''));
  return [$r['nama'], trim($l.($r['alamat'] ? ($l ? ', ' : '').$r['alamat'] : ''))];
}
function daftar_kunci() {
  global $me;
  $k = ['umum'];
  if (pengurus()) $k[] = 'pengurus';
  foreach (q("SELECT DISTINCT kunci FROM chat WHERE kunci LIKE ? OR kunci LIKE ?", ["dm:$me-%", "dm:%-$me"]) as $r) $k[] = $r['kunci'];
  return $k;
}
function belum_dibaca($kunci) {
  global $me;
  $last = (int)q('SELECT last_id FROM chat_baca WHERE user_id=? AND kunci=?', [$me, $kunci])->fetchColumn();
  return (int)q('SELECT COUNT(*) FROM chat WHERE kunci=? AND id>? AND dari<>?', [$kunci, $last, $me])->fetchColumn();
}

switch ($aksi) {
  // ---------- polling ringan: lonceng, darurat aktif, chat belum dibaca ----------
  case 'poll':
    $items = q('SELECT id,tipe,judul,isi,url,dibaca,created_at FROM notifikasi WHERE user_id=? ORDER BY id DESC LIMIT 15', [$me])->fetchAll();
    $belum = (int)q('SELECT COUNT(*) FROM notifikasi WHERE user_id=? AND dibaca=0', [$me])->fetchColumn();
    $dar = q("SELECT d.id,d.jenis,d.pesan,d.lokasi,d.created_at,u.nama FROM darurat d JOIN users u ON u.id=d.user_id WHERE d.selesai_at IS NULL AND d.created_at > NOW() - INTERVAL 6 HOUR ORDER BY d.id DESC")->fetchAll();
    $chat = 0; foreach (daftar_kunci() as $k) $chat += belum_dibaca($k);
    keluar(['belum' => $belum, 'items' => $items, 'darurat' => $dar, 'chat' => $chat]);

  case 'baca':
    if (!empty($in['semua'])) q('UPDATE notifikasi SET dibaca=1 WHERE user_id=?', [$me]);
    else q('UPDATE notifikasi SET dibaca=1 WHERE user_id=? AND id=?', [$me, (int)($in['id'] ?? 0)]);
    keluar(['ok' => true]);

  // ---------- tombol darurat ----------
  case 'darurat':
    $jenis = in_array($in['jenis'] ?? '', JENIS_DARURAT) ? $in['jenis'] : 'Lainnya';
    $pesan = mb_substr(trim((string)($in['pesan'] ?? '')), 0, 300);
    if (q('SELECT COUNT(*) FROM darurat WHERE user_id=? AND created_at > NOW() - INTERVAL 2 MINUTE', [$me])->fetchColumn())
      keluar(['error' => 'Sinyal darurat Anda baru saja terkirim. Tunggu 2 menit sebelum mengirim lagi.'], 429);
    [$nama, $lok] = nama_lokasi($me);
    q('INSERT INTO darurat (user_id,jenis,pesan,lokasi) VALUES (?,?,?,?)', [$me, $jenis, $pesan, $lok]);
    notif(uid_semua(), 'darurat', 'DARURAT: '.$jenis, $nama.($lok ? ' - '.$lok : '').($pesan ? '. '.$pesan : ''), 'dashboard.php');
    keluar(['ok' => true, 'pesan' => 'Sinyal darurat terkirim ke semua pengurus dan warga.']);

  case 'darurat_selesai':
    if (!pengurus()) keluar(['error' => 'Hanya pengurus yang dapat menandai situasi aman.'], 403);
    $d = q('SELECT * FROM darurat WHERE id=? AND selesai_at IS NULL', [(int)($in['id'] ?? 0)])->fetch();
    if (!$d) keluar(['error' => 'Sinyal ini sudah ditangani.'], 404);
    q('UPDATE darurat SET selesai_at=NOW(), selesai_oleh=? WHERE id=?', [$u['nama'], $d['id']]);
    notif(uid_semua(), 'info', 'Situasi aman: '.$d['jenis'], 'Ditangani oleh '.$u['nama'].($d['lokasi'] ? ' - '.$d['lokasi'] : ''), 'dashboard.php');
    keluar(['ok' => true]);

  // ---------- chat ----------
  case 'chat_list':
    $daftar = [];
    foreach (daftar_kunci() as $k) {
      if ($k === 'umum') $nama = 'Grup Warga';
      elseif ($k === 'pengurus') $nama = 'Grup Pengurus';
      else { preg_match('/^dm:(\d+)-(\d+)$/', $k, $m); $lain = (int)$m[1] === $me ? (int)$m[2] : (int)$m[1]; $nama = q('SELECT nama FROM users WHERE id=?', [$lain])->fetchColumn() ?: 'Pengguna'; }
      $akhir = q('SELECT isi, created_at FROM chat WHERE kunci=? ORDER BY id DESC LIMIT 1', [$k])->fetch();
      $daftar[] = ['kunci' => $k, 'nama' => $nama, 'grup' => strpos($k, 'dm:') !== 0, 'belum' => belum_dibaca($k), 'akhir' => $akhir ? mb_substr($akhir['isi'], 0, 60) : '', 'waktu' => $akhir['created_at'] ?? ''];
    }
    usort($daftar, fn($a, $b) => [$b['grup'], $b['waktu']] <=> [$a['grup'], $a['waktu']]);
    // Kontak untuk pesan pribadi: warga bisa menghubungi pengurus; pengurus bisa menghubungi siapa saja.
    $kontak = pengurus()
      ? q("SELECT id, nama, jabatan, role FROM users WHERE id<>? ORDER BY role<>'rt', nama", [$me])->fetchAll()
      : q("SELECT id, nama, jabatan, role FROM users WHERE id<>? AND role IN ('rt','admin') ORDER BY role<>'rt', nama", [$me])->fetchAll();
    foreach ($kontak as &$x) { $x['label'] = $x['role'] === 'admin' ? 'Administrator' : (JABATAN[$x['jabatan']] ?? 'Warga'); $x['kunci'] = kunci_dm($me, $x['id']); unset($x['role'], $x['jabatan']); }
    keluar(['daftar' => $daftar, 'kontak' => $kontak]);

  case 'chat_ambil':
    $k = (string)($_GET['kunci'] ?? '');
    if (!boleh_chat($k)) keluar(['error' => 'Percakapan tidak tersedia.'], 403);
    $setelah = (int)($_GET['setelah'] ?? 0);
    $rows = q('SELECT c.id, c.dari, c.isi, c.created_at, u.nama FROM chat c JOIN users u ON u.id=c.dari WHERE c.kunci=? AND c.id>? ORDER BY c.id DESC LIMIT 100', [$k, $setelah])->fetchAll();
    $rows = array_reverse($rows);
    if ($rows) q('INSERT INTO chat_baca (user_id,kunci,last_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE last_id=GREATEST(last_id,VALUES(last_id))', [$me, $k, end($rows)['id']]);
    foreach ($rows as &$r) $r['saya'] = (int)$r['dari'] === $me;
    keluar(['pesan' => $rows]);

  case 'chat_kirim':
    $k = (string)($in['kunci'] ?? ''); $isi = trim((string)($in['isi'] ?? ''));
    if (!boleh_chat($k)) keluar(['error' => 'Percakapan tidak tersedia.'], 403);
    if ($isi === '' || mb_strlen($isi) > 1000) keluar(['error' => 'Pesan kosong atau terlalu panjang (maks. 1000 karakter).'], 422);
    if (strpos($k, 'dm:') === 0 && !pengurus()) {
      preg_match('/^dm:(\d+)-(\d+)$/', $k, $m); $lain = (int)$m[1] === $me ? (int)$m[2] : (int)$m[1];
      if (!q("SELECT COUNT(*) FROM users WHERE id=? AND role IN ('rt','admin')", [$lain])->fetchColumn()) keluar(['error' => 'Warga hanya dapat mengirim pesan pribadi ke pengurus.'], 403);
    }
    q('INSERT INTO chat (kunci,dari,isi) VALUES (?,?,?)', [$k, $me, $isi]);
    keluar(['ok' => true]);
}
keluar(['error' => 'Aksi tidak dikenal.'], 400);
