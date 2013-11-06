<?php

// Allow only moodle internal call
defined('MOODLE_INTERNAL') || die;

// Required files
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/term/locallib.php');
require_once('rest.php');

// This class needs to have the namescheme modulename_filename and extends the moodleform class
class term_subtopic_form extends moodleform {
	
	// This function defines the form to add subtopics to a chosen topic
    function definition() {
	
		// To import the global references into this function
		global $REST;
		
		// Create form
        $mform = $this->_form;
		
		// Put the form in a bordered box with header
		$mform->addElement('header', 'General', get_string('subtopics_form', 'term'));
		
		// Set databasename in REST Client got from course instance config
		$REST->setDBname($this->_customdata['dbname']);
		
		// Get all topics
		$topics = $REST->get('topics', array('order'=>'topicname ASC'));
		
		// Create topicarray needed for selectbox (id as index and name as content)
        $topicbuffer = array();
		
		$check = (array) $topics[0];
		if (!array_key_exists("status", $check)) {
		
			foreach ($topics as $topic) {
				$topicbuffer[$topic->id] = $topic->topicname;
			}
		
		}
		
		// Add selectbox to choose a topic and set it as required
		$mform->addElement('select', 'topicid', get_string('choose_topic', 'term'), $topicbuffer, array('style'=>'width:400px'));
        $mform->addRule('topicid', get_string('required', 'term'), 'required', null, 'client');
		
		// Add textfield to enter a subtopicname and set it as required. Default its empty and only Text allowed.
        $mform->addElement('text', 'subtopicname', get_string('subtopic', 'term'), array('style'=>'width:400px'));
		$mform->setDefault('subtopicname', '');
        $mform->setType('subtopicname', PARAM_TEXT);
        $mform->addRule('subtopicname', get_string('required'), 'required', null, 'client');
        $mform->addRule('subtopicname', get_string('required'), 'required', null, 'server');
        
		// Add textfield to enter a 1st reference. Default its empty and only Text allowed. Its not required.
        $mform->addElement('text', 'reference1', get_string('reference1', 'term'), array('style'=>'width:400px'));
        $mform->setDefault('reference1', '');
		$mform->setType('reference1', PARAM_TEXT);
        
		// Add textfield to enter a 2nd reference. Default its empty and only Text allowed. Its not required.
        $mform->addElement('text', 'reference2', get_string('reference2', 'term'), array('style'=>'width:400px'));
        $mform->setDefault('reference2', '');
		$mform->setType('reference2', PARAM_TEXT);
        
		// Add textfield to enter a 3rd reference. Default its empty and only Text allowed. Its not required.
        $mform->addElement('text', 'reference3', get_string('reference3', 'term'), array('style'=>'width:400px'));
		$mform->setDefault('reference3', '');
        $mform->setType('reference3', PARAM_TEXT);
		
		// If passed, get subtopic content for editing
		if (isset($this->_customdata['subtopic'])) {
			$subtopic = $this->_customdata['subtopic'];
		}
		
		if (isset($subtopic)) {
			$mform->addElement('hidden', 'subtopicid', $subtopic->id);
			$mform->addElement('hidden', 'n', $this->_customdata['cmid']);
            $this->set_data($subtopic);
		} else {
            $mform->addElement('hidden', 'id', $this->_customdata['cmid']);
        }
		
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
		$response = $REST->get("subtopic", array('subtopicname'=>$data['subtopicname']));
		$check = (array) $response[0];
		if (!array_key_exists("status", $check) && !isset($data['n']))
			$errors['subtopicname'] = get_string('subtopic_exists', 'term');
        
		return $errors;
    }	
}