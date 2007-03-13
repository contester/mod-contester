<?PHP  // $Id: lib.php,v 1.3 2004/06/09 22:35:27 gustav_delius Exp $

/// Library of functions and constants for module contester
/// (replace contester with the name of your module and delete this line)


$contester_CONSTANT = 7;     /// for example
$contester_SAMPLES_PREFIX = '\\begin{example}';
$contester_SAMPLES_SUFFIX = '\\end{example}';
$contester_SAMPLE_PREFIX = '\\exmp';
$contester_SAMPLE_SUFFIX = '%';


function contester_add_instance($contester) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will create a new instance and return the id number 
/// of the new instance.

    $contester->timemodified = time();

    # May have to add extra stuff in here #
    
    if ($returnid = insert_record("contester", $contester))
    {
    	// Set defaults or add something else...
    }

    return $returnid;
}


function contester_update_instance($contester) {
/// Given an object containing all the necessary data, 
/// (defined by the form in mod.html) this function 
/// will update an existing instance with new data.

    $contester->timemodified = time();
    $contester->id = $contester->instance;

    # May have to add extra stuff in here #
    
    if (isset($contester->add_problem) && (trim($contester->add_problem) != '0'))
    {
    	$map_inst = null;
    	$map_inst->problemid = $contester->add_problem;
    	$map_inst->contesterid = $contester->id;
    	insert_record("contester_problemmap", $map_inst, false);
    	unset($map_inst);
    }
    if (!isset($contester->description)) $contester->description = '';

    return update_record("contester", $contester);
}


