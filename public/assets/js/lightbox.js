/* =====================================================================
   Lightbox de galeria — sem dependências.
   Ativa em links [data-lightbox] dentro de [data-lightbox-gallery].
   Suporta navegação (prev/next), teclado (esc/setas) e legenda.
   ===================================================================== */
(function () {
    'use strict';

    var gallery = document.querySelector('[data-lightbox-gallery]');
    var box = document.getElementById('lightbox');
    if (!gallery || !box) return;

    var img = document.getElementById('lightboxImg');
    var caption = document.getElementById('lightboxCaption');
    var links = Array.prototype.slice.call(gallery.querySelectorAll('[data-lightbox]'));
    var current = 0;

    function show(i) {
        if (i < 0) i = links.length - 1;
        if (i >= links.length) i = 0;
        current = i;
        var link = links[i];
        img.src = link.getAttribute('data-lightbox');
        img.alt = link.getAttribute('data-caption') || '';
        caption.textContent = link.getAttribute('data-caption') || '';
        box.classList.add('open');
        box.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function close() {
        box.classList.remove('open');
        box.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    links.forEach(function (link, i) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            show(i);
        });
    });

    box.querySelector('.lightbox-close').addEventListener('click', close);
    box.querySelector('.lightbox-prev').addEventListener('click', function () { show(current - 1); });
    box.querySelector('.lightbox-next').addEventListener('click', function () { show(current + 1); });
    box.addEventListener('click', function (e) { if (e.target === box) close(); });

    document.addEventListener('keydown', function (e) {
        if (!box.classList.contains('open')) return;
        if (e.key === 'Escape') close();
        else if (e.key === 'ArrowLeft') show(current - 1);
        else if (e.key === 'ArrowRight') show(current + 1);
    });
})();
