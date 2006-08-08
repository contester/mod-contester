<?PHP  // $Id: lib.php,v 1.3 2004/06/09 22:35:27 gustav_delius Exp $

/// Library of functions and constants for module contester
/// (replace contester with the name of your module and delete this line)


$contester_CONSTANT = 7;     /// for example


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
	$tmp = get_record_sql("SELECT COUNT(1) as cnt FROM mdl_contester_submits WHERE (contester = {$submit->contester}) AND (student = {$submit->student}) AND (problem = {$submit->problem}) AND (submitted < {$submit->submitted})");
	$attempts = $tmp["cnt"];
	
	$result = get_record_sql("SELECT * FROM mdl_contester_testings WHERE (submitid = $submitid) ORDER BY id DESC LIMIT 0, 1");
	$fields = array("compiled", "taken", "pass");
	foreach($fields as $field)
		$submit[$field] = $result[$field];

	$submit["attempt"] = $attempts + 1;
	$submit["points"] = (30 - $attempts) * $submit["passed"] / $submit["taken"];

	return $submit;
}
                                                                                                          
function contester_get_last_submits($contesterid, $cnt = 1, $user = NULL, $problem = NULL)
{
	$query = "SELECT id FROM mdl_contester_submits WHERE (contester = $contesterid) ";
	if ($user != NULL)
		$query .= " AND (student = $user) ";
	if ($problem != NULL)
		$query .= " AND (problem = $problem) ";
	$query .= " LIMIT 0, $cnt ORDER BY submitted DESC ";

	$submits = get_recordset_sql($query);

	$result = array();
	foreach($submits as $line)
		$result []= contester_get_submit($line["id"]);

	return $result;
}

function contester_get_best_submit($contesterid, $user, $problem)
{
	$submits = contester_get_last_submits($contesterid, 65536, $user, $problem);
	$result = 0;
	foreach($submits as $line)
	{
		$submit = contester_get_submit($line["id"]);
		$result = max($result, $submit["points"]);
	}
	return $result;
}


function contester_get_user_points($contesterid, $user)
{
	$problems = get_records_select("contester_problemmap", "contesterid = $contesterid", "problemid");
	$result = 0;
	foreach($problems as $line)
	{
		$result += contester_get_best_submit($contesterid, $user, $line["problemid"]);
	}
	return $result;
}


?>
