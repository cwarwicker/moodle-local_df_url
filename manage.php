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
 * This page displays a table of all the editable URL conversions and lets you add new ones.
 *
 * @package    local_df_url
 * @copyright  2020 onwards Conn Warwicker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_df_url\router;

require_once('../../config.php');

// Need to be logged in to view this page.
require_login();

// Need the capability to configure the plugin.
$context = context_system::instance();
require_capability('local/df_url:config', $context);

$action = optional_param('action', null, PARAM_TEXT);
$id = optional_param('id', null, PARAM_INT);

if ($action == 'delete') {
    require_sesskey();
    router::delete($id);
}

$PAGE->set_context($context);
$PAGE->set_url( new moodle_url('/local/df_url/manage.php') );
$PAGE->set_title( get_string('manageurls', 'local_df_url') );
$PAGE->set_heading( get_string('manageurls', 'local_df_url') );

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('local_df_url');
echo $renderer->render_table();

echo $OUTPUT->footer();