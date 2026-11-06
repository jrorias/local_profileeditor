<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Branding stylesheet (colours, fonts, sizes) for the profile editor pages.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require(__DIR__ . '/../../config.php');

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=604800');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT');
echo \local_profileeditor\branding::css();
