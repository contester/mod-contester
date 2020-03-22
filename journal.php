<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// Журнал

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID

    $group_value  = optional_param('group', -1, PARAM_INT);
    $MIN_year = 2000;
    $MAX_year = date('Y');
	$year_from_value  = optional_param('year_from', date('Y') - 1, PARAM_INT);
	$month_from_value  = optional_param('month_from', 9, PARAM_INT);
	$day_from_value  = optional_param('day_from', 1, PARAM_INT);
	$time_from_value  = optional_param('time_from', '00:00:00', PARAM_TEXT);

	$year_to_value  = optional_param('year_to', -1, PARAM_INT);
	$month_to_value  = optional_param('month_to', 12, PARAM_INT);
	$day_to_value  = optional_param('day_to', -1, PARAM_INT);
	$time_to_value  = optional_param('time_to', '23:59:59', PARAM_TEXT);


	if ($id) {
        if (! $cm = $DB->get_record("course_modules", array("id"=>$id))) {
            print_error("Course Module ID was incorrect");
        }

        if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
            print_error("Course is misconfigured");
        }

        if (! $contester = $DB->get_record("contester", array("id"=>$cm->instance))) {
            print_error("Course module is incorrect");
        }

    } else {
        if (! $contester = $DB->get_record("contester", array("id"=>$a))) {
            print_error("Course module is incorrect");
        }
        if (! $course = $DB->get_record("course", array("id"=>$contester->course))) {
            print_error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("contester", $contester->id, $course->id)) {
            print_error("Course Module ID was incorrect");
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
	
    $PAGE->set_url('/mod/contester/journal.php', array('id' => $cm->id));
    $PAGE->set_title(format_string($contester->name));
    $PAGE->set_heading(format_string($course->fullname));
    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $a));
    $PAGE->navbar->add("$contester->name", $contester_url);  
	
/// Print the main part of the page
	echo $OUTPUT->header();
	contester_print_begin($contester->id);
	//$context = get_context_instance(CONTEXT_MODULE, $cm->id);
	$context = context_module::instance($cm->id);	
	$is_teacher = has_capability('moodle/course:viewhiddenactivities', $context);
	$is_admin = has_capability('moodle/site:config', $context);

	// contester id -> course
	$grps = groups_get_all_groups($contester->course);

	echo '<form name=options method="GET" action="journal.php">';
	echo '<input type=hidden name="a" value="'. $a . '">';
	echo '<table width = 95% heigth = 5%><tr><td>';
	echo '<select name="group">';
	echo '<option value="none"'; if ($group_value == 'none') echo ' selected'; echo '>---<br>';
	foreach($grps as $grp){
		echo '<option value="'.$grp->id.'"'; if ($group_value == $grp->id) echo ' selected'; echo '>'.$grp->name.'<br>';
	}
	echo '</select>';
///Select time from
	echo '</td><td>';
	echo get_string('datefrom', 'contester');
	echo '<select name="year_from">';
	echo '<option value="none"'; if ($year_from_value == -1) echo ' selected'; echo '>----<br>';
	for($i = $MIN_year; $i <= $MAX_year; $i++){
		echo '<option value="'.$i.'"'; if ($year_from_value == $i) echo ' selected'; echo '>'.$i.'<br>';
	}
	echo '</select>';
	echo '<select name="month_from">';
	echo '<option value="none"'; if ($month_from_value == -1) echo ' selected'; echo '>---<br>';
	for($i = 1; $i <= 12; $i++){
		echo '<option value="'.date('m', mktime(0, 0, 0, $i, 1, 2000)).'"';
		if (!($month_from_value == -1) && date('F', mktime(0, 0, 0, $month_from_value, 1, 2000)) == date('F', mktime(0, 0, 0, $i, 1, 2000))) echo ' selected';
		echo '>'.get_string(strtolower(date('F', mktime(0, 0, 0, $i, 1, 2000))), 'contester').'<br>';
	}
	echo '</select>';
	echo '<select name="day_from">';
	echo '<option value="none"'; if ($day_from_value == -1) echo ' selected'; echo '>--<br>';
	for($i = 1; $i <= 31; $i++){
		echo '<option value="'.date('d', mktime(0, 0, 0, 1, $i, 2000)).'"';
		if (!($day_from_value == -1) && date('d', mktime(0, 0, 0, 1, $day_from_value, 2000)) == date('d', mktime(0, 0, 0, 1, $i, 2000))) echo ' selected';
		echo '>'.date('d', mktime(0, 0, 0, 1, $i, 2000)).'<br>';
	}
	echo '</select>';
	echo '<input type=text name="time_from" value="';
	if ($time_from_value == -1 || $time_from_value == 0) echo '00:00:00';
		else  echo $time_from_value;
	echo '">';
