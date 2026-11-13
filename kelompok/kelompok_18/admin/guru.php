<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/** Read a trimmed string value from the POST body. */
function guru_post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Send a flash message and return to the guru list (Post/Redirect/Get). */
function guru_redirect_with_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: guru.php');
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
        $guruId = (int) ($_POST['guru_id'] ?? 0);

        try {
            $pdo->beginTransaction();

            $statement = $pdo->prepare('SELECT user_id, is_active FROM guru WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $guruId]);
            $target = $statement->fetch();

            if (!is_array($target)) {
                $pdo->rollBack();
                guru_redirect_with_flash('error', 'Data guru tidak ditemukan.');
            }

            $newStatus = !(bool) $target['is_active'];

            $statement = $pdo->prepare('UPDATE guru SET is_active = :active WHERE id = :id');
            $statement->execute(['active' => $newStatus ? 1 : 0, 'id' => $guruId]);

            if ($target['user_id'] !== null) {
                $statement = $pdo->prepare('UPDATE users SET is_active = :active WHERE id = :id');
                $statement->execute(['active' => $newStatus ? 1 : 0, 'id' => (int) $target['user_id']]);
            }

            $pdo->commit();
            guru_redirect_with_flash(
                'success',
                $newStatus ? 'Guru berhasil diaktifkan.' : 'Guru berhasil dinonaktifkan.'
            );
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('ATTENDARA toggle guru failed: ' . $exception->getMessage());
            guru_redirect_with_flash('error', 'Status guru gagal diubah.');
        }
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $nomorInduk = guru_post_string('nomor_induk');
        $nama = guru_post_string('nama');
        $email = guru_post_string('email');
        $noTelepon = guru_post_string('no_telepon');
        $username = guru_post_string('username');
        $password = (string) ($_POST['password'] ?? '');

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

        if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254)) {
            $errors[] = 'Format email tidak valid.';
        }

        if (mb_strlen($noTelepon) > 30) {
            $errors[] = 'Nomor telepon maksimal 30 karakter.';
        }

        if ($username === '') {
            $errors[] = 'Username wajib diisi.';
        } elseif (!preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username)) {
            $errors[] = 'Username 3-100 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.';
        }

        if ($id === 0 && $password === '') {
            $errors[] = 'Password wajib diisi untuk akun guru baru.';
        }
        if ($password !== '' && mb_strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        }

        // Uniqueness checks with friendly messages.
        if ($errors === []) {
            $sql = 'SELECT COUNT(*) FROM guru WHERE nomor_induk = :nomor_induk';
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

            $sql = 'SELECT COUNT(*) FROM users WHERE username = :username';
            $params = ['username' => $username];
            if ($id > 0) {
                $sql .= ' AND id <> (SELECT user_id FROM guru WHERE id = :gid)';
                $params['gid'] = $id;
            }
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = 'Username sudah digunakan.';
            }
        }

        if ($errors === []) {
            $emailValue = $email === '' ? null : $email;
            $teleponValue = $noTelepon === '' ? null : $noTelepon;

            try {
                $pdo->beginTransaction();

                if ($id === 0) {
                    $statement = $pdo->prepare(
                        'INSERT INTO users (username, password_hash, role, is_active)
                         VALUES (:username, :password_hash, :role, TRUE)'
                    );
                    $statement->execute([
                        'username' => $username,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => 'guru',
                    ]);
                    $userId = (int) $pdo->lastInsertId();

                    $statement = $pdo->prepare(
                        'INSERT INTO guru (user_id, nomor_induk, nama, email, no_telepon, is_active)
                         VALUES (:user_id, :nomor_induk, :nama, :email, :no_telepon, TRUE)'
                    );
                    $statement->execute([
                        'user_id' => $userId,
                        'nomor_induk' => $nomorInduk,
                        'nama' => $nama,
                        'email' => $emailValue,
                        'no_telepon' => $teleponValue,
                    ]);

                    $message = 'Guru berhasil ditambahkan.';
                } else {
                    $statement = $pdo->prepare('SELECT user_id FROM guru WHERE id = :id');
                    $statement->execute(['id' => $id]);
                    $current = $statement->fetch();

                    if (!is_array($current)) {
                        throw new RuntimeException('Data guru tidak ditemukan.');
                    }

                    $statement = $pdo->prepare(
                        'UPDATE guru
                         SET nomor_induk = :nomor_induk, nama = :nama, email = :email, no_telepon = :no_telepon
                         WHERE id = :id'
                    );
                    $statement->execute([
                        'nomor_induk' => $nomorInduk,
                        'nama' => $nama,
                        'email' => $emailValue,
                        'no_telepon' => $teleponValue,
                        'id' => $id,
                    ]);

                    if ($current['user_id'] !== null) {
                        $statement = $pdo->prepare('UPDATE users SET username = :username WHERE id = :id');
                        $statement->execute([
                            'username' => $username,
                            'id' => (int) $current['user_id'],
                        ]);

                        if ($password !== '') {
                            $statement = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
                            $statement->execute([
                                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                                'id' => (int) $current['user_id'],
                            ]);
                        }
                    }

                    $message = 'Data guru berhasil diperbarui.';
                }

                $pdo->commit();
                guru_redirect_with_flash('success', $message);
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('ATTENDARA save guru failed: ' . $exception->getMessage());
                $errors[] = $exception->getCode() === '23000'
                    ? 'Nomor induk atau username sudah digunakan.'
                    : 'Data guru gagal disimpan.';
            } catch (RuntimeException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = $exception->getMessage();
            }
        }
    }
}

