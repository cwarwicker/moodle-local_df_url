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

$path = required_param('qs', PARAM_RAW);
$url = local_df_url\router::route($path);

if ($url !== false) {

    // Put params back into GET global.
    foreach ($url->params() as $key => $val) {
        $_GET[$key] = $val;
    }

    $file = $url->out_omit_querystring();

    // Remove wwwroot.
    if (strpos($file, $CFG->wwwroot) === 0) {
        $file = $CFG->dirroot . substr($file, strlen($CFG->wwwroot));
    } else {
        $file = false;
    }

    // If it's a valid file, require it.
    if (is_file($file)) {

        // Change to the directory of the file, so require statements are valid inside the file(s).
        chdir(dirname($file));

        // Require the file that would normally be loaded up by going to that url.
        require_once($file);

        exit;

    }

}

// If we got this far, there was a problem with the routing.
// If we have debugging enabled, display an error page. Otherwise, redirect to index.
if ($CFG->debugdisplay) {

    header('HTTP/1.0 404 Not Found');
    $PAGE->set_url( ($url) ? $url->out() : ($CFG->wwwroot . $_SERVER['REQUEST_URI']) );
    $PAGE->set_context( context_course::instance(SITEID) );
    $PAGE->set_title( get_string('error404', 'local_df_url') );
    $PAGE->set_heading( get_string('error404', 'local_df_url') );
    echo $OUTPUT->header();
    echo get_string('error404:info', 'local_df_url', s($_SERVER['REQUEST_URI']));
    echo $OUTPUT->footer();
    exit;

} else {
    redirect( new moodle_url('/') );
}

