<?php require 'config.php'; require 'layout.php'; need(['admin','rt']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); izin('surat_buat'); q('DELETE FROM surat_terbit WHERE id=?', [$_POST['hapus']]); flash('Surat dihapus dari arsip. QR-nya tidak lagi valid.'); header('Location: arsip.php'); exit; }
head('Arsip Surat', 'Semua surat beserta status persetujuan dan tautan verifikasinya.'); ?>
<div class="panel table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Nomor</th><th>Jenis</th><th>Pemohon</th><th>Status</th><th>Dibuat</th><th></th></tr></thead><tbody>
<?php foreach (q('SELECT s.*, w.nama_kk FROM surat_terbit s LEFT JOIN warga w ON w.id=s.warga_id ORDER BY s.id DESC LIMIT 300') as $s): ?>
<tr><td class="fw-semibold"><?= e($s['nomor']) ?></td><td><?= e($s['judul']) ?></td><td><?= e($s['nama_kk'] ?: '-') ?></td><td><?= badge($s['status']) ?><div class="small text-secondary"><?= $s['ttd_qr'] ? 'TTD QR' : 'TTD basah' ?></div></td><td><?= tgl($s['created_at']) ?></td>
<td class="text-nowrap"><a class="btn btn-sm btn-light" target="_blank" href="cetak.php?id=<?= $s['id'] ?>"><i class="bi bi-printer me-1"></i>Cetak</a>
<?php if ($s['status'] === 'terbit'): ?><a class="btn btn-sm btn-light" target="_blank" href="verifikasi.php?t=<?= e($s['token']) ?>"><i class="bi bi-patch-check me-1"></i>Verifikasi</a><?php endif; ?>
<?php if (bisa('surat_buat')): ?><form method="post" class="d-inline" onsubmit="return confirm('Hapus surat ini dari arsip?')"><?= csrf_field() ?><button name="hapus" value="<?= $s['id'] ?>" class="btn btn-sm btn-light text-danger">Hapus</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php foot();
