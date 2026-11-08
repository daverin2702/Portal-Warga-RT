/* Notifikasi, tombol darurat, dan chat pop-up. Data diambil dari api.php secara berkala (polling). */
(function () {
  if (!window.RT) return;
  const $ = id => document.getElementById(id);
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const jam = t => { const d = new Date(String(t).replace(' ', 'T')); return isNaN(d) ? '' : d.toLocaleString('id-ID', {day:'numeric', month:'short', hour:'2-digit', minute:'2-digit'}); };
  const store = { get(k, d) { try { return JSON.parse(localStorage.getItem(k)) ?? d; } catch (e) { return d; } }, set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} } };
  async function api(aksi, body, qs = '') {
    const r = await fetch('api.php?aksi=' + aksi + qs, body ? { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF': RT.csrf }, body: JSON.stringify(body) } : {});
    const j = await r.json().catch(() => ({ error: 'Server tidak merespons.' }));
    if (r.status === 401) location.href = 'index.php';
    if (!r.ok || j.error) throw new Error(j.error || 'Gagal memuat.');
    return j;
  }

  // ---------- suara ----------
  let ctx;
  function bunyi(sirene) {
    try {
      ctx = ctx || new (window.AudioContext || window.webkitAudioContext)();
      const n = sirene ? 6 : 1;
      for (let i = 0; i < n; i++) {
        const o = ctx.createOscillator(), g = ctx.createGain(), t = ctx.currentTime + i * 0.45;
        o.type = sirene ? 'sawtooth' : 'sine';
        o.frequency.setValueAtTime(sirene ? 880 : 740, t);
        if (sirene) o.frequency.linearRampToValueAtTime(560, t + 0.4);
        g.gain.setValueAtTime(sirene ? 0.25 : 0.12, t); g.gain.exponentialRampToValueAtTime(0.001, t + 0.42);
        o.connect(g).connect(ctx.destination); o.start(t); o.stop(t + 0.43);
      }
    } catch (e) {}
  }
  document.addEventListener('click', () => { try { ctx = ctx || new (window.AudioContext || window.webkitAudioContext)(); ctx.resume(); } catch (e) {} }, { once: true });

  // ---------- notifikasi ----------
  const IKON = { darurat: 'bi-exclamation-octagon-fill text-danger', persetujuan: 'bi-check2-square text-primary', tagihan: 'bi-wallet2 text-warning', pengumuman: 'bi-megaphone text-info', aduan: 'bi-chat-left-text text-secondary', info: 'bi-info-circle text-secondary' };
  let lastNotif = store.get('rt_last_notif_' + RT.me, 0), pertama = true;
  function gambarNotif(d) {
    const b = $('bellBadge'); b.classList.toggle('d-none', !d.belum); b.textContent = d.belum > 9 ? '9+' : d.belum;
    $('notifList').innerHTML = d.items.length ? d.items.map(n =>
      `<a class="notif-item ${n.dibaca == 0 ? 'baru' : ''}" href="${esc(n.url || '#')}" data-id="${n.id}"><i class="bi ${IKON[n.tipe] || IKON.info}"></i><div><div class="fw-semibold">${esc(n.judul)}</div>${n.isi ? `<div class="small text-secondary">${esc(n.isi)}</div>` : ''}<div class="small text-secondary">${jam(n.created_at)}</div></div></a>`).join('')
      : '<div class="p-3 small text-secondary">Belum ada notifikasi.</div>';
    const baru = d.items.filter(n => +n.id > lastNotif && n.dibaca == 0);
    if (baru.length && !pertama) {
      bunyi(false);
      if ('Notification' in window && Notification.permission === 'granted')
        baru.slice(0, 3).forEach(n => { try { new Notification(n.judul, { body: n.isi || '', tag: 'rt' + n.id }); } catch (e) {} });
    }
    if (d.items.length) { lastNotif = Math.max(lastNotif, ...d.items.map(n => +n.id)); store.set('rt_last_notif_' + RT.me, lastNotif); }
  }
  $('notifList').addEventListener('click', e => { const a = e.target.closest('.notif-item'); if (a) api('baca', { id: +a.dataset.id }).catch(() => {}); });
  $('bacaSemua').onclick = () => api('baca', { semua: 1 }).then(poll).catch(() => {});
  $('izinBrowser').onclick = e => {
    e.preventDefault();
    if (!('Notification' in window)) return alert('Browser ini tidak mendukung notifikasi.');
    Notification.requestPermission().then(p => { e.target.textContent = p === 'granted' ? 'Notifikasi browser aktif' : 'Izin notifikasi ditolak di browser'; });
  };
  if ('Notification' in window && Notification.permission === 'granted') $('izinBrowser').textContent = 'Notifikasi browser aktif';

  // ---------- darurat ----------
  let sosDilihat = store.get('rt_sos_ack', []);
  function gambarDarurat(list) {
    const aktif = list.filter(x => !sosDilihat.includes(+x.id));
    const L = $('sosLayer');
    if (!aktif.length) { L.classList.add('d-none'); return; }
    const x = aktif[0], tampilBaru = L.classList.contains('d-none') || L.dataset.id != x.id;
    L.dataset.id = x.id;
    $('sosIsi').innerHTML = `<div class="small text-uppercase fw-bold" style="letter-spacing:.1em">Sinyal darurat</div>
      <h2 class="display-6 fw-bold my-1">${esc(x.jenis)}</h2>
      <div class="fs-5 fw-semibold">${esc(x.nama)}</div><div>${esc(x.lokasi || 'Lokasi tidak tercatat')}</div>
      ${x.pesan ? `<div class="mt-2 fst-italic">"${esc(x.pesan)}"</div>` : ''}
      <div class="small mt-2 opacity-75">${jam(x.created_at)}${aktif.length > 1 ? ' &middot; ' + (aktif.length - 1) + ' sinyal lain' : ''}</div>
      <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
        <button class="btn btn-light fw-semibold" data-ack="${x.id}">Saya mengerti</button>
        ${RT.pengurus ? `<button class="btn btn-outline-light fw-semibold" data-aman="${x.id}">Tandai situasi aman</button>` : ''}
        <a class="btn btn-outline-light" href="tel:112">Telepon 112</a></div>`;
    L.classList.remove('d-none');
    if (tampilBaru) {
      bunyi(true);
      if ('Notification' in window && Notification.permission === 'granted') try { new Notification('DARURAT: ' + x.jenis, { body: x.nama + ' - ' + (x.lokasi || ''), requireInteraction: true, tag: 'sos' + x.id }); } catch (e) {}
    }
  }
  $('sosLayer').addEventListener('click', e => {
    const a = e.target.closest('[data-ack]'), m = e.target.closest('[data-aman]');
    if (a) { sosDilihat.push(+a.dataset.ack); store.set('rt_sos_ack', sosDilihat.slice(-50)); poll(); }
    if (m && confirm('Tandai keadaan darurat ini sudah ditangani? Semua warga akan diberi tahu.')) api('darurat_selesai', { id: +m.dataset.aman }).then(poll).catch(err => alert(err.message));
  });
  $('kirimDarurat').onclick = async () => {
    const jenis = (document.querySelector('input[name=jd]:checked') || {}).value;
    if (!confirm('Kirim sinyal darurat "' + jenis + '" ke semua pengurus dan warga?')) return;
    const b = $('kirimDarurat'); b.disabled = true;
    try { const r = await api('darurat', { jenis, pesan: $('pesanDarurat').value }); bootstrap.Modal.getInstance($('mPanic')).hide(); $('pesanDarurat').value = ''; alert(r.pesan); poll(); }
    catch (err) { alert(err.message); } finally { b.disabled = false; }
  };

  // ---------- polling ----------
  let timer;
  async function poll() {
    clearTimeout(timer);
    try {
      const d = await api('poll');
      gambarNotif(d); gambarDarurat(d.darurat);
      const c = $('chatBadge'); c.classList.toggle('d-none', !d.chat || chatTerbuka); c.textContent = d.chat > 9 ? '9+' : d.chat;
      pertama = false;
    } catch (e) {}
    timer = setTimeout(poll, document.hidden ? 45000 : 15000);
  }
  document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

  // ---------- chat ----------
  let chatTerbuka = false, kunciAktif = null, lastChat = 0, chatTimer;
  const pop = $('chatPop'), body = $('chatBody');
  $('btnChat').onclick = () => { chatTerbuka = !chatTerbuka; pop.classList.toggle('d-none', !chatTerbuka); if (chatTerbuka) bukaDaftar(); else tutupRuang(); };
  $('chatClose').onclick = () => { chatTerbuka = false; pop.classList.add('d-none'); tutupRuang(); poll(); };
  $('chatBack').onclick = () => { tutupRuang(); bukaDaftar(); };
  function tutupRuang() { kunciAktif = null; clearTimeout(chatTimer); }
  async function bukaDaftar() {
    $('chatTitle').textContent = 'Chat'; $('chatBack').classList.add('d-none'); $('chatForm').classList.add('d-none');
    body.innerHTML = '<div class="p-3 small text-secondary">Memuat...</div>';
    try {
      const d = await api('chat_list');
      const ada = new Set(d.daftar.map(x => x.kunci));
      body.innerHTML = '<div class="chat-sec">Percakapan</div>' + d.daftar.map(x =>
        `<button class="chat-row" data-k="${esc(x.kunci)}" data-n="${esc(x.nama)}"><span class="ava">${x.grup ? '<i class="bi bi-people-fill"></i>' : esc(x.nama.slice(0, 1))}</span><span class="flex-grow-1 text-start"><span class="d-block fw-semibold">${esc(x.nama)}</span><span class="d-block small text-secondary text-truncate">${esc(x.akhir) || 'Belum ada pesan'}</span></span>${x.belum ? `<span class="badge text-bg-danger">${x.belum}</span>` : ''}</button>`).join('')
        + '<div class="chat-sec">' + (RT.pengurus ? 'Pesan pribadi' : 'Hubungi pengurus') + '</div>'
        + (d.kontak.filter(x => !ada.has(x.kunci)).map(x =>
          `<button class="chat-row" data-k="${esc(x.kunci)}" data-n="${esc(x.nama)}"><span class="ava">${esc(x.nama.slice(0, 1))}</span><span class="flex-grow-1 text-start"><span class="d-block fw-semibold">${esc(x.nama)}</span><span class="d-block small text-secondary">${esc(x.label)}</span></span></button>`).join('') || '<div class="px-3 pb-3 small text-secondary">Tidak ada kontak lain.</div>');
    } catch (e) { body.innerHTML = `<div class="p-3 small text-danger">${esc(e.message)}</div>`; }
  }
  body.addEventListener('click', e => { const r = e.target.closest('.chat-row'); if (r) bukaRuang(r.dataset.k, r.dataset.n); });
  function bukaRuang(k, nama) {
    kunciAktif = k; lastChat = 0;
    $('chatTitle').textContent = nama; $('chatBack').classList.remove('d-none'); $('chatForm').classList.remove('d-none');
    body.innerHTML = ''; ambil(); $('chatInput').focus();
  }
  async function ambil() {
    clearTimeout(chatTimer); if (!kunciAktif) return;
    const k = kunciAktif;
    try {
      const d = await api('chat_ambil', null, '&kunci=' + encodeURIComponent(k) + '&setelah=' + lastChat);
      if (k !== kunciAktif) return;
      if (!lastChat && !d.pesan.length) body.innerHTML = '<div class="p-3 small text-secondary text-center">Belum ada pesan. Mulai percakapan.</div>';
      else if (!lastChat) body.innerHTML = '';
      d.pesan.forEach(m => {
        body.insertAdjacentHTML('beforeend', `<div class="msg ${m.saya ? 'saya' : ''}">${!m.saya && !k.startsWith('dm:') ? `<div class="small fw-semibold">${esc(m.nama)}</div>` : ''}<div>${esc(m.isi).replace(/\n/g, '<br>')}</div><div class="t">${jam(m.created_at)}</div></div>`);
        lastChat = Math.max(lastChat, +m.id);
      });
      if (d.pesan.length) body.scrollTop = body.scrollHeight;
    } catch (e) {}
    chatTimer = setTimeout(ambil, document.hidden ? 15000 : 4000);
  }
  $('chatForm').onsubmit = async e => {
    e.preventDefault();
    const i = $('chatInput'), isi = i.value.trim(); if (!isi || !kunciAktif) return;
    i.value = '';
    try { await api('chat_kirim', { kunci: kunciAktif, isi }); ambil(); } catch (err) { i.value = isi; alert(err.message); }
  };

  poll();
})();
