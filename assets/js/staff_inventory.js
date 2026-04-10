// assets/js/staff_inventory.js
'use strict';

(function () {
    const BASE = (typeof APP_URL !== 'undefined' ? APP_URL : '');

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        if (!t) return;
        t.textContent = msg;
        t.className   = `toast toast--visible toast--${type}`;
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.className = 'toast'; }, 3500);
    }

    // Status-to-badge-class map
    const badgeClass = {
        available: 'badge--available',
        reserved:  'badge--reserved',
        damaged:   'badge--damaged',
    };

    document.getElementById('invTable')?.addEventListener('change', function (e) {
        const sel = e.target.closest('.status-select');
        if (!sel || !sel.value) return;

        const itemId    = sel.dataset.id;
        const newStatus = sel.value;
        const badge     = document.getElementById('status-badge-' + itemId);

        sel.disabled = true;

        fetch(BASE + '/api/staff/update_item_status.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    new URLSearchParams({ item_id: itemId, status: newStatus }).toString()
        })
        .then(r => r.json())
        .then(data => {
            sel.disabled = false;
            sel.value    = '';   // reset to "Change…"

            if (data.success) {
                if (badge) {
                    // Remove all status classes and apply new one
                    badge.className = 'badge ' + (badgeClass[newStatus] || '');
                    badge.textContent = newStatus.charAt(0).toUpperCase()
                                      + newStatus.slice(1);
                }
                // Remove the just-selected option from dropdown
                const opt = sel.querySelector(`option[value="${newStatus}"]`);
                if (opt) opt.remove();

                // Add back the old status option
                const oldStatus = badge?.dataset.prevStatus || '';
                showToast(`Item status updated to ${newStatus}.`, 'success');
            } else {
                showToast(data.message || 'Could not update status.', 'error');
            }
        })
        .catch(() => {
            sel.disabled = false;
            showToast('Network error.', 'error');
        });
    });

})();