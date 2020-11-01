<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// Выводит все правильные решения данной
/// задачи, предварительно проверив наличие прав доступа.

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID
    $pid = required_param('pid', PARAM_INT); // ID of problem in problemmap

    global $DB;

    if ($id) {
        if (! $cm = $DB->get_record("course_modules", array("id" => $id))) {
            print_error("Course Module ID was incorrect");
        }

        if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
            print_error("Course is misconfigured");
        }

        if (! $contester = $DB->get_record("contester", array("id" => $cm->instance))) {
            print_error("Course module is incorrect");
        }

    } else {
        if (! $contester = $DB->get_record("contester", array("id" => $a))) {
            print_error("Course module is incorrect");
        }
        if (! $course = $DB->get_record("course", array("id" => $contester->course))) {
            print_error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("contester", $contester->id, $course->id)) {
            print_error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    //add_to_log($course->id, "contester", "problem_solutions", "problem_solutions.php?a=$contester->id&pid=$pid", "$contester->id");

/// Print the page header

    $PAGE->set_url('/mod/contester/problem_solutions.php', array('a' => $a, 'pid' => $id));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");
    $PAGE->navbar->add("$contester->name");
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);

    echo $OUTPUT->header();

/// Print the main part of the page
	contester_print_begin($contester->id);
	// header
	echo "<br>";
	print_string('solutionlist', 'contester');
	$sql_problem_name = "SELECT problems.name from mdl_contester_problemmap as map, mdl_contester_problems as problems WHERE
	map.contesterid = ? AND map.id = ? AND problems.id = map.problemid";
	echo " ".get_string('oftask', 'contester')." ".$DB->get_field_sql($sql_problem_name, array($contester->id, $pid))."<br>";
	// достаем и выводим список правильных решений.
	//$table = null;
	$table = new html_table();
	$table->head = array(get_string('student', 'contester'), get_string('time', 'contester'), get_string('size', 'contester'));
	$size = 'CHAR_LENGTH(submits.solution)';
	$context = context_module::instance($cm->id);
    $is_admin = has_capability('moodle/site:config', $context);

	if ($is_admin || $DB->get_field('contester', 'freeview', array('id' => $contester->id))) $size = 
	"concat('<a href=show_solution.php?a=$contester->id&sid=', CAST(submits.id AS CHAR), '>', CAST($size AS CHAR), '</a>')";

	$realpid = $DB->get_record('contester_problemmap', array('id' => $pid));
	$realpid = $realpid->problemid;
	$problem = $DB->get_record('contester_problems', array('id' => $realpid));

	$sql = "SELECT submits.id FROM mdl_contester_submits as submits, mdl_contester_testings as test 
	WHERE
		submits.problem=? AND submits.contester=? AND test.submitid=submits.id AND test.taken=test.passed
	";
	//echo $sql;
	$solutions = $DB->get_recordset_sql($sql, array($problem->dbid, $contester->id));

	foreach ($solutions as $solution)
	{
		//print_r(var_export($solution, true));
		$row = array();
		$user = $DB->get_record_sql("SELECT user.firstname, user.lastname FROM mdl_user as user, mdl_contester_submits as submit
			WHERE submit.id=? AND user.id = submit.student", array($solution->id));
		$row[]= $user->firstname.' '.$user->lastname;
		$time = $DB->get_record_sql("SELECT MAX(res.timex) as time FROM mdl_contester_results as res 
			WHERE 
			res.testingid=?", array($solution->id));
		$row[]= $time->time;
		$length = $DB->get_record_sql("SELECT CHAR_LENGTH(solution) as len from mdl_contester_submits
		WHERE id=?", array($solution->id));
		$len = $length->len;
		if ($is_admin || $DB->get_field('contester', 'freeview', array('id' => $contester->id))) 
			$len = "<a href=show_solution.php?a=$contester->id&sid={$solution->id}>".$len."</a>";
		$row[]= $len;
		$table->data []= $row;
	}

/*	$sql = "SELECT CONCAT( user.firstname, ' ', user.lastname ), MAX(results.timex), $size FROM
	user as user, contester_results as results, contester_submits as submits, contester_testings as testings, 
	contester_problemmap as map, contester_problems as problems WHERE
	map.contesterid=$contester->id AND map.id=$pid AND map.problemid=problems.id AND 
	submits.problem = problems.dbid AND testings.submitid=submits.id AND submits.student=user.id AND testings.taken=testings.passed
	AND submits.processed = 255 GROUP BY user.id, submits.id ORDER BY CHAR_LENGTH(submits.solution) ASC
	";
	echo "<textarea>".$sql."</textarea>";

	$tmp = mysql_query($sql);
	while ($row = mysql_fetch_array($tmp))
	{
		unset ($row[0]);
		unset ($row[1]);
		unset ($row[2]);

		$table->data []= $row;
	}
	*/
	if ($table->data === false)
	{
		print_string('nocorrectsolutions', contester);
	} else {
		echo html_writer::table($table);
		//print_table($table);
	}
/// Finish the page
    contester_print_end();

    echo $OUTPUT->footer();

?>
