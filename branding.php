<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Branding: organisation name, colours, fonts and sizes used on the editor pages and the Excel template.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_profileeditor\branding;

$action = optional_param('action', '', PARAM_ALPHA);

admin_externalpage_setup('local_profileeditor_branding');
$pageurl = new moodle_url('/local/profileeditor/branding.php');
$PAGE->set_url($pageurl);

if ($action === 'export') {
    require_sesskey();
    $json = json_encode(['type' => 'local_profileeditor_branding', 'version' => 1, 'branding' => branding::get()],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $name = trim(preg_replace('/[^A-Za-z0-9]+/', '_', branding::org_name()), '_');
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . ($name ? $name . '_' : '') . 'branding.json"');
    echo $json;
    exit;
}
if ($action === 'save') {
    require_sesskey();
    $in = [];
    foreach (array_keys(branding::DEFAULTS) as $k) {
        $in[$k] = optional_param($k, branding::DEFAULTS[$k], PARAM_RAW_TRIMMED);
    }
    $in['showlogo'] = optional_param('showlogo', 0, PARAM_BOOL);
    branding::save($in);
    redirect($pageurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}
if ($action === 'reset') {
    require_sesskey();
    branding::reset();
    redirect($pageurl, get_string('branding_resetdone', 'local_profileeditor'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}
if ($action === 'import') {
    require_sesskey();
    $file = $_FILES['brandfile'] ?? null;
    $data = ($file && empty($file['error']) && is_uploaded_file($file['tmp_name'])) ?
        json_decode(file_get_contents($file['tmp_name']), true) : null;
    if (!is_array($data) || !isset($data['branding']) || !is_array($data['branding'])) {
        redirect($pageurl, get_string('branding_badfile', 'local_profileeditor'), null,
            \core\output\notification::NOTIFY_ERROR);
    }
    branding::save($data['branding']);
    redirect($pageurl, get_string('branding_imported', 'local_profileeditor'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$b = branding::get();
branding::require_css();

$fontoptions = [];
foreach (branding::FONTS as $k => $font) {
    $fontoptions[$k] = $font[0];
}
$jsdata = [
    'presets' => branding::PRESETS,
    'defaults' => branding::DEFAULTS,
    'fonts' => array_map(function($f) {
        return ['stack' => $f[1], 'google' => $f[2]];
    }, branding::FONTS),
];
$PAGE->requires->js(new moodle_url('/local/profileeditor/js/branding.js', ['v' => 2026110600]), false);

/**
 * Colour input row.
 *
 * @param string $key
 * @param array $b
 * @return string
 */
function local_profileeditor_colour(string $key, array $b): string {
    $id = 'lpe-b-' . $key;
    return html_writer::div(
        html_writer::label(get_string('branding_' . $key, 'local_profileeditor'), $id, true, ['class' => 'lpe-label']) .
        html_writer::div(
            html_writer::empty_tag('input', ['type' => 'color', 'value' => $b[$key], 'data-for' => $id,
                'class' => 'lpe-colourpick', 'aria-label' => get_string('branding_' . $key, 'local_profileeditor')]) .
            html_writer::empty_tag('input', ['type' => 'text', 'name' => $key, 'id' => $id, 'value' => $b[$key],
                'class' => 'form-control lpe-colourtext', 'pattern' => '#[0-9A-Fa-f]{6}', 'maxlength' => 7,
                'data-var' => $key]),
            'lpe-colour'),
        'lpe-field');
}

/**
 * Number (px) input row.
 *
 * @param string $key
 * @param array $b
 * @return string
 */
function local_profileeditor_size(string $key, array $b): string {
    [$min, $max] = branding::SIZES[$key];
    $id = 'lpe-b-' . $key;
    return html_writer::div(
        html_writer::label(get_string('branding_' . $key, 'local_profileeditor'), $id, true, ['class' => 'lpe-label']) .
        html_writer::div(
            html_writer::empty_tag('input', ['type' => 'range', 'min' => $min, 'max' => $max, 'value' => $b[$key],
                'data-for' => $id, 'class' => 'lpe-range', 'aria-label' => get_string('branding_' . $key, 'local_profileeditor')]) .
            html_writer::empty_tag('input', ['type' => 'number', 'min' => $min, 'max' => $max, 'name' => $key, 'id' => $id,
                'value' => $b[$key], 'class' => 'form-control lpe-num', 'data-var' => $key]) .
            html_writer::span('px', 'lpe-muted'),
            'lpe-sizerow'),
        'lpe-field');
}

echo $OUTPUT->header();
echo html_writer::start_div('lpe-brand', ['id' => 'lpe-brandroot', 'data-config' => json_encode($jsdata)]);
echo branding::header(get_string('nav_branding', 'local_profileeditor'), 'branding',
    get_string('branding_subtitle', 'local_profileeditor'));

echo html_writer::start_div('lpe-brandgrid');

// Left: the form.
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl->out(false), 'class' => 'lpe-card',
    'id' => 'lpe-brandform']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);

// Presets.
echo html_writer::tag('h4', get_string('branding_presets', 'local_profileeditor'));
$presets = '';
foreach (branding::PRESETS as $k => $p) {
    $sw = html_writer::span('', 'lpe-sw', ['style' => 'background:' . $p['headerbg']]) .
        html_writer::span('', 'lpe-sw', ['style' => 'background:' . $p['primary']]) .
        html_writer::span('', 'lpe-sw', ['style' => 'background:' . $p['accent']]);
    $presets .= html_writer::tag('button', $sw . ' ' . get_string('preset_' . $k, 'local_profileeditor'),
        ['type' => 'button', 'class' => 'lpe-preset', 'data-preset' => $k]);
}
echo html_writer::div($presets, 'lpe-presets');
echo html_writer::div(get_string('branding_presets_desc', 'local_profileeditor'), 'lpe-muted small mb-3');

// Identity.
echo html_writer::tag('h4', get_string('branding_identity', 'local_profileeditor'));
echo html_writer::div(
    html_writer::label(get_string('branding_orgname', 'local_profileeditor'), 'lpe-b-orgname', true, ['class' => 'lpe-label']) .
    html_writer::empty_tag('input', ['type' => 'text', 'name' => 'orgname', 'id' => 'lpe-b-orgname', 'value' => $b['orgname'],
        'class' => 'form-control', 'maxlength' => 120, 'placeholder' => format_string($SITE->fullname)]) .
    html_writer::div(get_string('branding_orgname_desc', 'local_profileeditor'), 'lpe-muted small'), 'lpe-field');
echo html_writer::div(
    html_writer::label(get_string('branding_tagline', 'local_profileeditor'), 'lpe-b-tagline', true, ['class' => 'lpe-label']) .
    html_writer::empty_tag('input', ['type' => 'text', 'name' => 'tagline', 'id' => 'lpe-b-tagline', 'value' => $b['tagline'],
        'class' => 'form-control', 'maxlength' => 200]), 'lpe-field');
echo html_writer::div(html_writer::checkbox('showlogo', 1, (bool) $b['showlogo'],
    ' ' . get_string('branding_showlogo', 'local_profileeditor'), ['id' => 'lpe-b-showlogo']), 'lpe-field');

// Colours.
echo html_writer::tag('h4', get_string('branding_colours', 'local_profileeditor'));
echo html_writer::start_div('lpe-fieldgrid');
foreach (branding::COLOURS as $c) {
    echo local_profileeditor_colour($c, $b);
}
echo html_writer::end_div();

// Fonts.
echo html_writer::tag('h4', get_string('branding_fonts', 'local_profileeditor'));
echo html_writer::start_div('lpe-fieldgrid');
echo html_writer::div(
    html_writer::label(get_string('branding_font', 'local_profileeditor'), 'lpe-b-font', true, ['class' => 'lpe-label']) .
    html_writer::select($fontoptions, 'font', $b['font'], false, ['id' => 'lpe-b-font', 'class' => 'custom-select form-select',
        'data-var' => 'font']), 'lpe-field');
echo html_writer::div(
    html_writer::label(get_string('branding_headingfont', 'local_profileeditor'), 'lpe-b-headingfont', true,
        ['class' => 'lpe-label']) .
    html_writer::select(['same' => get_string('branding_samefont', 'local_profileeditor')] + $fontoptions, 'headingfont',
        $b['headingfont'], false, ['id' => 'lpe-b-headingfont', 'class' => 'custom-select form-select',
        'data-var' => 'headingfont']), 'lpe-field');
echo html_writer::end_div();
echo html_writer::div(
    html_writer::label(get_string('branding_customfont', 'local_profileeditor'), 'lpe-b-customfont', true,
        ['class' => 'lpe-label']) .
    html_writer::empty_tag('input', ['type' => 'text', 'name' => 'customfont', 'id' => 'lpe-b-customfont',
        'value' => $b['customfont'], 'class' => 'form-control', 'placeholder' => '"Brand Sans", Arial',
        'data-var' => 'customfont']) .
    html_writer::div(get_string('branding_customfont_desc', 'local_profileeditor'), 'lpe-muted small'), 'lpe-field');
echo html_writer::div(
    html_writer::label(get_string('branding_fonturl', 'local_profileeditor'), 'lpe-b-fonturl', true, ['class' => 'lpe-label']) .
    html_writer::empty_tag('input', ['type' => 'url', 'name' => 'fonturl', 'id' => 'lpe-b-fonturl', 'value' => $b['fonturl'],
        'class' => 'form-control', 'placeholder' => 'https://fonts.googleapis.com/css2?family=…']) .
    html_writer::div(get_string('branding_fonturl_desc', 'local_profileeditor'), 'lpe-muted small'), 'lpe-field');

// Sizes.
echo html_writer::tag('h4', get_string('branding_sizes', 'local_profileeditor'));
echo html_writer::start_div('lpe-fieldgrid');
foreach (array_keys(branding::SIZES) as $k) {
    echo local_profileeditor_size($k, $b);
}
echo html_writer::div(
    html_writer::label(get_string('branding_density', 'local_profileeditor'), 'lpe-b-density', true, ['class' => 'lpe-label']) .
    html_writer::select(['comfortable' => get_string('branding_density_comfortable', 'local_profileeditor'),
        'compact' => get_string('branding_density_compact', 'local_profileeditor')], 'density', $b['density'], false,
        ['id' => 'lpe-b-density', 'class' => 'custom-select form-select', 'data-var' => 'density']), 'lpe-field');
echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-primary btn-lg']) . ' ' .
    html_writer::tag('button', get_string('branding_reset', 'local_profileeditor'), ['type' => 'submit', 'name' => 'action',
        'value' => 'reset', 'class' => 'btn btn-outline-secondary', 'formnovalidate' => 'formnovalidate']),
    'lpe-actions');
echo html_writer::end_tag('form');

// Right: live preview + export/import.
echo html_writer::start_div('lpe-brandside');
echo html_writer::tag('h4', get_string('branding_preview', 'local_profileeditor'));
$preview = html_writer::div(
    html_writer::div(html_writer::div(s(branding::org_name()), 'lpe-hero-org', ['id' => 'lpe-pv-org']) .
        html_writer::tag('h2', get_string('pluginname', 'local_profileeditor'), ['class' => 'lpe-hero-title']) .
        html_writer::div(s($b['tagline']), 'lpe-hero-sub', ['id' => 'lpe-pv-tagline']), 'lpe-hero-text'),
    'lpe-hero lpe-hero-sm');
$preview .= html_writer::div(
    html_writer::tag('button', get_string('apply', 'local_profileeditor'), ['type' => 'button', 'class' => 'btn btn-primary']) .
    ' ' . html_writer::tag('button', get_string('nav_import', 'local_profileeditor'),
        ['type' => 'button', 'class' => 'btn btn-accent']) .
    ' ' . html_writer::link('#', get_string('branding_samplelink', 'local_profileeditor')), 'lpe-pv-buttons');
$preview .= html_writer::div(html_writer::tag('table',
    html_writer::tag('thead', html_writer::tag('tr', html_writer::tag('th', get_string('user')) .
        html_writer::tag('th', get_string('department')) . html_writer::tag('th', get_string('branding_samplemanager',
        'local_profileeditor')))) .
    html_writer::tag('tbody',
        html_writer::tag('tr', html_writer::tag('td', 'Alex Morgan') . html_writer::tag('td', 'Finance', ['class' => 'lpe-cell lpe-editable']) .
            html_writer::tag('td', 'Sam Lee', ['class' => 'lpe-cell lpe-editable'])) .
        html_writer::tag('tr', html_writer::tag('td', 'Jordan Patel') .
            html_writer::tag('td', 'Engineering', ['class' => 'lpe-cell lpe-editable lpe-pv-hover']) .
            html_writer::tag('td', 'Chris Diaz', ['class' => 'lpe-cell lpe-readonly']))),
    ['class' => 'table table-sm lpe-table']), 'lpe-tablewrap');
echo html_writer::div($preview, 'lpe-preview', ['id' => 'lpe-preview']);

echo html_writer::start_div('lpe-card mt-3');
echo html_writer::tag('h4', get_string('branding_share', 'local_profileeditor'));
echo html_writer::tag('p', get_string('branding_share_desc', 'local_profileeditor'), ['class' => 'small']);
echo html_writer::link(new moodle_url($pageurl, ['action' => 'export', 'sesskey' => sesskey()]),
    get_string('branding_export', 'local_profileeditor'), ['class' => 'btn btn-outline-secondary mb-2']);
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl->out(false), 'enctype' => 'multipart/form-data']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'import']);
echo html_writer::empty_tag('input', ['type' => 'file', 'name' => 'brandfile', 'accept' => '.json', 'required' => 'required',
    'class' => 'form-control mb-2']);
echo html_writer::tag('button', get_string('branding_import', 'local_profileeditor'),
    ['type' => 'submit', 'class' => 'btn btn-outline-secondary']);
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
