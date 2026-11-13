<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/**
 * Day numbers follow the application convention: 1 = Monday ... 7 = Sunday.
 * The database stores the raw number; the label is only for display.
 */
const JADWAL_DAYS = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
    7 => 'Minggu',
];

/** Read a trimmed string value from the POST body. */
function jadwal_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Read an integer value from the POST body. */
function jadwal_post_int(string $key): int
{
    $value = $_POST[$key] ?? 0;
    return is_scalar($value) ? (int) $value : 0;
}

/** Normalize a user-supplied time to H:i:s, or return null when invalid. */
function jadwal_time_value(string $value): ?string
{
    foreach (['H:i:s', 'H:i'] as $format) {
        $time = DateTime::createFromFormat($format, $value);
        if ($time instanceof DateTime && $time->format($format) === $value) {
            return $time->format('H:i:s');
        }
    }
    return null;
}

/** Validate an optional date in Y-m-d format. */
function jadwal_date_value(string $value): ?string
{
    if ($value === '') {
        return '';
    }
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date instanceof DateTime && $date->format('Y-m-d') === $value ? $value : null;
}

/** Send a flash message and return to the schedule list. */
function jadwal_redirect_with_flash(int $filterSemesterId, string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    $query = $filterSemesterId > 0 ? '?semester_id=' . $filterSemesterId : '';
    header('Location: jadwal-mengajar.php' . $query);
    exit;
}

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$filterSemesterId = $isPost
    ? jadwal_post_int('filter_semester_id')
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
        $id = jadwal_post_int('id');
        $penugasanId = jadwal_post_int('penugasan_guru_id');
        $hari = jadwal_post_int('hari');
        $jamMulai = jadwal_time_value(jadwal_post_string('jam_mulai'));
        $jamSelesai = jadwal_time_value(jadwal_post_string('jam_selesai'));
        $berlakuMulai = jadwal_date_value(jadwal_post_string('berlaku_mulai'));
        $berlakuSampai = jadwal_date_value(jadwal_post_string('berlaku_sampai'));

        // The schedule always hangs off an assignment; teacher/class/semester come from it.
        $assignment = null;
        if ($penugasanId > 0) {
            $statement = $pdo->prepare(
                'SELECT p.id, p.guru_id, p.kelas_id, k.semester_id
                 FROM penugasan_guru p
                 INNER JOIN kelas k ON k.id = p.kelas_id
                 WHERE p.id = :id LIMIT 1'
            );
            $statement->execute(['id' => $penugasanId]);
            $assignment = $statement->fetch() ?: null;
        }
        if ($assignment === null) {
            $errors[] = 'Penugasan guru yang dipilih tidak ditemukan.';
        }

        if (!isset(JADWAL_DAYS[$hari])) {
            $errors[] = 'Hari wajib dipilih antara 1 (Senin) sampai 7 (Minggu).';
        }

        if ($jamMulai === null) {
            $errors[] = 'Jam mulai wajib diisi dengan format HH:MM atau HH:MM:SS.';
        }
        if ($jamSelesai === null) {
            $errors[] = 'Jam selesai wajib diisi dengan format HH:MM atau HH:MM:SS.';
        }
        if ($jamMulai !== null && $jamSelesai !== null && $jamSelesai <= $jamMulai) {
            $errors[] = 'Jam selesai harus lebih besar dari jam mulai.';
        }

        if ($berlakuMulai === null) {
            $errors[] = 'Format berlaku mulai tidak valid.';
        }
        if ($berlakuSampai === null) {
            $errors[] = 'Format berlaku sampai tidak valid.';
        }
        if (is_string($berlakuMulai) && is_string($berlakuSampai)
            && $berlakuMulai !== '' && $berlakuSampai !== '' && $berlakuSampai < $berlakuMulai) {
            $errors[] = 'Berlaku sampai tidak boleh lebih awal dari berlaku mulai.';
        }

        // Prevent overlapping schedules for the same class or the same teacher.
        if ($errors === [] && $assignment !== null && $jamMulai !== null && $jamSelesai !== null) {
            $statement = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM jadwal_mengajar j
                 INNER JOIN penugasan_guru p ON p.id = j.penugasan_guru_id
                 INNER JOIN kelas k ON k.id = p.kelas_id
                 WHERE j.hari = :hari
                   AND j.jam_mulai < :jam_selesai
                   AND j.jam_selesai > :jam_mulai
                   AND k.semester_id = :semester_id
                   AND (p.kelas_id = :kelas_id OR p.guru_id = :guru_id)
                   AND j.id <> :id'
            );
            $statement->execute([
                'hari' => $hari,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'semester_id' => (int) $assignment['semester_id'],
                'kelas_id' => (int) $assignment['kelas_id'],
                'guru_id' => (int) $assignment['guru_id'],
                'id' => $id,
            ]);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Jadwal bentrok dengan jadwal lain pada kelas atau guru yang sama.';
            }
        }

        if ($errors === []) {
            $berlakuMulaiValue = $berlakuMulai === '' ? null : $berlakuMulai;
            $berlakuSampaiValue = $berlakuSampai === '' ? null : $berlakuSampai;

            try {
                if ($id === 0) {
                    $statement = $pdo->prepare(
                        'INSERT INTO jadwal_mengajar
                             (penugasan_guru_id, hari, jam_mulai, jam_selesai, berlaku_mulai, berlaku_sampai)
                         VALUES (:penugasan_guru_id, :hari, :jam_mulai, :jam_selesai, :berlaku_mulai, :berlaku_sampai)'
                    );
                    $statement->execute([
                        'penugasan_guru_id' => $penugasanId,
                        'hari' => $hari,
                        'jam_mulai' => $jamMulai,
                        'jam_selesai' => $jamSelesai,
                        'berlaku_mulai' => $berlakuMulaiValue,
                        'berlaku_sampai' => $berlakuSampaiValue,
                    ]);
                    jadwal_redirect_with_flash($filterSemesterId, 'success', 'Jadwal mengajar berhasil ditambahkan.');
                } else {
                    $statement = $pdo->prepare(
                        'UPDATE jadwal_mengajar
                         SET penugasan_guru_id = :penugasan_guru_id, hari = :hari,
                             jam_mulai = :jam_mulai, jam_selesai = :jam_selesai,
                             berlaku_mulai = :berlaku_mulai, berlaku_sampai = :berlaku_sampai
                         WHERE id = :id'
                    );
                    $statement->execute([
                        'penugasan_guru_id' => $penugasanId,
                        'hari' => $hari,
                        'jam_mulai' => $jamMulai,
                        'jam_selesai' => $jamSelesai,
                        'berlaku_mulai' => $berlakuMulaiValue,
                        'berlaku_sampai' => $berlakuSampaiValue,
                        'id' => $id,
                    ]);
                    jadwal_redirect_with_flash($filterSemesterId, 'success', 'Jadwal mengajar berhasil diperbarui.');
                }
            } catch (PDOException $exception) {
                error_log('ATTENDARA save jadwal failed: ' . $exception->getMessage());
                $errors[] = 'Jadwal mengajar gagal disimpan.';
            }
        }
    }

    if ($action === 'delete') {
        $id = jadwal_post_int('id');

        try {
            $pdo->beginTransaction();

            $statement = $pdo->prepare('SELECT id FROM jadwal_mengajar WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $id]);
            if ($statement->fetchColumn() === false) {
                $pdo->rollBack();
                jadwal_redirect_with_flash($filterSemesterId, 'error', 'Data jadwal tidak ditemukan.');
            }

            $statement = $pdo->prepare('DELETE FROM jadwal_mengajar WHERE id = :id');
            $statement->execute(['id' => $id]);

            $pdo->commit();
            jadwal_redirect_with_flash($filterSemesterId, 'success', 'Jadwal mengajar berhasil dihapus.');
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('ATTENDARA delete jadwal failed: ' . $exception->getMessage());
            jadwal_redirect_with_flash(
                $filterSemesterId,
                'error',
                $exception->getCode() === '23000'
                    ? 'Jadwal masih dipakai oleh sesi pembelajaran sehingga tidak dapat dihapus.'
                    : 'Jadwal mengajar gagal dihapus.'
            );
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

// Assignments available for the form (all semesters; the schedule inherits them).
$assignmentOptions = $pdo->query(
    'SELECT p.id, g.nama AS guru_nama, mp.nama AS mapel_nama,
            k.nama AS kelas_nama, s.nama AS semester_nama
     FROM penugasan_guru p
     INNER JOIN guru g ON g.id = p.guru_id
     INNER JOIN mata_pelajaran mp ON mp.id = p.mata_pelajaran_id
     INNER JOIN kelas k ON k.id = p.kelas_id
     INNER JOIN semester s ON s.id = k.semester_id
     ORDER BY s.tanggal_mulai DESC, k.nama, g.nama'
)->fetchAll();

$editSchedule = null;
if ($editId > 0) {
    $statement = $pdo->prepare(
        'SELECT id, penugasan_guru_id, hari, jam_mulai, jam_selesai, berlaku_mulai, berlaku_sampai
         FROM jadwal_mengajar WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $editId]);
    $editSchedule = $statement->fetch() ?: null;
}

$formValues = [
    'id' => 0,
    'penugasan_guru_id' => 0,
    'hari' => 0,
    'jam_mulai' => '',
    'jam_selesai' => '',
    'berlaku_mulai' => '',
    'berlaku_sampai' => '',
];
if ($isPost && ($_POST['action'] ?? '') === 'save' && $errors !== []) {
    $formValues = [
        'id' => jadwal_post_int('id'),
        'penugasan_guru_id' => jadwal_post_int('penugasan_guru_id'),
        'hari' => jadwal_post_int('hari'),
        'jam_mulai' => jadwal_post_string('jam_mulai'),
        'jam_selesai' => jadwal_post_string('jam_selesai'),
        'berlaku_mulai' => jadwal_post_string('berlaku_mulai'),
        'berlaku_sampai' => jadwal_post_string('berlaku_sampai'),
    ];
} elseif ($editSchedule !== null) {
    $formValues = [
        'id' => (int) $editSchedule['id'],
        'penugasan_guru_id' => (int) $editSchedule['penugasan_guru_id'],
        'hari' => (int) $editSchedule['hari'],
        'jam_mulai' => (string) $editSchedule['jam_mulai'],
        'jam_selesai' => (string) $editSchedule['jam_selesai'],
        'berlaku_mulai' => (string) ($editSchedule['berlaku_mulai'] ?? ''),
        'berlaku_sampai' => (string) ($editSchedule['berlaku_sampai'] ?? ''),
    ];
}
$isEditing = $formValues['id'] > 0;

// Schedule list for the selected semester (0 = all semesters).
$listSql = 'SELECT j.id, j.hari, j.jam_mulai, j.jam_selesai, j.berlaku_mulai, j.berlaku_sampai,
                   p.id AS penugasan_id,
                   g.nama AS guru_nama, mp.nama AS mapel_nama,
                   k.nama AS kelas_nama, s.nama AS semester_nama
            FROM jadwal_mengajar j
            INNER JOIN penugasan_guru p ON p.id = j.penugasan_guru_id
            INNER JOIN guru g ON g.id = p.guru_id
            INNER JOIN mata_pelajaran mp ON mp.id = p.mata_pelajaran_id
            INNER JOIN kelas k ON k.id = p.kelas_id
            INNER JOIN semester s ON s.id = k.semester_id';
$listParams = [];
if ($filterSemesterId > 0) {
    $listSql .= ' WHERE k.semester_id = :semester_id';
    $listParams['semester_id'] = $filterSemesterId;
}
$listSql .= ' ORDER BY s.tanggal_mulai DESC, j.hari, j.jam_mulai';

$statement = $pdo->prepare($listSql);
$statement->execute($listParams);
$scheduleList = $statement->fetchAll();

$showForm = $isEditing
    || isset($_GET['form'])
    || ($isPost && ($_POST['action'] ?? '') === 'save' && $errors !== []);

admin_page_start(
    $showForm ? ($isEditing ? 'Edit Jadwal' : 'Tambah Jadwal') : 'Jadwal Mengajar',
    'jadwal-mengajar.php',
    $showForm ? null : ['label' => 'Tambah Jadwal', 'href' => 'jadwal-mengajar.php?form=1' . ($filterSemesterId > 0 ? '&semester_id=' . (string) $filterSemesterId : '')],
    $showForm ? null : 'Guru, kelas, dan semester ditampilkan dari penugasan guru.'
);
require __DIR__ . '/views/' . ($showForm ? 'jadwal-mengajar-form.php' : 'jadwal-mengajar-list.php');
admin_page_end();
