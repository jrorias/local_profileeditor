<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Builds the user list query from the editor filters (shared by the page, CSV download and bulk edit).
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profileeditor;

defined('MOODLE_INTERNAL') || die();

/**
 * User query.
 */
class user_query {

    /**
     * Read the filters from the request.
     *
     * @return array
     */
    public static function filters_from_request(): array {
        return [
            'search' => optional_param('search', '', PARAM_RAW_TRIMMED),
            'filterfield' => optional_param('filterfield', '', PARAM_ALPHANUMEXT),
            'filtervalue' => optional_param('filtervalue', '', PARAM_RAW_TRIMMED),
            'filterempty' => optional_param('filterempty', 0, PARAM_BOOL),
            'showsuspended' => optional_param('showsuspended', 0, PARAM_BOOL),
            'sort' => optional_param('sort', 'core_lastname', PARAM_ALPHANUMEXT),
            'dir' => optional_param('dir', 'ASC', PARAM_ALPHA) === 'DESC' ? 'DESC' : 'ASC',
        ];
    }

    /**
     * Build WHERE / ORDER BY.
     *
     * @param array $f filters
     * @return array [wheresql, params, orderby]
     */
    public static function build(array $f): array {
        global $DB, $CFG;
        $allfields = field_manager::get_all_fields();

        $where  = ['u.deleted = 0', 'u.id <> :guestid'];
        $params = ['guestid' => $CFG->siteguest];

        if (empty($f['showsuspended'])) {
            $where[] = 'u.suspended = 0';
        }

        $search = (string) ($f['search'] ?? '');
        if ($search !== '') {
            $conds = [];
            $i = 0;
            $cols = ['u.firstname', 'u.lastname', 'u.email', 'u.username', 'u.idnumber',
                $DB->sql_concat('u.firstname', "' '", 'u.lastname')];
            foreach ($cols as $col) {
                $p = 'srch' . $i++;
                $conds[] = $DB->sql_like($col, ':' . $p, false, false);
                $params[$p] = '%' . $DB->sql_like_escape($search) . '%';
            }
            $p = 'srch' . $i;
            $conds[] = "EXISTS (SELECT 1 FROM {user_info_data} sd
                                 WHERE sd.userid = u.id AND " . $DB->sql_like('sd.data', ':' . $p, false, false) . ")";
            $params[$p] = '%' . $DB->sql_like_escape($search) . '%';
            $where[] = '(' . implode(' OR ', $conds) . ')';
        }

        $filterfield = (string) ($f['filterfield'] ?? '');
        $filtervalue = (string) ($f['filtervalue'] ?? '');
        if ($filterfield !== '' && isset($allfields[$filterfield])) {
            $ff = $allfields[$filterfield];
            if (!empty($f['filterempty'])) {
                if ($ff->core) {
                    $where[] = "(u.{$ff->shortname} IS NULL OR u.{$ff->shortname} = '')";
                } else {
                    $zero = in_array($ff->datatype, ['checkbox', 'datetime']) ? " AND fd.data <> '0'" : '';
                    $where[] = "NOT EXISTS (SELECT 1 FROM {user_info_data} fd
                                             WHERE fd.userid = u.id AND fd.fieldid = :ffid
                                               AND fd.data IS NOT NULL AND fd.data <> ''$zero)";
                    $params['ffid'] = $ff->id;
                }
            } else if ($filtervalue !== '') {
                $params['ffval'] = '%' . $DB->sql_like_escape($filtervalue) . '%';
                if ($ff->core) {
                    $where[] = $DB->sql_like('u.' . $ff->shortname, ':ffval', false, false);
                } else {
                    $where[] = "EXISTS (SELECT 1 FROM {user_info_data} fd
                                         WHERE fd.userid = u.id AND fd.fieldid = :ffid
                                           AND " . $DB->sql_like('fd.data', ':ffval', false, false) . ")";
                    $params['ffid'] = $ff->id;
                }
            }
        }

        $sort = (string) ($f['sort'] ?? '');
        $dir = ($f['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        if ($sort === 'username') {
            $orderby = "u.username $dir";
        } else if (isset($allfields[$sort]) && $allfields[$sort]->core) {
            $orderby = "u.{$allfields[$sort]->shortname} $dir, u.id";
        } else if (isset($allfields[$sort])) {
            $orderby = "(SELECT so.data FROM {user_info_data} so
                          WHERE so.userid = u.id AND so.fieldid = :sortfid) $dir, u.id";
            $params['sortfid'] = $allfields[$sort]->id;
        } else {
            $orderby = 'u.lastname ASC, u.firstname ASC';
        }

        return [implode(' AND ', $where), $params, $orderby];
    }

    /**
     * User columns to select.
     *
     * @return string
     */
    public static function user_columns(): string {
        return 'u.id, u.username, u.suspended, u.auth, u.' . implode(', u.', field_manager::CORE_FIELDS);
    }

    /**
     * User columns without table alias.
     *
     * @return string
     */
    public static function user_columns_plain(): string {
        return 'id, username, suspended, auth, ' . implode(', ', field_manager::CORE_FIELDS);
    }
}
