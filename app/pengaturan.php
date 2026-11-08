<?php require 'config.php'; require 'layout.php'; need(['admin']);
$TEKS = ['nama_situs','brand_sub','hero_badge','hero_judul','hero_lead','hero_btn1','hero_btn2','pengurus_judul','pengurus_sub',
         'ketua_nama','ketua_masa','ketua_sambutan','wakil_nama','wakil_masa','wakil_sambutan','footer_kiri','footer_kanan'];
function simpan($k, $v) { q('INSERT INTO pengaturan (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)', [$k, $v]); }
function gambar($k, $ico = false) {
  $old = cfg_raw($k);
  if (!empty($_POST['hapus_'.$k])) { if ($old && is_file(__DIR__.'/uploads/'.basename($old))) @unlink(__DIR__.'/uploads/'.basename($old)); $old = ''; }
  return $ico ? upload_ico($k, $old) : upload_img($k, $old);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  foreach ($TEKS as $k) simpan($k, trim($_POST[$k] ?? ''));
  simpan('tampil_statistik', isset($_POST['tampil_statistik']) ? '1' : '0');
  simpan('profil_sumber', ($_POST['profil_sumber'] ?? '') === 'manual' ? 'manual' : 'akun');
  foreach (['logo','hero_gambar','ketua_foto','wakil_foto'] as $k) simpan($k, gambar($k));
  simpan('favicon', gambar('favicon', true));
  $fitur = [];
  foreach ((array)($_POST['fj'] ?? []) as $i => $j) {
    $j = trim($j); $d = trim($_POST['fd'][$i] ?? ''); $ic = $_POST['fi'][$i] ?? 'bi-star';
    if ($j !== '') $fitur[] = ['i' => in_array($ic, IKON_FITUR) ? $ic : 'bi-star', 'j' => $j, 'd' => $d];
  }
  simpan('fitur_json', json_encode(array_slice($fitur, 0, 9), JSON_UNESCAPED_UNICODE));
  flash('Pengaturan situs disimpan.'); header('Location: pengaturan.php#'.preg_replace('/[^a-z]/', '', $_POST['tab'] ?? '')); exit;
}
function t($k, $l, $span = 12, $ph = '', $area = false) {
  $v = e(cfg_raw($k)); $ph = e($ph ?: cfg($k));
  echo "<div class='col-md-$span'><label class='form-label'>$l</label>".($area ? "<textarea name='$k' rows='2' class='form-control' placeholder='$ph'>$v</textarea>" : "<input name='$k' class='form-control' value='$v' placeholder='$ph'>")."</div>";
}
function img($k, $l, $hint, $accept = 'image/*') {
  $f = foto(cfg_raw($k));
  echo "<div class='col-md-6'><label class='form-label'>$l</label>";
  if ($f) echo "<div class='d-flex align-items-center gap-2 mb-2'><img src='".e($f)."' style='height:48px;max-width:120px;object-fit:contain;border-radius:8px;background:#f1f5f9'><label class='small'><input type='checkbox' class='form-check-input me-1' name='hapus_$k' value='1'>Hapus</label></div>";
  echo "<input type='file' name='$k' accept='$accept' class='form-control'><div class='form-text'>$hint</div></div>";
}
head('Pengaturan Situs', 'Semua isi halaman depan bisa diubah di sini. Kosongkan kolom teks untuk memakai teks bawaan.'); ?>
<form method="post" enctype="multipart/form-data" id="f"><?= csrf_field() ?><input type="hidden" name="tab" id="tab" value="identitas">
<div class="d-flex flex-wrap gap-2 mb-3 sticky-top py-2 no-print" style="background:var(--bg);z-index:10">
  <ul class="nav nav-pills" role="tablist">
    <?php foreach (['identitas'=>'1. Identitas & logo','hero'=>'2. Bagian utama','pengurus'=>'3. Profil pengurus','fitur'=>'4. Fitur layanan','footer'=>'5. Footer'] as $id => $l): ?>
      <li class="nav-item"><button type="button" class="nav-link <?= $id === 'identitas' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#t-<?= $id ?>" data-tab="<?= $id ?>"><?= $l ?></button></li>
    <?php endforeach; ?>
  </ul>
  <div class="ms-auto d-flex gap-2"><a href="index.php?pratinjau=1" target="_blank" class="btn btn-light"><i class="bi bi-eye me-1"></i>Pratinjau beranda</a><button class="btn btn-primary">Simpan semua</button></div>
