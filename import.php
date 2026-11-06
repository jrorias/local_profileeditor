<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Import updates: upload the filled-in template, preview exactly what will change, then apply.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_profileeditor\branding;
use local_profileeditor\importer;

$action = optional_param('action', '', PARAM_ALPHA);
$token  = optional_param('token', '', PARAM_ALPHANUM);

admin_externalpage_setup('local_profileeditor_import');
$context = context_system::instance();
require_capability('local/profileeditor:edit', $context);
$pageurl = new moodle_url('/local/profileeditor/import.php');
$PAGE->set_url($pageurl);
branding::require_css();

/**
 * Print the page start.
 */
function local_profileeditor_import_start() {
    global $OUTPUT;
    echo $OUTPUT->header();
    echo html_writer::start_div('lpe-brand');
    echo branding::header(get_string('nav_import', 'local_profileeditor'), 'import',
        get_string('import_subtitle', 'local_profileeditor'));
}

/**
 * Print the page end.
 */
function local_profileeditor_import_end() {
    global $OUTPUT;
    echo html_writer::end_div();
    echo $OUTPUT->footer();
}

/**
 * A stat tile.
 *
 * @param int|string $n
 * @param string $label
 * @param string $class
 * @return string
 */
function local_profileeditor_stat($n, string $label, string $class = ''): string {
    return html_writer::div(html_writer::div(s($n), 'lpe-stat-n') . html_writer::div(s($label), 'lpe-stat-l'),
        'lpe-stat ' . $class);
}

