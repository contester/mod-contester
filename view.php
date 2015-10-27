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

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // ... contester instance ID - it should be named as the first character of the module.

if ($id) {
    if(! $cm = get_coursemodule_from_id('contester', $id))
	{
		error("Course Module ID was incorrect");
	}
    if(! $course = $DB->get_record('course', array('id' => $cm->course)))
	{
		error("Course is misconfigured");
	}
    if(! $contester = $DB->get_record('contester', array('id' => $cm->instance)))
	{
		error("Course module is incorrect");
	}
} 
else 
{
    if(! $contester = $DB->get_record('contester', array('id' => $a)))
	{
		error("Course module is incorrect");
	}
    if(! $course = $DB->get_record('course', array('id' => $contester->course)))
	{
		error("Course is misconfigured");
	}
    if(! $cm = get_coursemodule_from_instance('contester', $contester->id, $course->id))
	{
		 error("Course Module ID was incorrect");
	}
}

require_login($course, true, $cm);

$event = \mod_contester\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $contester);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/contester/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($contester->name));
$PAGE->set_heading(format_string($course->fullname));

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
	echo $contester->description;
	echo "<br><br>";
	contester_print_begin($contester->id);
    $sql = "SELECT mdl_contester_problemmap.id as id, mdl_contester_problems.name as name from mdl_contester_problems, mdl_contester_problemmap
			WHERE  mdl_contester_problemmap.problemid=mdl_contester_problems.id and
				   mdl_contester_problemmap.contesterid=$contester->id order by mdl_contester_problemmap.id";
    $problem_list = $DB->get_recordset_sql($sql);
    if ($problem_list->valid())
    {
    	echo "<table width = 90% border=1 bordercolor=black cellpadding=5><tr><td>".get_string('number', 'contester').
    	"</td><td>".get_string('name','contester')."</td><td>".
    	get_string('action', 'contester')."</td></tr>";

    	$i = 1;
    	foreach ($problem_list as $problem)
    	{
    		echo "<tr valign><td align=center valign=middle>".($i++)."</td><td valign=middle align=left><nobr>".
	    	$problem['name']."</td><td  align=center valign=middle nobr>";
    		//print_single_button("problem.php", array("pid"=>$problem['id'],"a"=>$contester->id), get_string('definition', 'contester'), 'post');
    		contester_print_link_to_problem($contester->id, $problem['id']);
    		print_single_button("submit_form.php", array("pid"=>$problem['id'],"a"=>$contester->id), get_string('submit', 'contester'), 'post');
	    	echo "</nobr></td></tr>";
    	}
    	echo "</table>";
    } 
	else print_string('noproblems', 'contester');
	contester_print_end();
//End new code

// Finish the page.
echo $OUTPUT->footer();
