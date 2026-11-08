<?php require 'config.php';
// Endpoint publik pengecekan keaslian surat (dipakai pop-up "Cek Surat" di halaman depan).
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
function jawab($d, $k = 200) { http_response_code($k); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
// Batas sederhana: 20 pengecekan per 10 menit per sesi browser.
$_SESSION['cek'] = array_values(array_filter($_SESSION['cek'] ?? [], fn($t) => $t > time() - 600));
if (count($_SESSION['cek']) >= 20) jawab(['error' => 'Terlalu banyak pengecekan. Coba lagi dalam beberapa menit.'], 429);
$_SESSION['cek'][] = time();
$in = json_decode(file_get_contents('php://input'), true) ?: [];
$s = null;
if (!empty($in['kode'])) {
  // Isi QR berupa tautan verifikasi.php?t=TOKEN, atau token saja.
  $kode = trim((string)$in['kode']);
  if (preg_match('/[?&]t=([a-f0-9]{24})/i', $kode, $m) || preg_match('/^([a-f0-9]{24})$/i', $kode, $m))
    $s = q('SELECT * FROM surat_terbit WHERE token=?', [strtolower($m[1])])->fetch();
  else jawab(['valid' => false, 'pesan' => 'Kode QR ini bukan kode surat dari '.nama_rt().'.']);
} else {
  $nomor = trim((string)($in['nomor'] ?? '')); $tgl = (string)($in['tanggal'] ?? '');
  if ($nomor === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) jawab(['error' => 'Isi nomor surat dan tanggal pembuatan.'], 422);
  $s = q("SELECT * FROM surat_terbit WHERE REPLACE(nomor,' ','')=? AND DATE(created_at)=?", [preg_replace('/\s+/', '', $nomor), $tgl])->fetch();
}
if (!$s) jawab(['valid' => false, 'pesan' => 'Surat tidak ditemukan. Periksa kembali nomor dan tanggal, atau hubungi pengurus RT.']);
if ($s['status'] !== 'terbit') jawab(['valid' => false, 'pesan' => 'Surat ini tercatat tetapi belum disetujui Ketua RT, sehingga belum berlaku.']);
jawab(['valid' => true, 'data' => data_verifikasi($s)]);
