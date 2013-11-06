<?php
// Required files
require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once('rest.php');

// Get parameters
$id        = required_param('id', PARAM_INT); // Here: subtopicid
$cmid      = required_param('cmid', PARAM_INT);
$sesskey   = required_param('sesskey', PARAM_ALPHA);
$confirm   = optional_param('confirm', 0, PARAM_BOOL); // Here: if deleting is confirmed
$cm         = get_coursemodule_from_id('term', $cmid, 0, false, MUST_EXIST);
$term  		= $DB->get_record('term', array('id' => $cm->instance), '*', MUST_EXIST);

// Set databasename in REST Client got from course instance config
$REST->setDBname($term->dbname);

// Require login and valid session
require_login();
require_sesskey();

// Get context to check capability
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('mod/term:deleteterms', $context);
$PAGE->set_context($context);

// Set URL, title, header and class of body
$title = get_string('delete_subtopic', 'term');
$PAGE->set_url('/mod/term/deletesubtopic.php', array('id'=>$id, 'sesskey'=>$sesskey, 'cmid'=>$cmid));
$PAGE->set_title(format_string($title));
$PAGE->set_heading(format_string($title));
$PAGE->add_body_class('mod_term');

// If the delete operation was confirmed
if ($confirm) {
	
	// Delete subtopic and related mappings
	$REST->delete("subtopic", array('id'=>$id));
    redirect('managesubtopics.php?id='.$cmid);
}

// Print site header
echo $OUTPUT->header();

// Build redirect URLs for user decision (continue or cancel deleting)
$continue = new moodle_url('/mod/term/deletesubtopic.php', array('id'=>$id, 'sesskey'=>$sesskey, 'confirm'=>1, 'cmid'=>$cmid));
$cancel = new moodle_url('/mod/term/managesubtopics.php', array('id'=>$cmid));

// Print confirmation question
$confirm_string = get_string('confirm_delete_subtopic', 'term');
echo $OUTPUT->confirm("<strong>".get_string('delete_subtopic', 'term')."</strong><p>$confirm_string</p>", $continue, $cancel);

// Print site footer
echo $OUTPUT->footer();