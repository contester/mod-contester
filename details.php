<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// This page prints testing results

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

    //add_to_log($course->id, "contester", "status", "status.php?id=$cm->id", "$contester->id");

/// Print the page header

    $PAGE->set_url('/mod/contester/details.php', array('a' => $contester->id,
                                                       'sid' => $sid));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading($course->fullname);

    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $contester->id));
    $PAGE->navbar->add("$contester->name", $contester_url);

    echo $OUTPUT->header();


/// Print the main part of the page

    contester_print_begin($contester->id);

    $sr = $DB->get_record_sql("SELECT  problems.name as problem_name,
                                       user.firstname as firstname,
                                       user.lastname as lastname,
                                       submits.submitted_uts as submitted_uts
                               FROM    {contester_submits} submits,
                                       {contester_problems} problems,
                                       {user} user
                               WHERE
                                       submits.problem=problems.dbid AND
                                       user.id = submits.student AND
                                       submits.id=?", array($sid));

    echo $sr->firstname . ' ' . $sr->lastname . ' ' . $sr ->problem_name . ' ' .
         '<br />' . userdate($sr->submitted_uts, get_string('strftimedatetime')) . '<br/><br/>';

    $result = contester_get_detailed_info($sid);
    echo "<p>";
    contester_draw_assoc_table($result);
    echo "</p>";

    contester_print_end();

/// Finish the page

    echo $OUTPUT->footer();

?>
