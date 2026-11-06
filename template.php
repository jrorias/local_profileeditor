<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Download the update template with live user data.
 *
 * Params: format (xlsx|csv), tfields[] (field keys, default all custom profile fields), tsuspended.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_profileeditor\template_builder;

require_login(null, false);
require_capability('local/profileeditor:view', context_system::instance());
require_sesskey();

$format     = optional_param('format', '', PARAM_ALPHA);
$tfields    = optional_param_array('tfields', [], PARAM_ALPHANUMEXT);
$tsuspended = optional_param('tsuspended', 0, PARAM_BOOL);

if ($format !== 'csv' && $format !== 'xlsx') {
    $format = get_config('local_profileeditor', 'templateformat') === 'csv' ? 'csv' : 'xlsx';
}
$keys = $tfields ?: template_builder::default_keys();

if ($format === 'csv') {
    template_builder::send_csv($keys, (bool) $tsuspended);
} else {
    template_builder::send_xlsx($keys, (bool) $tsuspended);
}
