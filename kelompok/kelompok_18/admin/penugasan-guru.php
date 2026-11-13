<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/** Read an integer value from the POST body. */
function penugasan_post_int(string $key): int
{
    $value = $_POST[$key] ?? 0;
    return is_scalar($value) ? (int) $value : 0;
}

/** Send a flash message and return to the assignment list. */
function penugasan_redirect_with_flash(int $filterSemesterId, string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    $query = $filterSemesterId > 0 ? '?semester_id=' . $filterSemesterId : '';
    header('Location: penugasan-guru.php' . $query);
    exit;
}


$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$filterSemesterId = $isPost
    ? penugasan_post_int('filter_semester_id')
    : (isset($_GET['semester_id']) ? (int) $_GET['semester_id'] : 0);
if ($filterSemesterId < 0) {
    $filterSemesterId = 0;
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
if ($editId < 0) {
    $editId = 0;
}

// ---------------------------------------------------------------
// Handle POST actions (save and delete). CSRF is mandatory.
// ---------------------------------------------------------------
if ($isPost) {
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Permintaan tidak valid.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = penugasan_post_int('id');
        $guruId = penugasan_post_int('guru_id');
        $mataPelajaranId = penugasan_post_int('mata_pelajaran_id');
        $kelasId = penugasan_post_int('kelas_id');

        // Server-side validation against the current data.
        $statement = $pdo->prepare('SELECT COUNT(*) FROM guru WHERE id = :id AND is_active = TRUE');
        $statement->execute(['id' => $guruId]);
        if ((int) $statement->fetchColumn() === 0) {
            $errors[] = 'Guru yang dipilih tidak ditemukan atau sudah nonaktif.';
        }

        $statement = $pdo->prepare('SELECT COUNT(*) FROM mata_pelajaran WHERE id = :id AND is_active = TRUE');
        $statement->execute(['id' => $mataPelajaranId]);
        if ((int) $statement->fetchColumn() === 0) {
            $errors[] = 'Mata pelajaran yang dipilih tidak ditemukan atau sudah nonaktif.';
        }

        $statement = $pdo->prepare('SELECT COUNT(*) FROM kelas WHERE id = :id AND is_active = TRUE');
        $statement->execute(['id' => $kelasId]);
        if ((int) $statement->fetchColumn() === 0) {
            $errors[] = 'Kelas yang dipilih tidak ditemukan atau sudah nonaktif.';
        }

        // Satu guru hanya boleh memiliki satu penugasan per mata pelajaran + kelas.
        if ($errors === []) {
            $sql = 'SELECT COUNT(*) FROM penugasan_guru
                    WHERE guru_id = :guru_id
                      AND mata_pelajaran_id = :mata_pelajaran_id
                      AND kelas_id = :kelas_id';
            $params = [
                'guru_id' => $guruId,
                'mata_pelajaran_id' => $mataPelajaranId,
                'kelas_id' => $kelasId,
            ];
            if ($id > 0) {
                $sql .= ' AND id <> :id';
                $params['id'] = $id;
            }
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Guru tersebut sudah ditugaskan pada mata pelajaran dan kelas ini.';
            }
        }

        if ($errors === []) {
            try {
                if ($id === 0) {
                    $statement = $pdo->prepare(
                        'INSERT INTO penugasan_guru (guru_id, mata_pelajaran_id, kelas_id)
                         VALUES (:guru_id, :mata_pelajaran_id, :kelas_id)'
                    );
                    $statement->execute([
                        'guru_id' => $guruId,
                        'mata_pelajaran_id' => $mataPelajaranId,
                        'kelas_id' => $kelasId,
                    ]);
                    penugasan_redirect_with_flash($filterSemesterId, 'success', 'Penugasan guru berhasil ditambahkan.');
                } else {
                    $statement = $pdo->prepare(
                        'UPDATE penugasan_guru
                         SET guru_id = :guru_id, mata_pelajaran_id = :mata_pelajaran_id, kelas_id = :kelas_id
                         WHERE id = :id'
                    );
                    $statement->execute([
                        'guru_id' => $guruId,
                        'mata_pelajaran_id' => $mataPelajaranId,
                        'kelas_id' => $kelasId,
                        'id' => $id,
                    ]);
                    penugasan_redirect_with_flash($filterSemesterId, 'success', 'Data penugasan berhasil diperbarui.');
                }
            } catch (PDOException $exception) {
                error_log('ATTENDARA save penugasan failed: ' . $exception->getMessage());
                $errors[] = $exception->getCode() === '23000'
                    ? 'Guru tersebut sudah ditugaskan pada mata pelajaran dan kelas ini.'
                    : 'Data penugasan gagal disimpan.';
            }
        }
    }

    if ($action === 'delete') {
        $id = penugasan_post_int('id');

        try {
            $pdo->beginTransaction();

            $statement = $pdo->prepare('SELECT id FROM penugasan_guru WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $id]);
            if ($statement->fetchColumn() === false) {
                $pdo->rollBack();
                penugasan_redirect_with_flash($filterSemesterId, 'error', 'Data penugasan tidak ditemukan.');
            }

            // Schedules reference the assignment through a restrictive foreign key.
            $statement = $pdo->prepare('SELECT COUNT(*) FROM jadwal_mengajar WHERE penugasan_guru_id = :id');
            $statement->execute(['id' => $id]);
            if ((int) $statement->fetchColumn() > 0) {
                $pdo->rollBack();
                penugasan_redirect_with_flash(
                    $filterSemesterId,
                    'error',
                    'Penugasan masih memiliki jadwal mengajar. Hapus jadwalnya terlebih dahulu.'
                );
            }

            $statement = $pdo->prepare('DELETE FROM penugasan_guru WHERE id = :id');
            $statement->execute(['id' => $id]);

            $pdo->commit();
            penugasan_redirect_with_flash($filterSemesterId, 'success', 'Penugasan guru berhasil dihapus.');
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('ATTENDARA delete penugasan failed: ' . $exception->getMessage());
            penugasan_redirect_with_flash($filterSemesterId, 'error', 'Penugasan guru gagal dihapus.');
        }
    }
}

