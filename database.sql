CREATE DATABASE IF NOT EXISTS db_rt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_rt;

CREATE TABLE warga (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_kk VARCHAR(20) NULL UNIQUE,
  nik_kepala VARCHAR(20) NULL,
  nama_kk VARCHAR(100) NOT NULL,
  alamat VARCHAR(200) NULL,
  jml_anggota TINYINT NOT NULL DEFAULT 1,
  status_hunian ENUM('pemilik','penyewa') NOT NULL DEFAULT 'pemilik',
  telp VARCHAR(20),
  darurat_nama VARCHAR(100),
  darurat_telp VARCHAR(20),
  tempat_lahir VARCHAR(60),
  tgl_lahir DATE NULL,
  jenis_kelamin VARCHAR(20),
  agama VARCHAR(20),
  pekerjaan VARCHAR(60),
  blok VARCHAR(20),
  no_rumah VARCHAR(10),
  email VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  role ENUM('admin','rt','warga') NOT NULL,
  warga_id INT NULL,
  jabatan VARCHAR(30) NOT NULL DEFAULT 'warga',
  foto VARCHAR(80) NULL,
  ganti_sandi TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (warga_id) REFERENCES warga(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pengumuman (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  isi TEXT NOT NULL,
  kategori ENUM('kegiatan','kerja bakti','siskamling','keamanan','lainnya') NOT NULL DEFAULT 'kegiatan',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tagihan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  warga_id INT NOT NULL,
  jenis VARCHAR(60) NOT NULL,
  periode VARCHAR(7) NOT NULL,
  alokasi VARCHAR(80) NOT NULL DEFAULT 'Kas umum',
  nominal INT NOT NULL,
  status ENUM('belum','menunggu','lunas') NOT NULL DEFAULT 'belum',
  metode VARCHAR(40),
  no_struk VARCHAR(30),
  tgl_bayar DATETIME NULL,
  bukti VARCHAR(80) NULL,
  catatan VARCHAR(200) NULL,
  tgl_upload DATETIME NULL,
  FOREIGN KEY (warga_id) REFERENCES warga(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE kas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  tipe ENUM('masuk','keluar') NOT NULL,
  kategori VARCHAR(60) NOT NULL,
  alokasi VARCHAR(80) NOT NULL DEFAULT 'Kas umum',
  keterangan VARCHAR(200),
  nominal INT NOT NULL,
  tagihan_id INT NULL,
  status ENUM('diajukan','diperiksa','disetujui','ditolak') NOT NULL DEFAULT 'disetujui',
  alur TEXT
) ENGINE=InnoDB;

CREATE TABLE surat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  warga_id INT NOT NULL,
  jenis VARCHAR(60) NOT NULL,
  keperluan VARCHAR(250) NOT NULL,
  status ENUM('diajukan','diteruskan','diperiksa','disetujui','ditolak') NOT NULL DEFAULT 'diajukan',
  catatan VARCHAR(250),
  no_surat VARCHAR(80),
  data_json TEXT,
  alur TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (warga_id) REFERENCES warga(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE aduan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  warga_id INT NOT NULL,
  kategori VARCHAR(40) NOT NULL,
  judul VARCHAR(150) NOT NULL,
  deskripsi TEXT NOT NULL,
  status ENUM('baru','diproses','selesai') NOT NULL DEFAULT 'baru',
  tanggapan VARCHAR(300),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (warga_id) REFERENCES warga(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== Fitur surat, profil, dan pengaturan =====
CREATE TABLE pengaturan (
  k VARCHAR(50) PRIMARY KEY,
  v TEXT
) ENGINE=InnoDB;

CREATE TABLE templat_surat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL UNIQUE,
  isi MEDIUMTEXT NOT NULL,
  input_json TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE surat_terbit (
  id INT AUTO_INCREMENT PRIMARY KEY,
  templat_id INT NULL,
  surat_id INT NULL,
  warga_id INT NULL,
  nomor VARCHAR(80) NOT NULL,
  judul VARCHAR(150) NOT NULL,
  isi MEDIUMTEXT NOT NULL,
  pakai_kop TINYINT(1) NOT NULL DEFAULT 1,
  ttd_qr TINYINT(1) NOT NULL DEFAULT 1,
  keperluan VARCHAR(250) NULL,
  status ENUM('draf','diperiksa','terbit','ditolak') NOT NULL DEFAULT 'terbit',
  alur TEXT,
  token CHAR(24) NOT NULL UNIQUE,
  penandatangan VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (templat_id) REFERENCES templat_surat(id) ON DELETE SET NULL,
  FOREIGN KEY (surat_id) REFERENCES surat(id) ON DELETE SET NULL,
  FOREIGN KEY (warga_id) REFERENCES warga(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jenis_tagihan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(60) NOT NULL UNIQUE,
  keterangan VARCHAR(150),
  nominal INT NOT NULL DEFAULT 0,
  alokasi VARCHAR(80) NOT NULL DEFAULT 'Kas umum',
  wajib TINYINT(1) NOT NULL DEFAULT 0,
  aktif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifikasi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  tipe VARCHAR(20) NOT NULL DEFAULT 'info',
  judul VARCHAR(150) NOT NULL,
  isi VARCHAR(300),
  url VARCHAR(150),
  dibaca TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id, dibaca),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS darurat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  jenis VARCHAR(40) NOT NULL,
  pesan VARCHAR(300),
  lokasi VARCHAR(150),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  selesai_at DATETIME NULL,
  selesai_oleh VARCHAR(100) NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS chat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kunci VARCHAR(40) NOT NULL,
  dari INT NOT NULL,
  isi TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (kunci, id),
  FOREIGN KEY (dari) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS chat_baca (
  user_id INT NOT NULL,
  kunci VARCHAR(40) NOT NULL,
  last_id INT NOT NULL DEFAULT 0,
  PRIMARY KEY (user_id, kunci),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== Data awal (akun contoh dan data demo) =====
INSERT INTO warga (no_kk,nik_kepala,nama_kk,alamat,jml_anggota,status_hunian,telp,darurat_nama,darurat_telp) VALUES
 ('3578010101010001','3578010101800001','Budi Santoso','Jl. Mawar No. 12',4,'pemilik','081234567890','Siti Santoso','081298765432'),
 ('3578010101010002','3578010101850002','Andi Wijaya','Jl. Melati No. 7',3,'penyewa','081211112222','Rina Wijaya','081233334444');

INSERT INTO users (username,password,nama,role,warga_id) VALUES
 ('admin','$2y$12$7Do3mZ4BG6GtQiLjZDC0Ie/Aj7MkeZU4V/rN7t4cn1DHaPX31yUAy','Administrator','admin',NULL),
 ('rt','$2y$12$EffzbBZKb/fzBFlrbLd61OegZlQPy42RKKq9unjdUMJZpos4xNJEa','Ketua RT','rt',NULL),
 ('budi','$2y$12$MjfGif7/zOo0svLCMea7auOPJJ.M5j1t8g/pHJ0JgsSkWycppYbGG','Budi Santoso','warga',1);

INSERT INTO pengumuman (judul,isi,kategori) VALUES
 ('Kerja bakti bersihkan saluran air','Minggu pagi pukul 06.00, kumpul di pos ronda. Bawa sapu dan cangkul.','kerja bakti'),
 ('Jadwal piket siskamling bulan ini','Jadwal lengkap ditempel di pos ronda. Mohon tukar jadwal lewat ketua RT.','siskamling');

INSERT INTO kas (tanggal,tipe,kategori,alokasi,keterangan,nominal) VALUES
 (CURDATE(),'masuk','Saldo awal','Kas umum','Saldo awal pembukuan',1000000),
 (CURDATE(),'masuk','Donasi','Pembangunan saung','Donasi awal warga',500000);

INSERT INTO tagihan (warga_id,jenis,periode,alokasi,nominal) VALUES
 (1,'IPL',DATE_FORMAT(CURDATE(),'%Y-%m'),'IPL',100000),
 (2,'IPL',DATE_FORMAT(CURDATE(),'%Y-%m'),'IPL',100000);

UPDATE warga SET tempat_lahir='Surabaya', tgl_lahir='1980-05-14', jenis_kelamin='Laki-laki', agama='Islam', pekerjaan='Karyawan Swasta' WHERE id=1;
UPDATE warga SET tempat_lahir='Malang', tgl_lahir='1985-09-02', jenis_kelamin='Laki-laki', agama='Islam', pekerjaan='Wiraswasta' WHERE id=2;
UPDATE warga SET alamat='Jl. Mawar', blok='A', no_rumah='12' WHERE id=1;
UPDATE warga SET alamat='Jl. Melati', blok='B', no_rumah='7' WHERE id=2;
UPDATE users SET jabatan='ketua' WHERE username='rt';

-- ===== Templat surat bawaan =====
INSERT INTO templat_surat (nama,isi,input_json) VALUES
('Surat Pengantar RT/RW','<h3 style="text-align:center;margin:0"><u>SURAT PENGANTAR RT/RW</u></h3><p style="text-align:center;margin:0 0 12px">Nomor: {{nomor_surat}}</p><p>Yang bertanda tangan di bawah ini, Ketua RT {{rt}} RW {{rw}}, Kelurahan {{kelurahan}}, Kecamatan {{kecamatan}}, {{kota}}, menerangkan bahwa:</p><table class="bio" style="width:100%"><tbody>
<tr><td style="width:34%">Nama Lengkap</td><td style="width:3%">:</td><td><b>{{nama}}</b></td></tr>
<tr><td>NIK</td><td>:</td><td>{{nik}}</td></tr>
<tr><td>Tempat / Tgl. Lahir</td><td>:</td><td>{{tempat_lahir}}, {{tgl_lahir}}</td></tr>
<tr><td>Jenis Kelamin</td><td>:</td><td>{{jenis_kelamin}}</td></tr>
<tr><td>Agama</td><td>:</td><td>{{agama}}</td></tr>
<tr><td>Pekerjaan</td><td>:</td><td>{{pekerjaan}}</td></tr>
<tr><td>Alamat</td><td>:</td><td>{{alamat}}, RT {{rt}} / RW {{rw}}</td></tr>
</tbody></table><p>Adalah benar warga kami dan berdomisili di wilayah RT {{rt}} RW {{rw}}. Surat pengantar ini dibuat untuk keperluan: <b>{{keperluan}}</b>. Mohon bantuan dan fasilitasi seperlunya.</p><p>Demikian surat ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p><div style="width:48%;margin-left:52%;text-align:center"><p style="margin:12px 0 0">{{kota}}, {{tanggal_surat}}<br>Ketua RT {{rt}} / RW {{rw}}</p><p style="margin:6px 0">{{qr_ttd}}</p><p style="margin:0"><b><u>{{nama_ketua}}</u></b></p></div>','[]'),
('Surat Keterangan Domisili','<h3 style="text-align:center;margin:0"><u>SURAT KETERANGAN DOMISILI</u></h3><p style="text-align:center;margin:0 0 12px">Nomor: {{nomor_surat}}</p><p>Yang bertanda tangan di bawah ini, Ketua RT {{rt}} RW {{rw}}, Kelurahan {{kelurahan}}, Kecamatan {{kecamatan}}, {{kota}}, menerangkan bahwa:</p><table class="bio" style="width:100%"><tbody>
<tr><td style="width:34%">Nama Lengkap</td><td style="width:3%">:</td><td><b>{{nama}}</b></td></tr>
<tr><td>NIK</td><td>:</td><td>{{nik}}</td></tr>
<tr><td>Tempat / Tgl. Lahir</td><td>:</td><td>{{tempat_lahir}}, {{tgl_lahir}}</td></tr>
<tr><td>Jenis Kelamin</td><td>:</td><td>{{jenis_kelamin}}</td></tr>
<tr><td>Agama</td><td>:</td><td>{{agama}}</td></tr>
<tr><td>Pekerjaan</td><td>:</td><td>{{pekerjaan}}</td></tr>
<tr><td>Alamat</td><td>:</td><td>{{alamat}}, RT {{rt}} / RW {{rw}}</td></tr>
</tbody></table><p>Orang tersebut benar berdomisili di alamat di atas dengan status hunian <b>{{status_hunian}}</b>. Surat keterangan ini dibuat untuk keperluan administrasi: <b>{{keperluan}}</b>.</p><p>Demikian surat ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p><div style="width:48%;margin-left:52%;text-align:center"><p style="margin:12px 0 0">{{kota}}, {{tanggal_surat}}<br>Ketua RT {{rt}} / RW {{rw}}</p><p style="margin:6px 0">{{qr_ttd}}</p><p style="margin:0"><b><u>{{nama_ketua}}</u></b></p></div>','[]'),
('Surat Pengantar Pembuatan KTP / KK','<h3 style="text-align:center;margin:0"><u>SURAT PENGANTAR PEMBUATAN KTP / KK</u></h3><p style="text-align:center;margin:0 0 12px">Nomor: {{nomor_surat}}</p><p>Yang bertanda tangan di bawah ini, Ketua RT {{rt}} RW {{rw}}, Kelurahan {{kelurahan}}, Kecamatan {{kecamatan}}, {{kota}}, menerangkan bahwa:</p><table class="bio" style="width:100%"><tbody>
<tr><td style="width:34%">Nama Lengkap</td><td style="width:3%">:</td><td><b>{{nama}}</b></td></tr>
<tr><td>NIK</td><td>:</td><td>{{nik}}</td></tr>
<tr><td>Tempat / Tgl. Lahir</td><td>:</td><td>{{tempat_lahir}}, {{tgl_lahir}}</td></tr>
<tr><td>Jenis Kelamin</td><td>:</td><td>{{jenis_kelamin}}</td></tr>
<tr><td>Agama</td><td>:</td><td>{{agama}}</td></tr>
<tr><td>Pekerjaan</td><td>:</td><td>{{pekerjaan}}</td></tr>
<tr><td>Alamat</td><td>:</td><td>{{alamat}}, RT {{rt}} / RW {{rw}}</td></tr>
</tbody></table><p>Adalah benar warga kami yang bermaksud mengurus <b>{{jenis_pengurusan}}</b> (KTP / Kartu Keluarga) di kantor kelurahan/kecamatan setempat. Keterangan tambahan: {{keperluan}}.</p><p>Demikian surat ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p><div style="width:48%;margin-left:52%;text-align:center"><p style="margin:12px 0 0">{{kota}}, {{tanggal_surat}}<br>Ketua RT {{rt}} / RW {{rw}}</p><p style="margin:6px 0">{{qr_ttd}}</p><p style="margin:0"><b><u>{{nama_ketua}}</u></b></p></div>','[{"key": "jenis_pengurusan", "label": "Jenis Pengurusan (KTP/KK baru, perubahan data, kehilangan)", "type": "text", "wajib": true}]'),
('Surat Pengantar Kematian','<h3 style="text-align:center;margin:0"><u>SURAT PENGANTAR KEMATIAN</u></h3><p style="text-align:center;margin:0 0 12px">Nomor: {{nomor_surat}}</p><p>Yang bertanda tangan di bawah ini, Ketua RT {{rt}} RW {{rw}}, Kelurahan {{kelurahan}}, Kecamatan {{kecamatan}}, {{kota}}, menerangkan bahwa:</p><table class="bio" style="width:100%"><tbody>
<tr><td style="width:34%">Nama Lengkap</td><td style="width:3%">:</td><td><b>{{nama}}</b></td></tr>
<tr><td>NIK</td><td>:</td><td>{{nik}}</td></tr>
<tr><td>Tempat / Tgl. Lahir</td><td>:</td><td>{{tempat_lahir}}, {{tgl_lahir}}</td></tr>
<tr><td>Jenis Kelamin</td><td>:</td><td>{{jenis_kelamin}}</td></tr>
<tr><td>Agama</td><td>:</td><td>{{agama}}</td></tr>
<tr><td>Pekerjaan</td><td>:</td><td>{{pekerjaan}}</td></tr>
<tr><td>Alamat</td><td>:</td><td>{{alamat}}, RT {{rt}} / RW {{rw}}</td></tr>
</tbody></table><p>Bahwa pada hari/tanggal <b>{{tgl_meninggal}}</b>, telah meninggal dunia:</p><table class="bio" style="width:100%"><tbody><tr><td style="width:34%">Nama Almarhum/ah</td><td style="width:3%">:</td><td><b>{{nama_almarhum}}</b></td></tr><tr><td>Usia</td><td>:</td><td>{{usia_almarhum}} tahun</td></tr><tr><td>Tempat Meninggal</td><td>:</td><td>{{tempat_meninggal}}</td></tr><tr><td>Hubungan dengan Pelapor</td><td>:</td><td>{{hubungan_pelapor}}</td></tr></tbody></table><p>Surat pengantar ini dibuat untuk pengurusan: <b>{{keperluan}}</b> (misalnya pemakaman atau pelaporan ke kelurahan).</p><p>Demikian surat ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p><div style="width:48%;margin-left:52%;text-align:center"><p style="margin:12px 0 0">{{kota}}, {{tanggal_surat}}<br>Ketua RT {{rt}} / RW {{rw}}</p><p style="margin:6px 0">{{qr_ttd}}</p><p style="margin:0"><b><u>{{nama_ketua}}</u></b></p></div>','[{"key": "nama_almarhum", "label": "Nama Almarhum/ah", "type": "text", "wajib": true}, {"key": "usia_almarhum", "label": "Usia Almarhum/ah", "type": "number", "wajib": true}, {"key": "tgl_meninggal", "label": "Tanggal Meninggal", "type": "date", "wajib": true}, {"key": "tempat_meninggal", "label": "Tempat Meninggal", "type": "text", "wajib": true}, {"key": "hubungan_pelapor", "label": "Hubungan Pelapor dengan Almarhum/ah", "type": "text", "wajib": true}]'),
('Surat Keterangan Tidak Mampu (SKTM)','<h3 style="text-align:center;margin:0"><u>SURAT KETERANGAN TIDAK MAMPU (SKTM)</u></h3><p style="text-align:center;margin:0 0 12px">Nomor: {{nomor_surat}}</p><p>Yang bertanda tangan di bawah ini, Ketua RT {{rt}} RW {{rw}}, Kelurahan {{kelurahan}}, Kecamatan {{kecamatan}}, {{kota}}, menerangkan bahwa:</p><table class="bio" style="width:100%"><tbody>
<tr><td style="width:34%">Nama Lengkap</td><td style="width:3%">:</td><td><b>{{nama}}</b></td></tr>
<tr><td>NIK</td><td>:</td><td>{{nik}}</td></tr>
<tr><td>Tempat / Tgl. Lahir</td><td>:</td><td>{{tempat_lahir}}, {{tgl_lahir}}</td></tr>
<tr><td>Jenis Kelamin</td><td>:</td><td>{{jenis_kelamin}}</td></tr>
<tr><td>Agama</td><td>:</td><td>{{agama}}</td></tr>
<tr><td>Pekerjaan</td><td>:</td><td>{{pekerjaan}}</td></tr>
<tr><td>Alamat</td><td>:</td><td>{{alamat}}, RT {{rt}} / RW {{rw}}</td></tr>
</tbody></table><p>Berdasarkan pengamatan kami, orang tersebut termasuk keluarga kurang mampu secara ekonomi. Surat keterangan ini dibuat untuk keperluan: <b>{{keperluan}}</b> (misalnya keringanan biaya sekolah, pengobatan, atau bantuan sosial).</p><p>Demikian surat ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p><div style="width:48%;margin-left:52%;text-align:center"><p style="margin:12px 0 0">{{kota}}, {{tanggal_surat}}<br>Ketua RT {{rt}} / RW {{rw}}</p><p style="margin:6px 0">{{qr_ttd}}</p><p style="margin:0"><b><u>{{nama_ketua}}</u></b></p></div>','[]'),
('Surat Pengantar Nikah / Kelahiran / Kepindahan','<h3 style="text-align:center;margin:0"><u>SURAT PENGANTAR NIKAH / KELAHIRAN / KEPINDAHAN</u></h3><p style="text-align:center;margin:0 0 12px">Nomor: {{nomor_surat}}</p><p>Yang bertanda tangan di bawah ini, Ketua RT {{rt}} RW {{rw}}, Kelurahan {{kelurahan}}, Kecamatan {{kecamatan}}, {{kota}}, menerangkan bahwa:</p><table class="bio" style="width:100%"><tbody>
<tr><td style="width:34%">Nama Lengkap</td><td style="width:3%">:</td><td><b>{{nama}}</b></td></tr>
<tr><td>NIK</td><td>:</td><td>{{nik}}</td></tr>
<tr><td>Tempat / Tgl. Lahir</td><td>:</td><td>{{tempat_lahir}}, {{tgl_lahir}}</td></tr>
<tr><td>Jenis Kelamin</td><td>:</td><td>{{jenis_kelamin}}</td></tr>
<tr><td>Agama</td><td>:</td><td>{{agama}}</td></tr>
<tr><td>Pekerjaan</td><td>:</td><td>{{pekerjaan}}</td></tr>
<tr><td>Alamat</td><td>:</td><td>{{alamat}}, RT {{rt}} / RW {{rw}}</td></tr>
</tbody></table><p>Adalah benar warga kami yang bermaksud mengurus <b>{{jenis_pengurusan}}</b>. Keterangan: {{keperluan}}.</p><p>Demikian surat ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p><div style="width:48%;margin-left:52%;text-align:center"><p style="margin:12px 0 0">{{kota}}, {{tanggal_surat}}<br>Ketua RT {{rt}} / RW {{rw}}</p><p style="margin:6px 0">{{qr_ttd}}</p><p style="margin:0"><b><u>{{nama_ketua}}</u></b></p></div>','[{"key": "jenis_pengurusan", "label": "Jenis Pengurusan (Nikah / Kelahiran / Kepindahan)", "type": "text", "wajib": true}]'),
('Surat Keterangan Usaha (SKU)','<h3 style="text-align:center;margin:0"><u>SURAT KETERANGAN USAHA (SKU)</u></h3><p style="text-align:center;margin:0 0 12px">Nomor: {{nomor_surat}}</p><p>Yang bertanda tangan di bawah ini, Ketua RT {{rt}} RW {{rw}}, Kelurahan {{kelurahan}}, Kecamatan {{kecamatan}}, {{kota}}, menerangkan bahwa:</p><table class="bio" style="width:100%"><tbody>
<tr><td style="width:34%">Nama Lengkap</td><td style="width:3%">:</td><td><b>{{nama}}</b></td></tr>
<tr><td>NIK</td><td>:</td><td>{{nik}}</td></tr>
<tr><td>Tempat / Tgl. Lahir</td><td>:</td><td>{{tempat_lahir}}, {{tgl_lahir}}</td></tr>
<tr><td>Jenis Kelamin</td><td>:</td><td>{{jenis_kelamin}}</td></tr>
<tr><td>Agama</td><td>:</td><td>{{agama}}</td></tr>
<tr><td>Pekerjaan</td><td>:</td><td>{{pekerjaan}}</td></tr>
<tr><td>Alamat</td><td>:</td><td>{{alamat}}, RT {{rt}} / RW {{rw}}</td></tr>
</tbody></table><p>Orang tersebut memiliki usaha di lingkungan RT {{rt}} RW {{rw}} dengan keterangan:</p><table class="bio" style="width:100%"><tbody><tr><td style="width:34%">Nama Usaha</td><td style="width:3%">:</td><td><b>{{nama_usaha}}</b></td></tr><tr><td>Jenis Usaha</td><td>:</td><td>{{jenis_usaha}}</td></tr><tr><td>Alamat Usaha</td><td>:</td><td>{{alamat_usaha}}</td></tr></tbody></table><p>Surat keterangan ini dibuat untuk keperluan: <b>{{keperluan}}</b>.</p><p>Demikian surat ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p><div style="width:48%;margin-left:52%;text-align:center"><p style="margin:12px 0 0">{{kota}}, {{tanggal_surat}}<br>Ketua RT {{rt}} / RW {{rw}}</p><p style="margin:6px 0">{{qr_ttd}}</p><p style="margin:0"><b><u>{{nama_ketua}}</u></b></p></div>','[{"key": "nama_usaha", "label": "Nama Usaha", "type": "text", "wajib": true}, {"key": "jenis_usaha", "label": "Jenis Usaha", "type": "text", "wajib": true}, {"key": "alamat_usaha", "label": "Alamat Usaha", "type": "text", "wajib": true}]');

INSERT INTO users (username,password,nama,role,warga_id,jabatan,ganti_sandi) VALUES
 ('wakil','$2y$12$O91zCi60zN2CSPknHRUI4OxmLtZThftU6I9WPpjIKq5AoxnOfRupi','Wakil Ketua RT','rt',NULL,'wakil',1),
 ('sekretaris','$2y$12$3LCKdHL9ZNCWjqKODbB/p./w17H9VHu1LKRHEYmtE5gMOPWXKaTqe','Sekretaris RT','rt',NULL,'sekretaris',1),
 ('bendahara','$2y$12$7iKjEA.BNAcfaJbi80IW7OnquIGP53PjXbs4hbQokybwEV25fuobS','Bendahara RT','rt',NULL,'bendahara',1);

-- ===== Jenis tagihan bawaan =====
INSERT IGNORE INTO jenis_tagihan (nama,keterangan,nominal,alokasi,wajib) VALUES
 ('IPL','Iuran Pengelolaan Lingkungan: keamanan, kebersihan, dan sampah',100000,'IPL',1),
 ('Kas RT','Iuran kas RT untuk kegiatan dan operasional',25000,'Kas umum',1);
