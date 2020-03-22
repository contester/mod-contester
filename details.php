<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// This page prints a particular instance of contester
/// (Replace contester with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT);  // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID
    $sid = required_param('sid', PARAM_INT);  // submit ID
    
    global $DB;

    if ($id) {
        if (! $cm = $DB->get_record('course_modules', array('id' => $id))) {
            print_error("Course Module ID was incorrect");
        }

        if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
            print_error("Course is misconfigured");
        }

        if (! $contester = $DB->get_record('contester', array('id' => $cm->instance))) {
            print_error("Course module is incorrect");
        }

    } else {
        if (! $contester = $DB->get_record('contester', array('id' => $a))) {
            print_error("Course module is incorrect");
        }
        if (! $course = $DB->get_record('course', array('id' => $contester->course))) {
            print_error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("contester", $contester->id, $course->id)) {
            print_error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    //add_to_log($course->id, "contester", "status", "status.php?id=$cm->id", "$contester->id");

/// Print the page header

    $PAGE->set_url('/mod/contester/details.php', array('a' => $a, 'sid' => $id));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");

    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $a));
    $PAGE->navbar->add("$contester->name", $contester_url);
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);
    $PAGE->set_button(update_module_button($cm->id, $course->id, get_string("modulename", "contester")));
    
    echo $OUTPUT->header();
                  

/// Print the main part of the page
	contester_print_begin($contester->id);    
    
    // Heading
    $sr = $DB->get_record_sql("SELECT  problems.name as problem_name,
	                               user.firstname as firstname,
				       user.lastname as lastname,
				       submits.submitted_uts as submitted_uts
                               FROM    {contester_submits} submits,
				       {contester_problems} problems,
				       {user} user
                               WHERE
				       submits.problem=problems.dbid AND
                                       user.id = submits.student AND
                                       submits.id=?", array($sid));
    echo $sr->firstname . ' ' . $sr->lastname . ' ' . $sr ->problem_name . ' ' . userdate($sr->submitted_uts) . '<br/><br/>';

    // Table
	$result = contester_get_detailed_info($sid);
	echo "<p>";
	contester_draw_assoc_table($result);
	echo "</p>";

/// Finish the page
	contester_print_end();
    echo $OUTPUT->footer();
?>
