<?php

// Allow only moodle internal call
defined('MOODLE_INTERNAL') || die;

// Required files
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/term/locallib.php');
require_once('rest.php');

// This class needs to have the namescheme modulename_filename and extends the moodleform class
class term_topic_form extends moodleform {
	
	// This function defines the form to add subtopics to a chosen topic
    function definition() {
		
		global $REST;
	
		// Create form
        $mform = $this->_form;
		
		// Put the form in a bordered box with header
		$mform->addElement('header', 'General', get_string('add_topic', 'term'));
		
		// Add textfield to enter a topicname and set it as required. Default its empty and only Text allowed.
        $mform->addElement('text', 'topicname', get_string('topic', 'term'), array('style'=>'width:400px'));
		$mform->setDefault('topicname', '');
        $mform->setType('topicname', PARAM_TEXT);
        $mform->addRule('topicname', get_string('required', 'term'), 'required', null, 'client');
        $mform->addRule('topicname', get_string('required', 'term'), 'required', null, 'server');
		
		// If passed, get topic content for editing
		if (isset($this->_customdata['topic'])) {
			$topic = $this->_customdata['topic'];
		}
				
		if (isset($topic)) {
			$mform->addElement('hidden', 'topicid', $topic->id);
			$mform->addElement('hidden', 'n', $this->_customdata['cmid']);
            $this->set_data($topic);
		} else {
            $mform->addElement('hidden', 'id', $this->_customdata['cmid']);
        }
		
		// Set databasename in REST Client got from course instance config
		$REST->setDBname($this->_customdata['dbname']);
		
		// Add action buttons for the form (submit, cancel)
		$this->add_action_buttons();
	}
		
	// This function validates the form submitted data
    function validation($data, $files) {
        
		// To import the global references into this function
		global $REST;
		
		// Validate the submitted data of setted types and rules
		$errors = parent::validation($data, $files);
		
		// Validate of term is already set
		$response = $REST->get("topic", array('topicname'=>$data['topicname']));
		$check = (array) $response[0];
		if (!array_key_exists("status", $check) && !isset($data['n']))
			$errors['topicname'] = get_string('topic_exists', 'term');
        
		return $errors;
    }	
}