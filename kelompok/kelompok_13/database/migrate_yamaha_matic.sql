USE bengkel_db;

SET @yamaha_matic_id = (
    SELECT id
    FROM kategori_kendaraan
    WHERE jenis = 'Roda 2' AND LOWER(merk) = 'yamaha' AND LOWER(tipe) = 'matic'
    ORDER BY id
    LIMIT 1
);

INSERT INTO kategori_kendaraan (jenis, merk, tipe)
SELECT 'Roda 2', 'Yamaha', 'Matic'
WHERE @yamaha_matic_id IS NULL;

SET @yamaha_matic_id = (
    SELECT id
    FROM kategori_kendaraan
    WHERE jenis = 'Roda 2' AND LOWER(merk) = 'yamaha' AND LOWER(tipe) = 'matic'
    ORDER BY id
    LIMIT 1
);
SET @yamaha_oil_service_id = (
    SELECT id
    FROM layanan_servis
    WHERE kategori_id = @yamaha_matic_id
      AND nama_paket = 'Ganti Oli Mesin & Gardan'
    ORDER BY id
    LIMIT 1
);

INSERT INTO layanan_servis (
    kategori_id,
    nama_paket,
    deskripsi,
    tarif_dasar,
    estimasi_durasi_menit,
    status_layanan
)
SELECT
    @yamaha_matic_id,
    'Ganti Oli Mesin & Gardan',
    'Penggantian oli mesin dan oli gardan untuk motor matic Yamaha',
    75000.00,
    20,
    'Tersedia'
WHERE @yamaha_matic_id IS NOT NULL AND @yamaha_oil_service_id IS NULL;

INSERT INTO layanan_servis (
    kategori_id,
    nama_paket,
    deskripsi,
    tarif_dasar,
    estimasi_durasi_menit,
    status_layanan
)
SELECT
    @yamaha_matic_id,
    'Tune Up Injeksi Motor',
    'Pembersihan injektor, throttle body, filter udara, dan pemeriksaan sistem injeksi',
    85000.00,
    45,
    'Tersedia'
WHERE @yamaha_matic_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM layanan_servis
      WHERE kategori_id = @yamaha_matic_id
        AND nama_paket = 'Tune Up Injeksi Motor'
  );

INSERT INTO layanan_servis (
    kategori_id,
    nama_paket,
    deskripsi,
    tarif_dasar,
    estimasi_durasi_menit,
    status_layanan
)
SELECT
    @yamaha_matic_id,
    'Kalibrasi Throttle Body & Reset TP',
    'Penyetelan throttle body dan reset parameter sensor ECU',
    50000.00,
    30,
    'Tersedia'
WHERE @yamaha_matic_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM layanan_servis
      WHERE kategori_id = @yamaha_matic_id
        AND nama_paket = 'Kalibrasi Throttle Body & Reset TP'
  );

INSERT INTO layanan_servis (
    kategori_id,
    nama_paket,
    deskripsi,
    tarif_dasar,
    estimasi_durasi_menit,
    status_layanan
)
SELECT
    @yamaha_matic_id,
    'Skir Klep & Penyetelan Celah Katup',
    'Pembersihan dudukan klep dan penyetelan ulang celah katup',
    175000.00,
    120,
    'Tersedia'
WHERE @yamaha_matic_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM layanan_servis
      WHERE kategori_id = @yamaha_matic_id
        AND nama_paket = 'Skir Klep & Penyetelan Celah Katup'
  );
