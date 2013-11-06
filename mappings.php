<?php
// Required files
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once('mappings_form.php');
require_once('rest.php');

// Need always one of this two parameters
$id = optional_param('id', 0, PARAM_INT); // coursemodule id
$n  = optional_param('n', 0, PARAM_INT);  // instance id

// Further parameters
$termid = required_param('termid', PARAM_INT);
$level = optional_param('level', 0, PARAM_INT);
$topicid = optional_param('topicid', 0, PARAM_INT);
$subtopicname = optional_param('subtopics', 0, PARAM_ALPHANUMEXT);

// Get module information from given parameter
if ($id) {
    $cm         = get_coursemodule_from_id('term', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $term  		= $DB->get_record('term', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $term  		= $DB->get_record('term', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $term->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('term', $term->id, $course->id, false, MUST_EXIST);
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
$PAGE->set_url('/mod/term/mappings.php', array('id' => $cm->id));
$PAGE->set_title(format_string($term->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('mod_term');

// Get term with the id
$termtomap = $REST->get('term', array('id'=>$termid));
$termtomap = $termtomap[0];

// Create the mappings form and pass ids
$mform = new term_mappings_form(null, array('id'=>$id, 'topicid'=>$topicid, 'termid'=>$termid, 'dbname'=>$term->dbname));

// If the cancel button of the form is pressed, redirect to view
if ($mform->is_cancelled()) {
    redirect("view.php?id=$cm->id");
} 

// Else if the termform was submitted, get the submitted data
else if ($data = $mform->get_data()) {   
    global $DB, $USER;
	
	// Create object with db fieldnames and fill with content
    $newmapping = new stdClass();
	$newmapping->termid = $data->termid;
	$newmapping->subtopic = $data->subtopics;
    $newmapping->lastedit = time();
	
	// Get names from moodle registration for author field
	$author = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
    $newmapping->author = $author->firstname." ".$author->lastname;
	$newmapping->id = $REST->post('mapping', $newmapping);
	
	// Redirect after submitting to the mappings again
    redirect("mappings.php?id=$cm->id&termid=$termid");
} 

// Else if form is launched without any action (no submit, no cancel)
else {
	// Start output
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('mappings_heading', 'term'));
	
	echo '<h2>'.get_string('add_mappings', 'term').$termtomap->term.'</h2>';
	
	// Show the form
	$mform->display();
	
	// Get all existing mappings
    $mappings = $REST->get("mappings", array('termid'=>$termid));
	
	// If there are some mappings
	$check = (array) $mappings[0];
	if (!array_key_exists("status", $check)) {
		
		// Print a title above the existing mappings
        echo '<h3>'.get_string('existing_mappings', 'term').'<b>'.$termtomap->term.'</b></h3>';
        
		// Print a bordered box for the existing mappings
		echo $OUTPUT->box_start('generalbox term_content');
		
		// Create a html fieldset
        echo html_writer::start_tag('fieldset', array('class' => 'modulelegend'));
        
		// For every mapping create a fieldentry with delete option icon
        foreach ($mappings as $mapping) {
			
			// Create URL to delete mapping
			$delurl = new moodle_url('deletemapping.php', array('id'=>$mapping->mapid, 'sesskey'=>$USER->sesskey, 'cmid'=>$cm->id, 'termid'=>$termid));
			// Create HTML link for this URL
            $actions = html_writer::link($delurl, get_string('delete', 'term'), array('alt'=>'delete'));
			// Create the delete icon
            $delicon = '<a title="'.get_string('delete', 'term').'" href="'.$delurl.'"><img src="'.$OUTPUT->pix_url('t/delete').'" class="iconsmall" alt="'.get_string('delete', 'term').'" /></a>';	   
			// Concat the delete icon behind the delete link
            $actions .= $delicon;
			
			// Print entry with mapping as "topicname > subtopicname" and delete icon behind
            echo html_writer::tag('p', $mapping->mapname.' ('.$actions.')');
        }
		
		// Create div container for finish mappings button, which redirects to view
        echo html_writer::start_tag('div', array('class' => 'finishterm', 'style'=>'float:right'));
        echo $OUTPUT->single_button(new moodle_url('/mod/term/view.php', array('id'=>$cm->id)), get_string('finish_mappings', 'mod_term'),'', array('style'=>'margin-left:200px'));
        echo html_writer::end_tag('div');
		
		// Close tags and do linebreak
        echo html_writer::end_tag('fieldset'); 
        echo $OUTPUT->box_end();
        echo html_writer::tag('br','');
    }
}

// Print site footer
echo $OUTPUT->footer();
