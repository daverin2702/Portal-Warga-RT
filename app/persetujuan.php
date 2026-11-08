<?php require 'config.php'; require 'layout.php'; need(['admin','rt']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  [$ok, $msg] = aksi_persetujuan($_POST['tipe'] ?? '', (int)($_POST['id'] ?? 0), $_POST['aksi'] ?? '', trim($_POST['catatan'] ?? ''), isset($_POST['ttd']) ? ['ttd_qr' => $_POST['ttd'] === 'qr' ? 1 : 0] : []);
  flash($msg, $ok ? 'success' : 'danger'); header('Location: persetujuan.php'); exit;
}
$A = [
  'surat' => ['diajukan'=>['teruskan','surat_teruskan','Teruskan ke Wakil'], 'diteruskan'=>['periksa','surat_periksa','Sudah diperiksa'], 'diperiksa'=>['setujui','surat_setujui','Setujui & tandatangani']],
  'draf'  => ['draf'=>['periksa','surat_periksa','Sudah diperiksa'], 'diperiksa'=>['setujui','surat_setujui','Setujui & tandatangani']],
  'kas'   => ['diajukan'=>['periksa','kas_periksa','Sudah diperiksa'], 'diperiksa'=>['setujui','kas_setujui','Setujui pengeluaran']],
];
function tombol($A, $tipe, $id, $status, $ttd = 1) {
  $a = $A[$tipe][$status] ?? null; if (!$a || !bisa($a[1])) return '<span class="small text-secondary">Menunggu pejabat berwenang</span>';
  $pilih = ($a[0] === 'setujui' && $tipe !== 'kas') ? '<select name="ttd" class="form-select form-select-sm" style="max-width:190px" title="Jenis tanda tangan"><option value="qr"'.($ttd ? ' selected' : '').'>TTD digital (QR)</option><option value="basah"'.($ttd ? '' : ' selected').'>TTD basah (tanpa QR)</option></select>' : '';
  return '<form method="post" class="d-flex gap-1 flex-wrap">'.csrf_field().'<input type="hidden" name="tipe" value="'.$tipe.'"><input type="hidden" name="id" value="'.$id.'">'
    .$pilih.'<input name="catatan" class="form-control form-control-sm" style="max-width:200px" placeholder="Catatan (opsional)">'
    .'<button name="aksi" value="'.$a[0].'" class="btn btn-sm btn-primary">'.e($a[2]).'</button>'
    .'<button name="aksi" value="tolak" class="btn btn-sm btn-light text-danger" onclick="return confirm(\'Tolak permintaan ini?\')">Tolak</button></form>';
}
head('Persetujuan', 'Alur surat: Sekretaris membuat/meneruskan, Wakil Ketua memeriksa (checker), Ketua menandatangani (signer). Pengeluaran kas: Bendahara memposting, Wakil memeriksa, Ketua menyetujui.'); ?>
<div class="panel mb-4 table-responsive"><h2 class="h5 mb-3">Pengajuan surat warga</h2><table class="table align-middle mb-0"><thead><tr><th>Pemohon</th><th>Surat</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>
<?php $n = 0; foreach (q("SELECT s.*, w.nama_kk FROM surat s JOIN warga w ON w.id=s.warga_id WHERE s.status IN ('diajukan','diteruskan','diperiksa') ORDER BY s.id") as $s): $n++; ?>
<tr><td><strong><?= e($s['nama_kk']) ?></strong><div class="small text-secondary"><?= tgl($s['created_at']) ?></div></td>
<td><?= e($s['jenis']) ?><div class="small text-secondary"><?= e($s['keperluan']) ?></div><div class="alur"><?= e($s['alur']) ?></div></td>
<td><?= badge($s['status']) ?></td><td><?= tombol($A, 'surat', $s['id'], $s['status']) ?></td></tr>
<?php endforeach; if (!$n) echo '<tr><td colspan="4" class="text-secondary">Tidak ada pengajuan yang sedang berjalan.</td></tr>'; ?></tbody></table></div>

<div class="panel mb-4 table-responsive"><h2 class="h5 mb-3">Draf surat manual</h2><table class="table align-middle mb-0"><thead><tr><th>Nomor</th><th>Surat</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>
<?php $n = 0; foreach (q("SELECT st.*, w.nama_kk FROM surat_terbit st LEFT JOIN warga w ON w.id=st.warga_id WHERE st.status IN ('draf','diperiksa') ORDER BY st.id") as $s): $n++; ?>
<tr><td><?= e($s['nomor']) ?></td><td><?= e($s['judul']) ?><div class="small text-secondary"><?= e($s['nama_kk'] ?: '-') ?> &middot; <?= $s['ttd_qr'] ? 'TTD QR' : 'TTD basah' ?> &middot; <a target="_blank" href="cetak.php?id=<?= $s['id'] ?>">lihat draf</a></div><div class="alur"><?= e($s['alur']) ?></div></td>
<td><?= badge($s['status']) ?></td><td><?= tombol($A, 'draf', $s['id'], $s['status'], (int)$s['ttd_qr']) ?></td></tr>
<?php endforeach; if (!$n) echo '<tr><td colspan="4" class="text-secondary">Tidak ada draf surat.</td></tr>'; ?></tbody></table></div>

<div class="panel table-responsive"><h2 class="h5 mb-3">Pengeluaran kas</h2><table class="table align-middle mb-0"><thead><tr><th>Tanggal</th><th>Keterangan</th><th>Nominal</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>
<?php $n = 0; foreach (q("SELECT * FROM kas WHERE status IN ('diajukan','diperiksa') ORDER BY id") as $k): $n++; ?>
<tr><td><?= tgl($k['tanggal']) ?></td><td><?= e($k['keterangan'] ?: $k['kategori']) ?><div class="small text-secondary"><?= e($k['alokasi']) ?></div><div class="alur"><?= e($k['alur']) ?></div></td>
<td class="text-danger"><?= rp($k['nominal']) ?></td><td><?= badge($k['status']) ?></td><td><?= tombol($A, 'kas', $k['id'], $k['status']) ?></td></tr>
<?php endforeach; if (!$n) echo '<tr><td colspan="5" class="text-secondary">Tidak ada pengeluaran yang menunggu.</td></tr>'; ?></tbody></table></div>
<?php foot();
