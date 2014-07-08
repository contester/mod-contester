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

	$temp_name = $_FILES["definition"]["tmp_name"];
	if (!is_uploaded_file($temp_name))
	{
		// Handle submit error
		print_file_upload_error($_FILES["definition"]);
		error('OMFG!');
	}
	$text = file_get_contents($temp_name);
	$dbid = required_param('dbid');
	contester_parse_task($text, $dbid);
	echo 'Все пучком';
	print_continue();

?>
