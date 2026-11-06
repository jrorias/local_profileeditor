/**
 * Branding page: presets, colour pickers and live preview.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
(function() {
    'use strict';
    var root = document.getElementById('lpe-brandroot');
    var form = document.getElementById('lpe-brandform');
    if (!root || !form) {
        return;
    }
    var cfg = JSON.parse(root.getAttribute('data-config'));
    var loadedFonts = {};

    function val(name) {
        var el = form.querySelector('[name="' + name + '"]');
        return el ? el.value : '';
    }

    function stack(key) {
        if (key === 'custom') {
            var c = val('customfont');
            return c ? c + ', system-ui, sans-serif' : cfg.fonts.system.stack;
        }
        var f = cfg.fonts[key] || cfg.fonts.system;
        if (f.google && !loadedFonts[f.google]) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://fonts.googleapis.com/css2?family=' + f.google + '&display=swap';
            document.head.appendChild(link);
            loadedFonts[f.google] = true;
        }
        return f.stack;
    }

    // Apply the form values to the whole page live (the page itself is the preview).
    function update() {
        var s = root.style;
        Object.keys(cfg.presets.classic).forEach(function(c) {
            var v = val(c);
            if (/^#[0-9a-fA-F]{6}$/.test(v)) {
                s.setProperty('--lpe-' + c, v);
            }
        });
        var body = stack(val('font'));
        var head = val('headingfont') === 'same' ? body : stack(val('headingfont'));
        s.setProperty('--lpe-font', body);
        s.setProperty('--lpe-headingfont', head);
        ['basesize', 'tablesize', 'headingsize', 'radius'].forEach(function(k) {
            s.setProperty('--lpe-' + k, (parseInt(val(k), 10) || 0) + 'px');
        });
        s.setProperty('--lpe-cellpad', val('density') === 'compact' ? '.25rem .4rem' : '.5rem .65rem');
        var org = document.getElementById('lpe-pv-org');
        if (org) {
            org.textContent = val('orgname') || form.querySelector('[name="orgname"]').placeholder;
        }
        var tag = document.getElementById('lpe-pv-tagline');
        if (tag) {
            tag.textContent = val('tagline');
        }
        document.getElementById('lpe-b-customfont').closest('.lpe-field').style.display =
            (val('font') === 'custom' || val('headingfont') === 'custom') ? '' : 'none';
    }

    // Colour picker <-> text box, range <-> number box.
    form.querySelectorAll('[data-for]').forEach(function(picker) {
        var target = document.getElementById(picker.getAttribute('data-for'));
        picker.addEventListener('input', function() {
            target.value = picker.value;
            update();
        });
        target.addEventListener('input', function() {
            if (picker.type !== 'color' || /^#[0-9a-fA-F]{6}$/.test(target.value)) {
                picker.value = target.value;
            }
            update();
        });
    });
    form.addEventListener('input', update);
    form.addEventListener('change', update);

    // Presets fill the colour fields (fonts and sizes are kept).
    root.querySelectorAll('[data-preset]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var p = cfg.presets[btn.getAttribute('data-preset')];
            Object.keys(p).forEach(function(k) {
                var t = form.querySelector('[name="' + k + '"]');
                if (t) {
                    t.value = p[k];
                    var picker = form.querySelector('[data-for="' + t.id + '"]');
                    if (picker) {
                        picker.value = p[k];
                    }
                }
            });
            root.querySelectorAll('[data-preset]').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            update();
        });
    });

    update();
})();
