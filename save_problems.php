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
    $is_teacher = has_capability('moodle/course:viewhiddenactivities', $context);    
    
    if (!$is_admin && !$is_teacher) print_error(get_string('accessdenied', 'contester'));

/// Print the page header

	$sql = "SELECT mdl_contester.id as id, 
				   mdl_contester.name as name
			FROM   mdl_contester
			WHERE  mdl_contester.id=?";
	if (!$contester = $DB->get_record_sql($sql, array($a))) print_error('No such problem!');

    $PAGE->set_url('/mod/contester/save_problems.php');
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");
    $PAGE->navbar->add("$contester->name");
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);
    $PAGE->set_button(update_module_button($cm->id, $course->id, get_string("modulename", "contester")));                  

/// Print the main part of the page

	$dels = optional_param('probsdel', array(), PARAM_TAGLIST);
	foreach ($dels as $item)
	{
		$DB->delete_records('contester_problemmap', array('problemid'=>$item, 'contesterid'=>$a));
	}

	$adds = optional_param('probsadd', array(), PARAM_TAGLIST);
	foreach ($adds as $item)
	{
		if (!$DB->get_record('contester_problemmap', array('problemid'=>$item, 'contesterid'=>$a)))
		{
    		$probmr = new stdClass();
    		$probmr->problemid = $item;
    		$probmr->contesterid = $a;
    		$DB->insert_record('contester_problemmap', $probmr);
    	}
	}
	
	$freeview = optional_param('freeview', 0, PARAM_INT);
	$viewown = optional_param('viewown', 0, PARAM_INT);	
	
	$contester = $DB->get_record('contester', array('id' => $a));
	$contester->freeview = $freeview;
	$contester->viewown = $viewown;
	
	$DB->update_record('contester', $contester);
	
	redirect("view.php?a=$contester->id", get_string('updatesuccess', 'contester'), 2);

/// Finish the page
	contester_print_end();
    //print_footer($course);
    echo $OUTPUT->footer()

?>
