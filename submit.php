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
            error("Course module is incorrect 1");
        }

    } else {
        if (! $contester = get_record("contester", "id", $a)) {
            error("Course module is incorrect 2");
        }
        if (! $course = get_record("course", "id", $contester->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("contester", $contester->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    add_to_log($course->id, "contester", "submit", "submit.php?id=$cm->id", "$contester->id");

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

///    echo "<form method=\"post\" action=\"submit.php\">Problem source: <input type=\"file\" name=\"solution\"><br><input type=\"submit\"></form>";

	$temp_name = $_FILES["solution"]["tmp_name"];
	if (!is_uploaded_file($temp_name))
	{
		// Handle submit error
		print_string("File submit error. Try again.", "contester");
	}
	else
	{

		$submit = NULL;
		$submit->contester = $contester->id;
		$submit->student = $USER->id;
		$submit->problem = $_POST["problem"];
		$submit->lang = $_POST["lang"];
		$submit->solution = mysql_escape_string(file_get_contents($temp_name));	

		insert_record("contester_submits", $submit);

		print_string("Submitted successfully.", "contester");
	}

/// Finish the page
    print_footer($course);

?>
