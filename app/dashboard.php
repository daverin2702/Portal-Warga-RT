<?php require 'config.php'; require 'layout.php'; need(['admin','rt','warga']);
$u = user();
if ($u['role'] === 'warga') {
  head('Tagihan & Pembayaran', 'Selesaikan tagihan Anda lalu unggah bukti bayar. Pembayaran masih manual dan diverifikasi bendahara.');
  $w = wid();
  $b = q("SELECT COUNT(*) c, COALESCE(SUM(nominal),0) s FROM tagihan WHERE warga_id=? AND status='belum'", [$w])->fetch();
  $m = q("SELECT COUNT(*) c, COALESCE(SUM(nominal),0) s FROM tagihan WHERE warga_id=? AND status='menunggu'", [$w])->fetch();
  $l = q("SELECT COUNT(*) FROM tagihan WHERE warga_id=? AND status='lunas'", [$w])->fetchColumn();
  echo '<div class="row g-3 mb-4">'; kartu('Belum dibayar', rp($b['s']).' <span class="fs-6 text-secondary">('.$b['c'].' tagihan)</span>', '#tagihan'); kartu('Menunggu verifikasi', $m['c'], '#tagihan'); kartu('Sudah lunas', $l, 'tagihan.php'); echo '</div>'; ?>
  <div class="row g-3" id="tagihan">
    <div class="col-lg-8">
      <?php $rows = q("SELECT * FROM tagihan WHERE warga_id=? AND status<>'lunas' ORDER BY id DESC", [$w])->fetchAll();
      if (!$rows) echo '<div class="panel text-center text-secondary py-5"><i class="bi bi-check-circle fs-1 text-success"></i><div class="mt-2">Tidak ada tagihan yang harus dibayar. Terima kasih!</div></div>';
      foreach ($rows as $t): ?>
      <div class="panel mb-3">
        <div class="d-flex justify-content-between align-items-start">
          <div><div class="fw-bold"><?= e($t['jenis']) ?> <span class="text-secondary fw-normal">&middot; <?= e($t['periode']) ?></span></div><div class="small text-secondary"><?= e($t['alokasi']) ?></div></div>
          <div class="text-end"><div class="fs-5 fw-bold"><?= rp($t['nominal']) ?></div><?= badge($t['status']) ?></div>
        </div>
        <?php if ($t['catatan']): ?><div class="alert alert-danger py-2 small mt-2 mb-0">Bukti sebelumnya ditolak: <?= e($t['catatan']) ?></div><?php endif; ?>
        <div class="mt-3">
          <?php if ($t['status'] === 'menunggu'): ?>
            <div class="small text-secondary"><i class="bi bi-hourglass-split me-1"></i>Bukti diterima <?= tgl($t['tgl_upload']) ?> via <?= e($t['metode']) ?>, menunggu verifikasi bendahara. <a href="bukti.php?id=<?= $t['id'] ?>" target="_blank">Lihat bukti</a></div>
          <?php else: form_bukti($t, 'dashboard.php'); endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <div class="panel"><h2 class="h6 fw-bold">Riwayat lunas terbaru</h2>
        <?php foreach (q("SELECT * FROM tagihan WHERE warga_id=? AND status='lunas' ORDER BY tgl_bayar DESC LIMIT 5", [$w]) as $t): ?>
          <div class="d-flex justify-content-between border-top py-2"><span><?= e($t['jenis']) ?> <span class="text-secondary"><?= e($t['periode']) ?></span></span>
          <span><?= rp($t['nominal']) ?> <a class="ms-2" target="_blank" href="struk.php?id=<?= $t['id'] ?>">Struk</a></span></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="col-lg-4"><div class="panel"><h2 class="h6 fw-bold"><i class="bi bi-bank me-1"></i>Cara membayar</h2>
      <p class="mb-3" style="white-space:pre-line"><?= e(cfg('rekening')) ?></p>
      <ol class="small text-secondary ps-3 mb-0"><li>Transfer sesuai nominal tagihan.</li><li>Foto atau tangkap layar bukti transfer.</li><li>Unggah bukti pada tagihan yang dibayar.</li><li>Bendahara memverifikasi, lalu struk digital terbit.</li></ol></div></div>
  </div>
<?php foot(); exit; }

