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
 * This is the router class which handles all re-routing to moodle urls
 *
 * @package    local_df_url
 * @copyright  2020 onwards Conn Warwicker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_df_url;

use moodle_url, stdClass;

defined('MOODLE_INTERNAL') || die;

class router {

    /**
     * Take the query string passed to the router and work out which page we want to load.
     *
     * @param string $querystring
     * @return moodle_url|false
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function route(string $querystring) {

        // Find all the patterns we have defined in the database and see if any of them lead to a converted url.
        $records = record::get_enabled();
        if ($records) {

            foreach ($records as $record) {

                $url = self::convert_url($querystring, $record);
                if ($url !== false) {
                    return $url;
                }

            }

        }

        return false;

    }

    /**
     * Invert a Moodle url to a nice url.
     * @param string $url Moodle url string.
     * @return false|moodle_url
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function invert_route(string $url) {

        // Find all the patterns we have defined in the database and see if any of them lead to a converted url.
        $records = record::get_enabled();
        if ($records) {

            foreach ($records as $record) {

                $newurl = self::invert_url($record, $url);
                if ($newurl !== false) {
                    return $newurl;
                }

            }

        }

        return false;

    }

    /**
     * Try to convert the query string to a url, using the supplied database record.
     *
     * This takes a "nice" url from the querystring and works out the actual Moodle URL it should be loading.
     *
     * @param string $querystring E.g. course/shortname/glossary/71-test-assignment/view
     * @param stdClass $record Record from the local_df_urls table
     * @return moodle_url|false E.g. http://yourmoodle.com/mod/glossary/view.php?id=71
     * @throws \moodle_exception
     */
    public static function convert_url(string $querystring, record $record) {

        global $CFG;

        // See if the url matches this pattern.
        preg_match('/' . $record->regex . '/', $querystring, $matches);

        // If there are no matches with this pattern, then return false and we can try the next one.
        if (!$matches) {
            return false;
        }

        // Remove first match, as that's the whole thing and we only want the individual variables.
        unset($matches[0]);

        $params = $record->conversionparams;
        $url = $CFG->wwwroot . $record->conversion;

        // Loop through matches and convert
        foreach ($matches as $number => $value) {

            // Get the params info for this parameter number.
            $info = self::get_param_data($params, $number);
            if (!is_null($info)) {

                $newvalue = self::get_value($value, $info);

                // If any of the values are NULL, then the routing failed.
                if (is_null($newvalue)) {
                    return false;
                }

                $url = str_replace('${' . $number . '}', $newvalue, $url);

            }

        }

        $url = new moodle_url($url);
        return $url;

    }

    /**
     * Given the pattern of a nice url and a non-nice URL, convert the url to a nice one.
     *
     * @param record $record Record from the local_df_urls table.
     * @param string $url E.g. /course/view.php?id=123
     * @return moodle_url|false E.g. /course/shortname/view
     * @throws \moodle_exception
     */
    public static function invert_url(record $record, string $url) {

        $pattern = $record->invert_conversion();

        // Does the URL we found on the page, match the inverted conversion?
        if (preg_match($pattern, $url, $matches)) {

            var_dump($url);
            var_dump($pattern);

            // Remove first match, as that's the whole thing and we only want the individual variables.
            unset($matches[0]);

            // Okay, so that means we want to now convert the normal Moodle URL to the nice one.
            // Start by getting the variables out of the simple property of the record.
            if (preg_match_all('/\$\{(\d)\}/', $record->simple, $simplematches)) {

                // Start with the readable nice url from the record and convert the variables.
                $niceurl = $record->simple;

                // Loop through the simple matches.
                foreach ($simplematches[1] as $number) {

                    // Does this number exist in the inversion params?
                    $info = self::get_param_data($record->inversionparams, $number);

                    // Or does it exist on the main conversion params?
                    if (is_null($info)) {
                        $info = self::get_param_data($record->conversionparams, $number);
                    }

                    if (!is_null($info)) {

                        $key = (isset($info->use)) ? $info->use : $number;
                        $value = self::get_value($matches[$key], $info);

                        $niceurl = str_replace('${' . $number . '}', $value, $niceurl);

                    }

                }

                return new moodle_url($niceurl);

            }

        }

        return false;

    }

    /**
     * Get the data for a specific parameter number from a local_df_url record's params field.
     *
     * @param array $params
     * @param int $number
     * @return \stdClass|null
     */
    public static function get_param_data(array $params, int $number) {

        if ($params) {
            foreach ($params as $param) {
                if ($param->id == $number) {
                    return $param;
                }
            }
        }

        return null;

    }

    /**
     * Given a value and it's parameter type, convert it into what we want returned.
     *
     * @param string $value
     * @param \stdClass $info
     * @return string|null
     */
    public static function get_value(string $value, \stdClass $info) {

        $type = strtolower($info->type);

        // Do we want to convert the value into something else?
        if ($type === 'convert') {

            return self::convert_variable($value, $info->data);

        } else {

            // Is the value supplied empty but there was a default value defined?
            if ($value === '' && isset($info->default)) {
                $value = $info->default;
            }

            // Any other type and we assume 'plain'.
            // In which case, we do nothing but urlencode it.
            return urlencode($value);

        }

    }

    /**
     * Convert a query string variable.
     * This can be used to do things like convert ids to names and visa versa.
     *
     * @param string $value This is the value to be converted
     * @param array $data This is an array containing the conversion data, such as type
     * @return string|null
     */
    public static function convert_variable(string $value, array $data) {

        // Does this conversion method exist?
        $method = 'convert_' . $data[0];
        if (method_exists('\local_df_url\converter', $method)) {
            array_shift($data);
            return \local_df_url\converter::$method($value, $data);
        }

        return null;

    }

}