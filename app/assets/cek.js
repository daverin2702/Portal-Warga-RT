/* Pop-up cek keaslian surat di halaman depan: pindai QR (kamera/foto) atau input nomor + tanggal. */
(function () {
  const $ = id => document.getElementById(id);
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const pop = $('cekPop'), hasil = $('cekHasil');
  let scanner = null, jalan = false, sibuk = false;
  // Cadangan tanpa pustaka: pembaca QR bawaan browser (Chrome/Edge Android, sebagian desktop).
  let native = false, aliran = null, putar = null, detektor = null;
  const adaNative = 'BarcodeDetector' in window;
  function detektorNative() { return detektor = detektor || new BarcodeDetector({ formats: ['qr_code'] }); }
  async function mulaiNative() {
    detektorNative();
    aliran = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
    const v = document.createElement('video'); v.setAttribute('playsinline', ''); v.muted = true; v.srcObject = aliran; v.style.width = '100%';
    $('cekReader').innerHTML = ''; $('cekReader').appendChild(v); await v.play();
    native = true; jalan = true; $('cekMulai').innerHTML = '<i class="bi bi-stop-circle me-1"></i>Matikan kamera';
    const ulang = async () => {
      if (!jalan) return;
      try { const r = await detektor.detect(v); if (r.length) { const t = r[0].rawValue; await berhenti(); cek({ kode: t }); return; } } catch (e) {}
      putar = setTimeout(ulang, 250);
    };
    ulang();
  }

  function muatPustaka() {
    if (window.Html5Qrcode) return Promise.resolve();
    return new Promise((ok, gagal) => { const s = document.createElement('script'); s.src = window.ASET_QR || 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js'; s.onload = ok; s.onerror = () => gagal(new Error('Pustaka pemindai gagal dimuat. Periksa koneksi internet.')); document.head.appendChild(s); });
  }
  async function berhenti() {
    if (native) { clearTimeout(putar); if (aliran) aliran.getTracks().forEach(t => t.stop()); aliran = null; native = false; }
    else if (scanner && jalan) { try { await scanner.stop(); } catch (e) {} }
    jalan = false; $('cekMulai').innerHTML = '<i class="bi bi-camera-video me-1"></i>Nyalakan kamera'; }

  function tampil(d) {
    if (d.error) { hasil.innerHTML = `<div class="alert alert-warning small mb-0">${esc(d.error)}</div>`; return; }
    if (!d.valid) { hasil.innerHTML = `<div class="cek-res gagal"><i class="bi bi-x-octagon-fill"></i><div><strong>Tidak terverifikasi</strong><div class="small">${esc(d.pesan)}</div></div></div>`; return; }
    const x = d.data;
    hasil.innerHTML = `<div class="cek-res ok"><i class="bi bi-patch-check-fill"></i><div><strong>Surat asli dan berlaku</strong><div class="small">Sebagian data disamarkan untuk melindungi privasi.</div></div></div>
      <table class="table table-sm small mt-2 mb-0"><tbody>
      <tr><th>Nomor</th><td>${esc(x.nomor)}</td></tr><tr><th>Jenis</th><td>${esc(x.jenis)}</td></tr><tr><th>Tanggal</th><td>${esc(x.tanggal)}</td></tr>
      <tr><th>Pemohon</th><td>${esc(x.pemohon)}</td></tr><tr><th>Tujuan</th><td>${esc(x.tujuan)}</td></tr>
      <tr><th>Penandatangan</th><td>${esc(x.penandatangan)}</td></tr><tr><th>Tanda tangan</th><td>${esc(x.ttd)}</td></tr></tbody></table>`;
  }
  async function cek(body) {
    if (sibuk) return; sibuk = true;
    hasil.innerHTML = '<div class="small text-secondary">Memeriksa...</div>';
    try { const r = await fetch('cek.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) }); tampil(await r.json()); }
    catch (e) { tampil({ error: 'Gagal menghubungi server.' }); }
    finally { sibuk = false; }
  }

  $('cekFab').onclick = () => { pop.classList.toggle('d-none'); if (pop.classList.contains('d-none')) berhenti(); };
  $('cekClose').onclick = () => { pop.classList.add('d-none'); berhenti(); };
  document.querySelectorAll('[data-cek]').forEach(b => b.onclick = () => {
    document.querySelectorAll('[data-cek]').forEach(x => x.classList.toggle('active', x === b));
    const scan = b.dataset.cek === 'scan';
    $('cekScan').classList.toggle('d-none', !scan); $('cekManual').classList.toggle('d-none', scan);
    hasil.innerHTML = ''; if (!scan) berhenti();
  });

  $('cekMulai').onclick = async () => {
    if (jalan) return berhenti();
    hasil.innerHTML = '';
    let pustaka = true;
    try { await muatPustaka(); } catch (e) { pustaka = false; }
    if (!pustaka) {
      if (!adaNative) { hasil.innerHTML = '<div class="alert alert-warning small mb-0">Pemindai belum tersedia tanpa internet di browser ini. Gunakan tab Input manual.</div>'; return; }
      try { await mulaiNative(); } catch (e) { hasil.innerHTML = `<div class="alert alert-warning small mb-0">Kamera tidak bisa dibuka (${esc(e.message || e)}). Izinkan akses kamera, buka lewat https/localhost, atau gunakan input manual.</div>`; }
      return;
    }
    try {
      $('cekReader').innerHTML = '';
      scanner = scanner || new Html5Qrcode('cekReader');
      await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 220, height: 220 } }, async teks => { await berhenti(); cek({ kode: teks }); });
      jalan = true; $('cekMulai').innerHTML = '<i class="bi bi-stop-circle me-1"></i>Matikan kamera';
    } catch (e) {
      hasil.innerHTML = `<div class="alert alert-warning small mb-0">Kamera tidak bisa dibuka (${esc(e.message || e)}). Izinkan akses kamera, buka lewat https, atau gunakan foto/input manual.</div>`;
    }
  };
  $('cekFoto').onchange = async e => {
    const f = e.target.files[0]; if (!f) return;
    try {
      await berhenti();
      let teks = null;
      try { await muatPustaka(); scanner = scanner || new Html5Qrcode('cekReader'); teks = await scanner.scanFile(f, false); }
      catch (e) { if (!adaNative) throw e; const r = await detektorNative().detect(await createImageBitmap(f)); if (!r.length) throw e; teks = r[0].rawValue; }
      cek({ kode: teks });
    }
    catch (err) { hasil.innerHTML = '<div class="alert alert-warning small mb-0">Kode QR tidak terbaca dari foto. Coba foto yang lebih jelas dan tegak lurus.</div>'; }
    e.target.value = '';
  };
  $('cekManual').onsubmit = e => { e.preventDefault(); cek({ nomor: $('cekNomor').value, tanggal: $('cekTgl').value }); };
})();
