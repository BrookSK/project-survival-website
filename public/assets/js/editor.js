/* =====================================================================
   Editor de conteúdo leve (rich text) — sem dependências externas.
   Transforma <textarea data-editor> em um editor WYSIWYG com toolbar.
   O HTML é sincronizado de volta ao textarea (que é enviado no form e
   sanitizado no backend por HtmlSanitizer).
   ===================================================================== */
(function () {
    'use strict';

    var COMMANDS = [
        { cmd: 'formatBlock', value: 'h2', label: 'H2', title: 'Título' },
        { cmd: 'formatBlock', value: 'h3', label: 'H3', title: 'Subtítulo' },
        { cmd: 'formatBlock', value: 'p', label: '¶', title: 'Parágrafo' },
        { sep: true },
        { cmd: 'bold', label: 'B', title: 'Negrito', style: 'font-weight:700' },
        { cmd: 'italic', label: 'I', title: 'Itálico', style: 'font-style:italic' },
        { cmd: 'underline', label: 'U', title: 'Sublinhado', style: 'text-decoration:underline' },
        { sep: true },
        { cmd: 'insertUnorderedList', label: '• Lista', title: 'Lista' },
        { cmd: 'insertOrderedList', label: '1. Lista', title: 'Lista numerada' },
        { cmd: 'formatBlock', value: 'blockquote', label: '❝', title: 'Citação' },
        { sep: true },
        { cmd: 'createLink', label: '🔗', title: 'Link', prompt: 'URL do link:' },
        { cmd: 'insertImage', label: '🖼️', title: 'Imagem', prompt: 'URL da imagem:' },
        { cmd: 'unlink', label: '⛓️‍💥', title: 'Remover link' },
        { sep: true },
        { cmd: 'removeFormat', label: 'Limpar', title: 'Limpar formatação' }
    ];

    function buildToolbar(editable, textarea) {
        var bar = document.createElement('div');
        bar.className = 'rte-toolbar';

        COMMANDS.forEach(function (item) {
            if (item.sep) {
                var sep = document.createElement('span');
                sep.className = 'rte-sep';
                bar.appendChild(sep);
                return;
            }
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'rte-btn';
            btn.textContent = item.label;
            btn.title = item.title || '';
            if (item.style) btn.setAttribute('style', item.style);
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                editable.focus();
                exec(item);
                sync(editable, textarea);
            });
            bar.appendChild(btn);
        });

        return bar;
    }

    function exec(item) {
        try {
            if (item.prompt) {
                var val = window.prompt(item.prompt);
                if (!val) return;
                document.execCommand(item.cmd, false, val);
            } else if (item.value) {
                document.execCommand(item.cmd, false, item.value);
            } else {
                document.execCommand(item.cmd, false, null);
            }
        } catch (err) { /* noop */ }
    }

    function sync(editable, textarea) {
        textarea.value = editable.innerHTML;
    }

    function init(textarea) {
        // Evita inicializar duas vezes
        if (textarea.dataset.editorReady === '1') return;
        textarea.dataset.editorReady = '1';

        var wrap = document.createElement('div');
        wrap.className = 'rte';

        var editable = document.createElement('div');
        editable.className = 'rte-content';
        editable.contentEditable = 'true';
        editable.setAttribute('role', 'textbox');
        editable.setAttribute('aria-multiline', 'true');
        editable.innerHTML = textarea.value || '<p></p>';

        var toolbar = buildToolbar(editable, textarea);

        // Esconde o textarea original mas mantém no form
        textarea.style.display = 'none';
        textarea.parentNode.insertBefore(wrap, textarea);
        wrap.appendChild(toolbar);
        wrap.appendChild(editable);
        wrap.appendChild(textarea);

        editable.addEventListener('input', function () { sync(editable, textarea); });
        editable.addEventListener('blur', function () { sync(editable, textarea); });

        // Garante sincronização final no submit
        var form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', function () { sync(editable, textarea); });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('textarea[data-editor]').forEach(init);
    });
})();
