WEBSITE RT - PANDUAN PEMASANGAN

STRUKTUR
  install.php      installer bertahap (hapus setelah selesai)
  database.sql     struktur tabel + templat surat (+ data contoh opsional)
  index.php        pintu masuk: ke app/ jika sudah terpasang, ke installer jika belum
  app/             seluruh berkas aplikasi
  app/sql/         berkas pembaruan database versi lama (dipakai otomatis oleh installer)

PASANG BARU (XAMPP atau hosting)
1. Salin semua isi folder ke htdocs/websitert (atau public_html di hosting).
2. Buka http://localhost/websitert/ — otomatis diarahkan ke installer.
3. Ikuti 5 langkah: cek server, isi koneksi database, buat tabel (boleh dengan data contoh),
   isi nama website, RT/RW, wilayah, logo, favicon, tema, dan akun admin.
4. Klik "Hapus install.php" di langkah terakhir.

MEMPERBARUI WEBSITE LAMA (database sudah berisi data)
1. Timpa berkas dengan versi ini. Jangan hapus database.
2. Buka install.php, isi koneksi database yang sama.
3. Di langkah Tabel pilih "Pakai data yang ada". Struktur tabel diperbarui otomatis bila perlu.
4. Akun admin boleh dikosongkan untuk memakai akun yang sudah ada.

VERSI DATABASE 5
Menambah tabel jenis_tagihan (IPL dan Kas RT bawaan). Website lama: jalankan install.php,
pilih "Pakai data yang ada"; pembaruan dijalankan otomatis. Manual: impor app/sql/update_v5.sql.

VERSI DATABASE 6
Menambah tabel notifikasi, darurat, chat, dan chat_baca. Website lama: jalankan install.php,
pilih "Pakai data yang ada". Manual: impor app/sql/update_v6.sql.

NOTIFIKASI, DARURAT, CHAT
- Lonceng di kanan atas: tagihan, pengumuman, persetujuan sesuai jabatan, bukti bayar, aduan, darurat.
- Tombol Darurat: sinyal ke semua pengguna (pengurus dan warga), muncul layar merah dengan sirene,
  dibatasi 1 kali per 2 menit per akun. Pengurus bisa menandai "situasi aman".
- Chat pop-up: Grup Warga, Grup Pengurus, pesan pribadi (warga ke pengurus; pengurus ke siapa saja).
- Semua bekerja dengan polling (cek berkala tiap 15 detik, chat tiap 4 detik) saat website terbuka.
  Notifikasi browser bisa diaktifkan dari menu lonceng. Tidak ada push saat website tertutup.

VERSI DATABASE 7: TANDA TANGAN & CEK SURAT
- Sekretaris memilih tanda tangan Ketua saat membuat surat: digital (QR) atau basah (tanpa QR).
  Ketua bisa mengubahnya saat menandatangani di menu Persetujuan.
- Surat tanpa QR memuat catatan kecil: keaslian bisa dicek dengan nomor surat dan tanggal.
- Halaman depan punya tombol "Cek Surat" (kanan bawah): scan QR dengan kamera atau foto, atau input
  nomor surat + tanggal pembuatan. Nama pemohon, tujuan, dan penandatangan ditampilkan tersensor (Bu** Sa****).
- Kamera browser hanya bisa dipakai di https atau localhost.

MODE OFFLINE (ASET LOKAL)
- Sudah tersedia di app/assets/vendor: Bootstrap 5.3.8, Bootstrap Icons 1.13.2, font Plus Jakarta Sans/Inter/Fraunces,
  dan pembuat QR untuk tanda tangan surat. Belum ada: Chart.js (grafik dasbor) dan html5-qrcode (pemindai kamera);
  lihat app/assets/vendor/README.txt. Tanpa keduanya, grafik dan pemindai memakai internet, sedangkan
  pemindai jatuh ke pembaca QR bawaan browser bila tersedia.
- Semua halaman memuat Bootstrap, ikon, font, Chart.js, dan pustaka QR lewat app/aset.php.
  Jika salinan lokal ada di app/assets/vendor, itu yang dipakai; jika tidak, otomatis memakai CDN.
- Unduh sekali lewat menu admin "Aset Offline" (atau tombol di langkah 1 installer) saat server terhubung internet.
- Jika unduhan gagal karena sertifikat SSL di XAMPP: buka php.ini, isi
  curl.cainfo = "C:\xampp\apache\bin\curl-ca-bundle.crt"  (atau lokasi cacert.pem), lalu restart Apache.

MEMASANG ULANG
Hapus app/install.lock lewat file manager, lalu buka install.php.

CODESPACES / DOCKER
Koneksi database diberikan lewat variabel lingkungan (DB_HOST dst.), jadi installer tidak wajib.
Data contoh diimpor otomatis dari database.sql.

FITUR & WEWENANG (ringkas)
- Sekretaris: membuat surat manual, meneruskan pengajuan surat warga, data warga.
- Wakil Ketua: checker (memeriksa surat dan pengeluaran). Ketua: signer (menandatangani surat, menyetujui pengeluaran).
- Bendahara: menerbitkan tagihan wajib (IPL, Kas RT) dan tagihan lain, mengatur Jenis Tagihan,
  verifikasi bukti bayar, posting pengeluaran bulanan, laporan kas bulanan.
- Admin: semua wewenang, akun, impor Excel, Pengaturan Situs, Tema Website.

VERSI PUSTAKA
- Bootstrap 5.3.8 (stabil terbaru) dan Bootstrap Icons 1.13.2, dimuat dari CDN jsDelivr.
- Bootstrap 6 masih alpha (6.0.0-alpha.1, Oktober 2026). Perpindahan ke 6 ditunda sampai rilis stabil,
  karena v6 butuh browser baru (Chrome 130+, Safari 18+) dan mengganti banyak nama kelas.
