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
 * This class handles records in the local_df_urls table.
 *
 * @package    local_df_url
 * @copyright  2020 onwards Conn Warwicker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_df_url;

defined('MOODLE_INTERNAL') || die;

/**
 * This class handles records in the local_df_urls table.
 *
 * @package    local_df_url
 * @copyright  2020 onwards Conn Warwicker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class record {

    /**
     * @var int|false Record id.
     */
    private $id = false;

    /**
     * @var string Record type, e.g. 'core_course', 'core_mod', etc...
     */
    private $type;

    /**
     * @var string Regex string to match a nice url. E.g. ^course\/([a-z0-9\-]+)\/?(.*+)
     */
    private $regex;

    /**
     * @var string The nice url in a more readable format, rather than regex. E.g. /course/${1}/${2}
     */
    private $simple;

    /**
     * @var string URL string with variable placeholders to be replaced by matching groups from the pattern.
     */
    private $conversion;

    /**
     * @var array Array of parameters for the record: [id, type, data, default] to be used converting nice url to real url.
     */
    private $conversionparams;

    /**
     * @var array Array of parameters for the record: [id, type, data, default] convering real url to nice url.
     */
    private $inversionparams;

    /**
     * @var bool Whether the record is enabled or not.
     */
    private $enabled;

    /**
     * @var float Order number for making sure some patterns are checked before others.
     */
    private $ordernum;

    /**
     * Construct the record object from the database record ID
     *
     * @param int $id
     * @throws \dml_exception
     */
    public function __construct(int $id = 0) {

        global $DB;

        if ($id) {

            $record = $DB->get_record('local_df_urls', array('id' => $id));
            if ($record) {

                $this->id = $record->id;
                $this->type = $record->type;
                $this->regex = $record->regex;
                $this->simple = $record->simple;
                $this->conversion = $record->conversion;
                $this->conversionparams = json_decode($record->conversionparams);
                $this->inversionparams = json_decode($record->inversionparams);
                $this->enabled = (bool)$record->enabled;
                $this->ordernum = $record->ordernum;

            }

        }

    }

    /**
     * Check if the record exists or not.
     * @return bool
     */
    public function exists() : bool {
        return ($this->id !== false);
    }

    /**
     * Get a property from the object
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        if (property_exists($this, $name)) {
            return $this->{$name};
        } else {
            return null;
        }
    }

    /**
     * Invert the conversion by changing the placeholders to regex matching groups
     * For example change the conversion string:
     *      /course/${2}.php?id=${1}
     * to:
     *      /\/course\/(.*?)\.php\?id\=(.*?)/U
     *
     * @return string
     */
    public function invert_conversion() {

        global $CFG;

        // Replace variables with a temporary replacement, which will then in turn be replaced with a matching group.
        $pattern = preg_replace('/\$\{(.*?)\}/', '_DF_URL_REPLACEMENT_', $this->conversion);

        // Prepend the wwwroot to the beginning of the pattern, as we don't want to convert links to other sites.
        $pattern = $CFG->wwwroot . $pattern;

        // Add backslashes in front of regex key characters, so as not to break it.
        $pattern = preg_quote($pattern);

        // That doesn't include the forward slash, so do that manually.
        $pattern = str_replace('/', '\/', $pattern);

        // Finally replace that temporary replacement with the regex to match a group.
        $pattern = preg_replace('/_DF_URL_REPLACEMENT_/', '(.*?)', $pattern);

        // Prepend ^ to match the beginning of the string. Can't do that earlier or it gets escaped by preg_quote.
        $pattern = '^' . $pattern;

        // Make it an Ungreedy match.
        $pattern = '/'.$pattern.'/U';

        return $pattern;

    }

    /**
     * Get all the enabled records
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_enabled() : array {

        global $DB;

        $return = [];
        $records = $DB->get_records('local_df_urls', array('enabled' => 1), 'ordernum DESC', 'id');
        foreach ($records as $record) {
            $return[$record->id] = new record($record->id);
        }

        return $return;

    }

    /**
     * Get all the enabled records
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_all() : array {

        global $DB;

        $return = [];
        $records = $DB->get_records('local_df_urls', null, 'enabled DESC, type ASC, ordernum DESC', 'id');
        foreach ($records as $record) {
            $return[$record->id] = new record($record->id);
        }

        return $return;

    }

    /**
     * Delete a route from the database and remove it from the cache.
     * @return bool
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function delete() : bool {

        global $DB;

        // Delete from the cache.
        static::delete_cache($this->id);

        // Delete the database record.
        return $DB->delete_records('local_df_urls', array('id' => $this->id));

    }

    /**
     * Delete any cached conversions/inversions using this route record
     * @param int $id ID of the record to delete
     * @return void
     * @throws \coding_exception
     */
    public static function delete_cache(int $id) : void {

        // If we are using caching, remove any conversions or inversions.
        if (router::using_caching()) {

            // Loop through cached elements and delete any which were using this id.
            $cache = router::get_cache();
            $types = $cache->get_many(['converted', 'inverted']);

            // Loop through the types (converted and inverted) and then through their cached conversions/inversions.
            if ($types) {
                foreach ($types as $type => $conversions) {
                    if ($conversions) {
                        foreach ($conversions as $path => $url) {
                            // If it was converted/inverted from this record, remove it from the cached array.
                            if ($url->_local_df_url_id == $id) {
                                unset($types[$type][$path]);
                            }
                        }
                    }
                }
            }

            // Set alerted arrays back into the cache.
            $cache->set('converted', $types['converted']);
            $cache->set('inverted', $types['inverted']);

        }

    }

}