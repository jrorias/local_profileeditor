<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Profile field editor: one table with every user and every profile field, editable in place.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_profileeditor\branding;
use local_profileeditor\field_manager;
use local_profileeditor\template_builder;
use local_profileeditor\user_query;

$defaultperpage = (int) get_config('local_profileeditor', 'defaultperpage') ?: 50;

$filters  = user_query::filters_from_request();
$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', $defaultperpage, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

admin_externalpage_setup('local_profileeditor');
$context = context_system::instance();
$canedit = has_capability('local/profileeditor:edit', $context);

$allfields = field_manager::get_ordered_fields();
$fields    = field_manager::get_visible_fields();

$urlparams = array_filter($filters, function($v) {
    return $v !== '' && $v !== 0 && $v !== null && $v !== false;
});
$urlparams['perpage'] = $perpage;
$baseurl = new moodle_url('/local/profileeditor/index.php', $urlparams);
$PAGE->set_url($baseurl);

[$wheresql, $params, $orderby] = user_query::build($filters);
$usercols = user_query::user_columns();

// CSV download of the current view (all matching users).
if ($download === 'csv') {
    require_once($CFG->libdir . '/csvlib.class.php');
    $users = $DB->get_records_sql("SELECT $usercols FROM {user} u WHERE $wheresql ORDER BY $orderby", $params);
    $writer = new csv_export_writer();
    $writer->set_filename(template_builder::filename());
    $header = ['username'];
    foreach ($fields as $f) {
        $header[] = template_builder::column_key($f);
    }
    $writer->add_data($header);
    foreach (array_chunk($users, 500, true) as $chunk) {
        $values = field_manager::get_values($chunk, $fields);
        foreach ($chunk as $u) {
            $row = [$u->username];
            foreach ($fields as $f) {
                $row[] = field_manager::export_value($f, $values[$u->id][$f->key] ?? '');
            }
            $writer->add_data($row);
        }
    }
    $writer->download_file();
    exit;
}

$total = $DB->count_records_sql("SELECT COUNT(1) FROM {user} u WHERE $wheresql", $params);
$limit = $perpage > 0 ? $perpage : 0;
if ($limit && $page * $limit >= $total) {
    $page = 0;
}
$users = $DB->get_records_sql("SELECT $usercols FROM {user} u WHERE $wheresql ORDER BY $orderby",
    $params, $limit ? $page * $limit : 0, $limit);
$values = field_manager::get_values($users, $fields);

// Config for the JavaScript editor.
$jsfields = [];
foreach ($fields as $f) {
    $editable = $canedit && field_manager::is_editable($f);
    $jsfields[$f->key] = [
        'key' => $f->key,
        'name' => $f->name,
        'shortname' => $f->shortname,
        'datatype' => $f->datatype,
        'options' => $f->options,
        'editable' => $editable,
        'suggest' => $editable ? field_manager::get_suggestions($f) : [],
    ];
}
$jsconfig = [
    'ajaxurl' => (new moodle_url('/local/profileeditor/ajax.php'))->out(false),
    'sesskey' => sesskey(),
    'canedit' => $canedit,
    'fields' => $jsfields,
    'filters' => $filters,
    'total' => $total,
    'strings' => [
        'yes' => get_string('yes'),
        'no' => get_string('no'),
        'saved' => get_string('saved', 'local_profileeditor'),
        'bulkconfirm' => get_string('bulkconfirm', 'local_profileeditor', (object) ['field' => '{field}', 'count' => '{count}']),
        'bulknoneselected' => get_string('bulknoneselected', 'local_profileeditor'),
        'bulkdone' => get_string('bulkdone', 'local_profileeditor', (object) ['updated' => '{updated}', 'failed' => '{failed}']),
        'bulkworking' => get_string('bulkworking', 'local_profileeditor'),
        'choose' => get_string('choosedots'),
    ],
];

branding::require_css();
$PAGE->requires->js(new moodle_url('/local/profileeditor/js/editor.js', ['v' => 2026110600]), false);

