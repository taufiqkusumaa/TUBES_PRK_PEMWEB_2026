<section class="space-y-4">
    <?php if ($flash !== null): ?>
        <?php $flashType = ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success'; ?>
        <p role="status" class="rounded-xl px-4 py-3 text-sm <?= $flashType === 'error' ? 'bg-red-50 text-red-700' : 'bg-[#eff3ee] text-[#414942]' ?>">
            <?= h((string) ($flash['message'] ?? '')) ?>
        </p>
    <?php endif; ?>

    <div class="overflow-x-auto rounded-2xl border border-[#dfe6de] bg-white">
        <table class="min-w-full divide-y divide-[#e6ebe5] text-left text-sm">
            <thead class="bg-[#eff3ee] text-xs uppercase tracking-wide text-[#64666a]">
                <tr>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">No</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Semester</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Kode</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nama</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Tingkat</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Jumlah Siswa</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Status</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e6ebe5] text-[#414942]">
                <?php if ($kelasList === []): ?>
                    <tr><td colspan="8" class="px-4 py-9 text-center text-slate-500">Belum ada data kelas.</td></tr>
                <?php else: ?>
                    <?php foreach ($kelasList as $index => $kelas): ?>
                        <tr class="hover:bg-[#fafbf9]">
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) ($index + 1)) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) ($kelas['semester_nama'] ?? '-')) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) $kelas['kode']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 font-medium text-[#303832] sm:px-5"><?= h((string) $kelas['nama']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) ($kelas['tingkat'] ?? '-')) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) $kelas['jumlah_siswa']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5">
                                <?php if ((bool) $kelas['is_active']): ?>
                                    <span class="inline-flex rounded-full bg-[#e8f0e9] px-2.5 py-1 text-xs font-medium text-[#3f6548]">Aktif</span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="kelas.php?detail=<?= h((string) $kelas['id']) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#4e7a5d] hover:bg-[#eaf1ea]">Detail</a>
                                    <a href="kelas.php?edit=<?= h((string) $kelas['id']) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#4e7a5d] hover:bg-[#eaf1ea]">Edit</a>
                                    <form method="post" action="kelas.php" class="inline">
                                        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="kelas_id" value="<?= h((string) $kelas['id']) ?>">
                                        <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold <?= (bool) $kelas['is_active'] ? 'text-red-700 hover:bg-red-50' : 'text-[#4e7a5d] hover:bg-[#eaf1ea]' ?>">
                                            <?= (bool) $kelas['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
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
