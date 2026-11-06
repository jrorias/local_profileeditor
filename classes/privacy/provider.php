<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Privacy provider.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profileeditor\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for the change log.
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Metadata.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_profileeditor_log', [
            'userid' => 'privacy:metadata:log:userid',
            'fieldlabel' => 'privacy:metadata:log:fieldlabel',
            'oldvalue' => 'privacy:metadata:log:oldvalue',
            'newvalue' => 'privacy:metadata:log:newvalue',
            'changedby' => 'privacy:metadata:log:changedby',
            'timecreated' => 'privacy:metadata:log:timecreated',
        ], 'privacy:metadata:log');
        return $collection;
    }

    /**
     * Contexts with data for a user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $list = new contextlist();
        if ($DB->record_exists_select('local_profileeditor_log', 'userid = ? OR changedby = ?', [$userid, $userid])) {
            $list->add_system_context();
        }
        return $list;
    }

    /**
     * Users with data in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        if (!$userlist->get_context() instanceof \context_system) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT userid FROM {local_profileeditor_log}', []);
        $userlist->add_from_sql('changedby', 'SELECT changedby FROM {local_profileeditor_log}', []);
    }

    /**
     * Export data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_system) {
                continue;
            }
            $rows = $DB->get_records_select('local_profileeditor_log', 'userid = ? OR changedby = ?',
                [$userid, $userid], 'timecreated ASC');
            $data = [];
            foreach ($rows as $r) {
                $data[] = (object) [
                    'field' => $r->fieldlabel,
                    'oldvalue' => $r->oldvalue,
                    'newvalue' => $r->newvalue,
                    'aboutyou' => (int) $r->userid === (int) $userid,
                    'madebyyou' => (int) $r->changedby === (int) $userid,
                    'time' => \core_privacy\local\request\transform::datetime($r->timecreated),
                ];
            }
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_profileeditor')], (object) ['changes' => $data]);
        }
    }

    /**
     * Delete all data in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context instanceof \context_system) {
            $DB->delete_records('local_profileeditor_log');
        }
    }

    /**
     * Delete data for one user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_system) {
                $DB->delete_records('local_profileeditor_log', ['userid' => $userid]);
                $DB->set_field('local_profileeditor_log', 'changedby', 0, ['changedby' => $userid]);
            }
        }
    }

    /**
     * Delete data for several users.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        if (!$userlist->get_context() instanceof \context_system || !$userlist->get_userids()) {
            return;
        }
        [$sql, $params] = $DB->get_in_or_equal($userlist->get_userids());
        $DB->delete_records_select('local_profileeditor_log', "userid $sql", $params);
        $DB->set_field_select('local_profileeditor_log', 'changedby', 0, "changedby $sql", $params);
    }
}
