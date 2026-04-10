// assets/js/staff_transactions.js
'use strict';

(function () {
    const BASE = (typeof APP_URL !== 'undefined' ? APP_URL : '');

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        if (!t) return;
        t.textContent = msg;
        t.className   = `toast toast--visible toast--${type}`;
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.className = 'toast'; }, 4000);
    }

    function postJSON(url, data) {
        return fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    new URLSearchParams(data).toString()
        }).then(r => r.json());
    }

    // ── Process Return modal ─────────────────────────────────────
    const prModal   = document.getElementById('processReturnModal');
    const confirmPR = document.getElementById('confirmPR');
    let   pendingId = null;

    document.getElementById('txnTable')?.addEventListener('click', function (e) {
        const btn = e.target.closest('.process-return-btn');
        if (!btn) return;

        pendingId = btn.dataset.id;
        document.getElementById('prItemName').textContent    = btn.dataset.item;
        document.getElementById('prCustomerName').textContent = btn.dataset.customer;
        document.getElementById('prNotes').value             = '';

        const pen    = parseFloat(btn.dataset.estPenalty || '0');
        const notice = document.getElementById('prPenaltyNotice');
        const penAmt = document.getElementById('prPenaltyAmt');
        if (pen > 0) {
            penAmt.textContent = '₱ ' + pen.toFixed(2);
            notice.hidden = false;
        } else {
            notice.hidden = true;
        }
        prModal.hidden = false;
    });

    ['closePRModal','cancelPR'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', () => {
            prModal.hidden = true;
        });
    });
    prModal?.addEventListener('click', e => {
        if (e.target === prModal || e.target.classList.contains('modal__backdrop'))
            prModal.hidden = true;
    });

    confirmPR?.addEventListener('click', function () {
        this.disabled = true; this.textContent = '…';
        const notes = document.getElementById('prNotes').value.trim();

        postJSON(BASE + '/api/transactions/process_return.php', { id: pendingId, notes })
        .then(data => {
            this.disabled = false; this.textContent = 'Mark Returned';
            prModal.hidden = true;
            if (data.success) {
                document.getElementById('txn-row-' + pendingId)?.remove();
                showToast('Item marked as returned.', 'success');
            } else {
                showToast(data.message || 'Could not process return.', 'error');
            }
        })
        .catch(() => {
            this.disabled = false; this.textContent = 'Mark Returned';
            showToast('Network error.', 'error');
        });
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && prModal) prModal.hidden = true;
    });

})();