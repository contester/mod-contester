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
 * Define all the backup steps that will be used by the backup_contester_activity_task
 *
 * @package   mod_contester
 * @category  backup
 * @copyright 2015 Your Name <your@email.adress>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete contester structure for backup, with file and id annotations
 *
 * @package   mod_contester
 * @category  backup
 * @copyright 2015 Your Name <your@email.adress>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_contester_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // Get know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define the root element describing the contester instance.
        $contester = new backup_nested_element('contester', array('id'), array(
            'name', 'intro', 'introformat', 'publish',
            'showresults', 'display', 'allowupdate', 'showunanswered',
            'limitanswers', 'timeopen', 'timeclose', 'timemodified',
            'completionsubmit', 'showpreview', 'includeinactive'));

        // If we had more elements, we would build the tree here.
      
        //$options = new backup_nested_element('options');
        //$option = new backup_nested_element('option', array('id'), array(
        //    'text', 'maxanswers', 'timemodified'));
        //$answers = new backup_nested_element('answers');
        //$answer = new backup_nested_element('answer', array('id'), array(
        //    'userid', 'optionid', 'timemodified'));
        // Build the tree
        //$contester->add_child($options);
        //$options->add_child($option);
        //$contester->add_child($answers);
        //$answers->add_child($answer);

        // Define data sources.
        $contester->set_source_table('contester', array('id' => backup::VAR_ACTIVITYID));
            
        //$option->set_source_table('contester_options', array('contesterid' => backup::VAR_PARENTID), 'id ASC');
        // All the rest of elements only happen if we are including user info
        //if ($userinfo) {
        //    $answer->set_source_table('contester_answers', array('contesterid' => '../../id'));
        //}
        
        // Define id annotations
        //$answer->annotate_ids('user', 'userid');             

        // If we were referring to other tables, we would annotate the relation
        // with the element's annotate_ids() method.

        // Define file annotations (we do not use itemid).
        $contester->annotate_files('mod_contester', 'intro', null);

        // Return the root element (contester), wrapped into standard activity structure.
        return $this->prepare_activity_structure($contester);
    }
}
