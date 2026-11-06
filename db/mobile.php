<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Mobile app support: adds "Profile field editor" to the app's main menu (More) for users
 * who may use it, and an "Edit profile fields" link on user profiles in the app.
 *
 * KEEP IN SYNC: every plugin update must check that the app screens (classes/output/mobile.php)
 * still match the web pages. The cache key below follows the plugin version automatically.
 *
 * @package    local_profileeditor
 * @copyright  2026 Capytech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'local_profileeditor' => [
        'handlers' => [
            // Main menu (More) entry.
            'profileeditor' => [
                'delegate' => 'CoreMainMenuDelegate',
                'method' => 'mobile_view',
                'init' => 'mobile_init',
                'displaydata' => [
                    'title' => 'mobile_menutitle',
                    'icon' => 'fa-users',
                    'class' => '',
                ],
                'priority' => 300,
            ],
            // Link on a user's profile page.
            'profileeditoruser' => [
                'delegate' => 'CoreUserDelegate',
                'method' => 'mobile_user',
                'init' => 'mobile_init',
                'type' => 'listitem',
                'displaydata' => [
                    'title' => 'mobile_editprofilefields',
                    'icon' => 'fa-pen',
                    'class' => '',
                ],
                'priority' => 300,
            ],
        ],
        'lang' => [
            ['pluginname', 'local_profileeditor'],
            ['mobile_menutitle', 'local_profileeditor'],
            ['mobile_editprofilefields', 'local_profileeditor'],
            ['saved', 'local_profileeditor'],
        ],
    ],
];
