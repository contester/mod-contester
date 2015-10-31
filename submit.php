<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// Ставит решение студента в очередь тестирования
///

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID

    if ($id) {
        if (! $cm = $DB->get_record('course_modules', array('id' => $id))) {
            error("Course Module ID was incorrect");
        }

        if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
            error("Course is misconfigured");
        }

        if (! $contester = $DB->get_record('contester', array('id' => $cm->instance))) {
            error("Course module is incorrect 1");
        }

    } else {
        if (! $contester = $DB->get_record('contester', array('id' => $a))) {
            error("Course module is incorrect 2");
        }
        if (! $course = $DB->get_record('course', array('id' => $contester->course))) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("contester", $contester->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    //add_to_log($course->id, "contester", "submit", "submit.php?id=$cm->id", "$contester->id");

/// Print the page header

    /*if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strcontesters</a> -> $contester->name",
                  "", "", true, update_module_button($cm->id, $course->id, $strcontester),
                  navmenu($course, $cm)); */	
				  
	$PAGE->set_url('/mod/contester/submit.php', array('id' => $cm->id));
	$PAGE->set_title(format_string($contester->name));
	$PAGE->set_heading(format_string($course->fullname));

/// Print the main part of the page
	echo $OUTPUT->header();
	contester_print_begin($contester->id);

	$submit = new stdClass();
	$submit->contester = $contester->id;
	$submit->student = $USER->id;
	$submit->problem = required_param("problem", PARAM_INT); 
	if ($submit->problem == -1) error(get_string("shudchuzprob", 'contester'), "submit_form.php?a=$contester->id");
	$submit->lang = $_POST["lang"];
	if ($submit->lang == -1) error(get_string("shudchuzlang", 'contester'), "submit_form.php?a=$contester->id");
	//$submit->solution = required_param("code");
	$submit->solution = trim($_POST['code']);
    if ($submit->solution == "")
    {
    	$temp_name = $_FILES["solution"]["tmp_name"];
    	if (!is_uploaded_file($temp_name))
    	{
    		error(get_string("shudchuzcode", 'contester'), "submit_form.php?a=$contester->id");
    	}
    	else
    	{
    		$submit->solution = /*mysql_escape_string(*/file_get_contents($temp_name);//);
    	}
    }
    
	$context = context_module::instance($cm->id);
    $is_admin = has_capability('moodle/site:config', $context);    

    if ($is_admin)
    {
        //$string = $submit->solution;
        //$pattern ='/system\s*\(\s*\\\{0,1}"\s*pause\s*\\\{0,1}"\s*\)\s*;/i';
        //$replacement = '/* system(\"pause\"); */';
        //echo preg_replace($pattern, $replacement, $string);
    }
    
    // c# problem (contester could not kill process which is waiting for console input)
    $submit->solution = str_replace("Console.Read", '//Console.Read', $submit->solution);
    
    // c++ problem (contester could not kill process which is using system("PAUSE"))
    // $submit->solution = str_replace('system', "//system", $submit->solution);
    $pattern ='/system\s*\(\s*\\\{0,1}"\s*pause\s*\\\{0,1}"\s*\)\s*;/i';
    $replacement = '/* system(\"pause\"); */';
    $submit->solution = preg_replace($pattern, $replacement, $submit->solution);
                            
    
    $DB->insert_record("contester_submits", $submit);
    print_string("successsubmit", "contester");
    
    echo "<br><a href=\"status.php?id=$id&a=$a\">".get_string("status", 'contester')."</a><br>";


/// Finish the page
    contester_print_end();
//    print_footer($course);
	echo $OUTPUT->footer();

?>
