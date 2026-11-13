<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

function is_checked(mixed $value): bool
{
    return $value === true || $value === 1 || $value === '1' || $value === 't' || $value === 'on';
}

function save_uploaded_attachment(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        if (($file['error'] ?? null) === UPLOAD_ERR_INI_SIZE || ($file['error'] ?? null) === UPLOAD_ERR_FORM_SIZE) {
            throw new InputError('Ukuran lampiran maksimal 10 MB.');
        }
        throw new InputError('Lampiran tidak dapat diproses.');
    }
    if ((int) $file['size'] > 10 * 1024 * 1024) {
        throw new InputError('Ukuran lampiran maksimal 10 MB.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
    $extensions = [
        'application/pdf' => '.pdf',
        'image/jpeg' => '.jpg',
        'image/png' => '.png',
    ];
    if (!is_string($mime) || !isset($extensions[$mime])) {
        throw new InputError('Isi lampiran bukan PDF, JPG, atau PNG yang valid.');
    }

    $originalName = basename(str_replace('\\', '/', (string) ($file['name'] ?? 'lampiran')));
    $originalName = trim($originalName);
    if ($originalName === '') {
        $originalName = 'lampiran' . $extensions[$mime];
    }
    $characters = preg_split('//u', $originalName, -1, PREG_SPLIT_NO_EMPTY);
    if (is_array($characters) && count($characters) > 255) {
        $originalName = implode('', array_slice($characters, 0, 255));
    }
    if (!is_dir(UPLOAD_DIRECTORY) && !mkdir(UPLOAD_DIRECTORY, 0750, true) && !is_dir(UPLOAD_DIRECTORY)) {
        throw new RuntimeException('Direktori penyimpanan lampiran tidak dapat dibuat.');
    }

    $storageName = bin2hex(random_bytes(16)) . $extensions[$mime];
    $destination = UPLOAD_DIRECTORY . '/' . $storageName;
    if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
        throw new RuntimeException('Lampiran tidak dapat disimpan.');
    }

    return [
        'storageName' => $storageName,
        'originalName' => $originalName,
        'contentType' => $mime,
        'path' => $destination,
    ];
}

