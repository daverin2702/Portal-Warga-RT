<?php require 'config.php'; require 'layout.php'; need(['admin','rt']); izin('surat_buat');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $isi = $_POST['isi'] ?? '';
  if (trim(strip_tags($isi)) === '') { flash('Isi surat masih kosong.', 'danger'); header('Location: buat_surat.php'); exit; }
  $t = ($_POST['mode'] ?? '') === 'templat' ? q('SELECT * FROM templat_surat WHERE id=?', [$_POST['templat_id'] ?? 0])->fetch() : null;
  $w = q('SELECT * FROM warga WHERE id=?', [$_POST['warga_id'] ?? 0])->fetch() ?: [];
  $nomor = trim($_POST['nomor'] ?? '') ?: nomor_berikut();
  $defs = $t ? (json_decode($t['input_json'], true) ?: []) : [];
  $extra = olah_input($defs, $_POST['in'] ?? []) + ['nomor_surat' => $nomor, 'keperluan' => trim($_POST['keperluan'] ?? '')];
  $isi = bersih_html(render_vars($isi, data_surat($w, $extra)));
  $judul = $t['nama'] ?? (trim($_POST['judul'] ?? '') ?: 'Surat');
  q('INSERT INTO surat_terbit (templat_id,warga_id,nomor,judul,isi,pakai_kop,token,penandatangan,ttd_qr,keperluan,status,alur) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
    [$t['id'] ?? null, $w['id'] ?? null, $nomor, $judul, $isi, isset($_POST['pakai_kop']) ? 1 : 0, bin2hex(random_bytes(12)), cfg('ketua_nama'), ($_POST['ttd'] ?? 'qr') === 'qr' ? 1 : 0, trim($_POST['keperluan'] ?? '') ?: null, 'draf', catat_alur('', 'Draf dibuat oleh '.user()['nama'].' (Sekretaris)')]);
  notif(uid_cap('surat_periksa'), 'persetujuan', 'Draf surat perlu diperiksa', $judul.' - '.$nomor, 'persetujuan.php');
  flash('Draf tersimpan. Surat menunggu pemeriksaan Wakil Ketua dan persetujuan Ketua sebelum QR tanda tangan berlaku.');
  header('Location: cetak.php?id='.$GLOBALS['pdo']->lastInsertId()); exit;
}
$tpl = []; foreach (q('SELECT * FROM templat_surat ORDER BY nama') as $t) $tpl[$t['id']] = ['nama'=>$t['nama'], 'isi'=>$t['isi'], 'inputs'=>json_decode($t['input_json'], true) ?: []];
$wg = []; foreach (q('SELECT * FROM warga ORDER BY nama_kk') as $w) $wg[$w['id']] = data_surat($w) + ['id'=>$w['id']];
$J = fn($x) => json_encode($x, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE);
head('Buat Surat', 'Pilih templat atau tulis manual seperti di Word, lalu terbitkan dengan QR tanda tangan.'); ?>
<form method="post" id="f"><?= csrf_field() ?><input type="hidden" name="isi" id="isi">
<div class="row g-3">
  <div class="col-xl-4">
    <div class="panel mb-3">
      <div class="btn-group w-100 mb-3" role="group">
        <input type="radio" class="btn-check" name="mode" id="m1" value="templat" checked><label class="btn btn-outline-primary" for="m1">Dari templat</label>
        <input type="radio" class="btn-check" name="mode" id="m2" value="manual"><label class="btn btn-outline-primary" for="m2">Surat manual</label>
      </div>
      <div id="boxTpl" class="mb-2"><label class="form-label">Templat</label><select name="templat_id" id="tpl" class="form-select"><?php foreach ($tpl as $id => $t) echo "<option value='$id'>".e($t['nama'])."</option>"; ?></select></div>
      <div id="boxJudul" class="mb-2 d-none"><label class="form-label">Judul surat (untuk arsip)</label><input name="judul" class="form-control" placeholder="mis. Surat Undangan Rapat"></div>
      <div class="mb-2"><label class="form-label">Warga pemohon</label><select name="warga_id" id="wg" class="form-select"><option value="">- tanpa data warga -</option><?php foreach ($wg as $id => $w) echo "<option value='$id'>".e($w['nama'])." - ".e($w['alamat'])."</option>"; ?></select></div>
      <div class="mb-2"><label class="form-label">Nomor surat</label><input name="nomor" id="nomor" class="form-control" value="<?= e(nomor_berikut()) ?>"></div>
      <div class="mb-2"><label class="form-label">Keperluan</label><input name="keperluan" id="kep" class="form-control" placeholder="mis. melamar pekerjaan"></div>
      <div id="extra"></div>
      <div class="mt-3"><label class="form-label">Tanda tangan Ketua</label>
        <div class="btn-group w-100" role="group">
          <input type="radio" class="btn-check" name="ttd" id="ttdQr" value="qr" checked><label class="btn btn-outline-primary btn-sm" for="ttdQr"><i class="bi bi-qr-code me-1"></i>Digital (QR)</label>
          <input type="radio" class="btn-check" name="ttd" id="ttdBasah" value="basah"><label class="btn btn-outline-primary btn-sm" for="ttdBasah"><i class="bi bi-pen me-1"></i>Basah (tanpa QR)</label>
        </div><div class="form-text">Ketua masih bisa mengubah pilihan ini saat menandatangani. Surat tanpa QR tetap bisa dicek lewat nomor dan tanggal surat.</div></div>
      <div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="pakai_kop" id="pk" checked><label class="form-check-label" for="pk">Pakai kop surat</label></div>
    </div>
    <div class="d-grid gap-2"><button type="button" class="btn btn-light" id="apply"><i class="bi bi-magic me-1"></i>Isi surat dari templat</button>
      <button class="btn btn-primary"><i class="bi bi-qr-code me-1"></i>Simpan sebagai draf &amp; lihat</button></div>
    <div class="small text-secondary mt-3">Tanda tangan persetujuan berupa kode QR muncul di tempat tulisan <code>{{qr_ttd}}</code>. Pada surat manual, sisipkan lewat tombol di bawah.</div>
    <button type="button" class="chip mt-2" data-ins="{{qr_ttd}}">Sisipkan QR tanda tangan</button>
    <button type="button" class="chip" data-ins="{{nama}}">nama</button><button type="button" class="chip" data-ins="{{alamat}}">alamat</button><button type="button" class="chip" data-ins="{{tanggal_surat}}">tanggal_surat</button>
  </div>
  <div class="col-xl-8"><div class="panel p-0"><?php toolbar(); ?>
    <div class="a4-stage"><div class="a4"><div id="kop"><?= kop_html() ?></div><div id="editor" contenteditable="true" spellcheck="false"><p><br></p></div><div id="foot"><?= footer_html() ?></div></div></div>
  </div></div>
