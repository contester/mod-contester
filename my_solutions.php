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
    $PAGE->set_button(update_module_button($cm->id, $course->id,
                      get_string("modulename", "contester")));

    echo $OUTPUT->header();


/// Print the main part of the page


    contester_print_begin($contester->id);

    // print the number of solutions in queue
    $qnum = $DB->get_record_sql("SELECT  COUNT(1) as cnt
                                 FROM    mdl_contester_submits
                                 WHERE   ((processed is NULL) or (processed = 1))");
    $cnum = $DB->get_record_sql("SELECT  COUNT(1) as cnt
                                 FROM    mdl_contester_submits
                                 WHERE   (processed = 255)");
    echo "<p>".get_string("numinqueue", "contester").": ".$qnum->cnt.
          " (".get_string("numchecked", "contester")." ". $cnum->cnt.")";

    $thisc = get_string('thiscontester', 'contester');
    $allc = get_string('all', 'contester');

    if ($thisorall == 1) {
        $thisc = "<a href=my_solutions.php?a=".$contester->id."&tha=0>".$thisc."</a>";
    }
    else {
        $allc = "<a href=my_solutions.php?a=".$contester->id."&tha=1>".$allc."</a>";
    }
    echo "<p><strong>".get_string('solutionlist', 'contester')." (".$thisc." \ ".$allc.")</strong></p>";

    if (!$userid)
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
                                     FROM   mdl_contester_problems as problems,
                                            mdl_contester_submits as submits,
                                            mdl_contester_languages as languages,
                                            mdl_contester as contester,
                                            mdl_contester_problemmap as problemmap
                                     WHERE
                                            submits.student = ? AND
                                            submits.lang = languages.id AND
                                            submits.problem = problems.dbid AND
                                            submits.contester = contester.id AND
					    contester.id = ? AND
                                            problemmap.contesterid = contester.id AND
                                            problemmap.problemid = problems.id
                                     ORDER BY submits.submitted_uts DESC', array($userid, $contester->id));
    }
    else {
        $tmp = $DB->get_records_sql('SELECT submits.id as p4,
	                                    submits.contester as a,
	                                    problems.name as p1,
					    languages.name as p2,
					    submits.submitted_uts as submitted_uts,
					    contester.name as p5,
                                            problemmap.id as pid
                                     FROM   mdl_contester_problems as problems,
                                            mdl_contester_submits as submits,
                                            mdl_contester_languages as languages,
                                            mdl_contester as contester,
                                            mdl_contester_problemmap as problemmap
                                     WHERE
                                            submits.student=? AND
                                            submits.lang=languages.id AND
                                            submits.problem = problems.dbid AND
                                            submits.contester = contester.id AND
                                            problemmap.contesterid = contester.id AND
                                            problemmap.problemid = problems.id
                                     ORDER BY submits.submitted_uts DESC', array($userid));
    }

    foreach($tmp as $row)
    {
        $tmpsubmitinfo = contester_get_special_submit_info($row->p4, false, false); //do not return problem name & language info
        $url_problem = new moodle_url('problem.php', ['a' => $row->a, 'pid' => $row->pid]);
        $table->data []= array('<a href='.$url_problem.'>'.$row->p1.'</a>',
                               $row->p2,
                               userdate($row->submitted_uts, get_string('strftimedatetime')),
                               $tmpsubmitinfo->status,
                               '<a href=show_solution.php?a='.$row->a.'&sid='.$row->p4.'>'.$tmpsubmitinfo->points.'</a>',
                               '<a href=view.php?a='.$row->a.'>'.$row->p5.'</a>');
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
