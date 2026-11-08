<?php require 'config.php'; require 'layout.php'; need(['admin','rt','warga']);
// Titik integrasi payment gateway di masa depan: panggil lunasi() dari webhook setelah pembayaran terkonfirmasi.
function lunasi($id, $metode) {
  $t = q("SELECT t.*, w.nama_kk FROM tagihan t JOIN warga w ON w.id=t.warga_id WHERE t.id=? AND t.status IN ('belum','menunggu')", [$id])->fetch();
  if (!$t) return false;
  $no = 'STR-'.date('ymd').'-'.str_pad($id, 4, '0', STR_PAD_LEFT);
  q("UPDATE tagihan SET status='lunas', metode=?, no_struk=?, tgl_bayar=NOW(), catatan=NULL WHERE id=?", [$metode, $no, $id]);
  q("INSERT INTO kas (tanggal,tipe,kategori,alokasi,keterangan,nominal,tagihan_id,status) VALUES (CURDATE(),'masuk',?,?,?,?,?,'disetujui')",
    [$t['jenis'], $t['alokasi'], $t['jenis'].' '.$t['periode'].' - '.$t['nama_kk'], $t['nominal'], $id]);
  return $no;
}
$back = in_array($_POST['back'] ?? '', ['dashboard.php','tagihan.php']) ? $_POST['back'] : 'tagihan.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  if (isset($_POST['wajib'])) {
    izin('tagihan_buat');
    $per = preg_match('/^\d{4}-\d{2}$/', $_POST['periode'] ?? '') ? $_POST['periode'] : date('Y-m');
    $jenis = q('SELECT * FROM jenis_tagihan WHERE wajib=1 AND aktif=1')->fetchAll();
    $warga = array_column(q('SELECT id FROM warga')->fetchAll(), 'id');
    $baru = 0; $lewat = 0; $kena = [];
    foreach ($jenis as $j) foreach ($warga as $wid) {
      if (q('SELECT COUNT(*) FROM tagihan WHERE warga_id=? AND jenis=? AND periode=?', [$wid, $j['nama'], $per])->fetchColumn()) { $lewat++; continue; }
      q('INSERT INTO tagihan (warga_id,jenis,periode,alokasi,nominal) VALUES (?,?,?,?,?)', [$wid, $j['nama'], $per, $j['alokasi'], $j['nominal']]); $baru++; $kena[$wid][] = $j['nama'];
    }
    foreach ($kena as $wid => $nm) notif(uid_warga($wid), 'tagihan', 'Tagihan baru periode '.$per, implode(', ', $nm).'. Bayar dan unggah bukti di dasbor.', 'dashboard.php');
    flash($jenis ? "Tagihan wajib $per diterbitkan: $baru tagihan baru".($lewat ? ", $lewat dilewati karena sudah ada" : '').'.' : 'Belum ada jenis tagihan wajib yang aktif. Atur di menu Jenis Tagihan.', $jenis ? 'success' : 'warning');
  } elseif (isset($_POST['buat'])) {
    izin('tagihan_buat');
    $j = q('SELECT * FROM jenis_tagihan WHERE id=? AND aktif=1', [$_POST['jenis_id'] ?? 0])->fetch();
    $per = preg_match('/^\d{4}-\d{2}$/', $_POST['periode'] ?? '') ? $_POST['periode'] : date('Y-m');
    $nom = (int)($_POST['nominal'] ?? 0);
    if (!$j || $nom < 1000) { flash('Pilih jenis tagihan dan isi nominal minimal Rp 1.000.', 'danger'); header('Location: tagihan.php'); exit; }
    $target = $_POST['target'] ?? 'all';
    $ids = $target === 'all' ? array_column(q('SELECT id FROM warga')->fetchAll(), 'id') : [(int)$target];
    $baru = 0;
    foreach ($ids as $wid) {
      if (q('SELECT COUNT(*) FROM tagihan WHERE warga_id=? AND jenis=? AND periode=?', [$wid, $j['nama'], $per])->fetchColumn()) continue;
      q('INSERT INTO tagihan (warga_id,jenis,periode,alokasi,nominal) VALUES (?,?,?,?,?)', [$wid, $j['nama'], $per, $j['alokasi'], $nom]); $baru++;
      notif(uid_warga($wid), 'tagihan', 'Tagihan baru: '.$j['nama'], 'Periode '.$per.', '.rp($nom).'.', 'dashboard.php');
    }
    flash("$baru tagihan {$j['nama']} periode $per dibuat".($baru < count($ids) ? ' (sebagian sudah ada, dilewati)' : '').'.');
  } elseif (isset($_POST['upload']) && !pengurus()) {
    $t = q("SELECT * FROM tagihan WHERE id=? AND warga_id=? AND status='belum'", [$_POST['id'], wid()])->fetch();
    $m = in_array($_POST['metode'] ?? '', METODE_BAYAR) ? $_POST['metode'] : null;
    $f = $t && $m ? simpan_bukti('bukti') : '';
    if ($f) { q("UPDATE tagihan SET status='menunggu', metode=?, bukti=?, tgl_upload=NOW(), catatan=NULL WHERE id=?", [$m, $f, $t['id']]); flash('Bukti bayar terkirim. Menunggu verifikasi bendahara.'); notif(uid_cap('bayar_verifikasi'), 'tagihan', 'Bukti bayar masuk', user()['nama'].': '.$t['jenis'].' '.$t['periode'].', '.rp($t['nominal']), 'tagihan.php'); }
    else flash('Bukti gagal diunggah. Gunakan foto JPG/PNG/WEBP atau PDF, maksimal 3 MB.', 'danger');
  } elseif (isset($_POST['terima'])) {
    izin('bayar_verifikasi');
    $t = q('SELECT * FROM tagihan WHERE id=?', [$_POST['terima']])->fetch();
    if ($t && lunasi($t['id'], $t['metode'] ?? 'Tunai')) { flash('Pembayaran diverifikasi. Struk terbit dan kas bertambah.'); notif(uid_warga($t['warga_id']), 'tagihan', 'Pembayaran diterima', $t['jenis'].' '.$t['periode'].' lunas. Struk sudah tersedia.', 'tagihan.php'); }
    else flash('Tagihan tidak ditemukan atau sudah lunas.', 'warning');
  } elseif (isset($_POST['tunai'])) {
    izin('bayar_verifikasi'); lunasi($_POST['tunai'], 'Tunai'); flash('Tagihan ditandai lunas (tunai).');
  } elseif (isset($_POST['tolak'])) {
    izin('bayar_verifikasi');
    $t = q('SELECT * FROM tagihan WHERE id=?', [$_POST['tolak']])->fetch();
    if ($t && $t['bukti']) @unlink(__DIR__.'/uploads/bukti/'.basename($t['bukti']));
    q("UPDATE tagihan SET status='belum', bukti=NULL, catatan=? WHERE id=? AND status='menunggu'", [trim($_POST['catatan']) ?: 'Bukti tidak sesuai', $_POST['tolak']]);
    flash('Bukti ditolak dan dikembalikan ke warga.');
    if ($t) notif(uid_warga($t['warga_id']), 'tagihan', 'Bukti bayar ditolak', $t['jenis'].' '.$t['periode'].': '.(trim($_POST['catatan']) ?: 'Bukti tidak sesuai').'. Silakan unggah ulang.', 'dashboard.php');
  }
  header('Location: '.$back); exit;
}
$alokasi = '<datalist id="al"><option>Kas umum<option>Pembangunan saung<option>Playground<option>Siskamling<option>Kegiatan 17 Agustus</datalist>';
head('Iuran & Pembayaran', bisa('tagihan_buat') ? 'Bendahara menerbitkan tagihan (IPL, Kas RT, dan lainnya) dan memverifikasi bukti bayar.' : (pengurus() ? 'Tagihan seluruh warga.' : 'Riwayat tagihan Anda.')); echo $alokasi; ?>
<?php if (bisa('tagihan_buat')): $jt = q('SELECT * FROM jenis_tagihan WHERE aktif=1 ORDER BY wajib DESC, nama')->fetchAll(); $wj = array_filter($jt, fn($x) => $x['wajib']); ?>
<div class="row g-3 mb-4">
  <div class="col-lg-5"><div class="panel h-100"><h2 class="h5 mb-1">Terbitkan tagihan wajib</h2>
    <p class="small text-secondary">Satu klik menagih semua keluarga untuk setiap jenis tagihan wajib. Tagihan yang sudah ada untuk periode yang sama dilewati.</p>
    <?php if ($wj): ?><ul class="list-unstyled small mb-3"><?php foreach ($wj as $x) echo '<li class="d-flex justify-content-between border-top py-1"><span>'.e($x['nama']).'</span><strong>'.rp($x['nominal']).'</strong></li>'; ?></ul>
    <form method="post" class="d-flex gap-2"><?= csrf_field() ?><input type="month" name="periode" class="form-control" value="<?= date('Y-m') ?>" required>
      <button name="wajib" value="1" class="btn btn-primary text-nowrap" onclick="return confirm('Terbitkan semua tagihan wajib untuk seluruh keluarga?')">Terbitkan</button></form>
    <?php else: ?><div class="text-secondary small">Belum ada jenis tagihan wajib. <a href="jenis_tagihan.php">Atur jenis tagihan</a>.</div><?php endif; ?>
  </div></div>
  <div class="col-lg-7"><div class="panel h-100"><h2 class="h5 mb-1">Tagihan lain</h2>
    <p class="small text-secondary">Untuk tagihan di luar yang wajib, misalnya iuran 17 Agustus, atau menagih satu keluarga saja. <a href="jenis_tagihan.php">Tambah jenis baru</a>.</p>
    <form method="post" class="row g-2"><?= csrf_field() ?>
      <div class="col-md-5"><label class="form-label">Jenis</label><select name="jenis_id" id="jenisSel" class="form-select" required><?php foreach ($jt as $x) echo "<option value='{$x['id']}' data-nom='{$x['nominal']}'>".e($x['nama'])."</option>"; ?></select></div>
      <div class="col-md-3"><label class="form-label">Periode</label><input type="month" name="periode" class="form-control" value="<?= date('Y-m') ?>" required></div>
      <div class="col-md-4"><label class="form-label">Nominal (Rp)</label><input type="number" name="nominal" id="nomInp" min="1000" class="form-control" required></div>
      <div class="col-md-8"><label class="form-label">Ditagihkan ke</label><select name="target" class="form-select"><option value="all">Semua keluarga</option>
        <?php foreach (q('SELECT id,nama_kk,blok,no_rumah FROM warga ORDER BY blok, CAST(no_rumah AS UNSIGNED)') as $w) echo "<option value='{$w['id']}'>".e(trim($w['blok'].' '.$w['no_rumah']).' - '.$w['nama_kk'])."</option>"; ?></select></div>
      <div class="col-md-4 d-flex align-items-end"><button name="buat" value="1" class="btn btn-outline-primary w-100">Buat tagihan</button></div>
    </form>
    <script>const js=document.getElementById('jenisSel'),ni=document.getElementById('nomInp');function setNom(){const o=js.selectedOptions[0];if(o&&+o.dataset.nom)ni.value=o.dataset.nom;}js&&(js.onchange=setNom,setNom());</script>
  </div></div>