</div></form>
<script src="assets/editor.js"></script>
<script>
const TPL=<?= $J($tpl) ?>, WG=<?= $J($wg) ?>, BASE=<?= $J(data_surat([])) ?>;
const $=id=>document.getElementById(id), ed=$('editor');
const BLN=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
const fmt=v=>{const m=/^(\d{4})-(\d{2})-(\d{2})$/.exec(v);return m?(+m[3])+' '+BLN[+m[2]-1]+' '+m[1]:v;};
const esc=s=>String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
function drawExtra(){
  const box=$('extra'); box.innerHTML=''; if($('m2').checked) return;
  (TPL[$('tpl').value]?.inputs||[]).forEach(x=>{
    const d=document.createElement('div'); d.className='mb-2';
    const l=document.createElement('label'); l.className='form-label'; l.textContent=x.label+(x.wajib?' *':'');
    const i=document.createElement(x.type==='textarea'?'textarea':'input'); i.className='form-control'; i.name='in['+x.key+']'; i.dataset.key=x.key; i.dataset.type=x.type;
    if(x.type==='date') i.type='date'; else if(x.type==='number') i.type='number'; else if(x.type!=='textarea') i.type='text';
    d.append(l,i); box.appendChild(d);
  });
}
function apply(){
  const t=TPL[$('tpl').value]; if(!t) return;
  const d=Object.assign({}, BASE, WG[$('wg').value]||{}, {nomor_surat:$('nomor').value, keperluan:$('kep').value});
  document.querySelectorAll('#extra [data-key]').forEach(i=>d[i.dataset.key]=i.dataset.type==='date'?fmt(i.value):i.value);
  ed.innerHTML=t.isi.replace(/\{\{\s*([a-z0-9_]+)\s*\}\}/gi,(m,k)=>{k=k.toLowerCase();return(k!=='qr_ttd'&&d[k])?esc(d[k]):m;});
}
function mode(){const m=$('m2').checked;$('boxTpl').classList.toggle('d-none',m);$('boxJudul').classList.toggle('d-none',!m);$('apply').classList.toggle('d-none',m);drawExtra();}
document.querySelectorAll('[name=mode]').forEach(r=>r.onchange=mode);
$('tpl').onchange=()=>{drawExtra();apply();};
$('apply').onclick=apply; $('pk').onchange=()=>{const v=$('pk').checked?'':'none';$('kop').style.display=v;$('foot').style.display=v;};
$('f').addEventListener('submit',()=>$('isi').value=ed.innerHTML);
drawExtra(); if(Object.keys(TPL).length) apply();
</script>
<?php foot();
