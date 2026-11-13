-- ATTENDARA sample data for the target schema in attendara.sql.
-- Import this file ONCE after creating an empty schema.
-- Demo logins: admin/admin and guru1/guru1 through guru5/guru5.
-- Password hashes were generated with PHP password_hash(..., PASSWORD_DEFAULT).
-- Demo passwords are intentionally simple; never use these accounts in production.

USE attendara;

START TRANSACTION;

INSERT INTO users (id, username, password_hash, role, is_active) VALUES
(1, 'admin', '$2y$12$saPT7R7q.RvS6gDUSxtU1eObtTUD6gb6facFGFwMPw.g59IN.Sncy', 'admin', TRUE),
(2, 'guru1', '$2y$12$cfOyUrX44u6iht21Zus17O8IocYRLzeohCchK9kpvYYZebnNHKK4C', 'guru', TRUE),
(3, 'guru2', '$2y$12$m4xfuInal2IWFTI8ECW3XOrm.bmjOjDhPe0oFna/8rBudtEKj9II2', 'guru', TRUE),
(4, 'guru3', '$2y$12$o9ZVkkUkf8RrzUiUcYaoyObvkE70dP2QAAtsIZVQ2CwDIgm8G7tg', 'guru', TRUE),
(5, 'guru4', '$2y$12$FCua6ne.ZSL3Oq58cM2aK.Iet9Wtng4N4Z5HucPy9oLKvuT5uzFby', 'guru', TRUE),
(6, 'guru5', '$2y$12$UHcfzx8Xj7VA4vw4I0W059eTmUpRZd08QWWSDK0z25zefx6q.sjzEO', 'guru', TRUE);

INSERT INTO guru (id, user_id, nomor_induk, nama, email, no_telepon, is_active) VALUES
(1, 2, 'G-2026-001', 'Andi Pratama, S.Pd.', 'andi.pratama@example.test', '081200000101', TRUE),
(2, 3, 'G-2026-002', 'Siti Rahmawati, S.Pd.', 'siti.rahmawati@example.test', '081200000102', TRUE),
(3, 4, 'G-2026-003', 'Budi Santoso, S.Pd.', 'budi.santoso@example.test', '081200000103', TRUE),
(4, 5, 'G-2026-004', 'Dewi Anggraini, S.Pd.', 'dewi.anggraini@example.test', '081200000104', TRUE),
(5, 6, 'G-2026-005', 'Rizky Hidayat, S.Kom.', 'rizky.hidayat@example.test', '081200000105', TRUE);