function contester_delete_instance($id) {
/// Given an ID of an instance of this module, 
/// this function will permanently delete the instance 
/// and any data that depends on it.  

    if (! $contester = get_record("contester", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! delete_records("contester", "id", "$contester->id")) {
        $result = false;
    }
    if (! delete_records("contester_problemmap", "contesterid", "$contester->id"))
    {
    	$result = false;
    }
    // Deleting submits !!!
    "DELETE FROM mdl_contester_results WHERE testingid IN (SELECT DISTINCT id FROM mdl_contester_testings WHERE submitid IN (SELECT DISTINCT id FROM mdl_contester_submits WHERE contester = {$contester->id}))";
    "DELETE FROM mdl_contester_testings WHERE submitid IN (SELECT DISTINCT id FROM mdl_contester_submits WHERE contester = {$contester->id})";
    if (! delete_records("contester_submits", "contester", "$contester->id"))
    {
    	$result = false;
    }
    

    return $result;
}

function contester_user_outline($course, $user, $mod, $contester) {
/// Return a small object with summary information about what a 
/// user has done with a given particular instance of this module
/// Used for user activity reports.
/// $return->time = the time they did it
/// $return->info = a short text description

	$submits = contester_get_last_submits($contester->id);
	if (count($submits) > 0)
	{
		$submit = $submits[0];
		$return->time = $submits->submitted;
		$return->info = "Latest: Problem: " . $submit->problem . " Gained: " . $submit->points . " After: " . $submit->attempt . "\nTotal: " . contester_get_user_points($contester->id, $user->id);
	}

	return $return;
}

function contester_user_complete($course, $user, $mod, $contester) {
/// Print a detailed representation of what a  user has done with 
/// a given particular instance of this module, for user activity reports.
	
	$submits = contester_get_last_submits($contester->id, 65536, $user->id);
	$result = contester_get_user_points($contester->id, $user->id);

	print_string("Total gained: " . $result, "contester");
	foreach($submits as $line)
	{
		$submit = contester_get_submit($line["id"]);
		print_string("Problem: " . $submit->problem . " Gained: " . $submit->points . " After: " . $submit->attempt, "contester");
	}

    	return true;
}

function contester_print_recent_activity($course, $isteacher, $timestart) {
/// Given a course and a time, this module should find recent activity 
/// that has occurred in contester activities and print it out. 
/// Return true if there was output, or false is there was none.

    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

function contester_cron () {
/// Function to be run periodically according to the moodle cron
/// This function searches for things that need to be done, such 
/// as sending out mail, toggling flags etc ... 

    global $CFG;

    return true;
}

function contester_grades($contesterid) {
/// Must return an array of grades for a given instance of this module, 
/// indexed by user.  It also returns a maximum allowed grade.
///
///    $return->grades = array of grades;
///    $return->maxgrade = maximum allowed grade;
///
///    return $return;
 
   return NULL;
}

function contester_get_participants($contesterid) {
//Must return an array of user records (all data) who are participants
//for a given instance of contester. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.

	$students = get_records_sql("SELECT DISTINCT student FROM mdl_contester_submits WHERE contester = $contesterid");

	return $students;
}

function contester_scale_used ($contesterid,$scaleid) {
//This function returns if a scale is being used by one contester
//it it has support for grading and scales. Commented code should be
//modified if necessary. See forum, glossary or journal modules
//as reference.
   
    $return = false;

    //$rec = get_record("contester","id","$contesterid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other contester functions go here.  Each of them must have a name that 
/// starts with contester_

function contester_get_submit($submitid)
{
	$submit = get_record("contester_submits", "id", $submitid);
	$tmp = get_record_sql("SELECT COUNT(1) as cnt FROM mdl_contester_submits WHERE (contester = {$submit->contester}) AND (student = {$submit->student}) AND (problem = {$submit->problem}) AND (submitted < '{$submit->submitted}')");

	$attempts = 0 + $tmp->cnt;
	
	$result = get_record_sql("SELECT * FROM mdl_contester_testings WHERE (submitid = {$submitid}) ORDER BY id DESC");

	$fields = array("compiled", "taken", "passed");
	foreach($fields as $field)
	{
		$submit->$field = $result->$field;
	}

	if ($submit->taken)
		$submit->points = (30 - $attempts) * $submit->passed / $submit->taken;
	else
		$submit->points = 0;
	$submit->attempt = $attempts + 1;	

	return $submit;
}

/**
* Returns info about given submit
*
* @return object
* @param int $submitid ID of needed submit
*/
function contester_get_submit_info($submitid)
{
	$submit = get_record("contester_submits", "id", $submitid);
	$tmp = get_record_sql("SELECT COUNT(1) as cnt FROM mdl_contester_submits WHERE (contester = {$submit->contester}) AND (student = {$submit->student}) AND (problem = {$submit->problem}) AND (submitted < '{$submit->submitted}')");

	$attempts = 0 + $tmp->cnt;
	
	if (!$testing = get_record_sql("SELECT * FROM mdl_contester_testings WHERE (submitid = {$submitid}) ORDER BY id DESC"))
		$queued = true; else 
		{
			$queued = false;
			$fields = array("compiled", "taken", "passed");
			foreach($fields as $field)
			{
				$submit->$field = $testing->$field;
			}
		}

	

	if ($submit->taken)
		$submit->points = max((30 - $attempts), 15) * $submit->passed / $submit->taken;
	else
		$submit->points = 0;
	$submit->attempt = $attempts + 1;
	//$mapping = get_record("contester_problemmap", "id", $submit->problem, "contesterid", $submit->contester);
	$problem = get_record("contester_problems", "dbid", $submit->problem);
	$res = null;
	$res->problem = $problem->name;
	$lang = get_record("contester_languages", "id", $submit->lang);
	$res->prlanguage = $lang->name;
	if ($submit->processed == 255) {
		$res->status = "<a href=details.php?sid=$submit->id&a=$submit->contester>".get_string('passed', 'contester')." $testing->passed ".get_string('outof', 'contester')." $testing->taken.</a>";
	} else {
		if (!$queued){
			$result = get_record_sql("SELECT * FROM mdl_contester_results WHERE (testingid = {$testing->id}) ORDER BY testingid DESC");
			$res_id = $result->result;
		} else $res_id = 0;
		$res_desc = get_record("contester_resultdesc", "id", $res_id, 'language', 2);
		$res->status = $res_desc->description;
	}
	//$res->solution = $submit->solution;
	$res->points = $submit->points;
	return $res;
}

/**
* Returns detailed info about given submit in table
*
* @return array
* @param int $submitid ID of needed submit
*/
function contester_get_detailed_info($submitid)
{
	$result = array();
	$submit = get_record("contester_submits", "id", $submitid);    
	//print_r($submit);
	$res = null;
	if ($submit->processed == 0) { // если еще пока в очереди
		$res_desc = get_record("contester_resultdesc", "id", 0, 'language', 2);
		$res->status = $res_desc->description;
		$result []= $res;
		return $result;
	}
	if (!$testing = get_record("contester_testings", 'submitid', $submit->id)) {
		$res_desc = get_record("contester_resultdesc", "id", 0, 'language', 2);
		$res->status = $res_desc->description;
		$result []= $res;
		return $result;
	}
	if (!$testing->compiled) {
		$res->status = get_string('ce', 'contester');
		$result []= $res;
		return $result;
	}
	$sql = "SELECT * FROM mdl_contester_results WHERE testingid=$testing->id and not (test = 0)";
	//echo $sql;
	$results = get_recordset_sql($sql);
	while (!$results->EOF)
	{
		$res = null;
		//print_r($results);
		$res->number = $results->fields['test'];
		$res->time = $results->fields['timex'].'ms';
		$res->memory = ($results->fields['memory']/1024).'KB';
		$desc = get_record('contester_resultdesc', 'id', $results->fields['result'], 'language', '2');
		$res->result = $desc->description;
		//print_r($res);
		$result []= $res;
		$results->MoveNext();
	}
	return $result;
}

function contester_obj2assoc($obj)
{
	foreach($obj as $key => $val)
		$result[$key] = $val;
	return result;
}
                                                                                                          
function contester_get_last_submits($contesterid, $cnt = 1, $user = NULL, $problem = NULL)
{
	$query = "SELECT id FROM mdl_contester_submits WHERE (contester = $contesterid) ";
	if ($user != NULL)
		$query .= " AND (student = $user) ";
	if ($problem != NULL)
		$query .= " AND (problem = $problem) ";
	$query .= " ORDER BY submitted DESC LIMIT 0, $cnt ";
	
	$submits = get_recordset_sql($query);
	
	//var_dump($submits);

	$result = array();
	/*foreach($submits as $line)
		$result []= contester_get_submit($line["id"]);*/
	while (!$submits->EOF)
	{
	    $result []= contester_get_submit($submits->fields["id"]);
	    $submits->MoveNext();
	}		    

	return $result;
}

function contester_get_best_submit($contesterid, $user, $problem)
{
	$submits = contester_get_last_submits($contesterid, 65536, $user, $problem);
	$result = 0;
	foreach($submits as $line)
	{
		$submit = contester_get_submit($line->id);
		$result = max($result, $submit->points);
	}
	return $result;
}


function contester_get_user_points($contesterid, $user)
{
	$problems = get_records_select("contester_problemmap", "contesterid = $contesterid", "problemid");
	$result = 0;
	
	foreach($problems as $line)
	{
		$result += contester_get_best_submit($contesterid, $user, $line->problemid);		
	}	
	return $result;
}

function contester_draw_assoc_table($res)
{
    echo "<table width=90% align=left border=1>";    
    foreach($res as $line)
    {
        echo "<tr>";
        foreach($line as $key => $val)
        {
            echo "<td>&nbsp;";
            echo get_string($key, 'contester');;
            echo "</td>";
        }
        echo "</tr>";
        break;
    }
    foreach($res as $line)
    {
        echo "<tr>";
        foreach($line as $key => $val)
        {
            echo "<td>&nbsp;";
            /*echo substr($val, 0, min(strlen($val), 50));
            if (strlen($val) > 50) echo '...';*/
            echo $val;
            echo "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

}

function contester_draw_table_from_sql($query)
{
	$res = array();
        $result = mysql_query($query);
        while($line = mysql_fetch_assoc($result))
 	       $res[] = $line;
	contester_draw_assoc_table($res);
}
/**
* Shows problems mapped to given instance of contester
*
* @param int $instance - id of the contester's instance
*/
function contester_show_problemlist($instance)
{
	echo '<tr valign="top">';
	echo '<td align="right"><b>'.get_string('availableproblems', 'contester').':</b></td>';
    echo '<td align="left">';
    unset($res);
    //echo $instance;    

    $sql = "SELECT mdl_contester_problems.name as name from mdl_contester_problems, mdl_contester_problemmap
WHERE mdl_contester_problemmap.problemid=mdl_contester_problems.id and
mdl_contester_problemmap.contesterid=$instance order by mdl_contester_problems.name";
    //echo $sql;
    $res = get_recordset_sql($sql);
    
    //print_r($res);
    
    foreach ($res as $line)
    {
    	$name = $line['name'];
    	echo "<nobr>$name</nobr><br>";
    }
    echo '</td></tr>';
}
/**
* Shows list of all problems in DB. Name of select:add_problem
*
* values: id-s of problems in DB
*/
function contester_show_problemadd()
{
    echo '<tr valign="top">';
	echo '<td align="right"><b>'.get_string('addproblem', 'contester').':</b></td>';
	echo '<td>';
    unset($choices);
    unset($res);
    $res = get_records_sql("SELECT mdl_contester_problems.id as pr_id, mdl_contester_problems.name as name from mdl_contester_problems ORDER BY mdl_contester_problems.name");
    foreach ($res as $line){
    	$choices[$line->pr_id] = $line->name;
    }
    choose_from_menu($choices, 'add_problem');
    echo '</td></tr>';
}
/**
* Processes updates of tho mod 'contester'
*
* $data->path must contain the path to directory with a problem to add
* to contester.
* @return boolean
* @param object $data $data->path must contain the path to directory with a problem.
*/
function contester_process_options($data)
{
	$file = $data->path;
	assert(file_exists($file."/description"));
	assert(is_file($file."/description"));
	$descr = file_get_contents($file."/description");
	assert(file_exists($file."/Text"));
	assert(is_dir($file."/Text"));
	assert(file_exists($file."/Text/text.tex"));
	assert(is_file($file."/Text/text.tex"));
	$text = file_get_contents($file."/Text/text.tex");
	assert(substr($text, 0, 15) == "\\begin{problem}");
	// Разбор условия
	$text = substr_replace($text, "", 0, 16);
	$alt_descr = substr($text, 0, strpos($text, "}"));
	$text = substr_replace($text, "", 0, strpos($text, "}") + 2);
	// input и output пока никуда не выводятся
	$inp_file = substr($text, 0, strpos($text, "}"));
	$text = substr_replace($text, "", 0, strpos($text, "}") + 2);
	$out_file = substr($text, 0, strpos($text, "}"));
	$text = substr_replace($text, "", 0, strpos($text, "}") + 2);
	// то же самое с timelimit'ом
	$timelimit = substr($text, 0, strpos($text, "}"));
	$text = substr_replace($text, "", 0, strpos($text, "}") + 1);
	$statement = substr($text, 0, strpos($text, "\\InputFile"));
	$text = substr_replace($text, "", 0, strpos($text, "\\InputFile") + 10);
	$inp_format = substr($text, 0, strpos($text, "\\OutputFile"));
	$text = substr_replace($text, "", 0, strpos($text, "\\OutputFile") + 11);
	$out_format = substr($text, 0, strpos($text, "\\Example"));
	// создаем экземпляр, забиваем поля как в БД и вносим запись.
	$problem = null;
	$problem->name = $descr;
	$problem->description = $statement;
	$problem->input_format = $inp_format;
	$problem->output_format = $out_format;
	$problem->dbid = substr($file, strpos($file, ".") + 1) + 0;
	// id сохраняем чтоб внести сэмплы для этой задачи
	$pid = insert_record('contester_problems', $problem);
	// разбор сэмплов
	// может быть ботва если вместо example будет че-то типа examplerich...					
	$text = substr_replace($text, "", 0, strpos($text, "\\Example") + 8);
	$text = substr_replace($text, "", 0, strpos($text, "\\begin{example}") + 15);
	$num = 0;
	while (strpos($text, "\\exmp") !== false) {
		$text = substr_replace($text, "", 0, strpos($text, "\\exmp") + 6);
		// создаем экземпляр сэмпла, пихаем в базу.
		$example = null;
		$example->problem_id = $pid;
		$example->number = $num++;
		$example->input = substr($text, 0, strpos($text, "}"));
		$text = substr_replace($text, "", 0, strpos($text, "}") + 2);
		$example->output = substr($text, 0, strpos($text, "}"));
		$text = substr_replace($text, "", 0, strpos($text, "}") + 2);
		insert_record('contester_samples', $example);
	}
	return true;
}
/**
* Shows navigation bar for given instance of contester
*
* @param int $instance object  of the contester's instance
*/
function contester_show_nav_bar($instance) {
	echo "<nobr><a href=view.php?a=$instance>".get_string('problemlist', 'contester')."</a></nobr><br>";
	echo "<nobr><a href=submit_form.php?a=$instance>".get_string('submit', 'contester')."</a></nobr><br>";
	echo "<nobr><a href=status.php?a=$instance>".get_string('status', 'contester')."</a></nobr><br>";
}
/**
* Something like header
* @param int $instance object  of the contester's instance
*/
function contester_print_begin($instance) {
	echo "<table width=95% height=95%><tr><td valign=top>";
	contester_show_nav_bar($instance);
	echo "</td><td align=center>";
}
/**
* Something like footer
*/
function contester_print_end() {
	echo "</td></tr></table>";
}
?>