echo $OUTPUT->header();
echo html_writer::start_div('lpe-brand');
echo branding::header(get_string('pluginname', 'local_profileeditor'), 'editor',
    get_string('editor_subtitle', 'local_profileeditor'));

// Filter form.
$fieldoptions = ['' => get_string('anyfield', 'local_profileeditor')];
foreach ($allfields as $k => $f) {
    $fieldoptions[$k] = $f->name;
}
$perpageoptions = [25 => 25, 50 => 50, 100 => 100, 250 => 250, 500 => 500, 0 => get_string('all', 'local_profileeditor')];

echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out_omit_querystring(),
    'class' => 'lpe-card lpe-filters']);
echo html_writer::start_div('lpe-row');
echo html_writer::div(
    html_writer::label(get_string('search', 'local_profileeditor'), 'lpe-search', true, ['class' => 'lpe-label']) .
    html_writer::empty_tag('input', ['type' => 'text', 'name' => 'search', 'id' => 'lpe-search',
        'value' => $filters['search'], 'class' => 'form-control lpe-search',
        'placeholder' => get_string('searchplaceholder', 'local_profileeditor')]));
echo html_writer::div(
    html_writer::label(get_string('filterby', 'local_profileeditor'), 'lpe-filterfield', true, ['class' => 'lpe-label']) .
    html_writer::select($fieldoptions, 'filterfield', $filters['filterfield'], false,
        ['id' => 'lpe-filterfield', 'class' => 'custom-select form-select']));
echo html_writer::div(
    html_writer::label(get_string('contains', 'local_profileeditor'), 'lpe-filtervalue', true, ['class' => 'lpe-label']) .
    html_writer::empty_tag('input', ['type' => 'text', 'name' => 'filtervalue', 'id' => 'lpe-filtervalue',
        'value' => $filters['filtervalue'], 'class' => 'form-control']));
echo html_writer::div(
    html_writer::label(get_string('perpage', 'local_profileeditor'), 'lpe-perpage', true, ['class' => 'lpe-label']) .
    html_writer::select($perpageoptions, 'perpage', $perpage, false, ['id' => 'lpe-perpage', 'class' => 'custom-select form-select']));
echo html_writer::div(
    html_writer::checkbox('filterempty', 1, $filters['filterempty'], ' ' . get_string('emptyonly', 'local_profileeditor')) .
    html_writer::empty_tag('br') .
    html_writer::checkbox('showsuspended', 1, $filters['showsuspended'], ' ' . get_string('showsuspended', 'local_profileeditor')),
    'lpe-checks');
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sort', 'value' => $filters['sort']]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'dir', 'value' => $filters['dir']]);
echo html_writer::div(
    html_writer::tag('button', get_string('apply', 'local_profileeditor'), ['type' => 'submit', 'class' => 'btn btn-primary']) . ' ' .
    html_writer::link(new moodle_url('/local/profileeditor/index.php'), get_string('reset', 'local_profileeditor'),
        ['class' => 'btn btn-secondary']));
echo html_writer::end_div();
echo html_writer::end_tag('form');

// Update many users with a file: download template -> fill in -> import.
$templatefields = template_builder::get_template_fields();
$defaultkeys = template_builder::default_keys();
echo html_writer::start_tag('details', ['class' => 'lpe-card lpe-template']);
echo html_writer::tag('summary', html_writer::tag('strong', get_string('template_heading', 'local_profileeditor')) .
    html_writer::span(get_string('template_summary', 'local_profileeditor'), 'lpe-muted'));
echo html_writer::start_div('lpe-template-body');
echo html_writer::tag('ol', html_writer::tag('li', get_string('template_step1', 'local_profileeditor')) .
    html_writer::tag('li', get_string('template_step2', 'local_profileeditor')) .
    html_writer::tag('li', get_string('template_step3', 'local_profileeditor',
        (new moodle_url('/local/profileeditor/import.php'))->out())), ['class' => 'lpe-steps']);

