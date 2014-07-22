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
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // contester instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('contester', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $contester  = $DB->get_record('contester', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $contester  = $DB->get_record('contester', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $contester->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('contester', $contester->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

add_to_log($course->id, 'contester', 'view', "view.php?id={$cm->id}", $contester->name, $cm->id);

/// Print the page header

$PAGE->set_url('/mod/contester/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($contester->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// other things you may want to set - remove if not needed
//$PAGE->set_cacheable(false);
//$PAGE->set_focuscontrol('some-html-id');
//$PAGE->add_body_class('contester-'.$somevar);

// Output starts here
echo $OUTPUT->header();

if ($contester->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('contester', $contester, $cm->id), 'generalbox mod_introbox', 'contesterintro');
}
/*
 if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strcontesters</a> -> $contester->name",
                  "", "", true,
                  update_module_button($cm->id, $course->id, $strcontester),
                  navmenu($course, $cm));

*/

// main part
echo "<br><br>";
	echo $contester->description;
	echo "<br><br>";
	contester_print_begin($contester->id);
    $sql = "SELECT mdl_contester_problemmap.id as id, mdl_contester_problems.name as name from mdl_contester_problems, mdl_contester_problemmap
			WHERE  mdl_contester_problemmap.problemid=mdl_contester_problems.id and
				   mdl_contester_problemmap.contesterid=$contester->id order by mdl_contester_problemmap.id";
    $problem_list = $DB->get_recordset_sql($sql);
    if (!$problem_list->EOF)
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
    } else print_string('noproblems', 'contester');



// Finish the page
echo $OUTPUT->footer();

?>
