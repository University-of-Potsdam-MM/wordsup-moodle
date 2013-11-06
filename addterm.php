<?php
// Required files
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once('term_form.php');
require_once('rest.php');

// Need always one of this two parameters
$id = optional_param('id', 0, PARAM_INT); // coursemodule id
$n  = optional_param('n', 0, PARAM_INT);  // instance id

$termid  = optional_param('termid', 0, PARAM_INT); // termid (set if editing term)

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
$PAGE->set_url('/mod/term/addterm.php', array('id' => $cm->id));
$PAGE->set_title(format_string($term->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('mod_term');

// If termid isset, than its an update, so create term form and pass the termobject and the id to the form
if (isset($termid) && $termid != 0) {
	$data = array('id' => $termid);
	$_term = $REST->get("term", $data);
	$check = (array) $_term[0];
	if (!array_key_exists("status", $check)) {
		$term_form = new term_term_form(null, array('term'=>$_term[0], 'cmid'=>$cm->id, 'dbname'=>$term->dbname));
	}
	else die("Passed term id does not exist"); //*
} 

// Else create term form and pass the id to its hidden element
else {
    $term_form = new term_term_form(null, array('cmid'=>$cm->id, 'dbname'=>$term->dbname)); //7
    $term_form->set_data(array('id' => $cm->id, 'dbname'=>$term->dbname)); //7
}

// If the cancel button of the form is pressed, redirect to view
if ($term_form->is_cancelled()) {
    redirect("view.php?id=$cm->id");
}

// Else if the termform was submitted, get the submitted data
else if ($data = $term_form->get_data()) {
	
	// Create object with db fieldnames
	$term_submit = new stdClass();
	
	// If termid is set, prepare termobject for update row
    if (isset($termid) && $termid !=0) {
        
		$response = $REST->get("term", array('term'=>$data->term));
		$check = (array) $response[0];
		if (!array_key_exists("status", $check) && $check["id"] != $termid)
			redirect("addterm.php?id=$cm->id&termid=$data->termid&sesskey=$USER->sesskey", get_string('term_exists', 'term'), 1);
		
		// Fill termobject with content
        $term_submit->id = $data->termid;
		$term_submit->term = $data->term;
        $term_submit->badword1 = $data->badword1;
        $term_submit->badword2 = $data->badword2;
        $term_submit->badword3 = $data->badword3;
        $term_submit->badword4 = $data->badword4;
        $term_submit->badword5 = $data->badword5;
		
		// If no link is entered, set a deafult google search link
		if (isset($data->lookuplink) && $data->lookuplink != '') {
			$term_submit->lookuplink = $data->lookuplink;
		}
		else {
			$q = str_replace(' ', '+', $data->term);
			$term_submit->lookuplink = 'http://www.google.de/#hl=de&q='.$q;
		}
		
        $term_submit->level = $data->level;
		$term_submit->lastedit = time();
		
		// Get names from moodle registration for author field
		$author = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
		$term_submit->author = $author->firstname." ".$author->lastname;
        
		// Updates the term row in the database
		$REST->put("term", $term_submit);
		
		// Redirect after submitting to the termform again
        redirect("view.php?id=$cm->id", get_string('term_updating','mod_term'), 1);
    } 
	
	// Else if no termid is set, prepare termobject for inserting new row
	else {
        $term_submit->term = $data->term;
        $term_submit->badword1 = $data->badword1;
        $term_submit->badword2 = $data->badword2;
        $term_submit->badword3 = $data->badword3;
        $term_submit->badword4 = $data->badword4;
        $term_submit->badword5 = $data->badword5;
        
		if (isset($data->lookuplink) && $data->lookuplink != '') {
			$term_submit->lookuplink = $data->lookuplink;
		}
		else {
			$q = str_replace(' ', '+', $data->term);
			$term_submit->lookuplink = 'http://www.google.de/#hl=de&q='.$q;
		}
		
        $term_submit->level = $data->level;
		$term_submit->lastedit = time();
		
		// Get names from moodle registration for author field
		$author = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
		$term_submit->author = $author->firstname." ".$author->lastname;
		
		// Insert the new term in the database and get its id
		$response = $REST->post("term", $term_submit);
		$term_submit->id = $response->id;
		
		// Redirect after submitting to the termform again
        redirect("mappings.php?id=$cm->id&termid=$term_submit->id", get_string('term_inserting','mod_term'), 1);
    }
}

// Else if form is launched without any action (no submit, no cancel)
else {
	// Start output
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('addterm_heading','mod_term'));
	
	// Set current tab to addterm (needs to be before including tabs)
    $currenttab = 'add';
	
	// Include the tabs
    include('tabs.php');
	
	// Show the form
	$term_form->display();
}

// Print site footer
echo $OUTPUT->footer();