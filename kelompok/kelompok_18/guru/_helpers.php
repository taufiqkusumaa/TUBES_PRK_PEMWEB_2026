<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class GuruTapCooldownException extends RuntimeException
{
    public function __construct(string $message, public readonly int $retryAfter = 2)
    {
        parent::__construct($message);
    }
}

/** Return the PDO connection created by the shared application config. */
function guruDb(): PDO
{
    global $pdo;

    return $pdo;
}

/** Return the teacher profile linked to the authenticated user. */
function guruProfile(): ?array
{
    $statement = guruDb()->prepare(
        'SELECT id, user_id, nomor_induk, nama, email, no_telepon
         FROM guru
         WHERE user_id = :user_id AND is_active = 1
         LIMIT 1'
    );
    $statement->execute(['user_id' => currentUser()['id']]);
    $profile = $statement->fetch();

    return is_array($profile) ? $profile : null;
}

/** Return the single active semester, or null when none (or multiple) are configured. */
function guruActiveSemester(): ?array
{
    $statement = guruDb()->prepare(
        "SELECT id, kode, nama, tanggal_mulai, tanggal_selesai
         FROM semester
         WHERE status = :status
         ORDER BY tanggal_mulai DESC, id DESC
         LIMIT 2"
    );
    $statement->execute(['status' => 'aktif']);
    $semesters = $statement->fetchAll();

    if (count($semesters) > 1) {
        error_log('ATTENDARA Guru: more than one active semester is configured.');
        return null;
    }

    return $semesters[0] ?? null;
}

/** Fetch active semester assignments for the authenticated teacher. */
function guruActiveAssignments(int $guruId, int $semesterId): array
{
    $statement = guruDb()->prepare(
        'SELECT pg.id AS assignment_id, k.id AS id, k.kode AS code, k.nama AS name,
                k.tingkat AS level, s.id AS semester_id, s.nama AS semester,
                mp.id AS subject_id, mp.kode AS subject_code, mp.nama AS subject_name,
                COUNT(DISTINCT sis.id) AS student_count
         FROM penugasan_guru pg
         INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
         INNER JOIN semester s ON s.id = k.semester_id
         INNER JOIN mata_pelajaran mp ON mp.id = pg.mata_pelajaran_id
         LEFT JOIN anggota_kelas ak ON ak.kelas_id = k.id AND ak.semester_id = s.id
         LEFT JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
         WHERE pg.guru_id = :guru_id AND k.semester_id = :semester_id
         GROUP BY pg.id, k.id, k.kode, k.nama, k.tingkat, s.id, s.nama,
                  mp.id, mp.kode, mp.nama
         ORDER BY k.nama, k.id'
    );
    $statement->execute(['guru_id' => $guruId, 'semester_id' => $semesterId]);

    return $statement->fetchAll();
}

/** Fetch schedules belonging to the teacher's active-semester assignments. */
function guruActiveSchedules(int $guruId, int $semesterId): array
{
    $statement = guruDb()->prepare(
        'SELECT jm.id, pg.id AS assignment_id, k.id AS class_id, k.nama AS class_name,
                mp.nama AS subject_name, s.nama AS semester, jm.hari AS day_number, jm.jam_mulai AS start,
                jm.jam_selesai AS end, jm.berlaku_mulai AS valid_from,
                jm.berlaku_sampai AS valid_until
         FROM jadwal_mengajar jm
         INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
         INNER JOIN semester s ON s.id = k.semester_id
         INNER JOIN mata_pelajaran mp ON mp.id = pg.mata_pelajaran_id
         WHERE pg.guru_id = :guru_id AND k.semester_id = :semester_id
           AND (jm.berlaku_mulai IS NULL OR jm.berlaku_mulai <= CURRENT_DATE())
           AND (jm.berlaku_sampai IS NULL OR jm.berlaku_sampai >= CURRENT_DATE())
         ORDER BY jm.hari, jm.jam_mulai, k.nama'
    );
    $statement->execute(['guru_id' => $guruId, 'semester_id' => $semesterId]);
    $schedules = $statement->fetchAll();

    foreach ($schedules as &$schedule) {
        $schedule['day_label'] = guruDayLabel((int) $schedule['day_number']);
        $schedule['valid_from'] = $schedule['valid_from'] === null ? 'Tidak dibatasi' : date('d M Y', strtotime($schedule['valid_from']));
        $schedule['valid_until'] = $schedule['valid_until'] === null ? 'Tidak dibatasi' : date('d M Y', strtotime($schedule['valid_until']));
    }
    unset($schedule);

    return $schedules;
}

/** Find one class only when the teacher owns its active-semester assignment. */
function guruOwnedActiveClass(int $guruId, int $semesterId, int $classId): ?array
{
    $statement = guruDb()->prepare(
        'SELECT pg.id AS assignment_id, k.id, k.kode AS code, k.nama AS name,
                k.tingkat AS level, s.id AS semester_id, s.nama AS semester,
                pg.mata_pelajaran_id AS subject_id, mp.kode AS subject_code,
                mp.nama AS subject_name
         FROM penugasan_guru pg
         INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
         INNER JOIN semester s ON s.id = k.semester_id
         INNER JOIN mata_pelajaran mp ON mp.id = pg.mata_pelajaran_id
         WHERE pg.guru_id = :guru_id AND k.semester_id = :semester_id AND k.id = :class_id
         ORDER BY pg.id
         LIMIT 1'
    );
    $statement->execute([
        'guru_id' => $guruId,
        'semester_id' => $semesterId,
        'class_id' => $classId,
    ]);
    $class = $statement->fetch();

    return is_array($class) ? $class : null;
}

/** Find one specific active assignment owned by a teacher. */
function guruOwnedActiveAssignment(int $guruId, int $semesterId, int $assignmentId): ?array
{
    $statement = guruDb()->prepare(
        'SELECT pg.id AS assignment_id, pg.guru_id, pg.kelas_id AS id,
                pg.mata_pelajaran_id AS subject_id, mp.kode AS subject_code,
                mp.nama AS subject_name, k.kode AS code, k.nama AS name,
                k.tingkat AS level, k.semester_id, s.nama AS semester
         FROM penugasan_guru pg
         INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
         INNER JOIN semester s ON s.id = k.semester_id AND s.status = :active_status
         INNER JOIN mata_pelajaran mp ON mp.id = pg.mata_pelajaran_id
         WHERE pg.id = :assignment_id AND pg.guru_id = :guru_id
           AND k.semester_id = :semester_id
         LIMIT 1'
    );
    $statement->execute([
        'active_status' => 'aktif', 'assignment_id' => $assignmentId,
        'guru_id' => $guruId, 'semester_id' => $semesterId,
    ]);
    $assignment = $statement->fetch();

    return is_array($assignment) ? $assignment : null;
}

