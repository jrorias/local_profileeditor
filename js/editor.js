/**
 * Profile field editor - in-place cell editing (spreadsheet-style keyboard) and bulk edit.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
(function() {
    'use strict';

    var root = document.getElementById('lpe-root');
    if (!root) {
        return;
    }
    var cfg = JSON.parse(root.getAttribute('data-config'));
    var fields = cfg.fields;
    var str = cfg.strings;

    // Suggestion lists (datalists) so repeated values such as line manager names stay consistent.
    Object.keys(fields).forEach(function(key) {
        var f = fields[key];
        if (!f.suggest || !f.suggest.length) {
            return;
        }
        var dl = document.createElement('datalist');
        dl.id = 'lpe-dl-' + key;
        f.suggest.forEach(function(v) {
            var o = document.createElement('option');
            o.value = v;
            dl.appendChild(o);
        });
        document.body.appendChild(dl);
    });

    // Upload template: select all / none.
    var tSelectAll = document.getElementById('lpe-tselectall');
    if (tSelectAll) {
        tSelectAll.addEventListener('click', function(e) {
            e.preventDefault();
            var boxes = document.querySelectorAll('.lpe-tfield');
            var allChecked = Array.prototype.every.call(boxes, function(b) { return b.checked; });
            boxes.forEach(function(b) { b.checked = !allChecked; });
        });
    }

    // Toast messages.
    var toastBox = document.createElement('div');
    toastBox.className = 'lpe-toasts';
    document.body.appendChild(toastBox);
    function toast(msg, type) {
        var t = document.createElement('div');
        t.className = 'lpe-toast lpe-toast-' + (type || 'error');
        t.textContent = msg;
        toastBox.appendChild(t);
        setTimeout(function() {
            t.classList.add('lpe-hide');
            setTimeout(function() { t.remove(); }, 400);
        }, type === 'success' ? 2500 : 6000);
    }

    function flash(el, cls) {
        el.classList.add(cls);
        setTimeout(function() { el.classList.remove(cls); }, 1500);
    }

    function post(data) {
        var fd = new FormData();
        fd.append('sesskey', cfg.sesskey);
        Object.keys(data).forEach(function(k) {
            var v = data[k];
            if (Array.isArray(v)) {
                v.forEach(function(x) { fd.append(k + '[]', x); });
            } else {
                fd.append(k, v);
            }
        });
        return fetch(cfg.ajaxurl, {method: 'POST', body: fd, credentials: 'same-origin'})
            .then(function(r) { return r.json(); });
    }

    function option(sel, value, label) {
        var o = document.createElement('option');
        o.value = value;
        o.textContent = label;
        sel.appendChild(o);
    }

    function buildInput(f, raw) {
        var el;
        if (f.datatype === 'menu') {
            el = document.createElement('select');
            option(el, '', '');
            f.options.forEach(function(opt) { option(el, opt, opt); });
            if (raw && f.options.indexOf(raw) === -1) {
                option(el, raw, raw); // Keep a legacy value visible.
            }
            el.value = raw;
        } else if (f.datatype === 'checkbox') {
            el = document.createElement('select');
            option(el, '1', str.yes);
            option(el, '0', str.no);
            el.value = raw === '1' ? '1' : '0';
        } else if (f.datatype === 'datetime') {
            el = document.createElement('input');
            el.type = 'date';
            el.value = raw;
        } else if (f.datatype === 'textarea') {
            el = document.createElement('textarea');
            el.rows = 3;
            el.value = raw;
        } else {
            el = document.createElement('input');
            el.type = f.datatype === 'email' ? 'email' : 'text';
            el.value = raw;
            if (f.suggest && f.suggest.length) {
                el.setAttribute('list', 'lpe-dl-' + f.key);
            }
        }
        el.className = 'form-control form-control-sm lpe-input';
        return el;
    }

    function applyResult(td, res) {
        td.setAttribute('data-raw', res.raw);
        td.textContent = res.display;
    }

    // Next editable cell in a direction (right/left along the row, down/up the same column).
    function neighbour(td, dir) {
        if (dir === 'right' || dir === 'left') {
            var el = td;
            do {
                el = dir === 'right' ? el.nextElementSibling : el.previousElementSibling;
            } while (el && !el.classList.contains('lpe-editable'));
            return el;
        }
        var tr = td.parentElement;
        var key = td.getAttribute('data-field');
        do {
            tr = dir === 'down' ? tr.nextElementSibling : tr.previousElementSibling;
        } while (tr && !tr.querySelector('td.lpe-editable[data-field="' + key + '"]'));
        return tr ? tr.querySelector('td.lpe-editable[data-field="' + key + '"]') : null;
    }

    // In-place editing. Enter = save and go down, Tab = save and go right (Shift = back), Esc = cancel.
    function startEdit(td) {
        var f = fields[td.getAttribute('data-field')];
        if (!f || !f.editable || td.classList.contains('lpe-editing') || td.classList.contains('lpe-saving')) {
            return;
        }
        var raw = td.getAttribute('data-raw') || '';
        var original = td.textContent;
        var done = false;
        td.classList.add('lpe-editing');
        td.textContent = '';
        var input = buildInput(f, raw);
        td.appendChild(input);
        input.focus();
        if (input.select && input.tagName === 'INPUT' && input.type === 'text') {
            input.select();
        }

        function cancel() {
            if (done) {
                return;
            }
            done = true;
            td.classList.remove('lpe-editing');
            td.textContent = original;
        }

        function commit(next) {
            if (done) {
                return;
            }
            var val = input.value;
            if (val === raw) {
                cancel();
            } else {
                done = true;
                input.disabled = true;
                td.classList.add('lpe-saving');
                post({action: 'save', userid: td.getAttribute('data-userid'), field: f.key, value: val})
                    .then(function(res) {
                        td.classList.remove('lpe-saving', 'lpe-editing');
                        if (res.success) {
                            applyResult(td, res);
                            flash(td, 'lpe-ok');
                        } else {
                            td.textContent = original;
                            flash(td, 'lpe-err');
                            toast(res.error);
                        }
                    })
                    .catch(function(err) {
                        td.classList.remove('lpe-saving', 'lpe-editing');
                        td.textContent = original;
                        flash(td, 'lpe-err');
                        toast(String(err));
                    });
            }
            // Keep going without waiting for the save, spreadsheet style.
            if (next) {
                var n = neighbour(td, next);
                if (n) {
                    n.scrollIntoView({block: 'nearest', inline: 'nearest'});
                    startEdit(n);
                } else {
                    td.focus();
                }
            }
        }

        input.addEventListener('keydown', function(ev) {
            if (ev.key === 'Escape') {
                ev.preventDefault();
                cancel();
                td.focus();
            } else if (ev.key === 'Enter' && (f.datatype !== 'textarea' || ev.ctrlKey)) {
                ev.preventDefault();
                commit(ev.shiftKey ? 'up' : 'down');
            } else if (ev.key === 'Tab') {
                ev.preventDefault();
                commit(ev.shiftKey ? 'left' : 'right');
            }
        });
        input.addEventListener('blur', function() {
            commit(null);
        });
        if (input.tagName === 'SELECT') {
            input.addEventListener('change', function() {
                commit(null);
                td.focus();
            });
        }
    }

    root.addEventListener('click', function(e) {
        if (!cfg.canedit) {
            return;
        }
        var td = e.target.closest('td.lpe-editable');
        if (td) {
            startEdit(td);
        }
    });

    // Keyboard on a focused (not editing) cell: Enter/F2 or typing starts editing, arrows move.
    root.addEventListener('keydown', function(e) {
        var td = e.target;
        if (!cfg.canedit || !td.classList || !td.classList.contains('lpe-editable') || td.classList.contains('lpe-editing')) {
            return;
        }
        var map = {ArrowRight: 'right', ArrowLeft: 'left', ArrowDown: 'down', ArrowUp: 'up'};
        if (map[e.key]) {
            var n = neighbour(td, map[e.key]);
            if (n) {
                e.preventDefault();
                n.focus();
            }
        } else if (e.key === 'Enter' || e.key === 'F2') {
            e.preventDefault();
            startEdit(td);
        }
    });

    // Bulk edit.
    if (!cfg.canedit) {
        return;
    }
    var selectAll = document.getElementById('lpe-selectall');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            root.querySelectorAll('.lpe-select').forEach(function(cb) { cb.checked = selectAll.checked; });
        });
    }
    var bulkField = document.getElementById('lpe-bulkfield');
    var bulkWrap = document.getElementById('lpe-bulkvalue-wrap');
    var bulkApply = document.getElementById('lpe-bulkapply');
    var bulkInput = null;

    function rebuildBulkInput() {
        bulkWrap.textContent = '';
        bulkInput = null;
        var f = fields[bulkField.value];
        if (!f) {
            return;
        }
        bulkInput = buildInput(f, '');
        bulkInput.classList.add('lpe-bulkinput');
        bulkWrap.appendChild(bulkInput);
    }
    if (bulkField) {
        bulkField.addEventListener('change', rebuildBulkInput);
    }

    if (bulkApply) {
        bulkApply.addEventListener('click', function() {
            var f = fields[bulkField.value];
            if (!f || !bulkInput) {
                bulkField.focus();
                return;
            }
            var scope = document.getElementById('lpe-bulkscope');
            var all = scope && scope.value === 'all';
            var ids = [];
            if (!all) {
                root.querySelectorAll('.lpe-select:checked').forEach(function(cb) { ids.push(cb.value); });
                if (!ids.length) {
                    toast(str.bulknoneselected);
                    return;
                }
            }
            var msg = str.bulkconfirm.replace('{field}', f.name).replace('{count}', all ? cfg.total : ids.length);
            // eslint-disable-next-line no-alert
            if (!window.confirm(msg)) {
                return;
            }
            bulkApply.disabled = true;
            if (all) {
                var data = {action: 'bulkall', field: f.key, value: bulkInput.value};
                Object.keys(cfg.filters).forEach(function(k) {
                    var v = cfg.filters[k];
                    data[k] = (v === true) ? 1 : (v === false ? 0 : v);
                });
                toast(str.bulkworking, 'success');
                post(data).then(function(res) {
                    bulkApply.disabled = false;
                    if (!res.success) {
                        toast(res.error);
                        return;
                    }
                    toast(str.bulkdone.replace('{updated}', res.updatedcount).replace('{failed}', res.errors.length),
                        res.errors.length ? 'error' : 'success');
                    if (res.errors.length) {
                        toast(res.errors[0].error);
                    }
                    setTimeout(function() { window.location.reload(); }, 1200);
                }).catch(function(err) {
                    bulkApply.disabled = false;
                    toast(String(err));
                });
                return;
            }
            post({action: 'bulk', field: f.key, value: bulkInput.value, userids: ids})
                .then(function(res) {
                    bulkApply.disabled = false;
                    if (!res.success) {
                        toast(res.error);
                        return;
                    }
                    res.updated.forEach(function(r) {
                        var td = root.querySelector('td.lpe-cell[data-userid="' + r.userid + '"][data-field="' + f.key + '"]');
                        if (td) {
                            applyResult(td, r);
                            flash(td, 'lpe-ok');
                        }
                    });
                    res.errors.forEach(function(e) {
                        var td = root.querySelector('td.lpe-cell[data-userid="' + e.userid + '"][data-field="' + f.key + '"]');
                        if (td) {
                            flash(td, 'lpe-err');
                        }
                    });
                    var summary = str.bulkdone.replace('{updated}', res.updated.length)
                        .replace('{failed}', res.errors.length);
                    toast(summary, res.errors.length ? 'error' : 'success');
                    if (res.errors.length) {
                        toast(res.errors[0].error);
                    }
                })
                .catch(function(err) {
                    bulkApply.disabled = false;
                    toast(String(err));
                });
        });
    }
})();
