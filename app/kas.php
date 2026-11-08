<?php require 'config.php'; require 'layout.php'; need(['admin','rt','warga']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check(); izin('kas_catat');
  if (isset($_POST['hapus'])) {
    $k = q('SELECT * FROM kas WHERE id=?', [$_POST['hapus']])->fetch();
    if ($k && !$k['tagihan_id'] && ($k['status'] !== 'disetujui' || user()['role'] === 'admin')) { q('DELETE FROM kas WHERE id=?', [$k['id']]); flash('Catatan dihapus.'); }
    else flash('Catatan yang sudah disetujui tidak dapat dihapus.', 'danger');
  } else {
    $keluar = ($_POST['tipe'] ?? '') === 'keluar';
    q('INSERT INTO kas (tanggal,tipe,kategori,alokasi,keterangan,nominal,status,alur) VALUES (?,?,?,?,?,?,?,?)',
      [$_POST['tanggal'], $keluar ? 'keluar' : 'masuk', trim($_POST['kategori']), trim($_POST['alokasi']), trim($_POST['keterangan']), (int)$_POST['nominal'],
       $keluar ? 'diajukan' : 'disetujui', $keluar ? catat_alur('', 'Diajukan oleh '.user()['nama'].' (Bendahara)') : null]);
    if ($keluar) notif(uid_cap('kas_periksa'), 'persetujuan', 'Pengeluaran perlu diperiksa', trim($_POST['keterangan']) ?: trim($_POST['kategori']).' ('.rp((int)$_POST['nominal']).')', 'persetujuan.php');
    flash($keluar ? 'Pengeluaran diajukan. Menunggu pemeriksaan Wakil Ketua dan persetujuan Ketua.' : 'Pemasukan dicatat.');
  }
  header('Location: kas.php'); exit;
}
$th = (int)($_GET['tahun'] ?? date('Y'));
$bl = (int)($_GET['bulan'] ?? 0); if ($bl < 0 || $bl > 12) $bl = 0;
$BLN = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$sum = q("SELECT COALESCE(SUM(IF(tipe='masuk',nominal,0)),0) m, COALESCE(SUM(IF(tipe='keluar',nominal,0)),0) k FROM kas WHERE YEAR(tanggal)=? AND status='disetujui'", [$th])->fetch();
head('Kas RT '.$th, 'Hanya transaksi yang sudah disetujui yang dihitung dalam saldo dan laporan.'); ?>
<div class="no-print mb-3 d-flex gap-2 align-items-center">
  <form class="d-flex gap-2"><select name="bulan" class="form-select" onchange="this.form.submit()"><option value="0">Semua bulan</option><?php for ($i = 1; $i <= 12; $i++) echo "<option value='$i'".($i === $bl ? ' selected' : '').">{$BLN[$i]}</option>"; ?></select><select name="tahun" class="form-select" onchange="this.form.submit()">
    <?php foreach (q('SELECT DISTINCT YEAR(tanggal) y FROM kas UNION SELECT YEAR(CURDATE()) ORDER BY y DESC') as $y) echo "<option".($y['y']==$th?' selected':'').">{$y['y']}</option>"; ?></select></form>
  <button class="btn btn-light" onclick="print()"><i class="bi bi-printer"></i> Cetak laporan tahunan</button>
</div>
<div class="row g-3 mb-4">
  <?php kartu('Pemasukan '.$th, rp($sum['m'])); kartu('Pengeluaran '.$th, rp($sum['k'])); kartu('Selisih tahun ini', rp($sum['m']-$sum['k'])); kartu('Saldo kas saat ini', rp(saldo())); ?>