function send_attachment(PDO $db, array $user): never
{
    $caseNumber = $_GET['case'] ?? null;
    $attachmentId = positive_id($_GET['id'] ?? null, 'ID lampiran');
    if (!is_string($caseNumber) || strlen($caseNumber) > 32) {
        http_response_code(404);
        exit('Lampiran tidak ditemukan.');
    }

    $sql = 'SELECT a.storage_name, a.original_name, a.content_type
            FROM report_attachments a
            JOIN reports r ON r.id = a.report_id
            WHERE a.id = :attachment_id AND r.case_number = :case_number';
    $parameters = ['attachment_id' => $attachmentId, 'case_number' => $caseNumber];
    if ($user['role'] !== 'admin') {
        $sql .= ' AND r.user_id = :user_id';
        $parameters['user_id'] = (string) $user['id'];
    }
    $statement = $db->prepare($sql);
    $statement->execute($parameters);
    $attachment = $statement->fetch();
    if (!is_array($attachment)) {
        http_response_code(404);
        exit('Lampiran tidak ditemukan.');
    }

    $storageName = basename((string) $attachment['storage_name']);
    $path = realpath(UPLOAD_DIRECTORY . '/' . $storageName);
    $directory = realpath(UPLOAD_DIRECTORY);
    if ($path === false || $directory === false || !str_starts_with($path, $directory . DIRECTORY_SEPARATOR) || !is_file($path)) {
        http_response_code(404);
        exit('Berkas lampiran tidak tersedia.');
    }

    $safeName = str_replace(["\r", "\n", '"'], ['', '', ''], (string) $attachment['original_name']);
    header('Content-Type: ' . $attachment['content_type']);
    header('Content-Length: ' . (string) filesize($path));
    header('Content-Disposition: attachment; filename="lampiran"; filename*=UTF-8\'\'' . rawurlencode($safeName));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

$aboutPages = [
    'tentangkami',
    'about',
    'about-profile',
    'about-vision-mission',
    'about-structure',
    'about-services',
    'about-contact',
];
$pageNames = array_merge(
    ['auth', 'login', 'register', 'dashboard', 'report', 'history', 'detail', 'admin', 'attachment'],
    $aboutPages,
);
$page = $_GET['page'] ?? (current_user() === null ? 'about-profile' : (current_user()['role'] === 'admin' ? 'admin' : 'dashboard'));
if (!is_string($page) || !in_array($page, $pageNames, true)) {
    http_response_code(404);
    $page = 'login';
    $error = 'Halaman tidak ditemukan.';
} else {
    $error = null;
}
if ($page === 'auth') {
    $page = 'login';
}
$success = flash('success');
$formValues = [];
$report = null;
$reports = [];
$reportFilters = ['search' => '', 'status' => '', 'category' => '', 'dateFrom' => null, 'dateTo' => null];
$reportStatistics = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
$announcements = [];
$announcementToEdit = null;
$announcementCount = 0;
$pendingCount = 0;
$reportCount = 0;
$reportPageSize = 20;
$reportPage = 1;
$reportPageCount = 1;
$requestedReportPage = $_GET['reportPage'] ?? null;
if (is_string($requestedReportPage) && ctype_digit($requestedReportPage)) {
    $reportPage = max(1, (int) $requestedReportPage);
}
$user = current_user();
$db = null;

try {
    $db = database();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $action = $_POST['action'] ?? '';
        if (!is_string($action)) {
            throw new InputError('Permintaan tidak valid.');
        }

        switch ($action) {
            case 'login':
                auth_rate_limit();
                $nim = normalize_nim($_POST['nim'] ?? null);
                $password = $_POST['password'] ?? null;
                if (!is_string($password) || $password === '') {
                    throw new InputError('NPM atau kata sandi tidak valid.', 401);
                }
                $statement = $db->prepare('SELECT id, nim, full_name, role, password_hash FROM users WHERE nim = :nim');
                $statement->execute(['nim' => $nim]);
                $account = $statement->fetch();
                if (!is_array($account) || !password_verify($password, (string) $account['password_hash'])) {
                    throw new InputError('NPM atau kata sandi tidak valid.', 401);
                }
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => (string) $account['id'],
                    'nim' => $account['nim'],
                    'fullName' => $account['full_name'],
                    'role' => $account['role'],
                ];
                unset($_SESSION['_csrf']);
                redirect($account['role'] === 'admin' ? 'admin' : 'dashboard');

            case 'register':
                auth_rate_limit();
                $registration = validate_registration($_POST);
                try {
                    $statement = $db->prepare(
                        "INSERT INTO users (nim, full_name, password_hash)
                         VALUES (:nim, :full_name, :password_hash)
                         RETURNING id, nim, full_name, role",
                    );
                    $statement->execute([
                        'nim' => $registration['nim'],
                        'full_name' => $registration['fullName'],
                        'password_hash' => password_hash($registration['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                    ]);
                    $account = $statement->fetch();
                } catch (PDOException $databaseError) {
                    if ($databaseError->getCode() === '23505') {
                        throw new InputError('NPM sudah terdaftar.', 409);
                    }
                    throw $databaseError;
                }
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => (string) $account['id'],
                    'nim' => $account['nim'],
                    'fullName' => $account['full_name'],
                    'role' => $account['role'],
                ];
                unset($_SESSION['_csrf']);
                redirect('dashboard');

            case 'logout':
                if ($user === null) {
                    redirect('auth');
                }
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $parameters = session_get_cookie_params();
                    setcookie(session_name(), '', [
                        'expires' => time() - 42000,
                        'path' => $parameters['path'],
                        'domain' => $parameters['domain'],
                        'secure' => $parameters['secure'],
                        'httponly' => $parameters['httponly'],
                        'samesite' => $parameters['samesite'] ?? 'Lax',
                    ]);
                }
                session_destroy();
                redirect('auth');

            case 'report':
                $user = require_user();
                if ($user['role'] !== 'user') {
                    redirect('admin');
                }
                $reportInput = validate_report($_POST);
                $db->beginTransaction();
                $attachment = [];
                try {
                    $attachment = save_uploaded_attachment($_FILES['attachment'] ?? []);
                    $sequence = $db->query("SELECT TO_CHAR(CURRENT_DATE, 'YYYY') AS year, nextval('report_case_number_seq') AS number")->fetch();
                    $caseNumber = 'ADV-' . $sequence['year'] . '-' . str_pad((string) $sequence['number'], 6, '0', STR_PAD_LEFT);
                    $statement = $db->prepare(
                        'INSERT INTO reports
                         (case_number, user_id, title, category, incident_date, chronology, is_anonymous)
                         VALUES (:case_number, :user_id, :title, :category, :incident_date, :chronology, :is_anonymous)
                         RETURNING id',
                    );
                    $statement->execute([
                        'case_number' => $caseNumber,
                        'user_id' => (string) $user['id'],
                        'title' => $reportInput['title'],
                        'category' => $reportInput['category'],
                        'incident_date' => $reportInput['incidentDate'],
                        'chronology' => $reportInput['chronology'],
                        'is_anonymous' => $reportInput['anonymous'] ? 'true' : 'false',
                    ]);
                    $reportId = (string) $statement->fetchColumn();
                    if ($attachment !== []) {
                        $statement = $db->prepare(
                            'INSERT INTO report_attachments (report_id, storage_name, original_name, content_type)
                             VALUES (:report_id, :storage_name, :original_name, :content_type)',
                        );
                        $statement->execute([
                            'report_id' => $reportId,
                            'storage_name' => $attachment['storageName'],
                            'original_name' => $attachment['originalName'],
                            'content_type' => $attachment['contentType'],
                        ]);
                    }
                    $db->commit();
                } catch (Throwable $actionError) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    if ($attachment !== [] && is_file($attachment['path']) && !unlink($attachment['path'])) {
                        error_log('Unable to remove incomplete attachment: ' . $attachment['path']);
                    }
                    throw $actionError;
                }
                flash('success', 'Laporan berhasil dikirim.');
                redirect('detail', ['case' => $caseNumber]);

            case 'decision':
                $user = require_user('admin');
                $decision = validate_decision($_POST);
                $reportId = positive_id($_POST['reportId'] ?? null, 'ID laporan');
                $statement = $db->prepare(
                    "UPDATE reports
                     SET status = :status, decision_note = NULLIF(:note, ''),
                         decided_by = :decided_by, decided_at = NOW()
                     WHERE id = :id AND status = 'pending'",
                );
                $statement->execute([
                    'status' => $decision['decision'],
                    'note' => $decision['note'],
                    'decided_by' => (string) $user['id'],
                    'id' => $reportId,
                ]);
                if ($statement->rowCount() !== 1) {
                    throw new InputError('Laporan tidak ditemukan atau sudah diproses.', 409);
                }
                flash('success', 'Keputusan laporan berhasil disimpan.');
                redirect('admin', ['reportPage' => (string) $reportPage]);

            case 'announcement-create':
                $user = require_user('admin');
                $announcement = validate_announcement($_POST);
                $statement = $db->prepare(
                    'INSERT INTO announcements
                     (title, body, category, is_pinned, is_published, published_at, expires_at, created_by)
                     VALUES (:title, :body, :category, :is_pinned, :is_published,
                             CASE WHEN :publish_time THEN NOW() ELSE NULL END, :expires_at, :created_by)',
                );
                $published = $announcement['isPublished'] ? 'true' : 'false';
                $statement->execute([
                    'title' => $announcement['title'],
                    'body' => $announcement['body'],
                    'category' => $announcement['category'],
                    'is_pinned' => $announcement['isPinned'] ? 'true' : 'false',
                    'is_published' => $published,
                    'publish_time' => $published,
                    'expires_at' => $announcement['expiresAt'],
                    'created_by' => (string) $user['id'],
                ]);
                flash('success', 'Pengumuman berhasil disimpan.');
                redirect('admin', ['reportPage' => (string) $reportPage]);

            case 'announcement-edit':
                require_user('admin');
                $announcementId = positive_id($_POST['announcementId'] ?? null, 'ID pengumuman');
                $announcement = validate_announcement($_POST);
                $published = $announcement['isPublished'] ? 'true' : 'false';
                $statement = $db->prepare(
                    'UPDATE announcements
                     SET title = :title, body = :body, category = :category,
                         is_pinned = :is_pinned, is_published = :is_published,
                         published_at = CASE WHEN :publish_time
                             THEN COALESCE(published_at, NOW()) ELSE NULL END,
                         expires_at = :expires_at, updated_at = NOW()
                     WHERE id = :id',
                );
                $statement->execute([
                    'title' => $announcement['title'],
                    'body' => $announcement['body'],
                    'category' => $announcement['category'],
                    'is_pinned' => $announcement['isPinned'] ? 'true' : 'false',
                    'is_published' => $published,
                    'publish_time' => $published,
                    'expires_at' => $announcement['expiresAt'],
                    'id' => $announcementId,
                ]);
                if ($statement->rowCount() !== 1) {
                    throw new InputError('Pengumuman tidak ditemukan atau tidak berubah.', 404);
                }
                flash('success', 'Pengumuman berhasil diperbarui.');
                redirect('admin');

            case 'announcement-toggle':
                require_user('admin');
                $announcementId = positive_id($_POST['announcementId'] ?? null, 'ID pengumuman');
                $isPublished = ($_POST['isPublished'] ?? null) === '1';
                if (!in_array($_POST['isPublished'] ?? null, ['0', '1'], true)) {
                    throw new InputError('Status publikasi tidak valid.');
                }
                $statement = $db->prepare(
                    'UPDATE announcements
                     SET is_published = :is_published,
                         published_at = CASE WHEN :publish_time THEN COALESCE(published_at, NOW()) ELSE NULL END,
                         updated_at = NOW()
                     WHERE id = :id',
                );
                $published = $isPublished ? 'true' : 'false';
                $statement->execute([
                    'is_published' => $published,
                    'publish_time' => $published,
                    'id' => $announcementId,
                ]);
                if ($statement->rowCount() !== 1) {
                    throw new InputError('Pengumuman tidak ditemukan.', 404);
                }
                flash('success', 'Status pengumuman berhasil diperbarui.');
                redirect('admin', ['reportPage' => (string) $reportPage]);

            case 'announcement-delete':
                require_user('admin');
                $announcementId = positive_id($_POST['announcementId'] ?? null, 'ID pengumuman');
                $statement = $db->prepare('DELETE FROM announcements WHERE id = :id');
                $statement->execute(['id' => $announcementId]);
                if ($statement->rowCount() !== 1) {
                    throw new InputError('Pengumuman tidak ditemukan.', 404);
                }
                flash('success', 'Pengumuman berhasil dihapus.');
                redirect('admin', ['reportPage' => (string) $reportPage]);

            default:
                throw new InputError('Permintaan tidak dikenal.');
        }
    }

    $user = current_user();
    if (in_array($page, ['login', 'register'], true) && $user !== null) {
        redirect($user['role'] === 'admin' ? 'admin' : 'dashboard');
    }
    if (!in_array($page, array_merge(['login', 'register', 'attachment'], $aboutPages), true)) {
        $user = require_user();
        if ($page === 'admin' && $user['role'] !== 'admin') {
            redirect('dashboard');
        }
        if ($page !== 'admin' && $user['role'] === 'admin') {
            redirect('admin');
        }
    }

    if ($page === 'attachment') {
        send_attachment($db, require_user());
    }
    if ($page === 'dashboard') {
        $announcements = $db->query(
            'SELECT id, title, body, category, is_pinned, published_at, expires_at
             FROM announcements
             WHERE is_published = TRUE AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY is_pinned DESC, published_at DESC LIMIT 20',
        )->fetchAll();
        foreach ($announcements as &$announcement) {
            $announcement['is_pinned'] = is_checked($announcement['is_pinned']);
        }
        unset($announcement);
        $reports = report_rows($db, (string) $user['id']);
    } elseif ($page === 'history') {
        $reports = report_rows($db, (string) $user['id']);
    } elseif ($page === 'detail') {
        $caseNumber = $_GET['case'] ?? '';
        $report = is_string($caseNumber) ? find_report($db, $caseNumber, $user) : null;
    } elseif ($page === 'admin') {
        try {
            $reportFilters = validate_report_filters($_GET);
        } catch (InputError $filterError) {
            $error = $filterError->getMessage();
        }
        $reports = report_rows($db, (string) $user['id'], true, $reportFilters);
        $reportStatistics = report_statistics($db);
        $reportCount = $reportStatistics['total'];
        $pendingCount = $reportStatistics['pending'];
        $announcements = $db->query(
            'SELECT id, title, body, category, is_pinned, is_published,
                    published_at, expires_at, updated_at
             FROM announcements ORDER BY is_pinned DESC, updated_at DESC LIMIT 100',
        )->fetchAll();
        foreach ($announcements as &$announcement) {
            $announcement['is_pinned'] = is_checked($announcement['is_pinned']);
            $announcement['is_published'] = is_checked($announcement['is_published']);
        }
        unset($announcement);

        if (isset($_GET['editAnnouncement'])) {
            try {
                $editId = positive_id($_GET['editAnnouncement'], 'ID pengumuman');
                $statement = $db->prepare(
                    'SELECT id, title, body, category, is_pinned, is_published, expires_at
                     FROM announcements WHERE id = :id',
                );
                $statement->execute(['id' => $editId]);
                $announcementToEdit = $statement->fetch();
                if (!is_array($announcementToEdit)) {
                    $error = 'Pengumuman tidak ditemukan.';
                } else {
                    $announcementToEdit['is_published'] = is_checked($announcementToEdit['is_published']);
                    $announcementToEdit['is_pinned'] = is_checked($announcementToEdit['is_pinned']);
                }
            } catch (InputError $editError) {
                $error = $editError->getMessage();
            }
        }
        $announcementCount = count($announcements);
    }
} catch (InputError $actionError) {
    http_response_code($actionError->status);
    $error = $actionError->getMessage();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $page = match ($action) {
            'login' => 'login',
            'register' => 'register',
            'report' => 'report',
            'decision', 'announcement-create', 'announcement-edit', 'announcement-toggle', 'announcement-delete' => 'admin',
            default => $page,
        };
        $allowedOldValues = ['nim', 'fullName', 'title', 'category', 'incidentDate', 'chronology', 'anonymous', 'body', 'isPublished', 'isPinned', 'expiresAt'];
        foreach ($allowedOldValues as $name) {
            if (isset($_POST[$name]) && is_string($_POST[$name])) {
                $formValues[$name] = $_POST[$name];
            }
        }
        if ($action === 'decision') {
            foreach (['note' => 'decisionNote', 'decision' => 'decisionChoice', 'reportId' => 'decisionReportId'] as $inputName => $valueName) {
                if (isset($_POST[$inputName]) && is_string($_POST[$inputName])) {
                    $formValues[$valueName] = $_POST[$inputName];
                }
            }
        }
        if (in_array($action, ['announcement-create', 'announcement-edit'], true)) {
            $formValues['action'] = $action;
            if (isset($_POST['announcementId']) && is_string($_POST['announcementId'])) {
                $formValues['announcementId'] = $_POST['announcementId'];
            }
        }
    }
} catch (Throwable $applicationError) {
    error_log('ADVOKITA request failed: ' . $applicationError);
    http_response_code(500);
    $error = 'Terjadi kesalahan pada server. Coba lagi nanti.';
    $page = 'login';
    $user = null;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $page === 'admin'
    && is_array($user)
    && $user['role'] === 'admin'
    && $db instanceof PDO
) {
    try {
        $reports = report_rows($db, (string) $user['id'], true, $reportFilters);
        $reportStatistics = report_statistics($db);
        $reportCount = $reportStatistics['total'];
        $announcements = $db->query(
            'SELECT id, title, body, category, is_pinned, is_published,
                    published_at, expires_at, updated_at
             FROM announcements ORDER BY is_pinned DESC, updated_at DESC LIMIT 100',
        )->fetchAll();
        foreach ($announcements as &$announcement) {
            $announcement['is_pinned'] = is_checked($announcement['is_pinned']);
            $announcement['is_published'] = is_checked($announcement['is_published']);
        }
        unset($announcement);
        $pendingCount = $reportStatistics['pending'];
        $announcementCount = count($announcements);
        if (($formValues['action'] ?? '') === 'announcement-edit' && isset($formValues['announcementId'])) {
            $statement = $db->prepare(
                'SELECT id, title, body, category, is_pinned, is_published, expires_at
                 FROM announcements WHERE id = :id',
            );
            $statement->execute(['id' => $formValues['announcementId']]);
            $announcementToEdit = $statement->fetch() ?: null;
            if (is_array($announcementToEdit)) {
                $announcementToEdit['is_pinned'] = is_checked($announcementToEdit['is_pinned']);
                $announcementToEdit['is_published'] = is_checked($announcementToEdit['is_published']);
            }
        }
    } catch (Throwable $applicationError) {
        error_log('Unable to reload admin data: ' . $applicationError);
        $error = 'Data panel admin tidak dapat dimuat. Coba muat ulang halaman.';
    }
}

if (!in_array($page, ['login', 'register'], true) && $user !== null && isset($db)) {
    try {
        $user = current_user();
    } catch (Throwable $applicationError) {
        error_log('Unable to load current session: ' . $applicationError);
    }
}

$pageTitles = [
    'login' => 'Masuk',
    'register' => 'Registrasi',
    'dashboard' => 'Beranda',
    'report' => 'Pengaduan',
    'history' => 'Riwayat',
    'detail' => 'Detail Pengaduan',
    'admin' => 'Panel Admin',
    'tentangkami' => 'Tentang Kami',
    'about' => 'Tentang Kami',
    'about-profile' => 'Profil Lembaga',
    'about-vision-mission' => 'Visi dan Misi',
    'about-structure' => 'Struktur Organisasi',
    'about-services' => 'Layanan',
    'about-contact' => 'Kontak',
];
$title = $pageTitles[$page] ?? 'ADVOKITA';
require APP_ROOT . '/templates/layout.php';