<section class="space-y-4">
    <?php if ($flash !== null): ?>
        <?php $flashType = ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success'; ?>
        <p role="status" class="rounded-xl px-4 py-3 text-sm <?= $flashType === 'error' ? 'bg-red-50 text-red-700' : 'bg-[#e8f0e9] text-[#3f6548]' ?>">
            <?= h((string) ($flash['message'] ?? '')) ?>
        </p>
    <?php endif; ?>

    <div><?php admin_ui_input('Cari siswa', 'cari_siswa', '', 'text', 'Cari nama atau nomor induk'); ?></div>

    <div class="overflow-x-auto rounded-2xl border border-[#dfe6de] bg-white">
        <table data-admin-table class="min-w-full divide-y divide-[#e6ebe5] text-left text-sm">
            <thead class="bg-[#eff3ee] text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">No</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nomor Induk</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Nama</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Jenis Kelamin</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Tanggal Lahir</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Kelas Saat Ini</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Status</th>
                    <th scope="col" class="whitespace-nowrap px-4 py-3.5 font-semibold sm:px-5">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e6ebe5] text-slate-700">
                <?php if ($siswaList === []): ?>
                    <tr><td colspan="8" class="px-4 py-9 text-center text-slate-500">Belum ada data siswa.</td></tr>
                <?php else: ?>
                    <?php foreach ($siswaList as $index => $siswa): ?>
                        <tr class="hover:bg-[#fafbf9]">
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) ($index + 1)) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) $siswa['nomor_induk']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) $siswa['nama']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) ($siswa['jenis_kelamin'] ?? '-')) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5"><?= h((string) ($siswa['tanggal_lahir'] ?? '-')) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 sm:px-5"><?= h((string) ($siswa['kelas_nama'] ?? '-')) ?></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5">
                                <?php if ((bool) $siswa['is_active']): ?>
                                    <span class="inline-flex rounded-full bg-[#e8f0e9] px-2.5 py-1 text-xs font-medium text-[#3f6548]">Aktif</span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center sm:px-5">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="siswa.php?detail=<?= h((string) $siswa['id']) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#4e7a5d] hover:bg-[#e8f0e9]">Detail</a>
                                    <a href="siswa.php?edit=<?= h((string) $siswa['id']) ?>" class="rounded-md px-2 py-1 text-xs font-semibold text-[#4e7a5d] hover:bg-[#e8f0e9]">Edit</a>
                                    <form method="post" action="siswa.php" class="inline">
                                        <input type="hidden" name="_csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="siswa_id" value="<?= h((string) $siswa['id']) ?>">
                                        <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold <?= (bool) $siswa['is_active'] ? 'text-red-700 hover:bg-red-50' : 'text-[#4e7a5d] hover:bg-[#e8f0e9]' ?>">
                                            <?= (bool) $siswa['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
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
    <p id="siswa-search-empty" class="hidden rounded-xl bg-white px-4 py-6 text-center text-sm text-slate-500">Tidak ada siswa yang cocok dengan pencarian.</p>
</section>

<script>
document.getElementById('cari_siswa').addEventListener('input', event => {
    const query = event.target.value.trim().toLocaleLowerCase('id');
    const rows = [...document.querySelectorAll('[data-admin-table] tbody tr')];
    let visible = 0;
    rows.forEach(row => {
        const matches = row.textContent.toLocaleLowerCase('id').includes(query);
        row.hidden = !matches;
        if (matches) visible++;
    });
    document.getElementById('siswa-search-empty').classList.toggle('hidden', visible > 0);
});
</script>
