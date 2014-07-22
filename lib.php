<?PHP  // $Id: lib.php,v 1.3 2004/06/09 22:35:27 gustav_delius Exp $








// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants for module contester
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the contester specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_contester
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** example constant */
//define('contester_ULTIMATE_ANSWER', 42);

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function contester_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:         return true;
        case FEATURE_SHOW_DESCRIPTION:  return true;

        default:                        return null;
    }
}

/**
 * Saves a new instance of the contester into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $contester An object from the form in mod_form.php
 * @param mod_contester_mod_form $mform
 * @return int The id of the newly inserted contester record
 */
function contester_add_instance(stdClass $contester, mod_contester_mod_form $mform = null) {

    global $DB;

    $contester->timecreated = time();

    # You may have to add extra stuff in here #

    return $DB->insert_record('contester', $contester);
}


/**
 * Updates an instance of the contester in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $contester An object from the form in mod_form.php
 * @param mod_contester_mod_form $mform
 * @return boolean Success/Fail
 */
function contester_update_instance(stdClass $contester, mod_contester_mod_form $mform = null) {

    global $DB;

    $contester->timemodified = time();
    $contester->id = $contester->instance;

    # You may have to add extra stuff in here #

    if (!isset ($contester->freeview)) $contester->freeview = 0;
    if (!isset ($contester->viewown)) $contester->viewown = 0;

    if (isset($contester->add_problem) && (trim($contester->add_problem) != '0'))
    {
    	$map_inst = null;
		foreach ($contester->add_problem as $k=>$v) {
			$map_inst->problemid = $v;
			$map_inst->contesterid = $contester->id;
			$DB->insert_record("contester_problemmap", $map_inst, false);
		}
    	unset($map_inst);
    }

    if (!isset($contester->description)) $contester->description = '';

    $sql = "SELECT mdl_contester_problemmap.id as id
    		FROM   mdl_contester_problemmap
			WHERE  mdl_contester_problemmap.contesterid=$contester->id";
    
    $res = get_recordset_sql($sql);

    foreach ($res as $line)
    {
    	$id = "pid".$line['id'];
    	if (isset($contester->$id)) {
    		if ($contester->$id == "checked")
    			$DB->delete_records('contester_problemmap', 'id', $line['id']);
    	}
    }

    return $DB->update_record('contester', $contester);
}

/**
 * Removes an instance of the contester from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function contester_delete_instance($id) {

    global $DB;

    if (! $contester = $DB->get_record('contester', array('id' => $id))) {
        return false;
    }

    # Delete any dependent records here #
   
    $result = true;

    if (! $DB->delete_records("contester", "id", "$contester->id")) {   //$DB->delete_records('contester', array('id' => $contester->id));
        $result = false;
    }
    if (! $DB->delete_records("contester_problemmap", "contesterid", "$contester->id"))
    {
    	$result = false;
    }
    // Deleting submits !!!
    "DELETE FROM mdl_contester_results
     WHERE testingid IN (SELECT DISTINCT id FROM mdl_contester_testings
                         WHERE submitid IN (SELECT DISTINCT id FROM mdl_contester_submits WHERE contester = {$contester->id}))";
    "DELETE FROM mdl_contester_testings
     WHERE submitid IN (SELECT DISTINCT id FROM mdl_contester_submits
                        WHERE contester = {$contester->id})";
    if (! $DB->delete_records("contester_submits", "contester", "$contester->id"))
    {
    	$result = false;
    }

    return $result;

}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function contester_user_outline($course, $user, $mod, $contester) {

/*
    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
*/
    global $DB;

	unset($return);
	$submits = contester_get_last_submits($contester->id, 65536, $user->id);
	if ($submits && count($submits) > 0)
	{
		$submit = $submits[0];
		// yyyy-mm-dd hh:mm:ss
	    $full = explode(" ", $submit->submitted);
	    $date = explode("-", $full[0]);
        $time = explode(":", $full[1]);
        // int mktime(int hour, int minute, int second, int month, int day, int year [, int is_dst])
        $return->time = mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);

		$res = $DB->get_record('contester_problems', 'dbid', $submit->problem);
		$return->info = get_string("problem", "contester")." ".$submit->problem." (".$res->name.")<br />".
			get_string("points", "contester").": ".$submit->points;
			//. " After: " . $submit->attempt . " Total: " . contester_get_user_points($contester->id, $user->id);
	}

	return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $contester the module instance record
 * @return void, is supposed to echp directly
 */
function contester_user_complete($course, $user, $mod, $contester) {
    
     global $DB;

	unset($submits);
	$submits = contester_get_last_submits($contester->id, 65536, $user->id);
      print_string("total", "contester");
	if ($submits && (count($submits) > 0))
	{
		$result = contester_get_user_points($contester->id, $user->id);
		echo ": ".$result.".";

		foreach($submits as $line)
		{
			echo "<br />";
			$submit = contester_get_submit($line->id);

			$res = $DB->get_record('contester_problems', 'dbid', $submit->problem);
			echo get_string("problem", "contester")." ".$submit->problem." (".$res->name.") - ".
				get_string("points", "contester").": ".$submit->points."; ";
				//. " After: " . $submit->attempt, "contester");
		}
	}
	else
	{
		 echo ": 0.";
	}
    return true;
}


/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in contester activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function contester_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link contester_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function contester_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see contester_get_recent_mod_activity()}

 * @return void
 */
