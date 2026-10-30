// UI-only behaviour. No business logic lives here.

// 1) Delete confirmation modal (replaces window.confirm).
//    Any <form data-confirm-delete> opens the dialog; the form still posts
//    with its own @csrf / @method('DELETE') / route() action once confirmed.
const dialog = document.getElementById('confirmDialog');
let pendingForm = null;

document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!dialog || !(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm-delete')) return;
    e.preventDefault();
    pendingForm = form;
    dialog.querySelector('[data-dialog-heading]').textContent = form.dataset.heading || 'Delete this item?';
    dialog.querySelector('[data-dialog-title]').textContent = form.dataset.title || '';
    dialog.querySelector('[data-dialog-detail]').textContent = form.dataset.detail || '';
    const confirmLabel = dialog.querySelector('[data-dialog-confirm-label]');
    if (confirmLabel) confirmLabel.textContent = form.dataset.confirmLabel || 'Yes, delete';
    dialog.showModal();
});

dialog?.querySelector('[data-dialog-confirm]')?.addEventListener('click', () => {
    const form = pendingForm;
    pendingForm = null;
    dialog.close();
    form?.submit(); // programmatic submit: skips the submit event, so no loop
});
dialog?.addEventListener('close', () => { pendingForm = null; });

// 2) Left side menu (off-canvas on mobile, always visible on lg+)
const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebarBackdrop');
function setSidebar(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('-translate-x-full', !open);
    backdrop?.classList.toggle('hidden', !open);
    document.querySelectorAll('[data-sidebar-toggle][aria-expanded]')
        .forEach((b) => b.setAttribute('aria-expanded', String(open)));
}
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setSidebar(false); });

// Desktop: collapse to an icon rail / expand again (remembered in localStorage).
function setCollapsed(collapsed) {
    document.body.dataset.collapsed = String(collapsed);
    try { localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0'); } catch (e) {}
}

// 3) Click helpers
document.addEventListener('click', (e) => {
    const dismiss = e.target.closest('[data-dismiss]');
    if (dismiss) dismiss.closest('[data-flash]')?.remove();

    if (e.target.closest('[data-sidebar-collapse]')) {
        setCollapsed(document.body.dataset.collapsed !== 'true');
    }

    // Sidebar submenu (Room Booking > floors): chevron toggles a smooth grid-rows animation
    const sub = e.target.closest('[data-submenu-toggle]');
    if (sub) {
        const panel = document.getElementById(sub.getAttribute('aria-controls'));
        const open = sub.getAttribute('aria-expanded') !== 'true';
        sub.setAttribute('aria-expanded', String(open));
        panel?.classList.toggle('grid-rows-[1fr]', open);
        panel?.classList.toggle('grid-rows-[0fr]', !open);
        panel?.toggleAttribute('inert', !open); // closed links must not be tabbable
        sub.querySelector('[data-chevron]')?.classList.toggle('rotate-180', open);
    }

    if (e.target.closest('[data-sidebar-toggle]')) {
        setSidebar(sidebar?.classList.contains('-translate-x-full'));
    }

    const pw = e.target.closest('[data-toggle-password]');
    if (pw) {
        const input = document.getElementById(pw.dataset.togglePassword);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        pw.setAttribute('aria-pressed', String(show));
        pw.querySelector('[data-eye]')?.classList.toggle('hidden', show);
        pw.querySelector('[data-eye-off]')?.classList.toggle('hidden', !show);
    }
});

// 4) Live preview: <span data-bind="field_name"> mirrors the form field with that name.
//    Optional: data-bind-data="price" (read option[data-price]), data-format="money", data-empty="…".
document.querySelectorAll('[data-preview-root]').forEach((root) => {
    root.querySelectorAll('[data-bind]').forEach((el) => {
        const field = root.querySelector(`[name="${el.dataset.bind}"]`);
        if (!field) return;
        const update = () => {
            let v = field.value;
            if (field.tagName === 'SELECT') {
                const opt = field.selectedOptions[0];
                v = !field.value ? '' : el.dataset.bindData ? (opt?.dataset[el.dataset.bindData] ?? '') : opt.text.trim();
            }
            if (el.dataset.format === 'money' && v !== '' && !isNaN(v)) {
                v = '$' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            el.textContent = v || el.dataset.empty || '—';
        };
        field.addEventListener('input', update);
        field.addEventListener('change', update);
        update();
    });
});


// 5) Room Booking page ------------------------------------------------------
//    Pure UI helpers. Prices, overlap checks and permissions are all re-checked on the server.
const money = (n) => '$' + Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const isoDate = (d) => { const z = new Date(d.getTime() - d.getTimezoneOffset() * 60000); return z.toISOString().slice(0, 10); };
const addDays = (iso, n) => { const [y, m, d] = iso.split('-').map(Number); return isoDate(new Date(y, m - 1, d + n)); };
const nightsBetween = (a, b) => Math.round((new Date(b + 'T00:00') - new Date(a + 'T00:00')) / 86400000);

// 5a) Booking form: min dates, capacity hint, live "nights x rate = total"
document.querySelectorAll('[data-booking-form]').forEach((form) => {
    const inEl = form.querySelector('[data-bk-in]');
    const outEl = form.querySelector('[data-bk-out]');
    const guestsEl = form.querySelector('[data-bk-guests]');
    const summary = form.querySelector('[data-bk-summary]');
    const total = form.querySelector('[data-bk-total]');
    const hint = form.querySelector('[data-bk-capacity-hint]');
    const isNew = !!form.closest('dialog'); // new bookings cannot start in the past; edits may

    function refresh() {
        const price = Number(form.dataset.price) || 0;
        const capacity = Number(form.dataset.capacity) || 0;

        if (isNew) inEl.min = isoDate(new Date());
        if (inEl.value) {
            outEl.min = addDays(inEl.value, 1);
            if (outEl.value && outEl.value <= inEl.value) outEl.value = addDays(inEl.value, 1);
        }

        guestsEl.max = capacity || 20;
        if (hint) hint.textContent = capacity ? `This room sleeps up to ${capacity} ${capacity === 1 ? 'guest' : 'guests'}.` : '';

        const nights = inEl.value && outEl.value ? nightsBetween(inEl.value, outEl.value) : 0;
        if (nights > 0) {
            summary.textContent = `${nights} ${nights === 1 ? 'night' : 'nights'} × ${money(price)}`;
            total.textContent = money(nights * price);
        } else {
            summary.textContent = 'Choose your dates';
            total.textContent = money(0);
        }
    }

    [inEl, outEl, guestsEl].forEach((el) => el.addEventListener('input', refresh));
    form._refreshBooking = refresh;
    refresh();
});

// 5b) Booking modal: fill from the clicked room card
const bookingDialog = document.getElementById('bookingDialog');
if (bookingDialog) {
    const bForm = bookingDialog.querySelector('form');
    const setText = (sel, v) => { const el = bookingDialog.querySelector(sel); if (el) el.textContent = v; };

    document.addEventListener('click', (e) => {
        const card = e.target.closest('[data-book-room]');
        if (card) {
            const d = card.dataset;
            bForm.dataset.price = d.roomPrice;
            bForm.dataset.capacity = d.roomCapacity;
            bForm.querySelector('[data-bk-room-id]').value = d.roomId;
            setText('[data-bk-room-number]', d.roomNumber);
            setText('[data-bk-room-type]', d.roomType);
            setText('[data-bk-room-floor]', d.roomFloor);
            setText('[data-bk-room-price]', Number(d.roomPrice).toFixed(2));

            // fresh form for every room; drop stale server-side error messages
            bForm.querySelectorAll('input[name="guest_name"], input[name="phone"], input[name="id_number"], textarea').forEach((el) => { el.value = ''; });
            bForm.querySelector('[data-bk-guests]').value = 1;
            const start = bForm.dataset.defaultDate || isoDate(new Date());
            bForm.querySelector('[data-bk-in]').value = start;
            bForm.querySelector('[data-bk-out]').value = addDays(start, 1);
            bForm.querySelectorAll('[role="alert"]').forEach((el) => el.remove());
            bForm.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));

            bForm._refreshBooking();
            bookingDialog.showModal();
            bForm.querySelector('input[name="guest_name"]').focus();
            return;
        }
        if (e.target.closest('[data-bk-close]') || e.target === bookingDialog) bookingDialog.close();
    });

    // Server rejected the booking: re-open the modal with the user's input and the errors.
    if (bookingDialog.hasAttribute('data-open')) {
        bForm._refreshBooking();
        bookingDialog.showModal();
    }
}

