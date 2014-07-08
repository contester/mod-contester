<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// Отображает форму отправки решения с выбором задачи, языка программирования
/// программирования, и файла с исходным текстом решения.

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID

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

    add_to_log($course->id, "contester", "view", "view.php?id=$cm->id", "$contester->id");

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strcontesters</a> -> $contester->name",
                  "", "", true, update_module_button($cm->id, $course->id, $strcontester),
                  navmenu($course, $cm));

/// Print the main part of the page

	contester_print_begin($contester->id);

    echo "<form enctype=\"multipart/form-data\" method=\"post\" action=\"submit.php?a={$contester->id}\">";
	echo '<table cellpadding="5"><tbody>';

    if ($r = get_recordset_sql("
    			SELECT   *
    			FROM     mdl_contester_problems cp
    			JOIN     mdl_contester_problemmap cpm
    			ON       cpm.problemid = cp.id
    			WHERE    cpm.contesterid = ".$contester->id."
    			ORDER BY cpm.id"))
    {
    	echo '<tr><td align="right">'.get_string('problem', 'contester').":</td>";
    	echo "<td><select name=\"problem\" id=\"problemselect\">";
    	echo "<option value=\"-1\"";
    	if (optional_param('pid', -1) == -1) echo " selected";
    	echo ">" . get_string('chooseproblem', 'contester') . "</option>";
    	while (!$r->EOF)
    	{
    		echo "<option value=\"" . $r->fields["dbid"] . "\"";
    		if (optional_param('pid') == $r->fields['id']) echo " selected";
    		echo ">" . $r->fields["name"] . "</option>";
    		$r->MoveNext();
    	}
    	echo "</select></td></tr>";
    }

    if ($r = get_recordset_select("contester_languages"))
    {
    	$m = get_recordset("contester_language_map", 'contester_id', $contester->id);
    	$langs = array();
    	foreach ($m as $lang) $langs[$lang['language_id']] = 1;
    	echo '<tr><td align="right">'.get_string('prlanguage', 'contester').":</td>";
    	echo "<td><select name=\"lang\" id=\"langselect\">";
    	echo "<option value=\"-1\"";
    	echo " selected";
    	echo ">" . get_string('chooselanguage', 'contester') . "</option>";
    	while (!$r->EOF)
    	{
    		// if ($langs[$r->fields["id"]] == 1) {
    		echo "<option value=\"" . $r->fields["id"] . "\">" . $r->fields["name"] . "</option>";
    		// }
		    $r->MoveNext();
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
    print_footer($course);

?>
