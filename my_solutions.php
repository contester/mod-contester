<?PHP

/// This page prints current user's solutions

    require_once("../../config.php");
    require_once("lib.php");

    $a  = required_param('a', PARAM_INT);  // Contester ID
    $thisorall = optional_param('tha', 0 , PARAM_INT); // 0 - this contest, 1 - all submits

    if(! $contester = $DB->get_record('contester', array('id' => $a))) {
        print_error(get_string("incorrect_contester_id", "contester"));
    }
    if(! $course = $DB->get_record('course', array('id' => $contester->course))) {
        print_error(get_string("misconfigured_course", "contester"));
    }
    if(! $cm = get_coursemodule_from_instance('contester', $contester->id, $course->id)) {
        print_error(get_string("incorrect_cm_id", "contester"));
    }

    require_login($course->id);
    //add_to_log($course->id, "contester", get_string('mysolutions', 'contester'), "my_solutions.php?a=$contester->id", "$contester->id");

/// Print the page header

    $PAGE->set_url('/mod/contester/my_solutions.php', array('a' => $contester->id));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");

    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $contester->id));
    $PAGE->navbar->add("$contester->name", $contester_url);

    echo $OUTPUT->header();


/// Print the main part of the page


    contester_print_begin($contester->id);

    // print the number of solutions in queue
    // TODO: make this 1 query
    $qnum = $DB->count_records_select('contester_submits', '(processed is NULL) or (processed = 1)');
    $cnum = $DB->count_records_select('contester_submits', 'processed = 255');
    echo "<p>".get_string("numinqueue", "contester").": ".$qnum.
          " (".get_string("numchecked", "contester")." ". $cnum.")";

    $thisc = get_string('thiscontester', 'contester');
    $allc = get_string('all', 'contester');

    if ($thisorall == 1) {
        $thisc = "<a href=my_solutions.php?a=".$contester->id."&tha=0>".$thisc."</a>";
    }
    else {
        $allc = "<a href=my_solutions.php?a=".$contester->id."&tha=1>".$allc."</a>";
    }
    echo "<p><strong>".get_string('solutionlist', 'contester')." (".$thisc." \ ".$allc.")</strong></p>";

    $userid = $USER->id;

    $table = new html_table();
    $table->head = array(get_string('problem', 'contester'), get_string('prlanguage', 'contester'),
                         get_string('date'), get_string('status', 'contester'), get_string('points', 'contester'),
                         get_string('modulename', 'contester'));


    if ($thisorall != 1) {
        $tmp = $DB->get_records_sql('SELECT submits.id as p4,
	                                    submits.contester as a,
	                                    problems.name as p1,
					    languages.name as p2,
					    submits.submitted_uts as submitted_uts,
    		                            contester.name as p5,
                                            problemmap.id as pid
                                     FROM   {contester_problems} as problems,
					    {contester_submits} as submits,
					    {contester_languages} as languages,
					    {contester} as contester,
					    {contester_problemmap} as problemmap
                                     WHERE
                                            submits.student = ? AND
                                            submits.lang = languages.id AND
                                            submits.problem = problems.dbid AND
                                            submits.contester = contester.id AND
					    contester.id = ? AND
                                            problemmap.contesterid = contester.id AND
                                            problemmap.problemid = problems.id
                                     ORDER BY submits.submitted_uts DESC', [$userid, $contester->id]);
        foreach($tmp as $row) {
            $tmpsubmitinfo = contester_get_special_submit_info($row->p4, false, false); //do not return problem name & language info
            $url_problem = new moodle_url('problem.php', ['a' => $row->a, 'pid' => $row->pid]);
            $table->data []= array('<a href='.$url_problem.'>'.$row->p1.'</a>',
                                   $row->p2,
                                   userdate($row->submitted_uts, get_string('strftimedatetime')),
                                   $tmpsubmitinfo->status,
                                   '<a href=show_solution.php?a='.$row->a.'&sid='.$row->p4.'>'.$tmpsubmitinfo->points.'</a>',
                                   '<a href=view.php?a='.$row->a.'>'.$row->p5.'</a>');
        }
    }
    else {
        $tmp = $DB->get_records_sql('SELECT submits.id as submit_id,
	                                    problems.name as problem_name,
					    languages.name as language_name,
					    submits.submitted_uts as submitted_uts
                                     FROM   {contester_problems} as problems,
					    {contester_submits} as submits,
					    {contester_languages} as languages
                                     WHERE
                                            submits.student=? AND
                                            submits.lang=languages.id AND
                                            submits.problem = problems.dbid
                                     ORDER BY submits.submitted_uts DESC', [$userid]);
        foreach($tmp as $row) {
            $cont = $DB->get_records_sql('SELECT submits.contester as a,	                                    
    		                                 contester.name as contester_name
                                          FROM   {contester_submits} as submits,
                                                 {contester} as contester
                                          WHERE
                                                 submits.contester = contester.id AND
                                                 submits.id = ?', [$row->submit_id]);
            if ($cont) {
                $row->a = $cont[0]->a;
                $row->contester_name = $cont[0]->contester_name;

                $problemmap = $DB->get_records_sql('SELECT problemmap.id as pid
                                                    FROM   {contester_problems} as problems,
                                                           {contester_submits} as submits,
                                                           {contester_problemmap} as problemmap
                                                    WHERE
                                                           submits.problem = problems.dbid AND
                                                           problemmap.contesterid = submits.contester AND
                                                           problemmap.problemid = problems.id AND
                                                           submits.id = ?', [$row->submit_id]);
                if ($problemmap) {
                    $row->pid = $problemmap[0]->pid;
                }
	    }

            if (!isset ($row->a)) {
                $problem = $row->problem_name;
                $status = "[deleted contest]";
                $points = '<a href=show_solution.php?a='.$a.'&sid='.$row->submit_id.'>'."[deleted contest]".'</a>';
                $contester = "[deleted contest]";
	    }
            else if (!isset ($row->pid)) {
                $problem = $row->problem_name;
                $status = "[deleted problem]";
                $points = '<a href=show_solution.php?a='.$row->a.'&sid='.$row->submit_id.'>'."[deleted problem]".'</a>';
                $contester = '<a href=view.php?a='.$row->a.'>'.$row->contester_name.'</a>';
	    }
            else {
                $url_problem = new moodle_url('problem.php', ['a' => $row->a, 'pid' => $row->pid]);
                $problem = '<a href='.$url_problem.'>'.$row->problem_name.'</a>';
    
                $tmpsubmitinfo = contester_get_special_submit_info($row->submit_id, false, false); //do not return problem name & language info
                $status = $tmpsubmitinfo->status;
                $points = '<a href=show_solution.php?a='.$row->a.'&sid='.$row->submit_id.'>'.$tmpsubmitinfo->points.'</a>';
                $contester = '<a href=view.php?a='.$row->a.'>'.$row->contester_name.'</a>';
	    }

            $table->data []= array($problem,
                                   $row->language_name,
                                   userdate($row->submitted_uts, get_string('strftimedatetime')),
                                   $status,
                                   $points,
                                   $contester);

	}
    }


    if ($table->data === false) {
        print_string('nosolutions', contester);
    }
    else {
        echo html_writer::table($table);
    }


    contester_print_end();


/// Finish the page

    echo $OUTPUT->footer();

?>
