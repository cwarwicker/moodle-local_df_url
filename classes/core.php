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
 * This class contains simple methods to convert core elements, such as course_module ids into the activity name, etc...
 *
 * @package    local_df_url
 * @copyright  2020 onwards Conn Warwicker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_df_url;

defined('MOODLE_INTERNAL') || die;

/**
 * This class contains simple methods to convert core elements, such as course_module ids into the activity name, etc...
 *
 * @package    local_df_url
 * @copyright  2020 onwards Conn Warwicker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core
{

    /**
     * Get the name of an activity from its course_module id.
     * @param int $id Course module id
     * @return string|null
     * @throws \moodle_exception
     */
    public static function course_module_name(int $id) {
        $cm = get_course_and_cm_from_cmid($id);
        return isset($cm[1]->name) ? $cm[1]->name : null;
    }

}