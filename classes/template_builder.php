<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Builds the update template with live user data, as a branded Excel file (for the
 * Import updates page) or as a CSV (also accepted by the LMS "Upload users" tool).
 *
 * No email domains or accounts are built in: by default every active user is included.
 * Admins can optionally limit the template to some email domains and leave out accounts
 * in the plugin settings.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profileeditor;

defined('MOODLE_INTERNAL') || die();

/**
 * Template builder.
 */
class template_builder {

    /** @var int First data row in the Excel template (row 1 = labels, row 2 = column keys). */
    const XLSX_FIRSTROW = 3;

    /**
     * Configured email domains (optional, in order). Empty = all users.
     *
     * @return string[]
     */
    public static function get_domains(): array {
        return self::lines((string) get_config('local_profileeditor', 'templatedomains'), true);
    }

    /**
     * Configured excluded accounts (emails or usernames, lower case). Empty by default.
     *
     * @return string[]
     */
    public static function get_excluded(): array {
        return self::lines((string) get_config('local_profileeditor', 'templateexcluded'), false);
    }

    /**
     * Split a textarea setting into clean lower-case items.
     *
     * @param string $value
     * @param bool $stripat remove a leading @
     * @return string[]
     */
    public static function lines(string $value, bool $stripat): array {
        $out = [];
        foreach (preg_split('/[\s,;]+/', \core_text::strtolower($value)) as $line) {
            $line = trim($line);
            if ($stripat) {
                $line = ltrim($line, '@');
            }
            if ($line !== '') {
                $out[] = $line;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * Fields that can go in the template (all editor columns except email, which is always included),
     * in the editor's column order.
     *
     * @param string[]|null $keys only these field keys (null = all)
     * @return \stdClass[]
     */
    public static function get_template_fields(?array $keys = null): array {
        return array_filter(field_manager::get_visible_fields(), function($f) use ($keys) {
            return $f->key !== 'core_email' && ($keys === null || in_array($f->key, $keys, true));
        });
    }

    /**
     * Field keys ticked by default: every custom profile field.
     *
     * @return string[]
     */
    public static function default_keys(): array {
        $keys = [];
        foreach (self::get_template_fields() as $f) {
            if (!$f->core) {
                $keys[] = $f->key;
            }
        }
        return $keys;
    }

    /**
     * Column key used in files.
     *
     * @param \stdClass $f
     * @return string
     */
    public static function column_key(\stdClass $f): string {
        return $f->core ? $f->shortname : 'profile_field_' . $f->shortname;
    }

    /**
     * Users included in the template, in final order.
     *
     * @param bool $suspended include suspended users
     * @return \stdClass[]
     */
    public static function get_users(bool $suspended = false): array {
        global $DB, $CFG;
        $params = ['guestid' => $CFG->siteguest];
        $where = 'u.deleted = 0 AND u.id <> :guestid';
        if (!$suspended) {
            $where .= ' AND u.suspended = 0';
        }

        $order = [];
        $domains = self::get_domains();
        if ($domains) {
            $domainconds = [];
            $ordercase = 'CASE';
            foreach ($domains as $i => $domain) {
                $domainconds[] = $DB->sql_like('u.email', ':dom' . $i, false, false);
                $params['dom' . $i] = '%@' . $DB->sql_like_escape($domain);
                $ordercase .= ' WHEN ' . $DB->sql_like('u.email', ':dor' . $i, false, false) . ' THEN ' . ($i + 1);
                $params['dor' . $i] = '%@' . $DB->sql_like_escape($domain);
            }
            $where .= ' AND (' . implode(' OR ', $domainconds) . ')';
            $order[] = $ordercase . ' ELSE ' . (count($domains) + 1) . ' END ASC';
        }

        $excluded = self::get_excluded();
        if ($excluded) {
            [$ex1, $p1] = $DB->get_in_or_equal($excluded, SQL_PARAMS_NAMED, 'exe', false);
            [$ex2, $p2] = $DB->get_in_or_equal($excluded, SQL_PARAMS_NAMED, 'exu', false);
            $where .= " AND LOWER(u.email) $ex1 AND LOWER(u.username) $ex2";
            $params += $p1 + $p2;
        }

        // Optional sort field (Settings). Default: last name, first name.
        $all = field_manager::get_all_fields();
        $sortkey = (string) get_config('local_profileeditor', 'templatesort');
        $sortdir = get_config('local_profileeditor', 'templatesortdir') === 'DESC' ? 'DESC' : 'ASC';
        if ($sortkey !== '' && isset($all[$sortkey])) {
            $sf = $all[$sortkey];
            if ($sf->core) {
                $order[] = "u.{$sf->shortname} $sortdir";
            } else {
                $order[] = "(SELECT sd.data FROM {user_info_data} sd
                              WHERE sd.userid = u.id AND sd.fieldid = :sortfid) $sortdir";
                $params['sortfid'] = $sf->id;
            }
        }
        $order[] = 'u.lastname ASC, u.firstname ASC, u.username ASC';

        $cols = user_query::user_columns();
        return $DB->get_records_sql("SELECT $cols FROM {user} u WHERE $where ORDER BY " . implode(', ', $order), $params);
    }

    /**
     * Data rows (no header).
     *
     * @param \stdClass[] $fields
     * @param \stdClass[] $users
     * @return array
     */
    public static function get_data(array $fields, array $users): array {
        $rows = [];
        foreach (array_chunk($users, 500, true) as $chunk) {
            $values = field_manager::get_values($chunk, $fields);
            foreach ($chunk as $u) {
                $row = [$u->username, $u->email];
                foreach ($fields as $f) {
                    $row[] = field_manager::export_value($f, $values[$u->id][$f->key] ?? '');
                }
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * File name without extension.
     *
     * @return string
     */
    public static function filename(): string {
        $org = preg_replace('/[^A-Za-z0-9]+/', '_', branding::org_name());
        $org = trim($org, '_');
        return ($org !== '' ? $org . '_' : '') . 'user_update_template_' . date('Ymd_His');
    }

    /**
     * Send the CSV (column names match the LMS Upload users tool).
     *
     * @param string[]|null $keys
     * @param bool $suspended
     */
    public static function send_csv(?array $keys, bool $suspended) {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');
        $fields = self::get_template_fields($keys);
        $header = ['username', 'email'];
        foreach ($fields as $f) {
            $header[] = self::column_key($f);
        }
        $writer = new \csv_export_writer('comma');
        $writer->set_filename(self::filename());
        $writer->add_data($header);
        foreach (self::get_data($fields, self::get_users($suspended)) as $row) {
            $writer->add_data($row);
        }
        $writer->download_file();
    }

    /**
     * Send the branded Excel template.
     *
     * @param string[]|null $keys
     * @param bool $suspended
     */
    public static function send_xlsx(?array $keys, bool $suspended) {
        global $CFG;
        require_once($CFG->libdir . '/excellib.class.php'); // Loads the bundled spreadsheet library.
        \core_php_time_limit::raise(300);
        raise_memory_limit(MEMORY_EXTRA);

        $fields = self::get_template_fields($keys);
        $users = self::get_users($suspended);
        $spreadsheet = self::build_spreadsheet($fields, self::get_data($fields, $users), branding::get(),
            branding::org_name());

        $filename = self::filename() . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    /**
     * Build the spreadsheet object. Kept free of LMS calls (apart from strings) so it can be tested.
     *
     * @param \stdClass[] $fields
     * @param array $data rows
     * @param array $brand branding
     * @param string $orgname
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    public static function build_spreadsheet(array $fields, array $data, array $brand, string $orgname) {
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ss->getProperties()->setCreator($orgname)->setTitle(get_string('template_title', 'local_profileeditor'));
        $font = $brand['font'] === 'custom' ? trim(explode(',', $brand['customfont'])[0], " '\"") :
            trim(explode(',', branding::FONTS[$brand['font']][1] ?? 'Arial')[0], " '\"");
        if ($font === '' || $font === 'system-ui') {
            $font = 'Calibri';
        }
        $ss->getDefaultStyle()->getFont()->setName($font)->setSize(11);
        $hex = function(string $c) {
            return strtoupper(ltrim($c, '#'));
        };

        // Sheet 1: users.
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle(get_string('template_sheet_users', 'local_profileeditor'));

        $cols = [
            ['label' => get_string('username'), 'key' => 'username', 'locked' => true, 'field' => null],
            ['label' => get_string('email'), 'key' => 'email', 'locked' => true, 'field' => null],
        ];
        foreach ($fields as $f) {
            $cols[] = ['label' => $f->name, 'key' => self::column_key($f),
                'locked' => !field_manager::is_editable($f), 'field' => $f];
        }
        $last = max(self::XLSX_FIRSTROW, self::XLSX_FIRSTROW + count($data) - 1);
        $lastvalidation = $last + 500; // Leave room for pasted rows.

        // Lists sheet for drop-down options.
        $lists = $ss->createSheet();
        $lists->setTitle('Lists');
        $listcol = 1;

        foreach ($cols as $i => $col) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValueExplicit($letter . '1', $col['label'],
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($letter . '2', $col['key'],
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $width = $col['key'] === 'email' ? 34 : max(14, min(42, \core_text::strlen($col['label']) + 6));
            $sheet->getColumnDimension($letter)->setWidth($width);
            // Everything is text so leading zeros, IDs and dates are kept exactly.
            $sheet->getStyle("{$letter}1:{$letter}{$lastvalidation}")->getNumberFormat()
                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
            if ($col['locked']) {
                $sheet->getStyle("{$letter}" . self::XLSX_FIRSTROW . ":{$letter}{$last}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F2F4F7');
                $sheet->getStyle("{$letter}" . self::XLSX_FIRSTROW . ":{$letter}{$last}")->getFont()
                    ->getColor()->setRGB('667085');
            }

            $f = $col['field'];
            if ($f && !$col['locked'] && in_array($f->datatype, ['menu', 'checkbox'])) {
                $options = $f->datatype === 'menu' ? $f->options : ['1', '0'];
                if ($options) {
                    $ll = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($listcol);
                    $lists->setCellValueExplicit($ll . '1', $col['key'],
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    foreach (array_values($options) as $n => $opt) {
                        $lists->setCellValueExplicit($ll . ($n + 2), $opt,
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                    $dv = new \PhpOffice\PhpSpreadsheet\Cell\DataValidation();
                    $dv->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                        ->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP)
                        ->setAllowBlank(true)->setShowDropDown(true)->setShowErrorMessage(true)
                        ->setErrorTitle(get_string('template_invalidtitle', 'local_profileeditor'))
                        ->setError(get_string('template_invalidmenu', 'local_profileeditor'))
                        ->setFormula1('Lists!$' . $ll . '$2:$' . $ll . '$' . (count($options) + 1));
                    try {
                        $sheet->setDataValidation("{$letter}" . self::XLSX_FIRSTROW . ":{$letter}{$lastvalidation}", $dv);
                    } catch (\Throwable $e) {
                        // Older library versions: skip the drop-down, the import still validates values.
                        debugging($e->getMessage(), DEBUG_DEVELOPER);
                    }
                    $listcol++;
                }
            }
        }
        $lastletter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($cols));

        // Header styling with the brand colours.
        $sheet->getStyle("A1:{$lastletter}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $hex($brand['primarytext'])]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => $hex($brand['primary'])]],
            'alignment' => ['vertical' => 'center', 'wrapText' => true],
        ]);
        $sheet->getStyle("A2:{$lastletter}2")->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => $hex($brand['muted'])]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => $hex($brand['tableheadbg'])]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle("A1:{$lastletter}{$last}")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB($hex($brand['border']));

        // Data.
        $r = self::XLSX_FIRSTROW;
        foreach ($data as $row) {
            foreach (array_values($row) as $c => $value) {
                $sheet->setCellValueExplicit(
                    \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1) . $r, (string) $value,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $r++;
        }
        $sheet->freezePane('C' . self::XLSX_FIRSTROW);
        $sheet->setAutoFilter("A2:{$lastletter}{$last}");
        $lists->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

        // Sheet 2: how to.
        $help = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($ss, get_string('template_sheet_help', 'local_profileeditor'));
        $ss->addSheet($help, 1);
        $help->getColumnDimension('A')->setWidth(34);
        $help->getColumnDimension('B')->setWidth(30);
        $help->getColumnDimension('C')->setWidth(70);
        $help->setCellValue('A1', $orgname);
        $help->setCellValue('A2', get_string('template_title', 'local_profileeditor'));
        $help->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB($hex($brand['headertext']));
        $help->getStyle('A1:C1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB($hex($brand['headerbg']));
        $help->getStyle('A2')->getFont()->setSize(12)->getColor()->setRGB($hex($brand['primary']));
        $row = 4;
        for ($n = 1; $n <= 5; $n++) {
            $help->setCellValue('A' . $row, $n . '. ' . get_string('template_help' . $n, 'local_profileeditor'));
            $help->mergeCells("A{$row}:C{$row}");
            $row++;
        }
        $row++;
        foreach (['guide_col_column', 'guide_col_field', 'guide_col_format'] as $c => $str) {
            $help->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1) . $row,
                get_string($str, 'local_profileeditor'));
        }
        $help->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => $hex($brand['primarytext'])]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => $hex($brand['primary'])]],
        ]);
        $row++;
        foreach ($cols as $col) {
            $help->setCellValueExplicit('A' . $row, $col['key'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $help->setCellValue('B' . $row, $col['label']);
            $help->setCellValue('C' . $row, self::format_hint($col['field'], $col['key'], $col['locked']));
            $help->getStyle('C' . $row)->getAlignment()->setWrapText(true);
            $row++;
        }

        $ss->setActiveSheetIndex(0);
        return $ss;
    }

    /**
     * Plain-text hint about what to enter in a column.
     *
     * @param \stdClass|null $f
     * @param string $key
     * @param bool $locked
     * @return string
     */
    public static function format_hint(?\stdClass $f, string $key, bool $locked): string {
        if ($key === 'username') {
            return get_string('guide_fmt_username', 'local_profileeditor');
        }
        if ($key === 'email') {
            return get_string('guide_fmt_email', 'local_profileeditor');
        }
        if ($locked) {
            return get_string('guide_fmt_readonly', 'local_profileeditor');
        }
        switch ($f->datatype) {
            case 'menu':
                return get_string('guide_fmt_menu', 'local_profileeditor') . ' ' . implode(' | ', $f->options);
            case 'checkbox':
                return get_string('guide_fmt_checkbox', 'local_profileeditor');
            case 'datetime':
                return get_string('guide_fmt_date', 'local_profileeditor');
            default:
                return get_string('guide_fmt_text', 'local_profileeditor');
        }
    }
}
