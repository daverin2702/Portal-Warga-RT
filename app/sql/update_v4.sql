USE db_rt;
-- Jalankan SEKALI pada database versi 3 (yang sudah punya kolom jabatan dan blok).
ALTER TABLE users ADD COLUMN ganti_sandi TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE warga MODIFY no_kk VARCHAR(20) NULL, MODIFY nik_kepala VARCHAR(20) NULL, MODIFY alamat VARCHAR(200) NULL, ADD COLUMN email VARCHAR(100) NULL;
ALTER TABLE tagihan MODIFY status ENUM('belum','menunggu','lunas') NOT NULL DEFAULT 'belum', ADD COLUMN bukti VARCHAR(80) NULL, ADD COLUMN catatan VARCHAR(200) NULL, ADD COLUMN tgl_upload DATETIME NULL;
ALTER TABLE surat MODIFY status ENUM('diajukan','diteruskan','diperiksa','disetujui','ditolak') NOT NULL DEFAULT 'diajukan', ADD COLUMN alur TEXT NULL;
ALTER TABLE surat_terbit ADD COLUMN status ENUM('draf','diperiksa','terbit','ditolak') NOT NULL DEFAULT 'terbit', ADD COLUMN alur TEXT NULL;
ALTER TABLE kas ADD COLUMN status ENUM('diajukan','diperiksa','disetujui','ditolak') NOT NULL DEFAULT 'disetujui', ADD COLUMN alur TEXT NULL;

INSERT IGNORE INTO users (username,password,nama,role,warga_id,jabatan,ganti_sandi) VALUES
 ('wakil','$2y$12$O91zCi60zN2CSPknHRUI4OxmLtZThftU6I9WPpjIKq5AoxnOfRupi','Wakil Ketua RT','rt',NULL,'wakil',1),
 ('sekretaris','$2y$12$3LCKdHL9ZNCWjqKODbB/p./w17H9VHu1LKRHEYmtE5gMOPWXKaTqe','Sekretaris RT','rt',NULL,'sekretaris',1),
 ('bendahara','$2y$12$7iKjEA.BNAcfaJbi80IW7OnquIGP53PjXbs4hbQokybwEV25fuobS','Bendahara RT','rt',NULL,'bendahara',1);
