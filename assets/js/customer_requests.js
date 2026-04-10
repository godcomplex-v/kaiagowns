
'use strict';

(function () {
    const BASE = (typeof APP_URL !== 'undefined' ? APP_URL : '');
    const modal = document.getElementById('cancelModal');
    let   pendingId = null;

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        if (!t) return;
        t.textContent = msg;
        t.className   = `toast toast--visible toast--${type}`;
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.className = 'toast'; }, 3500);
    }

    document.getElementById('requestsTable')?.addEventListener('click', function (e) {
        const btn = e.target.closest('.cancel-btn');
        if (!btn) return;
        pendingId = btn.dataset.id;
        modal.hidden = false;
    });

    ['closeCancelModal','cancelCancelBtn'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', () => { modal.hidden = true; });
    });
    modal?.addEventListener('click', e => {
        if (e.target === modal || e.target.classList.contains('modal__backdrop'))
            modal.hidden = true;
    });

    document.getElementById('confirmCancelBtn')?.addEventListener('click', function () {
        this.disabled = true; this.textContent = '…';
        fetch(BASE + '/api/customer/cancel_request.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    `id=${encodeURIComponent(pendingId)}`
        })
        .then(r => r.json())
        .then(data => {
            this.disabled = false; this.textContent = 'Yes, Cancel';
            modal.hidden = true;
            if (data.success) {
                document.getElementById('req-row-' + pendingId)?.remove();
                showToast('Request cancelled.', 'success');
            } else {
                showToast(data.message || 'Could not cancel.', 'error');
            }
        })
        .catch(() => {
            this.disabled = false; this.textContent = 'Yes, Cancel';
            showToast('Network error.', 'error');
        });
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && modal) modal.hidden = true;
    });
})();