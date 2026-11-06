<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Branding for the profile editor pages and the downloaded template:
 * organisation name, colours, fonts and sizes. Nothing client-specific is built in.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profileeditor;

defined('MOODLE_INTERNAL') || die();

/**
 * Branding.
 */
class branding {

    /** @var string[] Colour settings. */
    const COLOURS = ['primary', 'primarytext', 'accent', 'headerbg', 'headertext', 'tableheadbg', 'tableheadtext',
        'text', 'muted', 'border', 'rowhover', 'background'];

    /** @var array Default branding (neutral). */
    const DEFAULTS = [
        'orgname' => '',
        'tagline' => '',
        'showlogo' => 1,
        'primary' => '#0f6cbf',
        'primarytext' => '#ffffff',
        'accent' => '#14b8a6',
        'headerbg' => '#1e293b',
        'headertext' => '#ffffff',
        'tableheadbg' => '#f1f5f9',
        'tableheadtext' => '#1e293b',
        'text' => '#1f2933',
        'muted' => '#64748b',
        'border' => '#dbe2ea',
        'rowhover' => '#fff8d6',
        'background' => '#ffffff',
        'font' => 'system',
        'headingfont' => 'same',
        'customfont' => '',
        'fonturl' => '',
        'basesize' => 15,
        'tablesize' => 14,
        'headingsize' => 26,
        'radius' => 8,
        'density' => 'comfortable',
    ];

    /** @var array Ready-made colour themes (generic, not tied to any client). */
    const PRESETS = [
        'classic' => ['primary' => '#0f6cbf', 'primarytext' => '#ffffff', 'accent' => '#14b8a6',
            'headerbg' => '#1e293b', 'headertext' => '#ffffff', 'tableheadbg' => '#f1f5f9', 'tableheadtext' => '#1e293b',
            'text' => '#1f2933', 'muted' => '#64748b', 'border' => '#dbe2ea', 'rowhover' => '#fff8d6', 'background' => '#ffffff'],
        'aquagrey' => ['primary' => '#00a3ad', 'primarytext' => '#ffffff', 'accent' => '#5fd0d6',
            'headerbg' => '#3c4146', 'headertext' => '#ffffff', 'tableheadbg' => '#eef1f3', 'tableheadtext' => '#3c4146',
            'text' => '#2b2f33', 'muted' => '#7a8188', 'border' => '#d5dadf', 'rowhover' => '#e6f7f8', 'background' => '#ffffff'],
        'navygold' => ['primary' => '#1d3557', 'primarytext' => '#ffffff', 'accent' => '#d4a017',
            'headerbg' => '#14213d', 'headertext' => '#f6d365', 'tableheadbg' => '#eef2f7', 'tableheadtext' => '#14213d',
            'text' => '#1b2430', 'muted' => '#6b7280', 'border' => '#d6dde8', 'rowhover' => '#fdf6e3', 'background' => '#ffffff'],
        'forest' => ['primary' => '#2f7d4f', 'primarytext' => '#ffffff', 'accent' => '#9bd770',
            'headerbg' => '#1f3d2b', 'headertext' => '#ffffff', 'tableheadbg' => '#eef5f0', 'tableheadtext' => '#1f3d2b',
            'text' => '#1f2a22', 'muted' => '#667a6d', 'border' => '#d3e2d8', 'rowhover' => '#f1f9ec', 'background' => '#ffffff'],
        'plum' => ['primary' => '#6b3fa0', 'primarytext' => '#ffffff', 'accent' => '#e879f9',
            'headerbg' => '#2e1a47', 'headertext' => '#ffffff', 'tableheadbg' => '#f4effa', 'tableheadtext' => '#2e1a47',
            'text' => '#231a2e', 'muted' => '#776b86', 'border' => '#e0d6ec', 'rowhover' => '#faf0ff', 'background' => '#ffffff'],
        'charcoal' => ['primary' => '#111827', 'primarytext' => '#ffffff', 'accent' => '#f97316',
            'headerbg' => '#111827', 'headertext' => '#ffffff', 'tableheadbg' => '#f3f4f6', 'tableheadtext' => '#111827',
            'text' => '#111827', 'muted' => '#6b7280', 'border' => '#e5e7eb', 'rowhover' => '#fff7ed', 'background' => '#ffffff'],
    ];

