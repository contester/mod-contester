<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// This page prints solution code

    require_once("../../config.php");
    require_once("lib.php");

    $a   = required_param('a', PARAM_INT);  // contester ID
    $sid = required_param('sid', PARAM_INT);  // submit ID

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

    //add_to_log($course->id, "contester", "show_solution", "show_solution.php?a=$contester->id&sid=$sid", "$contester->id");

    $context = context_module::instance($cm->id);
    $is_teacher = has_capability('moodle/course:viewhiddenactivities', $context);
    $is_admin = has_capability('moodle/site:config', $context);

    if ((!$is_admin) && (!$is_teacher) && (!$DB->get_field('contester', 'freeview', array('id' => $contester->id)))) {
        if (!$userid) {
            if (empty($USER->id)) {
                print_error('accessdenied', 'contester');
            }
            $userid = $USER->id;
    	}
        $user = $DB->get_field_sql("SELECT user.id
                                    FROM   {user} as user,
                                           {contester_submits} submits
                                    WHERE  submits.id=?
                                           AND
                                           user.id=submits.student",
                                   array($sid));
        if ($userid != $user) print_error('accessdenied', 'contester');
    }

/// Print the page header

    $PAGE->set_url('/mod/contester/show_solution.php', array('a' => $contester->id,
                                                             'sid' => $sid));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");

    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $contester->id));
    $PAGE->navbar->add("$contester->name", $contester_url);

    echo $OUTPUT->header();


/// Print the main part of the page

    contester_print_begin($contester->id);

    echo contester_get_submit_info_to_print($sid);

    echo "<textarea cols=85 rows = 30 readonly='yes'>".$DB->get_field('contester_submits', 'solution', array('id' => $sid))."</textarea>";

    contester_print_end();

/// Finish the page

    echo $OUTPUT->footer();

?>