// ---------------------------------------------------------------
// Load data for the edit form and the table.
// ---------------------------------------------------------------
$editGuru = null;
if ($editId > 0) {
    $statement = $pdo->prepare(
        'SELECT g.id, g.nomor_induk, g.nama, g.email, g.no_telepon, g.user_id, u.username
         FROM guru AS g
         LEFT JOIN users AS u ON u.id = g.user_id
         WHERE g.id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $editId]);
    $editGuru = $statement->fetch() ?: null;
}

$statement = $pdo->query(
    'SELECT g.id, g.nomor_induk, g.nama, g.email, g.no_telepon, g.is_active, u.username
     FROM guru AS g
     LEFT JOIN users AS u ON u.id = g.user_id
     ORDER BY g.nama'
);
$guruList = $statement->fetchAll();

// Repopulate the form from the last POST when validation failed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && $errors !== []) {
    $formValues = [
        'id' => (int) ($_POST['id'] ?? 0),
        'nomor_induk' => guru_post_string('nomor_induk'),
        'nama' => guru_post_string('nama'),
        'email' => guru_post_string('email'),
        'no_telepon' => guru_post_string('no_telepon'),
        'username' => guru_post_string('username'),
    ];
} elseif ($editGuru !== null) {
    $formValues = [
        'id' => (int) $editGuru['id'],
        'nomor_induk' => (string) $editGuru['nomor_induk'],
        'nama' => (string) $editGuru['nama'],
        'email' => (string) ($editGuru['email'] ?? ''),
        'no_telepon' => (string) ($editGuru['no_telepon'] ?? ''),
        'username' => (string) ($editGuru['username'] ?? ''),
    ];
} else {
    $formValues = [
        'id' => 0,
        'nomor_induk' => '',
        'nama' => '',
        'email' => '',
        'no_telepon' => '',
        'username' => '',
    ];
}

$isEditing = $formValues['id'] > 0;

$showForm = $isEditing
    || isset($_GET['form'])
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save' && $errors !== []);

admin_page_start(
    $showForm ? ($isEditing ? 'Edit Guru' : 'Tambah Guru') : 'Data Guru',
    'guru.php',
    $showForm ? null : ['label' => 'Tambah Guru', 'href' => 'guru.php?form=1'],
    $showForm ? null : 'Data profil dan akun login guru.'
);
require __DIR__ . '/views/' . ($showForm ? 'guru-form.php' : 'guru-list.php');
admin_page_end();
