<?php

require_once('../../config.php');
require_once("$CFG->libdir/formslib.php");

class coursecompletion_form extends moodleform {

    public function definition() {
        global $DB;
        global $CFG;
        $mform = & $this->_form;
        
		// The first element of the form - select course
		$options = array();
        $options[0] = 'Odaberi kolegij:';
        $options += $this->_customdata['courses']; 
        $mform->addElement('select', 'course', "Kolegij:", $options, 'align="center"');
        $mform->setType('course', PARAM_ALPHANUMEXT);
        
		// The second element of the form - select test
		$mform->addElement('date_selector', 'assesstimestart', get_string('from'));
        $mform->addElement('date_selector', 'assesstimeend', get_string('to'));
        
		// The third element of the form - button submit
        $mform->addElement('submit', 'save', 'Prikaži', get_string('report_coursecompletion'), 'align="right"');
    }
}
?>