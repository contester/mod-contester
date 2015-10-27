<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// Выводит все правильные решения данной
/// задачи, предварительно проверив наличие прав доступа.

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID
    $pid = required_param('pid', PARAM_INT); // ID of problem in problemmap

    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
    
        if (! $contester = get_record("contester", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $contester = get_record("contester", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $contester->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("contester", $contester->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    add_to_log($course->id, "contester", "problem_solutions", "problem_solutions.php?a=$contester->id&pid=$pid", "$contester->id");

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strcontesters</a> -> $contester->name", 
                  "", "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/styles.css\" />", true, update_module_button($cm->id, $course->id, $strcontester), 
                  navmenu($course, $cm));

/// Print the main part of the page
	contester_print_begin($contester->id);
	// header
	echo "<br>";
	print_string('solutionlist', 'contester');
	$sql_problem_name = "SELECT problems.name from contester_problemmap as map, contester_problems as problems WHERE
	map.contesterid = $contester->id AND map.id = $pid AND problems.id = map.problemid 
	";
	echo " ".get_string('oftask', 'contester')." ".get_field_sql($sql_problem_name)."<br>";
	// достаем и выводим список правильных решений.
	$table = null;
	$table->head = array(get_string('student', 'contester'), get_string('time', 'contester'), get_string('size', 'contester'));
	$size = 'CHAR_LENGTH(submits.solution)';
	if (isadmin() || get_field('contester', 'freeview', 'id', $contester->id)) $size = 
	"concat('<a href=show_solution.php?a=$contester->id&sid=', CAST(submits.id AS CHAR), '>', CAST($size AS CHAR), '</a>')";
	
	$realpid = get_record('contester_problemmap', 'id', $pid);
	$realpid = $realpid->problemid;
	$problem = get_record('contester_problems', 'id', $realpid);
	
	$sql = "SELECT submits.id FROM contester_submits as submits, contester_testings as test 
	WHERE
		submits.problem=$problem->dbid AND submits.contester=$contester->id AND test.submitid=submits.id AND test.taken=test.passed
	";
	//echo $sql;
	$solutions = get_recordset_sql($sql);
	
	
	foreach ($solutions as $solution)
	{
		//print_r(var_export($solution, true));
		$row = array();
		$user = get_record_sql("SELECT user.firstname, user.lastname FROM user as user, contester_submits as submit
			WHERE submit.id={$solution['id']} AND user.id = submit.student");
		$row[]= $user->firstname.' '.$user->lastname;
		$time = get_record_sql("SELECT MAX(res.timex) as time FROM contester_results as res 
			WHERE 
			res.testingid={$solution['id']}");
		$row[]= $time->time;
		$length = get_record_sql("SELECT CHAR_LENGTH(solution) as len from contester_submits
		WHERE id={$solution['id']}");
		$len = $length->len;
		if (isadmin() || get_field('contester', 'freeview', 'id', $contester->id)) 
			$len = "<a href=show_solution.php?a=$contester->id&sid={$solution['id']}>".$len."</a>";
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
		print_table($table);	
	}
/// Finish the page
	contester_print_end();
    print_footer($course);

?>
