{{--
    O tema laravel-usp-theme carrega Bootstrap 4.6, mas as views usam classes
    e atributos do Bootstrap 5. Esta camada implementa o subconjunto usado.
--}}
<style>
    /* Espaçamento entre itens flex/grid */
    .gap-1 { gap: .25rem !important; } .gap-2 { gap: .5rem !important; } .gap-3 { gap: 1rem !important; }
    .gap-4 { gap: 1.5rem !important; } .gap-5 { gap: 3rem !important; }

    /* Margens/paddings lógicos (me/ms/pe/ps) */
    .me-0 { margin-right: 0 !important; } .me-1 { margin-right: .25rem !important; } .me-2 { margin-right: .5rem !important; } .me-3 { margin-right: 1rem !important; } .me-4 { margin-right: 1.5rem !important; }
    .ms-0 { margin-left: 0 !important; } .ms-1 { margin-left: .25rem !important; } .ms-2 { margin-left: .5rem !important; } .ms-3 { margin-left: 1rem !important; } .ms-auto { margin-left: auto !important; }
    .pe-1 { padding-right: .25rem !important; } .pe-2 { padding-right: .5rem !important; } .ps-1 { padding-left: .25rem !important; } .ps-2 { padding-left: .5rem !important; }

    /* Gutters de grid (g-*, gy-*) */
    .row.g-2 { margin: -.25rem; } .row.g-2 > * { padding: .25rem; }
    .row.g-3 { margin: -.5rem; } .row.g-3 > * { padding: .5rem; }
    .row.g-4 { margin: -.75rem; } .row.g-4 > * { padding: .75rem; }
    .row.gy-2 > * { padding-top: .25rem; padding-bottom: .25rem; }
    @media (min-width: 1400px) { .col-xxl-4 { flex: 0 0 33.333333%; max-width: 33.333333%; } }

    /* Tipografia */
    .fw-bold { font-weight: 700 !important; } .fw-semibold { font-weight: 600 !important; } .fw-normal { font-weight: 400 !important; }
    .fst-italic { font-style: italic !important; }
    .fs-4 { font-size: 1.5rem !important; } .fs-5 { font-size: 1.25rem !important; } .fs-6 { font-size: 1rem !important; }
    .text-end { text-align: right !important; } .text-start { text-align: left !important; }
    .text-break { word-wrap: break-word !important; word-break: break-word !important; }
    .min-w-0 { min-width: 0; }
    .opacity-50 { opacity: .5 !important; }
    .rounded-3 { border-radius: .5rem !important; } .rounded-pill { border-radius: 50rem !important; }

    /* Cores "subtle" */
    .bg-primary-subtle { background-color: #cfe2ff !important; }
    .bg-secondary-subtle { background-color: #e2e3e5 !important; }
    .bg-success-subtle { background-color: #d1e7dd !important; }
    .bg-danger-subtle { background-color: #f8d7da !important; }
    .bg-warning-subtle { background-color: #fff3cd !important; }
    .bg-info-subtle { background-color: #cff4fc !important; }
    .bg-dark-subtle { background-color: #ced4da !important; }
    .border-primary-subtle { border-color: #9ec5fe !important; }
    .border-success-subtle { border-color: #a3cfbb !important; }
    .border-info-subtle { border-color: #9eeaf9 !important; }
    .text-warning-emphasis { color: #664d03 !important; }
    .badge.bg-primary-subtle, .badge.bg-secondary-subtle, .badge.bg-success-subtle,
    .badge.bg-danger-subtle, .badge.bg-warning-subtle, .badge.bg-info-subtle, .badge.bg-dark-subtle { font-weight: 600; }
    .badge.bg-primary, .badge.bg-success, .badge.bg-danger, .badge.bg-secondary, .badge.bg-info, .badge.bg-dark { color: #fff; }
    .badge.bg-warning, .badge.bg-light { color: #212529; }

    /* Abas com <button> */
    button.nav-link { background: none; border: 1px solid transparent; }
    .nav-tabs button.nav-link.active { background: #fff; border-color: #dee2e6 #dee2e6 #fff; }

    /* Formulários */
    .form-label { display: inline-block; margin-bottom: .5rem; }
    .form-select { display: block; width: 100%; padding: .375rem 2rem .375rem .75rem; font-size: 1rem; line-height: 1.5; color: #495057; background: #fff url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") no-repeat right .75rem center / 16px 12px; border: 1px solid #ced4da; border-radius: .25rem; appearance: none; }
    .form-select-sm { padding: .25rem 2rem .25rem .5rem; font-size: .875rem; }
    .form-select-lg { padding: .5rem 2rem .5rem 1rem; font-size: 1.25rem; }
    .form-check-input { width: 1em; height: 1em; position: static; margin: 0; vertical-align: middle; }
    .btn-close { box-sizing: content-box; width: 1em; height: 1em; padding: .25em; border: 0; border-radius: .25rem; opacity: .5; background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center / 1em auto no-repeat; }
    .btn-close:hover { opacity: .75; }
    .btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); }
    .alert-dismissible .btn-close { position: absolute; top: 0; right: 0; padding: 1rem; box-sizing: content-box; }
</style>

<script>
    // Traduz atributos data-bs-* (Bootstrap 5) para data-* (Bootstrap 4),
    // inclusive em elementos inseridos dinamicamente.
    (function () {
        const map = { 'data-bs-toggle': 'data-toggle', 'data-bs-target': 'data-target', 'data-bs-dismiss': 'data-dismiss' };
        function translate(root) {
            Object.keys(map).forEach(function (attr) {
                if (root.nodeType === 1 && root.hasAttribute && root.hasAttribute(attr) && !root.hasAttribute(map[attr])) {
                    root.setAttribute(map[attr], root.getAttribute(attr));
                }
                (root.querySelectorAll ? root.querySelectorAll('[' + attr + ']') : []).forEach(function (el) {
                    if (!el.hasAttribute(map[attr])) el.setAttribute(map[attr], el.getAttribute(attr));
                });
            });
        }
        document.addEventListener('DOMContentLoaded', function () {
            translate(document);
            new MutationObserver(function (mutations) {
                mutations.forEach(function (m) { m.addedNodes.forEach(translate); });
            }).observe(document.body, { childList: true, subtree: true });
        });
    })();
</script>
