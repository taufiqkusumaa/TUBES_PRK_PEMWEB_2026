<section class="space-y-4">
    <?php if ($flash !== null): ?>
        <?php $flashType = ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success'; ?>
        <p role="status" class="rounded-xl px-4 py-3 text-sm <?= $flashType === 'error' ? 'bg-red-50 text-red-700' : 'bg-[#e8f0e9] text-[#3f6548]' ?>">
            <?= h((string) ($flash['message'] ?? '')) ?>
        </p>
    <?php endif; ?>

    <div class="overflow-x-auto rounded-2xl border border-[#dfe6de] bg-white">
        <table class="min-w-full divide-y divide-[#e6ebe5] text-left text-sm">
            <thead class="bg-[#eff3ee] text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">No</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Kode</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nama</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Tanggal Mulai</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Tanggal Selesai</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Status</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e6ebe5] text-slate-700">
                <?php if ($semesterList === []): ?>
                    <tr><td colspan="7" class="px-4 py-9 text-center text-slate-500">Belum ada data semester.</td></tr>
                <?php else: ?>
                    <?php foreach ($semesterList as $index => $semester): ?>
                        <?php $status = (string) $semester['status']; ?>
                        <tr class="hover:bg-[#fafbf9]">
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) ($index + 1)) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) $semester['kode']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $semester['nama']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) $semester['tanggal_mulai']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) $semester['tanggal_selesai']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium <?= h(semester_status_badge_class($status)) ?>"><?= h($status) ?></span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="semester.php?edit=<?= h((string) $semester['id']) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#4e7a5d] hover:bg-[#e8f0e9]">Edit</a>
                                    <?php if ($status !== 'aktif'): ?>
                                        <form method="post" action="semester.php" class="inline">
                                            <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="semester_id" value="<?= h((string) $semester['id']) ?>">
                                            <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold text-[#4e7a5d] hover:bg-[#e8f0e9]">Jadikan Aktif</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold text-[#3f6548]">Aktif</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
