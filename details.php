<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// This page prints a particular instance of contester

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // contester instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('contester', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $contester  = $DB->get_record('contester', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $contester  = $DB->get_record('contester', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $contester->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('contester', $contester->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

add_to_log($course->id, 'contester', 'view', "view.php?id={$cm->id}", $contester->name, $cm->id);


/// Print the page header

$PAGE->set_url('/mod/contester/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($contester->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// other things you may want to set - remove if not needed
//$PAGE->set_cacheable(false);
//$PAGE->set_focuscontrol('some-html-id');
//$PAGE->add_body_class('contester-'.$somevar);

// Output starts here
echo $OUTPUT->header();

if ($contester->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('contester', $contester, $cm->id), 'generalbox mod_introbox', 'contesterintro');
}

/*
    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strcontesters</a> -> $contester->name",
                  "", "", true, update_module_button($cm->id, $course->id, $strcontester),
                  navmenu($course, $cm));
*/
/// Print the main part of the page
	contester_print_begin($contester->id);

	$submitid = required_param('sid', PARAM_INT);
	$result = contester_get_detailed_info($submitid);
	//print_r($result);
	echo "<p>";
	contester_draw_assoc_table($result);
	echo "</p>";

/// Finish the page
	contester_print_end();
	echo $OUTPUT->footer();

?>
