<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Reads, formats and saves user fields (core + all custom profile fields).
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profileeditor;

defined('MOODLE_INTERNAL') || die();

/**
 * Field manager.
 */
class field_manager {

    /** @var string[] Core user table columns offered in the editor. */
    const CORE_FIELDS = ['firstname', 'lastname', 'email', 'idnumber', 'department', 'institution', 'city', 'phone1', 'phone2'];

    /** @var string[] Core fields that must not be empty. */
    const REQUIRED_CORE = ['firstname', 'lastname', 'email'];

    /** @var string[] Core fields that get a suggestion list of existing values. */
    const SUGGEST_CORE = ['department', 'institution', 'city'];

    /** @var array|null Static cache. */
    protected static $fields = null;

    /**
     * Every field: core fields first, then all custom profile fields in category/sort order.
     * New custom profile fields appear here automatically.
     *
     * @return \stdClass[] keyed by field key (core_xxx or pf_ID)
     */
    public static function get_all_fields(): array {
        global $DB;
        if (self::$fields !== null) {
            return self::$fields;
        }
        $fields = [];
        $corecat = get_string('corefields', 'local_profileeditor');
        foreach (self::CORE_FIELDS as $name) {
            $fields['core_' . $name] = (object) [
                'key' => 'core_' . $name,
                'core' => true,
                'id' => 0,
                'shortname' => $name,
                'name' => get_string($name),
                'category' => $corecat,
                'datatype' => $name === 'email' ? 'email' : 'text',
                'options' => [],
                'maxlength' => 0,
            ];
        }
        $sql = "SELECT f.id, f.shortname, f.name, f.datatype, f.param1, f.param2, c.name AS categoryname
                  FROM {user_info_field} f
             LEFT JOIN {user_info_category} c ON c.id = f.categoryid
              ORDER BY c.sortorder, f.sortorder";
        foreach ($DB->get_records_sql($sql) as $f) {
            $options = [];
            if ($f->datatype === 'menu') {
                $options = array_values(array_filter(array_map('trim',
                    preg_split('/\R/', (string) $f->param1)), 'strlen'));
            }
            $fields['pf_' . $f->id] = (object) [
                'key' => 'pf_' . $f->id,
                'core' => false,
                'id' => (int) $f->id,
                'shortname' => $f->shortname,
                'name' => format_string($f->name, true, ['context' => \context_system::instance()]),
                'category' => format_string((string) $f->categoryname, true,
                    ['context' => \context_system::instance()]),
                'datatype' => $f->datatype,
                'options' => $options,
                'maxlength' => ($f->datatype === 'text' && (int) $f->param2 > 0) ? (int) $f->param2 : 0,
            ];
        }
        self::$fields = $fields;
        return $fields;
    }

    /**
     * All fields in the order chosen on the Columns page. Fields that are not in the saved
     * order yet (for example a profile field created later) are added at the end automatically.
     *
     * @return \stdClass[]
     */
    public static function get_ordered_fields(): array {
        $all = self::get_all_fields();
        $ordered = [];
        foreach (self::config_list('columnorder') as $key) {
            if (isset($all[$key])) {
                $ordered[$key] = $all[$key];
            }
        }
        return $ordered + $all;
    }

    /**
     * Fields shown in the editor (all except hidden ones), in the configured order.
     *
     * @return \stdClass[]
     */
    public static function get_visible_fields(): array {
        $hidden = self::config_list('hiddenfields');
        return array_diff_key(self::get_ordered_fields(), array_flip($hidden));
    }

    /**
     * Is a field hidden in the editor?
     *
     * @param \stdClass $field
     * @return bool
     */
    public static function is_hidden(\stdClass $field): bool {
        return in_array($field->key, self::config_list('hiddenfields'));
    }

    /**
     * Is a field editable?
     *
     * @param \stdClass $field
     * @return bool
     */
    public static function is_editable(\stdClass $field): bool {
        return !in_array($field->key, self::config_list('readonlyfields'));
    }

    /**
     * Read a comma separated config list.
     *
     * @param string $name
     * @return string[]
     */
    public static function config_list(string $name): array {
        $value = get_config('local_profileeditor', $name);
        if ($value === false) {
            // Not configured yet: email is read-only by default because it is the sign-in identity.
            return $name === 'readonlyfields' ? ['core_email'] : [];
        }
        return array_filter(explode(',', (string) $value));
    }

