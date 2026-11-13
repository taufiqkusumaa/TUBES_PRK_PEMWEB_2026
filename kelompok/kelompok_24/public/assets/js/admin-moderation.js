document.querySelectorAll('[data-dialog-open]').forEach((button) => {
  button.addEventListener('click', () => {
    const dialog = document.getElementById(button.dataset.dialogOpen);
    if (dialog instanceof HTMLDialogElement) {
      dialog.showModal();
    }
  });
});

document.querySelectorAll('.moderation-dialog').forEach((dialog) => {
  dialog.querySelectorAll('[data-dialog-close]').forEach((button) => {
    button.addEventListener('click', () => dialog.close());
  });

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      dialog.close();
    }
  });

  if (dialog.hasAttribute('data-reopen-dialog')) {
    dialog.showModal();
  }
});

document.querySelectorAll('.moderation-decision-form').forEach((form) => {
  form.addEventListener('submit', (event) => {
    const decision = form.querySelector('input[name="decision"]:checked');
    const label = decision?.value === 'approved' ? 'menyetujui' : 'menolak';
    if (!window.confirm(`Konfirmasi keputusan: ${label} laporan ${form.dataset.reportCase}?`)) {
      event.preventDefault();
    }
  });
});

document.querySelectorAll('.announcement-delete-form').forEach((form) => {
  form.addEventListener('submit', (event) => {
    if (!window.confirm('Hapus pengumuman ini? Tindakan ini tidak dapat dibatalkan.')) {
      event.preventDefault();
    }
  });
});