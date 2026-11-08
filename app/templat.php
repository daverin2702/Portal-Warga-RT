<?php require 'config.php'; require 'layout.php'; need(['admin','rt']); izin('templat');
const RESERVED = ['nama','nik','no_kk','tempat_lahir','tgl_lahir','jenis_kelamin','agama','pekerjaan','alamat','blok','no_rumah','status_hunian','telp','rt','rw','kelurahan','kecamatan','kota','nama_ketua','tanggal_surat','nomor_surat','keperluan','qr_ttd'];
function defs_ok($j) {
  $a = json_decode($j, true); if (!is_array($a)) return []; $o = []; $seen = [];
  foreach ($a as $f) {
    $k = preg_replace('/[^a-z0-9_]/', '', strtolower($f['key'] ?? '')); $l = trim($f['label'] ?? '');
    if (!$k || !$l || in_array($k, RESERVED) || isset($seen[$k])) continue; $seen[$k] = 1;
    $o[] = ['key'=>$k, 'label'=>$l, 'type'=>in_array($f['type'] ?? '', ['text','textarea','date','number']) ? $f['type'] : 'text', 'wajib'=>!empty($f['wajib'])];
  }
  return $o;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  if (isset($_POST['hapus'])) { q('DELETE FROM templat_surat WHERE id=?', [$_POST['hapus']]); flash('Templat dihapus. Arsip surat yang sudah terbit tetap aman.'); }
  elseif (isset($_POST['duplikat'])) {
    $t = q('SELECT * FROM templat_surat WHERE id=?', [$_POST['duplikat']])->fetch();
    if ($t) { try { q('INSERT INTO templat_surat (nama,isi,input_json) VALUES (?,?,?)', [$t['nama'].' (salinan)', $t['isi'], $t['input_json']]); flash('Templat diduplikasi.'); } catch (PDOException $x) { flash('Salinan sudah ada, ganti namanya dulu.', 'warning'); } }
  } else {
    $nama = trim($_POST['nama'] ?? ''); $isi = bersih_html($_POST['isi'] ?? '');
    $inp = json_encode(defs_ok($_POST['input_json'] ?? '[]'), JSON_UNESCAPED_UNICODE);
    if ($nama === '' || trim(strip_tags($isi)) === '') { flash('Nama dan isi templat wajib diisi.', 'danger'); header('Location: '.$_SERVER['REQUEST_URI']); exit; }
    try {
      if (!empty($_POST['id'])) { q('UPDATE templat_surat SET nama=?, isi=?, input_json=? WHERE id=?', [$nama, $isi, $inp, $_POST['id']]); flash('Templat diperbarui.'); }
      else { q('INSERT INTO templat_surat (nama,isi,input_json) VALUES (?,?,?)', [$nama, $isi, $inp]); flash('Templat dibuat.'); }
    } catch (PDOException $x) { flash('Nama templat sudah dipakai.', 'danger'); header('Location: '.$_SERVER['REQUEST_URI']); exit; }
  }
  header('Location: templat.php'); exit;
}
$edit = isset($_GET['baru']) || isset($_GET['id']);
if (!$edit) { head('Templat Surat', 'Susun templat sekali, pakai berulang untuk warga mana pun.'); ?>
  <div class="mb-3"><a href="?baru=1" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Templat baru</a></div>
  <div class="panel table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Nama templat</th><th>Input tambahan</th><th></th></tr></thead><tbody>
  <?php foreach (q('SELECT * FROM templat_surat ORDER BY nama') as $t): $d = json_decode($t['input_json'], true) ?: []; ?>
    <tr><td class="fw-semibold"><?= e($t['nama']) ?></td><td class="text-secondary"><?= $d ? e(implode(', ', array_column($d, 'label'))) : '-' ?></td>
    <td class="text-nowrap"><a class="btn btn-sm btn-light" href="?id=<?= $t['id'] ?>">Edit</a>
      <form method="post" class="d-inline"><?= csrf_field() ?><button name="duplikat" value="<?= $t['id'] ?>" class="btn btn-sm btn-light">Duplikat</button></form>
      <form method="post" class="d-inline" onsubmit="return confirm('Hapus templat ini?')"><?= csrf_field() ?><button name="hapus" value="<?= $t['id'] ?>" class="btn btn-sm btn-light text-danger">Hapus</button></form></td></tr>
  <?php endforeach; ?></tbody></table></div>
<?php foot(); exit; }

