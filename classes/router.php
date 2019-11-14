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
 * @package    local_df_url
 * @copyright  2019 Conn Warwicker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_df_url;

defined('MOODLE_INTERNAL') || die;

class router {

    public static function route($querystring) {

        global $DB;

        $records = $DB->get_records('df_urls', array('enabled' => 1));
        if ($records) {

            foreach($records as $record) {

                $url = self::convert_url($querystring, $record);
                if ($url !== false) {
                    return $url;
                }

            }

        }

        return false;

    }

    public static function convert_url($querystring, $record) {

        global $CFG;

        // See if the url matches this pattern.
        preg_match('/' . $record->pattern . '/', $querystring, $matches);

        // If there are no matches with this pattern, then return false and we can try the next one.
        if (!$matches) {
            return false;
        }

        // Remove first match, as that's the whole thing and we only want the individual variables.
        unset($matches[0]);

        $params = json_decode($record->params);
        $url = $CFG->wwwroot . $record->conversion;

        // Loop through matches and convert
        foreach ($matches as $number => $value) {

            // Get the params info for this parameter number.
            $info = self::get_param_data($params, $number);
            if (!is_null($info)) {

                $newvalue = self::get_value($value, $info->type, (isset($info->data)) ? $info->data : null);

                // If any of the values are NULL, then the routing failed.
                if (is_null($newvalue)) {
                    return false;
                }

                $url = str_replace('${' . $number . '}', $newvalue, $url);

            }

        }

        $url = new \moodle_url($url);
        return $url;

    }

    public static function get_param_data($params, $number) {

        foreach ($params as $param) {
            if ($param->id == $number) {
                return $param;
            }
        }

        return null;

    }

    public static function get_value($value, $type, $data = null) {

        $type = strtolower($type);

        // Do we want to convert the value into something else?
        if ($type === 'convert') {

            return self::convert_variable($value, $data);

        } else {

            // Any other type and we assume 'plain'.
            // In which case, we do nothing but urlencode it.
            return urlencode($value);

        }


    }

    public static function convert_variable($value, array $data) {

        // Type of conversion.
        $type = (isset($data[0])) ? $data[0] : false;

        // Does this conversion method exist?
        $method = 'convert_' . $type;
        if (method_exists('\local_df_url\converter', $method)) {
            array_shift($data);
            return \local_df_url\converter::$method($value, $data);
        }

        return null;

    }

}