// ===== Dasbor pengurus / admin =====
$jb = $u['role'] === 'admin' ? 'Administrator' : (JABATAN[$u['jabatan'] ?? 'warga'] ?? 'Pengurus');
head('Dasbor', $jb.' · '.nama_rt());
$tugas = array_filter(tugas_saya(), fn($x) => $x[1] > 0);
?>
<div class="panel mb-4">
  <h2 class="h5 mb-3"><i class="bi bi-bell me-1"></i>Perlu tindakan Anda</h2>
  <?php if ($tugas): foreach ($tugas as [$lbl, $n, $href]): ?>
    <a href="<?= $href ?>" class="d-flex justify-content-between align-items-center border-top py-2 text-decoration-none text-reset"><span><?= e($lbl) ?></span><span class="badge text-bg-warning"><?= $n ?></span></a>
  <?php endforeach; else: ?><div class="text-secondary">Tidak ada yang menunggu. Semua beres.</div><?php endif; ?>
  <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
    <?php if (bisa('surat_buat')): ?><a class="btn btn-primary btn-sm" href="buat_surat.php"><i class="bi bi-pencil-square me-1"></i>Buat surat</a><?php endif; ?>
    <?php if (bisa('tagihan_buat')): ?><a class="btn btn-primary btn-sm" href="tagihan.php"><i class="bi bi-wallet2 me-1"></i>Terbitkan tagihan</a><a class="btn btn-light btn-sm" href="jenis_tagihan.php">Jenis tagihan</a><?php endif; ?>
    <?php if (bisa('kas_catat')): ?><a class="btn btn-primary btn-sm" href="kas.php"><i class="bi bi-cash-coin me-1"></i>Posting pengeluaran</a><?php endif; ?>
    <a class="btn btn-light btn-sm" href="persetujuan.php">Pusat persetujuan</a><a class="btn btn-light btn-sm" href="arsip.php">Arsip surat</a>
  </div>
</div>
<?php
$kk = q('SELECT COUNT(*) FROM warga')->fetchColumn();
echo '<div class="row g-3 mb-4">';
kartu('Jumlah KK', $kk, 'warga.php');
kartu('Total jiwa', q('SELECT COALESCE(SUM(jml_anggota),0) FROM warga')->fetchColumn(), 'warga.php');
kartu('Saldo kas', rp(saldo()), 'kas.php');
kartu('Tagihan belum lunas', q("SELECT COUNT(*) FROM tagihan WHERE status<>'lunas'")->fetchColumn(), 'tagihan.php');
echo '</div>';

$blok = q("SELECT COALESCE(NULLIF(blok,''),'Tanpa blok') b, COUNT(*) c FROM warga GROUP BY b ORDER BY b")->fetchAll();
$hun = q('SELECT status_hunian s, COUNT(*) c FROM warga GROUP BY s')->fetchAll();
$masuk = array_fill(0, 12, 0); $keluar = array_fill(0, 12, 0);
foreach (q("SELECT MONTH(tanggal) m, SUM(IF(tipe='masuk',nominal,0)) a, SUM(IF(tipe='keluar',nominal,0)) k FROM kas WHERE YEAR(tanggal)=? AND status='disetujui' GROUP BY m", [date('Y')]) as $r) { $masuk[$r['m']-1] = (int)$r['a']; $keluar[$r['m']-1] = (int)$r['k']; }
$tag = q('SELECT status s, COUNT(*) c FROM tagihan WHERE periode=? GROUP BY s', [date('Y-m')])->fetchAll();
$J = fn($x) => json_encode($x, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE);
?>
<div class="row g-3 mb-4">
  <div class="col-lg-6"><div class="panel"><h2 class="h6 fw-bold">Jumlah KK per blok</h2><div class="chart-box"><canvas id="cBlok"></canvas></div></div></div>
  <div class="col-lg-3 col-md-6"><div class="panel"><h2 class="h6 fw-bold">Status hunian</h2><div class="chart-box"><canvas id="cHun"></canvas></div></div></div>
  <div class="col-lg-3 col-md-6"><div class="panel"><h2 class="h6 fw-bold">Tagihan <?= date('M Y') ?></h2><div class="chart-box"><canvas id="cTag"></canvas></div></div></div>
  <div class="col-12"><div class="panel"><h2 class="h6 fw-bold">Arus kas <?= date('Y') ?> (yang sudah disetujui)</h2><div class="chart-box"><canvas id="cKas"></canvas></div></div></div>
