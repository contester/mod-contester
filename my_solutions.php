<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// This page prints a particular instance of contester
/// (Replace contester with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID
    $thisorall = optional_param('tha', 0 , PARAM_INT); // 0 - this contest, 1 - all submits

    if ($id) {
        if (! $cm = $DB->get_record("course_modules", array("id"=>$id))) {
            error("Course Module ID was incorrect");
        }

        if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
            error("Course is misconfigured");
        }

        if (! $contester = $DB->get_record("contester", array("id"=>$cm->instance))) {
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

    //add_to_log($course->id, "contester", get_string('mysolutions', 'contester'), "my_solutions.php?a=$contester->id", "$contester->id");

/// Print the page header

	/*
    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strcontesters</a> -> $contester->name",
                  "", "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/styles.css\" />", true, update_module_button($cm->id, $course->id, $strcontester),
                  navmenu($course, $cm));
	*/
	
	$PAGE->set_url('/mod/contester/my_solutions.php', array('id' => $cm->id));
	$PAGE->set_title(format_string($contester->name));
	$PAGE->set_heading(format_string($course->fullname));

/// Print the main part of the page
	echo $OUTPUT->header();
	contester_print_begin($contester->id);
	// header
	echo "<br />";

	$thisc = get_string('thiscontester', 'contester');
	$allc = get_string('all', 'contester');
	//$thiscontester = "";
	if ($thisorall == 1)
	{
		$thisc = "<a href=my_solutions.php?a=".$contester->id."&tha=0>".$thisc."</a>";
	}
   	else
   	{
   		//$thiscontester = " AND contester.id = ".$contester->id." ";
   		$allc = "<a href=my_solutions.php?a=".$contester->id."&tha=1>".$allc."</a>";
   	}
	echo "<p><strong>".get_string('solutionlist', 'contester')." (".$thisc." \ ".$allc.")</strong></p>";

	if (!$userid) 
	$userid = $USER->id;

	//$table = null;
	$table = new html_table();
	$table->head = array(get_string('problem', 'contester'), get_string('prlanguage', 'contester'),
		get_string('date'), get_string('status', 'contester'), get_string('points', 'contester'),
		get_string('modulename', 'contester'));

	/*$sql = "
	SELECT problems.name as p1, languages.name as p2, submits.submitted as p3,
		   submits.id as p4, contester.name as p5
	FROM   mdl_contester_problems as problems,
		   mdl_contester_submits as submits,
		   mdl_contester_languages as languages,
		   mdl_contester as contester
	WHERE
		   submits.student=$userid AND
		   submits.lang=languages.id AND
		   submits.problem = problems.dbid AND
		   submits.contester = contester.id".$thiscontester."
	ORDER BY submits.submitted DESC
	";
	//echo "<textarea>".$sql."</textarea>";

	$tmp = mysql_query($sql);*/
	
	if ($thisorall != 1)	
	{
    	$tmp = $DB->get_records_sql('SELECT submits.id as p4, problems.name as p1, languages.name as p2, submits.submitted as p3,
    		   contester.name as p5
    	FROM   mdl_contester_problems as problems,
    		   mdl_contester_submits as submits,
    		   mdl_contester_languages as languages,
    		   mdl_contester as contester
    	WHERE
    		   submits.student=? AND
    		   submits.lang=languages.id AND
    		   submits.problem = problems.dbid AND
    		   submits.contester = contester.id AND contester.id = ? 
    	ORDER BY submits.submitted DESC', array($userid, $contester->id));
    }
    else
    {
    	$tmp = $DB->get_records_sql('SELECT submits.id as p4, problems.name as p1, languages.name as p2, submits.submitted as p3,
    		   contester.name as p5
    	FROM   mdl_contester_problems as problems,
    		   mdl_contester_submits as submits,
    		   mdl_contester_languages as languages,
    		   mdl_contester as contester
    	WHERE
    		   submits.student=? AND
    		   submits.lang=languages.id AND
    		   submits.problem = problems.dbid AND
    		   submits.contester = contester.id
    	ORDER BY submits.submitted DESC', array($userid));    
    }
    
	//while ($row = mysql_fetch_array($tmp))
	foreach($tmp as $row)
	{
		$tmpsubmitinfo = contester_get_special_submit_info($row->p4, false, false); //do not return problem name & language info
		$table->data []= array($row->p1,$row->p2,$row->p3,$tmpsubmitinfo->status,
			'<a href=show_solution.php?a='.$contester->id.'&sid='.$row->p4.'>'.$tmpsubmitinfo->points.'</a>',$row->p5);
	}

	//print_r($table->data);

	if ($table->data === false)
	{
		print_string('nosolutions', contester);
	} else {
		echo html_writer::table($table);
		//echo $OUTPUT->table($newtable);
		//print_table($table);
	}

/// Finish the page
	contester_print_end();
    //print_footer($course);
	echo $OUTPUT->footer();
?>
