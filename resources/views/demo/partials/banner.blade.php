<section class="demo-banner" role="status" aria-label="Informasi mode uji">
    <div class="demo-banner-copy">
        <span class="demo-banner-icon" aria-hidden="true">◉</span>
        <div>
            <strong>Mode uji</strong>
            <p>Data contoh dan perubahan tidak disimpan ke database.</p>
        </div>
    </div>
    <button class="demo-exit-button" type="button" data-demo-exit-open>
        <span aria-hidden="true">↗</span> Keluar dari mode uji
    </button>
</section>

<dialog class="demo-exit-dialog" data-demo-exit-dialog aria-labelledby="demo-exit-title" aria-describedby="demo-exit-description">
    <div class="demo-dialog-mark" aria-hidden="true">↗</div>
    <h2 id="demo-exit-title">Akhiri sesi uji?</h2>
    <p id="demo-exit-description">Kamu akan keluar dari mode uji. Data dan perubahan sesi ini akan dihapus dan tidak bisa dipulihkan.</p>
    <div class="demo-dialog-actions">
        <button class="btn light" type="button" data-demo-exit-close>Kembali</button>
        <form method="post" action="{{ route('demo.exit') }}">
            @csrf
            <button class="demo-exit-confirm" type="submit">Ya, keluar</button>
        </form>
    </div>
</dialog>

<script>
    (() => {
        const dialog = document.querySelector('[data-demo-exit-dialog]');
        if (!dialog) return;
        document.querySelector('[data-demo-exit-open]')?.addEventListener('click', () => dialog.showModal());
        document.querySelector('[data-demo-exit-close]')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    })();
</script>