<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// This page prints a particular instance of contester
/// (Replace contester with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID

    if ($id) {
        if (! $cm = $DB->get_record("course_modules", array("id"=>$id))) {
            error("Course Module ID was incorrect");
        }

        if (! $course = $DB->get_record("course", array("id"=> $cm->course))) {
            error("Course is misconfigured");
        }

        if (! $contester = $DB->get_record("contester", array("id"=> $cm->instance))) {
            error("Course module is incorrect");
        }

    } else {
        if (! $contester = $DB->get_record("contester", array("id"=>$a))) {
            error("Course module is incorrect");
        }
        if (! $course = $DB->get_record("course", array("id"=>$contester->course))) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("contester", $contester->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    //add_to_log($course->id, "contester", "status", "status.php?id=$cm->id", "$contester->id");

/// Print the page header

	/*
    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strcontesters</a> -> $contester->name",
                  "", "", true, update_module_button($cm->id, $course->id, $strcontester),
                  navmenu($course, $cm));
				  */
	$PAGE->set_url('/mod/contester/status.php', array('id' => $cm->id));
	$PAGE->set_title(format_string($contester->name));
	$PAGE->set_heading(format_string($course->fullname));

/// Print the main part of the page
	echo $OUTPUT->header();
	contester_print_begin($contester->id);

	$query = "SELECT   id
			  FROM     mdl_contester_submits
			  WHERE    (contester = $contester->id)
			  AND      (student = $USER->id)
			  ORDER BY submitted
			  DESC
			  LIMIT    0, 10 ";

	$submits = $DB->get_records_sql($query);

	//var_dump($submits);

	$result = array();
	foreach($submits as $line)
		$result []= contester_get_submit($line->id);
	/*while ($submits->valid())
	{
	    $result []= contester_get_submit_info($submits->fields["id"]);
	    $submits->MoveNext();
	}*/

    //$submits = contester_get_last_submits($contester->id, 10, $USER->id);

	// print the number of solutions in queue
	//if (isadmin())
	{
		$qnum = $DB->get_record_sql("SELECT  COUNT(1) as cnt
						    FROM    mdl_contester_submits
       						WHERE   ((processed is NULL) or (processed = 1))");
       	$cnum = $DB->get_record_sql("SELECT  COUNT(1) as cnt
						    FROM    mdl_contester_submits
       						WHERE   (processed = 255)");

		echo "<p>".get_string("numinqueue", "contester").": ".$qnum->cnt.
			" (".get_string("numchecked", "contester")." ". $cnum->cnt.")</p>";
    }

	echo "<p>";
    contester_draw_assoc_table($result);
	echo "</p>";
	

/// Finish the page
	contester_print_end();
    //print_footer($course);
	echo $OUTPUT->footer();

?>
