<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Columns: arrange the order of the editor columns, show/hide them and make them read-only.
 * Profile fields created later are added at the end automatically (shown and editable).
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_profileeditor\branding;
use local_profileeditor\field_manager;

admin_externalpage_setup('local_profileeditor_columns');
$pageurl = new moodle_url('/local/profileeditor/columns.php');
$PAGE->set_url($pageurl);

$all = field_manager::get_all_fields();

if (optional_param('save', 0, PARAM_BOOL) && confirm_sesskey()) {
    $order = array_values(array_filter(optional_param_array('order', [], PARAM_ALPHANUMEXT), function($k) use ($all) {
        return isset($all[$k]);
    }));
    $show = optional_param_array('show', [], PARAM_ALPHANUMEXT);
    $edit = optional_param_array('edit', [], PARAM_ALPHANUMEXT);
    $hidden = array_values(array_diff(array_keys($all), $show));
    $readonly = array_values(array_diff(array_keys($all), $edit));
    set_config('columnorder', implode(',', $order), 'local_profileeditor');
    set_config('hiddenfields', implode(',', $hidden), 'local_profileeditor');
    set_config('readonlyfields', implode(',', $readonly), 'local_profileeditor');
    redirect($pageurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}
if (optional_param('resetorder', 0, PARAM_BOOL) && confirm_sesskey()) {
    unset_config('columnorder', 'local_profileeditor');
    redirect($pageurl, get_string('columns_orderreset', 'local_profileeditor'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$hidden = field_manager::config_list('hiddenfields');

branding::require_css();
$PAGE->requires->js(new moodle_url('/local/profileeditor/js/columns.js', ['v' => 2026110600]), false);

echo $OUTPUT->header();
echo html_writer::start_div('lpe-brand');
echo branding::header(get_string('nav_columns', 'local_profileeditor'), 'columns',
    get_string('columns_subtitle', 'local_profileeditor'));

echo html_writer::div(get_string('columns_intro', 'local_profileeditor',
    (new moodle_url('/user/profile/index.php'))->out()), 'lpe-hint');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl->out(false), 'id' => 'lpe-columns']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::start_tag('table', ['class' => 'generaltable lpe-table-simple lpe-coltable']);
echo html_writer::tag('thead', html_writer::tag('tr',
    html_writer::tag('th', '', ['class' => 'lpe-drag-col']) .
    html_writer::tag('th', get_string('columns_field', 'local_profileeditor')) .
    html_writer::tag('th', get_string('columns_category', 'local_profileeditor')) .
    html_writer::tag('th', get_string('columns_type', 'local_profileeditor')) .
    html_writer::tag('th', get_string('columns_show', 'local_profileeditor') .
        html_writer::div(html_writer::link('#', get_string('all', 'local_profileeditor'), ['data-toggleall' => 'show']),
            'small')) .
    html_writer::tag('th', get_string('columns_editable', 'local_profileeditor') .
        html_writer::div(html_writer::link('#', get_string('all', 'local_profileeditor'), ['data-toggleall' => 'edit']),
            'small')) .
    html_writer::tag('th', get_string('columns_move', 'local_profileeditor'))
));
echo html_writer::start_tag('tbody');
foreach (field_manager::get_ordered_fields() as $k => $f) {
    $showattrs = ['type' => 'checkbox', 'name' => 'show[]', 'value' => $k, 'class' => 'lpe-c-show'];
    if (!in_array($k, $hidden)) {
        $showattrs['checked'] = 'checked';
    }
    $editattrs = ['type' => 'checkbox', 'name' => 'edit[]', 'value' => $k, 'class' => 'lpe-c-edit'];
    if (field_manager::is_editable($f)) {
        $editattrs['checked'] = 'checked';
    }
    $type = $f->core ? get_string('columns_type_core', 'local_profileeditor') : s($f->datatype);
    echo html_writer::tag('tr',
        html_writer::tag('td', '⋮⋮', ['class' => 'lpe-drag', 'title' => get_string('columns_drag', 'local_profileeditor')]) .
        html_writer::tag('td', html_writer::tag('strong', s($f->name)) . html_writer::div(s($f->shortname), 'lpe-short') .
            html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'order[]', 'value' => $k])) .
        html_writer::tag('td', s($f->category)) .
        html_writer::tag('td', html_writer::span($type, 'lpe-chip')) .
        html_writer::tag('td', html_writer::empty_tag('input', $showattrs)) .
        html_writer::tag('td', html_writer::empty_tag('input', $editattrs)) .
        html_writer::tag('td',
            html_writer::tag('button', '▲', ['type' => 'button', 'class' => 'btn btn-sm btn-light lpe-up',
                'aria-label' => get_string('moveup')]) . ' ' .
            html_writer::tag('button', '▼', ['type' => 'button', 'class' => 'btn btn-sm btn-light lpe-down',
                'aria-label' => get_string('movedown')])),
        ['draggable' => 'true', 'data-key' => $k]);
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo html_writer::div(
    html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'name' => 'save', 'value' => 1,
        'class' => 'btn btn-primary']) . ' ' .
    html_writer::tag('button', get_string('columns_resetorder', 'local_profileeditor'),
        ['type' => 'submit', 'name' => 'resetorder', 'value' => 1, 'class' => 'btn btn-outline-secondary']),
    'lpe-actions');
echo html_writer::end_tag('form');

echo html_writer::end_div();
echo $OUTPUT->footer();
