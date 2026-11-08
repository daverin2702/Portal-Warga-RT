USE db_rt;
-- Jalankan SEKALI pada database lama (yang dibuat dari versi sebelumnya).
ALTER TABLE warga
  ADD COLUMN tempat_lahir VARCHAR(60) NULL, ADD COLUMN tgl_lahir DATE NULL,
  ADD COLUMN jenis_kelamin VARCHAR(20) NULL, ADD COLUMN agama VARCHAR(20) NULL, ADD COLUMN pekerjaan VARCHAR(60) NULL;
ALTER TABLE surat MODIFY jenis VARCHAR(60) NOT NULL, MODIFY no_surat VARCHAR(80) NULL, ADD COLUMN data_json TEXT NULL;

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
  token CHAR(24) NOT NULL UNIQUE,
  penandatangan VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (templat_id) REFERENCES templat_surat(id) ON DELETE SET NULL,
  FOREIGN KEY (surat_id) REFERENCES surat(id) ON DELETE SET NULL,
  FOREIGN KEY (warga_id) REFERENCES warga(id) ON DELETE SET NULL
) ENGINE=InnoDB;

UPDATE warga SET tempat_lahir='Surabaya', tgl_lahir='1980-05-14', jenis_kelamin='Laki-laki', agama='Islam', pekerjaan='Karyawan Swasta' WHERE id=1;
UPDATE warga SET tempat_lahir='Malang', tgl_lahir='1985-09-02', jenis_kelamin='Laki-laki', agama='Islam', pekerjaan='Wiraswasta' WHERE id=2;

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
