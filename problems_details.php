<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// This page prints a particular instance of contester
/// (Replace contester with the name of your module)

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
    $is_teacher = has_capability('moodle/course:viewhiddenactivities', $context);    
    
    if (!$is_admin && !$is_teacher) print_error(get_string('accessdenied', 'contester'));

/// Print the page header

    $PAGE->set_url('/mod/contester/problems_details.php', array('a' => $a));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");

    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $a));
    $PAGE->navbar->add("$contester->name", $contester_url);  
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);
    $PAGE->set_button(update_module_button($cm->id, $course->id, get_string("modulename", "contester")));
    
    echo $OUTPUT->header();                  

/// Print the main part of the page

	echo '<form action=save_problems.php method="POST">';
	
	echo '<center>';
	contester_show_problems_to_delete($a);

	echo '<br>';
	echo '<br>';

	contester_show_problems_to_add($a);
	
	$res = $DB->get_record('contester', array('id' => $a));
	
	echo "<p>";
	
	$out = "<nobr><input type=\"checkbox\" name=\"freeview\" value=\"1\" ";
	if ($res->freeview)
		$out .= "checked";
	$out .= ">".get_string('viewable', 'contester');
 	echo $out;
 	
 	echo "</br>";
 	
 	$out = "<nobr><input type=\"checkbox\" name=\"viewown\" value=\"1\" ";
	if ($res->viewown)
		$out .= "checked";
	$out .= ">".get_string('viewown', 'contester');
 	echo $out;
 	
 	echo "</p>";

/// iomethod mode
    echo "<p>";
    echo get_string('iomethod', 'contester')."<br />";
    echo "<select name=\"iomethodmode\" id=\"iomodeselect\">";
    echo "<option value=\"0\"";
    if ($res->iomethodmode == 0)
        echo " selected";
    echo ">".get_string('mode_file', 'contester')."</option>";
    echo "<option value=\"1\"";
    if ($res->iomethodmode == 1)
        echo " selected";
    echo ">".get_string('mode_console', 'contester')."</option>";
    echo "<option value=\"2\"";
    if ($res->iomethodmode == 2)
        echo " selected";
    echo ">".get_string('mode_both', 'contester')."</option>";
    echo "</select>";
    echo "</p>";


 	contester_print_link_to_problems_preview($a);

 	if ($is_admin) {
 		echo '<p>';
 		contester_print_link_to_upload($a);
 		echo '</p>';
 	}
 	
	echo '<br>'; 	
 	
	echo '</center>'; 	

	echo '<center><input type=submit value="'.get_string('save', 'contester').'"</center>';
	echo '<input type=hidden name="a" value="'.$contester->id.'">';
	echo '</form>';

/// Finish the page
	contester_print_end();
    echo $OUTPUT->footer()

?>
