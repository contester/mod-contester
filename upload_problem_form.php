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

    $PAGE->set_url('/mod/contester/upload_problem_form.php', array('a' => $a));
    $PAGE->set_title("$course->shortname: $contester->name");
    $PAGE->set_heading("$course->fullname");
    $contester_url = new moodle_url('/mod/contester/view.php', array('a' => $a));
    $PAGE->navbar->add("$contester->name", $contester_url);  
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);
    $PAGE->set_button(update_module_button($cm->id, $course->id, get_string("modulename", "contester")));
    
    echo $OUTPUT->header(); 

/// Print the main part of the page

    echo "
	<form enctype=\"multipart/form-data\" method=\"post\" action=\"upload_problem.php?a=".$a."\">".
	get_string('dbid', 'contester')." <input type=text name='dbid' value=''><br/>".
	get_string('defintoupload', 'contester')." <input type=\"file\" name=\"definition\"><br/><input type=\"submit\" value=\"".get_string('submit', 'contester')."\"></form>
	";

/// Finish the page
    contester_print_end();
    echo $OUTPUT->footer()

?>
