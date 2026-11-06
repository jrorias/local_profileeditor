<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Change log of edits made with the profile field editor.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$userid  = optional_param('userid', 0, PARAM_INT);
$source  = optional_param('source', '', PARAM_ALPHA);
$page    = optional_param('page', 0, PARAM_INT);
$perpage = 100;

admin_externalpage_setup('local_profileeditor_log');
$baseurl = new moodle_url('/local/profileeditor/log.php', array_filter(['userid' => $userid, 'source' => $source]));
$PAGE->set_url($baseurl);
\local_profileeditor\branding::require_css();

$where = ['1 = 1'];
$params = [];
if ($userid) {
    $where[] = 'l.userid = :userid';
    $params['userid'] = $userid;
}
if (in_array($source, ['editor', 'bulk', 'import', 'mobile'], true)) {
    $where[] = 'l.source = :source';
    $params['source'] = $source;
}
$where = implode(' AND ', $where);

$total = $DB->count_records_sql("SELECT COUNT(1) FROM {local_profileeditor_log} l WHERE $where", $params);

$ufields = \core_user\fields::for_name()->get_sql('u', false, 'u_', '', false)->selects;
$cfields = \core_user\fields::for_name()->get_sql('c', false, 'c_', '', false)->selects;
$sql = "SELECT l.*, u.username AS u_username, $ufields, $cfields
          FROM {local_profileeditor_log} l
     LEFT JOIN {user} u ON u.id = l.userid
     LEFT JOIN {user} c ON c.id = l.changedby
         WHERE $where
      ORDER BY l.timecreated DESC, l.id DESC";
$records = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

echo $OUTPUT->header();
echo html_writer::start_div('lpe-brand');
echo \local_profileeditor\branding::header(get_string('changelog', 'local_profileeditor'), 'log',
    get_string('log_subtitle', 'local_profileeditor'));

$links = [];
$sources = ['' => 'log_src_all', 'editor' => 'log_src_editor', 'bulk' => 'log_src_bulk', 'import' => 'log_src_import',
    'mobile' => 'log_src_mobile'];
foreach ($sources as $src => $str) {
    $links[] = html_writer::link(new moodle_url('/local/profileeditor/log.php', array_filter(['userid' => $userid, 'source' => $src])),
        get_string($str, 'local_profileeditor'),
        ['class' => 'btn btn-sm ' . ($src === $source ? 'btn-primary' : 'btn-outline-secondary')]);
}
if ($userid) {
    $links[] = html_writer::link(new moodle_url('/local/profileeditor/log.php', array_filter(['source' => $source])),
        get_string('log_showall', 'local_profileeditor'), ['class' => 'btn btn-link btn-sm']);
}
echo html_writer::div(implode(' ', $links), 'lpe-toolbar-left mb-3');

/**
 * Extract a prefixed user from a log row.
 *
 * @param stdClass $r
 * @param string $prefix
 * @param int $id
 * @return stdClass
 */
function local_profileeditor_rowuser(stdClass $r, string $prefix, int $id): stdClass {
    $u = (object) ['id' => $id];
    foreach (\core_user\fields::get_name_fields() as $name) {
        $u->$name = $r->{$prefix . $name} ?? '';
    }
    return $u;
}

if (!$records) {
    echo $OUTPUT->notification(get_string('log_empty', 'local_profileeditor'), 'info');
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable lpe-table-simple';
    $table->head = [
        get_string('log_time', 'local_profileeditor'),
        get_string('log_user', 'local_profileeditor'),
        get_string('log_field', 'local_profileeditor'),
        get_string('log_old', 'local_profileeditor'),
        get_string('log_new', 'local_profileeditor'),
        get_string('log_by', 'local_profileeditor'),
        get_string('log_source', 'local_profileeditor'),
    ];
    foreach ($records as $r) {
        $target = local_profileeditor_rowuser($r, 'u_', (int) $r->userid);
        $editor = local_profileeditor_rowuser($r, 'c_', (int) $r->changedby);
        $targetname = trim(fullname($target)) ?: ('#' . $r->userid);
        $table->data[] = [
            userdate($r->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
            html_writer::link(new moodle_url('/local/profileeditor/log.php', ['userid' => $r->userid]), s($targetname)) .
                html_writer::div(s($r->u_username ?? ''), 'small text-muted'),
            s($r->fieldlabel),
            s($r->oldvalue),
            html_writer::tag('strong', s($r->newvalue)),
            s(trim(fullname($editor)) ?: ('#' . $r->changedby)),
            html_writer::span(get_string('log_src_' . ($r->source ?: 'editor'), 'local_profileeditor'),
                'lpe-chip lpe-chip-src-' . s($r->source ?: 'editor')),
        ];
    }
    echo html_writer::table($table);
    echo $OUTPUT->paging_bar($total, $page, $perpage, $baseurl);
}

echo html_writer::end_div();
echo $OUTPUT->footer();
