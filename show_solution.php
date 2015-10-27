<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// This page prints a particular instance of contester
/// (Replace contester with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID
    $sid = required_param('sid', PARAM_INT);
    
    global $DB;

    if ($id) {
        if (! $cm = $DB->get_record('course_modules', array('id' => $id))) {
            error("Course Module ID was incorrect");
        }

        if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
            error("Course is misconfigured");
        }

        if (! $contester = $DB->get_record('contester', array('id' => $cm->instance))) {
            error("Course module is incorrect");
        }

    } else {
        if (! $contester = $DB->get_record('contester', array('id' => $a))) {
            error("Course module is incorrect");
        }
        if (! $course = $DB->get_record('course', array('id' => $contester->course))) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("contester", $contester->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }
	//echo "botva";
    require_login($course->id);
    //echo "botva";
	add_to_log($course->id, "contester", "show_solution", "show_solution.php?a=$contester->id&sid=$sid", "$contester->id");
	//echo "botva";
	$context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $is_teacher = has_capability('moodle/course:viewhiddenactivities', $context);
    $is_admin = has_capability('moodle/site:config', $context);
    //echo "#".$is_teacher;
    if ((!$is_admin) && (!$is_teacher) && (!get_field('contester', 'freeview', 'id', $contester->id))) {
    	//echo "botva";
        if (!$userid)
        {
        	//echo "botva";
	       	if (empty($USER->id)) {
    	        error('accessdenied', 'contester');
        	}
        	$userid = $USER->id;
    	}
    	$user = get_field_sql("SELECT user.id FROM user as user, contester_submits as submits
    	WHERE submits.id=$sid AND user.id=submits.student");
    	if ($userid != $user) error('accessdenied', 'contester');
    }
    //echo "botva";

/// Print the page header

    /*if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strcontesters</a> -> $contester->name",
                  "", "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/styles.css\" />", true, update_module_button($cm->id, $course->id, $strcontester)),
                  navmenu($course, $cm));*/
                  
    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");    
    
    $PAGE->set_url('/mod/contester/show_solution.php', array('a' => $a, 'sid' => $id));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");
    $PAGE->navbar->add("$contester->name");
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);
    $PAGE->set_button(update_module_button($cm->id, $course->id, $strcontester));
    
    echo $OUTPUT->header();

/// Print the main part of the page
	contester_print_begin($contester->id);

	$r = $DB->get_records_sql('SELECT mcp.name FROM mdl_contester_submits mcs JOIN mdl_contester_problems mcp ON mcs.problem=mcp.dbid WHERE mcs.id=?', array($sid));

	$student = $DB->get_field('contester_submits', 'student', array('id' => $sid));
	echo $DB->get_field('user', 'firstname', array('id' => $student)).' '.$DB->get_field('user', 'lastname', array('id' => $student)).' ';
	foreach($r as $curname)
		echo $curname->name;
	echo ' ';
	echo  $DB->get_field('contester_submits', 'submitted', array('id' => $sid)).'<br/><br/>';
	echo "<textarea cols=70 rows = 30 readonly='yes'>".$DB->get_field('contester_submits', 'solution', array('id' => $sid))."</textarea>";

/// Finish the page
	contester_print_end();
    //print_footer($course);
   	echo $OUTPUT->footer();

?>
