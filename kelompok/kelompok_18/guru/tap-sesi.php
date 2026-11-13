<?php

declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$pageError = '';
$requestedTapCount = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    if (!verifyCsrfToken($_POST['_csrf_token'] ?? null)) {
        http_response_code(403);
        if ($action === 'tap') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Permintaan tidak valid.']);
            exit;
        }
        exit('Permintaan tidak valid.');
    }

    $postedSessionId = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);
    if (!is_int($postedSessionId) || $postedSessionId < 1) {
        http_response_code(422);
        if ($action === 'tap') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Data sesi tidak valid.']);
            exit;
        }
        exit('Data sesi tidak valid.');
    }

    if ($action === 'correct_count') {
        $requestedTapCount = filter_input(INPUT_POST, 'tap_count', FILTER_VALIDATE_INT);
        if (!is_int($requestedTapCount)) {
            http_response_code(422);
            $pageError = 'Jumlah tap harus berupa bilangan bulat.';
        } else {
            try {
                guruCorrectTapCount($postedSessionId, $requestedTapCount);
                header('Location: tap-sesi.php?session_id=' . $postedSessionId . '&correction=saved');
                exit;
            } catch (DomainException $exception) {
                http_response_code(409);
                $pageError = $exception->getMessage();
            } catch (Throwable $exception) {
                guruReportBackendError($exception);
            }
        }
    } elseif ($action === 'tap') {
        header('Content-Type: application/json; charset=utf-8');
        try {
            echo json_encode(guruTapAttendance($postedSessionId), JSON_THROW_ON_ERROR);
            exit;
        } catch (GuruTapCooldownException $exception) {
            http_response_code(429);
            echo json_encode(['error' => $exception->getMessage(), 'retry_after' => $exception->retryAfter]);
            exit;
        } catch (DomainException $exception) {
            http_response_code(409);
            echo json_encode(['error' => $exception->getMessage()]);
            exit;
        } catch (Throwable $exception) {
            error_log('ATTENDARA Guru tap failed: ' . $exception->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Tap tidak dapat diproses. Coba kembali.']);
            exit;
        }
    } else {
        http_response_code(422);
        exit('Aksi tap tidak valid.');
    }
}

$sessionId = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);
if (!is_int($sessionId) || $sessionId < 1) {
    http_response_code(404);
    exit('Sesi tidak ditemukan.');
}

try {
    $session = guruOwnedSession($sessionId);
    if ($session === null) {
        http_response_code(404);
        exit('Sesi tidak ditemukan.');
    }
    if ($session['status'] !== 'berlangsung') {
        http_response_code(409);
        exit('Tap hanya tersedia pada sesi yang sedang berlangsung.');
    }
    $tapCount = guruTapCount($sessionId);
    $rosterCount = guruSessionRosterCount($sessionId);
} catch (Throwable $exception) {
    guruReportBackendError($exception);
}

