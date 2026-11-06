<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Web service functions (used by the mobile app).
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_profileeditor_save_user' => [
        'classname' => 'local_profileeditor\external\save_user',
        'methodname' => 'execute',
        'description' => 'Save several profile fields of one user.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/profileeditor:edit',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_profileeditor_bulk_set' => [
        'classname' => 'local_profileeditor\external\bulk_set',
        'methodname' => 'execute',
        'description' => 'Set one profile field for every user matching a filter.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/profileeditor:edit',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