/** Translate the SQL weekday number (1=Monday ... 7=Sunday) to Indonesian. */
function guruDayLabel(int $dayNumber): string
{
    return [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
        5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'][$dayNumber] ?? 'Hari tidak valid';
}

/** Fetch active students assigned to a class in the specified semester. */
function guruClassStudents(int $classId, int $semesterId): array
{
    $statement = guruDb()->prepare(
        'SELECT sis.id, sis.nomor_induk AS number, sis.nama AS name
         FROM anggota_kelas ak
         INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
         WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id
         ORDER BY sis.nama, sis.id'
    );
    $statement->execute(['class_id' => $classId, 'semester_id' => $semesterId]);

    return $statement->fetchAll();
}

/** Fetch schedules for one teacher-owned active-semester class. */
function guruClassSchedules(int $assignmentId): array
{
    $statement = guruDb()->prepare(
        'SELECT id, hari AS day_number, jam_mulai AS start, jam_selesai AS end,
                berlaku_mulai AS valid_from, berlaku_sampai AS valid_until
         FROM jadwal_mengajar
         WHERE penugasan_guru_id = :assignment_id
           AND (berlaku_mulai IS NULL OR berlaku_mulai <= CURRENT_DATE())
           AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURRENT_DATE())
         ORDER BY hari, jam_mulai'
    );
    $statement->execute(['assignment_id' => $assignmentId]);
    $schedules = $statement->fetchAll();
    foreach ($schedules as &$schedule) {
        $schedule['day_label'] = guruDayLabel((int) $schedule['day_number']);
        $schedule['valid_from'] = $schedule['valid_from'] === null ? 'Tidak dibatasi' : date('d M Y', strtotime($schedule['valid_from']));
        $schedule['valid_until'] = $schedule['valid_until'] === null ? 'Tidak dibatasi' : date('d M Y', strtotime($schedule['valid_until']));
    }
    unset($schedule);

    return $schedules;
}

