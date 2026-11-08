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
  async function mulaiNative(id, kams) {
    detektorNative();
    aliran = await navigator.mediaDevices.getUserMedia({ video: id ? { deviceId: { exact: id } } : { facingMode: { ideal: 'environment' } } });
    if (kams) { const lengkap = (await navigator.mediaDevices.enumerateDevices()).filter(d => d.kind === 'videoinput'); const aktif = aliran.getVideoTracks()[0].getSettings().deviceId; isiPilihan(lengkap, aktif); }
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

  // ---------- kamera ----------
  function pesanKamera(e) {
    const n = (e && (e.name || '')) + ' ' + String(e && e.message || e || '');
    if (/NotFound|DevicesNotFound|OverconstrainedError|Requested device not found/i.test(n)) return 'Tidak ada kamera yang terdeteksi di perangkat ini. Pakai HP untuk memindai, pilih foto kode QR, atau isi nomor surat secara manual.';
    if (/NotAllowed|Permission|denied/i.test(n)) return 'Izin kamera ditolak. Klik ikon gembok di sebelah alamat situs, izinkan Kamera, lalu coba lagi.';
    if (/NotReadable|TrackStart|Could not start/i.test(n)) return 'Kamera sedang dipakai aplikasi lain (misalnya Zoom atau WhatsApp). Tutup aplikasi itu, lalu coba lagi.';
    if (/Secure|https/i.test(n)) return 'Kamera hanya bisa dipakai jika website dibuka lewat https atau localhost.';
    return 'Kamera tidak bisa dibuka (' + String(e && e.message || e) + ').';
  }
  function tampilGagalKamera(e) {
    hasil.innerHTML = `<div class="alert alert-warning small mb-0">${esc(pesanKamera(e))}
      <div class="d-flex flex-wrap gap-2 mt-2"><button type="button" class="btn btn-sm btn-light" data-aksi="foto"><i class="bi bi-image me-1"></i>Pilih foto QR</button>
      <button type="button" class="btn btn-sm btn-light" data-aksi="manual"><i class="bi bi-keyboard me-1"></i>Input manual</button></div></div>`;
  }
  hasil.addEventListener('click', e => {
    const b = e.target.closest('[data-aksi]'); if (!b) return;
    if (b.dataset.aksi === 'foto') $('cekFoto').click();
    if (b.dataset.aksi === 'manual') document.querySelector('[data-cek="manual"]').click();
  });
  async function daftarKamera() {
    if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) throw { name: 'SecureContext', message: 'https' };
    const semua = (await navigator.mediaDevices.enumerateDevices()).filter(d => d.kind === 'videoinput');
    if (!semua.length) throw { name: 'NotFoundError', message: 'Requested device not found' };
    return semua;
  }
  function pilihBelakang(kams) {
    const b = kams.find(k => /back|rear|belakang|environment|utama/i.test(k.label || ''));
    return (b || kams[kams.length - 1]).id || (b || kams[kams.length - 1]).deviceId;
  }
  function isiPilihan(kams, aktif) {
    const sel = $('cekKamera'); if (!sel) return;
    sel.innerHTML = kams.map((k, i) => `<option value="${esc(k.id || k.deviceId)}">${esc(k.label || 'Kamera ' + (i + 1))}</option>`).join('');
    sel.value = aktif; sel.classList.toggle('d-none', kams.length < 2);
  }
  async function nyalakan(idPilihan) {
    hasil.innerHTML = '';
    const kams = await daftarKamera();
    let pustaka = true;
    try { await muatPustaka(); } catch (e) { pustaka = false; }
    if (!pustaka) {
      if (!adaNative) { hasil.innerHTML = '<div class="alert alert-warning small mb-0">Pemindai kamera belum tersedia tanpa internet di browser ini. Gunakan Pilih foto QR atau Input manual.</div>'; return; }
      return mulaiNative(idPilihan || null, kams);
    }
    // html5-qrcode: daftar kamera lengkap dengan label (meminta izin sekali)
    let daftar = [];
    try { daftar = await Html5Qrcode.getCameras(); } catch (e) { throw e; }
    if (!daftar.length) throw { name: 'NotFoundError', message: 'Requested device not found' };
    const id = idPilihan || pilihBelakang(daftar);
    isiPilihan(daftar, id);
    $('cekReader').innerHTML = '';
    scanner = scanner || new Html5Qrcode('cekReader');
    await scanner.start(id, { fps: 10, qrbox: { width: 220, height: 220 } }, async teks => { await berhenti(); cek({ kode: teks }); });
    jalan = true; $('cekMulai').innerHTML = '<i class="bi bi-stop-circle me-1"></i>Matikan kamera';
  }
  $('cekMulai').onclick = async () => {
    if (jalan) return berhenti();
    try { await nyalakan(); } catch (e) { await berhenti(); tampilGagalKamera(e); }
  };
  if ($('cekKamera')) $('cekKamera').onchange = async e => {
    await berhenti();
    try { await nyalakan(e.target.value); } catch (err) { await berhenti(); tampilGagalKamera(err); }
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
