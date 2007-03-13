<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $

/// This page prints a particular instance of contester
/// (Replace contester with the name of your module)

    require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // contester ID

    if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            error("Course Module ID was incorrect");
        }
    
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }
    
        if (! $contester = get_record("contester", "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $contester = get_record("contester", "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $contester->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("contester", $contester->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    add_to_log($course->id, "contester", "view", "view.php?id=$cm->id", "$contester->id");

/// Print the page header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");

    print_header("$course->shortname: $contester->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strcontesters</a> -> $contester->name", 
                  "", "", true, update_module_button($cm->id, $course->id, $strcontester), 
                  navmenu($course, $cm));

/// Print the main part of the page

	contester_print_begin($contester->id);

    echo "<form enctype=\"multipart/form-data\" method=\"post\" action=\"submit.php?a={$contester->id}\">";

    //if ($r = get_recordset_select("contester_problems")) {
    if ($r = get_recordset_sql("SELECT * FROM mdl_contester_problems cp JOIN mdl_contester_problemmap cpm ON cpm.problemid = cp.id WHERE cpm.contesterid = ".$contester->id)) {
      echo get_string('problem', 'contester').": <select name=\"problem\">";
      while (!$r->EOF) {
        echo "<option value=\"" . $r->fields["dbid"] . "\"";
        if (optional_param('pid') == $r->fields['id']) echo " selected";
        echo ">" . $r->fields["name"] . "</option>";
        $r->MoveNext();
      }
      echo "</select><br/>";
    }

    if ($r = get_recordset_select("contester_languages")) {
      echo get_string('prlanguage', 'contester').": <select name=\"lang\">";
      while (!$r->EOF) {
        echo "<option value=\"" . $r->fields["id"] . "\">" . $r->fields["name"] . "</option>";
        $r->MoveNext();
      }
      echo "</select><br/>";
    }
    
    echo get_string('solution', 'contester').": <input type=\"file\" name=\"solution\"><br><input type=\"submit\" value=\"".get_string('submit', 'contester')."\"></form>";

/// Finish the page
	contester_print_end();
    print_footer($course);

?>
