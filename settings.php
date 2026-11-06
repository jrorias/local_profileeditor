<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Admin menu entries and settings.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Site administration > Users > Profile field editor.
$ADMIN->add('accounts', new admin_category('local_profileeditor_cat', get_string('pluginname', 'local_profileeditor')));

$ADMIN->add('local_profileeditor_cat', new admin_externalpage('local_profileeditor',
    get_string('nav_editor', 'local_profileeditor'),
    new moodle_url('/local/profileeditor/index.php'), 'local/profileeditor:view'));
$ADMIN->add('local_profileeditor_cat', new admin_externalpage('local_profileeditor_import',
    get_string('nav_import', 'local_profileeditor'),
    new moodle_url('/local/profileeditor/import.php'), 'local/profileeditor:edit'));
$ADMIN->add('local_profileeditor_cat', new admin_externalpage('local_profileeditor_guide',
    get_string('nav_guide', 'local_profileeditor'),
    new moodle_url('/local/profileeditor/guide.php'), 'local/profileeditor:view'));
$ADMIN->add('local_profileeditor_cat', new admin_externalpage('local_profileeditor_log',
    get_string('changelog', 'local_profileeditor'),
    new moodle_url('/local/profileeditor/log.php'), 'local/profileeditor:viewlog'));
$ADMIN->add('local_profileeditor_cat', new admin_externalpage('local_profileeditor_columns',
    get_string('nav_columns', 'local_profileeditor'),
    new moodle_url('/local/profileeditor/columns.php'), 'moodle/site:config'));
$ADMIN->add('local_profileeditor_cat', new admin_externalpage('local_profileeditor_branding',
    get_string('nav_branding', 'local_profileeditor'),
    new moodle_url('/local/profileeditor/branding.php'), 'moodle/site:config'));

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_profileeditor_settings', get_string('settings', 'local_profileeditor'));

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading('local_profileeditor/h_pages', '',
            get_string('settings_pages', 'local_profileeditor', (object) [
                'columns' => (new moodle_url('/local/profileeditor/columns.php'))->out(),
                'branding' => (new moodle_url('/local/profileeditor/branding.php'))->out(),
            ])));

        $settings->add(new admin_setting_configtext('local_profileeditor/defaultperpage',
            get_string('defaultperpage', 'local_profileeditor'),
            get_string('defaultperpage_desc', 'local_profileeditor'), 50, PARAM_INT));

        $settings->add(new admin_setting_heading('local_profileeditor/h_template',
            get_string('settings_template', 'local_profileeditor'), ''));
        $settings->add(new admin_setting_configselect('local_profileeditor/templateformat',
            get_string('templateformat', 'local_profileeditor'),
            get_string('templateformat_desc', 'local_profileeditor'), 'xlsx',
            ['xlsx' => get_string('format_xlsx', 'local_profileeditor'), 'csv' => get_string('format_csv', 'local_profileeditor')]));

        $sortchoices = ['' => get_string('templatesort_name', 'local_profileeditor')];
        foreach (\local_profileeditor\field_manager::get_all_fields() as $key => $f) {
            $sortchoices[$key] = $f->name . ' (' . $f->shortname . ')';
        }
        $settings->add(new admin_setting_configselect('local_profileeditor/templatesort',
            get_string('templatesort', 'local_profileeditor'),
            get_string('templatesort_desc', 'local_profileeditor'), '', $sortchoices));
        $settings->add(new admin_setting_configselect('local_profileeditor/templatesortdir',
            get_string('templatesortdir', 'local_profileeditor'), '', 'ASC',
            ['ASC' => get_string('asc'), 'DESC' => get_string('desc')]));

        // Optional filters. Empty by default: nothing is built in for any organisation.
        $settings->add(new admin_setting_configtextarea('local_profileeditor/templatedomains',
            get_string('templatedomains', 'local_profileeditor'),
            get_string('templatedomains_desc', 'local_profileeditor'), '', PARAM_RAW, 40, 3));
        $settings->add(new admin_setting_configtextarea('local_profileeditor/templateexcluded',
            get_string('templateexcluded', 'local_profileeditor'),
            get_string('templateexcluded_desc', 'local_profileeditor'), '', PARAM_RAW, 40, 5));

        $settings->add(new admin_setting_heading('local_profileeditor/h_import',
            get_string('settings_import', 'local_profileeditor'), ''));
        $matchchoices = [];
        foreach (['auto', 'username', 'email', 'idnumber'] as $m) {
            $matchchoices[$m] = get_string('import_matchby_' . $m, 'local_profileeditor');
        }
        $settings->add(new admin_setting_configselect('local_profileeditor/matchby',
            get_string('import_matchby', 'local_profileeditor'),
            get_string('matchby_desc', 'local_profileeditor'), 'auto', $matchchoices));
    }

    $ADMIN->add('local_profileeditor_cat', $settings);
}