    /**
     * Raw values for a set of users.
     *
     * @param \stdClass[] $users user records (must contain the core columns)
     * @param \stdClass[] $fields
     * @return array [userid][fieldkey] => raw value (string)
     */
    public static function get_values(array $users, array $fields): array {
        global $DB;
        $values = [];
        if (!$users) {
            return $values;
        }
        foreach ($users as $u) {
            foreach ($fields as $f) {
                $values[$u->id][$f->key] = $f->core ? (string) ($u->{$f->shortname} ?? '') : '';
            }
        }
        $customids = [];
        foreach ($fields as $f) {
            if (!$f->core) {
                $customids[$f->id] = $f->key;
            }
        }
        if ($customids) {
            [$usql, $uparams] = $DB->get_in_or_equal(array_keys($users), SQL_PARAMS_NAMED, 'u');
            [$fsql, $fparams] = $DB->get_in_or_equal(array_keys($customids), SQL_PARAMS_NAMED, 'f');
            $rs = $DB->get_recordset_sql("SELECT id, userid, fieldid, data
                                            FROM {user_info_data}
                                           WHERE userid $usql AND fieldid $fsql", $uparams + $fparams);
            foreach ($rs as $r) {
                $values[$r->userid][$customids[$r->fieldid]] = (string) $r->data;
            }
            $rs->close();
        }
        return $values;
    }

    /**
     * Human readable value.
     *
     * @param \stdClass $field
     * @param string $raw
     * @return string plain text (not escaped)
     */
    public static function format_value(\stdClass $field, string $raw): string {
        switch ($field->datatype) {
            case 'checkbox':
                return $raw ? get_string('yes') : get_string('no');
            case 'datetime':
                return ((int) $raw) ? userdate((int) $raw, get_string('strftimedate', 'langconfig')) : '';
            case 'textarea':
                return shorten_text(trim(html_to_text($raw, 0, false)), 100);
            default:
                return $raw;
        }
    }

    /**
     * Value as used by the edit input (dates as YYYY-MM-DD).
     *
     * @param \stdClass $field
     * @param string $raw
     * @return string
     */
    public static function input_value(\stdClass $field, string $raw): string {
        if ($field->datatype === 'datetime') {
            if (!(int) $raw) {
                return '';
            }
            $dt = new \DateTime('@' . (int) $raw);
            $dt->setTimezone(\core_date::get_user_timezone_object());
            return $dt->format('Y-m-d');
        }
        if ($field->datatype === 'checkbox') {
            return $raw ? '1' : '0';
        }
        return $raw;
    }

    /**
     * Value for CSV export (compatible with Admin > Upload users).
     *
     * @param \stdClass $field
     * @param string $raw
     * @return string
     */
    public static function export_value(\stdClass $field, string $raw): string {
        if ($field->datatype === 'datetime' || $field->datatype === 'checkbox') {
            return self::input_value($field, $raw);
        }
        return $raw;
    }

    /**
     * Validate and convert submitted input to the stored raw value.
     *
     * @param \stdClass $field
     * @param string $value
     * @param int $userid
     * @return string
     * @throws \moodle_exception
     */
    public static function normalise(\stdClass $field, string $value, int $userid): string {
        global $DB, $CFG;
        $value = trim($value);
        switch ($field->datatype) {
            case 'checkbox':
                return ($value !== '' && $value !== '0') ? '1' : '0';

            case 'datetime':
                if ($value === '') {
                    return '0';
                }
                $dt = \DateTime::createFromFormat('!Y-m-d', $value, \core_date::get_user_timezone_object());
                if (!$dt || $dt->format('Y-m-d') !== $value) {
                    throw new \moodle_exception('error_invaliddate', 'local_profileeditor');
                }
                return (string) $dt->getTimestamp();

            case 'menu':
                if ($value !== '' && !in_array($value, $field->options, true)) {
                    throw new \moodle_exception('error_invalidoption', 'local_profileeditor');
                }
                return $value;

            case 'textarea':
                return clean_text($value, FORMAT_HTML);

            case 'email':
                if ($value === '') {
                    throw new \moodle_exception('error_required', 'local_profileeditor');
                }
                if (!validate_email($value)) {
                    throw new \moodle_exception('error_invalidemail', 'local_profileeditor');
                }
                if (empty($CFG->allowaccountssameemail)) {
                    $select = $DB->sql_equal('email', ':email', false) .
                        ' AND id <> :id AND deleted = 0 AND mnethostid = :mnet';
                    if ($DB->record_exists_select('user', $select,
                            ['email' => $value, 'id' => $userid, 'mnet' => $CFG->mnet_localhost_id])) {
                        throw new \moodle_exception('error_duplicateemail', 'local_profileeditor');
                    }
                }
                return $value;

            default:
                $value = clean_param($value, PARAM_TEXT);
                if ($field->core && in_array($field->shortname, self::REQUIRED_CORE) && $value === '') {
                    throw new \moodle_exception('error_required', 'local_profileeditor');
                }
                $max = $field->maxlength ?: ($field->core ? 255 : 0);
                if ($max && \core_text::strlen($value) > $max) {
                    throw new \moodle_exception('error_toolong', 'local_profileeditor', '', $max);
                }
                return $value;
        }
    }

    /**
     * Save one field for one user.
     *
     * @param int $userid
     * @param \stdClass $field
     * @param string $value submitted value
     * @param string $source editor, bulk or import
     * @return string new raw value
     * @throws \moodle_exception
     */
    public static function save_value(int $userid, \stdClass $field, string $value, string $source = 'editor'): string {
        $result = self::save_values($userid, [$field->key => $value], $source);
        if (isset($result['errors'][$field->key])) {
            throw new \moodle_exception('error_generic', 'local_profileeditor', '', $result['errors'][$field->key]);
        }
        return $result['saved'][$field->key];
    }

    /**
     * Save several fields for one user in one go: one user update, one user_updated event,
     * one log row per changed field. Fields with invalid values are skipped and reported.
     *
     * @param int $userid
     * @param array $values field key => submitted value
     * @param string $source editor, bulk or import
     * @return array ['saved' => [key => raw], 'changed' => [key => bool], 'errors' => [key => message]]
     * @throws \moodle_exception when the user itself cannot be edited
     */
    public static function save_values(int $userid, array $values, string $source = 'editor'): array {
        global $DB, $USER, $CFG;

        $user = \core_user::get_user($userid, '*', MUST_EXIST);
        if ($user->deleted || isguestuser($user)) {
            throw new \moodle_exception('invaliduser');
        }
        if (is_siteadmin($user) && !is_siteadmin()) {
            throw new \moodle_exception('error_siteadmin', 'local_profileeditor');
        }

        $fields = self::get_all_fields();
        $saved = [];
        $changed = [];
        $errors = [];
        $core = [];
        $custom = [];
        $logs = [];
        $now = time();

        foreach ($values as $key => $value) {
            $field = $fields[$key] ?? null;
            if (!$field || !self::is_editable($field)) {
                $errors[$key] = get_string('error_invalidfield', 'local_profileeditor');
                continue;
            }
            try {
                $new = self::normalise($field, (string) $value, $userid);
            } catch (\moodle_exception $e) {
                $errors[$key] = $e->getMessage();
                continue;
            }
            if ($field->core) {
                $old = (string) $user->{$field->shortname};
            } else {
                $rec = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $field->id]);
                $old = $rec ? (string) $rec->data : '';
            }
            $saved[$key] = $new;
            $changed[$key] = ($old !== $new);
            if ($old === $new) {
                continue;
            }
            if ($field->core) {
                $core[$field->shortname] = $new;
            } else {
                $custom[] = [$field, $rec, $new];
            }
            $logs[] = (object) [
                'userid' => $userid,
                'fieldkey' => $field->key,
                'fieldlabel' => \core_text::substr($field->name . ' (' . $field->shortname . ')', 0, 255),
                'oldvalue' => self::format_value($field, $old),
                'newvalue' => self::format_value($field, $new),
                'changedby' => $USER->id,
                'source' => $source,
                'timecreated' => $now,
            ];
        }

