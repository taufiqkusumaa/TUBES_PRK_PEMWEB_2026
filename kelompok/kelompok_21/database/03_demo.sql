-- OPSIONAL: data fiktif untuk database baru/kosong. Jalankan sekali.
-- Semua akun demo memakai password Arunika123! Ganti melalui Profil saya.
USE akademik;
START TRANSACTION;
INSERT INTO GURU (id_guru, nip, nama_guru, email, password, peran) VALUES
(1, 'DEMO001', 'Admin Sekolah', 'admin@arunika.test', '$2b$12$lunrH3RbibYAsPsrmearVexzTG8I8C3pzet9W4kS1nbbYtOjdheUS', 'admin'),
(2, 'DEMO002', 'Endy Yoga', 'endy@arunika.test', '$2b$12$lunrH3RbibYAsPsrmearVexzTG8I8C3pzet9W4kS1nbbYtOjdheUS', 'guru'),
(3, 'DEMO003', 'Rio', 'rio@arunika.test', '$2b$12$lunrH3RbibYAsPsrmearVexzTG8I8C3pzet9W4kS1nbbYtOjdheUS', 'guru');
INSERT INTO KELAS (id_kelas, id_wali_kelas, nama_kelas, tingkat) VALUES
(1, 2, 'X IPA 1', 'X'), (2, 3, 'X IPA 2', 'X'), (3, 2, 'XI IPA 1', 'XI');
INSERT INTO MATA_PELAJARAN (id_mapel, kode_mapel, nama_mapel) VALUES
(1, 'MTK', 'Matematika'), (2, 'BIN', 'Bahasa Indonesia'), (3, 'INF', 'Informatika');
INSERT INTO PERIODE_PENILAIAN (id_periode, tahun_ajaran, semester, status_aktif) VALUES
(1, '2026/2027', 'Ganjil', 1), (2, '2026/2027', 'Genap', 0);
INSERT INTO PENUGASAN_MENGAJAR (id_penugasan, id_guru, id_kelas, id_mapel, id_periode) VALUES
(1, 2, 1, 3, 1), (2, 2, 3, 3, 1), (3, 3, 2, 1, 1);
INSERT INTO SISWA (id_siswa, id_kelas, nisn, nama_siswa, jenis_kelamin) VALUES
(1,1,'0000000001','Aditya Pratama','Laki-laki'), (2,1,'0000000002','Alya Putri','Perempuan'),
(3,1,'0000000003','Bima Saputra','Laki-laki'), (4,1,'0000000004','Citra Lestari','Perempuan'),
(5,2,'0000000005','Daffa Ramadhan','Laki-laki'), (6,2,'0000000006','Dinda Kirana','Perempuan'),
(7,3,'0000000007','Fajar Nugraha','Laki-laki'), (8,3,'0000000008','Nadia Maharani','Perempuan');

INSERT INTO PENILAIAN (id_penilaian, id_penugasan, judul_penilaian, jenis_komponen, tenggat_waktu, tanggal_dibuat) VALUES
(1, 1, 'Tugas Rekapitulasi Bab 1', 'Tugas', '2026-10-15 23:59:00', '2026-10-01 08:00:00'),
(2, 1, 'Ujian Akhir Semester Ganjil', 'UAS', '2026-11-20 12:00:00', '2026-11-01 08:00:00')
ON DUPLICATE KEY UPDATE judul_penilaian = VALUES(judul_penilaian);

INSERT INTO NILAI_DAN_PENGUMPULAN (id_siswa, id_penilaian, waktu_pengumpulan, status_pengumpulan, nilai) VALUES
(1, 1, '2026-10-14 10:00:00', 'Tepat Waktu', 95),
(1, 2, '2026-11-20 10:00:00', 'Tepat Waktu', 90),
(2, 1, '2026-10-14 11:00:00', 'Tepat Waktu', 85),
(2, 2, '2026-11-20 10:30:00', 'Tepat Waktu', 88),
(3, 1, '2026-10-15 09:00:00', 'Tepat Waktu', 90),
(3, 2, '2026-11-20 11:00:00', 'Tepat Waktu', 60),
(4, 1, '2026-10-16 10:00:00', 'Terlambat', 55),
(4, 2, '2026-11-20 11:15:00', 'Tepat Waktu', 62),
(5, 1, '2026-10-17 12:00:00', 'Terlambat', 65),
(5, 2, '2026-11-20 10:45:00', 'Tepat Waktu', 85),
(6, 1, '2026-10-18 14:00:00', 'Terlambat', 50),
(6, 2, '2026-11-20 11:30:00', 'Tepat Waktu', 55),
(7, 1, '2026-10-13 08:00:00', 'Tepat Waktu', 92),
(7, 2, '2026-11-20 09:45:00', 'Tepat Waktu', 94),
(8, 1, '2026-10-16 15:00:00', 'Terlambat', 60),
(8, 2, '2026-11-20 10:15:00', 'Tepat Waktu', 88)
ON DUPLICATE KEY UPDATE nilai = VALUES(nilai);
select * from nilai_dan_pengumpulan;


COMMIT;
