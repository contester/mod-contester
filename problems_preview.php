<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $


    require_once("../../config.php");
    require_once("lib.php");

    $id   = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a    = optional_param('a', 0, PARAM_INT);  // contester ID
    $sort = optional_param('sort', 0, PARAM_INT);
    $tag  = optional_param('tag', 0, PARAM_INT);

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

    $context = context_module::instance($cm->id);
	$is_teacher = has_capability('moodle/course:viewhiddenactivities', $context);
    $is_admin = has_capability('moodle/site:config', $context);

    require_login($course->id);

    //add_to_log($course->id, "contester", "preview", "problems_preview.php?a=$contester->id", "$contester->id");

    if (!($is_admin || $is_teacher)) {
    	print_error(get_string('accessdenied', 'contester'));
    }

/// Print the page header

    /*if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

	$curcontester = "$contester->name ->";

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation $curcontester".get_string("problemspreview", "contester"),
                  "", "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/styles.css\" />",
                  true, update_module_button($cm->id, $course->id, $strcontester),
                  navmenu($course, $cm));*/

    $PAGE->set_url('/mod/contester/problems_preview.php', array('a' => $a));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");
    $PAGE->navbar->add("$contester->name");
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);
    $PAGE->set_button(update_module_button($cm->id, $course->id, get_string("modulename", "contester")));
    
    echo $OUTPUT->header();	
                  
/// Print the main part of the page

	echo '<div id=textheader>'.get_string('tags', 'contester').':</div>';
	echo '<div id=tags>';
	contester_show_tags_ref($contester->id, $sort);
	echo '</div>';
	$linktoall = "<a href=\"../../mod/contester/problems_preview_all.php?a=".$contester->id.
				"&sort=".$sort."&tag=".$tag."\">".get_string('problemspreviewall', 'contester')."</a>";
	echo '<div id=textheader>'.get_string('problemlist', 'contester').' ('.$linktoall.'):</div>';
	contester_show_problems_preview($contester->id, $sort, $tag);

/// Finish the page
    //print_footer($course);
    echo $OUTPUT->footer();

?>

