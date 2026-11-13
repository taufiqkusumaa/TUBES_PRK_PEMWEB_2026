<section class="space-y-4">
    <form method="get" action="penugasan-guru.php" class="flex flex-wrap items-end gap-2">
        <div>
            <label for="filter-semester" class="mb-1.5 block text-sm font-medium text-slate-700">Semester</label>
            <select id="filter-semester" name="semester_id" class="min-h-10 rounded-lg border border-[#dfe6de] bg-white px-3 text-sm">
                <option value="0" <?= $filterSemesterId === 0 ? 'selected' : '' ?>>Semua semester</option>
                <?php foreach ($semesterList as $semester): ?>
                    <option value="<?= h((string) $semester['id']) ?>" <?= (int) $semester['id'] === $filterSemesterId ? 'selected' : '' ?>><?= h((string) $semester['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#4e7a5d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#41684f]">Tampilkan</button>
    </form>

    <?php if ($flash !== null): ?>
        <?php $flashType = ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success'; ?>
        <p role="status" class="rounded-xl px-4 py-3 text-sm <?= $flashType === 'error' ? 'bg-red-50 text-red-700' : 'bg-[#e8f0e9] text-[#3f6548]' ?>"><?= h((string) ($flash['message'] ?? '')) ?></p>
    <?php endif; ?>

    <div class="overflow-x-auto rounded-2xl border border-[#dfe6de] bg-white">
        <table data-admin-table class="min-w-full divide-y divide-[#e6ebe5] text-left text-sm">
            <thead class="bg-[#eff3ee] text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">No</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Guru</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Mata Pelajaran</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Kelas</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Semester</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Jadwal</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Dibuat</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e6ebe5] text-slate-700">
                <?php if ($assignmentList === []): ?>
                    <tr><td colspan="8" class="px-4 py-9 text-center text-slate-500">Belum ada penugasan guru.</td></tr>
                <?php else: ?>
                    <?php foreach ($assignmentList as $index => $assignment): ?>
                        <tr class="hover:bg-[#fafbf9]">
                            <td class="px-4 py-3.5 text-center sm:px-5"><?= h((string) ($index + 1)) ?></td>
                            <td class="px-4 py-3.5 sm:px-5"><span class="block font-medium text-slate-700"><?= h((string) $assignment['guru_nama']) ?></span><span class="block text-xs text-slate-500"><?= h((string) $assignment['guru_nomor']) ?></span></td>
                            <td class="px-4 py-3.5 sm:px-5"><span class="block font-medium text-slate-700"><?= h((string) $assignment['mapel_nama']) ?></span><span class="block text-xs text-slate-500"><?= h((string) $assignment['mapel_kode']) ?></span></td>
                            <td class="px-4 py-3.5 sm:px-5"><?= h((string) $assignment['kelas_nama']) ?></td>
                            <td class="px-4 py-3.5 sm:px-5"><?= h((string) $assignment['semester_nama']) ?></td>
                            <td class="px-4 py-3.5 text-center sm:px-5"><?= h((string) (int) $assignment['jumlah_jadwal']) ?></td>
                            <td class="px-4 py-3.5 text-center sm:px-5"><?= h((string) $assignment['created_at']) ?></td>
                            <td class="px-4 py-3.5 text-center sm:px-5">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="penugasan-guru.php?<?= $filterSemesterId > 0 ? 'semester_id=' . h((string) $filterSemesterId) . '&amp;' : '' ?>edit=<?= h((string) $assignment['id']) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#4e7a5d] hover:bg-[#e8f0e9]">Edit</a>
                                    <form method="post" action="penugasan-guru.php" class="inline" onsubmit="return confirm('Hapus penugasan ini?');">
                                        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= h((string) $assignment['id']) ?>">
                                        <input type="hidden" name="filter_semester_id" value="<?= h((string) $filterSemesterId) ?>">
                                        <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
