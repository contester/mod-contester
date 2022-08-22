<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

    require_once("../../config.php");
    require_once("lib.php");

    $a   = required_param('a', PARAM_INT);  // contester ID
    $pid = required_param('pid', PARAM_INT);

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
    
    //add_to_log($course->id, "contester", "details", "details.php?id=$cm->id", "$contester->id");
    $context = context_module::instance($cm->id);
    $is_admin = has_capability('moodle/site:config', $context);

    if (!$is_admin) print_error(get_string('accessdenied', 'contester'));


/// Print the page header

    $PAGE->set_url('/mod/contester/problem_details.php', array('a' => $contester->id, 'pid' => $pid));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading($course->fullname);

    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $contester->id));
    $PAGE->navbar->add("$contester->name", $contester_url);

    echo $OUTPUT->header();

/// Print the main part of the page

    echo '<form action=save_problem.php method="POST">';
    contester_show_problem_details($pid);
    echo '<input type=submit value="'.get_string('save', 'contester').'">';
    echo '<input type=hidden name="pid" value="'.$pid.'">';
    echo '<input type=hidden name="a" value="'.$contester->id.'">';
    echo '</form>';

    contester_print_end();

/// Finish the page

    echo $OUTPUT->footer()

?>