$t = isset($_GET['id']) ? q('SELECT * FROM templat_surat WHERE id=?', [$_GET['id']])->fetch() : null;
$isi = $t['isi'] ?? '<h3 style="text-align:center;margin:0"><u>JUDUL SURAT</u></h3><p style="text-align:center;margin:0 0 12px">Nomor: {{nomor_surat}}</p><p>Yang bertanda tangan di bawah ini, Ketua RT {{rt}} RW {{rw}}, menerangkan bahwa <b>{{nama}}</b> ...</p><p>Demikian surat ini dibuat untuk dipergunakan sebagaimana mestinya.</p><div style="width:48%;margin-left:52%;text-align:center"><p style="margin:12px 0 0">{{kota}}, {{tanggal_surat}}<br>Ketua RT {{rt}} / RW {{rw}}</p><p style="margin:6px 0">{{qr_ttd}}</p><p style="margin:0"><b><u>{{nama_ketua}}</u></b></p></div>';
$defs = json_decode($t['input_json'] ?? '[]', true) ?: [];
$grupA = ['nama','nik','no_kk','tempat_lahir','tgl_lahir','jenis_kelamin','agama','pekerjaan','alamat','blok','no_rumah','status_hunian','telp'];
$grupB = ['nomor_surat','tanggal_surat','keperluan','rt','rw','kelurahan','kecamatan','kota','nama_ketua','qr_ttd'];
head($t ? 'Edit templat' : 'Templat baru', 'Tulis langsung di kertas A4. Variabel {{...}} akan terisi otomatis saat surat dibuat.'); ?>
<form method="post" id="f"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($t['id'] ?? '') ?>"><input type="hidden" name="isi" id="isi"><input type="hidden" name="input_json" id="inputJson">
<div class="row g-3">
  <div class="col-xl-4">
    <div class="panel mb-3"><label class="form-label">Nama surat *</label><input name="nama" class="form-control" value="<?= e($t['nama'] ?? '') ?>" required placeholder="mis. Surat Keterangan Domisili"></div>
    <div class="panel mb-3">
      <div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h6 fw-bold mb-0">Input tambahan <span class="text-secondary fw-normal">(diisi saat pengajuan)</span></h2>
        <button type="button" class="btn btn-sm btn-primary" id="addInput">+ Tambah</button></div>
      <div id="inputs"></div>
    </div>
    <div class="panel mb-3"><h2 class="h6 fw-bold">Variabel tersedia</h2><div class="small text-secondary mb-2">Klik untuk menyisipkan di posisi kursor.</div>
      <div class="small fw-semibold mb-1">Data warga</div><div><?php foreach ($grupA as $v) echo "<button type='button' class='chip' data-ins='{{".$v."}}'>$v</button>"; ?></div>
      <div class="small fw-semibold mb-1 mt-2">Surat &amp; wilayah</div><div><?php foreach ($grupB as $v) echo "<button type='button' class='chip' data-ins='{{".$v."}}'>$v</button>"; ?></div>
      <div class="small fw-semibold mb-1 mt-2">Input tambahan</div><div id="chipsExtra"><span class="text-secondary small">Belum ada.</span></div>
      <div class="alert alert-light border small mt-3 mb-0"><code>{{qr_ttd}}</code> menjadi kode QR tanda tangan persetujuan saat surat dicetak. Siapa pun bisa memindainya untuk memverifikasi keaslian surat.</div>
    </div>
    <button class="btn btn-primary">Simpan templat</button> <a href="templat.php" class="btn btn-link">Batal</a>
  </div>
  <div class="col-xl-8"><div class="panel p-0"><?php toolbar(); ?>
    <div class="a4-stage"><div class="a4"><?= kop_html() ?><div id="editor" contenteditable="true" spellcheck="false"><?= $isi ?></div><?= footer_html() ?></div></div>
  </div></div>
</div></form>
<script src="assets/editor.js"></script>
<script>
let inputs = <?= json_encode($defs, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>;
const slug = s => s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,'');
const box = document.getElementById('inputs'), chips = document.getElementById('chipsExtra');
function chipsRefresh(){
  chips.innerHTML = '';
  inputs.filter(x=>x.key).forEach(x=>{ const b=document.createElement('button'); b.type='button'; b.className='chip'; b.dataset.ins='{{'+x.key+'}}'; b.textContent=x.key; chips.appendChild(b); });
  if(!chips.children.length) chips.innerHTML='<span class="text-secondary small">Belum ada.</span>';
}
function draw(){
  box.innerHTML='';
  inputs.forEach((x,i)=>{
    const d=document.createElement('div'); d.className='border rounded-3 p-2 mb-2';
    d.innerHTML='<div class="d-flex gap-2 align-items-center"><input class="form-control form-control-sm" placeholder="Label, mis. Nama Usaha"><select class="form-select form-select-sm" style="width:110px"><option value="text">Teks</option><option value="textarea">Teks panjang</option><option value="date">Tanggal</option><option value="number">Angka</option></select></div><div class="d-flex justify-content-between align-items-center mt-2"><label class="small mb-0"><input type="checkbox" class="form-check-input me-1">Wajib</label><button type="button" class="btn btn-sm text-danger p-0">Hapus</button></div><div class="small text-secondary mt-1">Variabel: <code class="v"></code></div>';
    const [lab,sel]=d.querySelectorAll('input.form-control,select'), cb=d.querySelector('input[type=checkbox]'), v=d.querySelector('.v');
    lab.value=x.label||''; sel.value=x.type||'text'; cb.checked=!!x.wajib; v.textContent=x.key?'{{'+x.key+'}}':'(isi label dulu)';
    lab.oninput=()=>{x.label=lab.value;x.key=slug(lab.value);v.textContent=x.key?'{{'+x.key+'}}':'(isi label dulu)';chipsRefresh();};
    sel.onchange=()=>x.type=sel.value; cb.onchange=()=>x.wajib=cb.checked;
    d.querySelector('button').onclick=()=>{inputs.splice(i,1);draw();chipsRefresh();};
    box.appendChild(d);
  });
}
document.getElementById('addInput').onclick=()=>{inputs.push({key:'',label:'',type:'text',wajib:true});draw();};
document.getElementById('f').addEventListener('submit',()=>{
  document.getElementById('isi').value=document.getElementById('editor').innerHTML;
  document.getElementById('inputJson').value=JSON.stringify(inputs.filter(x=>x.key));
});
draw(); chipsRefresh();
</script>
<?php foot();
