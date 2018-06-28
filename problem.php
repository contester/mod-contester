<?php

/// Отображает условие задачи со ссылками на отправку решения и список
/// правильных решений.


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

    //add_to_log($course->id, "contester", "problem", "problem.php?id=$contester->id&pid=$pid", "$contester->id");


/// Print the page header

    /*if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strcontesters</a> -> $contester->name",
                  "", "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/styles.css\" />", true, update_module_button($cm->id, $course->id, $strcontester),
                  navmenu($course, $cm));*/
    $PAGE->set_url('/mod/contester/problem.php', array('a' => $a, 'pid' => $id));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");

    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $a));
    $PAGE->navbar->add("$contester->name", $contester_url);
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);
    $PAGE->set_button(update_module_button($cm->id, $course->id, get_string("modulename", "contester")));
    
    echo $OUTPUT->header();              


/// Print the main part of the page

	contester_print_begin($contester->id);
	echo "<table width = 70%><tr><td>";
	
	if (!$problem = $DB->get_record_sql("SELECT mdl_contester_problems.id as id,
				   mdl_contester_problems.name as name,
				   mdl_contester_problems.description as description,
				   mdl_contester_problems.input_format as input,
				   mdl_contester_problems.output_format as output
		    FROM   mdl_contester_problems,
		   		   mdl_contester_problemmap
		    WHERE  mdl_contester_problemmap.problemid=mdl_contester_problems.id
		    AND	   mdl_contester_problemmap.id=?", array($pid))) 
		    	print_error('No such problem!');
	
	$text = "<div id=problemname>".$problem->name."</div>";
	echo $text;
	$text =	"<div>".$problem->description."</div>";
	echo format_text($text);
	$text =	"<div class=textheader>".get_string('inputformat', 'contester')."</div>";
	echo $text;
	$text =	"<div>".$problem->input."</div>";
	echo format_text($text);
	$text = "<div class=textheader>".get_string('outputformat', 'contester')."</div>";
	echo $text;
        $text = "<div>".$problem->output."</div>";
	echo format_text($text);

	/*$text = str_replace("\n", "<br />", $text);
	//$text = iconv("CP-1251", "UTF-8", $text);
	$text = preg_replace('/<[bB][rR]>(\n<[bB][rR]>|й|ц|у|к|е|н|г|ш|щ|з|х|ъ|ф|ы|в|а|п|р|о|л|д|ж|э|я|ч|с|м|и|т|ь|б|ю|ё{1})/' ,'\1',$text);
	//$text = preg_replace('/<[bB][rR]>([а-я])/', '\\1', $text);
	$text = str_replace("\"е", "ё", $text);
	$text = str_replace("\"e", "ё", $text); // какие-то нехорошие написали в условии ЛАТИНСКУЮ БУКВУ e,
											// и хотят чтоб она тоже заменялась на ё. Ну хрен ли ё не пишется?
	//$text = str_replace("$$", "$", $text);*/


	/*while (strpos($text, "$") !== false) {
		$pos = strpos($text, "$");
		$pos2 = strpos(substr($text, $pos+1), "$");
		$text = substr($text, 0, $pos).mutate(substr($text, $pos+1, $pos2)).substr($text, $pos+$pos2 + 2);
		//$text = substr($text, 0, $pos).'\\$\\$'.substr($text, $pos+1, $pos2).'\\$\\$'.substr($text, $pos+$pos2 + 2);
	}*/
	/*
	echo $text;*/

	
	// дальше сэмплы выводятся
	$sql = "select samples.input as input, samples.output as output
	from mdl_contester_samples samples
	where samples.problem_id=? order by samples.number";
	//error($sql);

	$text = "";
	$text .= "<div class=textheader>".get_string('samples', 'contester')."</div>";
	$samples = $DB->get_recordset_sql($sql, array($problem->id));
	foreach($samples as $sample)
	{
		$text .= "<div>".get_string('input', 'contester')."</div>
		<div align=left><pre>".$sample->input."</pre></div>"."<div>".
		get_string('output', 'contester')."</div><div align=left><pre>".$sample->output."</pre></div>";
	}
	echo $text;//format_text($text); в <pre></pre> после формата в хроме лишние переводы строк и в IE выглядит как 1,5 интервал

	echo "</td></tr></table>";
	echo "<a href='problem_solutions.php?a=$contester->id&pid=$pid'>".get_string("solutionlist", "contester")."</a>";


/// Finish the page
    contester_print_end();
    //print_footer($course);
    echo $OUTPUT->footer();

?>
