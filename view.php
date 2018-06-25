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
 * Prints a particular instance of contester
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_contester
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace contester with the name of your module and remove this line.

    require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
    require_once(dirname(__FILE__).'/lib.php');
    //require_once(dirname(__FILE__).'/classes/simplehtml_form.php');
    require_once("$CFG->libdir/formslib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // ... contester instance ID - it should be named as the first character of the module.

    if ($id) {
        if(! $cm = get_coursemodule_from_id('contester', $id))
    	{
    		print_error("Course Module ID was incorrect");
    	}
        if(! $course = $DB->get_record('course', array('id' => $cm->course)))
    	{
    		print_error("Course is misconfigured");
    	}
        if(! $contester = $DB->get_record('contester', array('id' => $cm->instance)))
    	{
    		print_error("Course module is incorrect");
    	}
    } 
    else 
    {
        if(! $contester = $DB->get_record('contester', array('id' => $a)))
    	{
    		print_error("Course module is incorrect");
    	}
        if(! $course = $DB->get_record('course', array('id' => $contester->course)))
    	{
    		print_error("Course is misconfigured");
    	}
        if(! $cm = get_coursemodule_from_instance('contester', $contester->id, $course->id))
    	{
    		 print_error("Course Module ID was incorrect");
    	}
    }

    require_login($course->id);
    //require_login($course, true, $cm);

    /*$event = \mod_contester\event\course_module_viewed::create(array(
        'objectid' => $PAGE->cm->instance,
        'context' => $PAGE->context,
    ));
    $event->add_record_snapshot('course', $PAGE->course);
    $event->add_record_snapshot($PAGE->cm->modname, $contester);
    $event->trigger();*/

    // Print the page header.

    $PAGE->set_url('/mod/contester/view.php', array('id' => $cm->id));
    $PAGE->set_title(format_string($contester->name));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->navbar->add("$contester->name");
    $PAGE->set_button(update_module_button($cm->id, $course->id, get_string("modulename", "contester")));

    /*
     * Other things you may want to set - remove if not needed.
     * $PAGE->set_cacheable(false);
     * $PAGE->set_focuscontrol('some-html-id');
     * $PAGE->add_body_class('contester-'.$somevar);
     */

    // Output starts here.
    echo $OUTPUT->header();
    // Conditions to show the intro can change to look for own settings or whatever.
    if ($contester->intro) {
        echo $OUTPUT->box(format_module_intro('contester', $contester, $cm->id), 'generalbox mod_introbox', 'contesterintro');
    }

	// Replace the following lines with you own code.

	//Start new code
	echo "<br><br>";
	//echo $contester->description;
	echo "Входные данные должны считываться из файла <b>input.txt</b><br>
		Выходные данные выводятся в файл <b>output.txt</b><br>";

	echo "<br><br>";
	contester_print_begin($contester->id);
    //$sql = "";
    $problem_list = $DB->get_recordset_sql('SELECT mdl_contester_problemmap.id as id, mdl_contester_problems.name as name from mdl_contester_problems, mdl_contester_problemmap
			WHERE  mdl_contester_problemmap.problemid=mdl_contester_problems.id and
				   mdl_contester_problemmap.contesterid=? order by mdl_contester_problemmap.id', array($contester->id));
    if ($problem_list->valid())
    {
    	echo "<table width = 90% border=1 bordercolor=black cellpadding=5><tr><td>".get_string('number', 'contester').
    	"</td><td>".get_string('name','contester')."</td><td>".
    	get_string('action', 'contester')."</td></tr>";

    	$i = 1;
    	foreach ($problem_list as $problem)
    	{
    		echo "<tr valign><td align=center valign=middle>".($i++)."</td><td valign=middle align=left><nobr>".
	    	$problem->name."</td><td  align=center valign=middle nobr>";
    		//print_single_button("problem.php", array("pid"=>$problem['id'],"a"=>$contester->id), get_string('definition', 'contester'), 'post');
    		//contester_print_link_to_problem($contester->id, );
    		echo "<a href=problem.php?a={$contester->id}&pid={$problem->id}>".
		   		get_string('problemstatement', 'contester')."</a>";

			//print_single_button("", array("pid"=>$problem['id'],"a"=>$contester->id), get_string('submit', 'contester'), 'post');
			echo "<form enctype=\"multipart/form-data\" method=\"post\" action=\"submit_form.php?pid={$problem->id}&a={$contester->id}\">";
			echo '<table cellpadding="5"><tbody>';
		    echo '<tr><td colspan="2" align="center"><input type="submit" value="'.get_string('submit', 'contester').'"></input></td></tr>';
			echo '</tbody></table></form>';

    		//$form = new moodleform();
            //$form->url = new moodle_url("submit_form.php", array("pid"=>$problem->id,"a"=>$contester->id)); // Required
            //$form->button = new html_button();
            //$form->button->text = get_string('submit', 'contester'); // Required
            //$form->addElement('button', get_string('submit', 'contester'), get_string('submit', 'contester'));
            //$form->button->disabled = $disabled;
            //$form->button->title = $tooltip;
            //$form->method = 'post';
            //$form->id = $formid;
             
            /*if ($jsconfirmmessage) {
                $confirmaction = new component_action('click', 'confirm_dialog', array('message' => $jsconfirmmessage));
                $form->button->add_action($confirmaction);
            }*/
             
            //echo $OUTPUT->button($form);
	    	echo "</nobr></td></tr>";
    	}
    	echo "</table>";
    } 
	else print_string('noproblems', 'contester');
	contester_print_end();
//End new code

// Finish the page.
echo $OUTPUT->footer();
