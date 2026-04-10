
'use strict';

(function () {
    // ── Availability calendar (2-month view) ─────────────────────
    const cal       = document.getElementById('availCalendar');
    const blocked   = (typeof BLOCKED_RANGES !== 'undefined') ? BLOCKED_RANGES : [];

    function isBlocked(dateStr) {
        return blocked.some(r => dateStr >= r.blocked_from && dateStr <= r.blocked_to);
    }

    function buildMonth(year, month) {
        const monthNames = ['January','February','March','April','May','June',
                            'July','August','September','October','November','December'];
        const today = new Date().toISOString().split('T')[0];
        const wrap  = document.createElement('div');
        wrap.className = 'cal-month';

        const title = document.createElement('div');
        title.className = 'cal-month-title';
        title.textContent = `${monthNames[month]} ${year}`;
        wrap.appendChild(title);

        const grid = document.createElement('div');
        grid.className = 'cal-grid';

        ['Su','Mo','Tu','We','Th','Fr','Sa'].forEach(d => {
            const h = document.createElement('span');
            h.className = 'cal-head'; h.textContent = d;
            grid.appendChild(h);
        });

        const first   = new Date(year, month, 1).getDay();
        const daysIn  = new Date(year, month + 1, 0).getDate();

        for (let i = 0; i < first; i++) {
            const blank = document.createElement('span');
            blank.className = 'cal-day cal-day--blank';
            grid.appendChild(blank);
        }
        for (let d = 1; d <= daysIn; d++) {
            const ds   = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const cell = document.createElement('span');
            cell.textContent = d;
            cell.className   = 'cal-day'
                + (ds === today        ? ' cal-day--today'   : '')
                + (isBlocked(ds)       ? ' cal-day--blocked' : ' cal-day--free');
            grid.appendChild(cell);
        }
        wrap.appendChild(grid);
        return wrap;
    }

    if (cal) {
        const now = new Date();
        cal.appendChild(buildMonth(now.getFullYear(), now.getMonth()));
        const next = new Date(now.getFullYear(), now.getMonth() + 1, 1);
        cal.appendChild(buildMonth(next.getFullYear(), next.getMonth()));
    }

    // ── Request form logic ───────────────────────────────────────
    const typeRadios  = document.querySelectorAll('[name=type]');
    const rentDates   = document.getElementById('rentDates');
    const amtInput    = document.getElementById('amount_paid');
    const amtHint     = document.getElementById('amtHint');
    const borrowInput = document.getElementById('borrow_date');
    const dueInput    = document.getElementById('due_date');
    const days        = (typeof DEFAULT_DAYS !== 'undefined') ? DEFAULT_DAYS : 7;
    const rentalP     = (typeof RENTAL_PRICE !== 'undefined') ? RENTAL_PRICE : 0;
    const saleP       = (typeof SALE_PRICE   !== 'undefined') ? SALE_PRICE   : 0;

    function getType() {
        for (const r of typeRadios) if (r.checked) return r.value;
        return 'rent';
    }

    function updateForm() {
        const t = getType();
        if (rentDates) rentDates.style.display = t === 'rent' ? '' : 'none';
        const price  = t === 'rent' ? rentalP : saleP;
        if (amtHint)  amtHint.textContent = price > 0
            ? `Suggested: ₱${price.toFixed(2)}` : '';
        if (amtInput && (!amtInput.value || amtInput.value === '0.00')) {
            amtInput.value = price.toFixed(2);
        }
    }

    typeRadios.forEach(r => r.addEventListener('change', updateForm));
    updateForm();

    // Auto-set due date
    borrowInput?.addEventListener('change', function () {
        if (!dueInput.value) {
            const d = new Date(this.value);
            d.setDate(d.getDate() + days);
            dueInput.value = d.toISOString().split('T')[0];
        }
    });

    // ── Form validation ──────────────────────────────────────────
    document.getElementById('requestForm')?.addEventListener('submit', function (e) {
        let valid = true;
        if (getType() === 'rent') {
            if (!borrowInput?.value) {
                alert('Please select a borrow date.'); valid = false;
            } else if (!dueInput?.value) {
                alert('Please select a return date.'); valid = false;
            } else if (dueInput.value < borrowInput.value) {
                alert('Return date must be after borrow date.'); valid = false;
            }
        }
        if (!valid) e.preventDefault();
    });
})();