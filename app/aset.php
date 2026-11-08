<?php
/* =====================================================================
   Aset tampilan (Bootstrap, ikon, font, pustaka JS).
   Jika salinan lokal ada di app/assets/vendor, semua halaman memakainya
   (website bisa dibuka tanpa internet). Jika belum, dipakai CDN.
   Salinan lokal dibuat sekali lewat menu admin "Aset Offline" atau installer.
   ===================================================================== */
const ASET_DIR = __DIR__.'/assets/vendor';
const ASET = [
  // kunci            => [berkas lokal (relatif ke app/), alamat CDN]
  'bootstrap.css'    => ['assets/vendor/bootstrap-5.3.8-dist/css/bootstrap.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'],
  'bootstrap.js'     => ['assets/vendor/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js'],
  'icons.css'        => ['assets/vendor/bootstrap-icons-1.13.2/bootstrap-icons.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css'],
  'fonts.css'        => ['assets/vendor/font/fonts.css', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap'],
  'chart.js'         => ['assets/vendor/chart/chart.umd.min.js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js'],
  'qrcode.js'        => ['assets/vendor/qrcode/qrcode.min.js', 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js'],
  'html5-qrcode.js'  => ['assets/vendor/html5-qrcode/html5-qrcode.min.js', 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js'],
];
// Berkas tambahan yang diunduh (font ikon dan font tema). Font Google diambil lewat fonts.css lengkap di bawah.
const ASET_EKSTRA = [
  'assets/vendor/bootstrap-icons-1.13.2/fonts/bootstrap-icons.woff2' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/fonts/bootstrap-icons.woff2',
  'assets/vendor/bootstrap-icons-1.13.2/fonts/bootstrap-icons.woff'  => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/fonts/bootstrap-icons.woff',
];
// Semua keluarga font yang dipakai tema (Teal: Plus Jakarta Sans, Biru: Inter, Terakota: Fraunces).
const ASET_FONT_SEMUA = 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&display=swap';

function aset_lokal($k) { return isset(ASET[$k]) && is_file(__DIR__.'/'.ASET[$k][0]) && filesize(__DIR__.'/'.ASET[$k][0]) > 0; }
// $pre: awalan jalur jika dipanggil dari luar folder app (mis. 'app/' untuk install.php)
function aset($k, $pre = '') {
  if (!isset(ASET[$k])) return '';
  if (aset_lokal($k)) return $pre.ASET[$k][0].'?v='.filemtime(__DIR__.'/'.ASET[$k][0]);
  return ASET[$k][1];
}
function aset_css($pre = '', $gaya = true) {
  $h = '';
  foreach (['fonts.css', 'bootstrap.css', 'icons.css'] as $k) $h .= '<link href="'.htmlspecialchars(aset($k, $pre)).'" rel="stylesheet">'."\n";
  if (!$gaya) return $h;
  return $h.'<link href="'.$pre.'assets/style.css?v='.@filemtime(__DIR__.'/assets/style.css').'" rel="stylesheet">'."\n";
}
function aset_offline_lengkap() { foreach (array_keys(ASET) as $k) if (!aset_lokal($k)) return false; return true; }

// ---------- pengunduh ----------
function aset_ambil($url, $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36') {
  if (function_exists('curl_init')) {
    $ca = array_filter([ini_get('curl.cainfo'), ini_get('openssl.cafile'),
      dirname(PHP_BINARY).'/../apache/bin/curl-ca-bundle.crt', dirname(PHP_BINARY).'/extras/ssl/cacert.pem',
      'C:/xampp/apache/bin/curl-ca-bundle.crt', 'C:/xampp/php/extras/ssl/cacert.pem'], fn($f) => $f && is_file($f));
    $c = curl_init($url);
    $opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 90, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_USERAGENT => $ua];
    if ($ca) $opt[CURLOPT_CAINFO] = reset($ca);
    curl_setopt_array($c, $opt);
    $d = curl_exec($c); $kode = curl_getinfo($c, CURLINFO_HTTP_CODE); $err = curl_error($c); curl_close($c);
    if ($d === false || $kode >= 400 || $d === '') {
      if (stripos($err, 'certificate') !== false) $err .= '. Sertifikat SSL tidak ditemukan: isi curl.cainfo di php.ini dengan lokasi berkas cacert.pem, lalu restart Apache.';
      return [null, $err ?: "HTTP $kode"];
    }
    return [$d, ''];
  }
  if (!ini_get('allow_url_fopen')) return [null, 'Aktifkan ekstensi curl atau allow_url_fopen di php.ini.'];
  $d = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 90, 'user_agent' => $ua, 'follow_location' => 1]]));
  return $d === false || $d === '' ? [null, 'Gagal mengunduh (pastikan ekstensi openssl aktif dan komputer terhubung internet).'] : [$d, ''];
}
function aset_tulis($rel, $isi) {
  $f = __DIR__.'/'.$rel; $d = dirname($f);
  if (!is_dir($d) && !@mkdir($d, 0755, true)) return 'Folder '.$d.' tidak bisa dibuat.';
  return @file_put_contents($f.'.tmp', $isi) !== false && @rename($f.'.tmp', $f) ? '' : 'Tidak bisa menulis '.$rel.' (periksa izin folder).';
}
// Mengunduh semua aset. Mengembalikan daftar [nama, ok(bool), keterangan].
function aset_unduh_semua() {
  @set_time_limit(300);
  $hasil = [];
  foreach (ASET as $k => [$lokal, $cdn]) {
    if ($k === 'fonts.css') continue;
    if (aset_lokal($k)) { $hasil[] = [$k, true, 'sudah ada']; continue; }
    [$d, $e] = aset_ambil($cdn);
    if ($d !== null && strlen($d) < 500) [$d, $e] = [null, 'Berkas yang diterima terlalu kecil'];
    if ($d !== null) $e = aset_tulis($lokal, $d);
    $hasil[] = [$k, $d !== null && $e === '', $e ?: round(strlen($d) / 1024).' KB'];
  }
  foreach (ASET_EKSTRA as $lokal => $cdn) {
    if (is_file(__DIR__.'/'.$lokal)) { $hasil[] = [basename($lokal), true, 'sudah ada']; continue; }
    [$d, $e] = aset_ambil($cdn);
    if ($d !== null) $e = aset_tulis($lokal, $d);
    $hasil[] = [basename($lokal), $d !== null && $e === '', $e ?: round(strlen($d) / 1024).' KB'];
  }
  // Font Google: unduh CSS (format woff2), unduh setiap berkas font, lalu ganti alamatnya ke salinan lokal.
  if (aset_lokal('fonts.css')) { $hasil[] = ['fonts.css', true, 'sudah ada']; return $hasil; }
  $fdir = dirname(ASET['fonts.css'][0]);
  [$css, $e] = aset_ambil(ASET_FONT_SEMUA);
  if ($css !== null && preg_match_all('#url\((https://fonts\.gstatic\.com/[^)]+)\)#', $css, $m)) {
    $gagal = 0; $n = 0;
    foreach (array_unique($m[1]) as $url) {
      $nama = 'f'.(++$n).'.'.(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'woff2');
      [$d, $e2] = aset_ambil($url);
      if ($d === null || aset_tulis($fdir.'/'.$nama, $d) !== '') { $gagal++; continue; }
      $css = str_replace('url('.$url.')', 'url('.$nama.')', $css);
    }
    $e = $gagal ? "$gagal dari $n berkas font gagal diunduh" : aset_tulis(ASET['fonts.css'][0], "/* Font lokal: Plus Jakarta Sans, Inter, Fraunces (Google Fonts, lisensi SIL OFL) */\n".$css);
    $hasil[] = ['fonts.css', $e === '', $e ?: "$n berkas font"];
  } else $hasil[] = ['fonts.css', false, $e ?: 'Format CSS font tidak dikenali'];
  return $hasil;
}
