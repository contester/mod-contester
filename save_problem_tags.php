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

    // TODO: don't do this! call contester_problem_details as a function which builds form, return error if not found.
    if (!$DB->record_exists('contester_problems', ['id' => $pid]))
        print_error('No such problem!');


    $PAGE->set_url('/mod/contester/save_problem_tags.php');
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");
    $PAGE->navbar->add("$contester->name");
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);

/// Print the main part of the page

	$dels = optional_param('tagsdel', array(), PARAM_TAGLIST);
	//print_r($dels);
	foreach ($dels as $item)
	{
		$DB->delete_records('contester_tagmap', array('id' => $item));
	}
	$adds = optional_param('tagsadd', array(), PARAM_TAGLIST);
	//print_r($adds);
	foreach ($adds as $item)
	{
		$tagmr = new stdClass();
		$tagmr->problemid = $pid;
		$tagmr->tagid = $item;
		$tmid = $DB->insert_record('contester_tagmap', $tagmr);
	}

	redirect("problem_preview.php?a=$contester->id&pid=$pid", get_string('updatesuccess', 'contester'), 2);

/// Finish the page
    contester_print_end();

    echo $OUTPUT->footer()

?>