INSERT INTO siswa (id, nomor_induk, nama, jenis_kelamin, tanggal_lahir, is_active) VALUES
(1, 'SMA-2026-001', 'Ahmad Fauzi', 'L', '2010-01-14', TRUE),
(2, 'SMA-2026-002', 'Rizal Ramadhan', 'L', '2010-02-21', TRUE),
(3, 'SMA-2026-003', 'Dimas Saputra', 'L', '2010-03-08', TRUE),
(4, 'SMA-2026-004', 'Fajar Nugraha', 'L', '2010-04-17', TRUE),
(5, 'SMA-2026-005', 'Arif Hidayat', 'L', '2010-05-03', TRUE),
(6, 'SMA-2026-006', 'Bagas Pratama', 'L', '2010-06-12', TRUE),
(7, 'SMA-2026-007', 'Ilham Maulana', 'L', '2010-07-26', TRUE),
(8, 'SMA-2026-008', 'Reza Firmansyah', 'L', '2010-08-19', TRUE),
(9, 'SMA-2026-009', 'Yoga Kurniawan', 'L', '2010-09-11', TRUE),
(10, 'SMA-2026-010', 'Naufal Akbar', 'L', '2010-10-05', TRUE),
(11, 'SMA-2026-011', 'Farhan Hakim', 'L', '2010-11-22', TRUE),
(12, 'SMA-2026-012', 'Aldi Setiawan', 'L', '2010-12-09', TRUE),
(13, 'SMA-2026-013', 'Rafi Alamsyah', 'L', '2010-01-29', TRUE),
(14, 'SMA-2026-014', 'Galih Permana', 'L', '2010-02-13', TRUE),
(15, 'SMA-2026-015', 'Daffa Putra', 'L', '2010-03-24', TRUE),
(16, 'SMA-2026-016', 'Bima Arya', 'L', '2010-04-06', TRUE),
(17, 'SMA-2026-017', 'Kevin Wijaya', 'L', '2010-05-28', TRUE),
(18, 'SMA-2026-018', 'Zidan Maulana', 'L', '2010-06-15', TRUE),
(19, 'SMA-2026-019', 'Aisyah Putri', 'P', '2010-01-18', TRUE),
(20, 'SMA-2026-020', 'Nabila Safitri', 'P', '2010-02-27', TRUE),
(21, 'SMA-2026-021', 'Putri Maharani', 'P', '2010-03-12', TRUE),
(22, 'SMA-2026-022', 'Zahra Amelia', 'P', '2010-04-23', TRUE),
(23, 'SMA-2026-023', 'Intan Permatasari', 'P', '2010-05-16', TRUE),
(24, 'SMA-2026-024', 'Citra Lestari', 'P', '2010-06-07', TRUE),
(25, 'SMA-2026-025', 'Alya Rahma', 'P', '2010-07-20', TRUE),
(26, 'SMA-2026-026', 'Dinda Aprilia', 'P', '2010-08-03', TRUE),
(27, 'SMA-2026-027', 'Salsabila Nurfadila', 'P', '2010-09-14', TRUE),
(28, 'SMA-2026-028', 'Annisa Khairunnisa', 'P', '2010-10-25', TRUE),
(29, 'SMA-2026-029', 'Tiara Anindya', 'P', '2010-11-08', TRUE),
(30, 'SMA-2026-030', 'Nadia Oktaviani', 'P', '2010-12-19', TRUE),
(31, 'SMA-2026-031', 'Rara Puspitasari', 'P', '2010-01-07', TRUE),
(32, 'SMA-2026-032', 'Farah Zahra', 'P', '2010-02-16', TRUE),
(33, 'SMA-2026-033', 'Mutiara Sari', 'P', '2010-03-29', TRUE),
(34, 'SMA-2026-034', 'Sheila Anjani', 'P', '2010-04-11', TRUE),
(35, 'SMA-2026-035', 'Fitria Kusuma', 'P', '2010-05-22', TRUE),
(36, 'SMA-2026-036', 'Vina Anggraini', 'P', '2010-06-30', TRUE),
(37, 'SMA-2026-037', 'Arkan Prakoso', 'L', '2010-07-13', TRUE),
(38, 'SMA-2026-038', 'Rangga Firmanda', 'L', '2010-08-24', TRUE),
(39, 'SMA-2026-039', 'Fikri Ramadhan', 'L', '2010-09-06', TRUE),
(40, 'SMA-2026-040', 'Hafiz Maulana', 'L', '2010-10-17', TRUE),
(41, 'SMA-2026-041', 'Raka Wibowo', 'L', '2010-11-29', TRUE),
(42, 'SMA-2026-042', 'Dhani Saputra', 'L', '2010-12-12', TRUE),
(43, 'SMA-2026-043', 'Laras Wulandari', 'P', '2010-07-04', TRUE),
(44, 'SMA-2026-044', 'Mega Pertiwi', 'P', '2010-08-15', TRUE),
(45, 'SMA-2026-045', 'Salma Nurfauziah', 'P', '2010-09-27', TRUE),
(46, 'SMA-2026-046', 'Niken Maharani', 'P', '2010-10-09', TRUE),
(47, 'SMA-2026-047', 'Gita Apriliani', 'P', '2010-11-18', TRUE),
(48, 'SMA-2026-048', 'Kirana Puspita', 'P', '2010-12-26', TRUE);

INSERT INTO mata_pelajaran (id, kode, nama, is_active) VALUES
(1, 'MAT', 'Matematika', TRUE),
(2, 'BIN', 'Bahasa Indonesia', TRUE),
(3, 'FIS', 'Fisika', TRUE),
(4, 'BIO', 'Biologi', TRUE),
(5, 'BIG', 'Bahasa Inggris', TRUE),
(6, 'INF', 'Informatika', TRUE),
(7, 'KIM', 'Kimia', TRUE);

