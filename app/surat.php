<?php require 'config.php'; require 'layout.php'; need(['admin','rt','warga']);
$tpl = q('SELECT id,nama,input_json FROM templat_surat ORDER BY nama')->fetchAll();
$defsByNama = []; foreach ($tpl as $t) $defsByNama[$t['nama']] = json_decode($t['input_json'], true) ?: [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !pengurus()) {
  csrf_check();
  $jenis = $_POST['jenis'] ?? ''; $defs = $defsByNama[$jenis] ?? null;
  if ($defs === null) { flash('Jenis surat tidak dikenal.', 'danger'); header('Location: surat.php'); exit; }
  $in = [];
  foreach ($defs as $f) { $v = trim($_POST['in'][$f['key']] ?? ''); if ($f['wajib'] && $v === '') { flash('Lengkapi kolom: '.$f['label'], 'danger'); header('Location: surat.php'); exit; } $in[$f['key']] = $v; }
  q('INSERT INTO surat (warga_id,jenis,keperluan,data_json,alur) VALUES (?,?,?,?,?)', [wid(), $jenis, trim($_POST['keperluan']), json_encode($in, JSON_UNESCAPED_UNICODE), catat_alur('', 'Diajukan oleh warga')]);
  notif(uid_cap('surat_teruskan'), 'persetujuan', 'Pengajuan surat baru', user()['nama'].': '.$jenis, 'persetujuan.php');
  flash('Permintaan surat dikirim. Prosesnya: diteruskan Sekretaris, diperiksa Wakil Ketua, disetujui Ketua.'); header('Location: surat.php'); exit;
}
head('Surat Pengantar', pengurus() ? 'Pantau seluruh pengajuan. Tindakan dilakukan di menu Persetujuan.' : 'Ajukan surat tanpa perlu datang ke rumah pengurus.'); ?>
<?php if (!pengurus()): ?>
<div class="panel mb-4"><h2 class="h5 mb-3">Ajukan surat</h2>
<form method="post" class="row g-2"><?= csrf_field() ?>
  <div class="col-md-5"><label class="form-label">Jenis surat</label><select name="jenis" id="jenis" class="form-select"><?php foreach ($tpl as $t) echo '<option>'.e($t['nama']).'</option>'; ?></select></div>
  <div class="col-md-7"><label class="form-label">Keperluan</label><input name="keperluan" class="form-control" placeholder="mis. melamar pekerjaan, pindah alamat" required></div>
  <div class="col-12"><div class="row g-2" id="extra"></div></div>
  <div class="col-12"><button class="btn btn-primary">Kirim permintaan</button></div>
</form></div>
<script>
const D=<?= json_encode($defsByNama, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>;
const sel=document.getElementById('jenis'), box=document.getElementById('extra');
function draw(){ box.innerHTML=''; (D[sel.value]||[]).forEach(x=>{
  const c=document.createElement('div'); c.className='col-md-6'; const l=document.createElement('label'); l.className='form-label'; l.textContent=x.label+(x.wajib?' *':'');
  const i=document.createElement(x.type==='textarea'?'textarea':'input'); i.className='form-control'; i.name='in['+x.key+']'; if(x.wajib) i.required=true;
  if(x.type==='date'||x.type==='number') i.type=x.type; c.append(l,i); box.appendChild(c); }); }
sel.onchange=draw; draw();
</script>
<?php else: ?><div class="mb-3"><a href="persetujuan.php" class="btn btn-primary"><i class="bi bi-check2-square me-1"></i>Buka Persetujuan</a></div><?php endif; ?>
<div class="panel table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Tanggal</th><?php if (pengurus()) echo '<th>Pemohon</th>'; ?><th>Jenis &amp; keperluan</th><th>Status</th><th></th></tr></thead><tbody>
<?php $rows = pengurus() ? q('SELECT s.*, w.nama_kk FROM surat s JOIN warga w ON w.id=s.warga_id ORDER BY s.id DESC LIMIT 200') : q('SELECT s.*, "" nama_kk FROM surat s WHERE warga_id=? ORDER BY s.id DESC', [wid()]);
foreach ($rows as $s): ?>
<tr><td><?= tgl($s['created_at']) ?></td><?php if (pengurus()) echo '<td>'.e($s['nama_kk']).'</td>'; ?>
<td><strong><?= e($s['jenis']) ?></strong><div class="small text-secondary"><?= e($s['keperluan']) ?></div><?php if ($s['no_surat']) echo '<div class="small">No. '.e($s['no_surat']).'</div>'; if ($s['catatan']) echo '<div class="small">Catatan: '.e($s['catatan']).'</div>'; ?><div class="alur"><?= e($s['alur']) ?></div></td>
<td><?= badge($s['status']) ?></td>
<td><?php if ($s['status']==='disetujui' && q("SELECT COUNT(*) FROM surat_terbit WHERE surat_id=? AND status='terbit'", [$s['id']])->fetchColumn()): ?>
  <a class="btn btn-sm btn-light" target="_blank" href="cetak.php?surat=<?= $s['id'] ?>"><i class="bi bi-printer me-1"></i>Cetak</a><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php foot();
