<?php

/// Отображает условие задачи


/**
* Convert the text between $'s into html form
*
* @param string $s a string to convert
*/

?>
<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

    require_once("../../config.php");
    require_once("lib.php");

    global $DB;

    $a   = required_param('a',   PARAM_INT); // Contester ID
    $pid = required_param('pid', PARAM_INT); // ID of problem in problemmap

    if(! $contester = $DB->get_record('contester', array('id' => $a))) {
        print_error(get_string("incorrect_contester_id", "contester"));
    }
    if(! $course = $DB->get_record('course', array('id' => $contester->course))) {
        print_error(get_string("misconfigured_course", "contester"));
    }
    if(! $cm = get_coursemodule_from_instance('contester', $contester->id, $course->id)) {
        print_error(get_string("incorrect_cm_id", "contester"));
    }

    $context = context_module::instance($cm->id);
    $is_teacher = has_capability('moodle/course:viewhiddenactivities', $context);
    $is_admin = has_capability('moodle/site:config', $context);

    require_login($course->id);

    //add_to_log(0, "contester", "preview", "problem_preview.php", "$pid");

    if (!($is_admin || $is_teacher)) {
    	print_error(get_string('accessdenied', 'contester'));
    }

/// Print the page header

    $PAGE->set_url('/mod/contester/problem_preview.php', ['a' => $contester->id, 'pid' => $pid]);
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");

    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $contester->id));
    $PAGE->navbar->add("$contester->name", $contester_url);

    echo $OUTPUT->header();


/// Print the main part of the page

    contester_print_begin($contester->id);

    echo "<table width = 70%>";

    $problem = contester_get_problem_with_samples_to_print($pid);
    if ($problem) {
        echo '<tr><td>';
        echo $problem->text;
        echo '</tr></td>';
    }
    else {
        print_error(get_string('noproblem', 'contester'));
    }

    if ($is_admin) {
        echo '<tr><td>';
        contester_print_link_to_problem_details($contester->id, $problem->id, $problem->dbid);
        echo '</td></tr>';

        echo '<tr><td><span id=textheader>'.get_string('tags', 'contester').':</span>';
        echo '<span id=tags>';
        contester_show_problem_tags($problem->id);
        contester_print_link_to_problem_tags_details($contester->id, $problem->id);
        echo '</span></td></tr>';
    }

    echo "</table>";

    contester_print_end();

/// Finish the page

    echo $OUTPUT->footer();

?>