        if (!$logs) {
            return ['saved' => $saved, 'changed' => $changed, 'errors' => $errors];
        }

        $transaction = $DB->start_delegated_transaction();
        foreach ($custom as [$field, $rec, $new]) {
            if ($rec) {
                $rec->data = $new;
                $DB->update_record('user_info_data', $rec);
            } else {
                $DB->insert_record('user_info_data', (object) [
                    'userid' => $userid,
                    'fieldid' => $field->id,
                    'data' => $new,
                    'dataformat' => $field->datatype === 'textarea' ? FORMAT_HTML : 0,
                ]);
            }
        }
        if ($core) {
            require_once($CFG->dirroot . '/user/lib.php');
            // Fires the user_updated event itself.
            user_update_user((object) (['id' => $userid] + $core), false, true);
        } else {
            \core\event\user_updated::create_from_userid($userid)->trigger();
        }
        $DB->insert_records('local_profileeditor_log', $logs);
        $transaction->allow_commit();

        return ['saved' => $saved, 'changed' => $changed, 'errors' => $errors];
    }

    /**
     * Existing distinct values for text fields, used as type-ahead suggestions so names stay consistent.
     *
     * @param \stdClass $field
     * @return string[]
     */
    public static function get_suggestions(\stdClass $field): array {
        global $DB;
        if ($field->core) {
            if (!in_array($field->shortname, self::SUGGEST_CORE)) {
                return [];
            }
            $col = $field->shortname; // Whitelisted above.
            $values = $DB->get_fieldset_sql("SELECT DISTINCT $col FROM {user}
                                              WHERE deleted = 0 AND $col <> '' ORDER BY $col");
        } else if ($field->datatype === 'text') {
            $values = $DB->get_fieldset_sql("SELECT DISTINCT data FROM {user_info_data}
                                              WHERE fieldid = ? AND data <> '' ORDER BY data", [$field->id]);
        } else {
            return [];
        }
        return array_slice(array_values(array_unique(array_map('strval', $values))), 0, 500);
    }
}