function contester_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function contester_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function contester_get_extra_capabilities() {
    return array();
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Is a given scale used by the instance of contester?
 *
 * This function returns if a scale is being used by one contester
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $contesterid ID of an instance of this module
 * @return bool true if the scale is used by the given contester instance
 */
function contester_scale_used($contesterid, $scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists('contester', array('id' => $contesterid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of contester.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any contester instance
 */
function contester_scale_used_anywhere($scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists('contester', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the give contester instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $contester instance object with extra cmidnumber and modname property
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return void
 */
function contester_grade_item_update(stdClass $contester, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    /** @example */
    $item = array();
    $item['itemname'] = clean_param($contester->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = $contester->grade;
    $item['grademin']  = 0;

    grade_update('mod/contester', $contester->course, 'mod', 'contester', $contester->id, 0, null, $item);
}

/**
 * Update contester grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $contester instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function contester_update_grades(stdClass $contester, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    /** @example */
    $grades = array(); // populate array of grade objects indexed by userid

    grade_update('mod/contester', $contester->course, 'mod', 'contester', $contester->id, 0, $grades);
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function contester_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for contester file areas
 *
 * @package mod_contester
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function contester_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the contester file areas
 *
 * @package mod_contester
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the contester's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function contester_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding contester nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the contester module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function contester_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the contester settings
 *
 * This function is called when the context for the page is a contester module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $contesternode {@link navigation_node}
 */
function contester_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $contesternode=null) {
}



////////////////////////////////////////////////////////////////////////////////
// OLD                                                                        //
////////////////////////////////////////////////////////////////////////////////

$contester_SAMPLES_PREFIX = '\\begin{example}';
$contester_SAMPLES_SUFFIX = '\\end{example}';
$contester_SAMPLE_PREFIX = '\\exmp';
$contester_SAMPLE_SUFFIX = '%';

/*
*
* function contester_choose_from_list is choose_from_menu with additional parameters
*
*/
function contester_choose_from_list ($options, $name, $multiple=false, $size=1, $selected='', $nothing='choose', $script='',
                           $nothingvalue='0', $return=false, $disabled=false, $tabindex=0) {

    if ($nothing == 'choose') {
        $nothing = get_string('choose') .'...';
    }

    $attributes = ($script) ? 'onchange="'. $script .'"' : '';
    if ($disabled) {
        $attributes .= ' disabled="disabled"';
    }

    if ($tabindex) {
        $attributes .= ' tabindex="'.$tabindex.'"';
    }

	if ($multiple) {
		$attributes .= ' multiple="multiple"';
	}

    $output = '<select id="menu'.$name.'" name="'. $name .'" '. $attributes .' size="'. $size .'">' . "\n";
    if ($nothing) {
        $output .= '   <option value="'. $nothingvalue .'"'. "\n";
        if ($nothingvalue === $selected) {
            $output .= ' selected="selected"';
        }
        $output .= '>'. $nothing .'</option>' . "\n";
    }
    if (!empty($options)) {
        foreach ($options as $value => $label) {
            $output .= '   <option value="'. $value .'"';
            if ((string)$value == (string)$selected) {
                $output .= ' selected="selected"';

            }
            if ($label === '') {
                $output .= '>'. $value .'</option>' . "\n";
            } else {
                $output .= '>'. $label .'</option>' . "\n";
            }
        }
    }
    $output .= '</select>' . "\n";

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


function contester_grades($contesterid) {
/// Must return an array of grades for a given instance of this module,
/// indexed by user.  It also returns a maximum allowed grade.
///
///    $return->grades = array of grades;
///    $return->maxgrade = maximum allowed grade;
///
///    return $return;
//	echo "lo";
//	var_dump($contesterid);             	echo "<br>";
	$students = contester_get_participants($contesterid);
	$return = null;
	/*echo "$contesterid<br>";
	echo "<br>";
	print_r($students);
	echo "<br>";*/
	foreach ($students as $student)
	{
		/*print_r($student);
		echo "<br>";*/
		$return->grades[$student['student']] = contester_get_user_points($contesterid, $student['student']);
	}

	$problems = $DB->get_records_select("contester_problemmap", "contesterid = $contesterid", "problemid");
	$return->maxgrade = sizeof($problems) * 30;
	/*print_r($return);
	echo "<br>";*/
// 	var_dump($return);	echo "<br>";

   return $return;
}

function contester_get_participants($contesterid) {
//Must return an array of user records (all data) who are participants
//for a given instance of contester. Must include every user involved
//in the instance, independient of his role (student, teacher, admin...)
//See other modules as example.

	//echo $contesterid;
	$sql = "SELECT DISTINCT student FROM mdl_contester_submits WHERE contester=$contesterid";
	$students = mysql_query($sql);
	$ret = null;
	while ($student = mysql_fetch_assoc($students)) $ret []= $student;
/*	echo "<br>$sql<br>";
	print_r($students);
	$return = array();
	foreach ($students as $student)
	{
		$return[$student->student] = 1;
	}
	$students = array();
	foreach($return as $key => $value)
	{
		$students[]=$key;
	}
	*/
	return $ret;
}


/**
* Called by course/reset.php
* @param $mform form passed by reference
*/
function contester_reset_course_form_definition(&$mform) {
   $mform->addElement('header', ' contesterheader', get_string('modulenameplural', 'contester'));
}

/**
* Course reset form defaults.
*/
function contester_reset_course_form_defaults($course) {
   return array('reset_contester_all'=>1);
}
                 
function contester_reset_userdata($data)
{
    $status = array();
    return $status;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other contester functions go here.  Each of them must have a name that
/// starts with contester_

function contester_get_submit($submitid)
{
    global $DB;

	$submit = $DB->get_record("contester_submits", "id", $submitid);
	$tmp = $DB->get_record_sql("SELECT  COUNT(1) as cnt
						   FROM    mdl_contester_submits
						   WHERE   (contester = {$submit->contester})
						   AND     (student = {$submit->student})
						   AND     (problem = {$submit->problem})
						   AND     (submitted < '{$submit->submitted}')");
	$attempts = 0 + $tmp->cnt;

	$result = $DB->get_record_sql("SELECT    *
							  FROM      mdl_contester_testings
							  WHERE     (submitid = {$submitid})
							  ORDER BY  id
							  DESC");

	$fields = array("compiled", "taken", "passed");
	foreach($fields as $field)
	{
		$submit->$field = $result->$field;
	}

	if ($submit->compiled && $submit->taken)
		$submit->points = contester_get_rounded_points($attempts, $submit->passed, $submit->taken);
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
    global $DB;
	//$submit = contester_get_submit($submitid);

	
	$submit = $DB->get_record("contester_submits", "id", $submitid);
	$tmp = $DB->get_record_sql("SELECT COUNT(1) as cnt
						   FROM            mdl_contester_submits
						   WHERE           (contester = {$submit->contester})
						   AND             (student = {$submit->student})
						   AND             (problem = {$submit->problem})
						   AND             (submitted < '{$submit->submitted}')");

	$attempts = 0 + $tmp->cnt;

	if (!$testing = $DB->get_record_sql("SELECT   *
									FROM     mdl_contester_testings
									WHERE    (submitid = {$submitid})
									ORDER BY id DESC"))
		$queued = true;
	else
	{
		$queued = false;
		$fields = array("compiled", "taken", "passed");
		foreach($fields as $field)
		{
			$submit->$field = $testing->$field;
		}
	}

	if ($submit->compiled && $submit->taken)
		$submit->points = contester_get_rounded_points($attempts, $submit->passed, $submit->taken);
	else
		$submit->points = 0;
	$submit->attempt = $attempts + 1;
	
	//$mapping = $DB->get_record("contester_problemmap", "id", $submit->problem, "contesterid", $submit->contester);
	$problem = $DB->get_record("contester_problems", "dbid", $submit->problem);
	$res = null;
	$res->problem = $problem->name;
	$lang = $DB->get_record("contester_languages", "id", $submit->lang);
	$res->prlanguage = $lang->name;

	if ($submit->processed == 255)
	{
		if ($submit->compiled)
			$res->status = "<a href=details.php?sid=$submit->id&a=$submit->contester>".
				get_string('passed', 'contester')." $submit->passed ".
				get_string('outof', 'contester')." $submit->taken.</a>";
		else
		{
			$res_id = 2;
			$res_desc = $DB->get_record("contester_resultdesc", "id", $res_id, 'language', 2);
			$res->status = $res_desc->description;
		}
	}
	else
	{
		if (!$queued)
		{
			/*$result = get_record_sql("SELECT    *
						  FROM      mdl_contester_results
						  WHERE    (testingid = {$testing->id})
						  ORDER BY  testingid DESC");
		        */
			$res_id = 1; //$result->result;
		}
		else
			$res_id = 0;
		$res_desc = $DB->get_record("contester_resultdesc", "id", $res_id, 'language', 2);
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
    global $DB;

	$result = array();
	$submit = $DB->get_record("contester_submits", "id", $submitid);
	//print_r($submit);
	$res = null;
	if ($submit->processed == 0) { // если еще пока в очереди
		$res_desc = $DB->get_record("contester_resultdesc", "id", 0, 'language', 2);
		$res->status = $res_desc->description;
		$result []= $res;
		return $result;
	}

	if (!$testing = $DB->get_record_sql("SELECT * FROM mdl_contester_testings WHERE (submitid = {$submitid}) ORDER BY id DESC")) {
		$res_desc = $DB->get_record("contester_resultdesc", "id", 0, 'language', 2);
		$res->status = $res_desc->description;
		$result []= $res;
		return $result;
	}
	if (!$testing->compiled) {
		//$res->status = get_string('ce', 'contester');
		$res_desc = $DB->get_record("contester_resultdesc", "id", 1, 'language', 2);
		$res->status = $res_desc->description;
		$result []= $res;
		return $result;
	}
	$sql = "SELECT * FROM mdl_contester_results WHERE testingid=$testing->id and not (test = 0)";
	//echo $sql;
	$results = $DB->get_recordset_sql($sql);
	while (!$results->EOF)
	{
		$res = null;
		//print_r($results);
		$res->number = $results->fields['test'];
		$res->time = $results->fields['timex'].'ms';
		$res->memory = ($results->fields['memory']/1024).'KB';
		$desc = $DB->get_record('contester_resultdesc', 'id', $results->fields['result'], 'language', '2');
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
	return $result;
}

function contester_get_last_submits($contesterid, $cnt = 1, $user = NULL, $problem = NULL, $datefrom = NULL, $dateto = NULL)
{
    global $DB;

	$query = "SELECT id FROM mdl_contester_submits WHERE (contester = $contesterid) ";
	if ($user != NULL)
		$query .= " AND (student = $user) ";
	if ($datefrom != NULL)
		$query .= " AND (submitted >= \"$datefrom\") ";
	if ($dateto != NULL)
		$query .= " AND (submitted <= \"$dateto\") ";
	if ($problem != NULL)
	{
		$res = $DB->get_record('contester_problems', 'id', $problem);
		$problem = $res->dbid;
		$query .= " AND (problem = $problem) ";
	}
	$query .= " ORDER BY submitted DESC LIMIT 0, $cnt ";
	//echo "$query<br>";
	$submits = $DB->get_recordset_sql($query);

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
	//error($user);
//	var_dump($contesterid." ".$user." ".$problem); 	echo "<br>";
	$submits = contester_get_last_submits($contesterid, 65536, $user, $problem);
	$result = 0;
	$correct = false;
	foreach($submits as $line)
	{
		$submit = contester_get_submit($line->id);
//		if ($user == "332") {var_dump($submit);               	echo "<br>";}
		if ($submit->taken == $submit->passed) $correct = true;
		$result = max($result, $submit->points);
	}
//	if ($correct === true) $result = "<b>$result</b>";
	return $result;
}

function contester_get_best_submit_reference($contesterid, $user, $problem, $datefrom, $dateto)
{
	$submits = contester_get_last_submits($contesterid, 65536, $user, $problem, $datefrom, $dateto);
	$result = -5;
	$sid = -1;
	$correct = false;
	foreach($submits as $line)
	{
		$submit = contester_get_submit($line->id);
//		if ($user == "332") {var_dump($submit);               	echo "<br>";}
		if ($submit->taken == $submit->passed) $correct = true;
		if ($result <= $submit->points)
		{
			$result = $submit->points;
			$taken = $submit->taken;
			$passed = $submit->passed;

			$sid = $submit->id;
		}
	}
	$points = $result;
//	if ($correct === true) $result = "<b>$result</b>";
	if ($sid == -1) return ""; else
	{
		$s = "";
		if ($correct) $result = '+ ' . $points; else
			$result = '- ('.$passed.'/'.$taken.')';

		$s = sprintf("<a href=\"show_solution.php?a=%d&sid=%d\">%s</a>", $contesterid, $sid, $result);
		return $s;
	}
}

// как contester_get_best_submit_reference, но ещё last среди best
function contester_get_last_best_submit_reference($contesterid, $user, $problem, $datefrom, $dateto)
{
	$submits = contester_get_last_submits($contesterid, 65536, $user, $problem, $datefrom, $dateto);
	$result = -5;
	$mincorrectresult = -5;
	$sid = -1;
	$taken = 0;
	$correct = false;
	foreach($submits as $line)
	{
		$submit = contester_get_submit($line->id);

		if ($submit->taken == 0) continue;
		// another correct
		if (($correct) && ($submit->taken == $submit->passed))
		{
			if ($mincorrectresult > $submit->points)
			{
				$mincorrectresult = $submit->points;
				$sid = $submit->id;
			}
			if ($result < $submit->points)
			{
				$result = $submit->points;
			}
		}

		// correct or better
		if ((!$correct) && ($result <= $submit->points))
		{
			if ($submit->taken == $submit->passed)
			{
				$correct = true;
				$mincorrectresult = $submit->points;
			}
			$result = $submit->points;
			$taken = $submit->taken;
			$passed = $submit->passed;

			$sid = $submit->id;
		}
	}
	$points = $result;

	if ($sid == -1 || $taken == 0) return "";
	else
	{
		$s = "";
		if ($correct) $result = '+ ' . $points; else
			$result = '- ('.$passed.'/'.$taken.')';

		$s = sprintf("<a href=\"show_solution.php?a=%d&sid=%d\">%s</a>", $contesterid, $sid, $result);
		return $s;
	}
}

// берём последнее из правильных или последнее из неправильных, если правильных не было
function contester_get_last_or_last_correct_submit_reference($contesterid, $user, $problem, $datefrom, $dateto)
{
	$submits = contester_get_last_submits($contesterid, 65536, $user, $problem, $datefrom, $dateto);

	// ^ sorted by submitted DESC
	$sid = -1;
	$points = -5;
	$taken = 0;
	$passed = 0;
	$correct = false;
	$compiled = false;


	foreach($submits as $line)
	{
		$submit = contester_get_submit($line->id);
		//if ($submit->compiled) echo '!';
		if ($submit->taken == 0) continue;
		if ($sid == -1) // no one yet
		{
			$sid = $submit->id;
			$points = $submit->points;
			$taken = $submit->taken;
			$passed = $submit->passed;
			$compiled = $submit->compiled;
			if ($taken == $passed)
			{
				$correct = true;
			}

		}
		else
		{
			if ($submit->taken == $submit->passed) // correct
			{
				$points = $submit->points;
				if (!$correct) 	//first correct
				{
					$compiled = $submit->compiled;
					$correct = true;
					$sid = $submit->id;
					$taken = $submit->taken;
					$passed = $submit->passed;
				}
			}
		}
	}

	if ($sid == -1 || $taken == 0) return "";
	else
	{
		$s = "";
		$result = "";
		if ($correct) $result = '+ ' . $points;
		else $result = '- ('.$passed.'/'.$taken.')';

		$s = sprintf("<a href=\"show_solution.php?a=%d&sid=%d\">%s</a>", $contesterid, $sid, $result);
		if (!$correct)
			$s = $s." ".sprintf("<a href=\"details.php?a=%d&sid=%d\">%s</a>", $contesterid, $sid, "*");
		return $s;
	}
}


// Like contester_get_last_best_submit_reference. Only result without reference
function contester_get_result_without_reference($contesterid, $user, $problem, $datefrom, $dateto)
{
	$submits = contester_get_last_submits($contesterid, 65536, $user, $problem, $datefrom, $dateto);
	$result = -5;
	$mincorrectresult = -5;
	$sid = -1;
	$correct = false;
	foreach($submits as $line)
	{
		$submit = contester_get_submit($line->id);

		// another correct
		if (($correct) && ($submit->taken == $submit->passed))
		{
			if ($mincorrectresult > $submit->points)
			{
				$mincorrectresult = $submit->points;
				$sid = $submit->id;
			}
			if ($result < $submit->points)
			{
				$result = $submit->points;
			}
		}

		// correct or better
		if ((!$correct) && ($result <= $submit->points))
		{
			if ($submit->taken == $submit->passed)
			{
				$correct = true;
				$mincorrectresult = $submit->points;
			}
			$result = $submit->points;
			$taken = $submit->taken;
			$passed = $submit->passed;

			$sid = $submit->id;
		}
	}
	$points = $result;
	if ($sid == -1 || $taken == 0) return ""; else
	{
		if ($correct) $result = '+ ' . $points; else
			$result = '- ('.$passed.'/'.$taken.')';
		return $result;
	}
}

function contester_get_user_points($contesterid, $user)
{
	global $DB;
//	var_dump($contesterid." ".$user); 	echo "<br>";
	$problems = $DB->get_recordset_select("contester_problemmap", "contesterid = $contesterid", "problemid");
	$result = 0;
	//print_r($problems);
	foreach($problems as $line)
	{
		if ($line['problemid'] && $line['problemid'] != 0)
			$result += contester_get_best_submit($contesterid, $user, $line['problemid']);
	}
	return $result;
}

function contester_get_rounded_points($attempts, $passed, $taken)
{
	if ($taken > $passed)
		return round(max(30 - $attempts, 15) * $passed / $taken /1.5, 2);
	return round(max(30 - $attempts, 15), 0);
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
	global $DB;
	echo '<tr valign="top">';
	echo '<td align="right"><b>'.get_string('availableproblems', 'contester').':</b></td>';
    echo '<td align="left">';
    echo '<table><tr><td colspan=3>'.get_string('problemstodelete', 'contester').'</td></tr>';
    unset($res);

    $sql = "SELECT   mdl_contester_problems.name as name,
    				 mdl_contester_problemmap.id as id,
    				 mdl_contester_problems.id as pid,
    				 mdl_contester_problems.dbid as dbid
    		from	 mdl_contester_problems, mdl_contester_problemmap
    		WHERE	 mdl_contester_problemmap.problemid=mdl_contester_problems.id
    			and  mdl_contester_problemmap.contesterid=$instance
    		order by mdl_contester_problemmap.id";

    $res = $DB->get_recordset_sql($sql);
    //print_r($res);

    foreach ($res as $line)
    {
    	$name = $line['name'];
    	echo "<tr><td><input type=checkbox name=\"pid".$line['id']."\" value=checked></td><td size=60%>
    	<nobr>$name</nobr></td>";
    	if (isadmin()) echo "<td size=40%><nobr>
    		<a href=$CFG->dirroot/mod/contester/problem_details.php?a=$instance&pid=".$line['pid'].">".
    		get_string('problemdetails', 'contester')." (".$line['dbid'].")</a></nobr></td>";
    	echo "</tr>";
    }
    echo '</table></td></tr>';
}

function contester_get_all_tags()
{
	global $DB;
	unset($res);
	$res = $DB->get_records_sql("SELECT   mdl_contester_tags.id  as id,
									 mdl_contester_tags.tag as tag,
									 COUNT(mdl_contester_tagmap.tagid) as count
							FROM     mdl_contester_tags LEFT JOIN mdl_contester_tagmap
							ON       mdl_contester_tags.id=mdl_contester_tagmap.tagid
							GROUP BY mdl_contester_tags.id
							ORDER BY mdl_contester_tags.tag");
    return $res;
}

function contester_count_all_problems()
{
	global $DB;
	unset($num);
	$num = $DB->get_records_sql("SELECT   COUNT(mdl_contester_problems.id) as n
							FROM     mdl_contester_problems");
    return array_shift($num)->n;
}

function contester_count_all_tags()
{
	global $DB;
	unset($num);
	$num = $DB->get_records_sql("SELECT   COUNT(mdl_contester_tags.id) as n
							FROM     mdl_contester_tags");
    return array_shift($num)->n;
}

function contester_show_tags_ref($instance, $sort, $ifall="")
{
	unset($tags);
	$tags = contester_get_all_tags();

	echo "<a href=$CFG->dirroot/mod/contester/problems_preview".$ifall.".php?a=$instance&sort=".$sort.
    		"&tag=0>".get_string("alltags", "contester").' ('.contester_count_all_problems().')'."</a> ";
	foreach ($tags as $item)
    {
    	echo "<nobr><a href=$CFG->dirroot/mod/contester/problems_preview".$ifall.".php?a=$instance&sort=".$sort.
    		"&tag=".$item->id.">".$item->tag.' ('.$item->count.')'."</a></nobr> ";
    }
}

function contester_get_problem_tags($pid)
{
	global $DB;
	unset($tags);
    $tags = $DB->get_records_sql("SELECT   mdl_contester_tags.tag as tag,
                                      mdl_contester_tags.id as id,
                                      mdl_contester_tagmap.id as mid
    						 FROM     mdl_contester_tagmap LEFT JOIN mdl_contester_tags
    						       ON mdl_contester_tagmap.tagid = mdl_contester_tags.id
    						 WHERE	  mdl_contester_tagmap.problemid = ".$pid."
    						 ORDER BY mdl_contester_tags.tag");
	return $tags;
}

function contester_get_not_problem_tags($pid)
{
	global $DB;
	unset($tags);
    $tags = $DB->get_records_sql("SELECT   mdl_contester_tags.tag,
                                      mdl_contester_tags.id
    						 FROM     mdl_contester_tags LEFT JOIN mdl_contester_tagmap
    						       ON mdl_contester_tagmap.tagid = mdl_contester_tags.id
    						 WHERE 	  0 = (SELECT COUNT(mdl_contester_tagmap.id)
    						               FROM   mdl_contester_tagmap
    						 		 	   WHERE  mdl_contester_tagmap.problemid = ".$pid."
    						 		   		 AND  mdl_contester_tagmap.tagid = mdl_contester_tags.id)
  				 		     GROUP BY mdl_contester_tags.tag
    						 ORDER BY mdl_contester_tags.tag");
	return $tags;
}

function contester_show_problem_tags($pid)
{
	unset($tags);
    $tags = contester_get_problem_tags($pid);
    foreach ($tags as $item)
    {
    	echo $item->tag." ";
    }
}

function contester_show_problems_preview($instance, $sort, $tag)
{
	global $DB;
	unset($res);
    unset($order);
    unset($whtag);
   	if ($sort == 1) {$order = "mdl_contester_problems.name";}
   	else {$order = "mdl_contester_problems.dbid";}
   	if ($tag != 0) {$whtag = " WHERE EXISTS (SELECT mdl_contester_tagmap.id
   											FROM   mdl_contester_tagmap
   											WHERE  mdl_contester_tagmap.problemid=mdl_contester_problems.id
   											       AND
   											       mdl_contester_tagmap.tagid=".$tag.") ";}
   	else {$whtag = "";}

	$res = $DB->get_records_sql("SELECT   mdl_contester_problems.id as pr_id,
	   							     mdl_contester_problems.name as name,
   								     mdl_contester_problems.dbid as dbid
   							FROM     mdl_contester_problems".$whtag."
   							ORDER BY ".$order);

    echo '<table cellpadding=5 border=1 bordercolor=#D0D0D0>';
    echo '<tr>';
    if ($sort == 0)
    {
   		echo "<th>".get_string('id', 'contester')."</th>";
    	echo "<th><a href=$CFG->dirroot/mod/contester/problems_preview.php?a=$instance&sort=1&tag=".$tag.">".
    		get_string('problemname', 'contester')."</a></th>";
    }
    else
    {
   		echo "<th><a href=$CFG->dirroot/mod/contester/problems_preview.php?a=$instance&sort=0&tag=".$tag.">".
   			get_string('id', 'contester')."</a></th>";
    	echo "<th>".get_string('problemname', 'contester')."</th>";
    }

   	echo '<th>'.get_string('tags', 'contester');
   	echo '</th>';

    echo '</tr>';
    foreach ($res as $line)
    {
    	echo '<tr>';
    	echo '<td>'.$line->dbid.'</td>';
    	echo "<td><a href=$CFG->dirroot/mod/contester/problem_preview.php?a=$instance&pid=".$line->pr_id.">".$line->name."</a></td>";

   		echo "<td><span id=taglist>";
   		contester_show_problem_tags($line->pr_id);
   		echo "</span></td>";
    	echo '</tr>';
    }
    echo '</table>';
}

function contester_get_problems_preview_all($instance, $sort, $tag)
{
	global $DB;
	unset($res);
    unset($order);
    unset($whtag);
   	if ($sort == 1) {$order = "mdl_contester_problems.name";}
   	else {$order = "mdl_contester_problems.dbid";}
   	if ($tag != 0) {$whtag = " WHERE EXISTS (SELECT mdl_contester_tagmap.id
   											 FROM   mdl_contester_tagmap
   											 WHERE  mdl_contester_tagmap.problemid=mdl_contester_problems.id
   											      AND
   											        mdl_contester_tagmap.tagid=".$tag.") ";}
   	else {$whtag = "";}

	$res = $DB->get_records_sql("SELECT  mdl_contester_problems.id as id,
	   							    mdl_contester_problems.name as name,
   								    mdl_contester_problems.dbid as dbid,
   								    mdl_contester_problems.description as description,
   								    mdl_contester_problems.input_format as input,
   								    mdl_contester_problems.output_format as output
   							    FROM     mdl_contester_problems".$whtag."
   							    ORDER BY ".$order);
 	return $res;
}

function contester_print_link_to_problem($instance, $pid)
{
	echo "<a href=$CFG->dirroot/mod/contester/problem.php?a=$instance&pid=$pid>".
   		get_string('problemstatement', 'contester')."</a>";
}

function contester_print_link_to_problem_details($instance, $pid, $dbid)
{
   	if (isadmin())
   		echo "<a href=$CFG->dirroot/mod/contester/problem_details.php?a=$instance&pid=$pid>".
	   		get_string('problemdetails', 'contester')." (".$dbid.")</a>";
}

function contester_print_link_to_problem_tags_details($instance, $pid)
{
   	if (isadmin())
   		echo "<a href=$CFG->dirroot/mod/contester/problem_tags_details.php?a=$instance&pid=$pid>".
	   		get_string('tagsdetails', 'contester')."</a>";
}

function contester_print_link_to_upload()
{
	if (isadmin())
		echo "<a href=$CFG->dirroot/mod/contester/upload_problem_form.php>".get_string('uploadtask', 'contester')."</a>";
}

function contester_print_link_to_problems_preview($instance)
{
	global $DB;
	if (! $contester = $DB->get_record("contester", "id", $instance)) {
    	error("Course module is incorrect");
 	}
    if (! $course = $DB->get_record("course", "id", $contester->course)) {
    	error("Course is misconfigured");
    }
    if (! $cm = get_coursemodule_from_instance("contester", $contester->id, $course->id)) {
    	error("Course Module ID was incorrect");
    }
	$context = get_context_instance(CONTEXT_MODULE, $cm->id);
	$is_teacher = has_capability('moodle/course:viewhiddenactivities', $context);
//	echo "<p>is_teacher".$is_teacher."</p>";
	if (isadmin() || $is_teacher)
		echo "<p><a href=$CFG->dirroot/mod/contester/problems_preview.php?a=$instance>".get_string('problemspreview', 'contester')."</a></p>";
}

/**
* Shows select-list of all problems in DB. Name of <select> - tag in HTML: add_problem
*
* values: id-s of problems in DB
*/
function contester_show_problemadd()
{
	global $DB;
    echo '<tr valign="top">';
	echo '<td align="right"><b>'.get_string('addproblem', 'contester').':</b></td>';
	echo '<td>';
    unset($choices);
    unset($res);
    $res = $DB->get_records_sql("SELECT   mdl_contester_problems.id as pr_id,
    								 mdl_contester_problems.dbid as dbid,
    							     mdl_contester_problems.name as name
    						FROM     mdl_contester_problems
    						ORDER BY mdl_contester_problems.dbid");
    foreach ($res as $line){
    	$choices[$line->pr_id] = $line->dbid." ".$line->name;
    }
    contester_choose_from_list($choices, 'add_problem[]', true, 20); //multiple + 20 rows
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
	/*$file = $data->path;
	assert(file_exists($file."/description"));
	assert(is_file($file."/description"));
	$descr = file_get_contents($file."/description");
	assert(file_exists($file."/Text"));
	assert(is_dir($file."/Text"));
	assert(file_exists($file."/Text/text.tex"));
	assert(is_file($file."/Text/text.tex"));
*/
	return true;
}

/**
 * Parses $text, containing problem description and samples of input and output, and adds it into
 * DB.
 *
 * @param string $text - problem definition.
 * @param string $dbid - id of problem in contester's DB.
 */
function contester_parse_task($text, $dbid)
{
	dlobal $DB;
	//convert_cyr_string()
	$text = iconv("windows-1251", "UTF-8", $text);
	//echo $text;
	assert(substr($text, 0, 15) == "\\begin{problem}");
	// Разбор условия
	$text = substr_replace($text, "", 0, 16);
	$alt_descr = substr($text, 0, strpos($text, "}"));
	$text = substr_replace($text, "", 0, strpos($text, "}") + 2);
	// input и output никуда не выводятся
	$inp_file = substr($text, 0, strpos($text, "}"));
	$text = substr_replace($text, "", 0, strpos($text, "}") + 2);
	$out_file = substr($text, 0, strpos($text, "}"));
	$text = substr_replace($text, "", 0, strpos($text, "}") + 2);
	// то же самое с timelimit'ом
	$timelimit = substr($text, 0, strpos($text, "}"));
	$text = substr_replace($text, "", 0, strpos($text, "}") + 1);
	$statement = substr($text, 0, strpos($text, "\\InputFile"));
	if ($statement[0] == '{') $statement = substr($statement, strpos($statement, '}') + 1);
	$text = substr_replace($text, "", 0, strpos($text, "\\InputFile") + 10);
	$inp_format = substr($text, 0, strpos($text, "\\OutputFile"));
	$text = substr_replace($text, "", 0, strpos($text, "\\OutputFile") + 11);
	$out_format = substr($text, 0, strpos($text, "\\Example"));
	// создаем экземпляр, забиваем поля как в БД и вносим запись.
	$problem = null;
	$problem->name = $alt_descr;
	$statement = trim($statement);
	$problem->description = $statement;
	$inp_format = trim($inp_format);
	$problem->input_format = $inp_format;
	$out_format = trim($out_format);
	$problem->output_format = $out_format;
	$problem->dbid = $dbid;
	// id сохраняем чтоб внести сэмплы для этой задачи
	$pid = $DB->insert_record('contester_problems',$problem);
	//print_r($problem);
	echo "<br/>";
	//p($pid);
	p(mysql_error());
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
		$example->output = rtrim($example->output); //тут может быть проблема, если по условию задачи
		$example->input = rtrim($example->input); //допускаются не "well-formed" тесты
		$text = substr_replace($text, "", 0, strpos($text, "}") + 2);
		$DB->insert_record('contester_samples', $example);
	}
}

/**
* Shows detailed info about problem with ability to edit
*
* @param int $pid id of problem to edit
*/
function contester_show_problem_details($pid)
{
	global $DB;
	//echo $usehtmleditor='Gecko';
	$usehtmleditor = can_use_html_editor();
	if (!$problem = $DB->get_record('contester_problems', 'id', $pid)) {
		error(get_string('noproblem'));
		return false;
	}
?>
<table cellpadding="5">
<tr valign="top">
    <td align="right"><b><?php  print_string("name") ?>:</b></td>
    <td>
        <input type="text" name="name" size="60" value="<?php  p($problem->name) ?>">
    </td>
</tr>
<!-- More rows go in here... -->
<tr valign="top">
    <td align="right"><b><?php print_string("description", "contester") ?>:</b>
    </td>
    <td>
    <?php
       print_textarea($usehtmleditor, 20, 60, 680, 400, "description", $problem->description);
       echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
    ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php print_string("inputformat", "contester") ?>:</b>
    </td>
    <td>
    <?php
       print_textarea($usehtmleditor, 20, 60, 680, 400, "inputformat", $problem->input_format);
       echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
    ?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php print_string("outputformat", "contester") ?>:</b>
    </td>
    <td>
    <?php
       print_textarea($usehtmleditor, 20, 60, 680, 400, "outputformat", $problem->output_format);
       echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
    ?>
    </td>
</tr>
	<?php
		$table = null;
		/*print_string('samples', 'contester');
		$table->head = array(get_string('input', 'contester'), get_string('output', 'contester'));
		$sql = "SELECT concat('<textarea name=samplein', CAST(samples.id AS CHAR), '>',
		samples.input, '</textarea>') as samplein, concat('<textarea name=sampleout', CAST(samples.id AS CHAR), '>',
		samples.output, '</textarea>') as sampleout FROM mdl_contester_samples as samples WHERE samples.problem_id=$problem->id
		";
		//print $sql;

		$tmp = mysql_query($sql);
		while ($row = mysql_fetch_array($tmp))
		{
			unset ($row[0]);
			unset ($row[1]);
			unset ($row[2]);

			$table->data []= $row;
		}
		print_table($table);
		echo '<input type="hidden" name="format" value="0" />';
		*/
	?>
</table>

<?php
	return true;
}

function contester_show_problem_tags_to_delete($pid)
{
	global $DB;
	if (!$problem = $DB->get_record('contester_problems', 'id', $pid)) {
		error(get_string('noproblem'));
		return false;
	}
	unset($tags);
    $tags = contester_get_problem_tags($pid);
    foreach ($tags as $item)
    {
    	echo "<nobr><input type=\"checkbox\" name=\"tagsdel[]\" value=".$item->mid.">".$item->tag."</nobr>&nbsp;";
    }
    return 0;
}

function contester_show_problem_tags_to_add($pid)
{
	if (!$problem = get_record('contester_problems', 'id', $pid)) {
		error(get_string('noproblem'));
		return false;
	}
	unset($tags);
    $tags = contester_get_not_problem_tags($pid);
    foreach ($tags as $item)
    {
    	echo "<nobr><input type=\"checkbox\" name=\"tagsadd[]\" value=".$item->id.">".$item->tag."</nobr>&nbsp;";
    }
    return 0;
}

/**
* Shows navigation bar for given instance of contester
*
* @param int $instance instance of the contester
*/
function contester_show_nav_bar($instance) {
	global $DB;
    if (! $contester = $DB->get_record("contester", "id", $instance)) {
    	error("Course module is incorrect");
    }
	if (! $course = $DB->get_record("course", "id", $contester->course)) {
		error("Course is misconfigured");
	}
    if (! $cm = get_coursemodule_from_instance("contester", $contester->id, $course->id)) {
    	error("Course Module ID was incorrect");
    }
	$context = get_context_instance(CONTEXT_MODULE, $cm->id);
	$is_teacher = has_capability('moodle/course:viewhiddenactivities', $context);

	echo "<nobr><a href=view.php?a=$instance>".get_string('problemlist', 'contester')."</a></nobr><br>";
	echo "<nobr><a href=submit_form.php?a=$instance>".get_string('submit', 'contester')."</a></nobr><br>";
	echo "<nobr><a href=status.php?a=$instance>".get_string('status', 'contester')."</a></nobr><br>";
	if (get_field('contester', 'viewown', 'id', $instance)) echo "<nobr><a href=my_solutions.php?a=$instance>".get_string('mysolutions', 'contester')."</a></nobr><br>";
	//if ($is_teacher) раньше журнал был только для учителей
	echo "<nobr><a href=journal.php?a=$instance>".get_string('journal', 'contester')."</a></nobr><br>";
}

/**
* Something like header
*
* @param int $instance -instance of the contester
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

/* begin test code */

function contester_get_special_submit_info($submitid, $cget_problem_name = true, $cget_langinfo = true, $cget_status = true, $cget_points = true)
{
	global $DB;
	$submit = $DB->get_record("contester_submits", "id", $submitid);
	$tmp = $DB->get_record_sql("SELECT  COUNT(1) as cnt
						   FROM    mdl_contester_submits
						   WHERE   (contester = {$submit->contester})
						   AND     (student = {$submit->student})
						   AND     (problem = {$submit->problem})
						   AND     (submitted < '{$submit->submitted}')");

	$attempts = 0 + $tmp->cnt;

	if (!$testing = $DB->get_record_sql("SELECT   *
	                                FROM     mdl_contester_testings
	                                WHERE    (submitid = {$submitid})
	                                ORDER BY id
	                                DESC"))
		$queued = true;
	else
	{
		$queued = false;
		$fields = array("compiled", "taken", "passed");
		foreach($fields as $field)
		{
			$submit->$field = $testing->$field;
		}
	}

	if ($submit->taken)
		$submit->points = contester_get_rounded_points($attempts, $submit->passed, $submit->taken);
	else
		$submit->points = 0;

	$submit->attempt = $attempts + 1;
	//$mapping = $DB->get_record("contester_problemmap", "id", $submit->problem, "contesterid", $submit->contester);
	$problem = $DB->get_record("contester_problems", "dbid", $submit->problem);
	$res = null;
	if ($cget_problem_name == true) {
		$res->problem = $problem->name;
	}
	else {
		$res->problem = "";
	}
	if ($cget_langinfo == true) {
		$lang = $DB->get_record("contester_languages", "id", $submit->lang);
		$res->prlanguage = $lang->name;
	}
	else {
		$res->prlanguage = "";
	}
	if ($cget_status == true) {
		if ($submit->processed == 255) {
			if ($submit->compiled)
				$res->status = "<a href=details.php?sid=$submit->id&a=$submit->contester>".
					get_string('passed', 'contester')." $testing->passed ".
					get_string('outof', 'contester')." $testing->taken.</a>";
			else
			{
				$res_id = 2;
				$res_desc = $DB->get_record("contester_resultdesc", "id", $res_id, 'language', 2);
				$res->status = $res_desc->description;
			}
		} else {
			if (!$queued){
				$result = $DB->get_record_sql("SELECT    *
							  FROM      mdl_contester_results
							  WHERE    (testingid = {$testing->id})
							  ORDER BY  testingid DESC");
				//$res_id = $result->result;
				$res_id = 1;
			} else $res_id = 0;
			$res_desc = $DB->get_record("contester_resultdesc", "id", $res_id, 'language', 2);
			$res->status = $res_desc->description;
		}
	}
	else {
		$res->status = "";
	}
	//$res->solution = $submit->solution;
	if ($cget_points == true) {
		$res->points = $submit->points;
	}
	else {
		$res->points = "";
	}
	return $res;
}

/* end test code */

?>

