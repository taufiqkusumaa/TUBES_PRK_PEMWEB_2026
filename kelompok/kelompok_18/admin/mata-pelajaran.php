<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/** Read a trimmed string value from the POST body. */
function mata_pelajaran_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Send a flash message and return to the subject list (Post/Redirect/Get). */
function mata_pelajaran_redirect_with_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: mata-pelajaran.php');
    exit;
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
if ($editId < 0) {
    $editId = 0;
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
        $mapelId = (int) ($_POST['mata_pelajaran_id'] ?? 0);

        try {
            $statement = $pdo->prepare('SELECT is_active FROM mata_pelajaran WHERE id = :id');
            $statement->execute(['id' => $mapelId]);
            $target = $statement->fetch();

            if (!is_array($target)) {
                mata_pelajaran_redirect_with_flash('error', 'Data mata pelajaran tidak ditemukan.');
            }

            $newStatus = !(bool) $target['is_active'];
            $statement = $pdo->prepare('UPDATE mata_pelajaran SET is_active = :active WHERE id = :id');
            $statement->execute(['active' => $newStatus ? 1 : 0, 'id' => $mapelId]);

            mata_pelajaran_redirect_with_flash(
                'success',
                $newStatus ? 'Mata pelajaran berhasil diaktifkan.' : 'Mata pelajaran berhasil dinonaktifkan.'
            );
        } catch (PDOException $exception) {
            error_log('ATTENDARA toggle mata pelajaran failed: ' . $exception->getMessage());
            mata_pelajaran_redirect_with_flash('error', 'Status mata pelajaran gagal diubah.');
        }
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $kode = mata_pelajaran_post_string('kode');
        $nama = mata_pelajaran_post_string('nama');

        // Server-side validation.
        if ($kode === '') {
            $errors[] = 'Kode mata pelajaran wajib diisi.';
        } elseif (mb_strlen($kode) > 30) {
            $errors[] = 'Kode mata pelajaran maksimal 30 karakter.';
        }

        if ($nama === '') {
            $errors[] = 'Nama mata pelajaran wajib diisi.';
        } elseif (mb_strlen($nama) > 100) {
            $errors[] = 'Nama mata pelajaran maksimal 100 karakter.';
        }

        // Uniqueness check with a friendly message.
        if ($errors === []) {
            $sql = 'SELECT COUNT(*) FROM mata_pelajaran WHERE kode = :kode';
            $params = ['kode' => $kode];
            if ($id > 0) {
                $sql .= ' AND id <> :id';
                $params['id'] = $id;
            }
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Kode mata pelajaran sudah digunakan.';
            }
        }

        if ($errors === []) {
            $sql = 'SELECT COUNT(*) FROM mata_pelajaran WHERE nama = :nama';
            $params = ['nama' => $nama];
            if ($id > 0) {
                $sql .= ' AND id <> :id';
                $params['id'] = $id;
            }
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Nama mata pelajaran sudah digunakan.';
            }
        }

        if ($errors === []) {
            try {
                if ($id === 0) {
                    $statement = $pdo->prepare(
                        'INSERT INTO mata_pelajaran (kode, nama, is_active)
                         VALUES (:kode, :nama, TRUE)'
                    );
                    $statement->execute([
                        'kode' => $kode,
                        'nama' => $nama,
                    ]);
                    mata_pelajaran_redirect_with_flash('success', 'Mata pelajaran berhasil ditambahkan.');
                } else {
                    $statement = $pdo->prepare(
                        'UPDATE mata_pelajaran
                         SET kode = :kode, nama = :nama
                         WHERE id = :id'
                    );
                    $statement->execute([
                        'kode' => $kode,
                        'nama' => $nama,
                        'id' => $id,
                    ]);
                    mata_pelajaran_redirect_with_flash('success', 'Data mata pelajaran berhasil diperbarui.');
                }
            } catch (PDOException $exception) {
                error_log('ATTENDARA save mata pelajaran failed: ' . $exception->getMessage());
                $errors[] = $exception->getCode() === '23000'
                    ? 'Kode atau nama mata pelajaran sudah digunakan.'
                    : 'Mata pelajaran gagal disimpan.';
            }
        }
    }
}

// ---------------------------------------------------------------
// Load data for the edit form and the table.
// ---------------------------------------------------------------
$editMapel = null;
if ($editId > 0) {
    $statement = $pdo->prepare('SELECT id, kode, nama, is_active FROM mata_pelajaran WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $editId]);
    $editMapel = $statement->fetch() ?: null;
}

$statement = $pdo->query(
    'SELECT id, kode, nama, is_active
     FROM mata_pelajaran
     ORDER BY nama'
);
$mataPelajaranList = $statement->fetchAll();

// Repopulate the form from the last POST when validation failed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && $errors !== []) {
    $formValues = [
        'id' => (int) ($_POST['id'] ?? 0),
        'kode' => mata_pelajaran_post_string('kode'),
        'nama' => mata_pelajaran_post_string('nama'),
    ];
} elseif ($editMapel !== null) {
    $formValues = [
        'id' => (int) $editMapel['id'],
        'kode' => (string) $editMapel['kode'],
        'nama' => (string) $editMapel['nama'],
    ];
} else {
    $formValues = [
        'id' => 0,
        'kode' => '',
        'nama' => '',
    ];
}

$isEditing = $formValues['id'] > 0;
$isFormPage = $isEditing
    || (isset($_GET['form']) && $_GET['form'] === '1')
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save');

admin_page_start(
    $isFormPage ? ($isEditing ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran') : 'Data Mata Pelajaran',
    'mata-pelajaran.php',
    $isFormPage ? null : ['label' => 'Tambah Mata Pelajaran', 'href' => 'mata-pelajaran.php?form=1'],
    $isFormPage ? null : 'Daftar mata pelajaran yang tersedia untuk penugasan guru.'
);

require __DIR__ . ($isFormPage ? '/views/mata-pelajaran-form.php' : '/views/mata-pelajaran-list.php');
admin_page_end();
