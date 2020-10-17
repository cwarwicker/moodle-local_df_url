<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local_df_url
 * @copyright  2020 onwards Conn Warwicker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    $ADMIN->add('localplugins', new admin_category('local_df_url', get_string('pluginname', 'local_df_url')));
    $page = new admin_settingpage('manage_local_df_url', get_string('manage', 'local_df_url'));

    if ($ADMIN->fulltree) {

        // Enable/Disable Plugin.
        $page->add( new admin_setting_configcheckbox(
            'local_df_url/enabled',
            get_string('setting:enabled', 'local_df_url'),
            get_string('setting:enabled:info', 'local_df_url'),
            1
        ) );

        // Enable/Disable Caching.
        $page->add( new admin_setting_configcheckbox(
            'local_df_url/caching',
            get_string('setting:caching', 'local_df_url'),
            get_string('setting:caching:info', 'local_df_url'),
            1
        ) );

        // Enable/Disable javascript inversion.
        $page->add( new admin_setting_configcheckbox(
            'local_df_url/inversion',
            get_string('setting:inversion', 'local_df_url'),
            get_string('setting:inversion:info', 'local_df_url'),
            1
        ) );

    }

    $ADMIN->add('local_df_url', $page);
    $ADMIN->add('local_df_url', new admin_externalpage('urls_local_df_url', get_string('conversioneditor', 'local_df_url'), new moodle_url('/local/df_url/manage.php')));

}