/**
 * Columns page: drag and drop / up-down ordering.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
(function() {
    'use strict';
    var form = document.getElementById('lpe-columns');
    if (!form) {
        return;
    }
    var tbody = form.querySelector('tbody');
    var dragged = null;

    tbody.addEventListener('dragstart', function(e) {
        dragged = e.target.closest('tr');
        if (dragged) {
            dragged.classList.add('lpe-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', dragged.getAttribute('data-key'));
        }
    });
    tbody.addEventListener('dragend', function() {
        if (dragged) {
            dragged.classList.remove('lpe-dragging');
        }
        dragged = null;
    });
    tbody.addEventListener('dragover', function(e) {
        e.preventDefault();
        var tr = e.target.closest('tr');
        if (!dragged || !tr || tr === dragged) {
            return;
        }
        var rect = tr.getBoundingClientRect();
        var after = (e.clientY - rect.top) > rect.height / 2;
        tbody.insertBefore(dragged, after ? tr.nextSibling : tr);
    });

    tbody.addEventListener('click', function(e) {
        var btn = e.target.closest('button');
        if (!btn) {
            return;
        }
        var tr = btn.closest('tr');
        if (btn.classList.contains('lpe-up') && tr.previousElementSibling) {
            tbody.insertBefore(tr, tr.previousElementSibling);
        } else if (btn.classList.contains('lpe-down') && tr.nextElementSibling) {
            tbody.insertBefore(tr.nextElementSibling, tr);
        }
        tr.classList.add('lpe-moved');
        setTimeout(function() { tr.classList.remove('lpe-moved'); }, 600);
    });

    form.querySelectorAll('[data-toggleall]').forEach(function(a) {
        a.addEventListener('click', function(e) {
            e.preventDefault();
            var cls = a.getAttribute('data-toggleall') === 'show' ? '.lpe-c-show' : '.lpe-c-edit';
            var boxes = form.querySelectorAll(cls);
            var allOn = Array.prototype.every.call(boxes, function(b) { return b.checked; });
            boxes.forEach(function(b) { b.checked = !allOn; });
        });
    });
})();