</div>
<div class="row g-3">
  <div class="col-lg-7"><div class="panel"><h2 class="h5">Pengumuman terbaru</h2>
    <?php foreach (q('SELECT * FROM pengumuman ORDER BY id DESC LIMIT 4') as $p): ?>
      <div class="border-top py-2"><span class="badge text-bg-secondary"><?= e($p['kategori']) ?></span> <strong class="ms-1"><?= e($p['judul']) ?></strong><div class="small text-secondary"><?= tgl($p['created_at']) ?></div></div>
    <?php endforeach; ?></div></div>
  <div class="col-lg-5"><div class="panel"><h2 class="h5">Kas terakhir</h2>
    <?php foreach (q("SELECT * FROM kas WHERE status='disetujui' ORDER BY tanggal DESC, id DESC LIMIT 5") as $k): ?>
      <div class="d-flex justify-content-between border-top py-2"><span><?= e($k['keterangan'] ?: $k['kategori']) ?><div class="small text-secondary"><?= e($k['alokasi']) ?></div></span>
      <span class="<?= $k['tipe']==='masuk' ? 'text-success' : 'text-danger' ?>"><?= $k['tipe']==='masuk' ? '+' : '-' ?><?= rp($k['nominal']) ?></span></div>
    <?php endforeach; ?></div></div>
</div>
<script src="<?= aset('chart.js') ?>"></script>
<script>
const blok=<?= $J($blok) ?>, hun=<?= $J($hun) ?>, tag=<?= $J($tag) ?>, masuk=<?= $J($masuk) ?>, keluar=<?= $J($keluar) ?>;
const CS=getComputedStyle(document.documentElement);const C=[CS.getPropertyValue('--brand').trim()||'#0E7C86','#F2B705','#2F6DB5','#C0392B','#6FAF3D','#8E6BBF','#EC8A1F'];
Chart.defaults.font.family=CS.getPropertyValue('--font').trim()||'system-ui';
const opt={responsive:true,maintainAspectRatio:false};
new Chart(cBlok,{type:'bar',data:{labels:blok.map(x=>x.b),datasets:[{label:'KK',data:blok.map(x=>+x.c),backgroundColor:C[0],borderRadius:6}]},options:{...opt,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
new Chart(cHun,{type:'doughnut',data:{labels:hun.map(x=>x.s),datasets:[{data:hun.map(x=>+x.c),backgroundColor:[C[0],C[1]]}]},options:{...opt,plugins:{legend:{position:'bottom'}}}});
new Chart(cTag,{type:'doughnut',data:{labels:tag.map(x=>x.s),datasets:[{data:tag.map(x=>+x.c),backgroundColor:[C[1],C[2],C[0]]}]},options:{...opt,plugins:{legend:{position:'bottom'}}}});
new Chart(cKas,{type:'bar',data:{labels:['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],datasets:[{label:'Pemasukan',data:masuk,backgroundColor:C[0],borderRadius:6},{label:'Pengeluaran',data:keluar,backgroundColor:C[3],borderRadius:6}]},options:{...opt,scales:{y:{beginAtZero:true,ticks:{callback:v=>'Rp '+Number(v).toLocaleString('id-ID')}}}}});
</script>
<?php foot();
