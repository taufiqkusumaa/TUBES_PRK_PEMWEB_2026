<?php
// app/Views/layouts/footer.php
// Penutup halaman, pesan global, dialog konfirmasi, dan perilaku UI bersama.
?>
</main>

<footer class="page-footer">
    <p>KebunKu: catatan harian budidaya hortikultura.</p>
    <p><a href="#konten">Kembali ke atas</a></p>
</footer>

<p id="pesanGlobal" class="global-message" role="status" aria-live="polite" hidden>
    <span id="pesanGlobalTeks"></span>
    <button type="button" class="close-global-message" aria-label="Tutup pesan">&times;</button>
</p>

<dialog id="dialogKonfirmasi" class="dialog-konfirmasi" aria-labelledby="konfirmasiJudul">
    <div class="modal-box">
        <h2 id="konfirmasiJudul">Konfirmasi tindakan</h2>
        <p id="konfirmasiPesan"></p>
        <div class="modal-footer">
            <button type="button" class="btn btn--secondary" data-konfirmasi-batal>Batal</button>
            <button type="button" class="btn btn--danger" data-konfirmasi-ya>Ya, lanjutkan</button>
        </div>
    </div>
</dialog>

<script src="/assets/js/app.js"></script>
</body>
</html>


