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

    $PAGE->set_url('/mod/contester/problem_solutions.php', array('a' => $a, 'pid' => $id));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");
    $PAGE->navbar->add("$contester->name");
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);

    echo $OUTPUT->header();


    $pd = $DB->get_record_sql('select p.id as problem_id, p.dbid as dbid, p.name as problem_name from {contester_problems} p, {contester_problemmap} m where m.id = ? and m.problemid = p.id', [$pid]);

	contester_print_begin($contester->id);
	echo "<br>";
	print_string('solutionlist', 'contester');
	echo " ".get_string('oftask', 'contester')." ".$pd->problem_name."<br>";
	$table = new html_table();
	$table->head = array(get_string('student', 'contester'), get_string('time', 'contester'), get_string('size', 'contester'));
	$context = context_module::instance($cm->id);
	$is_admin = has_capability('moodle/site:config', $context);

	$sql = 'select submits.id,
		OCTET_LENGTH(submits.solution) as slen,
		u.firstname, u.lastname
	       	from {contester_submits} submits,
		{contester_testings} test,
		{user} u
		where submits.problem = ? and submits.contester=? AND test.submitid=submits.id AND test.taken=test.passed
		AND submits.student = u.id order by submits.id';
	$solutions = $DB->get_recordset_sql($sql, [$pd->dbid, $contester->id]);

	foreach ($solutions as $solution)
	{
		$row = array();
		$row[]= $solution->firstname.' '.$solution->lastname;
		$time = $DB->get_record_sql("SELECT MAX(res.timex) as time FROM mdl_contester_results as res 
			WHERE 
			res.testingid=?", array($solution->id));
		$row[]= $time->time;
		$len = $solution->slen;
		if ($is_admin || $contester->freeview) {
			$len = "<a href=show_solution.php?a=$contester->id&sid={$solution->id}>".$len."</a>";
		}
		$row[]= $len;
		$table->data []= $row;
	}
	$solutions->close();

	if ($table->data === false)
	{
		print_string('nocorrectsolutions', contester);
	} else {
		echo html_writer::table($table);
	}
    contester_print_end();

    echo $OUTPUT->footer();

?>