$requestedTapCount ??= $tapCount;
$csrfJson = json_encode(csrfToken(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '""';

guru_page_start('Tap Kehadiran', 'kelas.php');
?>
<?php guru_session_header($session, 'tap'); ?>
<?php if ($pageError !== ''): ?><p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert"><?= attendara_ui_escape($pageError) ?></p><?php elseif (($_GET['correction'] ?? '') === 'saved'): ?><p class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">Jumlah tap berhasil dikoreksi.</p><?php endif; ?>
<section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
    <button id="anonymous-tap" type="button"<?= $tapCount >= $rosterCount ? ' disabled' : '' ?> aria-label="Catat satu tap kehadiran" class="flex min-h-[19rem] flex-col items-center justify-center rounded-3xl bg-[#4e7a5d] px-6 text-white shadow-sm transition hover:bg-[#41684f] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#b6d2ba] disabled:cursor-not-allowed disabled:bg-slate-400 lg:min-h-[26rem]"><span class="text-3xl font-semibold sm:text-4xl"><?= $tapCount >= $rosterCount ? 'Batas Tap Tercapai' : 'TAP KEHADIRAN' ?></span><span class="mt-3 text-sm text-white/80">Tekan tombol ini atau gunakan Space</span></button>
    <aside class="grid grid-cols-2 content-start gap-3">
        <div class="rounded-2xl border border-[#dfe6de] bg-white p-3 text-center"><p class="text-xs font-semibold uppercase tracking-wide text-[#526257]">Tap tercatat</p><p class="mt-2 text-3xl font-bold tabular-nums text-[#173e2b]"><span id="tap-count"><?= (int) $tapCount ?></span><span class="text-base font-medium text-slate-400"> / <span id="roster-count"><?= (int) $rosterCount ?></span></span></p></div>
        <form method="post" action="tap-sesi.php?session_id=<?= (int) $sessionId ?>" class="rounded-2xl border border-[#dfe6de] bg-white p-3">
        <input type="hidden" name="_csrf_token" value="<?= attendara_ui_escape(csrfToken()) ?>">
        <input type="hidden" name="action" value="correct_count">
        <input type="hidden" name="session_id" value="<?= (int) $sessionId ?>">
        <label for="tap-count-input" class="mb-2 block text-sm font-semibold text-[#303832]">Ubah jumlah</label>
        <div class="flex gap-2"><input id="tap-count-input" name="tap_count" type="number" min="0" max="<?= (int) $rosterCount ?>" value="<?= (int) $requestedTapCount ?>" required class="min-h-10 min-w-0 flex-1 rounded-lg border border-[#dfe6de] px-3 text-sm"><button type="submit" aria-label="Simpan koreksi hitungan tap" class="min-h-10 rounded-lg border border-[#dfe6de] bg-white px-3 text-sm font-semibold text-[#41684f] hover:bg-[#f2f6f2]">Ubah</button></div>
        </form>
        <p id="tap-feedback" class="col-span-2 min-h-10 text-sm text-slate-600" aria-live="polite">Cooldown antar-tap: 2 detik.</p>
    </aside>
</section>
<div class="flex flex-wrap gap-2"><a href="sesi-aktif.php?id=<?= (int) $sessionId ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-[#dfe6de] bg-white px-4 py-2 text-sm font-semibold text-[#41684f]">Kembali ke Sesi</a><a href="absensi.php?session_id=<?= (int) $sessionId ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white">Lanjut ke Data Siswa</a></div>
<script>
const tapToken = <?= $csrfJson ?>;
const tapEndpoint = 'tap-sesi.php?session_id=<?= (int) $sessionId ?>';
const tapButton = document.getElementById('anonymous-tap');
const tapFeedback = document.getElementById('tap-feedback');
const tapInput = document.getElementById('tap-count-input');
let cooldownTimer = null;
function applyCooldown(seconds) {
    window.clearInterval(cooldownTimer);
    let remaining = Math.max(1, Math.ceil(seconds));
    tapButton.disabled = true;
    tapButton.querySelector('span').textContent = 'Tunggu ' + remaining + ' detik';
    cooldownTimer = window.setInterval(() => {
        remaining -= 1;
        if (remaining <= 0) {
            window.clearInterval(cooldownTimer);
            cooldownTimer = null;
            const count = Number(document.getElementById('tap-count').textContent);
            const roster = Number(document.getElementById('roster-count').textContent);
            tapButton.disabled = count >= roster;
            tapButton.querySelector('span').textContent = count >= roster ? 'Batas Tap Tercapai' : 'TAP KEHADIRAN';
            return;
        }
        tapButton.querySelector('span').textContent = 'Tunggu ' + remaining + ' detik';
    }, 1000);
}
tapButton.addEventListener('click', async () => {
    if (tapButton.disabled) return;
    tapButton.disabled = true;
    tapButton.querySelector('span').textContent = 'Memproses…';
    tapFeedback.textContent = '';
    const body = new FormData();
    body.set('_csrf_token', tapToken);
    body.set('action', 'tap');
    body.set('session_id', '<?= (int) $sessionId ?>');
    try {
        const response = await fetch(tapEndpoint, {method: 'POST', body, credentials: 'same-origin', headers: {'Accept': 'application/json'}});
        const result = await response.json();
        if (!response.ok) {
            if (response.status === 429) applyCooldown(result.retry_after || 2);
            throw new Error(result.error || 'Tap tidak dapat diproses.');
        }
        document.getElementById('tap-count').textContent = String(result.tap_count);
        tapInput.value = String(result.tap_count);
        tapFeedback.textContent = 'Tap diterima pada ' + result.time + '.';
        applyCooldown(2);
    } catch (error) {
        tapFeedback.textContent = error.message;
        if (cooldownTimer === null) {
            tapButton.disabled = false;
            tapButton.querySelector('span').textContent = 'TAP KEHADIRAN';
        }
    }
});
document.addEventListener('keydown', event => {
    if (event.code !== 'Space' || event.repeat || !(event.target instanceof Element) || event.target.closest('input, textarea, select, button, a, [contenteditable="true"]')) return;
    event.preventDefault();
    tapButton.click();
});
</script>
<?php guru_page_end(); ?>