echo html_writer::start_tag('form', ['method' => 'post',
    'action' => (new moodle_url('/local/profileeditor/template.php'))->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::tag('div', html_writer::tag('strong', get_string('template_fields', 'local_profileeditor')) . ' ' .
    html_writer::link('#', get_string('template_selectall', 'local_profileeditor'),
        ['id' => 'lpe-tselectall', 'class' => 'small ml-2 ms-2']), ['class' => 'mb-1']);
echo html_writer::start_div('lpe-tfields');
foreach ($templatefields as $k => $f) {
    $attrs = ['type' => 'checkbox', 'name' => 'tfields[]', 'value' => $k, 'class' => 'lpe-tfield'];
    if (in_array($k, $defaultkeys, true)) {
        $attrs['checked'] = 'checked';
    }
    echo html_writer::tag('label', html_writer::empty_tag('input', $attrs) . ' ' . s($f->name) .
        ' ' . html_writer::tag('code', s(template_builder::column_key($f))), ['class' => 'lpe-tfield-label']);
}
echo html_writer::end_div();
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'tsuspended', 'value' => 0]);
echo html_writer::div(html_writer::checkbox('tsuspended', 1, false,
    ' ' . get_string('template_suspended', 'local_profileeditor')), 'small mb-2');
echo html_writer::tag('button', get_string('template_download_xlsx', 'local_profileeditor'),
    ['type' => 'submit', 'name' => 'format', 'value' => 'xlsx', 'class' => 'btn btn-primary']) . ' ';
echo html_writer::tag('button', get_string('template_download_csv', 'local_profileeditor'),
    ['type' => 'submit', 'name' => 'format', 'value' => 'csv', 'class' => 'btn btn-outline-secondary']) . ' ';
if ($canedit) {
    echo html_writer::link(new moodle_url('/local/profileeditor/import.php'),
        get_string('nav_import', 'local_profileeditor'), ['class' => 'btn btn-accent']) . ' ';
}
echo html_writer::link(new moodle_url('/local/profileeditor/guide.php'),
    get_string('template_fullguide', 'local_profileeditor'), ['class' => 'btn btn-link']);
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_tag('details');

// Toolbar.
echo html_writer::div(
    html_writer::tag('strong', get_string('usersfound', 'local_profileeditor', $total), ['class' => 'lpe-count']) .
    html_writer::div(html_writer::link(new moodle_url($baseurl, ['download' => 'csv']),
        get_string('downloadcsv', 'local_profileeditor'), ['class' => 'btn btn-outline-secondary btn-sm'])),
    'lpe-toolbar');

if ($canedit) {
    echo html_writer::div(get_string('clicktoedit', 'local_profileeditor'), 'lpe-hint');

    // Bulk edit bar.
    $bulkfields = ['' => get_string('choosedots')];
    foreach ($fields as $k => $f) {
        if (field_manager::is_editable($f)) {
            $bulkfields[$k] = $f->name;
        }
    }
    echo html_writer::start_div('lpe-bulk');
    echo html_writer::tag('strong', get_string('bulkedit', 'local_profileeditor') . ':');
    echo html_writer::select(['selected' => get_string('bulkscope_selected', 'local_profileeditor'),
        'all' => get_string('bulkscope_all', 'local_profileeditor', $total)], 'lpe-bulkscope', 'selected', false,
        ['id' => 'lpe-bulkscope', 'class' => 'custom-select form-select form-select-sm w-auto']);
    echo html_writer::span(get_string('set', 'local_profileeditor'));
    echo html_writer::select($bulkfields, 'lpe-bulkfield', '', false,
        ['id' => 'lpe-bulkfield', 'class' => 'custom-select form-select form-select-sm w-auto']);
    echo html_writer::span(get_string('to', 'local_profileeditor'));
    echo html_writer::span('', '', ['id' => 'lpe-bulkvalue-wrap']);
    echo html_writer::tag('button', get_string('bulkapply', 'local_profileeditor'),
        ['type' => 'button', 'id' => 'lpe-bulkapply', 'class' => 'btn btn-sm btn-primary']);
    echo html_writer::end_div();
}