///Select time to
	echo '</td><td>';
	echo get_string('to', 'contester');
	echo '<select name="year_to">';
	echo '<option value="none"'; if ($year_to_value == -1) echo ' selected'; echo '>----<br>';
	for($i = $MIN_year; $i <= date('Y'); $i++){
		echo '<option value="'.$i.'"'; if ($year_to_value == $i) echo ' selected'; echo '>'.$i.'<br>';
	}
	echo '</select>';
	echo '<select name="month_to">';
	echo '<option value="none"'; if ($month_to_value == -1) echo ' selected'; echo '>---<br>';
	for($i = 1; $i <= 12; $i++){
		echo '<option value="'.date('m', mktime(0, 0, 0, $i, 1, 2000)).'"';
		if (!($month_to_value == -1) && date('F', mktime(0, 0, 0, $month_to_value, 1, 2000)) == date('F', mktime(0, 0, 0, $i, 1, 2000))) echo ' selected';
		echo '>'.get_string(strtolower(date('F', mktime(0, 0, 0, $i, 1, 2000))), 'contester').'<br>';
	}
	echo '</select>';
	echo '<select name="day_to">';
	echo '<option value="none"'; if ($day_to_value == -1) echo ' selected'; echo '>--<br>';
	for($i = 1; $i <= 31; $i++){
		echo '<option value="'.date('d', mktime(0, 0, 0, 1, $i, 2000)).'"';
		if (!($day_to_value == -1) && date('d', mktime(0, 0, 0, 1, $day_to_value, 2000)) == date('d', mktime(0, 0, 0, 1, $i, 2000))) echo ' selected';
		echo '>'.date('d', mktime(0, 0, 0, 1, $i, 2000)).'<br>';
	}
	echo '</select>';
	echo '<input type=text name="time_to" value="';
	if ($time_to_value == -1 || $time_to_value == 0) echo '00:00:00';
		else  echo $time_to_value;
	echo '" onReset=returnValue("none")>';
	echo '</td><td>';
	echo '<input type=submit value="'.get_string('find', 'contester').'">';
	echo '</td></tr></table>';
	echo '</form>';
