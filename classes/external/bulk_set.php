<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Set one field for every user matching a filter (mobile app).
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
use local_profileeditor\user_query;

/**
 * bulk_set.
 */
class bulk_set extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'field' => new external_value(PARAM_ALPHANUMEXT, 'Field key to set'),
            'value' => new external_value(PARAM_RAW, 'New value', VALUE_DEFAULT, ''),
            'search' => new external_value(PARAM_RAW_TRIMMED, 'Search text', VALUE_DEFAULT, ''),
            'filterfield' => new external_value(PARAM_ALPHANUMEXT, 'Filter field key', VALUE_DEFAULT, ''),
            'filtervalue' => new external_value(PARAM_RAW_TRIMMED, 'Filter field contains', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Apply.
     *
     * @param string $field
     * @param string $value
     * @param string $search
     * @param string $filterfield
     * @param string $filtervalue
     * @return array
     */
    public static function execute($field, $value = '', $search = '', $filterfield = '', $filtervalue = '') {
        global $DB;
        $p = self::validate_parameters(self::execute_parameters(), compact('field', 'value', 'search',
            'filterfield', 'filtervalue'));
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/profileeditor:edit', $context);

        $fields = field_manager::get_all_fields();
        if (!isset($fields[$p['field']]) || !field_manager::is_editable($fields[$p['field']])) {
            throw new \moodle_exception('error_invalidfield', 'local_profileeditor');
        }
        if ($p['search'] === '' && ($p['filterfield'] === '' || $p['filtervalue'] === '')) {
            // Never update every user by accident from a phone.
            throw new \moodle_exception('mobile_bulknofilter', 'local_profileeditor');
        }
        \core_php_time_limit::raise(600);
        [$where, $params] = user_query::build(['search' => $p['search'], 'filterfield' => $p['filterfield'],
            'filtervalue' => $p['filtervalue'], 'filterempty' => 0, 'showsuspended' => 0, 'sort' => '', 'dir' => 'ASC']);
        $ids = $DB->get_fieldset_sql("SELECT u.id FROM {user} u WHERE $where", $params);
        $updated = 0;
        $failed = 0;
        foreach ($ids as $id) {
            try {
                field_manager::save_value((int) $id, $fields[$p['field']], $p['value'], 'bulk');
                $updated++;
            } catch (\moodle_exception $e) {
                $failed++;
            }
        }
        return ['updated' => $updated, 'failed' => $failed];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'updated' => new external_value(PARAM_INT, 'Users updated'),
            'failed' => new external_value(PARAM_INT, 'Users that failed'),
        ]);
    }
}
