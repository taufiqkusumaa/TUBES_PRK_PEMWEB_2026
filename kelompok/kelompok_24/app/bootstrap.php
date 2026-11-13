<?php
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';
$defaultUploadDirectory = (getenv('VERCEL') !== false || !empty($_ENV['VERCEL']))
    ? sys_get_temp_dir() . '/advokita_uploads'
    : APP_ROOT . '/storage/uploads';
define('UPLOAD_DIRECTORY', getenv('UPLOAD_DIRECTORY') ?: $defaultUploadDirectory);
const REPORT_CATEGORIES = [
    'UKT & Finansial',
    'Kekerasan & PPKS',
    'Akademik & Etik',
    'Pidana / UU ITE',
    'Lainnya',
];
const ANNOUNCEMENT_CATEGORIES = [
    'Akademik',
    'Kemahasiswaan',
    'Beasiswa',
    'Kegiatan Kampus',
    'Informasi Umum',
];

foreach ([APP_ROOT . '/.env'] as $environmentFile) {
    if (!is_file($environmentFile)) {
        continue;
    }

    foreach (file($environmentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($value !== '' && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
session_name('advokita_sid');
session_set_cookie_params([
    'lifetime' => 8 * 60 * 60,
    'path' => '/',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
ini_set('session.gc_maxlifetime', (string) (8 * 60 * 60));
session_start();

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' data: https://cdn.jsdelivr.net https://fonts.gstatic.com");

final class InputError extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 400)
    {
        parent::__construct($message);
    }
}

function database(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }

    function session_secret(): string
    {
        $secret = getenv('SESSION_SECRET');
        if ($secret === false || strlen($secret) < 32) {
            throw new RuntimeException('SESSION_SECRET harus diatur dengan minimal 32 karakter.');
        }
        return $secret;
    }

    $host = getenv('PGHOST') ?: '127.0.0.1';
    $port = getenv('PGPORT') ?: '5432';
    $name = getenv('PGDATABASE') ?: 'advokita';
    $user = getenv('PGUSER') ?: 'advokita_app';
    $password = getenv('PGPASSWORD');
    if ($password === false || $password === '') {
        throw new RuntimeException('PGPASSWORD belum diatur pada konfigurasi lokal.');
    }

    $connection = new PDO(
        "pgsql:host={$host};port={$port};dbname={$name}",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );
    return $connection;
}

function escape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $page, array $parameters = []): string
{
    return '/index.php?' . http_build_query(['page' => $page] + $parameters);
}

function redirect(string $page, array $parameters = []): never
{
    header('Location: ' . url($page, $parameters), true, 303);
    exit;
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function require_user(?string $role = null): array
{
    $user = current_user();
    if ($user === null) {
        flash('error', 'Silakan masuk terlebih dahulu.');
        redirect('auth');
    }

    if ($role !== null && $user['role'] !== $role) {
        redirect($user['role'] === 'admin' ? 'admin' : 'dashboard');
    }

    return $user;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return is_string($message) ? $message : null;
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_input(): void
{
    echo '<input type="hidden" name="_csrf" value="' . escape(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['_csrf'] ?? null;
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        throw new InputError('Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.', 403);
    }
}

function text_length(string $value): int
{
    $count = preg_match_all('/./us', $value, $matches);
    return $count === false ? strlen($value) : $count;
}

function required_text(mixed $value, string $label, int $maximum): string
{
    if (!is_string($value)) {
        throw new InputError("{$label} wajib diisi.");
    }
    $normalized = trim($value);
    if ($normalized === '' || text_length($normalized) > $maximum) {
        throw new InputError("{$label} wajib diisi dan maksimal {$maximum} karakter.");
    }
    return $normalized;
}

function normalize_nim(mixed $value): string
{
    if (!is_string($value) || !preg_match('/^[0-9]{4,20}$/D', trim($value))) {
        throw new InputError('NPM harus berupa angka 4 sampai 20 digit.');
    }
    return trim($value);
}

function validate_registration(array $input): array
{
    $fullName = required_text($input['fullName'] ?? null, 'Nama lengkap', 120);
    $nim = normalize_nim($input['nim'] ?? null);
    $password = $input['password'] ?? null;
    if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
        throw new InputError('Kata sandi harus memiliki 8 sampai 72 byte.');
    }
    if (!is_string($input['passwordConfirm'] ?? null) || !hash_equals($password, $input['passwordConfirm'])) {
        throw new InputError('Konfirmasi kata sandi tidak cocok.');
    }
    return ['fullName' => $fullName, 'nim' => $nim, 'password' => $password];
}

function validate_report(array $input): array
{
    $title = required_text($input['title'] ?? null, 'Judul laporan', 160);
    $category = required_text($input['category'] ?? null, 'Kategori', 60);
    if (!in_array($category, REPORT_CATEGORIES, true)) {
        throw new InputError('Kategori laporan tidak valid.');
    }

    $incidentDate = required_text($input['incidentDate'] ?? null, 'Tanggal kejadian', 10);
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $incidentDate);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (
        $date === false
        || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        || $date->format('Y-m-d') !== $incidentDate
    ) {
        throw new InputError('Tanggal kejadian tidak valid.');
    }
    if ($incidentDate > gmdate('Y-m-d')) {
        throw new InputError('Tanggal kejadian tidak boleh di masa depan.');
    }

    $chronology = required_text($input['chronology'] ?? null, 'Kronologi', 2000);
    if (text_length($chronology) < 20) {
        throw new InputError('Kronologi harus memiliki minimal 20 karakter.');
    }

    return [
        'title' => $title,
        'category' => $category,
        'incidentDate' => $incidentDate,
        'chronology' => $chronology,
        'anonymous' => isset($input['anonymous']) && $input['anonymous'] === 'true',
    ];
}

function validate_announcement(array $input): array
{
    $title = required_text($input['title'] ?? null, 'Judul pengumuman', 120);
    $body = required_text($input['body'] ?? null, 'Isi pengumuman', 3000);
    $category = required_text($input['category'] ?? null, 'Kategori pengumuman', 32);
    if (!in_array($category, ANNOUNCEMENT_CATEGORIES, true)) {
        throw new InputError('Kategori pengumuman tidak valid.');
    }

    foreach (['isPublished', 'isPinned'] as $checkboxName) {
        if (array_key_exists($checkboxName, $input) && $input[$checkboxName] !== 'on') {
            throw new InputError('Status pengumuman tidak valid.');
        }
    }

    $expiresAt = $input['expiresAt'] ?? '';
    if (!is_string($expiresAt)) {
        throw new InputError('Tanggal berakhir tidak valid.');
    }
    $expiresAt = trim($expiresAt);
    if ($expiresAt !== '') {
        $expiryDate = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $expiresAt);
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (
            $expiryDate === false
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $expiryDate->format('Y-m-d\TH:i') !== $expiresAt
        ) {
            throw new InputError('Tanggal berakhir tidak valid.');
        }
        $expiresAt = $expiryDate->format('Y-m-d H:i:sP');
    } else {
        $expiresAt = null;
    }

    return [
        'title' => $title,
        'body' => $body,
        'category' => $category,
        'isPublished' => isset($input['isPublished']),
        'isPinned' => isset($input['isPinned']),
        'expiresAt' => $expiresAt,
    ];
}

function validate_report_filters(array $input): array
{
    $search = $input['search'] ?? '';
    $status = $input['status'] ?? '';
    $category = $input['category'] ?? '';

    if (!is_string($search) || text_length(trim($search)) > 160) {
        throw new InputError('Pencarian maksimal 160 karakter.');
    }
    if (!is_string($status) || !in_array($status, ['', 'pending', 'approved', 'rejected'], true)) {
        throw new InputError('Filter status tidak valid.');
    }
    if (!is_string($category) || ($category !== '' && !in_array($category, REPORT_CATEGORIES, true))) {
        throw new InputError('Filter kategori tidak valid.');
    }

    $dates = [];
    foreach (['dateFrom' => 'Tanggal mulai', 'dateTo' => 'Tanggal akhir'] as $name => $label) {
        $dateValue = $input[$name] ?? '';
        if (!is_string($dateValue)) {
            throw new InputError("{$label} tidak valid.");
        }
        $dateValue = trim($dateValue);
        if ($dateValue === '') {
            $dates[$name] = null;
            continue;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue);
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (
            $date === false
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $date->format('Y-m-d') !== $dateValue
        ) {
            throw new InputError("{$label} tidak valid.");
        }
        $dates[$name] = $dateValue;
    }
    $dateFrom = $dates['dateFrom'];
    $dateTo = $dates['dateTo'];
    if ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo) {
        throw new InputError('Rentang tanggal tidak valid.');
    }

    return [
        'search' => trim($search),
        'status' => $status,
        'category' => $category,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
    ];
}

