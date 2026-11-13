<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expect_error(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (InputError) {
        return;
    }
    throw new RuntimeException($message);
}

$registration = validate_registration([
    'fullName' => '  Siti Rahma  ',
    'nim' => ' 12345678 ',
    'password' => 'secure-password',
    'passwordConfirm' => 'secure-password',
]);
expect($registration['fullName'] === 'Siti Rahma', 'Nama registrasi harus dinormalisasi.');
expect($registration['nim'] === '12345678', 'NPM harus dinormalisasi.');

expect_error(
    static fn () => normalize_nim('12A456'),
    'NPM yang tidak valid harus ditolak.',
);
expect_error(
    static fn () => validate_registration([
        'fullName' => 'Siti Rahma',
        'nim' => '12345678',
        'password' => 'short',
        'passwordConfirm' => 'short',
    ]),
    'Kata sandi pendek harus ditolak.',
);
expect_error(
    static fn () => validate_registration([
        'fullName' => 'Siti Rahma',
        'nim' => '12345678',
        'password' => str_repeat('é', 37),
        'passwordConfirm' => str_repeat('é', 37),
    ]),
    'Kata sandi di atas batas bcrypt harus ditolak.',
);

$report = validate_report([
    'title' => 'Permohonan pendampingan',
    'category' => REPORT_CATEGORIES[2],
    'incidentDate' => '2025-02-01',
    'chronology' => 'Penjelasan kejadian yang cukup panjang.',
    'anonymous' => 'true',
]);
expect($report['anonymous'] === true, 'Pilihan anonim harus dipertahankan.');
expect($report['category'] === 'Akademik & Etik', 'Kategori laporan harus dipertahankan.');

$validReport = [
    'title' => 'Laporan',
    'category' => REPORT_CATEGORIES[0],
    'incidentDate' => '2025-02-01',
    'chronology' => 'Penjelasan kejadian yang cukup panjang.',
];
expect_error(
    static fn () => validate_report(array_replace($validReport, ['category' => 'Kategori palsu'])),
    'Kategori yang tidak dikenal harus ditolak.',
);
expect_error(
    static fn () => validate_report(array_replace($validReport, ['incidentDate' => '2025-02-30'])),
    'Tanggal kalender yang tidak valid harus ditolak.',
);
expect_error(
    static fn () => validate_report(array_replace($validReport, ['chronology' => 'Terlalu singkat'])),
    'Kronologi pendek harus ditolak.',
);

$reportFilters = validate_report_filters([
    'search' => '  beasiswa  ',
    'status' => 'pending',
    'category' => REPORT_CATEGORIES[0],
    'dateFrom' => '2025-01-01',
    'dateTo' => '2025-01-31',
]);
expect($reportFilters['search'] === 'beasiswa', 'Pencarian laporan harus dinormalisasi.');
expect($reportFilters['status'] === 'pending', 'Filter status harus dipertahankan.');
expect($reportFilters['dateFrom'] === '2025-01-01', 'Filter tanggal awal harus dipertahankan.');
expect_error(
    static fn () => validate_report_filters(['status' => 'closed']),
    'Status filter di luar daftar harus ditolak.',
);
expect_error(
    static fn () => validate_report_filters(['dateFrom' => '2025-02-30']),
    'Tanggal filter yang tidak valid harus ditolak.',
);
expect_error(
    static fn () => validate_report_filters(['dateFrom' => '2025-02-10', 'dateTo' => '2025-02-01']),
    'Rentang tanggal terbalik harus ditolak.',
);

$announcement = validate_announcement([
    'title' => 'Jam layanan',
    'body' => 'Layanan tersedia pada jam kerja.',
    'category' => ANNOUNCEMENT_CATEGORIES[2],
    'isPublished' => 'on',
    'isPinned' => 'on',
    'expiresAt' => '2025-12-01T10:30',
]);
expect($announcement['isPublished'], 'Status publikasi harus dikonversi dari checkbox.');
expect($announcement['isPinned'], 'Status pin harus dikonversi dari checkbox.');
expect($announcement['category'] === 'Beasiswa', 'Kategori pengumuman harus dipertahankan.');
expect(is_string($announcement['expiresAt']), 'Tanggal berakhir valid harus dinormalisasi.');
expect(
    !validate_announcement([
        'title' => 'Draf',
        'body' => 'Informasi layanan.',
        'category' => ANNOUNCEMENT_CATEGORIES[4],
    ])['isPublished'],
    'Checkbox yang tidak dipilih harus menghasilkan status draf.',
);
expect_error(
    static fn () => validate_announcement([
        'title' => 'Pengumuman',
        'body' => 'Informasi layanan.',
        'category' => ANNOUNCEMENT_CATEGORIES[4],
        'isPublished' => ['on'],
    ]),
    'Nilai checkbox publikasi yang bukan string on harus ditolak.',
);
expect_error(
    static fn () => validate_announcement([
        'title' => 'Pengumuman',
        'body' => 'Informasi layanan.',
        'category' => 'Kategori lain',
    ]),
    'Kategori pengumuman yang tidak dikenal harus ditolak.',
);
expect_error(
    static fn () => validate_announcement([
        'title' => 'Pengumuman',
        'body' => 'Informasi layanan.',
        'category' => ANNOUNCEMENT_CATEGORIES[0],
        'expiresAt' => '2025-02-30T10:30',
    ]),
    'Tanggal berakhir yang tidak valid harus ditolak.',
);
expect(
    validate_decision(['decision' => 'approved', 'note' => 'Sudah ditinjau.'])['decision'] === 'approved',
    'Keputusan admin yang didukung harus diterima.',
);
expect_error(
    static fn () => validate_decision(['decision' => 'pending']),
    'Keputusan di luar daftar harus ditolak.',
);
expect_error(
    static fn () => validate_decision(['decision' => 'approved', 'note' => '   ']),
    'Catatan pertimbangan hukum kosong harus ditolak.',
);
expect_error(
    static fn () => validate_decision(['decision' => 'rejected', 'note' => str_repeat('x', 2001)]),
    'Catatan pertimbangan hukum di atas batas harus ditolak.',
);

fwrite(STDOUT, "Seluruh pengujian validasi PHP berhasil.\n");
