<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/** Read a trimmed string value from the POST body. */
function kelas_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Send a flash message and return to the class list (Post/Redirect/Get). */
function kelas_redirect_with_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: kelas.php');
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
        $kelasId = (int) ($_POST['kelas_id'] ?? 0);

        try {
            $statement = $pdo->prepare('SELECT is_active FROM kelas WHERE id = :id');
            $statement->execute(['id' => $kelasId]);
            $target = $statement->fetch();

            if (!is_array($target)) {
                kelas_redirect_with_flash('error', 'Data kelas tidak ditemukan.');
            }

            $newStatus = !(bool) $target['is_active'];
            $statement = $pdo->prepare('UPDATE kelas SET is_active = :active WHERE id = :id');
            $statement->execute(['active' => $newStatus ? 1 : 0, 'id' => $kelasId]);

            kelas_redirect_with_flash(
                'success',
                $newStatus ? 'Kelas berhasil diaktifkan.' : 'Kelas berhasil dinonaktifkan.'
            );
        } catch (PDOException $exception) {
            error_log('ATTENDARA toggle kelas failed: ' . $exception->getMessage());
            kelas_redirect_with_flash('error', 'Status kelas gagal diubah.');
        }
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $kode = kelas_post_string('kode');
        $nama = kelas_post_string('nama');
        $tingkat = kelas_post_string('tingkat');
        $semesterId = (int) ($_POST['semester_id'] ?? 0);

        // Server-side validation.
        if ($kode === '') {
            $errors[] = 'Kode kelas wajib diisi.';
        } elseif (mb_strlen($kode) > 30) {
            $errors[] = 'Kode kelas maksimal 30 karakter.';
        }

        if ($nama === '') {
            $errors[] = 'Nama kelas wajib diisi.';
        } elseif (mb_strlen($nama) > 100) {
            $errors[] = 'Nama kelas maksimal 100 karakter.';
        }

        if ($semesterId <= 0) {
            $errors[] = 'Semester wajib dipilih.';
        }

        $tingkatValue = null;
        if ($tingkat !== '') {
            if (!ctype_digit($tingkat) || (int) $tingkat > 255) {
                $errors[] = 'Tingkat harus berupa angka 0-255.';
            } else {
                $tingkatValue = (int) $tingkat;
            }
        }

        // Uniqueness check with a friendly message. Kode kelas unik per semester.
        if ($errors === []) {
            $sql = 'SELECT COUNT(*) FROM kelas WHERE kode = :kode AND semester_id = :semester_id';
            $params = ['kode' => $kode, 'semester_id' => $semesterId];
            if ($id > 0) {
                $sql .= ' AND id <> :id';
                $params['id'] = $id;
            }
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Kode kelas sudah digunakan pada semester ini.';
            }
        }

        if ($errors === []) {
            try {
                if ($id === 0) {
                    $statement = $pdo->prepare(
                        'INSERT INTO kelas (semester_id, kode, nama, tingkat, is_active)
                         VALUES (:semester_id, :kode, :nama, :tingkat, TRUE)'
                    );
                    $statement->execute([
                        'semester_id' => $semesterId,
                        'kode' => $kode,
                        'nama' => $nama,
                        'tingkat' => $tingkatValue,
                    ]);
                    kelas_redirect_with_flash('success', 'Kelas berhasil ditambahkan.');
                } else {
                    $statement = $pdo->prepare(
                        'UPDATE kelas SET semester_id = :semester_id, kode = :kode, nama = :nama, tingkat = :tingkat WHERE id = :id'
                    );
                    $statement->execute([
                        'semester_id' => $semesterId,
                        'kode' => $kode,
                        'nama' => $nama,
                        'tingkat' => $tingkatValue,
                        'id' => $id,
                    ]);
                    kelas_redirect_with_flash('success', 'Data kelas berhasil diperbarui.');
                }
            } catch (PDOException $exception) {
                error_log('ATTENDARA save kelas failed: ' . $exception->getMessage());
                $errors[] = $exception->getCode() === '23000'
                    ? 'Kode kelas sudah digunakan pada semester ini.'
                    : 'Data kelas gagal disimpan.';
            }
        }
    }
}

// ---------------------------------------------------------------
// Active semester is used as the default value for the class form.
// ---------------------------------------------------------------
$statement = $pdo->prepare('SELECT id, nama FROM semester WHERE status = :status LIMIT 1');
$statement->execute(['status' => 'aktif']);
$activeSemester = $statement->fetch() ?: null;

// Daftar semester untuk dropdown form kelas.
$semesterList = $pdo->query(
    'SELECT id, kode, nama, status FROM semester ORDER BY tanggal_mulai DESC, id DESC'
)->fetchAll();