/////////////////////////D.r.
	//echo $query."<BR>";
	//echo $group_value.' '.$year_value.' '.date(m, mktime(0, 0, 0, $month_value, 1, 2000)).' '.$month_value.
	//date(d, mktime(0, 0, 0, 1, $day_value, 2000)).' '.$time_value.mktime(0, 0, 0, 3, 1, 2000);
	if ($group_value == -1 || $group_value == 'none')
		$groupquery = '';
		else
		$groupquery = 'AND (mdl_groups_members.groupid ='.$group_value.')';
	if ($year_from_value == -1 || $year_from_value == 'none') $year_from_param = $MIN_year;
		else $year_from_param = $year_from_value;
	if ($month_from_value == -1 || $month_from_value == 'none') $month_from_param = date('m', mktime(0, 0, 0, 1, 1, 2000));
		else $month_from_param = date('m', mktime(0, 0, 0, $month_from_value, 1, 2000));
	if ($day_from_value == -1 || $day_from_value == 'none') $day_from_param = date('d', mktime(0, 0, 0, 1, 1, 2000));
		else $day_from_param = date('d', mktime(0, 0, 0, 1, $day_from_value, 2000));
	if ($time_from_value == -1) $time_from_param = '00:00:00';
		else $time_from_param = $time_from_value;
	$datefrom = $year_from_param.'-'.$month_from_param.'-'.$day_from_param.' '.$time_from_param;

	if ($year_to_value == -1 || $year_to_value == 'none') $year_to_param = $MAX_year;
		else $year_to_param = $year_to_value;
	if ($month_to_value == -1 || $month_to_value == 'none') $month_to_param = date('m', mktime(0, 0, 0, 12, 1, 2000));
		else $month_to_param = date('m', mktime(0, 0, 0, $month_to_value, 1, 2000));
	if ($day_to_value == -1 || $day_to_value == 'none') $day_to_param = date('d', mktime(0, 0, 0, 1, 31, 2000));
		else $day_to_param = date('d', mktime(0, 0, 0, 1, $day_to_value, 2000));
	if ($time_to_value == -1) $time_to_param = '23:59:59';
		else $time_to_param = $time_to_value;
	$dateto = $year_to_param.'-'.$month_to_param.'-'.$day_to_param.' '.$time_to_param;

	$contester_str = '(contester ='.$contester->id.') and';

	$query = "SELECT DISTINCT mdl_contester_submits.student
		FROM mdl_contester_submits JOIN mdl_user ON mdl_user.id=
		mdl_contester_submits.student
		WHERE
		    ".$contester_str." mdl_contester_submits.submitted >= \"".$datefrom."\"
            AND mdl_contester_submits.submitted <= \"".$dateto. "\"
        ORDER BY mdl_user.lastname, mdl_user.firstname";

	$query2 = "SELECT DISTINCT mdl_groups_members.userid AS student FROM
			(mdl_groups_members JOIN mdl_user ON mdl_user.id=mdl_groups_members.userid) JOIN
			mdl_contester_submits ON mdl_groups_members.userid=mdl_contester_submits.student
			WHERE ".
			$contester_str." mdl_contester_submits.submitted >= \"".$datefrom.
			"\" ". $groupquery ."AND mdl_contester_submits.submitted <= \"".$dateto. "\"
			ORDER BY mdl_user.lastname, mdl_user.firstname";

	//echo $query."<BR>";
	if ($group_value == -1 || $group_value == 'none') {
	    $students = $DB->get_recordset_sql($query);
	} else {
	    $students = $DB->get_recordset_sql($query2);
	}
	$query =

	"SELECT problemid, id as pid FROM mdl_contester_problemmap
		WHERE
		(contesterid = $contester->id) ORDER BY id";


	$problems = $DB->get_recordset_sql($query);
	//var_dump($problems);
	//var_dump($students);

	$table = array();
	$sts = array();
	foreach ($students as $student)
	{
		//$st = null;
		$st = new stdClass();
		//var_dump($student['student']);
		$st->name = $DB->get_field('user', 'lastname', array('id'=>$student->student)). ' '
			.$DB->get_field('user', 'firstname', array('id'=>$student->student));
		$st->id = $student->student;
		$sts []= $st;
	}
	$prs = array();
	foreach ($problems as $problem)
	{
		$pr = new stdClass();
		$pr->id = $problem->problemid;
		$pr->pid = $problem->pid;
		$pr->name = $DB->get_field('contester_problems', 'name', array('id'=>$pr->id));
		//echo $pr->pid." ".$pr->name." ".$pr->id."<br />";
		if ($pr->id != 0 && $pr->name != "")
			$prs []= $pr;
	}
	//var_dump($prs);
	//var_dump($sts);

	echo '<table border="1"><tr><td></td>';
	foreach ($prs as $pr)
	{
		echo "<td><a href=problem.php?pid=".$pr->pid.
			"&a=".$contester->id.">".$pr->name."</a></td>";
	}
	echo '<td>'.get_string('total', 'contester').'</td>';
	echo "</tr>\n";

    if (empty($USER->id)) {
        print_error('accessdenied', 'contester');
    }
    $userid = $USER->id;
	foreach ($sts as $st)
	{
		echo "<tr><td><a href=\"../../user/view.php?id=$st->id&course=$course->id\">$st->name</a></td>";
		$cnt = 0;
		foreach ($prs as $pr) {
			if ($is_admin || $is_teacher || $st->id == $userid)
			{
				$tmp = contester_get_last_or_last_correct_submit_reference($contester->id, $st->id, $pr->id, $datefrom, $dateto);                
				//contester_get_last_best_submit_reference($contester->id, $st->id, $pr->id, $datefrom, $dateto);
			}
			else
			{
				$tmp = contester_get_result_without_reference($contester->id, $st->id, $pr->id, $datefrom, $dateto);
			}
            echo "<td>".$tmp."</td>";
			if (strpos($tmp, '+') !== FALSE) ++$cnt;
		}
		echo "<td>$cnt</td>";
		echo "</tr>\n";
	}
	echo "</table>";
	//var_dump($submits);

	//$result = array();

	/*foreach($submits as $line)
		$result []= contester_get_submit($line["id"]);*/
	/*while (!$submits->EOF)
	{
	    $result []= contester_get_submit_info($submits->fields["id"]);
	    $submits->MoveNext();
	}

    //$submits = contester_get_last_submits($contester->id, 10, $USER->id);

    contester_draw_assoc_table($result);
    */
/// Finish the page
	contester_print_end();
    //print_footer($course);
	echo $OUTPUT->footer();
?>
