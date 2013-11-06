<?php
// Required files
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once('topic_form.php');
require_once('subtopic_form.php');
require_once('rest.php');

// Need always one of this two parameters
$id = optional_param('id', 0, PARAM_INT); // coursemodule id
$n  = optional_param('n', 0, PARAM_INT);  // instance id
$topicid  = optional_param('topicid', 0, PARAM_INT); // topicid (set if editing topic)
$subtopicid  = optional_param('subtopicid', 0, PARAM_INT); // subtopicid (set if editing subtopic)

// Get module information from given parameter
if ($id) {
    $cm         = get_coursemodule_from_id('term', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $term  		= $DB->get_record('term', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $cm         = get_coursemodule_from_id('term', $n, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $term  		= $DB->get_record('term', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

// Set databasename in REST Client got from course instance config
$REST->setDBname($term->dbname);

// Require login and coursemodule access
require_login($course, true, $cm);

// Get context and set it to this page
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$PAGE->set_context($context);

// Set URL, title, header and class of body
$PAGE->set_url('/mod/term/addtopic.php', array('id' => $cm->id));
$PAGE->set_title(format_string($term->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('mod_term');

// If topicid isset, than its an update, so create topic form and pass the topicobject and the id to the form
if (isset($topicid) && $topicid != 0) {
	$_topic = $REST->get('topic', array('id' => $topicid));
	$check = (array) $_topic[0];
	if (!array_key_exists("status", $check)) {
		$topic_form = new term_topic_form(null, array('topic'=>$_topic[0], 'cmid'=>$cm->id, 'dbname'=>$term->dbname));
	}
	else die("Passed topic does not exist");
} 
else {
	// Create topic form and pass the id to its hidden element
    $topic_form = new term_topic_form(null, array('cmid'=>$cm->id, 'dbname'=>$term->dbname));
	$topic_form->set_data(array('id' => $cm->id)); 
}

if (isset($subtopicid) && $subtopicid != 0) {
	$_subtopic = $REST->get('subtopic', array('id' => $subtopicid));
	$check = (array) $_subtopic[0];
	if (!array_key_exists("status", $check)) {
		$subtopic_form = new term_subtopic_form(null, array('subtopic'=>$_subtopic[0], 'cmid'=>$cm->id, 'dbname'=>$term->dbname)); //7
	}
	else die("Passed subtopic does not exist");
    
}
// Else create topic form and pass the id to its hidden element
else {

	// Create subtopic form and pass the id to its hidden element
	$subtopic_form = new term_subtopic_form(null, array('cmid' => $cm->id, 'dbname'=>$term->dbname));
}

// If any cancel button of the both forms is pressed, redirect to view
if ($topic_form->is_cancelled() or $subtopic_form->is_cancelled() ) {
    redirect("view.php?id=$cm->id");
}

// Else if the topicform was submitted, get the submitted data
else if ($data = $topic_form->get_data()) {
    global $DB, $USER, $REST;
	
	// Create object with db fieldnames and fill row content
	$topic = new stdClass();
	
	/////
	if (isset($topicid) && $topicid !=0) {
		$topic->id = $data->topicid;
		$topic->topicname = $data->topicname;
		$topic->lastedit  = time();
		
		// Get names from moodle registration for author field
		$author = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
		$topic->author = $author->firstname." ".$author->lastname;
		
		// Updates the topic row in the database
		$REST->put('topic', $topic);
		
		// Redirect after submitting to the topicform again
		redirect("addtopic.php?id=$cm->id", get_string('add_topic','mod_term'), 1);
	}
	
	else {	
		$topic->topicname = $data->topicname;
		$topic->lastedit  = time();
		
		// Get names from moodle registration for author field
		$author = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
		$topic->author = $author->firstname." ".$author->lastname;
		
		// Insert the new topic in the database and get its id
		$topic->id = $REST->post('topic', $topic);
		
		// Redirect after submitting to the topicform again
		redirect("addtopic.php?id=$cm->id", get_string('add_topic','mod_term'), 1);
	}
}

// Else if the subtopicform was submitted, get the submitted data
else if ($data = $subtopic_form->get_data()) {
    global $DB, $USER, $REST;
	
	// Create object with db fieldnames and fill row content
    $subtopic = new stdClass();
	
	if (isset($subtopicid) && $subtopicid !=0) {
		$subtopic->id = $data->subtopicid;
		$subtopic->subtopicname = $data->subtopicname;
		$subtopic->topicid  = $data->topicid;
		$subtopic->reference1  = $data->reference1;
		$subtopic->reference2  = $data->reference2;
		$subtopic->reference3  = $data->reference3;
		$subtopic->lastedit  = time();
		
		// Get names from moodle registration for author field
		$author = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
		$subtopic->author = $author->firstname." ".$author->lastname;
		
		// Updates the topic row in the database
		$REST->put('subtopic', $subtopic);
		
		// Redirect after submitting to the subtopicform again
		redirect("addtopic.php?id=$cm->id", get_string('add_subtopic', 'mod_term'));
	}
	else {
		$subtopic->subtopicname = $data->subtopicname;
		$subtopic->topicid  = $data->topicid;
		$subtopic->reference1  = $data->reference1;
		$subtopic->reference2  = $data->reference2;
		$subtopic->reference3  = $data->reference3;
		$subtopic->lastedit  = time();
		
		// Get names from moodle registration for author field
		$author = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
		$subtopic->author = $author->firstname." ".$author->lastname;
		
		// Insert the new subtopic in the database and get its id
		$subtopic->id = $REST->post('subtopic', $subtopic);
		
		// Redirect after submitting to the subtopicform again
		redirect("addtopic.php?id=$cm->id", get_string('add_subtopic', 'mod_term'));
	}
}

// Else if form is launched without any action (no submit, no cancel)
else {
	// Start output
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('addtopic_heading', 'mod_term'));
	
	// Set current tab to addtopic (needs to be before including tabs)
    $currenttab = 'addtopic';
	
	// Include the tabs
    include('tabs.php');
	
	// Show the two forms
	if (isset($topicid) && $topicid != 0) {
		$topic_form->display();
	}
	else if (isset($subtopicid) && $subtopicid != 0) {
		$subtopic_form->display();
	}
	else {
		$topic_form->display();
		$subtopic_form->display();
	}
}

// Print site footer
echo $OUTPUT->footer();