// The table.
$sortlink = function(string $key, string $label) use ($baseurl, $filters) {
    $newdir = ($filters['sort'] === $key && $filters['dir'] === 'ASC') ? 'DESC' : 'ASC';
    $arrow = $filters['sort'] === $key ? ($filters['dir'] === 'ASC' ? ' ▲' : ' ▼') : '';
    return html_writer::link(new moodle_url($baseurl, ['sort' => $key, 'dir' => $newdir, 'page' => 0]),
        s($label) . $arrow);
};

echo html_writer::start_div('lpe-root', ['id' => 'lpe-root', 'data-config' => json_encode($jsconfig)]);
echo html_writer::start_div('lpe-tablewrap');
echo html_writer::start_tag('table', ['class' => 'table table-sm table-hover lpe-table']);

echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr', ['class' => 'lpe-cats']);
echo html_writer::tag('th', '', ['colspan' => $canedit ? 2 : 1, 'class' => 'lpe-sticky']);
$cats = [];
foreach ($fields as $f) {
    $last = count($cats) - 1;
    if ($last >= 0 && $cats[$last][0] === $f->category) {
        $cats[$last][1]++;
    } else {
        $cats[] = [$f->category, 1];
    }
}
foreach ($cats as [$cat, $span]) {
    echo html_writer::tag('th', s($cat), ['colspan' => $span]);
}
echo html_writer::end_tag('tr');

echo html_writer::start_tag('tr');
if ($canedit) {
    echo html_writer::tag('th', html_writer::empty_tag('input', ['type' => 'checkbox', 'id' => 'lpe-selectall',
        'title' => get_string('selectall', 'local_profileeditor')]), ['class' => 'lpe-sticky lpe-chk']);
}
echo html_writer::tag('th', $sortlink('username', get_string('user')), ['class' => 'lpe-sticky lpe-user']);
foreach ($fields as $f) {
    $ro = $canedit && !field_manager::is_editable($f);
    echo html_writer::tag('th', $sortlink($f->key, $f->name) .
        html_writer::div(s($f->shortname) . ($ro ? ' · ' . get_string('readonlyhint', 'local_profileeditor') : ''),
            'lpe-short'), ['title' => $f->shortname]);
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');

echo html_writer::start_tag('tbody');
if (!$users) {
    echo html_writer::tag('tr', html_writer::tag('td', get_string('nousers', 'local_profileeditor'),
        ['colspan' => count($fields) + 2, 'class' => 'lpe-empty']));
}
foreach ($users as $u) {
    echo html_writer::start_tag('tr', ['class' => $u->suspended ? 'lpe-suspended' : '', 'data-userid' => $u->id]);
    if ($canedit) {
        echo html_writer::tag('td', html_writer::empty_tag('input', ['type' => 'checkbox',
            'class' => 'lpe-select', 'value' => $u->id]), ['class' => 'lpe-sticky lpe-chk']);
    }
    $profileurl = new moodle_url('/user/profile.php', ['id' => $u->id]);
    $fullname = trim($u->firstname . ' ' . $u->lastname);
    echo html_writer::tag('td', html_writer::link($profileurl, s($fullname !== '' ? $fullname : $u->username),
        ['target' => '_blank', 'class' => 'lpe-name']) . html_writer::div(s($u->username), 'lpe-short'),
        ['class' => 'lpe-sticky lpe-user']);
    foreach ($fields as $f) {
        $raw = $values[$u->id][$f->key] ?? '';
        $editable = $canedit && field_manager::is_editable($f);
        echo html_writer::tag('td', s(field_manager::format_value($f, $raw)), [
            'class' => 'lpe-cell' . ($editable ? ' lpe-editable' : ' lpe-readonly'),
            'data-userid' => $u->id,
            'data-field' => $f->key,
            'data-raw' => field_manager::input_value($f, $raw),
            'tabindex' => $editable ? '0' : null,
        ]);
    }
    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');
echo html_writer::end_div();
echo html_writer::end_div();

if ($limit) {
    echo $OUTPUT->paging_bar($total, $page, $limit, $baseurl);
}

echo html_writer::end_div();
echo $OUTPUT->footer();
