<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Template & guide: how to update many users at once with a file.
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

admin_externalpage_setup('local_profileeditor_guide');
$PAGE->set_url(new moodle_url('/local/profileeditor/guide.php'));
branding::require_css();

$fields = template_builder::get_template_fields();
$domains = template_builder::get_domains();
$excluded = template_builder::get_excluded();
$usercount = count(template_builder::get_users(false));
$uploadurl = new moodle_url('/admin/tool/uploaduser/index.php');
$canedit = has_capability('local/profileeditor:edit', context_system::instance());

echo $OUTPUT->header();
echo html_writer::start_div('lpe-brand');
echo branding::header(get_string('guide', 'local_profileeditor'), 'guide', get_string('guide_subtitle', 'local_profileeditor'));

// 1. Download.
echo html_writer::start_div('lpe-card');
echo html_writer::tag('h4', get_string('guide_download_heading', 'local_profileeditor'));
echo html_writer::tag('p', get_string('guide_download_desc', 'local_profileeditor', $usercount));
if ($domains) {
    echo html_writer::tag('p', get_string('guide_domains', 'local_profileeditor',
        s(implode(', ', array_map(function($d) {
            return '@' . $d;
        }, $domains)))), ['class' => 'small']);
}
if ($excluded) {
    echo html_writer::tag('p', get_string('guide_excludedcount', 'local_profileeditor', count($excluded)), ['class' => 'small']);
}
echo html_writer::start_tag('form', ['method' => 'post',
    'action' => (new moodle_url('/local/profileeditor/template.php'))->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::tag('button', get_string('template_download_xlsx', 'local_profileeditor'),
    ['type' => 'submit', 'name' => 'format', 'value' => 'xlsx', 'class' => 'btn btn-primary btn-lg']) . ' ';
echo html_writer::tag('button', get_string('template_download_csv', 'local_profileeditor'),
    ['type' => 'submit', 'name' => 'format', 'value' => 'csv', 'class' => 'btn btn-outline-secondary']) . ' ';
echo html_writer::link(new moodle_url('/local/profileeditor/index.php'),
    get_string('template_choosefields', 'local_profileeditor'), ['class' => 'btn btn-link']);
echo html_writer::end_tag('form');
echo html_writer::end_div();

// 2. Columns.
echo html_writer::tag('h4', get_string('guide_columns', 'local_profileeditor'));
$table = new html_table();
$table->attributes['class'] = 'generaltable lpe-table-simple';
$table->head = [get_string('guide_col_column', 'local_profileeditor'),
    get_string('guide_col_field', 'local_profileeditor'),
    get_string('guide_col_format', 'local_profileeditor')];
$table->data[] = [html_writer::tag('code', 'username'), get_string('username'),
    get_string('guide_fmt_username', 'local_profileeditor')];
$table->data[] = [html_writer::tag('code', 'email'), get_string('email'),
    get_string('guide_fmt_email', 'local_profileeditor')];
foreach ($fields as $f) {
    $table->data[] = [html_writer::tag('code', s(template_builder::column_key($f))), s($f->name),
        s(template_builder::format_hint($f, template_builder::column_key($f), !field_manager::is_editable($f)))];
}
echo html_writer::div(html_writer::table($table), 'lpe-scroll');

// 3. Steps (recommended: import here).
echo html_writer::start_div('lpe-grid');
echo html_writer::start_div('lpe-card');
echo html_writer::tag('h4', get_string('guide_steps', 'local_profileeditor'));
$steps = '';
for ($n = 1; $n <= 5; $n++) {
    $steps .= html_writer::tag('li', get_string('guide_step' . $n, 'local_profileeditor',
        (new moodle_url('/local/profileeditor/import.php'))->out()), ['class' => 'mb-2']);
}
echo html_writer::tag('ol', $steps, ['class' => 'lpe-steps']);
if ($canedit) {
    echo html_writer::link(new moodle_url('/local/profileeditor/import.php'), get_string('nav_import', 'local_profileeditor'),
        ['class' => 'btn btn-accent']);
}
echo html_writer::end_div();

// Alternative: the LMS Upload users tool with the CSV.
echo html_writer::start_div('lpe-card');
echo html_writer::tag('h4', get_string('guide_alt', 'local_profileeditor'));
echo html_writer::tag('p', get_string('guide_alt_desc', 'local_profileeditor', $uploadurl->out()));
$settings = new html_table();
$settings->attributes['class'] = 'generaltable lpe-table-simple';
$settings->head = [get_string('guide_setting', 'local_profileeditor'), get_string('guide_value', 'local_profileeditor')];
foreach (['uutype', 'uuupdatetype', 'uupasswordold', 'uuallowrenames', 'uustandardusernames', 'uubulk'] as $key) {
    $settings->data[] = [get_string('guide_set_' . $key, 'local_profileeditor'),
        html_writer::tag('strong', get_string('guide_val_' . $key, 'local_profileeditor'))];
}
echo html_writer::table($settings);
echo html_writer::end_div();
echo html_writer::end_div();

// Cautions.
echo html_writer::start_div('lpe-card lpe-warn');
echo html_writer::tag('h4', get_string('guide_cautions', 'local_profileeditor'));
$cautions = '';
for ($n = 1; $n <= 5; $n++) {
    $cautions .= html_writer::tag('li', get_string('guide_caution' . $n, 'local_profileeditor'));
}
echo html_writer::tag('ul', $cautions, ['class' => 'mb-0']);
echo html_writer::end_div();

echo html_writer::end_div();
echo $OUTPUT->footer();
