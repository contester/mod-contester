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
	$s = str_replace("le", "\\le", $s);
	return "<tex>$s</tex>";
}
?>
<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// This page prints a particular instance of contester
/// (Replace contester with the name of your module)

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

    add_to_log($course->id, "contester", "view", "view.php?id=$cm->id", "$contester->id");

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
	echo "<table width = 70%><tr><td>";
	// достаем и выводим название, условия, формат ввода-вывода
	$sql = "select mdl_contester_problems.id as id, mdl_contester_problems.name as name, mdl_contester_problems.description as description, 
	mdl_contester_problems.input_format as input, mdl_contester_problems.output_format as output
	from mdl_contester_problems, mdl_contester_problemmap where mdl_contester_problemmap.problemid=
	mdl_contester_problems.id and mdl_contester_problemmap.id=$pid";
	if (!$problem = get_record_sql($sql)) error('No such problem!');
	$text = "<div id=problemname>".$problem->name."</div><div id=description>".$problem->description.
	"</div><div id=textheader>".get_string('inputformat', 'contester')."</div><div id=inoutformat>".$problem->input.
	"</div><div id=textheader>".get_string('outputformat', 'contester')."</div><div id=inoutformat>".$problem->output.
	"</div>";
	$text = str_replace("\n", "<br>", $text);
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
	}
	/*
	echo $text;*/
	
	echo format_text($text);
	// дальше сэмплы выводятся
	$sql = "select samples.input as input, samples.output as output
	from mdl_contester_samples samples
	where samples.problem_id=$problem->id order by samples.number";
	//error($sql);
	
	$text = "";
	$text .= "<div id=textheader>".get_string('samples', 'contester')."</div>";
	$samples = get_recordset_sql($sql);
	foreach($samples as $sample)
	{
		$text .= "<div id=description>".get_string('input', 'contester')."</div>
		<div id=code><pre>".$sample['input']."</pre></div>"."<div id=description>".
		get_string('output', 'contester')."</div><div id=code><pre>".$sample['output']."</pre></div>";
	}
	echo $text;
	echo "</td></tr></table>";
/// Finish the page
	contester_print_end();
    print_footer($course);

?>
