<?php

// Allow only moodle internal call
defined('MOODLE_INTERNAL') || die;

// Required files
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/term/locallib.php');
require_once('rest.php');

// This class needs to have the namescheme modulename_filename and extends the moodleform class
class term_term_form extends moodleform {
	
	// This function defines the form to add subtopics to a chosen topic
    function definition() {
		
		// To import the global references into this function
        global $USER, $OUTPUT, $REST; //7 REST dazu
		
        // Create form
        $mform = $this->_form;
		
		// Put the form in a bordered box with header
		$mform->addElement('header', 'General', get_string('add_term', 'term'));
		
		// Add textfield to enter a termname and set it as required. Default its empty and only Text allowed.
        $mform->addElement('text', 'term', get_string('term', 'term'), array('style'=>'width:400px'));
        $mform->setDefault('term', '');
		$mform->setType('term', PARAM_TEXT);
        $mform->addRule('term', get_string('required', 'term'), 'required', null, 'client');
        $mform->addRule('term', get_string('required', 'term'), 'required', null, 'server');
		
		// Add textfield to enter badword1 and set it as required. Default its empty and only Text allowed.
        $mform->addElement('text', 'badword1', get_string('badword1', 'term'), array('style'=>'width:400px'));
		$mform->setDefault('badword1', '');
        $mform->setType('badword1', PARAM_TEXT);
        $mform->addRule('badword1', get_string('required', 'term'), 'required', null, 'client');
		
		// Add textfield to enter badword2 and set it as required. Default its empty and only Text allowed.
        $mform->addElement('text', 'badword2', get_string('badword2', 'term'), array('style'=>'width:400px'));
		$mform->setDefault('badword2', '');
        $mform->setType('badword2', PARAM_TEXT);
        $mform->addRule('badword2', get_string('required', 'term'), 'required', null, 'client');
		
		// Add textfield to enter badword3 and set it as required. Default its empty and only Text allowed.
		$mform->addElement('text', 'badword3', get_string('badword3', 'term'), array('style'=>'width:400px'));
		$mform->setDefault('badword3', '');
        $mform->setType('badword3', PARAM_TEXT);
        $mform->addRule('badword3', get_string('required', 'term'), 'required', null, 'client');
		
		// Add textfield to enter badword4 and set it as required. Default its empty and only Text allowed.
		$mform->addElement('text', 'badword4', get_string('badword4', 'term'), array('style'=>'width:400px'));
		$mform->setDefault('badword4', '');
        $mform->setType('badword4', PARAM_TEXT);
        $mform->addRule('badword4', get_string('required', 'term'), 'required', null, 'client');
		
		// Add textfield to enter badword5 and set it as required. Default its empty and only Text allowed.
		$mform->addElement('text', 'badword5', get_string('badword5', 'term'), array('style'=>'width:400px'));
		$mform->setDefault('badword5', '');
        $mform->setType('badword5', PARAM_TEXT);
        $mform->addRule('badword5', get_string('required', 'term'), 'required', null, 'client');
		
		// Add textfield to enter link and set it as required. Default its empty and only valid URLs allowed.
		$mform->addElement('text', 'lookuplink', get_string('lookuplink', 'term'), array('style'=>'width:400px'));
		$mform->setDefault('lookuplink', '');
        $mform->setType('lookuplink', PARAM_TEXT);
		//$mform->addRule('lookuplink', get_string('required', 'term'), 'required', null, 'client'); //should be not required...
        $mform->addRule('lookuplink', 'URL', 'regex', '^http\://[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(/\S*)?$^', 'server');
	
		// Add selectbox to choose a level and set it as required. Default is the first arrayindex. Options are 1 to 3.
		$levelbox = $mform->addElement('select', 'level', get_string('level', 'term'), array('1'=>get_string('level_easy', 'term'), '2'=>get_string('level_normal', 'term'), '3'=>get_string('level_hard', 'term')), array('style'=>'width:100px'));
		$levelbox->setSelected('2');
		$mform->addRule('level', get_string('required', 'term'), 'required', null, 'client');
        
		// If data is passed, than its an editform 
		if (isset($this->_customdata['term'])) {
            $term = $this->_customdata['term'];
        }
		
		// Set databasename in REST Client got from course instance config
		$REST->setDBname($this->_customdata['dbname']);
		
		// If its an editform, also load the mappings
        if (isset($term)) {
			// Create URL to edit mapping for the loaded term
            $editurl = new moodle_url('mappings.php', array('id'=>$this->_customdata['cmid'], 'sesskey'=>$USER->sesskey, 'termid'=>$term->id));   
            // Create HTML link for this URL
			$actions = html_writer::link($editurl, get_string('add_edit_mappings', 'term'), array('alt'=>'editterm'));
            // Create the edit icon
			$editicon = '<a title="'.get_string('edit', 'term').'" href="'.$editurl.'"><img src="'.$OUTPUT->pix_url('t/edit').'" class="iconsmall" alt="'.get_string('edit', 'term').'" /></a>';
			// Concat the icon behind the link
            $actions .= $editicon;
			
			// Create custom div container for the link
            $mform->addElement('html', '<div id="addmappings">');
            $mform->addElement('html', $actions);
            $mform->addElement('html', '</div>');
			
			// If there are some mappings
			if ($term->mappings) {
				// Print a mappings header one time
                $mform->addElement('html', '<br/>');
                $mform->addElement('html', '<p><b>'.get_string('existing_mappings', 'term').'</b></p>');
				
				// Print every mapping for this term
                foreach ($term->mappings as $mapping) {
					// Handle mapping as custom html form element
					$mform->addElement('html', '<p>'.$mapping.'</p>');
                }
            }
        }
		
		// Add id as hidden element for sending it as GET parameter when submit form
        if (isset($term)) {
            $mform->addElement('hidden', 'termid', $term->id); //for the GET param
            $mform->addElement('hidden', 'n', $this->_customdata['cmid']); //for the GET param
        } else {
            $mform->addElement('hidden', 'id', $this->_customdata['cmid']); //for the GET param
        }
		
		// Add action buttons for the form (submit, cancel)
		$this->add_action_buttons();
		
		if (isset($term)) {
            $this->set_data($term);
        }
	}
    	
	// This function validates the form submitted data
    function validation($data, $files) {
        
		// To import the global references into this function
		global $REST;
        
		// Validate the submitted data of setted types and rules
		$errors = parent::validation($data, $files);
		
		// Validate of term is already set
		$response = $REST->get("term", array('term'=>$data['term']));
		$check = (array) $response[0];
		if (!array_key_exists("status", $check) && !isset($data['n']))
			$errors['term'] = get_string('term_exists', 'term');
        
		return $errors;
    }	
}