</div>
<?php elseif (!pengurus()): ?>
<div class="alert alert-light border small"><i class="bi bi-info-circle me-1"></i>Tagihan diterbitkan oleh Bendahara. Bayar tagihan yang ada, lalu unggah bukti pembayarannya.</div>
<?php endif; ?>
<div class="panel table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Periode</th><?php if (pengurus()) echo '<th>Keluarga</th>'; ?><th>Tagihan</th><th>Nominal</th><th>Status</th><th></th></tr></thead><tbody>
<?php $rows = pengurus() ? q("SELECT t.*, w.nama_kk FROM tagihan t JOIN warga w ON w.id=t.warga_id ORDER BY FIELD(t.status,'menunggu','belum','lunas'), t.id DESC LIMIT 300") : q('SELECT t.*, "" nama_kk FROM tagihan t WHERE warga_id=? ORDER BY t.id DESC', [wid()]);
foreach ($rows as $t): ?>
<tr><td><?= e($t['periode']) ?></td><?php if (pengurus()) echo '<td>'.e($t['nama_kk']).'</td>'; ?>
<td><?= e($t['jenis']) ?><div class="small text-secondary"><?= e($t['alokasi']) ?></div></td><td><?= rp($t['nominal']) ?></td>
<td><?= badge($t['status']) ?><?php if ($t['metode']) echo '<div class="small text-secondary">'.e($t['metode']).'</div>'; if ($t['catatan'] && $t['status']==='belum') echo '<div class="small text-danger">'.e($t['catatan']).'</div>'; ?></td>
<td style="min-width:260px">
<?php if ($t['status'] === 'lunas'): ?><a class="btn btn-sm btn-light" target="_blank" href="struk.php?id=<?= $t['id'] ?>">Struk</a>
<?php elseif ($t['status'] === 'menunggu'): ?>
  <a class="btn btn-sm btn-light" target="_blank" href="bukti.php?id=<?= $t['id'] ?>">Lihat bukti</a>
  <?php if (bisa('bayar_verifikasi')): ?>
  <form method="post" class="d-inline"><?= csrf_field() ?><button name="terima" value="<?= $t['id'] ?>" class="btn btn-sm btn-success">Terima</button></form>
  <form method="post" class="d-inline-flex gap-1"><?= csrf_field() ?><input name="catatan" class="form-control form-control-sm" style="width:130px" placeholder="Alasan tolak"><button name="tolak" value="<?= $t['id'] ?>" class="btn btn-sm btn-light text-danger">Tolak</button></form>
  <?php else: ?><span class="small text-secondary d-block">Menunggu bendahara</span><?php endif; ?>
<?php elseif (pengurus()): if (bisa('bayar_verifikasi')): ?><form method="post" onsubmit="return confirm('Tandai lunas (bayar tunai)?')"><?= csrf_field() ?><button name="tunai" value="<?= $t['id'] ?>" class="btn btn-sm btn-light">Lunas (tunai)</button></form><?php endif; ?>
<?php else: form_bukti($t, 'tagihan.php'); endif; ?>
</td></tr>
<?php endforeach; ?></tbody></table></div>
<?php foot();
