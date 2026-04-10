// assets/js/staff_create.js
'use strict';

(function () {
    const typeSelect   = document.getElementById('type');
    const rentFields   = document.getElementById('rentFields');
    const borrowInput  = document.getElementById('borrow_date');
    const dueInput     = document.getElementById('due_date');
    const itemSelect   = document.getElementById('item_id');
    const amountInput  = document.getElementById('amount_paid');
    const priceHint    = document.getElementById('suggestedPrice');
    const defaultDays  = (typeof DEFAULT_DAYS !== 'undefined') ? DEFAULT_DAYS : 7;

    // ── Show/hide rent fields based on type ──────────────────────
    function toggleRentFields() {
        const isRent = typeSelect.value === 'rent';
        rentFields.style.display = isRent ? '' : 'none';
        if (isRent && !dueInput.value && borrowInput.value) {
            const d = new Date(borrowInput.value);
            d.setDate(d.getDate() + defaultDays);
            dueInput.value = d.toISOString().split('T')[0];
        }
        updatePriceHint();
    }

    typeSelect?.addEventListener('change', toggleRentFields);
    toggleRentFields();

    // ── Auto-set due date when borrow date changes ───────────────
    borrowInput?.addEventListener('change', function () {
        if (!dueInput.value) {
            const d = new Date(this.value);
            d.setDate(d.getDate() + defaultDays);
            dueInput.value = d.toISOString().split('T')[0];
        }
    });

    // ── Suggest price from selected item ─────────────────────────
    function updatePriceHint() {
        const opt = itemSelect?.options[itemSelect.selectedIndex];
        if (!opt || !opt.value) { priceHint.textContent = ''; return; }

        const isRent  = typeSelect.value === 'rent';
        const price   = isRent ? opt.dataset.rental : opt.dataset.sale;
        const label   = isRent ? 'Rental price' : 'Sale price';

        if (price && parseFloat(price) > 0) {
            priceHint.textContent = `${label}: ₱${parseFloat(price).toFixed(2)}`;
            if (!amountInput.value || amountInput.value === '0.00') {
                amountInput.value = parseFloat(price).toFixed(2);
            }
        } else {
            priceHint.textContent = '';
        }
    }

    itemSelect?.addEventListener('change', updatePriceHint);
    typeSelect?.addEventListener('change', updatePriceHint);

    // ── Client-side validation ───────────────────────────────────
    document.getElementById('createForm')?.addEventListener('submit', function (e) {
        let valid = true;
        const clearErr = el => { if (el) el.textContent = ''; };
        const setErr   = (el, msg) => { if (el) el.textContent = msg; };

        const custErr   = document.querySelector('[name=customer_id]')
                            ?.closest('.form-group')?.querySelector('.field-error');
        const itemErr   = document.querySelector('[name=item_id]')
                            ?.closest('.form-group')?.querySelector('.field-error');
        const typeErr   = document.querySelector('[name=type]')
                            ?.closest('.form-group')?.querySelector('.field-error');
        const amtErr    = document.querySelector('[name=amount_paid]')
                            ?.closest('.form-group')?.querySelector('.field-error');

        [custErr,itemErr,typeErr,amtErr].forEach(clearErr);

        if (!document.getElementById('customer_id')?.value)
            { setErr(custErr,'Please select a customer.'); valid=false; }
        if (!document.getElementById('item_id')?.value)
            { setErr(itemErr,'Please select an item.'); valid=false; }
        if (!typeSelect?.value)
            { setErr(typeErr,'Please select a type.'); valid=false; }

        const amt = parseFloat(amountInput?.value);
        if (isNaN(amt) || amt < 0)
            { setErr(amtErr,'Enter a valid amount.'); valid=false; }

        if (typeSelect?.value === 'rent') {
            const bErr = borrowInput?.closest('.form-group')?.querySelector('.field-error');
            const dErr = dueInput?.closest('.form-group')?.querySelector('.field-error');
            clearErr(bErr); clearErr(dErr);
            if (!borrowInput?.value) { setErr(bErr,'Borrow date required.'); valid=false; }
            if (!dueInput?.value)    { setErr(dErr,'Due date required.');     valid=false; }
            if (borrowInput?.value && dueInput?.value && dueInput.value < borrowInput.value)
                { setErr(dErr,'Due date must be after borrow date.'); valid=false; }
        }

        if (!valid) e.preventDefault();
    });

})();