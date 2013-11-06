<?php

// Allow only moodle internal call
defined('MOODLE_INTERNAL') || die;

// Required files
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/term/locallib.php');
require_once('rest.php');

// This class needs to have the namescheme modulename_filename and extends the moodleform class
class term_statics_form extends moodleform {
	
	// This function defines the form to add subtopics to a chosen topic
    function definition() {
	
		// To import the global references into this function
		global $REST;
		
		// Get passed id
		$id = $this->_customdata['id'];
		
		// Create form
        $mform = $this->_form;

		// Put the form in a bordered box with header
		$mform->addElement('header', 'General', get_string('statistics', 'term'), array('style'=>'width:400px'));
		
		// Set databasename in REST Client got from course instance config
		$REST->setDBname($this->_customdata['dbname']);
		
		// Get all topics
		$topics = $REST->get('topics', array('order'=>'topicname ASC'));
		
		// Create topicarray needed for selectbox (id as index and name as content)
        $topicbuffer = array(''=>get_string('choose_topic', 'term'));
		
		$check = (array) $topics[0];
		if (!array_key_exists("status", $check)) {
		
			foreach ($topics as $topic) {
				$topicbuffer[$topic->id] = $topic->topicname;
			}
		
		}
		
		// Add selectbox to choose a topic and set it as required
        $mform->addElement('select', 'topicid', get_string('choose_topic', 'term'), $topicbuffer);
        $mform->setType('topicid', PARAM_TEXT);
        $mform->addRule('topicid', get_string('required', 'term'), 'required', null, 'client');		
		
		// If id is passed to the form
        if (isset($id)) {
			// Add id as hidden element for sending it as GET parameter when submit form
			$mform->addElement('hidden', 'id', $id);
        }
		
		// Add submit button for the form (print statistics)
		$mform->addElement('submit', 'submitbutton', get_string('create_chart', 'term'));
    }
}