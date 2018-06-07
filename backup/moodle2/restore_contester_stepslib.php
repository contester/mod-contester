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
 * Define all the restore steps that will be used by the restore_contester_activity_task
 *
 * @package   mod_contester
 * @category  backup
 * @copyright 2015 Your Name <your@email.adress>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one contester activity
 *
 * @package   mod_contester
 * @category  backup
 * @copyright 2015 Your Name <your@email.adress>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_contester_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {@link restore_path_element}
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');
        $paths[] = new restore_path_element('contester', '/activity/contester');
        $paths[] = new restore_path_element('contester_option', '/activity/contester/options/option');
        if ($userinfo) {
            $paths[] = new restore_path_element('contester_answer', '/activity/contester/answers/answer');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_contester($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
      
        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        // Create the contester instance.
        $newitemid = $DB->insert_record('contester', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_contester_option($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->contesterid = $this->get_new_parentid('contester');
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $newitemid = $DB->insert_record('contester_options', $data);
        $this->set_mapping('contester_option', $oldid, $newitemid);
    }
  
    protected function process_contester_answer($data) {
        global $DB;
        $data = (object)$data;
        $data->contesterid = $this->get_new_parentid('contester');
        $data->optionid = $this->get_mappingid('contester_option', $data->optionid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $newitemid = $DB->insert_record('contester_answers', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }
  
    /**
     * Post-execution actions
     */
    protected function after_execute() {
        // Add contester related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_contester', 'intro', null);
    }
}