// ---------------------------------------------------------------
// Load reference data for the filter and the form.
// ---------------------------------------------------------------
$semesterList = $pdo->query(
    "SELECT id, kode, nama, status FROM semester
     ORDER BY (status = 'aktif') DESC, tanggal_mulai DESC, id DESC"
)->fetchAll();

$activeSemesterId = 0;
foreach ($semesterList as $semester) {
    if ($semester['status'] === 'aktif') {
        $activeSemesterId = (int) $semester['id'];
        break;
    }
}

// Default the list filter to the active semester on a fresh page load.
if (!$isPost && !isset($_GET['semester_id'])) {
    $filterSemesterId = $activeSemesterId;
}

$semesterExists = false;
foreach ($semesterList as $semester) {
    if ((int) $semester['id'] === $filterSemesterId) {
        $semesterExists = true;
        break;
    }
}
if ($filterSemesterId > 0 && !$semesterExists) {
    $filterSemesterId = 0;
}

$guruList = $pdo->query(
    'SELECT id, nomor_induk, nama FROM guru WHERE is_active = TRUE ORDER BY nama, nomor_induk'
)->fetchAll();

$mataPelajaranList = $pdo->query(
    'SELECT id, kode, nama FROM mata_pelajaran WHERE is_active = TRUE ORDER BY nama, kode'
)->fetchAll();

$kelasList = $pdo->query(
    'SELECT k.id, k.kode, k.nama, k.tingkat, k.semester_id, sm.nama AS semester_nama
     FROM kelas AS k
     INNER JOIN semester AS sm ON sm.id = k.semester_id
     WHERE k.is_active = TRUE
     ORDER BY sm.tanggal_mulai DESC, sm.id DESC, k.tingkat IS NULL, k.tingkat, k.nama'
)->fetchAll();

// Assignment being edited (only via GET so form repopulation stays predictable).
$editAssignment = null;
if ($editId > 0) {
    $statement = $pdo->prepare(
        'SELECT id, guru_id, mata_pelajaran_id, kelas_id FROM penugasan_guru WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $editId]);
    $editAssignment = $statement->fetch() ?: null;
}

// Repopulate the form after a failed POST, otherwise use the edit row or defaults.
$formValues = [
    'id' => 0,
    'guru_id' => 0,
    'mata_pelajaran_id' => 0,
    'kelas_id' => 0,
];
if ($isPost && ($_POST['action'] ?? '') === 'save' && $errors !== []) {
    $formValues = [
        'id' => penugasan_post_int('id'),
        'guru_id' => penugasan_post_int('guru_id'),
        'mata_pelajaran_id' => penugasan_post_int('mata_pelajaran_id'),
        'kelas_id' => penugasan_post_int('kelas_id'),
    ];
} elseif ($editAssignment !== null) {
    $formValues = [
        'id' => (int) $editAssignment['id'],
        'guru_id' => (int) $editAssignment['guru_id'],
        'mata_pelajaran_id' => (int) $editAssignment['mata_pelajaran_id'],
        'kelas_id' => (int) $editAssignment['kelas_id'],
    ];
}
$isEditing = $formValues['id'] > 0;

// Assignment list for the selected semester (0 = all semesters).
// Semester comes from the class because penugasan_guru no longer stores semester_id.
$listSql = 'SELECT p.id, p.created_at,
                   g.id AS guru_id, g.nama AS guru_nama, g.nomor_induk AS guru_nomor,
                   mp.nama AS mapel_nama, mp.kode AS mapel_kode,
                   k.nama AS kelas_nama, k.semester_id,
                   s.nama AS semester_nama,
                   (SELECT COUNT(*) FROM jadwal_mengajar j WHERE j.penugasan_guru_id = p.id) AS jumlah_jadwal
            FROM penugasan_guru p
            INNER JOIN guru g ON g.id = p.guru_id
            INNER JOIN mata_pelajaran mp ON mp.id = p.mata_pelajaran_id
            INNER JOIN kelas k ON k.id = p.kelas_id
            INNER JOIN semester s ON s.id = k.semester_id';
$listParams = [];
if ($filterSemesterId > 0) {
    $listSql .= ' WHERE k.semester_id = :semester_id';
    $listParams['semester_id'] = $filterSemesterId;
}
$listSql .= ' ORDER BY s.tanggal_mulai DESC, k.nama, g.nama, mp.nama';

$statement = $pdo->prepare($listSql);
$statement->execute($listParams);
$assignmentList = $statement->fetchAll();

$showForm = $isEditing
    || isset($_GET['form'])
    || ($isPost && ($_POST['action'] ?? '') === 'save' && $errors !== []);

admin_page_start(
    $showForm ? ($isEditing ? 'Edit Penugasan Guru' : 'Tambah Penugasan Guru') : 'Penugasan Guru',
    'penugasan-guru.php',
    $showForm ? null : ['label' => 'Tambah Penugasan', 'href' => 'penugasan-guru.php?form=1' . ($filterSemesterId > 0 ? '&semester_id=' . (string) $filterSemesterId : '')],
    $showForm ? null : 'Atur guru, mata pelajaran, dan kelas dalam satu penugasan.'
);
require __DIR__ . '/views/' . ($showForm ? 'penugasan-guru-form.php' : 'penugasan-guru-list.php');
admin_page_end();
