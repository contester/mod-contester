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

    if ($datetime_to_value == NULL) {
        $dt_to = new DateTime("now", $school_timezone);
        $dt_to->modify('+1 hour');  // на всякий пожарный <- wtf
    }
    else {
        $dt_to = new DateTime($datetime_to_value, $school_timezone);
    }
    $dateto_uts = $dt_to->getTimestamp();

    if ($datetime_from_value == NULL) {
        $dt_from = $dt_to;
        $dt_from->modify('-2 year');
    }
    else {
        $dt_from = new DateTime($datetime_from_value, $school_timezone);
    }
    $datefrom_uts = $dt_from->getTimestamp();

    $query = "SELECT DISTINCT submits.student id, concat(u.lastname, ' ', u.firstname) name
              FROM   {contester_submits} submits,
		     {user} u
              WHERE  u.id=submits.student
                     AND
                     submits.contester = ?
                     AND
                     submits.submitted_uts BETWEEN ? AND ?
              ORDER  BY u.lastname, u.firstname";

    $query2 = "SELECT DISTINCT gm.userid id, concat(u.lastname, ' ', u.firstname) name
               FROM   {groups_members} gm, {user} u,
                      {contester_submits} submits
               WHERE  u.id = gm.userid
                      AND
                      gm.userid = submits.student
                      AND
                      submits.contester = ?
                      AND
                      gm.groupid = ?
                      AND
                      submits.submitted_uts BETWEEN ? AND ?
               ORDER BY u.lastname, u.firstname";

    if ($group_value == -1 || $group_value == 'none') {
        $sts = $DB->get_records_sql($query,
                     [$contester->id, $datefrom_uts, $dateto_uts]);
    }
    else {
        $sts = $DB->get_records_sql($query2,
                     [$contester->id, $group_value, $datefrom_uts, $dateto_uts]);
    }


    $query =
	"SELECT pm.problemid id, pm.id as pid, p.name FROM {contester_problemmap} pm, {contester_problems} p
		WHERE
		p.id = pm.problemid AND pm.contesterid = ? ORDER BY pm.id";


    $prs = $DB->get_records_sql($query, [$contester->id]);

    $table = array();
    echo '<p style="padding-top:20pt"><table align="left" border="1"><tr><td></td>';
    foreach ($prs as $pr) {
        echo "<td><a href=problem.php?pid=".$pr->pid.
              "&a=".$contester->id.">".$pr->name."</a></td>";
    }
    echo '<td>'.get_string('total', 'contester').'</td>';
    echo "</tr>\n";

    if (empty($USER->id)) {
        print_error('accessdenied', 'contester');
    }
    $userid = $USER->id;
    foreach ($sts as $st) {
        echo "<tr><td><a href=\"../../user/view.php?id=$st->id&course=$course->id\">$st->name</a></td>";
        $cnt = 0;
        foreach ($prs as $pr) {
            if ($is_admin || $is_teacher || $st->id == $userid) {
                $tmp = contester_get_last_or_last_correct_submit_reference($contester->id, $st->id, $pr->id, $datefrom_uts, $dateto_uts);
            }
            else {
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
