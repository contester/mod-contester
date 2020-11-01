<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// Отображает форму отправки решения с выбором задачи, языка программирования
/// программирования, и файла с исходным текстом решения.

    require('../../config.php');
    require_once('lib.php');

    $a   = required_param('a', PARAM_INT);       // contester ID
    $pid = optional_param('pid', -1, PARAM_INT); // problem ID

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
    $is_teacher = has_capability('moodle/course:viewhiddenactivities', $context);  

    //add_to_log($course->id, "contester", "view", "view.php?id=$cm->id", "$contester->id");


/// Print the page header

    if ($pid == -1) {
        $PAGE->set_url('/mod/contester/submit_form.php', array('a' => $contester->id));
    }
    else {
        $PAGE->set_url('/mod/contester/submit_form.php', array('a' => $contester->id,
                                                               'pid' => $pid));
    }
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");

    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $contester->id));
    $PAGE->navbar->add("$contester->name", $contester_url);

    echo $OUTPUT->header();


/// Print the main part of the page


    contester_print_begin($contester->id);

    echo '<form enctype="multipart/form-data" method="post" action="submit.php?a=' . $contester->id . '">';
    echo '<table cellpadding="5"><tbody>';

    if ($r = $DB->get_records_sql("
            SELECT   *
            FROM     {contester_problems} problems,
                     {contester_problemmap} problemmap
            WHERE    problemmap.problemid = problems.id
                     AND
                     problemmap.contesterid = ?
            ORDER BY problemmap.id",
            array($contester->id))) {
    	echo '<tr><td align="right">'.get_string('problem', 'contester').":</td>";
    	echo '<td><select name="problem" id="problemselect">';
    	echo '<option value="-1"';
    	if ($pid == -1)
            echo " selected";
    	echo ">" . get_string('chooseproblem', 'contester') . "</option>";

    	foreach($r as $rr) {
            echo '<option value="' . $rr->dbid . '"';
            if ($pid == $rr->id)
                echo " selected";
            echo '>' . $rr->name . '</option>';
        }
        echo "</select></td></tr>";
    }

    if ($r = $DB->get_records_select("contester_languages", "display is not null", array(), "display")) {
        echo '<tr><td align="right">'.get_string('prlanguage', 'contester').":</td>";
        echo '<td><select name="lang" id="langselect">';
        echo '<option value="-1"';
        echo ' selected';
        echo '>' . get_string('chooselanguage', 'contester') . '</option>';
        foreach($r as $rr) {
            echo '<option value="' . $rr->id . '">' . $rr->name . '</option>';
        }
        echo '</select></td></tr>';
    }

    $iomethodmode = $DB->get_field('contester', 'iomethodmode', array('id' => $contester->id));
    if ($iomethodmode > 1) {
        echo '<tr><td align="right">'.get_string('iomethodshort', 'contester').":</td>";
        echo '<td>' . '<input type="radio" name="iomethod" value="1" checked />'.get_string('consoleioshort', 'contester').'<br />' .
                      '<input type="radio" name="iomethod" value="0" />'.get_string('fileioshort', 'contester').'<br />' . '</td></tr>';
    }

    echo '<tr><td colspan="2" align="center">'.get_string('solution', 'contester').":</td>";
    echo '<tr><td colspan="2"><textarea rows="20" cols="85" id="code" name="code" placeholder="' .
         get_string('solution', 'contester').'"></textarea></td><tr>';

    echo '<tr><td colspan="2" align="center"><input type="submit" value="'.get_string('submit', 'contester').'"></input></td></tr>';
    echo '</tbody></table></form>';

    contester_print_end();

/// Finish the page


    echo $OUTPUT->footer();

?>
