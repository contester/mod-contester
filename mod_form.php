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
 * The main contester configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_contester
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_contester
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_contester_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', 'Контестер', array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'contestername', 'contester');
		
        // Adding the standard "intro" and "introformat" fields.
        //$this->add_intro_editor();
        //$this->standard_intro_elements();
		

        // Adding the rest of contester settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.
		
        //$mform->addElement('static', 'label1', 'contestersetting1', 'Hello world');
		
		$mform->addElement('static', 'label1', 'Доступные задачи', 'Отметьте задачи, которые нужно удалить');
		
		$sql = "SELECT   mdl_contester_problems.name as name,
				 mdl_contester_problemmap.id as id,
				 mdl_contester_problems.id as pid,
				 mdl_contester_problems.dbid as dbid
		FROM	 mdl_contester_problems, mdl_contester_problemmap
		WHERE	 mdl_contester_problemmap.problemid=mdl_contester_problems.id
		AND		 mdl_contester_problemmap.contesterid=?
		ORDER BY mdl_contester_problemmap.id";

		global $DB;
		global $COURSE;
		
		$contesterId = 1;
		
    	$res = $DB->get_records_sql($sql, array($contesterId));
	
		$context = context_module::instance($COURSE->id);
    	$is_admin = has_capability('moodle/site:config', $context);
		
		foreach ($res as $line)
		{
			$name = $line->name;
			$out = "<tr><td><input type=checkbox name=\"pid".$line->id."\" value=checked></td><td size=60%>
			<nobr>$name</nobr></td>";
			if ($is_admin) 
				$out .= "<td size=40%><nobr>
				<a href=../mod/contester/problem_details.php?a=.$contesterId.&pid=".$line->pid.">".
				get_string('problemdetails', 'contester')." (".$line->dbid.")</a></nobr></td>";
			$out .= "</tr>";
			
			$mform->addElement('static', 'label1', '', $out);		
		}
		
        /*echo '<tr valign="top">';
    	echo '<td align="right"><b>'.get_string('addproblem', 'contester').':</b></td>';
    	echo '<td>';
        $res = $DB->get_records_sql("SELECT   mdl_contester_problems.id as pr_id,
        								 mdl_contester_problems.dbid as dbid,
        							     mdl_contester_problems.name as name
        						FROM     mdl_contester_problems
        						ORDER BY mdl_contester_problems.dbid");
        foreach ($res as $line){
        	$choices[$line->pr_id] = $line->dbid." ".$line->name;
        }
        contester_choose_from_list($choices, 'add_problem[]', true, 20); //multiple + 20 rows
        echo '</td></tr>';*/
		
        $mform->addElement('header', 'contesterfieldset', get_string('contesterfieldset', 'contester'));
        $mform->addElement('static', 'label2', 'contestersetting2', 'Your contester fields go here. Replace me!');
		
        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
