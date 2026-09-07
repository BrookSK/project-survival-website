/* =====================================================================
   Ordenação drag & drop reutilizável (HTML5 Drag and Drop, sem libs).
   Uso: <ul class="sortable-list" data-reorder-url="/endpoint">
          <li class="sortable-item" data-id="1">... <span class="drag-handle">⠿</span></li>
        </ul>
   Ao soltar, envia POST {order: [ids...], _token} para data-reorder-url.
   O token CSRF é lido de <meta name="csrf-token">.
   ===================================================================== */
(function () {
    'use strict';

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function persist(list) {
        var url = list.getAttribute('data-reorder-url');
        if (!url) return;
        var ids = Array.prototype.map.call(list.querySelectorAll('.sortable-item'), function (li) {
            return li.getAttribute('data-id');
        });

        var body = new URLSearchParams();
        body.append('_token', csrfToken());
        ids.forEach(function (id) { body.append('order[]', id); });

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        }).then(function (r) {
            if (r.ok && window.showToast) window.showToast('Ordem atualizada', 'success');
        }).catch(function () {
            if (window.showToast) window.showToast('Falha ao salvar a ordem', 'error');
        });
    }

    function initList(list) {
        var dragEl = null;

        list.querySelectorAll('.sortable-item').forEach(function (item) {
            var handle = item.querySelector('.drag-handle') || item;
            item.setAttribute('draggable', 'false');
            handle.addEventListener('mousedown', function () { item.setAttribute('draggable', 'true'); });
            handle.addEventListener('mouseup', function () { item.setAttribute('draggable', 'false'); });

            item.addEventListener('dragstart', function (e) {
                dragEl = item;
                item.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            item.addEventListener('dragend', function () {
                item.classList.remove('dragging');
                item.setAttribute('draggable', 'false');
                list.querySelectorAll('.drag-over').forEach(function (el) { el.classList.remove('drag-over'); });
                persist(list);
            });
            item.addEventListener('dragover', function (e) {
                e.preventDefault();
                if (!dragEl || dragEl === item) return;
                var rect = item.getBoundingClientRect();
                var after = (e.clientY - rect.top) > rect.height / 2;
                item.classList.add('drag-over');
                if (after) {
                    item.parentNode.insertBefore(dragEl, item.nextSibling);
                } else {
                    item.parentNode.insertBefore(dragEl, item);
                }
            });
            item.addEventListener('dragleave', function () { item.classList.remove('drag-over'); });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.sortable-list[data-reorder-url]').forEach(initList);
    });
})();
