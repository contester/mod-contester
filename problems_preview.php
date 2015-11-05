<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $


    require_once("../../config.php");
    require_once("lib.php");

    $id   = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a    = optional_param('a', 0, PARAM_INT);  // contester ID
    $sort = optional_param('sort', 0, PARAM_INT);
    $tag  = optional_param('tag', 0, PARAM_INT);

    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }

        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }

        if (! $contester = get_record("contester", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $contester = get_record("contester", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $contester->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("contester", $contester->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

	$context = get_context_instance(CONTEXT_MODULE, $cm->id);
	$is_teacher = has_capability('moodle/course:viewhiddenactivities', $context);

    require_login($course->id);

    add_to_log($course->id, "contester", "preview", "problems_preview.php?a=$contester->id", "$contester->id");

    if (!(isadmin() || $is_teacher)) {
    	error(get_string('accessdenied', 'contester'));
    }

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

	$curcontester = "$contester->name ->";

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation $curcontester".get_string("problemspreview", "contester"),
                  "", "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/styles.css\" />",
                  true, update_module_button($cm->id, $course->id, $strcontester),
                  navmenu($course, $cm));

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
    print_footer($course);

?>

