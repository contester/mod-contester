<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// Adds solution to the testing queue

    require('../../config.php');
    require_once('lib.php');

    $a  = required_param('a', PARAM_INT);

    if(! $contester = $DB->get_record('contester', array('id' => $a))) {
        print_error(get_string("incorrect_contester_id", "contester"));
    }
    if(! $course = $DB->get_record('course', array('id' => $contester->course))) {
        print_error(get_string("misconfigured_course", "contester"));
    }
    if(! $cm = get_coursemodule_from_instance('contester', $contester->id, $course->id)) {
        print_error(get_string("incorrect_cm_id", "contester"));
    }

    require_login($course->id);

    $context = context_module::instance($cm->id);
    $is_admin = has_capability('moodle/site:config', $context);

    //add_to_log($course->id, "contester", "submit", "submit.php?id=$cm->id", "$contester->id");

/// Print the page header

    $PAGE->set_url('/mod/contester/submit.php', array('a' => $contester->id));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");

    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $contester->id));
    $PAGE->navbar->add("$contester->name", $contester_url);

    echo $OUTPUT->header();

/// Print the main part of the page

    contester_print_begin($contester->id);

    $now = new DateTime("now", core_date::get_server_timezone_object());

    $submit = new stdClass();
    $submit->contester = $contester->id;
    $submit->student = $USER->id;
    $submit->submitted_uts = $now->getTimestamp();
    $submit->problem = required_param("problem", PARAM_INT);
    $return = new moodle_url('/mod/contester/submit_form.php', array('a' => $contester->id));

    if ($submit->problem == -1)
        print_error(get_string("shudchuzprob", 'contester'), "", $return);
    $submit->lang = $_POST["lang"];
    if ($submit->lang == -1)
        print_error(get_string("shudchuzlang", 'contester'), "", $return);
    $iomethodmode = $DB->get_field('contester', 'iomethodmode', array('id' => $a));
    if ($iomethodmode < 2) {
        $submit->iomethod = $iomethodmode;
    }
    else {
        $submit->iomethod = optional_param("iomethod", 0, PARAM_INT);
    }
    $submit->solution = trim($_POST['code']);
    if ($submit->solution == "") {
        print_error(get_string("shudchuzcode", 'contester'), "", $return);
    }

    // c# problem (contester could not kill process which is waiting for console input)
    if ($submit->iomethod == 0) {
        $submit->solution = str_replace("Console.Read", '//Console.Read', $submit->solution);
    }
    // c++ problem (contester could not kill process which is using system("PAUSE"))
    // $submit->solution = str_replace('system', "//system", $submit->solution);
    $pattern ='/system\s*\(\s*\\\{0,1}"\s*pause\s*\\\{0,1}"\s*\)\s*;/i';
    $replacement = '/* system(\"pause\"); */';
    $submit->solution = preg_replace($pattern, $replacement, $submit->solution);

    $DB->insert_record("contester_submits", $submit);
    print_string("successsubmit", "contester");

    $solutions_url = new moodle_url('/mod/contester/my_solutions.php', array('a' => $contester->id));
    echo '<br><a href="' . $solutions_url . '">' . get_string("mysolutions", 'contester').'</a><br>';

    contester_print_end();

/// Finish the page


    echo $OUTPUT->footer();

?>
