

import Alpine from 'alpinejs';

window.Alpine = Alpine;

const confirmedForms = new WeakSet();
let pendingForm = null;
let pendingSubmitter = null;
let pendingConfirmationStage = 0;

const confirmationDialog = document.getElementById('action-confirmation');
const confirmationEyebrow = document.getElementById('action-confirmation-eyebrow');
const confirmationTitle = document.getElementById('action-confirmation-title');
const confirmationMessage = document.getElementById('action-confirmation-message');
const confirmationItem = document.getElementById('action-confirmation-item');
const confirmationCancel = document.getElementById('action-confirmation-cancel');
const confirmationContinue = document.getElementById('action-confirmation-continue');

function showConfirmation(stage) {
    if (!pendingForm) {
        return;
    }

    const form = pendingForm;
    const method = (form.querySelector('input[name="_method"]')?.value || form.method || 'GET').toUpperCase();
    const isDelete = method === 'DELETE';
    const action = form.dataset.confirmAction || (isDelete ? 'menghapus' : ['PUT', 'PATCH'].includes(method) ? 'menyimpan perubahan' : 'menambahkan');
    const isCreate = method === 'POST' && /^(menambahkan|mengirim)/i.test(action);
    const isUpdate = ['PUT', 'PATCH'].includes(method) || /^(memperbarui|mengalihkan|memindahkan|menyimpan perubahan)/i.test(action);
    const item = form.dataset.confirmItem || form.getAttribute('action') || 'data ini';
    const title = form.dataset.confirmTitle || (isDelete ? 'Konfirmasi penghapusan' : isCreate ? 'Konfirmasi penambahan' : isUpdate ? 'Konfirmasi perubahan' : 'Konfirmasi tindakan');

    confirmationEyebrow.textContent = stage === 2 ? 'Verifikasi kedua' : isDelete ? 'Hapus data' : isCreate ? 'Tambah data' : isUpdate ? 'Simpan perubahan' : 'Konfirmasi tindakan';
    confirmationTitle.textContent = stage === 2 ? 'Konfirmasi terakhir' : title;
    confirmationMessage.textContent = stage === 2
        ? `Verifikasi kedua: tindakan ini akan langsung dijalankan setelah Anda mengonfirmasi.`
        : form.dataset.confirmMessage || (isDelete
            ? 'Apakah Anda yakin ingin menghapus data berikut?'
            : isUpdate
                ? 'Apakah Anda yakin ingin menyimpan perubahan pada data berikut?'
                : isCreate
                    ? 'Apakah Anda yakin ingin menambahkan data berikut?'
                    : `Apakah Anda yakin ingin ${action}?`);
    confirmationItem.textContent = item;
    confirmationContinue.textContent = stage === 2
        ? form.dataset.confirmContinue || (isDelete ? 'Ya, Hapus' : isCreate ? 'Ya, Tambahkan' : isUpdate ? 'Ya, Simpan' : 'Ya, Lanjutkan')
        : 'Lanjutkan';
}

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (confirmedForms.has(form)) {
        confirmedForms.delete(form);
        return;
    }

    const method = (form.querySelector('input[name="_method"]')?.value || form.method || 'GET').toUpperCase();

    if (form.hasAttribute('data-confirmation-exempt') || ['GET', 'DIALOG'].includes(method)) {
        return;
    }

    if (!(confirmationDialog instanceof HTMLDialogElement) || !confirmationContinue) {
        return;
    }

    event.preventDefault();

    pendingForm = form;
    pendingSubmitter = event.submitter instanceof HTMLElement ? event.submitter : null;
    pendingConfirmationStage = 1;
    showConfirmation(pendingConfirmationStage);
    confirmationDialog.showModal();
    confirmationCancel?.focus();
});

confirmationCancel?.addEventListener('click', () => {
    confirmationDialog.close();
    pendingForm = null;
    pendingSubmitter = null;
    pendingConfirmationStage = 0;
});

confirmationContinue?.addEventListener('click', () => {
    if (!pendingForm) {
        return;
    }

    if (pendingConfirmationStage === 1) {
        pendingConfirmationStage = 2;
        showConfirmation(pendingConfirmationStage);
        confirmationCancel?.focus();
        return;
    }

    const form = pendingForm;
    const submitter = pendingSubmitter;
    pendingForm = null;
    pendingSubmitter = null;
    pendingConfirmationStage = 0;
    confirmedForms.add(form);
    confirmationDialog.close();

    if (submitter && submitter.form === form) {
        form.requestSubmit(submitter);
    } else {
        form.requestSubmit();
    }
});

confirmationDialog?.addEventListener('close', () => {
    pendingForm = null;
    pendingSubmitter = null;
    pendingConfirmationStage = 0;
});

Alpine.start();
