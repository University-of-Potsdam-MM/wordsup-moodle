<?php
// Required files
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once('statics_form.php');
require_once('rest.php');

// Need always one of this two parameters and the topicid
$id = optional_param('id', 0, PARAM_INT); // coursemodule id
$n  = optional_param('n', 0, PARAM_INT);  // instance id

$topicid  = optional_param('topicid', 0, PARAM_INT);

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

if (isset($topicid) && $topicid != 0) {
	
	// Get subtopics with their results
	$subtopics = $REST->get("results", array('topicid'=>$topicid));
	
	// Init chart bar html code variable
    $graphic = '';
	
	if ($subtopics) {
		// For every subtopic concat the generated html code
		foreach ($subtopics as $subtopic) {
			// Code for text above the chart bar like "Subtopicname (x/y) - z%"
			$graphic .= $subtopic->subtopicname.' ('.$subtopic->ts.'/'.$subtopic->tp.') - '.$subtopic->procent.' %';
			// Code for colored chart bar in length depending of the procent value
			$graphic .= html_writer::tag('div', '', array('class' => 'termgraph', 'style'=>'width:'.($subtopic->width+5).'px; background-color:#244f77 !important; height:12px;'));
			// Code for an linebreak between the bars
			$graphic .= html_writer::tag('br', '');
		}
	}
}

// Require login and coursemodule access
require_login($course, true, $cm);

// Get context and set it to this page
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$PAGE->set_context($context);

// Set URL, title, header and class of body
$PAGE->set_url('/mod/term/resultstatics.php', array('id' => $cm->id));
$PAGE->set_title(format_string($term->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('mod_term');

// Start output
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('result_stats_heading', 'term'));

// Set current tab to termdatabase (needs to be before including tabs)
$currenttab = 'resultstatics';

// Include the tabs
include('tabs.php');

// Create and show form for topic choosing
$mform = new term_statics_form(null, array('id'=>$id, 'dbname'=>$term->dbname));
$mform->display();

// If there is a chart, show it, else show no chart message
if (isset($graphic)) {
    echo $graphic;
} 

// Print site footer
echo $OUTPUT->footer();