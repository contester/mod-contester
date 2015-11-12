<?php

/**
* Convert the text between $'s into html form
*
* @param string $s a string to convert
*/
function mutate($s)
{
	/*$s = str_replace("le", " <=", $s);
	return "<div id=symbol>$s</div>";*/
	$s = str_replace("dots", "...", $s);
	$s = str_replace("le", "\\le", $s);
	$s = str_replace("<=", "\\le", $s);
	$s = str_replace(">=", "\\ge", $s);
	$s = str_replace("<", "&lt", $s);
	$s = str_replace(">", "&gt", $s);
	$s = str_replace("&lt", "</tex>&lt<tex>", $s);
	$s = str_replace("&gt", "</tex>&gt<tex>", $s);

	return "<tex>$s</tex>";
}
?>
<?PHP

    require_once("../../config.php");
    require_once("lib.php");

    $id   = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a    = optional_param('a', 0, PARAM_INT);  // contester ID
    $sort = optional_param('sort', 0, PARAM_INT);
    $tag  = optional_param('tag', 0, PARAM_INT);
    
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

	$context = context_module::instance($cm->id);
    $is_teacher = has_capability('moodle/course:viewhiddenactivities', $context);
    $is_admin = has_capability('moodle/site:config', $context);

    require_login($cm->id);

//    add_to_log($course->id, "contester", "preview_all", "problems_preview_all.php?a=$contester->id", "$contester->id");

    if (!($is_admin || $is_teacher)) {
    	print_error(get_string('accessdenied', 'contester'));
    }

/// Print the page header

    /*if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

	$curcontester = "$contester->name ->";

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");
    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation $curcontester".get_string('problemspreview', 'contester'),
                  "", "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/styles.css\" />",
                  true, update_module_button($cm->id, $course->id, $strcontester),
                  navmenu($course, $cm));*/
                  
    $PAGE->set_url('/mod/contester/problems_preview_all.php', array('id' => $id, 'a' => $a, 'sort' => $sort, 'tag' => $tag));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");
    $PAGE->navbar->add("$contester->name");
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);
    $PAGE->set_button(update_module_button($cm->id, $course->id, get_string("modulename", "contester")));
    
    echo $OUTPUT->header();                  

/// Print the main part of the page

    echo '<div id=textheader>'.get_string('tags', 'contester').':</div>';
	echo '<div id=tags>';
	contester_show_tags_ref($contester->id, $sort, "_all");
	echo '</div>';
	$linktoprew = "<a href=\"../../mod/contester/problems_preview.php?a=".$contester->id.
				"&sort=".$sort."&tag=".$tag."\">".get_string('problemlist', 'contester')."</a>";
	echo '<div id=textheader>'.get_string('problemspreviewall', 'contester').' ('.$linktoprew.'):</div>';

	if ($sort == 0)
	{
		echo "<div id=sort>".get_string('id', 'contester')." <a href=\"../../mod/contester/problems_preview_all.php?a=".
			$contester->id."&sort=1&tag=".$tag."\">".get_string('problemname', 'contester')."</a></div>";
	}
	else
	{
		echo "<div id=sort><a href=\"../../mod/contester/problems_preview_all.php?a=".$contester->id."&sort=0&tag=".$tag.
			"\">".get_string('id', 'contester')."</a> ".get_string('problemname', 'contester')."</div>";
	}

	$problems = contester_get_problems_preview_all($contester->id, $sort, $tag);
    foreach ($problems as $problem)
    {
    	echo "<table width = 95% height=95% cellpadding=10><tr><td>";
		$text = "<div id=problemname> ".$problem->name." [".$problem->dbid."]</div><div id=description>".$problem->description.
		"</div><div id=textheader>".get_string('inputformat', 'contester')."</div><div id=inoutformat>".$problem->input.
		"</div><div id=textheader>".get_string('outputformat', 'contester')."</div><div id=inoutformat>".$problem->output.
		"</div>";

		/*$text = str_replace("\n", "<br />", $text);
		$text = preg_replace('/<[bB][rR]>(\n<[bB][rR]>|é|ö|ó|ê|å|í|ã|ø|ù|ç|õ|ú|ô|û|â|à|ï|ð|î|ë|ä|æ|ý|ÿ|÷|ñ|ì|è|ò|ü|á|þ|¸{1})/' ,'\1',$text);
		$text = str_replace("\"å", "¸", $text);
		$text = str_replace("\"e", "¸", $text);
		$text = str_replace("$$", "$", $text);

		while (strpos($text, "$") !== false) {
			$pos = strpos($text, "$");
			$pos2 = strpos(substr($text, $pos+1), "$");
			$text = substr($text, 0, $pos).mutate(substr($text, $pos+1, $pos2)).substr($text, $pos+$pos2 + 2);
		}*/
		echo format_text($text);
		$sql = "select samples.input as input, samples.output as output
				from mdl_contester_samples samples
				where samples.problem_id=? order by samples.number";
		$text = "";
		$text .= "<div id=textheader>".get_string('samples', 'contester')."</div>";
		$samples = $DB->get_records_sql($sql, array($problem->id));
		foreach($samples as $sample)
		{
			$text .= "<div id=sample>".get_string('input', 'contester')."</div>
					<div id=code align=left><pre>".$sample->input."</pre></div>"."<div id=sample>".
					get_string('output', 'contester')."</div><div id=code align=left><pre>".$sample->output."</pre></div>";
		}
		echo $text;
		echo "</td></tr>";
		if ($is_admin)
		{
			echo '<tr><td>';
			contester_print_link_to_problem_details($contester->id, $problem->id, $problem->dbid);
			echo '</td></tr>';
		}
		if ($is_admin)
		{
			echo '<tr><td><span id=textheader>'.get_string('tags', 'contester').':</span>';
			echo '<span id=tags>';
   			contester_show_problem_tags($problem->id);
   			contester_print_link_to_problem_tags_details($contester->id, $problem->id);
  			echo '</span></td></tr>';
   		}

		echo "</table>";
	}

/// Finish the page
    //print_footer($course);
    echo $OUTPUT->footer();

?>

