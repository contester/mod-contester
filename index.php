<?PHP // $Id: index.php,v 1.3 2006/04/29 22:22:27 skodak Exp $

/// This page lists all the instances of contester in a particular course
/// Replace contester with the name of your module

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "contester", "view all", "index.php?id=$course->id", "");


/// Get all required strings

    $strcontesters = get_string("modulenameplural", "contester");
    $strcontester  = get_string("modulename", "contester");


/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

    print_header("$course->shortname: $strcontesters", "$course->fullname", "$navigation $strcontesters", "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $contesters = get_all_instances_in_course("contester", $course)) {
        notice("There are no contesters", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname);
        $table->align = array ("center", "left");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ("center", "left", "left", "left");
    } else {
        $table->head  = array ($strname);
        $table->align = array ("left", "left", "left");
    }

    foreach ($contesters as $contester) {
        if (!$contester->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$contester->coursemodule\">$contester->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$contester->coursemodule\">$contester->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($contester->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>
