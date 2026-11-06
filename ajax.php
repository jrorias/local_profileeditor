<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AJAX endpoint: save one cell, apply one value to selected users, or to every user matching the filter.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../config.php');

use local_profileeditor\field_manager;
use local_profileeditor\user_query;

require_login(null, false);
require_sesskey();
$context = context_system::instance();
require_capability('local/profileeditor:edit', $context);

header('Content-Type: application/json; charset=utf-8');

$action = required_param('action', PARAM_ALPHA);
$key    = required_param('field', PARAM_ALPHANUMEXT);
$value  = optional_param('value', '', PARAM_RAW);

$fields = field_manager::get_all_fields();
if (!isset($fields[$key]) || !field_manager::is_editable($fields[$key])) {
    echo json_encode(['success' => false, 'error' => get_string('error_invalidfield', 'local_profileeditor')]);
    die;
}
$field = $fields[$key];

$result = function(int $userid, string $raw) use ($field) {
    return [
        'userid' => $userid,
        'raw' => field_manager::input_value($field, $raw),
        'display' => field_manager::format_value($field, $raw),
    ];
};

$applytomany = function(array $userids) use ($field, $value, $result) {
    core_php_time_limit::raise(600);
    $updated = [];
    $errors = [];
    foreach (array_unique(array_filter($userids)) as $userid) {
        try {
            $raw = field_manager::save_value((int) $userid, $field, $value, 'bulk');
            $updated[] = $result((int) $userid, $raw);
        } catch (moodle_exception $e) {
            $errors[] = ['userid' => (int) $userid, 'error' => $e->getMessage()];
        }
    }
    return ['success' => true, 'updated' => $updated, 'errors' => $errors];
};

if ($action === 'save') {
    $userid = required_param('userid', PARAM_INT);
    try {
        $raw = field_manager::save_value($userid, $field, $value, 'editor');
        echo json_encode(['success' => true] + $result($userid, $raw));
    } catch (moodle_exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    die;
}

if ($action === 'bulk') {
    echo json_encode($applytomany(optional_param_array('userids', [], PARAM_INT)));
    die;
}

if ($action === 'bulkall') {
    // Every user matching the current filters (all pages).
    [$wheresql, $params] = user_query::build(user_query::filters_from_request());
    $ids = $DB->get_fieldset_sql("SELECT u.id FROM {user} u WHERE $wheresql", $params);
    $out = $applytomany($ids);
    // The page reloads after this, so only send the counts.
    $out['updated'] = array_slice($out['updated'], 0, 0);
    $out['count'] = count($ids);
    $out['updatedcount'] = count($ids) - count($out['errors']);
    echo json_encode($out);
    die;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