/** Create or resume the ongoing session for one owned schedule, with its roster snapshot. */
function guruStartSession(int $assignmentId, int $scheduleId): int
{
    $semester = guruActiveSemester();
    if ($semester === null) {
        throw new DomainException('Profil Guru atau semester aktif tidak tersedia.');
    }

    $user = currentUser();
    if ($user === null) {
        throw new DomainException('Sesi login tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        // Lock the schedule row first so simultaneous start requests for it serialize.
        $scheduleQuery = $pdo->prepare(
            'SELECT jm.id AS schedule_id, pg.id AS assignment_id,
                    pg.kelas_id, k.semester_id
             FROM jadwal_mengajar jm
             INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
             INNER JOIN semester sem ON sem.id = k.semester_id AND sem.status = :active_status
             WHERE jm.id = :schedule_id AND pg.id = :assignment_id
               AND g.user_id = :user_id AND k.semester_id = :semester_id
               AND (jm.berlaku_mulai IS NULL OR jm.berlaku_mulai <= CURRENT_DATE())
               AND (jm.berlaku_sampai IS NULL OR jm.berlaku_sampai >= CURRENT_DATE())
             LIMIT 1 FOR UPDATE'
        );
        $scheduleQuery->execute([
            'active_status' => 'aktif', 'schedule_id' => $scheduleId,
            'assignment_id' => $assignmentId, 'user_id' => $user['id'],
            'semester_id' => $semester['id'],
        ]);
        $schedule = $scheduleQuery->fetch();
        if (!is_array($schedule)) {
            throw new DomainException('Jadwal tidak ditemukan, tidak berlaku, atau bukan milik penugasan Anda.');
        }

        $ongoingQuery = $pdo->prepare(
            "SELECT id FROM sesi_pembelajaran
             WHERE jadwal_mengajar_id = :schedule_id AND status = :status
             ORDER BY id DESC LIMIT 1 FOR UPDATE"
        );
        $ongoingQuery->execute(['schedule_id' => $schedule['schedule_id'], 'status' => 'berlangsung']);
        $ongoingSessionId = $ongoingQuery->fetchColumn();
        if ($ongoingSessionId !== false) {
            $pdo->commit();
            return (int) $ongoingSessionId;
        }

        $insert = $pdo->prepare(
            "INSERT INTO sesi_pembelajaran
                (jadwal_mengajar_id, mulai_at, status, jumlah_tap)
             VALUES (:schedule_id, CURRENT_TIMESTAMP(), :status, 0)"
        );
        $insert->execute([
            'schedule_id' => $schedule['schedule_id'],
            'status' => 'berlangsung',
        ]);
        $sessionId = (int) $pdo->lastInsertId();

        $attendance = $pdo->prepare(
            "INSERT INTO absensi
                (sesi_pembelajaran_id, siswa_id, status, dicatat_oleh_user_id, waktu_dicatat)
             SELECT :session_id, ak.siswa_id, :attendance_status, :user_id, CURRENT_TIMESTAMP()
             FROM anggota_kelas ak
             INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
             WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id"
        );
        $attendance->execute([
            'session_id' => $sessionId,
            'attendance_status' => 'hadir',
            'user_id' => $user['id'],
            'class_id' => $schedule['kelas_id'],
            'semester_id' => $schedule['semester_id'],
        ]);
        if ($attendance->rowCount() < 1) {
            throw new DomainException('Sesi tidak dapat dimulai karena roster kelas belum memiliki siswa aktif.');
        }
        $pdo->commit();

        return $sessionId;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Fetch a session only when it belongs to the authenticated teacher. */
function guruOwnedSession(int $sessionId): ?array
{
    $statement = guruDb()->prepare(
        'SELECT sp.id, pg.id AS assignment_id, jm.id AS schedule_id,
                DATE(sp.mulai_at) AS tanggal, sp.mulai_at AS waktu_mulai,
                sp.selesai_at AS waktu_selesai, sp.mulai_at, sp.selesai_at,
                sp.jumlah_tap, sp.tap_terakhir_at, sp.status,
                k.id AS class_id, k.kode AS class_code, k.nama AS class_name,
                sem.id AS semester_id, sem.nama AS semester, sem.status AS semester_status,
                mp.id AS subject_id, mp.nama AS subject_name,
                jm.hari AS day_number, jm.jam_mulai AS scheduled_start, jm.jam_selesai AS scheduled_end
         FROM sesi_pembelajaran sp
         INNER JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
         INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
         INNER JOIN guru g ON g.id = pg.guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id
         INNER JOIN semester sem ON sem.id = k.semester_id
         INNER JOIN mata_pelajaran mp ON mp.id = pg.mata_pelajaran_id
         WHERE sp.id = :session_id AND g.user_id = :user_id
         LIMIT 1'
    );
    $statement->execute(['session_id' => $sessionId, 'user_id' => currentUser()['id']]);
    $session = $statement->fetch();

    if (!is_array($session)) {
        return null;
    }

    $session['day_label'] = $session['day_number'] === null ? null : guruDayLabel((int) $session['day_number']);
    return $session;
}

/** Fetch attendance and point totals from one owned session's fixed snapshot. */
function guruSessionAttendance(int $sessionId): array
{
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        throw new DomainException('Sesi tidak ditemukan.');
    }

    $statement = guruDb()->prepare(
        "SELECT sis.id, sis.nomor_induk AS number, sis.nama AS name,
                a.status AS attendance_status, a.catatan AS note,
                COALESCE(p.total_points, 0) AS points
         FROM absensi a
         INNER JOIN siswa sis ON sis.id = a.siswa_id
         LEFT JOIN (
             SELECT sesi_pembelajaran_id, siswa_id, SUM(poin) AS total_points
             FROM poin_keaktifan WHERE sesi_pembelajaran_id = :points_session_id
             GROUP BY sesi_pembelajaran_id, siswa_id
         ) p ON p.sesi_pembelajaran_id = :joined_session_id AND p.siswa_id = sis.id
         WHERE a.sesi_pembelajaran_id = :session_id
         ORDER BY sis.nama, sis.id"
    );
    $statement->execute([
        'session_id' => $sessionId,
        'points_session_id' => $sessionId,
        'joined_session_id' => $sessionId,
    ]);

    return $statement->fetchAll();
}

/** Increment an anonymous tap counter using the session row as a global lock. */
function guruTapAttendance(int $sessionId): array
{
    $user = currentUser();
    if ($user === null) {
        throw new DomainException('Sesi login tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $sessionQuery = $pdo->prepare(
            "SELECT sp.id, sp.jumlah_tap, sp.status,
                    TIMESTAMPDIFF(SECOND, sp.tap_terakhir_at, CURRENT_TIMESTAMP()) AS elapsed_seconds
             FROM sesi_pembelajaran sp
             INNER JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
             INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             WHERE sp.id = :session_id AND g.user_id = :user_id
             LIMIT 1 FOR UPDATE"
        );
        $sessionQuery->execute(['session_id' => $sessionId, 'user_id' => $user['id']]);
        $session = $sessionQuery->fetch();
        if (!is_array($session) || $session['status'] !== 'berlangsung') {
            throw new DomainException('Sesi tidak ditemukan atau tidak sedang berlangsung.');
        }
        // The schema stores tap_terakhir_at at whole-second precision; a 3-second
        // database bucket guarantees that two real tap events are at least 2s apart.
        if ($session['elapsed_seconds'] !== null && (int) $session['elapsed_seconds'] < 3) {
            $retryAfter = max(1, 3 - (int) $session['elapsed_seconds']);
            throw new GuruTapCooldownException('Tunggu hingga jeda tap terpenuhi.', $retryAfter);
        }

        $rosterQuery = $pdo->prepare(
            'SELECT COUNT(*) FROM absensi WHERE sesi_pembelajaran_id = :session_id'
        );
        $rosterQuery->execute(['session_id' => $sessionId]);
        $rosterCount = (int) $rosterQuery->fetchColumn();
        $tapCount = (int) $session['jumlah_tap'];
        if ($tapCount >= $rosterCount) {
            throw new DomainException('Jumlah tap sudah mencapai jumlah siswa pada sesi ini.');
        }

        $update = $pdo->prepare(
            "UPDATE sesi_pembelajaran
             SET jumlah_tap = jumlah_tap + 1, tap_terakhir_at = CURRENT_TIMESTAMP()
             WHERE id = :session_id AND status = :ongoing_status"
        );
        $update->execute(['session_id' => $sessionId, 'ongoing_status' => 'berlangsung']);
        $timestamp = $pdo->prepare(
            "SELECT DATE_FORMAT(tap_terakhir_at, '%H:%i:%s')
             FROM sesi_pembelajaran WHERE id = :session_id"
        );
        $timestamp->execute(['session_id' => $sessionId]);
        $tapTime = (string) $timestamp->fetchColumn();
        $pdo->commit();

        return ['time' => $tapTime, 'tap_count' => $tapCount + 1, 'roster_count' => $rosterCount];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Return the persisted anonymous tap count for one owned session. */
function guruTapCount(int $sessionId): int
{
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        throw new DomainException('Sesi tidak ditemukan.');
    }

    return (int) $session['jumlah_tap'];
}

/** Count students in a session's fixed attendance snapshot. */
function guruSessionRosterCount(int $sessionId): int
{
    if (guruOwnedSession($sessionId) === null) {
        throw new DomainException('Sesi tidak ditemukan.');
    }

    $statement = guruDb()->prepare(
        'SELECT COUNT(*) FROM absensi WHERE sesi_pembelajaran_id = :session_id'
    );
    $statement->execute(['session_id' => $sessionId]);

    return (int) $statement->fetchColumn();
}

/** Correct the anonymous tap count while the owned session is still ongoing. */
function guruCorrectTapCount(int $sessionId, int $tapCount): void
{
    if ($tapCount < 0) {
        throw new DomainException('Jumlah tap tidak boleh kurang dari nol.');
    }
    $user = currentUser();
    if ($user === null) {
        throw new DomainException('Sesi login tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $sessionQuery = $pdo->prepare(
            'SELECT sp.id, sp.status
             FROM sesi_pembelajaran sp
             INNER JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
             INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             WHERE sp.id = :session_id AND g.user_id = :user_id
             LIMIT 1 FOR UPDATE'
        );
        $sessionQuery->execute(['session_id' => $sessionId, 'user_id' => $user['id']]);
        $session = $sessionQuery->fetch();
        if (!is_array($session) || $session['status'] !== 'berlangsung') {
            throw new DomainException('Jumlah tap hanya dapat dikoreksi pada sesi yang sedang berlangsung milik Anda.');
        }

        $rosterQuery = $pdo->prepare(
            'SELECT COUNT(*) FROM absensi WHERE sesi_pembelajaran_id = :session_id'
        );
        $rosterQuery->execute(['session_id' => $sessionId]);
        $rosterCount = (int) $rosterQuery->fetchColumn();
        if ($tapCount > $rosterCount) {
            throw new DomainException('Jumlah tap tidak boleh melebihi jumlah siswa pada sesi ini.');
        }

        $update = $pdo->prepare(
            'UPDATE sesi_pembelajaran SET jumlah_tap = :tap_count
             WHERE id = :session_id AND status = :ongoing_status'
        );
        $update->execute([
            'tap_count' => $tapCount, 'session_id' => $sessionId,
            'ongoing_status' => 'berlangsung',
        ]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Add a manual participation score to an owned, ongoing session. */
function guruAddPoints(int $sessionId, int $studentId, string $points, ?string $note): void
{
    $points = trim($points);
    if (
        !preg_match('/^(?:0|[1-9][0-9]{0,4})(?:\.[0-9]{1,2})?$/', $points)
        || preg_match('/^0(?:\.0{1,2})?$/', $points)
    ) {
        throw new DomainException('Poin harus lebih dari 0 dan maksimal 99999.99 dengan maksimal 2 angka desimal.');
    }
    if ($note !== null && (function_exists('mb_strlen') ? mb_strlen($note, 'UTF-8') : strlen($note)) > 500) {
        throw new DomainException('Catatan maksimal 500 karakter.');
    }

    $user = currentUser();
    if ($user === null) {
        throw new DomainException('Sesi login tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $sessionQuery = $pdo->prepare(
            'SELECT sp.id, sp.status
             FROM sesi_pembelajaran sp
             INNER JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
             INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             WHERE sp.id = :session_id AND g.user_id = :user_id
             LIMIT 1 FOR UPDATE'
        );
        $sessionQuery->execute([
            'session_id' => $sessionId,
            'user_id' => $user['id'],
        ]);
        $session = $sessionQuery->fetch();
        if (!is_array($session) || $session['status'] !== 'berlangsung') {
            throw new DomainException('Poin hanya dapat dicatat pada sesi Guru yang sedang berlangsung.');
        }

        $studentQuery = $pdo->prepare(
            'SELECT id FROM absensi
             WHERE sesi_pembelajaran_id = :session_id AND siswa_id = :student_id
             LIMIT 1 FOR UPDATE'
        );
        $studentQuery->execute([
            'session_id' => $sessionId,
            'student_id' => $studentId,
        ]);
        if ($studentQuery->fetchColumn() === false) {
            throw new DomainException('Siswa tidak terdaftar pada kelas sesi ini.');
        }

        $insert = $pdo->prepare(
            'INSERT INTO poin_keaktifan
                (sesi_pembelajaran_id, siswa_id, poin, dicatat_oleh_user_id, catatan, waktu_dicatat)
             VALUES (:session_id, :student_id, :points, :user_id, :note, CURRENT_TIMESTAMP())'
        );
        $insert->execute([
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'points' => $points,
            'user_id' => $user['id'],
            'note' => $note === '' ? null : $note,
        ]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Fetch score history for one student in an owned session. */
function guruStudentPointHistory(int $sessionId, int $studentId): array
{
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        throw new DomainException('Sesi tidak ditemukan.');
    }

    $snapshot = guruDb()->prepare(
        'SELECT id FROM absensi WHERE sesi_pembelajaran_id = :session_id AND siswa_id = :student_id LIMIT 1'
    );
    $snapshot->execute(['session_id' => $sessionId, 'student_id' => $studentId]);
    if ($snapshot->fetchColumn() === false) {
        throw new DomainException('Siswa tidak memiliki baris absensi pada snapshot sesi ini.');
    }

    $statement = guruDb()->prepare(
        'SELECT pk.poin, pk.waktu_dicatat, pk.catatan, u.username AS recorded_by
         FROM poin_keaktifan pk
         INNER JOIN users u ON u.id = pk.dicatat_oleh_user_id
         WHERE pk.sesi_pembelajaran_id = :session_id AND pk.siswa_id = :student_id
         ORDER BY pk.waktu_dicatat DESC, pk.id DESC'
    );
    $statement->execute(['session_id' => $sessionId, 'student_id' => $studentId]);

    return $statement->fetchAll();
}

/** Return historical class and semester filter options from teacher-owned assignments. */
function guruArchiveFilters(): array
{
    $classes = guruDb()->prepare(
        'SELECT DISTINCT k.id, k.nama AS name
         FROM penugasan_guru pg
         INNER JOIN guru g ON g.id = pg.guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id
         WHERE g.user_id = :user_id
         ORDER BY k.nama, k.id'
    );
    $classes->execute(['user_id' => currentUser()['id']]);

    $semesters = guruDb()->prepare(
        'SELECT DISTINCT sem.id, sem.nama AS name, sem.tanggal_mulai
         FROM penugasan_guru pg
         INNER JOIN guru g ON g.id = pg.guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id
         INNER JOIN semester sem ON sem.id = k.semester_id
         WHERE g.user_id = :user_id
         ORDER BY sem.tanggal_mulai DESC, sem.id DESC'
    );
    $semesters->execute(['user_id' => currentUser()['id']]);

    return ['classes' => $classes->fetchAll(), 'semesters' => $semesters->fetchAll()];
}

/** Fetch completed and cancelled sessions for the authenticated teacher with optional filters. */
function guruArchiveSessions(?int $classId = null, ?int $semesterId = null): array
{
    $sql =
        'SELECT sp.id, DATE(sp.mulai_at) AS tanggal,
                sp.mulai_at AS waktu_mulai, sp.selesai_at AS waktu_selesai,
                jm.jam_mulai AS scheduled_start, jm.jam_selesai AS scheduled_end,
                k.id AS class_id, k.nama AS class_name,
                sem.id AS semester_id, sem.nama AS semester,
                mp.nama AS subject_name, sp.status,
                COUNT(DISTINCT a.siswa_id) AS student_count
         FROM sesi_pembelajaran sp
         INNER JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
         INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
         INNER JOIN guru g ON g.id = pg.guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id
         INNER JOIN semester sem ON sem.id = k.semester_id
         INNER JOIN mata_pelajaran mp ON mp.id = pg.mata_pelajaran_id
         LEFT JOIN absensi a ON a.sesi_pembelajaran_id = sp.id
         WHERE g.user_id = :user_id AND sp.status IN (:completed_status, :cancelled_status)';
    $parameters = [
        'user_id' => currentUser()['id'],
        'completed_status' => 'selesai',
        'cancelled_status' => 'dibatalkan',
    ];

    if ($classId !== null) {
        $sql .= ' AND k.id = :class_id';
        $parameters['class_id'] = $classId;
    }
    if ($semesterId !== null) {
        $sql .= ' AND sem.id = :semester_id';
        $parameters['semester_id'] = $semesterId;
    }

    $sql .=
        ' GROUP BY sp.id, sp.mulai_at, sp.selesai_at,
                  jm.jam_mulai, jm.jam_selesai, k.id, k.nama,
                  sem.id, sem.nama, mp.nama, sp.status
          ORDER BY sp.mulai_at DESC, sp.id DESC';
    $statement = guruDb()->prepare($sql);
    $statement->execute($parameters);

    return $statement->fetchAll();
}

/** Return one terminal owned session and its snapshot attendance and completed-session score totals. */
function guruArchiveDetail(int $sessionId): ?array
{
    $session = guruOwnedSession($sessionId);
    if ($session === null || !in_array($session['status'], ['selesai', 'dibatalkan'], true)) {
        return null;
    }

    $pointJoin = $session['status'] === 'selesai'
        ? 'LEFT JOIN (
               SELECT sesi_pembelajaran_id, siswa_id, SUM(poin) AS total_points
               FROM poin_keaktifan
               WHERE sesi_pembelajaran_id = :points_session_id
               GROUP BY sesi_pembelajaran_id, siswa_id
           ) p ON p.sesi_pembelajaran_id = a.sesi_pembelajaran_id AND p.siswa_id = a.siswa_id'
        : '';
    $pointSelect = $session['status'] === 'selesai' ? 'COALESCE(p.total_points, 0)' : '0';
    $statement = guruDb()->prepare(
        "SELECT sis.id, sis.nomor_induk AS number, sis.nama AS name,
                a.status AS attendance_status,
                {$pointSelect} AS points
         FROM absensi a
         INNER JOIN siswa sis ON sis.id = a.siswa_id
         {$pointJoin}
         WHERE a.sesi_pembelajaran_id = :session_id
         ORDER BY sis.nama, sis.id"
    );
    $parameters = ['session_id' => $sessionId];
    if ($session['status'] === 'selesai') {
        $parameters['points_session_id'] = $sessionId;
    }
    $statement->execute($parameters);
    $session['students'] = $statement->fetchAll();

    return $session;
}

/** Fetch groups for one teacher-owned active assignment. */
function guruGroupsForAssignment(int $guruId, int $assignmentId): array
{
    $statement = guruDb()->prepare(
        'SELECT kg.id, kg.nama AS name, COUNT(ak.siswa_id) AS member_count
         FROM kelompok kg
         INNER JOIN penugasan_guru pg ON pg.id = kg.penugasan_guru_id
         LEFT JOIN anggota_kelompok ak ON ak.kelompok_id = kg.id
         WHERE pg.id = :assignment_id AND pg.guru_id = :guru_id
         GROUP BY kg.id, kg.nama
         ORDER BY kg.nama, kg.id'
    );
    $statement->execute(['assignment_id' => $assignmentId, 'guru_id' => $guruId]);

    return $statement->fetchAll();
}

/** Fetch active assignment roster, gender and membership in one of its groups. */
function guruGroupStudents(int $assignmentId, ?int $groupId = null): array
{
    $statement = guruDb()->prepare(
        'SELECT sis.id, sis.nomor_induk AS number, sis.nama AS name,
                sis.jenis_kelamin AS gender,
                CASE WHEN ag.kelompok_id IS NULL THEN 0 ELSE 1 END AS is_member
         FROM penugasan_guru pg
         INNER JOIN kelas k ON k.id = pg.kelas_id
         INNER JOIN anggota_kelas ak ON ak.kelas_id = k.id AND ak.semester_id = k.semester_id
         INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
         LEFT JOIN anggota_kelompok ag ON ag.siswa_id = sis.id AND ag.kelompok_id = :group_id
         WHERE pg.id = :assignment_id
         ORDER BY sis.nama, sis.id'
    );
    $statement->execute(['group_id' => $groupId, 'assignment_id' => $assignmentId]);

    return $statement->fetchAll();
}

/** Validate and transactionally save a complete, gender-balanced roster partition. */
function guruCreateGroups(
    int $assignmentId,
    string $namePrefix,
    array $memberGroups,
    string $groupRule,
    int $groupValue,
    int $minimumMale,
    int $minimumFemale
): void {
    $namePrefix = trim($namePrefix);
    $length = function_exists('mb_strlen') ? mb_strlen($namePrefix, 'UTF-8') : strlen($namePrefix);
    if ($namePrefix === '' || $length > 80) {
        throw new DomainException('Nama kelompok wajib diisi dan maksimal 80 karakter.');
    }
    if (!in_array($groupRule, ['groups', 'members'], true) || $groupValue < 1 || $groupValue > 20) {
        throw new DomainException('Kriteria pembagian kelompok tidak valid.');
    }
    if ($minimumMale < 0 || $minimumMale > 20 || $minimumFemale < 0 || $minimumFemale > 20) {
        throw new DomainException('Jumlah minimum gender harus berada dalam rentang 0 sampai 20.');
    }

    $user = currentUser();
    $semester = guruActiveSemester();
    if ($user === null || $semester === null) {
        throw new DomainException('Akun Guru atau semester aktif tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $assignmentQuery = $pdo->prepare(
            'SELECT pg.id, pg.kelas_id, k.semester_id
             FROM penugasan_guru pg
             INNER JOIN guru g ON g.id = pg.guru_id
             INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
             INNER JOIN semester sem ON sem.id = k.semester_id AND sem.status = :active_status
             WHERE pg.id = :assignment_id AND g.user_id = :user_id
               AND k.semester_id = :semester_id
             LIMIT 1 FOR UPDATE'
        );
        $assignmentQuery->execute([
            'active_status' => 'aktif', 'assignment_id' => $assignmentId,
            'user_id' => $user['id'], 'semester_id' => $semester['id'],
        ]);
        $assignment = $assignmentQuery->fetch();
        if (!is_array($assignment)) {
            throw new DomainException('Penugasan tidak ditemukan atau bukan milik Anda pada semester aktif.');
        }

        $existingGroupsQuery = $pdo->prepare(
            'SELECT id FROM kelompok WHERE penugasan_guru_id = :assignment_id LIMIT 1 FOR UPDATE'
        );
        $existingGroupsQuery->execute(['assignment_id' => $assignmentId]);
        if ($existingGroupsQuery->fetchColumn() !== false) {
            throw new DomainException('Kelompok untuk penugasan ini sudah tersedia. Edit kelompok yang ada untuk mempertahankan satu pembagian roster.');
        }

        $rosterQuery = $pdo->prepare(
            'SELECT sis.id, sis.jenis_kelamin
             FROM anggota_kelas ak
             INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
             WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id
             ORDER BY sis.id
             FOR UPDATE'
        );
        $rosterQuery->execute([
            'class_id' => $assignment['kelas_id'], 'semester_id' => $assignment['semester_id'],
        ]);
        $rosterRows = $rosterQuery->fetchAll();
        $roster = [];
        $genderTotals = ['L' => 0, 'P' => 0];
        foreach ($rosterRows as $row) {
            $id = (int) $row['id'];
            $gender = (string) $row['jenis_kelamin'];
            if (!isset($genderTotals[$gender])) {
                throw new DomainException('Jenis kelamin siswa tidak valid pada roster.');
            }
            $roster[$id] = $gender;
            $genderTotals[$gender]++;
        }

        $studentCount = count($roster);
        if ($studentCount < 1) {
            throw new DomainException('Roster aktif penugasan ini belum memiliki siswa.');
        }
        $groupCount = $groupRule === 'groups'
            ? $groupValue
            : (int) ceil($studentCount / $groupValue);
        if ($groupCount > $studentCount || ($groupRule === 'groups' && $groupCount > 20)) {
            throw new DomainException('Jumlah kelompok tidak boleh melebihi siswa dan maksimal 20 kelompok.');
        }
        if (count($memberGroups) !== $groupCount) {
            throw new DomainException('Jumlah hasil kelompok tidak sesuai dengan kriteria yang dipilih.');
        }
        $minimumTogether = $minimumMale + $minimumFemale;
        if ($minimumTogether > (int) ceil($studentCount / $groupCount)) {
            throw new DomainException('Jumlah minimum laki-laki dan perempuan melebihi kapasitas kelompok terbesar.');
        }

        $seen = [];
        $groupSizes = [];
        $genderCounts = [];
        foreach ($memberGroups as $groupIndex => $members) {
            if (!is_array($members) || $members === []) {
                throw new DomainException('Setiap kelompok harus memiliki anggota.');
            }
            $groupSizes[$groupIndex] = count($members);
            $genderCounts[$groupIndex] = ['L' => 0, 'P' => 0];
            foreach ($members as $studentId) {
                if (!is_int($studentId) || !isset($roster[$studentId]) || isset($seen[$studentId])) {
                    throw new DomainException('Hasil kelompok mengandung siswa di luar roster atau anggota ganda.');
                }
                $seen[$studentId] = true;
                $genderCounts[$groupIndex][$roster[$studentId]]++;
            }
        }
        if (count($seen) !== $studentCount) {
            throw new DomainException('Semua siswa aktif harus masuk tepat ke satu kelompok.');
        }
        if (max($groupSizes) - min($groupSizes) > 1) {
            throw new DomainException('Ukuran kelompok harus seimbang dengan selisih maksimal satu siswa.');
        }
        if ($groupRule === 'members' && max($groupSizes) > $groupValue) {
            throw new DomainException('Jumlah anggota melebihi batas per kelompok yang dipilih.');
        }

        $possibleSatisfied = $groupCount;
        if ($minimumMale > 0) {
            $possibleSatisfied = min($possibleSatisfied, intdiv($genderTotals['L'], $minimumMale));
        }
        if ($minimumFemale > 0) {
            $possibleSatisfied = min($possibleSatisfied, intdiv($genderTotals['P'], $minimumFemale));
        }
        $possibleSatisfied = min($possibleSatisfied, count(array_filter(
            $groupSizes,
            static fn (int $size): bool => $size >= $minimumTogether
        )));
        $satisfied = 0;
        foreach ($genderCounts as $counts) {
            if ($counts['L'] >= $minimumMale && $counts['P'] >= $minimumFemale) {
                $satisfied++;
            }
        }
        if ($satisfied < $possibleSatisfied) {
            throw new DomainException('Hasil pengacakan belum memenuhi jumlah maksimum kelompok untuk minimum gender. Acak ulang.');
        }


        $duplicateName = $pdo->prepare(
            'SELECT id FROM kelompok WHERE penugasan_guru_id = :assignment_id AND nama = :name LIMIT 1'
        );
        $insertGroup = $pdo->prepare(
            'INSERT INTO kelompok (penugasan_guru_id, nama) VALUES (:assignment_id, :name)'
        );
        $insertMember = $pdo->prepare(
            'INSERT INTO anggota_kelompok (kelompok_id, siswa_id) VALUES (:group_id, :student_id)'
        );
        foreach (array_values($memberGroups) as $index => $members) {
            $groupName = $namePrefix . ' ' . ($index + 1);
            $groupLength = function_exists('mb_strlen') ? mb_strlen($groupName, 'UTF-8') : strlen($groupName);
            if ($groupLength > 100) {
                throw new DomainException('Nama kelompok melebihi batas database.');
            }
            $duplicateName->execute(['assignment_id' => $assignmentId, 'name' => $groupName]);
            if ($duplicateName->fetchColumn() !== false) {
                throw new DomainException('Nama kelompok sudah digunakan pada penugasan ini.');
            }
            $insertGroup->execute(['assignment_id' => $assignmentId, 'name' => $groupName]);
            $newGroupId = (int) $pdo->lastInsertId();
            foreach ($members as $studentId) {
                $insertMember->execute(['group_id' => $newGroupId, 'student_id' => $studentId]);
            }
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Return a group only when its owning assignment belongs to this active-semester teacher. */
function guruOwnedActiveGroup(int $groupId): ?array
{
    $user = currentUser();
    $semester = guruActiveSemester();
    if ($user === null || $semester === null) {
        return null;
    }

    $statement = guruDb()->prepare(
        'SELECT kg.id, kg.penugasan_guru_id AS assignment_id,
                pg.kelas_id AS class_id, k.semester_id,
                kg.nama AS name, k.nama AS class_name, sem.nama AS semester,
                mp.nama AS subject_name
         FROM kelompok kg
         INNER JOIN penugasan_guru pg ON pg.id = kg.penugasan_guru_id
         INNER JOIN guru g ON g.id = pg.guru_id
         INNER JOIN kelas k ON k.id = pg.kelas_id
         INNER JOIN semester sem ON sem.id = k.semester_id AND sem.status = :active_status
         INNER JOIN mata_pelajaran mp ON mp.id = pg.mata_pelajaran_id
         WHERE kg.id = :group_id AND g.user_id = :user_id
           AND k.semester_id = :semester_id AND k.is_active = 1
         LIMIT 1'
    );
    $statement->execute([
        'active_status' => 'aktif', 'group_id' => $groupId,
        'user_id' => $user['id'], 'semester_id' => $semester['id'],
    ]);
    $group = $statement->fetch();

    return is_array($group) ? $group : null;
}

/** Update one active-assignment group and its member set transactionally. */
function guruUpdateGroup(int $groupId, string $name, array $studentIds): void
{
    $name = trim($name);
    $nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
    if ($name === '' || $nameLength > 100) {
        throw new DomainException('Nama kelompok wajib diisi dan maksimal 100 karakter.');
    }
    if ($studentIds === [] || count(array_unique($studentIds)) !== count($studentIds)) {
        throw new DomainException('Kelompok tidak boleh kosong dan anggota tidak boleh ganda.');
    }
    foreach ($studentIds as $studentId) {
        if (!is_int($studentId) || $studentId < 1) {
            throw new DomainException('Daftar anggota tidak valid.');
        }
    }

    $user = currentUser();
    $semester = guruActiveSemester();
    if ($user === null || $semester === null) {
        throw new DomainException('Akun Guru atau semester aktif tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $groupQuery = $pdo->prepare(
            'SELECT kg.id, kg.penugasan_guru_id AS assignment_id,
                    pg.kelas_id AS class_id, k.semester_id
             FROM kelompok kg
             INNER JOIN penugasan_guru pg ON pg.id = kg.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             INNER JOIN kelas k ON k.id = pg.kelas_id AND k.is_active = 1
             INNER JOIN semester sem ON sem.id = k.semester_id AND sem.status = :active_status
             WHERE kg.id = :group_id AND g.user_id = :user_id
               AND k.semester_id = :semester_id
             LIMIT 1 FOR UPDATE'
        );
        $groupQuery->execute([
            'active_status' => 'aktif', 'group_id' => $groupId,
            'user_id' => $user['id'], 'semester_id' => $semester['id'],
        ]);
        $group = $groupQuery->fetch();
        if (!is_array($group)) {
            throw new DomainException('Kelompok tidak ditemukan atau tidak dapat dikelola pada semester ini.');
        }

        $rosterQuery = $pdo->prepare(
            'SELECT ak.siswa_id
             FROM anggota_kelas ak
             INNER JOIN siswa sis ON sis.id = ak.siswa_id AND sis.is_active = 1
             WHERE ak.kelas_id = :class_id AND ak.semester_id = :semester_id'
        );
        $rosterQuery->execute([
            'class_id' => $group['class_id'], 'semester_id' => $group['semester_id'],
        ]);
        $roster = array_map('intval', $rosterQuery->fetchAll(PDO::FETCH_COLUMN));
        foreach ($studentIds as $studentId) {
            if (!in_array($studentId, $roster, true)) {
                throw new DomainException('Anggota kelompok harus berasal dari roster aktif penugasan ini.');
            }
        }

        $otherMembersQuery = $pdo->prepare(
            'SELECT ag.siswa_id
             FROM anggota_kelompok ag
             INNER JOIN kelompok other_group ON other_group.id = ag.kelompok_id
             WHERE other_group.penugasan_guru_id = :assignment_id
               AND other_group.id <> :group_id'
        );
        $otherMembersQuery->execute([
            'assignment_id' => $group['assignment_id'], 'group_id' => $groupId,
        ]);
        $membersInOtherGroups = array_map('intval', $otherMembersQuery->fetchAll(PDO::FETCH_COLUMN));
        if (array_intersect($studentIds, $membersInOtherGroups) !== []) {
            throw new DomainException('Siswa yang dipilih sudah menjadi anggota kelompok lain pada penugasan ini.');
        }

        if (count(array_unique($membersInOtherGroups)) !== count($membersInOtherGroups)) {
            throw new DomainException('Pembagian kelompok saat ini memiliki anggota ganda. Perbaiki data kelompok terlebih dahulu.');
        }
        $allAssigned = array_fill_keys($membersInOtherGroups, true);
        foreach ($studentIds as $studentId) {
            $allAssigned[$studentId] = true;
        }
        if (count($allAssigned) !== count($roster)) {
            throw new DomainException('Perubahan harus mempertahankan seluruh roster dalam tepat satu kelompok.');
        }
        $missing = array_diff($roster, array_keys($allAssigned));
        if ($missing !== []) {
            throw new DomainException('Perubahan harus mempertahankan seluruh roster dalam tepat satu kelompok.');
        }

        $duplicateName = $pdo->prepare(
            'SELECT id FROM kelompok
             WHERE penugasan_guru_id = :assignment_id AND nama = :name AND id <> :group_id
             LIMIT 1'
        );
        $duplicateName->execute([
            'assignment_id' => $group['assignment_id'], 'name' => $name, 'group_id' => $groupId,
        ]);
        if ($duplicateName->fetchColumn() !== false) {
            throw new DomainException('Nama kelompok sudah digunakan pada penugasan ini.');
        }

        $update = $pdo->prepare('UPDATE kelompok SET nama = :name WHERE id = :group_id');
        $update->execute(['name' => $name, 'group_id' => $groupId]);
        $deleteMembers = $pdo->prepare('DELETE FROM anggota_kelompok WHERE kelompok_id = :group_id');
        $deleteMembers->execute(['group_id' => $groupId]);
        $insertMember = $pdo->prepare(
            'INSERT INTO anggota_kelompok (kelompok_id, siswa_id) VALUES (:group_id, :student_id)'
        );
        foreach ($studentIds as $studentId) {
            $insertMember->execute(['group_id' => $groupId, 'student_id' => $studentId]);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}
/** Update one snapshot attendance row during its owned session. */
function guruSaveAttendance(int $sessionId, int $studentId, string $status, ?string $note): void
{
    if (!in_array($status, ['hadir', 'izin', 'alpha', 'sakit'], true)) {
        throw new DomainException('Status kehadiran tidak valid.');
    }
    if ($note !== null && (function_exists('mb_strlen') ? mb_strlen($note, 'UTF-8') : strlen($note)) > 500) {
        throw new DomainException('Catatan maksimal 500 karakter.');
    }

    $user = currentUser();
    if ($user === null) {
        throw new DomainException('Sesi login tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $sessionQuery = $pdo->prepare(
            'SELECT sp.id, sp.status
             FROM sesi_pembelajaran sp
             INNER JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
             INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             WHERE sp.id = :session_id AND g.user_id = :user_id
             LIMIT 1 FOR UPDATE'
        );
        $sessionQuery->execute([
            'session_id' => $sessionId,
            'user_id' => $user['id'],
        ]);
        $session = $sessionQuery->fetch();
        if (!is_array($session) || $session['status'] !== 'berlangsung') {
            throw new DomainException('Absensi hanya dapat diubah pada sesi yang sedang berlangsung milik Anda.');
        }

        $existing = $pdo->prepare(
            'SELECT id FROM absensi
             WHERE sesi_pembelajaran_id = :session_id AND siswa_id = :student_id
             LIMIT 1 FOR UPDATE'
        );
        $existing->execute(['session_id' => $sessionId, 'student_id' => $studentId]);
        $attendanceId = $existing->fetchColumn();
        if ($attendanceId === false) {
            throw new DomainException('Siswa tidak memiliki baris absensi pada snapshot sesi ini.');
        }

        $save = $pdo->prepare(
            'UPDATE absensi
             SET status = :status, dicatat_oleh_user_id = :user_id,
                 catatan = :note, waktu_dicatat = CURRENT_TIMESTAMP()
             WHERE id = :attendance_id AND sesi_pembelajaran_id = :session_id'
        );
        $save->execute([
            'status' => $status,
            'user_id' => $user['id'],
            'note' => $note === '' ? null : $note,
            'attendance_id' => $attendanceId,
            'session_id' => $sessionId,
        ]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Finish an owned ongoing session only when its tap and absence counts reconcile, or cancel it. */
function guruEndSession(int $sessionId, string $status): void
{
    if (!in_array($status, ['selesai', 'dibatalkan'], true)) {
        throw new InvalidArgumentException('Status sesi tidak valid.');
    }
    $user = currentUser();
    if ($user === null) {
        throw new DomainException('Sesi login tidak tersedia.');
    }

    $pdo = guruDb();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            "SELECT sp.id, sp.status, sp.jumlah_tap
             FROM sesi_pembelajaran sp
             INNER JOIN jadwal_mengajar jm ON jm.id = sp.jadwal_mengajar_id
             INNER JOIN penugasan_guru pg ON pg.id = jm.penugasan_guru_id
             INNER JOIN guru g ON g.id = pg.guru_id
             WHERE sp.id = :session_id AND g.user_id = :user_id
             LIMIT 1 FOR UPDATE"
        );
        $statement->execute([
            'session_id' => $sessionId,
            'user_id' => $user['id'],
        ]);
        $session = $statement->fetch();
        if (!is_array($session)) {
            throw new DomainException('Sesi tidak ditemukan atau bukan milik Anda.');
        }

        if ($status === 'selesai') {
            if ($session['status'] !== 'berlangsung') {
                throw new DomainException('Hanya sesi yang sedang berlangsung yang dapat diselesaikan.');
            }
            $counts = $pdo->prepare(
                "SELECT COUNT(*) AS roster_count,
                        COALESCE(SUM(CASE WHEN status <> 'hadir' THEN 1 ELSE 0 END), 0) AS nonpresent_count
                 FROM absensi WHERE sesi_pembelajaran_id = :session_id"
            );
            $counts->execute(['session_id' => $sessionId]);
            $result = $counts->fetch();
            $rosterCount = (int) $result['roster_count'];
            $nonpresentCount = (int) $result['nonpresent_count'];
            $tapCount = (int) $session['jumlah_tap'];
            if ($tapCount + $nonpresentCount !== $rosterCount) {
                throw new DomainException(
                    'Sesi belum dapat diselesaikan: jumlah tap ditambah siswa bukan hadir harus sama dengan jumlah siswa pada snapshot.'
                );
            }
            $update = $pdo->prepare(
                "UPDATE sesi_pembelajaran
                 SET status = :terminal_status, selesai_at = CURRENT_TIMESTAMP()
                 WHERE id = :session_id AND status = :ongoing_status"
            );
            $update->execute([
                'terminal_status' => 'selesai', 'session_id' => $sessionId,
                'ongoing_status' => 'berlangsung',
            ]);
        } elseif ($session['status'] === 'berlangsung') {
            $update = $pdo->prepare(
                "UPDATE sesi_pembelajaran
                 SET status = :terminal_status, selesai_at = CURRENT_TIMESTAMP()
                 WHERE id = :session_id AND status = :ongoing_status"
            );
            $update->execute([
                'terminal_status' => 'dibatalkan', 'session_id' => $sessionId,
                'ongoing_status' => 'berlangsung',
            ]);
        } elseif ($session['status'] === 'selesai') {
            // Cancellation of a completed session changes only its terminal label.
            $update = $pdo->prepare(
                'UPDATE sesi_pembelajaran SET status = :terminal_status
                 WHERE id = :session_id AND status = :completed_status'
            );
            $update->execute([
                'terminal_status' => 'dibatalkan', 'session_id' => $sessionId,
                'completed_status' => 'selesai',
            ]);
        } else {
            throw new DomainException('Sesi yang sudah dibatalkan tidak dapat diubah kembali.');
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** Return a generic response for backend data loading failures. */
function guruReportBackendError(Throwable $exception): never
{
    error_log('ATTENDARA Guru page failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Data halaman Guru sementara tidak dapat dimuat.');
}
