<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// This page prints a particular instance of contester
/// (Replace contester with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID

    global $DB;

    if ($id) {
        if (! $cm = $DB->get_record("course_modules", array("id" => $id))) {
            print_error("Course Module ID was incorrect");
        }

        if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
            print_error("Course is misconfigured");
        }

        if (! $contester = $DB->get_record("contester", array("id" => $cm->instance))) {
            print_error("Course module is incorrect");
        }

    } else {
        if (! $contester = $DB->get_record("contester", array("id" => $a))) {
            print_error("Course module is incorrect");
        }
        if (! $course = $DB->get_record("course", array("id" => $contester->course))) {
            print_error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("contester", $contester->id, $course->id)) {
            print_error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);
    //add_to_log($course->id, "contester", "details", "details.php?id=$cm->id", "$contester->id");
    $context = context_module::instance($cm->id);
    $is_admin = has_capability('moodle/site:config', $context);

    if (!$is_admin) print_error(get_string('accessdenied', 'contester'));
    $pid = required_param('pid', PARAM_INT);

/// Print the page header

	$sql = "SELECT mdl_contester_problems.name as name
			FROM   mdl_contester_problems
			WHERE  mdl_contester_problems.id=?";
	if (!$problem = $DB->get_record_sql($sql, array($pid))) print_error('No such problem!');

    $PAGE->set_url('/mod/contester/problem_tag_details.php', array('a' => $a, 'pid' => $pid));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");
    $PAGE->navbar->add("$contester->name");
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);

    echo $OUTPUT->header();

/// Print the main part of the page

	echo '<form action=save_problem_tags.php method="POST">';

	echo '<span id=textheader>'.get_string('deltags', 'contester').':</span><span id=tags>';
	contester_show_problem_tags_to_delete($pid);
	echo '</span>';

	echo '<br>';
	echo '<br>';

	echo '<span id=textheader>'.get_string('addtags', 'contester').':</span><span id=tags>';
	contester_show_problem_tags_to_add($pid);
	echo '</span><br />';

	echo '<br>';

	echo '<input type=submit value="'.get_string('save', 'contester').'">';
	echo '<input type=hidden name="pid" value="'.$pid.'">';
	echo '<input type=hidden name="a" value="'.$contester->id.'">';
	echo '</form>';

/// Finish the page
    contester_print_end();

    echo $OUTPUT->footer()

?>
