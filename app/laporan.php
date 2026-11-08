<?php require 'config.php'; require 'layout.php'; need(['admin','rt','warga']);
$ok = fn($v) => preg_match('/^\d{4}-\d{2}$/', (string)$v);
$dari = $ok($_GET['dari'] ?? '') ? $_GET['dari'] : date('Y-m');
$sampai = $ok($_GET['sampai'] ?? '') ? $_GET['sampai'] : $dari;
if ($sampai < $dari) [$dari, $sampai] = [$sampai, $dari];
$mulai = $dari.'-01'; $akhir = date('Y-m-t', strtotime($sampai.'-01'));
$BLN = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$nm = fn($ym) => $BLN[(int)substr($ym, 5, 2)].' '.substr($ym, 0, 4);
$judulPeriode = $dari === $sampai ? $nm($dari) : $nm($dari).' s.d. '.$nm($sampai);
$awal = (int)q("SELECT COALESCE(SUM(IF(tipe='masuk',nominal,-nominal)),0) FROM kas WHERE status='disetujui' AND tanggal < ?", [$mulai])->fetchColumn();
$rows = q("SELECT * FROM kas WHERE status='disetujui' AND tanggal BETWEEN ? AND ? ORDER BY tanggal, id", [$mulai, $akhir])->fetchAll();
$masuk = array_values(array_filter($rows, fn($r) => $r['tipe'] === 'masuk'));
$keluar = array_values(array_filter($rows, fn($r) => $r['tipe'] === 'keluar'));
$tm = array_sum(array_column($masuk, 'nominal')); $tk = array_sum(array_column($keluar, 'nominal'));
function rekap($list) { $o = []; foreach ($list as $r) { $o[$r['kategori']] = ($o[$r['kategori']] ?? 0) + $r['nominal']; } arsort($o); return $o; }
$bendahara = q("SELECT nama FROM users WHERE jabatan='bendahara' LIMIT 1")->fetchColumn() ?: '................................';
$menunggu = pengurus() ? (int)q("SELECT COUNT(*) FROM kas WHERE status IN ('diajukan','diperiksa') AND tanggal BETWEEN ? AND ?", [$mulai, $akhir])->fetchColumn() : 0;
function tabel_detail($judul, $list, $total, $warna) { ?>
  <div class="panel mb-3 table-responsive"><h2 class="h5 mb-3"><?= $judul ?></h2>
  <table class="table table-sm align-middle mb-0"><thead><tr><th style="width:44px">No</th><th style="width:110px">Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Alokasi</th><th class="text-end">Nominal</th></tr></thead><tbody>
  <?php foreach ($list as $i => $r): ?><tr><td><?= $i + 1 ?></td><td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td><td><?= e($r['keterangan'] ?: $r['kategori']) ?></td><td><?= e($r['kategori']) ?></td><td><?= e($r['alokasi']) ?></td><td class="text-end"><?= rp($r['nominal']) ?></td></tr><?php endforeach;
  if (!$list) echo '<tr><td colspan="6" class="text-secondary">Tidak ada transaksi pada periode ini.</td></tr>'; ?>
  </tbody><tfoot><tr><th colspan="5" class="text-end">Total</th><th class="text-end <?= $warna ?>"><?= rp($total) ?></th></tr></tfoot></table></div>
<?php }
head('Laporan Keuangan', 'Rincian pemasukan dan pengeluaran kas RT yang sudah disetujui. Bisa dicetak atau disimpan sebagai PDF.'); ?>
<div class="panel mb-3 no-print">
  <form class="row g-2 align-items-end">
    <div class="col-sm-4 col-md-3"><label class="form-label">Dari bulan</label><input type="month" name="dari" class="form-control" value="<?= e($dari) ?>"></div>
    <div class="col-sm-4 col-md-3"><label class="form-label">Sampai bulan</label><input type="month" name="sampai" class="form-control" value="<?= e($sampai) ?>"></div>
    <div class="col-sm-4 col-md-6 d-flex flex-wrap gap-2">
      <button class="btn btn-primary">Tampilkan</button>
      <a class="btn btn-light" href="?dari=<?= date('Y') ?>-01&amp;sampai=<?= date('Y') ?>-12">Tahun <?= date('Y') ?></a>
      <button type="button" class="btn btn-light" onclick="print()"><i class="bi bi-printer me-1"></i>Cetak / PDF</button>
    </div>
  </form>
  <?php if ($menunggu): ?><div class="small text-warning-emphasis mt-2"><i class="bi bi-hourglass-split me-1"></i><?= $menunggu ?> pengeluaran pada periode ini masih menunggu persetujuan dan belum masuk laporan.</div><?php endif; ?>
</div>

<div class="lap-print">
  <div class="d-none d-print-block mb-2"><?= kop_html() ?></div>
  <div class="text-center mb-3"><h2 class="h4 fw-bold mb-0">LAPORAN KEUANGAN KAS <?= strtoupper(nama_rt()) ?></h2><div class="text-secondary">Periode <?= e($judulPeriode) ?></div></div>

  <div class="row g-3 mb-3">
    <div class="col-md-6"><div class="panel h-100"><h2 class="h5 mb-3">Ringkasan</h2>
      <table class="table lap-sum mb-0"><tbody>
        <tr><td>Saldo awal (per <?= tgl_id($mulai) ?>)</td><td class="text-end"><?= rp($awal) ?></td></tr>
        <tr><td>Total pemasukan</td><td class="text-end text-success">+ <?= rp($tm) ?></td></tr>
        <tr><td>Total pengeluaran</td><td class="text-end text-danger">- <?= rp($tk) ?></td></tr>
        <tr class="fw-bold"><td>Saldo akhir (per <?= tgl_id($akhir) ?>)</td><td class="text-end"><?= rp($awal + $tm - $tk) ?></td></tr>
      </tbody></table></div></div>
    <div class="col-md-6"><div class="panel h-100"><h2 class="h5 mb-3">Per kategori</h2>
      <table class="table table-sm mb-0"><thead><tr><th>Kategori</th><th class="text-end">Masuk</th><th class="text-end">Keluar</th></tr></thead><tbody>
      <?php $rm = rekap($masuk); $rk = rekap($keluar);
      foreach (array_unique(array_merge(array_keys($rm), array_keys($rk))) as $k): ?>
        <tr><td><?= e($k) ?></td><td class="text-end"><?= isset($rm[$k]) ? rp($rm[$k]) : '-' ?></td><td class="text-end"><?= isset($rk[$k]) ? rp($rk[$k]) : '-' ?></td></tr>
      <?php endforeach; if (!$rm && !$rk) echo '<tr><td colspan="3" class="text-secondary">-</td></tr>'; ?></tbody></table></div></div>
  </div>

  <?php tabel_detail('Rincian pemasukan', $masuk, $tm, 'text-success'); tabel_detail('Rincian pengeluaran', $keluar, $tk, 'text-danger'); ?>

  <div class="row mt-4 text-center small">
    <div class="col-6"><div>Mengetahui,<br>Ketua <?= nama_rt() ?></div><div style="height:64px"></div><div class="fw-semibold"><u><?= e(cfg('ketua_nama')) ?></u></div></div>
    <div class="col-6"><div><?= e(cfg('kota')) ?>, <?= tgl_id(date('Y-m-d')) ?><br>Bendahara</div><div style="height:64px"></div><div class="fw-semibold"><u><?= e($bendahara) ?></u></div></div>
  </div>
  <div class="d-none d-print-block small text-secondary mt-3">Dicetak dari <?= e(nama_situs()) ?> pada <?= date('d/m/Y H:i') ?>.</div>
</div>
<?php foot();
