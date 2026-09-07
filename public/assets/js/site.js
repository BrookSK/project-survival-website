/* =====================================================================
   Site público - JavaScript
   Header sticky, menu mobile, FAQ acordeão, reveal on scroll.
   Sem dependências externas.
   ===================================================================== */
(function () {
    'use strict';

    // Header muda de estilo ao rolar
    var header = document.getElementById('siteHeader');
    function onScroll() {
        if (!header) return;
        if (window.scrollY > 20) header.classList.add('scrolled');
        else header.classList.remove('scrolled');
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // Menu mobile
    var toggle = document.getElementById('navToggle');
    var mobileNav = document.getElementById('mobileNav');
    if (toggle && mobileNav) {
        toggle.addEventListener('click', function () {
            var open = mobileNav.classList.toggle('open');
            toggle.textContent = open ? '\u2715' : '\u2630';
            document.body.style.overflow = open ? 'hidden' : '';
        });
        mobileNav.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () {
                mobileNav.classList.remove('open');
                toggle.textContent = '\u2630';
                document.body.style.overflow = '';
            });
        });
    }

    // FAQ acordeão
    document.querySelectorAll('.faq-question').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var item = btn.closest('.faq-item');
            var answer = item.querySelector('.faq-answer');
            var isOpen = item.classList.toggle('open');
            answer.style.maxHeight = isOpen ? (answer.scrollHeight + 'px') : '0';
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });

    // Reveal on scroll
    var reveals = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && reveals.length) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        reveals.forEach(function (el) { io.observe(el); });
    } else {
        reveals.forEach(function (el) { el.classList.add('visible'); });
    }

    // Menu de conta do jogador (dropdown no header)
    var accountMenu = document.getElementById('accountMenu');
    var accountBtn = document.getElementById('accountBtn');
    if (accountMenu && accountBtn) {
        accountBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = accountMenu.classList.toggle('open');
            accountBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function () {
            accountMenu.classList.remove('open');
            accountBtn.setAttribute('aria-expanded', 'false');
        });
    }

    // Estado de carregamento no envio de formulários (contato, etc.)
    document.querySelectorAll('form').forEach(function (form) {
        if (form.hasAttribute('data-no-loading')) return;
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (!btn || btn.getAttribute('data-loading') === 'true') return;
            setTimeout(function () { btn.setAttribute('data-loading', 'true'); }, 0);
        });
    });
})();
