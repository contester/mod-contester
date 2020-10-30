<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// Журнал

    $school_timezone = new DateTimeZone('Europe/Samara');

    require_once("../../config.php");
    require_once("lib.php");

    $a   = required_param('a', PARAM_INT);  // contester ID
    $group_value  = optional_param('group', -1, PARAM_INT);

    $datetime_from_value = optional_param('datetime_from', NULL, PARAM_TEXT);
    $datetime_to_value   = optional_param('datetime_to', NULL, PARAM_TEXT);


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


    // add_to_log($course->id, "contester", "status", "status.php?id=$cm->id", "$contester->id");

/// Print the page header

    $PAGE->set_url('/mod/contester/journal.php', array('a' => $contester->id));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading($course->fullname);

    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $contester->id));
    $PAGE->navbar->add("$contester->name", $contester_url);
    $PAGE->set_button(update_module_button($cm->id, $course->id,
                      get_string("modulename", "contester")));

    echo $OUTPUT->header();

/// Print the main part of the page

    contester_print_begin($contester->id);

    $context = context_module::instance($cm->id);
    $is_teacher = has_capability('moodle/course:viewhiddenactivities', $context);
    $is_admin = has_capability('moodle/site:config', $context);


    // TODO: maybe replace this selector with a call to groups_print_course_menu.
    $grps = groups_get_all_groups($contester->course);

    echo '<form name=options method="GET" action="journal.php">';
    echo '<input type=hidden name="a" value="'. $a . '">';
    echo '<table align="left" cellpadding="5pt"><tr><td align="right">';
    echo get_string('group', 'contester');
    echo '</td><td><select name="group">';
    echo '<option value="none"'; if ($group_value == 'none') echo ' selected'; echo '>---<br>';
    foreach($grps as $grp){
        echo '<option value="'.$grp->id.'"'; if ($group_value == $grp->id) echo ' selected'; echo '>'.$grp->name.'<br>';
    }
    echo '</select>';

///Select time from
    echo '</td><td align="right">';
    echo get_string('datefrom', 'contester');
    echo '</td><td><input type="datetime-local" id="datetime_from" name="datetime_from" value="'
                 .$datetime_from_value.'"/>';

///Select time to
    echo '</td><td align="right">';
    echo get_string('to', 'contester');
    echo '</td><td><input type="datetime-local" id="datetime_to" name="datetime_to" value="'
          .$datetime_to_value.'"/>';

    echo '</td><td>';


    echo '<input type=submit value="'.get_string('find', 'contester').'">';
    echo '</td></tr></table>';
    echo '</form>';

    if ($group_value == -1 || $group_value == 'none')
        $groupquery = '';
    else
        $groupquery = 'AND (mdl_groups_members.groupid ='.$group_value.')';

    if ($datetime_from_value == NULL) {
        $dt_from = new DateTime("now", $school_timezone);
        $dt_from->modify('-2 year');
    }
    else {
        $dt_from = new DateTime($datetime_from_value, $school_timezone);
    }
    $datefrom_uts = $dt_from->getTimestamp();

    if ($datetime_to_value == NULL) {
        $dt_to = new DateTime("now", $school_timezone);
        $dt_to->modify('+1 hours');
    }
    else {
        $dt_to = new DateTime($datetime_to_value, $school_timezone);
    }
    $dateto_uts = $dt_to->getTimestamp();


    $query = "SELECT DISTINCT submits.student
              FROM   {contester_submits} submits,
                     mdl_user
              WHERE  mdl_user.id=submits.student
                     AND
                     submits.contester = $contester->id
                     AND
                     submits.submitted_uts BETWEEN ? AND ?
              ORDER  BY mdl_user.lastname, mdl_user.firstname";

    $query2 = "SELECT DISTINCT mdl_groups_members.userid AS student
               FROM   mdl_groups_members, mdl_user,
                      {contester_submits} submits
               WHERE  mdl_user.id = mdl_groups_members.userid
                      AND
                      mdl_groups_members.userid = submits.student
                      AND
                      submits.contester = $contester->id
                      AND
                      mdl_groups_members.groupid = ?
                      AND
                      submits.submitted_uts BETWEEN ? AND ?
               ORDER BY mdl_user.lastname, mdl_user.firstname";

    if ($group_value == -1 || $group_value == 'none') {
        $students = $DB->get_recordset_sql($query,
                     array($datefrom_uts, $dateto_uts));
    }
    else {
        $students = $DB->get_recordset_sql($query2,
                     array($group_value, $datefrom_uts, $dateto_uts));
    }

	$query =

	"SELECT problemid, id as pid FROM mdl_contester_problemmap
		WHERE
		(contesterid = $contester->id) ORDER BY id";


	$problems = $DB->get_recordset_sql($query);

	$table = array();
	$sts = array();
	foreach ($students as $student)
	{
		$st = new stdClass();
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
		if ($pr->id != 0 && $pr->name != "")
			$prs []= $pr;
	}

	echo '<p style="padding-top:20pt"><table align="left" border="1"><tr><td></td>';
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
				$tmp = contester_get_last_or_last_correct_submit_reference($contester->id, $st->id, $pr->id, $datefrom_uts, $dateto_uts);
			}
			else
			{
				$tmp = contester_get_result_without_reference($contester->id, $st->id, $pr->id, $datefrom_uts, $dateto_uts);
			}
            echo "<td>".$tmp."</td>";
			if (strpos($tmp, '+') !== FALSE) ++$cnt;
		}
		echo "<td>$cnt</td>";
		echo "</tr>\n";
	}
	echo "</table></p>";


    contester_print_end();

/// Finish the page

    echo $OUTPUT->footer();
?>