// 5c) Loading state: disable the submit button once the form is really submitting
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || e.defaultPrevented) return;
    if (!form.matches('[data-booking-form], [data-loading-form]')) return;
    const btn = form.querySelector('[data-submit]');
    if (!btn) return;
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    const label = btn.querySelector('[data-submit-text]');
    if (label) { btn.dataset.originalLabel = label.textContent; label.textContent = form.dataset.submitLabel || 'Working…'; }
});
// Back button (bfcache) must not leave a button stuck in the loading state
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-submit][aria-busy]').forEach((btn) => {
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
        const label = btn.querySelector('[data-submit-text]');
        if (label && btn.dataset.originalLabel) label.textContent = btn.dataset.originalLabel;
    });
});

// 5d) Room filters: selects submit on change, the search box after a short pause
const filterForm = document.querySelector('[data-filter-form]');
if (filterForm) {
    const grid = document.getElementById('roomGrid');
    const textInput = filterForm.querySelector('[data-filter-text]');
    let t;

    const go = (fromText) => {
        grid?.classList.add('opacity-50', 'pointer-events-none');
        grid?.setAttribute('aria-busy', 'true');
        try { if (fromText) sessionStorage.setItem('bkFocusSearch', '1'); } catch (e) {}
        filterForm.requestSubmit();
    };

    filterForm.querySelectorAll('[data-filter-auto]').forEach((el) => el.addEventListener('change', () => go(false)));
    textInput?.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => go(true), 450); });

    // keep typing after the page reloads with the filtered result
    try {
        if (sessionStorage.getItem('bkFocusSearch') && textInput) {
            sessionStorage.removeItem('bkFocusSearch');
            textInput.focus();
            textInput.setSelectionRange(textInput.value.length, textInput.value.length);
        }
    } catch (e) {}
}
