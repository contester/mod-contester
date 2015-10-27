<?PHP  // $Id: view.php,v 1.2 2006/04/29 22:19:41 skodak Exp $


    require_once("../../config.php");
    require_once("lib.php");

    require_login();

    add_to_log(0, "contester", "upload", "upload_problem_form.php", "$contester->id");
    
    if (!isadmin()) {
    	error(get_string('accessdenied', 'contester'));
    }

    print_header();

/// Print the main part of the page
	echo "
	<form enctype=\"multipart/form-data\" method=\"post\" action=\"upload_problem.php\">".
	get_string('dbid', 'contester')." <input type=text name='dbid' value=''><br/>".
	get_string('defintoupload', 'contester')." <input type=\"file\" name=\"definition\"><br/><input type=\"submit\" value=\"".get_string('submit', 'contester')."\"></form>
	";
	print_footer();

?>