// ---------------------------------------------------------------
// Detail view: class info and its students in the class's own semester.
// ---------------------------------------------------------------
if ($detailId > 0) {
    $statement = $pdo->prepare(
        'SELECT k.id, k.semester_id, sm.nama AS semester_nama, sm.status AS semester_status,
                k.kode, k.nama, k.tingkat, k.is_active, k.created_at, k.updated_at
         FROM kelas AS k
         INNER JOIN semester AS sm ON sm.id = k.semester_id
         WHERE k.id = :id LIMIT 1'
    );
    $statement->execute(['id' => $detailId]);
    $detailKelas = $statement->fetch();

    if (!is_array($detailKelas)) {
        kelas_redirect_with_flash('error', 'Data kelas tidak ditemukan.');
    }

    $detailSemesterId = (int) $detailKelas['semester_id'];

    $statement = $pdo->prepare(
        'SELECT s.nomor_induk, s.nama, s.is_active
         FROM anggota_kelas AS ak
         JOIN siswa AS s ON s.id = ak.siswa_id
         WHERE ak.kelas_id = :kelas_id AND ak.semester_id = :semester_id
         ORDER BY s.nama'
    );
    $statement->execute([
        'kelas_id' => $detailId,
        'semester_id' => $detailSemesterId,
    ]);
    $anggota = $statement->fetchAll();

    admin_page_start('Detail Kelas', 'kelas.php');
    ?>
    <section class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-[#303832]"><?= h((string) $detailKelas['nama']) ?></h2>
                <p class="mt-1 text-sm text-[#64666a]">Kode <?= h((string) $detailKelas['kode']) ?></p>
            </div>
            <div class="flex items-center gap-2">
                <a href="kelas.php?edit=<?= h((string) $detailKelas['id']) ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#41684f]">Edit</a>
                <a href="kelas.php" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#dfe6de] bg-white px-4 py-2 text-sm font-semibold text-[#64666a] hover:bg-[#eff3ee]">Kembali</a>
            </div>
        </div>

        <dl class="grid gap-4 rounded-2xl border border-[#dfe6de] bg-white p-5 sm:grid-cols-2 sm:p-6">
            <div><dt class="text-sm font-medium text-slate-500">Tingkat</dt><dd class="mt-1 text-sm text-slate-800"><?= h((string) ($detailKelas['tingkat'] ?? '-')) ?></dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Status</dt><dd class="mt-1 text-sm text-slate-800"><?= (bool) $detailKelas['is_active'] ? 'Aktif' : 'Nonaktif' ?></dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Semester</dt><dd class="mt-1 text-sm text-slate-800"><?= h((string) ($detailKelas['semester_nama'] ?? '-')) ?></dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Jumlah siswa</dt><dd class="mt-1 text-sm text-slate-800"><?= h((string) count($anggota)) ?></dd></div>
        </dl>

        <div class="space-y-3">
            <h3 class="text-base font-semibold text-[#303832]">Anggota Kelas</h3>
            <div class="overflow-x-auto rounded-2xl border border-[#dfe6de] bg-white">
                <table class="min-w-full divide-y divide-[#e6ebe5] text-left text-sm">
                    <thead class="bg-[#eff3ee] text-xs uppercase tracking-wide text-[#64666a]">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">No</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nomor Induk</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nama</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e6ebe5] text-[#414942]">
                        <?php if ($anggota === []): ?>
                            <tr><td colspan="4" class="px-4 py-9 text-center text-slate-500">Belum ada siswa pada kelas ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($anggota as $index => $siswa): ?>
                                <tr class="hover:bg-[#fafbf9]">
                                    <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) ($index + 1)) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) $siswa['nomor_induk']) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $siswa['nama']) ?></td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= (bool) $siswa['is_active'] ? 'Aktif' : 'Nonaktif' ?></td>
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
$editKelas = null;
if ($editId > 0) {
    $statement = $pdo->prepare('SELECT id, semester_id, kode, nama, tingkat FROM kelas WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $editId]);
    $editKelas = $statement->fetch() ?: null;
}

$statement = $pdo->query(
    'SELECT k.id, k.semester_id, sm.nama AS semester_nama, sm.status AS semester_status,
            k.kode, k.nama, k.tingkat, k.is_active,
            COUNT(ak.siswa_id) AS jumlah_siswa
     FROM kelas AS k
     INNER JOIN semester AS sm ON sm.id = k.semester_id
     LEFT JOIN anggota_kelas AS ak ON ak.kelas_id = k.id AND ak.semester_id = k.semester_id
     GROUP BY k.id, k.semester_id, sm.nama, sm.status, k.kode, k.nama, k.tingkat, k.is_active
     ORDER BY sm.tanggal_mulai DESC, sm.id DESC, k.kode'
);
$kelasList = $statement->fetchAll();

// Repopulate the form from the last POST when validation failed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && $errors !== []) {
    $formValues = [
        'id' => (int) ($_POST['id'] ?? 0),
        'kode' => kelas_post_string('kode'),
        'nama' => kelas_post_string('nama'),
        'tingkat' => kelas_post_string('tingkat'),
        'semester_id' => (int) ($_POST['semester_id'] ?? 0),
    ];
} elseif ($editKelas !== null) {
    $formValues = [
        'id' => (int) $editKelas['id'],
        'kode' => (string) $editKelas['kode'],
        'nama' => (string) $editKelas['nama'],
        'tingkat' => (string) ($editKelas['tingkat'] ?? ''),
        'semester_id' => (int) ($editKelas['semester_id'] ?? 0),
    ];
} else {
    $formValues = [
        'id' => 0,
        'kode' => '',
        'nama' => '',
        'tingkat' => '',
        'semester_id' => $activeSemester !== null ? (int) $activeSemester['id'] : 0,
    ];
}

$isEditing = $formValues['id'] > 0;
$isFormPage = $isEditing
    || (isset($_GET['form']) && $_GET['form'] === '1')
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save');

admin_page_start(
    $isFormPage ? ($isEditing ? 'Edit Kelas' : 'Tambah Kelas') : 'Data Kelas',
    'kelas.php',
    $isFormPage ? null : ['label' => 'Tambah Kelas', 'href' => 'kelas.php?form=1'],
    $isFormPage ? null : 'Kelola kelas dan jumlah siswa per semester.'
);

require __DIR__ . ($isFormPage ? '/views/kelas-form.php' : '/views/kelas-list.php');
admin_page_end();
