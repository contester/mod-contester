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

    require('../../config.php');
    require_once('lib.php');

    $id = optional_param('id', 0, PARAM_INT); // Course module ID, or
    $a  = optional_param('a',  0, PARAM_INT); // ...contester instance ID

    if ($id) {
        list ($course, $cm) = get_course_and_cm_from_cmid($id, 'contester');
        $contester = $DB->get_record('contester', array('id'=> $cm->instance), '*', MUST_EXIST);
    }
    else {
        if(! $contester = $DB->get_record('contester', array('id' => $a))) {
            print_error(get_string("incorrect_contester_id", "contester"));
        }
        if(! $course = $DB->get_record('course', array('id' => $contester->course))) {
            print_error(get_string("misconfigured_course", "contester"));
        }
        if(! $cm = get_coursemodule_from_instance('contester', $contester->id, $course->id)) {
            print_error(get_string("incorrect_cm_id", "contester"));
        }
    }

    require_login($course->id);

    // Print the page header.

    $PAGE->set_url('/mod/contester/view.php', array('a' => $contester->id));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading($course->fullname);

    $PAGE->navbar->add("$contester->name");

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

    contester_print_begin($contester->id, $contester->name);

    $problem_list = $DB->get_recordset_sql("SELECT
	problemmap.id as id,
	problems.name as name
        from {contester_problems} problems,
	{contester_problemmap} problemmap
	WHERE problemmap.problemid=problems.id and
        problemmap.contesterid=? order by problemmap.id", array($contester->id));
    if ($problem_list->valid()) {
        echo "<table width = 90% border=1 bordercolor=black cellpadding=5><tr><td>".get_string('number', 'contester').
             "</td><td>".get_string('name','contester')."</td><td>".
             get_string('action', 'contester')."</td></tr>";

        $i = 1;
        foreach ($problem_list as $problem) {
            echo "<tr valign><td align=center valign=middle>".($i++)."</td><td valign=middle align=left><nobr>".
                 $problem->name."</td><td  align=center valign=middle nobr>";
            echo "<a href=problem.php?a={$contester->id}&pid={$problem->id}>".
                 get_string('problemstatement', 'contester')."</a>";
            echo "<form enctype=\"multipart/form-data\" method=\"post\" action=\"submit_form.php?pid={$problem->id}&a={$contester->id}\">";
            echo '<table cellpadding="5"><tbody>';
            echo '<tr><td colspan="2" align="center"><input type="submit" value="'.get_string('submit', 'contester').'"></input></td></tr>';
            echo '</tbody></table></form>';
            echo "</nobr></td></tr>";
        }
        echo "</table>";
    }
    else print_string('noproblems', 'contester');

    $problem_list->close();

    contester_print_end();

// Finish the page.
    echo $OUTPUT->footer();

?>
