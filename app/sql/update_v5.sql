USE db_rt;
-- Jalankan SEKALI pada database versi 4. Installer menjalankannya otomatis.
CREATE TABLE IF NOT EXISTS jenis_tagihan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(60) NOT NULL UNIQUE,
  keterangan VARCHAR(150),
  nominal INT NOT NULL DEFAULT 0,
  alokasi VARCHAR(80) NOT NULL DEFAULT 'Kas umum',
  wajib TINYINT(1) NOT NULL DEFAULT 0,
  aktif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
INSERT IGNORE INTO jenis_tagihan (nama,keterangan,nominal,alokasi,wajib) VALUES
 ('IPL','Iuran Pengelolaan Lingkungan: keamanan, kebersihan, dan sampah',100000,'IPL',1),
 ('Kas RT','Iuran kas RT untuk kegiatan dan operasional',25000,'Kas umum',1);
