<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Upgrade steps.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_profileeditor_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026110600) {
        // Change source column on the log (editor / bulk / import).
        $table = new xmldb_table('local_profileeditor_log');
        $field = new xmldb_field('source', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'editor', 'changedby');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Version 1.x shipped with built-in, client-specific template domains and excluded accounts.
        // If a site still has those untouched built-in values, clear them so the plugin starts clean.
        // (Compared by fingerprint so no client data is kept in this code.)
        $fingerprints = [
            'templatedomains' => '84e1fca95f7d8566b6a2ab8338f238bc',
            'templateexcluded' => '20da7e1e55ef13b1c59c66e54e446037',
        ];
        foreach ($fingerprints as $name => $hash) {
            $value = get_config('local_profileeditor', $name);
            if ($value === false || local_profileeditor_upgrade_fingerprint((string) $value) === $hash) {
                set_config($name, '', 'local_profileeditor');
            }
        }

        upgrade_plugin_savepoint(true, 2026110600, 'local', 'profileeditor');
    }

    return true;
}

/**
 * Normalised fingerprint of a list setting.
 *
 * @param string $value
 * @return string
 */
function local_profileeditor_upgrade_fingerprint(string $value): string {
    $items = preg_split('/[\s,;]+/', core_text::strtolower($value));
    $items = array_map(function($x) {
        return ltrim(trim($x), '@');
    }, $items);
    $items = array_values(array_unique(array_filter($items, 'strlen')));
    sort($items);
    return md5(implode("\n", $items));
}
