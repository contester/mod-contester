<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// This page prints a particular instance of contester
/// (Replace contester with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID
    $subid = optional_param('subid', 0, PARAM_INT); // submit ID

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

    add_to_log($course->id, "contester", "sub_detail", "sub_detail.php?id=$cm->id", "$contester->id");

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strcontesters</a> -> $contester->name", 
                  "", "", true, update_module_button($cm->id, $course->id, $strcontester), 
                  navmenu($course, $cm));

/// Print the main part of the page

	if ($USER->student[1])
	{
		$query = "select id, processed, test, timex, memory, success, description from mdl_contester_results Join mdl_contester_resultdesc On (result = mdl_contester_resultdesc.id) and (success =  mdl_contester_resultdesc.success) where (id = $subid) and (language = 1)";
		$query .= " Union select mdl_contester_submits.id, submitted, 0, 0, 0, 0, description from mdl_contester_submits Join mdl_contester_resultdesc On ( mdl_contester_resultdesc.id = 0) and ( mdl_contester_resultdesc.success = 0) where ( mdl_contester_submits.id = $subid) and (language = 1)";
		$query .= " Order by 2, 3";
	}
	else
	{
		$query = "select id, processed, test, timex, memory, testeroutput, testererror, testerexitcode, success, description from mdl_contester_results Join mdl_contester_resultdesc On (result = mdl_contester_resultdesc.id) and (success =  mdl_contester_resultdesc.success) where (id = $subid) and (language = 1)";
                $query .= " Union select mdl_contester_submits.id, submitted, 0, 0, 0, '', 0, 0, 0, description from mdl_contester_submits Join mdl_contester_resultdesc On ( mdl_contester_resultdesc.id = 0) and ( mdl_contester_resultdesc.success = 0) where ( mdl_contester_submits.id = $subid) and (language = 1)";
                $query .= " Order by 2, 3";

	}

	//contester_draw_table_from_sql($query);
	
	echo contester_get_user_points($a, $USER->id);
	echo '!';
  
/// Finish the page
    print_footer($course);

?>
