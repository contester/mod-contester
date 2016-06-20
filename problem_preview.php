<?php

/// Отображает условие задачи


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
<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID
    
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

    require_login($course->id);

    $pid = required_param('pid', PARAM_INT); // ID of problem in problems
    //add_to_log(0, "contester", "preview", "problem_preview.php", "$pid");

    if (!($is_admin || $is_teacher)) {
    	print_error(get_string('accessdenied', 'contester'));
    }

/// Print the page header

	// достаем name для header-а, заодно всё остальное: название, условия, формат ввода-вывода
	$sql = "select mdl_contester_problems.id as id, mdl_contester_problems.name as name,
				   mdl_contester_problems.dbid as dbid, mdl_contester_problems.description as description,
				   mdl_contester_problems.input_format as input, mdl_contester_problems.output_format as output
			from   mdl_contester_problems
			where  mdl_contester_problems.id=?";
	if (!$problem = $DB->get_record_sql($sql, array($pid))) print_error('No such problem!');

    /*if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }
    $problemspreview = "<a href=\"../../mod/contester/problems_preview.php?a=".$contester->id."\">".get_string('problemspreview', 'contester')."</a> ->";
    $curcontester = "$contester->name ->";
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation $curcontester $problemspreview".$problem->name,
                  "", "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/styles.css\" />",
                  true, update_module_button($cm->id, $course->id, $strcontester),
                  navmenu($course, $cm));*/
                  
    $PAGE->set_url('/mod/contester/problem_preview.php', array('a' => $a, 'pid' => $pid));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");
    $PAGE->navbar->add("$contester->name");
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);
    $PAGE->set_button(update_module_button($cm->id, $course->id, get_string("modulename", "contester")));

    echo $OUTPUT->header();        

/// Print the main part of the page

	echo "<table width = 95% height=95% cellpadding=10><tr><td>";

	$text = "<div id=problemname> ".$problem->name."</div><div id=description>".$problem->description.
	"</div><div id=textheader>".get_string('inputformat', 'contester')."</div><div id=inoutformat>".$problem->input.
	"</div><div id=textheader>".get_string('outputformat', 'contester')."</div><div id=inoutformat>".$problem->output.
	"</div>";
	/*$text = str_replace("\n", "<br />", $text);
	//$text = iconv("CP-1251", "UTF-8", $text);
	$text = preg_replace('/<[bB][rR]>(\n<[bB][rR]>|й|ц|у|к|е|н|г|ш|щ|з|х|ъ|ф|ы|в|а|п|р|о|л|д|ж|э|я|ч|с|м|и|т|ь|б|ю|ё{1})/' ,'\1',$text);
	//$text = preg_replace('/<[bB][rR]>([а-я])/', '\\1', $text);
	$text = str_replace("\"е", "ё", $text);
	$text = str_replace("\"e", "ё", $text); // какие-то нехорошие написали в условии ЛАТИНСКУЮ БУКВУ e,
											// и хотят чтоб она тоже заменялась на ё. Ну хрен ли ё не пишется?
	$text = str_replace("$$", "$", $text);


	while (strpos($text, "$") !== false) {
		$pos = strpos($text, "$");
		$pos2 = strpos(substr($text, $pos+1), "$");
		$text = substr($text, 0, $pos).mutate(substr($text, $pos+1, $pos2)).substr($text, $pos+$pos2 + 2);
		//$text = substr($text, 0, $pos).'\\$\\$'.substr($text, $pos+1, $pos2).'\\$\\$'.substr($text, $pos+$pos2 + 2);
	}*/
	/*
	echo $text;*/

	echo format_text($text);
	// дальше сэмплы выводятся
	$sql = "select samples.input as input, samples.output as output
	from mdl_contester_samples samples
	where samples.problem_id=? order by samples.number";
	//error($sql);

	$text = "";
	$text .= "<div id=textheader>".get_string('samples', 'contester')."</div>";
	$samples = $DB->get_records_sql($sql, array($problem->id));
	foreach($samples as $sample)
	{
		$text .= "<div id=sample>".get_string('input', 'contester')."</div>
		<div id=code align=left><pre>".$sample->input."</pre></div>"."<div id=sample>".
		get_string('output', 'contester')."</div><div id=code align=left><pre>".$sample->output."</pre></div>";
	}
	echo $text;//format_text($text); в <pre></pre> после формата в хроме лишние переводы строк и в IE выглядит как 1,5 интервал

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
/// Finish the page
    //print_footer($course);
    echo $OUTPUT->footer();

?>
