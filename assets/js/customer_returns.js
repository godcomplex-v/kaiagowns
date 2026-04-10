
'use strict';

(function () {
    const BASE  = (typeof APP_URL !== 'undefined' ? APP_URL : '');
    const modal = document.getElementById('noticeModal');
    let   pendingId = null;

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        if (!t) return;
        t.textContent = msg;
        t.className   = `toast toast--visible toast--${type}`;
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.className = 'toast'; }, 3500);
    }

    document.querySelectorAll('.submit-notice-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            pendingId = this.dataset.id;
            document.getElementById('noticeItemName').textContent = this.dataset.item;
            modal.hidden = false;
        });
    });

    ['closeNoticeModal','cancelNotice'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', () => { modal.hidden = true; });
    });
    modal?.addEventListener('click', e => {
        if (e.target === modal || e.target.classList.contains('modal__backdrop'))
            modal.hidden = true;
    });

    document.getElementById('confirmNotice')?.addEventListener('click', function () {
        this.disabled = true; this.textContent = '…';
        fetch(BASE + '/api/customer/submit_return_notice.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    `transaction_id=${encodeURIComponent(pendingId)}`
        })
        .then(r => r.json())
        .then(data => {
            this.disabled = false; this.textContent = 'Send Notice';
            modal.hidden = true;
            if (data.success) {
                // Replace button with "Notice Sent" badge
                const btn = document.querySelector(
                    `.submit-notice-btn[data-id="${pendingId}"]`
                );
                if (btn) {
                    const badge = document.createElement('span');
                    badge.className   = 'badge badge--pending';
                    badge.textContent = 'Notice: Pending Pickup';
                    btn.replaceWith(badge);
                }
                showToast('Return notice sent to staff.', 'success');
            } else {
                showToast(data.message || 'Could not send notice.', 'error');
            }
        })
        .catch(() => {
            this.disabled = false; this.textContent = 'Send Notice';
            showToast('Network error.', 'error');
        });
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && modal) modal.hidden = true;
    });
})();