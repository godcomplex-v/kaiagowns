// assets/js/staff_notifications.js
'use strict';

(function () {
    const BASE = (typeof APP_URL !== 'undefined' ? APP_URL : '');

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        if (!t) return;
        t.textContent = msg;
        t.className   = `toast toast--visible toast--${type}`;
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.className = 'toast'; }, 3000);
    }

    function postJSON(url, data) {
        return fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    new URLSearchParams(data).toString()
        }).then(r => r.json());
    }

    // ── Mark single notification read ────────────────────────────
    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id   = this.dataset.id;
            const item = document.getElementById('notif-' + id);

            postJSON(BASE + '/api/staff/mark_notification_read.php', { id })
            .then(data => {
                if (data.success) {
                    item?.classList.remove('notif-full-item--unread');
                    this.remove();
                    showToast('Marked as read.', 'success');
                }
            });
        });
    });

    // ── Mark all read ─────────────────────────────────────────────
    document.getElementById('markAllRead')?.addEventListener('click', function () {
        this.disabled = true;
        postJSON(BASE + '/api/staff/mark_notification_read.php', { all: '1' })
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notif-full-item--unread')
                    .forEach(el => el.classList.remove('notif-full-item--unread'));
                document.querySelectorAll('.mark-read-btn')
                    .forEach(el => el.remove());
                this.remove();
                showToast('All notifications marked as read.', 'success');
            }
        });
    });

})();