// 1. Upload + build the preview.
if ($action === 'preview') {
    require_sesskey();
    $matchby = optional_param('matchby', 'auto', PARAM_ALPHA);
    $blankclears = optional_param('blankclears', 0, PARAM_BOOL);
    if (!in_array($matchby, ['auto', 'username', 'email', 'idnumber'], true)) {
        $matchby = 'auto';
    }
    $file = $_FILES['importfile'] ?? null;
    if (!$file || !empty($file['error']) || !is_uploaded_file($file['tmp_name'])) {
        redirect($pageurl, get_string('import_nofile', 'local_profileeditor'), null, \core\output\notification::NOTIFY_ERROR);
    }
    core_php_time_limit::raise(600);
    raise_memory_limit(MEMORY_EXTRA);
    try {
        $rows = importer::read_file($file['tmp_name'], clean_param($file['name'], PARAM_FILE));
        $plan = importer::build_plan($rows, $matchby, (bool) $blankclears);
    } catch (moodle_exception $e) {
        redirect($pageurl, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
    $plan['filename'] = clean_param($file['name'], PARAM_FILE);
    $token = importer::store_plan($plan);
    redirect(new moodle_url($pageurl, ['token' => $token]));
}

// 2b. Cancel.
if ($action === 'cancel') {
    require_sesskey();
    importer::delete_plan($token);
    redirect($pageurl);
}

// 3. Apply.
if ($action === 'apply') {
    require_sesskey();
    $plan = importer::load_plan($token);
    if (!$plan) {
        redirect($pageurl, get_string('import_expired', 'local_profileeditor'), null,
            \core\output\notification::NOTIFY_WARNING);
    }
    importer::delete_plan($token); // Never apply the same file twice.
    core_php_time_limit::raise(1800);
    raise_memory_limit(MEMORY_EXTRA);

    local_profileeditor_import_start();
    echo html_writer::tag('h3', get_string('import_applying', 'local_profileeditor'));
    $pbclass = class_exists('\core\output\progress_bar') ? '\core\output\progress_bar' : '\progress_bar';
    $pb = new $pbclass('lpe-import-progress', 500, true);
    $result = importer::apply_plan($plan, function($done, $total) use ($pb) {
        if ($done === $total || $done % 10 === 0) {
            $pb->update($done, max(1, $total), get_string('import_progress', 'local_profileeditor',
                (object) ['done' => $done, 'total' => $total]));
        }
    });

    echo html_writer::start_div('lpe-stats');
    echo local_profileeditor_stat($result['users'], get_string('import_r_users', 'local_profileeditor'), 'ok');
    echo local_profileeditor_stat($result['fields'], get_string('import_r_fields', 'local_profileeditor'), 'ok');
    echo local_profileeditor_stat(count($result['errors']), get_string('import_r_errors', 'local_profileeditor'),
        $result['errors'] ? 'bad' : '');
    echo html_writer::end_div();

    if ($result['errors']) {
        $t = new html_table();
        $t->attributes['class'] = 'generaltable lpe-table-simple';
        $t->head = [get_string('import_who', 'local_profileeditor'), get_string('log_field', 'local_profileeditor'),
            get_string('import_problem', 'local_profileeditor')];
        foreach ($result['errors'] as $e) {
            $t->data[] = [s($e['who']), s($e['field']), s($e['msg'])];
        }
        echo html_writer::table($t);
    }
    echo html_writer::div(
        html_writer::link(new moodle_url('/local/profileeditor/log.php', ['source' => 'import']),
            get_string('import_viewlog', 'local_profileeditor'), ['class' => 'btn btn-primary']) . ' ' .
        html_writer::link(new moodle_url('/local/profileeditor/index.php'),
            get_string('backtoeditor', 'local_profileeditor'), ['class' => 'btn btn-secondary']) . ' ' .
        html_writer::link($pageurl, get_string('import_another', 'local_profileeditor'),
            ['class' => 'btn btn-outline-secondary']), 'mt-3');
    local_profileeditor_import_end();
    exit;
}

// 2. Preview.
if ($token !== '') {
    $plan = importer::load_plan($token);
    if (!$plan) {
        redirect($pageurl, get_string('import_expired', 'local_profileeditor'), null,
            \core\output\notification::NOTIFY_WARNING);
    }
    $st = $plan['stats'];
    local_profileeditor_import_start();
    echo html_writer::tag('h3', get_string('import_preview', 'local_profileeditor', s($plan['filename'] ?? '')));
    echo html_writer::div(get_string('import_previewintro', 'local_profileeditor',
        get_string('import_matchby_' . $plan['matchby'], 'local_profileeditor')), 'lpe-muted mb-3');

    echo html_writer::start_div('lpe-stats');
    echo local_profileeditor_stat($st['rows'], get_string('import_s_rows', 'local_profileeditor'));
    echo local_profileeditor_stat($st['matched'], get_string('import_s_matched', 'local_profileeditor'));
    echo local_profileeditor_stat($st['users'], get_string('import_s_users', 'local_profileeditor'), 'ok');
    echo local_profileeditor_stat($st['changes'], get_string('import_s_changes', 'local_profileeditor'), 'ok');
    echo local_profileeditor_stat($st['unchanged'], get_string('import_s_unchanged', 'local_profileeditor'));
    echo local_profileeditor_stat($st['errors'], get_string('import_s_errors', 'local_profileeditor'),
        $st['errors'] ? 'bad' : '');
    echo html_writer::end_div();

    // Columns found in the file.
    $chips = '';
    foreach ($plan['columns'] as $c) {
        $n = ($c['key'] && isset($plan['percolumn'][$c['key']])) ? ' · ' . $plan['percolumn'][$c['key']] : '';
        $chips .= html_writer::span(s($c['name']) . html_writer::tag('small', ' ' .
            get_string('import_col_' . $c['status'], 'local_profileeditor') . $n), 'lpe-chip lpe-chip-' . $c['status']);
    }
    echo html_writer::div(html_writer::tag('strong', get_string('import_columns', 'local_profileeditor')) .
        html_writer::div($chips, 'lpe-chips'), 'lpe-card');

    // Apply / cancel.
    $buttons = '';
    if ($st['changes']) {
        $buttons .= html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl->out(false),
            'class' => 'd-inline']) .
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'token', 'value' => $token]) .
            html_writer::tag('button', get_string('import_apply', 'local_profileeditor',
                (object) ['changes' => $st['changes'], 'users' => $st['users']]),
                ['type' => 'submit', 'name' => 'action', 'value' => 'apply', 'class' => 'btn btn-primary btn-lg']) .
            html_writer::end_tag('form') . ' ';
    } else {
        echo $OUTPUT->notification(get_string('import_nothing', 'local_profileeditor'), 'info');
    }
    $buttons .= html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl->out(false), 'class' => 'd-inline']) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'token', 'value' => $token]) .
        html_writer::tag('button', get_string('cancel'), ['type' => 'submit', 'name' => 'action', 'value' => 'cancel',
            'class' => 'btn btn-secondary btn-lg']) .
        html_writer::end_tag('form');
    echo html_writer::div($buttons, 'lpe-actions');

    // Problems.
    if ($plan['errors']) {
        echo html_writer::tag('h4', get_string('import_errors', 'local_profileeditor', count($plan['errors'])));
        echo html_writer::div(get_string('import_errorsnote', 'local_profileeditor'), 'lpe-muted mb-2');
        $t = new html_table();
        $t->attributes['class'] = 'generaltable lpe-table-simple';
        $t->head = [get_string('import_row', 'local_profileeditor'), get_string('import_who', 'local_profileeditor'),
            get_string('log_field', 'local_profileeditor'), get_string('import_value', 'local_profileeditor'),
            get_string('import_problem', 'local_profileeditor')];
        foreach (array_slice($plan['errors'], 0, 1000) as $e) {
            $t->data[] = [$e['row'], s($e['who']), s($e['field']), s($e['value'] ?? ''),
                html_writer::span(s($e['msg']), 'text-danger')];
        }
        echo html_writer::div(html_writer::table($t), 'lpe-scroll');
    }

    // Changes.
    if ($plan['changes']) {
        $shown = 2000;
        echo html_writer::tag('h4', get_string('import_changes', 'local_profileeditor', count($plan['changes'])));
        if (count($plan['changes']) > $shown) {
            echo html_writer::div(get_string('import_changesmore', 'local_profileeditor', $shown), 'lpe-muted mb-2');
        }
        $fields = \local_profileeditor\field_manager::get_all_fields();
        $t = new html_table();
        $t->attributes['class'] = 'generaltable lpe-table-simple';
        $t->head = [get_string('import_row', 'local_profileeditor'), get_string('import_who', 'local_profileeditor'),
            get_string('log_field', 'local_profileeditor'), get_string('log_old', 'local_profileeditor'),
            get_string('log_new', 'local_profileeditor')];
        foreach (array_slice($plan['changes'], 0, $shown) as $c) {
            $t->data[] = [$c['row'], s($c['name']) . html_writer::div(s($c['who']), 'lpe-short'),
                s($fields[$c['k']]->name ?? $c['k']), html_writer::span(s($c['old']), 'lpe-old'),
                html_writer::tag('strong', s($c['new']), ['class' => 'lpe-new'])];
        }
        echo html_writer::div(html_writer::table($t), 'lpe-scroll');
        echo html_writer::div($buttons, 'lpe-actions');
    }
    local_profileeditor_import_end();
    exit;
}

