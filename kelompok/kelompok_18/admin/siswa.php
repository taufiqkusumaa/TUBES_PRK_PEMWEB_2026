<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/** Read a trimmed string value from the POST body. */
function siswa_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Send a flash message and return to the student list (Post/Redirect/Get). */
function siswa_redirect_with_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: siswa.php');
    exit;
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
if ($editId < 0) {
    $editId = 0;
}

$detailId = isset($_GET['detail']) ? (int) $_GET['detail'] : 0;
if ($detailId < 0) {
    $detailId = 0;
}

// ---------------------------------------------------------------
// Handle POST actions (save and toggle). CSRF is mandatory.
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Permintaan tidak valid.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $siswaId = (int) ($_POST['siswa_id'] ?? 0);

        try {
            $statement = $pdo->prepare('SELECT is_active FROM siswa WHERE id = :id');
            $statement->execute(['id' => $siswaId]);
            $target = $statement->fetch();

            if (!is_array($target)) {
                siswa_redirect_with_flash('error', 'Data siswa tidak ditemukan.');
            }

            $newStatus = !(bool) $target['is_active'];
            $statement = $pdo->prepare('UPDATE siswa SET is_active = :active WHERE id = :id');
            $statement->execute(['active' => $newStatus ? 1 : 0, 'id' => $siswaId]);

            siswa_redirect_with_flash(
                'success',
                $newStatus ? 'Siswa berhasil diaktifkan.' : 'Siswa berhasil dinonaktifkan.'
            );
        } catch (PDOException $exception) {
            error_log('ATTENDARA toggle siswa failed: ' . $exception->getMessage());
            siswa_redirect_with_flash('error', 'Status siswa gagal diubah.');
        }
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $nomorInduk = siswa_post_string('nomor_induk');
        $nama = siswa_post_string('nama');
        $jenisKelamin = siswa_post_string('jenis_kelamin');
        $tanggalLahir = siswa_post_string('tanggal_lahir');

        // Server-side validation.
        if ($nomorInduk === '') {
            $errors[] = 'Nomor induk wajib diisi.';
        } elseif (mb_strlen($nomorInduk) > 50) {
            $errors[] = 'Nomor induk maksimal 50 karakter.';
        }

        if ($nama === '') {
            $errors[] = 'Nama wajib diisi.';
        } elseif (mb_strlen($nama) > 150) {
            $errors[] = 'Nama maksimal 150 karakter.';
        }

        if ($jenisKelamin !== '' && mb_strlen($jenisKelamin) > 20) {
            $errors[] = 'Jenis kelamin maksimal 20 karakter.';
        }

        $tanggalLahirValue = null;
        if ($tanggalLahir !== '') {
            $date = DateTime::createFromFormat('Y-m-d', $tanggalLahir);
            $isValidDate = $date instanceof DateTime && $date->format('Y-m-d') === $tanggalLahir;
            if (!$isValidDate) {
                $errors[] = 'Format tanggal lahir tidak valid.';
            } else {
                $tanggalLahirValue = $tanggalLahir;
            }
        }

        // Uniqueness check with a friendly message.
        if ($errors === []) {
            $sql = 'SELECT COUNT(*) FROM siswa WHERE nomor_induk = :nomor_induk';
            $params = ['nomor_induk' => $nomorInduk];
            if ($id > 0) {
                $sql .= ' AND id <> :id';
                $params['id'] = $id;
            }
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Nomor induk sudah digunakan.';
            }
        }

        if ($errors === []) {
            $jenisKelaminValue = $jenisKelamin === '' ? null : $jenisKelamin;

            try {
                if ($id === 0) {
                    $statement = $pdo->prepare(
                        'INSERT INTO siswa (nomor_induk, nama, jenis_kelamin, tanggal_lahir, is_active)
                         VALUES (:nomor_induk, :nama, :jenis_kelamin, :tanggal_lahir, TRUE)'
                    );
                    $statement->execute([
                        'nomor_induk' => $nomorInduk,
                        'nama' => $nama,
                        'jenis_kelamin' => $jenisKelaminValue,
                        'tanggal_lahir' => $tanggalLahirValue,
                    ]);
                    siswa_redirect_with_flash('success', 'Siswa berhasil ditambahkan.');
                } else {
                    $statement = $pdo->prepare(
                        'UPDATE siswa
                         SET nomor_induk = :nomor_induk, nama = :nama,
                             jenis_kelamin = :jenis_kelamin, tanggal_lahir = :tanggal_lahir
                         WHERE id = :id'
                    );
                    $statement->execute([
                        'nomor_induk' => $nomorInduk,
                        'nama' => $nama,
                        'jenis_kelamin' => $jenisKelaminValue,
                        'tanggal_lahir' => $tanggalLahirValue,
                        'id' => $id,
                    ]);
                    siswa_redirect_with_flash('success', 'Data siswa berhasil diperbarui.');
                }
            } catch (PDOException $exception) {
                error_log('ATTENDARA save siswa failed: ' . $exception->getMessage());
                $errors[] = $exception->getCode() === '23000'
                    ? 'Nomor induk sudah digunakan.'
                    : 'Data siswa gagal disimpan.';
            }
        }
    }
}

