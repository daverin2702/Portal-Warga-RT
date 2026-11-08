USE db_rt;
-- Jalankan SEKALI pada database versi 2 (yang sudah punya tabel templat_surat).
ALTER TABLE users ADD COLUMN jabatan VARCHAR(30) NOT NULL DEFAULT 'warga', ADD COLUMN foto VARCHAR(80) NULL;
ALTER TABLE warga ADD COLUMN blok VARCHAR(20) NULL, ADD COLUMN no_rumah VARCHAR(10) NULL;
UPDATE users SET jabatan='ketua' WHERE username='rt';