    /** @var array Fonts: key => [label, CSS stack, Google Fonts family or ''] */
    const FONTS = [
        'system' => ['System default', 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif', ''],
        'inter' => ['Inter', '"Inter", system-ui, sans-serif', 'Inter:wght@400;500;600;700'],
        'opensans' => ['Open Sans', '"Open Sans", system-ui, sans-serif', 'Open+Sans:wght@400;600;700'],
        'roboto' => ['Roboto', '"Roboto", system-ui, sans-serif', 'Roboto:wght@400;500;700'],
        'lato' => ['Lato', '"Lato", system-ui, sans-serif', 'Lato:wght@400;700'],
        'montserrat' => ['Montserrat', '"Montserrat", system-ui, sans-serif', 'Montserrat:wght@400;500;600;700'],
        'poppins' => ['Poppins', '"Poppins", system-ui, sans-serif', 'Poppins:wght@400;500;600;700'],
        'nunitosans' => ['Nunito Sans', '"Nunito Sans", system-ui, sans-serif', 'Nunito+Sans:wght@400;600;700'],
        'sourcesans' => ['Source Sans 3', '"Source Sans 3", system-ui, sans-serif', 'Source+Sans+3:wght@400;600;700'],
        'notosans' => ['Noto Sans', '"Noto Sans", system-ui, sans-serif', 'Noto+Sans:wght@400;600;700'],
        'notoarabic' => ['Noto Sans Arabic', '"Noto Sans Arabic", "Noto Sans", system-ui, sans-serif',
            'Noto+Sans+Arabic:wght@400;600;700'],
        'helvetica' => ['Helvetica Neue', '"Helvetica Neue", Helvetica, Arial, sans-serif', ''],
        'arial' => ['Arial / Helvetica', 'Arial, "Helvetica Neue", Helvetica, sans-serif', ''],
        'segoe' => ['Segoe UI', '"Segoe UI", Tahoma, Geneva, Verdana, sans-serif', ''],
        'georgia' => ['Georgia (serif)', 'Georgia, "Times New Roman", serif', ''],
        'custom' => ['Custom…', '', ''],
    ];

    /** @var array Size limits [min, max] in px. */
    const SIZES = ['basesize' => [11, 22], 'tablesize' => [10, 20], 'headingsize' => [16, 44], 'radius' => [0, 24]];

    /**
     * Current branding (saved values merged over defaults).
     *
     * @return array
     */
    public static function get(): array {
        $saved = json_decode((string) get_config('local_profileeditor', 'branding'), true);
        return self::clean(is_array($saved) ? $saved : []);
    }

    /**
     * Validate and fill a branding array.
     *
     * @param array $in
     * @return array
     */
    public static function clean(array $in): array {
        $out = self::DEFAULTS;
        $out['orgname'] = \core_text::substr(clean_param((string) ($in['orgname'] ?? ''), PARAM_TEXT), 0, 120);
        $out['tagline'] = \core_text::substr(clean_param((string) ($in['tagline'] ?? ''), PARAM_TEXT), 0, 200);
        $out['showlogo'] = empty($in['showlogo']) ? 0 : 1;
        if (!array_key_exists('showlogo', $in)) {
            $out['showlogo'] = self::DEFAULTS['showlogo'];
        }
        foreach (self::COLOURS as $c) {
            $v = strtolower(trim((string) ($in[$c] ?? '')));
            if (preg_match('/^#[0-9a-f]{3}$/', $v)) {
                $v = '#' . $v[1] . $v[1] . $v[2] . $v[2] . $v[3] . $v[3];
            }
            if (preg_match('/^#[0-9a-f]{6}$/', $v)) {
                $out[$c] = $v;
            }
        }
        foreach (['font', 'headingfont'] as $k) {
            $v = (string) ($in[$k] ?? '');
            if (isset(self::FONTS[$v]) || ($k === 'headingfont' && $v === 'same')) {
                $out[$k] = $v;
            }
        }
        $out['customfont'] = \core_text::substr(preg_replace('/[^A-Za-z0-9 ,\'"\-]/', '',
            (string) ($in['customfont'] ?? '')), 0, 200);
        $url = trim((string) ($in['fonturl'] ?? ''));
        $out['fonturl'] = (preg_match('~^https://[^\s"\'()<>]+$~', $url)) ? $url : '';
        foreach (self::SIZES as $k => [$min, $max]) {
            if (isset($in[$k]) && is_numeric($in[$k])) {
                $out[$k] = max($min, min($max, (int) $in[$k]));
            }
        }
        $out['density'] = ($in['density'] ?? '') === 'compact' ? 'compact' : 'comfortable';
        return $out;
    }

    /**
     * Save branding.
     *
     * @param array $data
     */
    public static function save(array $data) {
        set_config('branding', json_encode(self::clean($data)), 'local_profileeditor');
        set_config('brandrev', time(), 'local_profileeditor');
    }

    /**
     * Back to defaults.
     */
    public static function reset() {
        unset_config('branding', 'local_profileeditor');
        set_config('brandrev', time(), 'local_profileeditor');
    }

    /**
     * Organisation name shown on the pages and in file names (falls back to the site name).
     *
     * @return string
     */
    public static function org_name(): string {
        global $SITE;
        $b = self::get();
        return $b['orgname'] !== '' ? $b['orgname'] : format_string($SITE->fullname);
    }

    /**
     * CSS font stack for a font key.
     *
     * @param array $b
     * @param string $key
     * @return string
     */
    protected static function stack(array $b, string $key): string {
        if ($key === 'custom') {
            return $b['customfont'] !== '' ? $b['customfont'] . ', system-ui, sans-serif' : self::FONTS['system'][1];
        }
        return self::FONTS[$key][1] ?? self::FONTS['system'][1];
    }

    /**
     * External font stylesheet URLs needed.
     *
     * @param array $b
     * @return string[]
     */
    public static function font_urls(array $b): array {
        $families = [];
        foreach ([$b['font'], $b['headingfont']] as $k) {
            if (!empty(self::FONTS[$k][2])) {
                $families[self::FONTS[$k][2]] = true;
            }
        }
        $urls = [];
        if ($families) {
            $urls[] = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', array_keys($families)) .
                '&display=swap';
        }
        if ($b['fonturl'] !== '') {
            $urls[] = $b['fonturl'];
        }
        return $urls;
    }

    /**
     * CSS variables (and font imports) for the plugin pages.
     *
     * @param array|null $b
     * @return string
     */
    public static function css(?array $b = null): string {
        $b = $b ?? self::get();
        $css = '';
        foreach (self::font_urls($b) as $url) {
            $css .= "@import url('" . str_replace("'", '', $url) . "');\n";
        }
        $body = self::stack($b, $b['font']);
        $head = $b['headingfont'] === 'same' ? $body : self::stack($b, $b['headingfont']);
        $pad = $b['density'] === 'compact' ? '.25rem .4rem' : '.5rem .65rem';
        $css .= ".lpe-brand {\n";
        foreach (self::COLOURS as $c) {
            $css .= "  --lpe-{$c}: {$b[$c]};\n";
        }
        $css .= "  --lpe-font: {$body};\n  --lpe-headingfont: {$head};\n" .
            "  --lpe-basesize: {$b['basesize']}px;\n  --lpe-tablesize: {$b['tablesize']}px;\n" .
            "  --lpe-headingsize: {$b['headingsize']}px;\n  --lpe-radius: {$b['radius']}px;\n" .
            "  --lpe-cellpad: {$pad};\n}\n";
        return $css;
    }

    /**
     * Add the branding stylesheet to the page. Call before $OUTPUT->header().
     */
    public static function require_css() {
        global $PAGE;
        $rev = (int) get_config('local_profileeditor', 'brandrev');
        $PAGE->requires->css(new \moodle_url('/local/profileeditor/brandcss.php', ['rev' => $rev]));
    }

    /**
     * Branded page header with navigation between the plugin pages.
     *
     * @param string $title
     * @param string $current nav key of the current page
     * @param string $subtitle
     * @return string HTML
     */
    public static function header(string $title, string $current, string $subtitle = ''): string {
        global $OUTPUT;
        $b = self::get();
        $context = \context_system::instance();

        $logo = '';
        if ($b['showlogo']) {
            $url = $OUTPUT->get_compact_logo_url(300, 120) ?: $OUTPUT->get_logo_url(300, 120);
            if ($url) {
                $logo = \html_writer::empty_tag('img', ['src' => $url->out(false), 'alt' => '', 'class' => 'lpe-hero-logo']);
            }
        }
        $text = \html_writer::div(s(self::org_name()), 'lpe-hero-org') .
            \html_writer::tag('h2', s($title), ['class' => 'lpe-hero-title']);
        $sub = $subtitle !== '' ? $subtitle : $b['tagline'];
        if ($sub !== '') {
            $text .= \html_writer::div(s($sub), 'lpe-hero-sub');
        }

        $nav = [
            'editor' => ['/local/profileeditor/index.php', 'nav_editor', 'local/profileeditor:view'],
            'import' => ['/local/profileeditor/import.php', 'nav_import', 'local/profileeditor:edit'],
            'guide' => ['/local/profileeditor/guide.php', 'nav_guide', 'local/profileeditor:view'],
            'log' => ['/local/profileeditor/log.php', 'nav_log', 'local/profileeditor:viewlog'],
            'columns' => ['/local/profileeditor/columns.php', 'nav_columns', 'moodle/site:config'],
            'branding' => ['/local/profileeditor/branding.php', 'nav_branding', 'moodle/site:config'],
        ];
        $links = '';
        foreach ($nav as $key => [$path, $str, $cap]) {
            if (!has_capability($cap, $context)) {
                continue;
            }
            $attrs = ['class' => 'lpe-nav-link' . ($key === $current ? ' active' : '')];
            if ($key === $current) {
                $attrs['aria-current'] = 'page';
            }
            $links .= \html_writer::link(new \moodle_url($path), get_string($str, 'local_profileeditor'), $attrs);
        }

        return \html_writer::div(
            \html_writer::div($logo . \html_writer::div($text, 'lpe-hero-text'), 'lpe-hero-main') .
            \html_writer::tag('nav', $links, ['class' => 'lpe-nav']),
            'lpe-hero');
    }
}