INSERT INTO semester (id, kode, nama, tanggal_mulai, tanggal_selesai, status) VALUES
(1, '2025-GENAP', 'Tahun Ajaran 2025/2026 — Genap', '2026-01-05', '2026-06-30', 'selesai'),
(2, '2026-GANJIL', 'Tahun Ajaran 2026/2027 — Ganjil', '2026-07-13', '2026-12-19', 'aktif');

INSERT INTO kelas (id, semester_id, kode, nama, tingkat, is_active) VALUES
(1, 1, 'X-IPA-1', 'X IPA 1', 10, TRUE),
(2, 2, 'XI-IPA-1', 'XI IPA 1', 11, TRUE),
(3, 2, 'XI-IPS-1', 'XI IPS 1', 11, TRUE);

-- Siswa angkatan kelas XI memiliki riwayat kelas X pada semester sebelumnya.
INSERT INTO anggota_kelas (siswa_id, kelas_id, semester_id)
SELECT id, 1, 1 FROM siswa WHERE id BETWEEN 1 AND 36;

INSERT INTO anggota_kelas (siswa_id, kelas_id, semester_id)
SELECT id, 2, 2 FROM siswa WHERE id BETWEEN 1 AND 36;

INSERT INTO anggota_kelas (siswa_id, kelas_id, semester_id)
SELECT id, 3, 2 FROM siswa WHERE id BETWEEN 37 AND 48;

-- Guru dapat memiliki banyak penugasan; setiap baris adalah satu guru/mapel/kelas.
INSERT INTO penugasan_guru (id, guru_id, mata_pelajaran_id, kelas_id) VALUES
(1, 1, 1, 2),  -- Andi: Matematika, XI IPA 1
(2, 1, 1, 3),  -- Andi: Matematika, XI IPS 1
(3, 2, 2, 2),  -- Siti: Bahasa Indonesia, XI IPA 1
(4, 2, 2, 3),  -- Siti: Bahasa Indonesia, XI IPS 1
(5, 3, 3, 2),  -- Budi: Fisika, XI IPA 1
(6, 3, 1, 3),  -- Budi: Matematika, XI IPS 1
(7, 4, 4, 2),  -- Dewi: Biologi, XI IPA 1
(8, 4, 7, 2),  -- Dewi: Kimia, XI IPA 1
(9, 4, 4, 3),  -- Dewi: Biologi, XI IPS 1
(10, 5, 5, 2), -- Rizky: Bahasa Inggris, XI IPA 1
(11, 5, 5, 3), -- Rizky: Bahasa Inggris, XI IPS 1
(12, 5, 6, 2), -- Rizky: Informatika, XI IPA 1
(13, 1, 1, 1); -- Riwayat Andi: Matematika, X IPA 1

INSERT INTO jadwal_mengajar
  (id, penugasan_guru_id, hari, jam_mulai, jam_selesai, berlaku_mulai, berlaku_sampai)
VALUES
(1, 1, WEEKDAY(CURRENT_DATE()) + 1, '07:00:00', '08:30:00', '2026-07-13', '2026-12-19'),
(2, 2, 3, '09:00:00', '10:30:00', '2026-07-13', '2026-12-19'),
(3, 3, 2, '09:00:00', '10:30:00', '2026-07-13', '2026-12-19'),
(4, 4, 4, '07:00:00', '08:30:00', '2026-07-13', '2026-12-19'),
(5, 5, 1, '09:00:00', '10:30:00', '2026-07-13', '2026-12-19'),
(6, 6, 5, '07:00:00', '08:30:00', '2026-07-13', '2026-12-19'),
(7, 7, 2, '13:00:00', '14:30:00', '2026-07-13', '2026-12-19'),
(8, 8, 4, '13:00:00', '14:30:00', '2026-07-13', '2026-12-19'),
(9, 9, 5, '13:00:00', '14:30:00', '2026-07-13', '2026-12-19'),
(10, 10, 3, '07:00:00', '08:30:00', '2026-07-13', '2026-12-19'),
(11, 11, 1, '13:00:00', '14:30:00', '2026-07-13', '2026-12-19'),
(12, 12, 4, '09:00:00', '10:30:00', '2026-07-13', '2026-12-19'),
(13, 13, 1, '07:00:00', '08:30:00', '2026-01-05', '2026-06-30');

