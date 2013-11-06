<?php
// Required files
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once('rest.php');

// Need always one of this two parameters
$id = optional_param('id', 0, PARAM_INT); // coursemodule id
$n  = optional_param('n', 0, PARAM_INT);  // instance id

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
$PAGE->set_url('/mod/term/managesubtopics.php', array('id' => $cm->id));
$PAGE->set_title(format_string($term->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('mod_term');

// Start output
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('subtopic_heading', 'term'));

// Set current tab to managesubtopics (needs to be before including tabs)
$currenttab = 'managesubtopics';

// Include the tabs
include('tabs.php');

// Print title over table
echo '<h2>'.get_string('table_title_subtopics', 'term').'</h2>';

// Get the per page settings of the module for the table
$perpage = $CFG->term_tablerows;

// Get pagenumber to browse the correct tablepage
$pageno = optional_param('page', 0, PARAM_INT);

// Create table
$table = new flexible_table('mod-term-managesubtopics');

$tablecolumns = array('id', 'subtopicname', 'topic', 'reference1', 'reference2', 'reference3', 'operations');
$tableheaders =	array(
					get_string('id', 'term'),
                    get_string('subtopic', 'term'),
                    get_string('topic', 'term'),
                    get_string('reference1', 'term'),
					get_string('reference2', 'term'),
					get_string('reference3', 'term'),
                    get_string('operations', 'term')
                );
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($CFG->wwwroot.'/mod/term/managesubtopics.php?id='.$cm->id);

// Sorted by topicname by default
$table->sortable(true, 'subtopicname');

// Allow column collapsing and use initials
$table->collapsible(true);
$table->initialbars(true);

// Set column classes
$table->column_class('id', 'id');
$table->column_class('subtopicname', 'subtopicname');
$table->column_class('topic', 'topic');
$table->column_class('reference1', 'reference1');
$table->column_class('reference2', 'reference2');
$table->column_class('reference3', 'reference3');
$table->column_class('operations', 'operations');

// Set table layout attributes
$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'terms');
$table->set_attribute('class', 'submissions');
$table->set_attribute('width', '100%');

// Disable sorting for the following columns
$table->no_sorting('operations');

// Setup the table with definitions above
$table->setup();

// Create conditions array for sql query
$conditions = array();

// Get the order of the tablerows as order by fragment
if ($sort = $table->get_sql_sort()) {
	$conditions['order'] = $sort;
}

// Get the table start and end row number as sql limit fragment
if ( is_numeric($table->get_page_start()) && is_numeric($table->get_page_size()) ) {
	$conditions['limit'] = $table->get_page_start().", ".$table->get_page_size();
}

// Get subtopics with given order by and limit fragment to get only the results for the current tablepage
$subtopics = $REST->get("subtopics", $conditions);

$check = (array) $subtopics[0];
if (!array_key_exists("status", $check)) {
    
	// Set the pagesize (per page and total)
	$table->pagesize($perpage, count($subtopics));
	
	// Calculate the from... to positions of the table
    $offset = $pageno * $perpage;
    $endpos = $offset + $perpage;
    
	// Init currentposition as control variable in the foreach loop
	$currentpos = 0;
	
	// For every subtopic in subtopics
    foreach ($subtopics as $subtopic) {
	
		// If the position fits the tablepage (usually it fits all because of the sql query limit)
        if ($currentpos == $offset && $offset < $endpos) {
			
			// Set subtopicname for the topicname column
            $subtopicname = $subtopic->subtopicname;
			$topicname = $subtopic->topic;
			$ref1 = $subtopic->reference1;
			$ref2 = $subtopic->reference2;
			$ref3 = $subtopic->reference3;
			
			// The following block creates the operations column content
			
			// Create URL to edit subtopic
            $editurl = new moodle_url('addtopic.php', array('id'=>$cm->id, 'sesskey'=>$USER->sesskey, 'subtopicid'=>$subtopic->id));
			// Create HTML link for this URL
            $actions = html_writer::link($editurl, get_string('edit', 'term'), array('alt'=>'editsubtopic'));
			// Create the edit icon
            $editicon = '<a title="'.get_string('edit').'" href="'.$editurl.'"><img src="'.$OUTPUT->pix_url('t/edit').'" class="iconsmall" alt="'.get_string('edit').'" /></a>';	   
			// Concat the edit icon behind the edit link
            $actions .= $editicon;
			// Concat with a seperator between edit and delete links
            $actions .='  '.'|'.'  ';
			// Create URL to delete subtopic
            $delurl = new moodle_url('deletesubtopic.php', array('id'=>$subtopic->id, 'sesskey'=>$USER->sesskey, 'cmid'=>$cm->id));
			// Create HTML link for this URL
            $actions .= html_writer::link($delurl, get_string('delete', 'term'), array('alt'=>'delete'));
            // Create the delete icon
			$delicon = '<a title="'.get_string('delete').'" href="'.$delurl.'"><img src="'.$OUTPUT->pix_url('t/delete').'" class="iconsmall" alt="'.get_string('delete').'" /></a>';	   
			// Concat the delete icon behind the delete link
            $actions .= $delicon;
			
			// Create the row and fill with its content
            $row = array($subtopic->id, $subtopicname, $topicname, $ref1, $ref2, $ref3, $actions); //? topicid to topicname
            
			// Add the row to the table
            $table->add_data($row, null);
			
			$offset++;
        }
        $currentpos++;
    }
	
	// Set the row count
    $table->totalrows = count($subtopics);
	
	// Print table htmlcode
    $table->print_html();

} 

// Else if there are not subtopics, print no subtopics message instead of table
else {
	// Print it in a div container
    echo html_writer::tag('div', get_string('no_subtopics', 'term'), array('class'=>'nosubmisson'));
}

// Print site footer
echo $OUTPUT->footer();