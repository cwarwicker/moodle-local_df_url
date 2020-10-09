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
 * This is the converter class which handles converting values in the urls.
 * @package    local_df_url
 * @copyright  2020 onwards Conn Warwicker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_df_url;

defined('MOODLE_INTERNAL') || die;

class converter {

    /**
     * Convert a value by using a database table and a from and to field.
     * @param string $value The value to be converted
     * @param array $data This array should contain exactly 3 elements: [table, fieldfrom, fieldto]
     * @return mixed
     * @throws \dml_exception
     */
    public static function convert_db(string $value, array $data) {

        global $DB;

        if (count($data) <> 3) {
            return null;
        }

        $table = $data[0];
        $input = $data[1];
        $output = $data[2];

        $record = $DB->get_field($table, $output, array($input => $value));
        return ($record) ? $record : null;

    }

    /**
     * Convert a value by using a function or class method
     * @param $value The value to be converted
     * @param array $data This array should contain exactly 2 elements. These should be: [filepath, functionname].
     *                    E.g.
     *                      ["local/my_plugin/my_file.php", "my_function"]
     *                      ["local/my_plugin/classes/my_class.php", "local_my_plugin\my_class::my_method"]
     * @return string|null
     */
    public static function convert_hook($value, array $data) {

        global $CFG;


        // There must be 2 elements in the $data array - file and function/method.
        if (count($data) <> 2) {
            return null;
        }

        $file = $data[0];
        $func = $data[1];

        // Add preceding slash to file, if it doesn't have one.
        if (strpos($file, DIRECTORY_SEPARATOR) !== 0) {
            $file = DIRECTORY_SEPARATOR . $file;
        }

        $file = $CFG->dirroot . $file;

        // If the file doesn't exist, then we can't call a method from it.
        if (!file_exists($file)) {
            return null;
        }

        // Require the file so we can access the functions/classes in it.
        // We don't want any output it might bring with it though, so wrap it in ob_start and ob_end_clean.
        ob_start();
        require_once($file);
        ob_end_clean();

        // If it's a class method.
        if (strpos($func, '::') !== false) {

            $explode = explode('::', $func);
            $class = $explode[0];
            $method = $explode[1];

            // Check it's a valid class method.
            if (!method_exists($class, $method)) {
                return null;
            }

            // Call the method and pass the value through.
            return urlencode( $class::$method($value) );

        } else {

            // Must be a global function then.

            // Check if it's callable.
            if (!is_callable($func)) {
                return null;
            }

            // Call the function and pass the value through.
            return urlencode( $func($value) );

        }

    }

}