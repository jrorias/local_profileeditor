<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Import profile updates from the Excel template or a CSV: read, preview the changes, apply.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profileeditor;

defined('MOODLE_INTERNAL') || die();

/**
 * Importer.
 */
class importer {

    /** @var int Plans older than this are discarded. */
    const PLAN_TTL = 86400;

    /**
     * Read a .xlsx or .csv file into rows of strings.
     *
     * @param string $path
     * @param string $filename original name (for the extension)
     * @return array
     * @throws \moodle_exception
     */
    public static function read_file(string $path, string $filename): array {
        $ext = \core_text::strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext === 'xlsx') {
            return self::read_xlsx($path);
        }
        if ($ext === 'csv' || $ext === 'txt') {
            return self::read_csv(file_get_contents($path));
        }
        throw new \moodle_exception('import_badtype', 'local_profileeditor');
    }

    /**
     * Read the first sheet of an Excel file.
     *
     * @param string $path
     * @return array
     */
    public static function read_xlsx(string $path): array {
        global $CFG;
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            require_once($CFG->libdir . '/excellib.class.php');
        }
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $ss = $reader->load($path);
        } catch (\Throwable $e) {
            throw new \moodle_exception('import_unreadable', 'local_profileeditor', '', $e->getMessage());
        }
        $sheet = $ss->getSheet(0);
        $rows = [];
        foreach ($sheet->toArray(null, true, false, false) as $row) {
            $rows[] = array_map([self::class, 'cell_to_string'], $row);
        }
        return $rows;
    }

    /**
     * Convert a spreadsheet cell value to a string without scientific notation.
     *
     * @param mixed $v
     * @return string
     */
    public static function cell_to_string($v): string {
        if ($v === null) {
            return '';
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        if (is_float($v)) {
            if (floor($v) == $v && abs($v) < 1e15) {
                return sprintf('%.0f', $v);
            }
            return rtrim(rtrim(sprintf('%.10F', $v), '0'), '.');
        }
        return trim((string) $v);
    }

    /**
     * Parse CSV text (comma, semicolon or tab; UTF-8 or Windows-1252).
     *
     * @param string $content
     * @return array
     */
    public static function read_csv(string $content): array {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        $firstline = strtok($content, "\n");
        $delims = [',' => substr_count($firstline, ','), ';' => substr_count($firstline, ';'),
            "\t" => substr_count($firstline, "\t")];
        arsort($delims);
        $delim = key($delims);

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $content);
        rewind($fh);
        $rows = [];
        while (($row = fgetcsv($fh, 0, $delim, '"', '\\')) !== false) {
            if ($row === [null]) {
                continue;
            }
            $rows[] = array_map(function($v) {
                return trim((string) $v);
            }, $row);
        }
        fclose($fh);
        return $rows;
    }

    /**
     * Work out which column is which. Accepts column keys (username, email, department,
     * profile_field_xxx), plain shortnames and the field names shown in the editor.
     *
     * @param array $row header row
     * @param \stdClass[] $fields all fields
     * @return array col index => 'username' | 'email' | field key | null
     */
    public static function map_header(array $row, array $fields): array {
        $lookup = ['username' => 'username', 'email' => 'email'];
        $lookup[\core_text::strtolower(get_string('username'))] = 'username';
        $lookup[\core_text::strtolower(get_string('email'))] = 'email';
        foreach ($fields as $f) {
            if ($f->key === 'core_email') {
                continue;
            }
            $lookup[\core_text::strtolower(template_builder::column_key($f))] = $f->key;
            $lookup[\core_text::strtolower($f->shortname)] = $lookup[\core_text::strtolower($f->shortname)] ?? $f->key;
            $lookup[\core_text::strtolower(trim(strip_tags($f->name)))] =
                $lookup[\core_text::strtolower(trim(strip_tags($f->name)))] ?? $f->key;
        }
        $map = [];
        foreach ($row as $i => $cell) {
            $k = \core_text::strtolower(trim((string) $cell));
            $map[$i] = $lookup[$k] ?? null;
        }
        return $map;
    }

    /**
     * Find the header row (one of the first 5 rows that has a username or email column).
     * The Excel template has labels in row 1 and column keys in row 2, so prefer the row
     * with the most recognised columns.
     *
     * @param array $rows
     * @param \stdClass[] $fields
     * @return array [row index, map]
     * @throws \moodle_exception
     */
    public static function find_header(array $rows, array $fields): array {
        $best = null;
        foreach (array_slice($rows, 0, 5, true) as $i => $row) {
            $map = self::map_header($row, $fields);
            if (!in_array('username', $map, true) && !in_array('email', $map, true)) {
                continue;
            }
            $score = count(array_filter($map));
            if ($best === null || $score > $best[2]) {
                $best = [$i, $map, $score];
            }
        }
        if ($best === null) {
            throw new \moodle_exception('import_noheader', 'local_profileeditor');
        }
        return [$best[0], $best[1]];
    }

    /**
     * Turn friendly input into the format the editor expects (dates YYYY-MM-DD, checkbox 1/0,
     * menu options matched without case).
     *
     * @param \stdClass $field
     * @param string $value
     * @return string
     */
    public static function prepare_value(\stdClass $field, string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        switch ($field->datatype) {
            case 'checkbox':
                $v = \core_text::strtolower($value);
                return in_array($v, ['1', 'yes', 'y', 'true', 'x', '✓', \core_text::strtolower(get_string('yes'))], true)
                    ? '1' : '0';
            case 'menu':
                foreach ($field->options as $opt) {
                    if (\core_text::strtolower($opt) === \core_text::strtolower($value)) {
                        return $opt;
                    }
                }
                return $value;
            case 'datetime':
                return self::prepare_date($value);
            default:
                return $value;
        }
    }

    /**
     * Normalise a date written in common ways (or an Excel serial number) to YYYY-MM-DD.
     *
     * @param string $value
     * @return string unchanged if not recognised (validation then reports it)
     */
    public static function prepare_date(string $value): string {
        if (preg_match('/^\d{4,5}(\.\d+)?$/', $value) && (float) $value > 20000 && (float) $value < 80000) {
            // Excel serial date.
            $ts = ((int) floor((float) $value) - 25569) * 86400;
            return gmdate('Y-m-d', $ts);
        }
        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})/', $value, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $value, $m)) {
            // Day first (d/m/Y), the common format outside the US.
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        $ts = strtotime($value . ' 12:00 UTC');
        if ($ts !== false && preg_match('/[a-z]/i', $value)) {
            return gmdate('Y-m-d', $ts);
        }
        return $value;
    }

    /**
     * Compare the file with the LMS and list what would change. Nothing is saved.
     *
     * @param array $rows file rows
     * @param string $matchby auto, username, email or idnumber
     * @param bool $blankclears blank cells clear the field (otherwise they are skipped)
     * @return array plan
     * @throws \moodle_exception
     */
    public static function build_plan(array $rows, string $matchby, bool $blankclears): array {
        global $DB, $CFG;
        $fields = field_manager::get_all_fields();
        [$headerrow, $map] = self::find_header($rows, $fields);

        // Which column identifies the user.
        $idcols = [];
        foreach ($map as $i => $k) {
            if ($k === 'username' || $k === 'email' || $k === 'core_idnumber') {
                $idcols[$k === 'core_idnumber' ? 'idnumber' : $k] = $i;
            }
        }
        if ($matchby === 'auto') {
            $matchby = isset($idcols['username']) ? 'username' : (isset($idcols['email']) ? 'email' : '');
        }
        if (!isset($idcols[$matchby])) {
            throw new \moodle_exception('import_nomatchcol', 'local_profileeditor', '', $matchby);
        }
        $idcol = $idcols[$matchby];

        // Columns to update.
        $columns = [];
        $updatecols = [];
        foreach ($rows[$headerrow] as $i => $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            $k = $map[$i] ?? null;
            if ($i === $idcol) {
                $status = 'match';
            } else if ($k === null) {
                $status = 'unknown';
            } else if ($k === 'username') {
                $status = 'identity';
            } else if ($k === 'email') {
                // The email column updates the email only when users are matched by something else
                // and email is not read-only.
                $k = 'core_email';
                $status = field_manager::is_editable($fields[$k]) ? 'update' : 'readonly';
            } else {
                $status = field_manager::is_editable($fields[$k]) ? 'update' : 'readonly';
            }
            $columns[] = ['label' => $label, 'key' => $k, 'status' => $status,
                'name' => ($k && isset($fields[$k])) ? $fields[$k]->name : $label];
            if ($status === 'update') {
                $updatecols[$i] = $k;
            }
        }

        // Every user once, so 1000s of rows need only a few queries.
        $users = $DB->get_records_select('user', 'deleted = 0 AND id <> :guest', ['guest' => $CFG->siteguest],
            '', 'id, username, email, idnumber, firstname, lastname, suspended, mnethostid');
        $index = [];
        $dupes = [];
        foreach ($users as $u) {
            $v = \core_text::strtolower(trim((string) $u->$matchby));
            if ($v === '') {
                continue;
            }
            if (isset($index[$v])) {
                $dupes[$v] = true;
            }
            $index[$v] = $u->id;
        }

        $plan = [
            'matchby' => $matchby,
            'blankclears' => $blankclears,
            'columns' => $columns,
            'changes' => [],
            'errors' => [],
            'percolumn' => [],
            'stats' => ['rows' => 0, 'matched' => 0, 'notfound' => 0, 'unchanged' => 0, 'users' => 0, 'changes' => 0,
                'errors' => 0, 'duplicates' => 0],
        ];
        $seen = [];
        $matched = [];
        foreach ($rows as $r => $row) {
            if ($r <= $headerrow || !array_filter($row, 'strlen')) {
                continue;
            }
            // Skip the key row of the Excel template if the label row was picked as header.
            if (in_array(\core_text::strtolower(trim((string) ($row[$idcol] ?? ''))), ['username', 'email', 'idnumber'])) {
                continue;
            }
            $plan['stats']['rows']++;
            $id = \core_text::strtolower(trim((string) ($row[$idcol] ?? '')));
            $line = $r + 1;
            if ($id === '' || !isset($index[$id])) {
                $plan['stats']['notfound']++;
                $plan['errors'][] = ['row' => $line, 'who' => $id, 'field' => '',
                    'msg' => get_string('import_usernotfound', 'local_profileeditor')];
                continue;
            }
            if (isset($dupes[$id])) {
                $plan['stats']['duplicates']++;
                $plan['errors'][] = ['row' => $line, 'who' => $id, 'field' => '',
                    'msg' => get_string('import_ambiguous', 'local_profileeditor', $matchby)];
                continue;
            }
            if (isset($seen[$id])) {
                $plan['stats']['duplicates']++;
                $plan['errors'][] = ['row' => $line, 'who' => $id, 'field' => '',
                    'msg' => get_string('import_duplicaterow', 'local_profileeditor', $seen[$id])];
                continue;
            }
            $seen[$id] = $line;
            $matched[$index[$id]] = ['row' => $line, 'cells' => $row];
        }
        $plan['stats']['matched'] = count($matched);

        // Current values, in chunks.
        $updatefields = [];
        foreach (array_unique($updatecols) as $k) {
            $updatefields[$k] = $fields[$k];
        }
        foreach (array_chunk(array_keys($matched), 500) as $ids) {
            $chunkusers = array_intersect_key($users, array_flip($ids));
            $fullusers = $DB->get_records_list('user', 'id', $ids, '', user_query::user_columns_plain());
            $current = field_manager::get_values($fullusers, $updatefields);
            foreach ($ids as $userid) {
                $u = $chunkusers[$userid];
                $who = $u->$matchby;
                $line = $matched[$userid]['row'];
                $cells = $matched[$userid]['cells'];
                $userchanges = 0;
                if (is_siteadmin($userid) && !is_siteadmin()) {
                    $plan['errors'][] = ['row' => $line, 'who' => $who, 'field' => '',
                        'msg' => get_string('error_siteadmin', 'local_profileeditor')];
                    continue;
                }
                foreach ($updatecols as $i => $k) {
                    $f = $fields[$k];
                    $value = trim((string) ($cells[$i] ?? ''));
                    if ($value === '' && !$blankclears) {
                        continue;
                    }
                    $input = self::prepare_value($f, $value);
                    try {
                        $new = field_manager::normalise($f, $input, (int) $userid);
                    } catch (\moodle_exception $e) {
                        $plan['errors'][] = ['row' => $line, 'who' => $who, 'field' => $f->name, 'msg' => $e->getMessage(),
                            'value' => \core_text::substr($value, 0, 100)];
                        continue;
                    }
                    $old = $current[$userid][$k] ?? '';
                    if ($f->datatype === 'checkbox' || $f->datatype === 'datetime') {
                        $old = $old === '' ? '0' : $old;
                    }
                    if ((string) $old === $new) {
                        continue;
                    }
                    $plan['changes'][] = [
                        'u' => (int) $userid,
                        'k' => $k,
                        'v' => field_manager::input_value($f, $new),
                        'who' => $who,
                        'name' => trim($u->firstname . ' ' . $u->lastname),
                        'row' => $line,
                        'old' => \core_text::substr(field_manager::format_value($f, (string) $old), 0, 200),
                        'new' => \core_text::substr(field_manager::format_value($f, $new), 0, 200),
                    ];
                    $plan['percolumn'][$k] = ($plan['percolumn'][$k] ?? 0) + 1;
                    $userchanges++;
                }
                if ($userchanges) {
                    $plan['stats']['users']++;
                } else {
                    $plan['stats']['unchanged']++;
                }
            }
        }
        $plan['stats']['changes'] = count($plan['changes']);
        $plan['stats']['errors'] = count($plan['errors']);
        return $plan;
    }

    /**
     * Folder for stored plans.
     *
     * @return string
     */
    protected static function dir(): string {
        return make_temp_directory('local_profileeditor/import');
    }

    /**
     * Store a plan until the admin confirms it.
     *
     * @param array $plan
     * @return string token
     */
    public static function store_plan(array $plan): string {
        global $USER;
        foreach (glob(self::dir() . '/*.json') ?: [] as $old) {
            if (filemtime($old) < time() - self::PLAN_TTL) {
                @unlink($old);
            }
        }
        $token = random_string(24);
        $plan['owner'] = (int) $USER->id;
        $plan['created'] = time();
        file_put_contents(self::dir() . '/' . $token . '.json', json_encode($plan));
        return $token;
    }

    /**
     * Load a stored plan (only for the user who created it).
     *
     * @param string $token
     * @return array|null
     */
    public static function load_plan(string $token): ?array {
        global $USER;
        if (!preg_match('/^[A-Za-z0-9]{24}$/', $token)) {
            return null;
        }
        $file = self::dir() . '/' . $token . '.json';
        if (!is_readable($file)) {
            return null;
        }
        $plan = json_decode(file_get_contents($file), true);
        if (!is_array($plan) || (int) ($plan['owner'] ?? 0) !== (int) $USER->id ||
                ($plan['created'] ?? 0) < time() - self::PLAN_TTL) {
            return null;
        }
        return $plan;
    }

    /**
     * Delete a stored plan.
     *
     * @param string $token
     */
    public static function delete_plan(string $token) {
        if (preg_match('/^[A-Za-z0-9]{24}$/', $token)) {
            @unlink(self::dir() . '/' . $token . '.json');
        }
    }

    /**
     * Apply a plan: one save per user (all that user's fields at once).
     *
     * @param array $plan
     * @param callable|null $progress function(int $done, int $total)
     * @return array ['users' => n, 'fields' => n, 'errors' => [...]]
     */
    public static function apply_plan(array $plan, ?callable $progress = null): array {
        $byuser = [];
        $who = [];
        foreach ($plan['changes'] as $c) {
            $byuser[$c['u']][$c['k']] = $c['v'];
            $who[$c['u']] = $c['who'];
        }
        $fields = field_manager::get_all_fields();
        $result = ['users' => 0, 'fields' => 0, 'errors' => []];
        $total = count($byuser);
        $done = 0;
        foreach ($byuser as $userid => $values) {
            try {
                $r = field_manager::save_values((int) $userid, $values, 'import');
                $n = count(array_filter($r['changed']));
                if ($n) {
                    $result['users']++;
                    $result['fields'] += $n;
                }
                foreach ($r['errors'] as $k => $msg) {
                    $result['errors'][] = ['who' => $who[$userid], 'field' => $fields[$k]->name ?? $k, 'msg' => $msg];
                }
            } catch (\Throwable $e) {
                $result['errors'][] = ['who' => $who[$userid], 'field' => '', 'msg' => $e->getMessage()];
            }
            $done++;
            if ($progress) {
                $progress($done, $total);
            }
        }
        return $result;
    }
}