function validate_decision(array $input): array
{
    $decision = $input['decision'] ?? null;
    if (!in_array($decision, ['approved', 'rejected'], true)) {
        throw new InputError('Pilih setujui atau tolak.');
    }
    $note = required_text($input['note'] ?? null, 'Catatan pertimbangan hukum', 2000);
    return ['decision' => $decision, 'note' => trim($note)];
}

function positive_id(mixed $value, string $label): string
{
    if (!is_string($value) && !is_int($value)) {
        throw new InputError("{$label} tidak valid.");
    }
    $value = (string) $value;
    if (!preg_match('/^[1-9][0-9]{0,18}$/D', $value) || (strlen($value) === 19 && strcmp($value, '9223372036854775807') > 0)) {
        throw new InputError("{$label} tidak valid.");
    }
    return $value;
}

function auth_rate_limit(): void
{
    $db = database();
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $key = hash_hmac('sha256', $ip, session_secret());
    $db->beginTransaction();
    try {
        $lock = $db->prepare("SELECT pg_advisory_xact_lock(hashtext('advokita-auth'), hashtext(:ip_hash))");
        $lock->execute(['ip_hash' => $key]);
        $statement = $db->prepare("SELECT COUNT(*) FROM auth_attempts WHERE ip_hash = :ip_hash AND created_at > NOW() - INTERVAL '15 minutes'");
        $statement->execute(['ip_hash' => $key]);
        if ((int) $statement->fetchColumn() >= 10) {
            $db->rollBack();
            throw new InputError('Terlalu banyak percobaan. Coba lagi beberapa saat.', 429);
        }
        $statement = $db->prepare('INSERT INTO auth_attempts (ip_hash) VALUES (:ip_hash)');
        $statement->execute(['ip_hash' => $key]);
        $db->commit();
        if (random_int(1, 100) === 1) {
            $db->exec("DELETE FROM auth_attempts WHERE created_at < NOW() - INTERVAL '1 day'");
        }
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}

function report_rows(PDO $db, string $userId, bool $admin = false, array $filters = []): array
{
    $sql = "SELECT r.*, CASE WHEN r.is_anonymous THEN NULL ELSE u.full_name END AS submitter_name,
            CASE WHEN r.is_anonymous THEN NULL ELSE u.nim END AS submitter_nim,
            reviewer.full_name AS decided_by_name
            FROM reports r
            JOIN users u ON u.id = r.user_id
            LEFT JOIN users reviewer ON reviewer.id = r.decided_by";
    $conditions = [];
    $parameters = [];
    if (!$admin) {
        $conditions[] = 'r.user_id = :user_id';
        $parameters['user_id'] = $userId;
    } else {
        if (($filters['search'] ?? '') !== '') {
            $conditions[] = '(r.title ILIKE :title_search OR r.chronology ILIKE :chronology_search)';
            $searchPattern = '%' . $filters['search'] . '%';
            $parameters['title_search'] = $searchPattern;
            $parameters['chronology_search'] = $searchPattern;
        }
        if (($filters['status'] ?? '') !== '') {
            $conditions[] = 'r.status = :status';
            $parameters['status'] = $filters['status'];
        }
        if (($filters['category'] ?? '') !== '') {
            $conditions[] = 'r.category = :category';
            $parameters['category'] = $filters['category'];
        }
        if (($filters['dateFrom'] ?? null) !== null) {
            $conditions[] = 'r.created_at >= CAST(:date_from AS DATE)';
            $parameters['date_from'] = $filters['dateFrom'];
        }
        if (($filters['dateTo'] ?? null) !== null) {
            $conditions[] = 'r.created_at < CAST(:date_to AS DATE) + INTERVAL \'1 day\'';
            $parameters['date_to'] = $filters['dateTo'];
        }
    }
    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= $admin
        ? " ORDER BY CASE r.status WHEN 'pending' THEN 0 ELSE 1 END, r.created_at ASC"
        : ' ORDER BY r.created_at DESC';

    $statement = $db->prepare($sql);
    $statement->execute($parameters);
    $reports = $statement->fetchAll();

    $attachmentsByReport = [];
    if ($admin && $reports !== []) {
        $reportIds = array_values(array_column($reports, 'id'));
        $placeholders = [];
        $parameters = [];
        foreach ($reportIds as $index => $reportId) {
            $parameter = 'report_id_' . $index;
            $placeholders[] = ':' . $parameter;
            $parameters[$parameter] = (string) $reportId;
        }

        $attachmentStatement = $db->prepare(
            'SELECT report_id, id, original_name AS name
             FROM report_attachments
             WHERE report_id IN (' . implode(', ', $placeholders) . ')
             ORDER BY report_id, id',
        );
        $attachmentStatement->execute($parameters);
    } elseif (!$admin || $reports !== []) {
        $attachmentSql = 'SELECT a.report_id, a.id, a.original_name AS name
                          FROM report_attachments a
                          JOIN reports r ON r.id = a.report_id';
        if (!$admin) {
            $attachmentSql .= ' WHERE r.user_id = :user_id';
        }
        $attachmentSql .= ' ORDER BY a.report_id, a.id';
        $attachmentStatement = $db->prepare($attachmentSql);
        if ($admin) {
            $attachmentStatement->execute();
        } else {
            $attachmentStatement->execute(['user_id' => $userId]);
        }
    }

    if (isset($attachmentStatement)) {
        foreach ($attachmentStatement->fetchAll() as $attachment) {
            $attachmentsByReport[(string) $attachment['report_id']][] = [
                'id' => $attachment['id'],
                'name' => $attachment['name'],
            ];
        }
    }

    foreach ($reports as &$report) {
        $report['attachments'] = $attachmentsByReport[(string) $report['id']] ?? [];
    }
    unset($report);
    return $reports;
}

function report_statistics(PDO $db): array
{
    $statement = $db->query(
        "SELECT COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status = 'pending') AS pending,
                COUNT(*) FILTER (WHERE status = 'approved') AS approved,
                COUNT(*) FILTER (WHERE status = 'rejected') AS rejected
         FROM reports",
    );
    $statistics = $statement->fetch();
    return array_map('intval', $statistics);
}

function attachment_rows(PDO $db, string $reportId): array
{
    $statement = $db->prepare('SELECT id, original_name AS name FROM report_attachments WHERE report_id = :report_id ORDER BY id');
    $statement->execute(['report_id' => $reportId]);
    return $statement->fetchAll();
}

function find_report(PDO $db, string $caseNumber, array $user): ?array
{
    $sql = "SELECT r.*, CASE WHEN r.is_anonymous THEN NULL ELSE u.full_name END AS submitter_name,
            CASE WHEN r.is_anonymous THEN NULL ELSE u.nim END AS submitter_nim,
            reviewer.full_name AS decided_by_name
            FROM reports r
            JOIN users u ON u.id = r.user_id
            LEFT JOIN users reviewer ON reviewer.id = r.decided_by
            WHERE r.case_number = :case_number";
    $parameters = ['case_number' => $caseNumber];
    if ($user['role'] !== 'admin') {
        $sql .= ' AND r.user_id = :user_id';
        $parameters['user_id'] = (string) $user['id'];
    }
    $statement = $db->prepare($sql);
    $statement->execute($parameters);
    $report = $statement->fetch();
    if (!is_array($report)) {
        return null;
    }
    $report['attachments'] = attachment_rows($db, (string) $report['id']);
    return $report;
}

function format_date(?string $value): string
{
    if ($value === null || $value === '') {
        return 'Tanggal tidak tersedia';
    }
    try {
        $date = new DateTimeImmutable($value);
    } catch (Throwable) {
        return 'Tanggal tidak tersedia';
    }
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    return $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
}

function format_datetime(?string $value): string
{
    if ($value === null || $value === '') {
        return 'Waktu tidak tersedia';
    }
    try {
        return (new DateTimeImmutable($value))->format('j M Y, H:i');
    } catch (Throwable) {
        return 'Waktu tidak tersedia';
    }
}

function status_label(string $status): string
{
    return match ($status) {
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        default => 'Menunggu ditinjau',
    };
}

function status_class(string $status): string
{
    return match ($status) {
        'approved' => 'text-bg-success',
        'rejected' => 'text-bg-danger',
        default => 'text-bg-warning',
    };
}