-- Sesi aktif demo untuk kelas berisi 36 siswa: 34 tap dan dua status non-hadir.
INSERT INTO sesi_pembelajaran
  (id, jadwal_mengajar_id, mulai_at, selesai_at, status, jumlah_tap, tap_terakhir_at)
VALUES
(1, 1, CURRENT_TIMESTAMP(), NULL, 'berlangsung', 34, CURRENT_TIMESTAMP()),
(2, 13, '2026-04-13 07:05:00', '2026-04-13 08:25:00', 'selesai', 33, NULL);

INSERT INTO absensi
  (sesi_pembelajaran_id, siswa_id, status, dicatat_oleh_user_id, waktu_dicatat, catatan)
SELECT 1, ak.siswa_id,
       CASE ak.siswa_id
         WHEN 35 THEN 'izin'
         WHEN 36 THEN 'alpha'
         ELSE 'hadir'
       END,
       2, CURRENT_TIMESTAMP(),
       CASE ak.siswa_id
         WHEN 35 THEN 'Izin keluarga'
         WHEN 36 THEN 'Belum ada keterangan'
         ELSE NULL
       END
FROM anggota_kelas ak
WHERE ak.kelas_id = 2 AND ak.semester_id = 2;

INSERT INTO absensi
  (sesi_pembelajaran_id, siswa_id, status, dicatat_oleh_user_id, waktu_dicatat, catatan)
SELECT 2, ak.siswa_id,
       CASE ak.siswa_id
         WHEN 5 THEN 'sakit'
         WHEN 17 THEN 'izin'
         WHEN 28 THEN 'alpha'
         ELSE 'hadir'
       END,
       2, '2026-04-13 07:10:00',
       CASE ak.siswa_id
         WHEN 5 THEN 'Surat keterangan diserahkan ke sekolah'
         WHEN 17 THEN 'Izin kegiatan keluarga'
         WHEN 28 THEN 'Tidak hadir'
         ELSE NULL
       END
FROM anggota_kelas ak
WHERE ak.kelas_id = 1 AND ak.semester_id = 1;

INSERT INTO kelompok (id, penugasan_guru_id, nama) VALUES
(1, 1, 'Kelompok Aljabar'),
(2, 1, 'Kelompok Geometri'),
(3, 1, 'Kelompok Statistika'),
(4, 1, 'Kelompok Eksplorasi');

-- Four balanced groups: each receives four or five students of each gender.
INSERT INTO anggota_kelompok (kelompok_id, siswa_id)
SELECT CASE
         WHEN siswa_id BETWEEN 1 AND 5 OR siswa_id BETWEEN 19 AND 22 THEN 1
         WHEN siswa_id BETWEEN 6 AND 10 OR siswa_id BETWEEN 23 AND 26 THEN 2
         WHEN siswa_id BETWEEN 11 AND 14 OR siswa_id BETWEEN 27 AND 31 THEN 3
         WHEN siswa_id BETWEEN 15 AND 18 OR siswa_id BETWEEN 32 AND 36 THEN 4
       END,
       siswa_id
FROM anggota_kelas
WHERE kelas_id = 2 AND semester_id = 2;

INSERT INTO poin_keaktifan
  (sesi_pembelajaran_id, siswa_id, poin, dicatat_oleh_user_id, waktu_dicatat, catatan)
VALUES
(1, 1, 5.00, 2, CURRENT_TIMESTAMP(), 'Menjelaskan langkah penyelesaian di papan tulis'),
(1, 19, 3.00, 2, CURRENT_TIMESTAMP(), 'Membantu kelompok memeriksa jawaban'),
(2, 8, 2.00, 2, '2026-04-13 08:00:00', 'Aktif menjawab pertanyaan');

COMMIT;
