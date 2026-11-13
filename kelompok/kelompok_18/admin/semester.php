<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/** Read a trimmed string value from the POST body. */
function semester_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Send a flash message and return to the semester list (Post/Redirect/Get). */
function semester_redirect_with_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: semester.php');
    exit;
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
if ($editId < 0) {
    $editId = 0;
}

// ---------------------------------------------------------------
// Handle POST actions (save and set-active). CSRF is mandatory.
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Permintaan tidak valid.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'activate') {
        $semesterId = (int) ($_POST['semester_id'] ?? 0);

        try {
            $pdo->beginTransaction();

            $statement = $pdo->prepare('SELECT status FROM semester WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $semesterId]);
            $target = $statement->fetch();

            if (!is_array($target)) {
                $pdo->rollBack();
                semester_redirect_with_flash('error', 'Data semester tidak ditemukan.');
            }

            // Only one active semester is allowed. The previous active becomes 'selesai'.
            $statement = $pdo->prepare("UPDATE semester SET status = 'selesai' WHERE status = 'aktif' AND id <> :id");
            $statement->execute(['id' => $semesterId]);

            $statement = $pdo->prepare("UPDATE semester SET status = 'aktif' WHERE id = :id");
            $statement->execute(['id' => $semesterId]);

            $pdo->commit();
            semester_redirect_with_flash('success', 'Semester aktif berhasil ditetapkan.');
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('ATTENDARA activate semester failed: ' . $exception->getMessage());
            semester_redirect_with_flash('error', 'Semester aktif gagal ditetapkan.');
        }
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $kode = semester_post_string('kode');
        $nama = semester_post_string('nama');
        $tanggalMulai = semester_post_string('tanggal_mulai');
        $tanggalSelesai = semester_post_string('tanggal_selesai');

        // Server-side validation.
        if ($kode === '') {
            $errors[] = 'Kode semester wajib diisi.';
        } elseif (mb_strlen($kode) > 40) {
            $errors[] = 'Kode semester maksimal 40 karakter.';
        }

        if ($nama === '') {
            $errors[] = 'Nama semester wajib diisi.';
        } elseif (mb_strlen($nama) > 100) {
            $errors[] = 'Nama semester maksimal 100 karakter.';
        }

        $start = DateTime::createFromFormat('Y-m-d', $tanggalMulai);
        $validStart = $start instanceof DateTime && $start->format('Y-m-d') === $tanggalMulai;
        if ($tanggalMulai === '' || !$validStart) {
            $errors[] = 'Tanggal mulai wajib diisi dengan format yang valid.';
        }

        $end = DateTime::createFromFormat('Y-m-d', $tanggalSelesai);
        $validEnd = $end instanceof DateTime && $end->format('Y-m-d') === $tanggalSelesai;
        if ($tanggalSelesai === '' || !$validEnd) {
            $errors[] = 'Tanggal selesai wajib diisi dengan format yang valid.';
        }

        if ($validStart && $validEnd && $tanggalSelesai < $tanggalMulai) {
            $errors[] = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
        }

        // Uniqueness check with a friendly message.
        if ($errors === []) {
            $sql = 'SELECT COUNT(*) FROM semester WHERE kode = :kode';
            $params = ['kode' => $kode];
            if ($id > 0) {
                $sql .= ' AND id <> :id';
                $params['id'] = $id;
            }
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Kode semester sudah digunakan.';
            }
        }

        if ($errors === []) {
            try {
                if ($id === 0) {
                    $statement = $pdo->prepare(
                        "INSERT INTO semester (kode, nama, tanggal_mulai, tanggal_selesai, status)
                         VALUES (:kode, :nama, :tanggal_mulai, :tanggal_selesai, 'direncanakan')"
                    );
                    $statement->execute([
                        'kode' => $kode,
                        'nama' => $nama,
                        'tanggal_mulai' => $tanggalMulai,
                        'tanggal_selesai' => $tanggalSelesai,
                    ]);
                    semester_redirect_with_flash('success', 'Semester berhasil ditambahkan.');
                } else {
                    $statement = $pdo->prepare(
                        'UPDATE semester
                         SET kode = :kode, nama = :nama, tanggal_mulai = :tanggal_mulai, tanggal_selesai = :tanggal_selesai
                         WHERE id = :id'
                    );
                    $statement->execute([
                        'kode' => $kode,
                        'nama' => $nama,
                        'tanggal_mulai' => $tanggalMulai,
                        'tanggal_selesai' => $tanggalSelesai,
                        'id' => $id,
                    ]);
                    semester_redirect_with_flash('success', 'Data semester berhasil diperbarui.');
                }
            } catch (PDOException $exception) {
                error_log('ATTENDARA save semester failed: ' . $exception->getMessage());
                $errors[] = $exception->getCode() === '23000'
                    ? 'Kode semester sudah digunakan.'
                    : 'Data semester gagal disimpan.';
            }
        }
    }
}

// ---------------------------------------------------------------
// Load data for the edit form and the table.
// ---------------------------------------------------------------
$editSemester = null;
if ($editId > 0) {
    $statement = $pdo->prepare(
        'SELECT id, kode, nama, tanggal_mulai, tanggal_selesai FROM semester WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $editId]);
    $editSemester = $statement->fetch() ?: null;
}

$statement = $pdo->query(
    'SELECT id, kode, nama, tanggal_mulai, tanggal_selesai, status
     FROM semester
     ORDER BY tanggal_mulai DESC, id DESC'
);
$semesterList = $statement->fetchAll();

// Repopulate the form from the last POST when validation failed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && $errors !== []) {
    $formValues = [
        'id' => (int) ($_POST['id'] ?? 0),
        'kode' => semester_post_string('kode'),
        'nama' => semester_post_string('nama'),
        'tanggal_mulai' => semester_post_string('tanggal_mulai'),
        'tanggal_selesai' => semester_post_string('tanggal_selesai'),
    ];
} elseif ($editSemester !== null) {
    $formValues = [
        'id' => (int) $editSemester['id'],
        'kode' => (string) $editSemester['kode'],
        'nama' => (string) $editSemester['nama'],
        'tanggal_mulai' => (string) $editSemester['tanggal_mulai'],
        'tanggal_selesai' => (string) $editSemester['tanggal_selesai'],
    ];
} else {
    $formValues = [
        'id' => 0,
        'kode' => '',
        'nama' => '',
        'tanggal_mulai' => '',
        'tanggal_selesai' => '',
    ];
}

$isEditing = $formValues['id'] > 0;
$isFormPage = $isEditing
    || (isset($_GET['form']) && $_GET['form'] === '1')
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save');

/** Map a semester status to a Tailwind badge class pair. */
function semester_status_badge_class(string $status): string
{
    return match ($status) {
        'aktif' => 'bg-[#e8f0e9] text-[#3f6548]',
        'selesai' => 'bg-slate-100 text-slate-600',
        default => 'bg-amber-50 text-amber-700',
    };
}

admin_page_start(
    $isFormPage ? ($isEditing ? 'Edit Semester' : 'Tambah Semester') : 'Data Semester',
    'semester.php',
    $isFormPage ? null : ['label' => 'Tambah Semester', 'href' => 'semester.php?form=1'],
    $isFormPage ? null : 'Hanya satu semester yang berstatus aktif.'
);

require __DIR__ . ($isFormPage ? '/views/semester-form.php' : '/views/semester-list.php');
admin_page_end();
