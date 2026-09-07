/* =====================================================================
   Painel Administrativo - JavaScript
   Interações leves e sem dependências: sidebar mobile, menu do usuário,
   auto-dismiss de alertas, modais de confirmação e geração de slug.
   ===================================================================== */
(function () {
    'use strict';

    /* ---- Toast global (feedback de ações AJAX) ---- */
    window.showToast = function (message, type) {
        type = type || 'info';
        var stack = document.querySelector('.flash-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'flash-stack';
            document.body.appendChild(stack);
        }
        var el = document.createElement('div');
        el.className = 'alert alert-' + (type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info'));
        el.setAttribute('role', 'status');
        el.innerHTML = '<span></span><button class="alert-close" aria-label="Fechar">\u00d7</button>';
        el.querySelector('span').textContent = message;
        el.querySelector('.alert-close').addEventListener('click', function () { el.remove(); });
        stack.appendChild(el);
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 4000);
    };

    /* ---- Sidebar mobile ---- */
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('menuToggle');

    function closeSidebar() {
        sidebar && sidebar.classList.remove('open');
        overlay && overlay.classList.remove('open');
    }
    if (toggle) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        });
    }
    if (overlay) overlay.addEventListener('click', closeSidebar);

    /* ---- Menu do usuário ---- */
    const userBtn = document.getElementById('userBtn');
    const userMenu = document.getElementById('userMenu');
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            userMenu.classList.toggle('open');
        });
        document.addEventListener('click', function () { userMenu.classList.remove('open'); });
    }

    /* ---- Auto-dismiss de flash messages ---- */
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 5000);
    });
    document.querySelectorAll('.alert-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const el = btn.closest('.alert');
            if (el) el.remove();
        });
    });

    /* ---- Modais de confirmação (ex.: exclusão) ---- */
    // Uso: <button data-confirm="Mensagem" data-form="idDoForm">Excluir</button>
    const backdrop = document.getElementById('confirmModal');
    const confirmText = document.getElementById('confirmText');
    const confirmOk = document.getElementById('confirmOk');
    const confirmCancel = document.getElementById('confirmCancel');
    let pendingForm = null;

    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!backdrop) {
                // fallback nativo se o modal não existir na página
                if (confirm(btn.getAttribute('data-confirm'))) {
                    submitTarget(btn);
                }
                return;
            }
            confirmText.textContent = btn.getAttribute('data-confirm');
            pendingForm = resolveForm(btn);
            backdrop.classList.add('open');
        });
    });

    function resolveForm(btn) {
        const formId = btn.getAttribute('data-form');
        if (formId) return document.getElementById(formId);
        return btn.closest('form');
    }
    function submitTarget(btn) {
        const f = resolveForm(btn);
        if (f) f.submit();
    }
    if (confirmOk) confirmOk.addEventListener('click', function () {
        if (pendingForm) pendingForm.submit();
    });
    if (confirmCancel) confirmCancel.addEventListener('click', function () {
        backdrop.classList.remove('open');
        pendingForm = null;
    });
    if (backdrop) backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) { backdrop.classList.remove('open'); pendingForm = null; }
    });

    /* ---- Geração automática de slug ---- */
    // Uso: input[data-slug-source] e input[data-slug-target]
    function slugify(text) {
        return text.toString().toLowerCase().trim()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
    const slugSource = document.querySelector('[data-slug-source]');
    const slugTarget = document.querySelector('[data-slug-target]');
    if (slugSource && slugTarget) {
        let touched = slugTarget.value.trim() !== '';
        slugTarget.addEventListener('input', function () { touched = true; });
        slugSource.addEventListener('input', function () {
            if (!touched) slugTarget.value = slugify(slugSource.value);
        });
    }

    /* ---- Preview de upload de imagem ---- */
    document.querySelectorAll('[data-image-input]').forEach(function (input) {
        input.addEventListener('change', function () {
            const targetId = input.getAttribute('data-preview');
            const preview = targetId ? document.getElementById(targetId) : null;
            if (preview && input.files && input.files[0]) {
                preview.src = URL.createObjectURL(input.files[0]);
                preview.style.display = 'block';
            }
        });
    });

    /* ---- Estado de carregamento no envio de formulários ---- */
    // Marca o botão de submit com data-loading e evita envio duplicado.
    // Ignora formulários com [data-no-loading].
    document.querySelectorAll('form').forEach(function (form) {
        if (form.hasAttribute('data-no-loading')) return;
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (!btn || btn.getAttribute('data-loading') === 'true') return;
            // Aguarda o próximo tick para não bloquear o envio nativo.
            setTimeout(function () { btn.setAttribute('data-loading', 'true'); }, 0);
        });
    });
})();
