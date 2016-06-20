<?php
require_once("$CFG->libdir/formslib.php");
 
class simplehtml_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
 
        $mform = $this->_form; // Don't forget the underscore!  
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}
?>