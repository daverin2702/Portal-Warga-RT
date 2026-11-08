USE db_rt;
-- Jalankan SEKALI pada database versi 6. Installer menjalankannya otomatis.
ALTER TABLE surat_terbit ADD COLUMN ttd_qr TINYINT(1) NOT NULL DEFAULT 1, ADD COLUMN keperluan VARCHAR(250) NULL;
UPDATE surat_terbit st JOIN surat s ON s.id=st.surat_id SET st.keperluan=s.keperluan WHERE st.keperluan IS NULL;
