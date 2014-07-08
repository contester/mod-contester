<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// This page prints a particular instance of contester
/// (Replace contester with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID

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

    require_login($course->id);
    add_to_log($course->id, "contester", "details", "details.php?id=$cm->id", "$contester->id");
    if (!isadmin()) error(get_string('accessdenied', 'contester'));
	$pid = required_param('pid');

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }
    $problemspreview = "<a href=\"../../mod/contester/problems_preview.php?a=".$contester->id."\">".get_string('problemspreview', 'contester')."</a> ->";
    $curcontester = "$contester->name ->";
    $strcontester = get_string("modulename", "contester");
    $strprdetails = get_string("problemdetails", "contester");

	$sql = "SELECT mdl_contester_problems.name as name
			FROM   mdl_contester_problems
			WHERE  mdl_contester_problems.id=$pid";
	if (!$problem = get_record_sql($sql)) error('No such problem!');
    $problempreview = "<a href=\"../../mod/contester/problem_preview.php?a=".$contester->id."&pid=".$pid."\">".$problem->name."</a> ->";

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation $curcontester $problemspreview $problempreview $strprdetails",
                  "", "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/styles.css\" />",
                  true, update_module_button($cm->id, $course->id, $strcontester),
                  navmenu($course, $cm));

/// Print the main part of the page

	echo '<form action=save_problem.php method="POST">';
	contester_show_problem_details($pid);
	echo '<input type=submit value="'.get_string('save', 'contester').'">';
	echo '<input type=hidden name="pid" value="'.$pid.'">';
	echo '<input type=hidden name="a" value="'.$contester->id.'">';
	echo '</form>';

/// Finish the page
	contester_print_end();
    print_footer($course);

?>
