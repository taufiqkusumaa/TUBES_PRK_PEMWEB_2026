<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$sessionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!is_int($sessionId) || $sessionId < 1) {
    http_response_code(404);
    exit('Sesi tidak ditemukan.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Permintaan tidak valid.');
    }

    $postedSessionId = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? null;
    if ($postedSessionId !== $sessionId || !in_array($action, ['finish_session', 'cancel_session'], true)) {
        http_response_code(422);
        exit('Data sesi tidak valid.');
    }

    try {
        guruEndSession($sessionId, $action === 'finish_session' ? 'selesai' : 'dibatalkan');
        header('Location: sesi-aktif.php?id=' . $sessionId);
        exit;
    } catch (DomainException $exception) {
        http_response_code(409);
        header('Content-Type: text/plain; charset=utf-8');
        exit($exception->getMessage());
    } catch (Throwable $exception) {
        guruReportBackendError($exception);
    }
}

try {
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        http_response_code(404);
        exit('Sesi tidak ditemukan.');
    }
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

guru_page_start('Sesi Pembelajaran', 'kelas.php');
?>
<?php guru_session_header($session, '', false); ?>
<p class="text-sm text-slate-600">Mulai aktual: <?= attendara_ui_escape(date('d M Y H:i', strtotime($session['mulai_at']))) ?></p>

<section class="space-y-4">
    <div><h2 class="text-lg font-semibold text-[#303832]">Menu Dalam Sesi</h2></div>
    <div class="grid gap-4 md:grid-cols-3">
        <a href="tap-sesi.php?session_id=<?= (int) $session['id'] ?>" class="group flex items-center justify-between gap-4 rounded-xl border border-[#dfe6de] bg-white p-5 transition hover:border-[#aebcaf] hover:bg-[#fafbf9] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2"><span><span class="block font-semibold text-[#303832]">Tap Kehadiran</span><span class="mt-2 block text-sm text-slate-500">Tambah hitungan tap anonim pada sesi.</span></span><svg aria-hidden="true" class="h-5 w-5 shrink-0 text-[#6c776d] transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></a>
        <a href="absensi.php?session_id=<?= (int) $session['id'] ?>" class="group flex items-center justify-between gap-4 rounded-xl border border-[#dfe6de] bg-white p-5 transition hover:border-[#aebcaf] hover:bg-[#fafbf9] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2"><span><span class="block font-semibold text-[#303832]">Data Siswa &amp; Poin</span><span class="mt-2 block text-sm text-slate-500">Atur status kehadiran dan poin siswa.</span></span><svg aria-hidden="true" class="h-5 w-5 shrink-0 text-[#6c776d] transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></a>
        <a href="kelompok.php?session_id=<?= (int) $session['id'] ?>" class="group flex items-center justify-between gap-4 rounded-xl border border-[#dfe6de] bg-white p-5 transition hover:border-[#aebcaf] hover:bg-[#fafbf9] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#679173] focus-visible:ring-offset-2"><span><span class="block font-semibold text-[#303832]">Kelompok</span><span class="mt-2 block text-sm text-slate-500">Buat dan acak kelompok siswa.</span></span><svg aria-hidden="true" class="h-5 w-5 shrink-0 text-[#6c776d] transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></a>
    </div>
</section>
<a href="kelas-detail.php?assignment_id=<?= (int) $session['assignment_id'] ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#dfe6de] bg-white px-4 py-2 text-sm font-semibold text-[#41684f] hover:bg-[#f2f6f2]">Kembali ke Kelas</a>

<?php guru_page_end(); ?>
