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