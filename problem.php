<?php
// $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// Отображает условие задачи со ссылками на отправку решения и список
/// правильных решений.

    require_once("../../config.php");
    require_once("lib.php");

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

    require_login($course->id);

    //add_to_log($course->id, "contester", "problem", "problem.php?id=$contester->id&pid=$pid", "$contester->id");

/// Print the page header

    $PAGE->set_url('/mod/contester/problem.php', array('a' => $contester->id, 'pid' => $pid));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");

    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $contester->id));
    $PAGE->navbar->add("$contester->name", $contester_url);
    $PAGE->set_button(update_module_button($cm->id, $course->id,
                      get_string("modulename", "contester")));

    echo $OUTPUT->header();


/// Print the main part of the page

    contester_print_begin($contester->id);

    $iomethodmode = $DB->get_field('contester', 'iomethodmode', array('id' => $contester->id));
    echo "<p>" . get_string('iomethod', 'contester');
    if ($iomethodmode == 0 or $iomethodmode == 2) {
        echo '<br />' . get_string('mode_console', 'contester');
    }
    if ($iomethodmode == 1 or $iomethodmode == 2) {
        echo '<br />' . get_string('mode_file', 'contester');
    }
    echo "<table width = 70%><tr><td>";
    if (!$problem = $DB->get_record_sql("SELECT problems.id as id,
                                                problems.name as name,
                                                problems.description as description,
                                                problems.input_format as input,
                                                problems.output_format as output
                                         FROM   {contester_problems} problems,
                                                {contester_problemmap} problemmap
                                         WHERE  problemmap.problemid=problems.id
                                         AND    problemmap.id=?", array($pid)))
        print_error(get_string('noproblem', 'contester'));

    echo "<div id=problemname>".$problem->name."</div><br />";
    echo format_text("<div>".$problem->description."</div>")."<br />";
    echo "<div class=textheader>".get_string('inputformat', 'contester')."</div>";
    echo format_text("<div>".$problem->input."</div>")."<br />";
    echo "<div class=textheader>".get_string('outputformat', 'contester')."</div>";
    echo format_text("<div>".$problem->output."</div>")."<br />";

    $text = "<div class=textheader>".get_string('samples', 'contester')."</div>";
    $samples = $DB->get_recordset_sql("SELECT samples.input as input,
                                              samples.output as output
                                       FROM   {contester_samples} samples
                                       WHERE  samples.problem_id=?
                                              order by samples.number",
                                      array($problem->id));
    foreach($samples as $sample) {
        $text .= "<div>".get_string('input', 'contester')."</div><div align=left><pre>".
                 $sample->input."</pre></div>"."<div>".get_string('output', 'contester').
                 "</div><div align=left><pre>".$sample->output."</pre></div>";
    }
    echo $text."</td></tr></table>";

    echo '<form enctype="multipart/form-data" method="post" action="submit_form.php?pid='.$pid.'&a='.$contester->id.'">';
    echo '<table cellpadding="5"><tbody>';
    echo '<tr><td colspan="2" align="center"><input type="submit" value="'.get_string('submit', 'contester').'"></input></td></tr>';
    echo '</tbody></table></form>';

    $solutions_url = new moodle_url('/mod/contester/problem_solutions.php', array('a' => $contester->id, 'pid' => $pid));
    echo "<a href=$solutions_url>".get_string("solutionlist", "contester")."</a>";

    contester_print_end();

/// Finish the page

    echo $OUTPUT->footer();

?>
