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
                  "", "", true, 
                  update_module_button($cm->id, $course->id, $strcontester), 
                  navmenu($course, $cm));


/// Print the main part of the page
	echo "<br><br>";	
	echo $contester->description;
	echo "<br><br>";
	contester_print_begin($contester->id);
    $sql = "SELECT mdl_contester_problemmap.id as id, mdl_contester_problems.name as name from mdl_contester_problems, mdl_contester_problemmap
WHERE mdl_contester_problemmap.problemid=mdl_contester_problems.id and
mdl_contester_problemmap.contesterid=$contester->id order by mdl_contester_problems.name";
    $problem_list = get_recordset_sql($sql);
    if (!$problem_list->EOF)
    {
    	echo "<table width = 90% border=1><tr><td>".get_string('number', 'contester').
    	"</td><td>".get_string('name','contester')."</td><td>".
    	get_string('action', 'contester')."</td></tr>";
    
    	$i = 1;
    	foreach ($problem_list as $problem)
    	{
    		echo "<tr><td align=center>".($i++)."</td><td align=left><nobr>".
	    	$problem['name']."</td><td>";
    		print_single_button("problem.php", array("pid"=>$problem['id'], "a"=>$contester->id), get_string('view'), 'post');
    		print_single_button("submit_form.php", array("pid"=>$problem['id'],"a"=>$contester->id), get_string('submit', 'contester'), 'post');
	    	echo "</td></tr>";
    	}
    	echo "</table>";
    } else print_string('noproblems', 'contester');	
/// Finish the page
	contester_print_end();
    print_footer($course);

?>
