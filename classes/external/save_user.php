<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Save several profile fields of one user (mobile app).
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profileeditor\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use local_profileeditor\field_manager;

/**
 * save_user.
 */
class save_user extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User id'),
            'values' => new external_value(PARAM_RAW, 'JSON object: field key => value'),
        ]);
    }

    /**
     * Save.
     *
     * @param int $userid
     * @param string $values
     * @return array
     */
    public static function execute($userid, $values) {
        $p = self::validate_parameters(self::execute_parameters(), ['userid' => $userid, 'values' => $values]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/profileeditor:edit', $context);

        $data = json_decode($p['values'], true);
        if (!is_array($data)) {
            throw new \invalid_parameter_exception('values must be a JSON object');
        }
        // Only editable fields; others are silently ignored (they are shown read-only in the app).
        $fields = field_manager::get_all_fields();
        $save = [];
        foreach ($data as $key => $value) {
            if (isset($fields[$key]) && field_manager::is_editable($fields[$key]) && !is_array($value)) {
                $save[$key] = $value === null ? '' : (is_bool($value) ? ($value ? '1' : '0') : (string) $value);
            }
        }
        $result = field_manager::save_values($p['userid'], $save, 'mobile');
        if ($result['errors']) {
            $msgs = [];
            foreach ($result['errors'] as $key => $msg) {
                $msgs[] = ($fields[$key]->name ?? $key) . ': ' . $msg;
            }
            throw new \moodle_exception('mobile_saveproblems', 'local_profileeditor', '', implode(' | ', $msgs));
        }
        return ['changed' => count(array_filter($result['changed']))];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'changed' => new external_value(PARAM_INT, 'Number of values changed'),
        ]);
    }
}
