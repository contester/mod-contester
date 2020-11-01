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
    require_login($course->id);
    //add_to_log($course->id, "contester", "details", "details.php?id=$cm->id", "$contester->id");
    $context = context_module::instance($cm->id);
    $is_admin = has_capability('moodle/site:config', $context);

    if (!$is_admin)
        print_error(get_string('accessdenied', 'contester'));

/// Print the page header
    $PAGE->set_url('/mod/contester/upload_problem.php', array('a' => $a));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");
    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $a));
    $PAGE->navbar->add("$contester->name", $contester_url);
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);

    echo $OUTPUT->header();


/// Print the main part of the page

    $temp_name = $_FILES["definition"]["tmp_name"];
    if (!is_uploaded_file($temp_name))
    {
        // Handle submit error
        print_file_upload_error($_FILES["definition"]);
        print_error('OMFG!');
    }
    $text = file_get_contents($temp_name);
    $dbid = required_param('dbid', PARAM_INT);
    contester_parse_task($text, $dbid);
    echo "<a href=\"upload_problem_form.php?a=".$a."\">".get_string("uploadanothertask", 'contester')."</a><br>";
?>