// ---------------------------------------------------------------
// Active semester is used to show the student's current class.
// ---------------------------------------------------------------
$statement = $pdo->prepare('SELECT id, nama FROM semester WHERE status = :status LIMIT 1');
$statement->execute(['status' => 'aktif']);
$activeSemester = $statement->fetch() ?: null;

// ---------------------------------------------------------------
// Detail view: profile plus placement history, never overwritten.
// ---------------------------------------------------------------
if ($detailId > 0) {
    $statement = $pdo->prepare(
        'SELECT id, nomor_induk, nama, jenis_kelamin, tanggal_lahir, is_active, created_at, updated_at
         FROM siswa WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $detailId]);
    $detailSiswa = $statement->fetch();

    if (!is_array($detailSiswa)) {
        siswa_redirect_with_flash('error', 'Data siswa tidak ditemukan.');
    }

    $statement = $pdo->prepare(
        'SELECT sm.nama AS semester_nama, sm.status AS semester_status,
                k.nama AS kelas_nama, ak.created_at
         FROM anggota_kelas AS ak
         JOIN semester AS sm ON sm.id = ak.semester_id
         JOIN kelas AS k ON k.id = ak.kelas_id
         WHERE ak.siswa_id = :id
         ORDER BY sm.tanggal_mulai DESC, sm.id DESC'
    );
    $statement->execute(['id' => $detailId]);
    $riwayat = $statement->fetchAll();

    admin_page_start('Detail Siswa', 'siswa.php');
    ?>
    <section class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-[#303832]"><?= h((string) $detailSiswa['nama']) ?></h2>
                <p class="mt-1 text-sm text-slate-600">Nomor induk <?= h((string) $detailSiswa['nomor_induk']) ?></p>
            </div>
            <div class="flex items-center gap-2">
                <a href="siswa.php?edit=<?= h((string) $detailSiswa['id']) ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#41684f]">Edit</a>
                <a href="siswa.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#dfe6de] bg-white px-4 py-2 text-sm font-semibold text-[#41684f] hover:bg-[#eff3ee]">Kembali</a>
            </div>
        </div>

        <dl class="grid gap-4 rounded-2xl border border-[#dfe6de] bg-white p-5 sm:grid-cols-2 sm:p-6">
            <div><dt class="text-sm font-medium text-slate-500">Jenis kelamin</dt><dd class="mt-1 text-sm text-slate-800"><?= h((string) ($detailSiswa['jenis_kelamin'] ?? '-')) ?></dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Tanggal lahir</dt><dd class="mt-1 text-sm text-slate-800"><?= h((string) ($detailSiswa['tanggal_lahir'] ?? '-')) ?></dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Status</dt><dd class="mt-1 text-sm text-slate-800"><?= (bool) $detailSiswa['is_active'] ? 'Aktif' : 'Nonaktif' ?></dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Terdaftar</dt><dd class="mt-1 text-sm text-slate-800"><?= h((string) $detailSiswa['created_at']) ?></dd></div>
        </dl>

        <div class="space-y-3">
            <h3 class="text-base font-semibold text-[#303832]">Riwayat Penempatan Kelas</h3>
            <div class="overflow-x-auto rounded-2xl border border-[#dfe6de] bg-white">
                <table class="min-w-full divide-y divide-[#e6ebe5] text-left text-sm">
                    <thead class="bg-[#eff3ee] text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Semester</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Kelas</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Status Semester</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Dicatat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e6ebe5] text-slate-700">
                        <?php if ($riwayat === []): ?>
                            <tr><td colspan="4" class="px-4 py-9 text-center text-slate-500">Belum ada riwayat penempatan kelas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($riwayat as $item): ?>
                                <tr class="hover:bg-[#fafbf9]">
                                    <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $item['semester_nama']) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $item['kelas_nama']) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) $item['semester_status']) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) $item['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php
    admin_page_end();
    exit;
}