// 1. Upload form.
local_profileeditor_import_start();
$defaultmatch = get_config('local_profileeditor', 'matchby') ?: 'auto';

echo html_writer::start_div('lpe-grid');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl->out(false),
    'enctype' => 'multipart/form-data', 'class' => 'lpe-card lpe-upload']);
echo html_writer::tag('h4', get_string('import_step_upload', 'local_profileeditor'));
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'preview']);
echo html_writer::div(
    html_writer::empty_tag('input', ['type' => 'file', 'name' => 'importfile', 'id' => 'lpe-importfile',
        'accept' => '.xlsx,.csv', 'required' => 'required', 'class' => 'lpe-file']) .
    html_writer::label(get_string('import_choosefile', 'local_profileeditor'), 'lpe-importfile', false,
        ['class' => 'lpe-drop']) ,
    'lpe-dropwrap');
echo html_writer::div(get_string('import_maxsize', 'local_profileeditor', display_size(get_max_upload_file_size())),
    'lpe-muted small mb-3');

$matchopts = [];
foreach (['auto', 'username', 'email', 'idnumber'] as $m) {
    $matchopts[$m] = get_string('import_matchby_' . $m, 'local_profileeditor');
}
echo html_writer::div(
    html_writer::label(get_string('import_matchby', 'local_profileeditor'), 'lpe-matchby', true, ['class' => 'lpe-label']) .
    html_writer::select($matchopts, 'matchby', $defaultmatch, false, ['id' => 'lpe-matchby',
        'class' => 'custom-select form-select']), 'mb-3');
echo html_writer::div(
    html_writer::label(get_string('import_blank', 'local_profileeditor'), 'lpe-blank', true, ['class' => 'lpe-label']) .
    html_writer::select([0 => get_string('import_blank_skip', 'local_profileeditor'),
        1 => get_string('import_blank_clear', 'local_profileeditor')], 'blankclears', 0, false,
        ['id' => 'lpe-blank', 'class' => 'custom-select form-select']), 'mb-3');
echo html_writer::tag('button', get_string('import_previewbtn', 'local_profileeditor'),
    ['type' => 'submit', 'class' => 'btn btn-primary btn-lg']);
echo html_writer::div(get_string('import_safe', 'local_profileeditor'), 'lpe-muted small mt-2');
echo html_writer::end_tag('form');

// How it works.
echo html_writer::start_div('lpe-card lpe-how');
echo html_writer::tag('h4', get_string('import_how', 'local_profileeditor'));
$steps = '';
for ($n = 1; $n <= 4; $n++) {
    $steps .= html_writer::tag('li', get_string('import_how' . $n, 'local_profileeditor'));
}
echo html_writer::tag('ol', $steps, ['class' => 'lpe-steps']);
echo html_writer::start_tag('form', ['method' => 'post',
    'action' => (new moodle_url('/local/profileeditor/template.php'))->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::tag('button', get_string('template_download_xlsx', 'local_profileeditor'),
    ['type' => 'submit', 'name' => 'format', 'value' => 'xlsx', 'class' => 'btn btn-accent']) . ' ';
echo html_writer::link(new moodle_url('/local/profileeditor/index.php'),
    get_string('template_choosefields', 'local_profileeditor'), ['class' => 'btn btn-link']);
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo html_writer::end_div();
$PAGE->requires->js_amd_inline("
    var f = document.getElementById('lpe-importfile');
    var l = document.querySelector('label.lpe-drop');
    if (f && l) {
        f.addEventListener('change', function() {
            if (f.files.length) { l.textContent = f.files[0].name; l.classList.add('has-file'); }
        });
    }
");
local_profileeditor_import_end();
