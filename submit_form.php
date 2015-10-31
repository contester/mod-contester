<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// Отображает форму отправки решения с выбором задачи, языка программирования
/// программирования, и файла с исходным текстом решения.

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID

    if ($id) {
        if (! $cm = $DB->get_record("course_modules", array("id"=>$id))) {
            error("Course Module ID was incorrect");
        }

        if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
            error("Course is misconfigured");
        }

        if (! $contester = $DB->get_record("contester", array("id"=>$cm->instance))) {
            error("Course module is incorrect");
        }

    } else {
        if (! $contester = $DB->get_record("contester", array("id"=>$a))) {
            error("Course module is incorrect");
        }
        if (! $course = $DB->get_record("course", array("id"=>$contester->course))) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("contester", $contester->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    //add_to_log($course->id, "contester", "view", "view.php?id=$cm->id", "$contester->id");
	
	/*$event = \mod_contester\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
	$event->add_record_snapshot('course', $PAGE->course);
	$event->add_record_snapshot($PAGE->cm->modname, $contester);
	$event->trigger(); */
	
	//Start new code
	
	/*
	$event = \mod_contester\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
	));
	$event->add_record_snapshot('course', $PAGE->course);
	$event->add_record_snapshot($PAGE->cm->modname, $contester);
	$event->trigger(); 
	*/
	
	//End new code

/// Print the page header

    /* if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strcontesters</a> -> $contester->name",
                  "", "", true, update_module_button($cm->id, $course->id, $strcontester),
                  navmenu($course, $cm)); */
	//Start new code
	
	$PAGE->set_url('/mod/contester/submit_form.php', array('id' => $cm->id));
	$PAGE->set_title(format_string($contester->name));
	$PAGE->set_heading(format_string($course->fullname));
	$PAGE->set_button(update_module_button($cm->id, $course->id, get_string("modulename", "contester")));

	//End new code
	
/// Print the main part of the page
	
	echo $OUTPUT->header();

	contester_print_begin($contester->id);

    echo "<form enctype=\"multipart/form-data\" method=\"post\" action=\"submit.php?a={$contester->id}\">";
	echo '<table cellpadding="5"><tbody>';

    if ($r = $DB->get_records_sql("
    			SELECT   *
    			FROM     mdl_contester_problems cp
    			JOIN     mdl_contester_problemmap cpm
    			ON       cpm.problemid = cp.id
    			WHERE    cpm.contesterid = ?
    			ORDER BY cpm.id", array($contester->id)))
    {
    	echo '<tr><td align="right">'.get_string('problem', 'contester').":</td>";
    	echo "<td><select name=\"problem\" id=\"problemselect\">";
    	echo "<option value=\"-1\"";
    	if (optional_param('pid', -1, PARAM_INT) == -1) echo " selected";
    	echo ">" . get_string('chooseproblem', 'contester') . "</option>";
    	//while ($r->valid())
    	foreach($r as $rr)
    	{
    		echo "<option value=\"" . $rr->dbid . "\"";
    		if (optional_param('pid', -1, PARAM_INT) == $rr->id) echo " selected";
    		echo ">" . $rr->name . "</option>";
    		//$r->MoveNext();
    	}
    	echo "</select></td></tr>";
    }

	//Нужно обдумать второй параметр true
    if ($r = $DB->get_records_select("contester_languages", true))
    {
    	$m = $DB->get_recordset("contester_language_map", array('contester_id'=>$contester->id));
    	$langs = array();
    	foreach ($m as $lang) $langs[$lang['language_id']] = 1;
    	echo '<tr><td align="right">'.get_string('prlanguage', 'contester').":</td>";
    	echo "<td><select name=\"lang\" id=\"langselect\">";
    	echo "<option value=\"-1\"";
    	echo " selected";
    	echo ">" . get_string('chooselanguage', 'contester') . "</option>";
    	foreach($r as $rr)
    	{
    		// if ($langs[$r->fields["id"]] == 1) {
    		echo "<option value=\"" . $rr->id . "\">" . $rr->name . "</option>";
    		// }
		}
		echo "</select></td></tr>";
    }

    echo '<tr><td colspan="2" align="center">'.get_string('solution', 'contester').":</td>";
    //echo "<td><input type=\"file\" name=\"solution\"></td></tr>";

    echo '<tr><td colspan="2"><textarea rows="20" cols="60" id="code" name="code"
		placeholder="'.get_string('solution', 'contester').'"></textarea></td><tr>';

    echo '<tr><td colspan="2" align="center"><input type="submit" value="'.get_string('submit', 'contester').'"></input></td></tr>';

	echo '</tbody></table></form>';

/// Finish the page
	contester_print_end();
    //print_footer($course); Olf
	echo $OUTPUT->footer();

?>