</div>
<div class="panel mb-4 table-responsive"><h2 class="h5 mb-3">Laporan bulanan <?= $th ?></h2><table class="table mb-0"><thead><tr><th>Bulan</th><th class="text-end">Pemasukan</th><th class="text-end">Pengeluaran</th><th class="text-end">Selisih</th></tr></thead><tbody>
<?php $rb = []; foreach (q("SELECT MONTH(tanggal) m, SUM(IF(tipe='masuk',nominal,0)) a, SUM(IF(tipe='keluar',nominal,0)) k FROM kas WHERE YEAR(tanggal)=? AND status='disetujui' GROUP BY m", [$th]) as $r) $rb[(int)$r['m']] = $r;
for ($i = 1; $i <= 12; $i++): $r = $rb[$i] ?? ['a'=>0,'k'=>0]; if (!$r['a'] && !$r['k'] && $i > (int)date('n') && $th == date('Y')) continue; ?>
<tr><td><a href="?tahun=<?= $th ?>&amp;bulan=<?= $i ?>#mutasi"><?= $BLN[$i] ?></a></td><td class="text-end text-success"><?= rp($r['a']) ?></td><td class="text-end text-danger"><?= rp($r['k']) ?></td><td class="text-end fw-semibold"><?= rp($r['a'] - $r['k']) ?></td></tr>
<?php endfor; ?></tbody></table></div>
<div class="panel mb-4 table-responsive"><h2 class="h5 mb-3">Dana per alokasi</h2><table class="table mb-0"><thead><tr><th>Alokasi</th><th>Masuk</th><th>Keluar</th><th>Sisa</th></tr></thead><tbody>
<?php foreach (q("SELECT alokasi, SUM(IF(tipe='masuk',nominal,0)) m, SUM(IF(tipe='keluar',nominal,0)) k FROM kas WHERE status='disetujui' GROUP BY alokasi ORDER BY alokasi") as $a): ?>
<tr><td><?= e($a['alokasi']) ?></td><td><?= rp($a['m']) ?></td><td><?= rp($a['k']) ?></td><td><strong><?= rp($a['m']-$a['k']) ?></strong></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php if (bisa('kas_catat')): ?>
<div class="panel mb-4 no-print"><h2 class="h5 mb-3">Posting pengeluaran / catat pemasukan</h2>
<form method="post" class="row g-2"><?= csrf_field() ?>
  <div class="col-md-2"><input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
  <div class="col-md-2"><select name="tipe" class="form-select"><option value="keluar">Pengeluaran</option><option value="masuk">Pemasukan</option></select></div>
  <div class="col-md-2"><input name="kategori" class="form-control" placeholder="Kategori" required></div>
  <div class="col-md-2"><input name="alokasi" class="form-control" value="Kas umum" required></div>
  <div class="col-md-2"><input type="number" name="nominal" min="1" class="form-control" placeholder="Nominal" required></div>
  <div class="col-md-2"><button class="btn btn-primary w-100">Simpan</button></div>
  <div class="col-12"><input name="keterangan" class="form-control" placeholder="Keterangan, misalnya beli semen untuk saung"></div>
</form><div class="small text-secondary mt-2">Pengeluaran baru berstatus <em>diajukan</em> dan baru berlaku setelah diperiksa Wakil Ketua dan disetujui Ketua.</div></div>
<?php endif; ?>
<div class="panel table-responsive" id="mutasi"><h2 class="h5 mb-3">Mutasi <?= $bl ? $BLN[$bl].' ' : '' ?><?= $th ?></h2><table class="table align-middle mb-0"><thead><tr><th>Tanggal</th><th>Keterangan</th><th>Alokasi</th><th class="text-end">Nominal</th><?php if (pengurus()) echo '<th>Status</th>'; ?><th class="no-print"></th></tr></thead><tbody>
<?php foreach (q('SELECT * FROM kas WHERE YEAR(tanggal)=? '.($bl ? 'AND MONTH(tanggal)='.$bl.' ' : '').(pengurus() ? '' : "AND status='disetujui' ").'ORDER BY tanggal DESC, id DESC', [$th]) as $k): ?>
<tr><td><?= tgl($k['tanggal']) ?></td><td><?= e($k['keterangan'] ?: $k['kategori']) ?><div class="small text-secondary"><?= e($k['kategori']) ?></div></td><td><?= e($k['alokasi']) ?></td>
<td class="text-end <?= $k['tipe']==='masuk' ? 'text-success' : 'text-danger' ?>"><?= $k['tipe']==='masuk' ? '+' : '-' ?><?= rp($k['nominal']) ?></td>
<?php if (pengurus()) echo '<td>'.badge($k['status']).'</td>'; ?>
<td class="no-print"><?php if (bisa('kas_catat') && !$k['tagihan_id'] && ($k['status'] !== 'disetujui' || user()['role'] === 'admin')): ?><form method="post" onsubmit="return confirm('Hapus catatan ini?')"><?= csrf_field() ?><button name="hapus" value="<?= $k['id'] ?>" class="btn btn-sm btn-light text-danger">Hapus</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php foot();