// ---------------------------------------------------------------
// Load data for the edit form and the table.
// ---------------------------------------------------------------
$editSiswa = null;
if ($editId > 0) {
    $statement = $pdo->prepare(
        'SELECT id, nomor_induk, nama, jenis_kelamin, tanggal_lahir
         FROM siswa WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $editId]);
    $editSiswa = $statement->fetch() ?: null;
}

if ($activeSemester !== null) {
    $statement = $pdo->prepare(
        'SELECT s.id, s.nomor_induk, s.nama, s.jenis_kelamin, s.tanggal_lahir, s.is_active,
                k.nama AS kelas_nama
         FROM siswa AS s
         LEFT JOIN anggota_kelas AS ak ON ak.siswa_id = s.id AND ak.semester_id = :semester_id
         LEFT JOIN kelas AS k ON k.id = ak.kelas_id
         ORDER BY s.nama'
    );
    $statement->execute(['semester_id' => (int) $activeSemester['id']]);
} else {
    $statement = $pdo->query(
        'SELECT id, nomor_induk, nama, jenis_kelamin, tanggal_lahir, is_active, NULL AS kelas_nama
         FROM siswa ORDER BY nama'
    );
}
$siswaList = $statement->fetchAll();

// Repopulate the form from the last POST when validation failed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && $errors !== []) {
    $formValues = [
        'id' => (int) ($_POST['id'] ?? 0),
        'nomor_induk' => siswa_post_string('nomor_induk'),
        'nama' => siswa_post_string('nama'),
        'jenis_kelamin' => siswa_post_string('jenis_kelamin'),
        'tanggal_lahir' => siswa_post_string('tanggal_lahir'),
    ];
} elseif ($editSiswa !== null) {
    $formValues = [
        'id' => (int) $editSiswa['id'],
        'nomor_induk' => (string) $editSiswa['nomor_induk'],
        'nama' => (string) $editSiswa['nama'],
        'jenis_kelamin' => (string) ($editSiswa['jenis_kelamin'] ?? ''),
        'tanggal_lahir' => (string) ($editSiswa['tanggal_lahir'] ?? ''),
    ];
} else {
    $formValues = [
        'id' => 0,
        'nomor_induk' => '',
        'nama' => '',
        'jenis_kelamin' => '',
        'tanggal_lahir' => '',
    ];
}

$isEditing = $formValues['id'] > 0;
$isFormPage = $isEditing
    || (isset($_GET['form']) && $_GET['form'] === '1')
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save');

admin_page_start(
    $isFormPage ? ($isEditing ? 'Edit Siswa' : 'Tambah Siswa') : 'Data Siswa',
    'siswa.php',
    $isFormPage ? null : ['label' => 'Tambah Siswa', 'href' => 'siswa.php?form=1'],
    $isFormPage ? null : 'Data profil serta kelas pada semester aktif.'
);

require __DIR__ . ($isFormPage ? '/views/siswa-form.php' : '/views/siswa-list.php');
admin_page_end();
