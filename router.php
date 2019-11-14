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
 * This is the router file which takes the url and querystring and attempts to find a redirect.
 * @package    local_df_nice_urls
 * @copyright  2019 Conn Warwicker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

/**
 *
 * Example URL: /course/1-short-name/view
 *
 * Store routes as:
 *
 * Pattern: e.g: course\/(\d)\-(.*?)\/(.*+)
 * Conversion: course/{$3}.php?id={$1}
 * Params:
 * $1 - PLAIN, $2 - PLAIN, $3 - PLAIN
 *
 * More complex route could involve actual conversion of data:
 *
 * URL: /course/short-name/edit
 * Pattern: e.g: course\/(.*?)\/(.*+)
 * Conversion: /course/{$2}.php?id={$1}
 * Params:
 * $1 - convert('table', 'course', 'shortname', 'id') - Convert the data using db table, look in mdl_course, for shortname = $1 and return the id.
 * $2 - PLAIN
 *
 *
 * Possible conversions:
 *
 * DB Table: table, tablename, inputfield, outputfield
 *      E.g. convert('db', 'user', 'username', 'id')
 *      This will do: $record = $DB->get_record('user', array('username' => $data)); return $record->id;
 * Method: hook, filetoinclude, method
 *      E.g. convert('hook', '/mod/my_mod/classes/my_mod.php', 'my_mod::my_function')
 *      Method must be static or just a global function.
 *      This will do: require_once($CFG->dirroot . '/mod/my_mod/classes/my_mod.php'); return my_mod::my_function($data);
 *
 */

$params = required_param('qs', PARAM_TEXT);

$url = local_df_url\router::route($params);
var_dump($url);
// if ($url !== false) {
//     redirect($url);
// } else {
//     redirect( new moodle_url() );
// }


