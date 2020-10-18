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
 * This form displays the table of editable URL conversions.
 *
 * @package    local_df_url
 * @copyright  2020 onwards Conn Warwicker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_df_url\output;

use local_df_url\record, moodle_url, plugin_renderer_base, stdClass;

defined('MOODLE_INTERNAL') or die();

class renderer extends plugin_renderer_base {

    /**
     * Render the table of URLs with edit/delete/add buttons
     * @return bool|string
     * @throws \moodle_exception
     */
    public function render_table() {

        global $OUTPUT;

        $data = ['rows' => []];
        $data['url_add'] = new moodle_url('/local/df_url/edit.php');

        $records = record::get_all();
        foreach ($records as $record) {

            $row = new stdClass();
            $row->id = $record->id;
            $row->enabled = ($record->enabled) ? $OUTPUT->image_url('t/go') : $OUTPUT->image_url('t/stop');
            $row->enabled_alt = ($record->enabled) ? get_string('enabled', 'local_df_url') : get_string('disabled', 'local_df_url');
            $row->ordernum = $record->ordernum;
            $row->type = $record->type;
            $row->regex = $record->regex;
            $row->conversion = $record->conversion;
            $row->url_edit = new moodle_url('/local/df_url/edit.php', array(
                'id' => $record->id
            ));
            $row->img_edit = $OUTPUT->image_url('t/edit');
            $row->url_delete = new moodle_url('/local/df_url/manage.php', array(
                'action' => 'delete',
                'id' => $record->id,
                'sesskey' => sesskey()
            ));
            $row->img_delete = $OUTPUT->image_url('t/delete');
            $data['rows'][] = $row;
        }

        return $this->render_from_template('local_df_url/table', $data);

    }

}