</div>
<div class="tab-content">
  <div class="tab-pane fade show active" id="t-identitas"><div class="panel"><div class="row g-3">
    <div class="col-12 small text-secondary">Tampil di pojok kiri atas menu dan halaman depan (gambar pertama Anda).</div>
    <?php t('nama_situs','Nama website',6); t('brand_sub','Teks di bawah nama',6,nama_rt().' · '.cfg('kelurahan'));
          img('logo','Logo RT','JPG/PNG/WEBP, maks. 2 MB. Juga dipakai sebagai logo kiri kop surat.');
          img('favicon','Ikon tab browser (favicon)','Berkas .ico atau .png persegi (mis. 32×32 atau 64×64), maks. 512 KB. Jika kosong, memakai logo RT.','.ico,image/x-icon,image/png'); ?>
  </div></div></div>

  <div class="tab-pane fade" id="t-hero"><div class="panel"><div class="row g-3">
    <div class="col-12 small text-secondary">Bagian paling atas halaman depan (gambar kedua Anda).</div>
    <?php t('hero_badge','Label kecil di atas judul',6,nama_rt()); t('hero_btn1','Teks tombol utama',3); t('hero_btn2','Teks tombol kedua',3);
          t('hero_judul','Judul besar',12,'',true); t('hero_lead','Kalimat penjelas',12,'',true);
          img('hero_gambar','Gambar di samping judul (opsional)','Foto lingkungan, gapura, atau kegiatan warga. Rasio lebar, maks. 2 MB.'); ?>
    <div class="col-md-6 d-flex align-items-end"><label class="form-check"><input type="checkbox" class="form-check-input" name="tampil_statistik" value="1" <?= cfg('tampil_statistik') === '1' ? 'checked' : '' ?>> Tampilkan statistik (jumlah KK, jiwa, blok) di bawah bagian utama</label></div>
  </div></div></div>

  <div class="tab-pane fade" id="t-pengurus"><div class="panel"><div class="row g-3">
    <div class="col-12 small text-secondary">Kartu Ketua dan Wakil Ketua di halaman depan (gambar keempat Anda).</div>
    <?php t('pengurus_judul','Judul bagian',6,'Pengurus '.nama_rt()); t('pengurus_sub','Subjudul',6); ?>
    <div class="col-12"><label class="form-label">Sumber nama dan foto</label>
      <div class="d-flex flex-wrap gap-3">
        <label class="form-check"><input type="radio" class="form-check-input" name="profil_sumber" value="akun" <?= cfg('profil_sumber') !== 'manual' ? 'checked' : '' ?>> Otomatis dari akun berjabatan Ketua/Wakil (disarankan, ikut berganti saat pergantian pengurus)</label>
        <label class="form-check"><input type="radio" class="form-check-input" name="profil_sumber" value="manual" <?= cfg('profil_sumber') === 'manual' ? 'checked' : '' ?>> Isian manual di bawah</label>
      </div><div class="form-text">Masa jabatan dan sambutan selalu diambil dari isian di bawah.</div></div>
    <div class="col-12"><h3 class="h6 fw-bold mt-2 mb-0">Ketua RT</h3></div>
    <?php t('ketua_nama','Nama (mode manual)',6,'Nama Ketua RT'); t('ketua_masa','Masa jabatan',6,'Periode 2024–2027'); t('ketua_sambutan','Sambutan',12,'',true);
          img('ketua_foto','Foto Ketua (mode manual)','Foto persegi, maks. 2 MB.'); ?>
    <div class="col-12"><h3 class="h6 fw-bold mt-2 mb-0">Wakil Ketua RT</h3></div>
    <?php t('wakil_nama','Nama (mode manual)',6,'Nama Wakil Ketua RT'); t('wakil_masa','Masa jabatan',6,'Periode 2024–2027'); t('wakil_sambutan','Pesan singkat',12,'',true);
          img('wakil_foto','Foto Wakil (mode manual)','Foto persegi, maks. 2 MB.'); ?>
  </div></div></div>

  <div class="tab-pane fade" id="t-fitur"><div class="panel">
    <div class="small text-secondary mb-3">Daftar layanan di halaman depan (gambar ketiga Anda). Maksimal 9 item; baris dengan judul kosong diabaikan.</div>
    <div id="fiturBox"></div>
    <button type="button" class="btn btn-light" id="addFitur"><i class="bi bi-plus-lg me-1"></i>Tambah fitur</button>
  </div></div>

  <div class="tab-pane fade" id="t-footer"><div class="panel"><div class="row g-3">
    <?php t('footer_kiri','Teks kiri',6,nama_rt().' · '.lokasi().(cfg('motto') ? ' · '.cfg('motto') : '')); t('footer_kanan','Teks kanan',6); ?>
    <div class="col-12 small text-secondary">Moto RT diubah di menu Profil &amp; Kop Surat.</div>
  </div></div></div>
</div>
</form>
<script>
const IKON=<?= json_encode(IKON_FITUR) ?>, FITUR=<?= json_encode(fitur_list(), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>;
const box=document.getElementById('fiturBox');
function row(f){
  if(box.children.length>=9) return;
  const d=document.createElement('div'); d.className='fitur-row';
  d.innerHTML='<div class="row g-2 align-items-center"><div class="col-auto"><div class="ficon"><i class="bi"></i></div></div><div class="col-md-3"><select name="fi[]" class="form-select form-select-sm"></select></div><div class="col-md"><input name="fj[]" class="form-control form-control-sm" placeholder="Judul"></div><div class="col-auto"><button type="button" class="btn btn-sm text-danger">Hapus</button></div><div class="col-12"><textarea name="fd[]" rows="2" class="form-control form-control-sm" placeholder="Keterangan singkat"></textarea></div></div>';
  const s=d.querySelector('select'), ic=d.querySelector('.ficon i');
  IKON.forEach(k=>{const o=document.createElement('option');o.value=k;o.textContent=k.replace('bi-','');s.appendChild(o);});
  s.value=IKON.includes(f.i)?f.i:IKON[0]; ic.className='bi '+s.value; s.onchange=()=>ic.className='bi '+s.value;
  d.querySelector('input').value=f.j||''; d.querySelector('textarea').value=f.d||'';
  d.querySelector('button').onclick=()=>d.remove(); box.appendChild(d);
}
FITUR.forEach(row);
document.getElementById('addFitur').onclick=()=>row({i:'bi-star',j:'',d:''});
document.querySelectorAll('[data-tab]').forEach(b=>b.addEventListener('shown.bs.tab',()=>{document.getElementById('tab').value=b.dataset.tab;history.replaceState(null,'','#'+b.dataset.tab);}));
const h=location.hash.slice(1); if(h){const b=document.querySelector('[data-tab="'+h+'"]'); if(b) addEventListener('load',()=>bootstrap.Tab.getOrCreateInstance(b).show());}
</script>
<?php foot();
