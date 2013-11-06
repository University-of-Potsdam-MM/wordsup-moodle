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
$PAGE->set_url('/mod/term/managetopics.php', array('id' => $cm->id));
$PAGE->set_title(format_string($term->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('mod_term');

// Start output
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('topic_heading', 'term'));

// Set current tab to managetopics (needs to be before including tabs)
$currenttab = 'managetopics';

// Include the tabs
include('tabs.php');

// Print title over table
echo '<h2>'.get_string('table_title_topics', 'term').'</h2>';

// Get the per page settings of the module for the table
$perpage = $CFG->term_tablerows;

// Get pagenumber to browse the correct tablepage
$pageno = optional_param('page', 0, PARAM_INT);

// Create table
$table = new flexible_table('mod-term-reports');

$tablecolumns = array('id', 'topicname', 'subtopics', 'operations');
$tableheaders =	array(
                    get_string('id', 'term'),
                    get_string('topic', 'term'),
                    get_string('subtopics', 'term'),
                    get_string('operations', 'term')
                );
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($CFG->wwwroot.'/mod/term/managetopics.php?id='.$cm->id);

// Sorted by topicname by default
$table->sortable(true, 'topicname');

// Allow column collapsing and use initials
$table->collapsible(true);
$table->initialbars(true);

// Set column classes
$table->column_class('id', 'id');
$table->column_class('topics', 'topics');
$table->column_class('subtopics', 'subtopics');
$table->column_class('operations', 'operations');

// Set table layout attributes
$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'terms');
$table->set_attribute('class', 'submissions');
$table->set_attribute('width', '100%');

// Disable sorting for the following columns
$table->no_sorting('subtopics');
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
	$conditions['limit'] = $table->get_page_start().",".$table->get_page_size();
}

// Get topics with given order by and limit fragment to get only the results for the current tablepage
$topics = $REST->get("topics", $conditions);

$check = (array) $topics[0];
if (!array_key_exists("status", $check)) {
    
	// Set the pagesize (per page and total)
	$table->pagesize($perpage, count($topics));
	
	// Calculate the from... to positions of the table
    $offset = $pageno * $perpage;
    $endpos = $offset + $perpage;
    
	// Init currentposition as control variable in the foreach loop
	$currentpos = 0;
	
	// For every topic in topics
    foreach ($topics as $topic) {
	
		// If the position fits the tablepage (usually it fits all because of the sql query limit)
        if ($currentpos == $offset && $offset < $endpos) {
			
			// Set topicname for the topicname column
            $topicname = $topic->topicname;
			
			
			// Create subtopicarray needed for selectbox (id as index and name as content)
            $subtopicarray = array();
			foreach ($topic->subtopics as $subtopic) {
					$subtopicarray[$subtopic] = $subtopic;
                }
			// Create selectbox with all subtopics of the current topic for the subtopics column
            $subtopicselectbox = html_writer::select($subtopicarray, 'subtopicselectbox', '', array(), array('style'=>'width:400px'));
			
			// The following block creates the operations column content
			
			// Create URL to edit topic
            $editurl = new moodle_url('addtopic.php', array('id'=>$cm->id, 'sesskey'=>$USER->sesskey, 'topicid'=>$topic->id));
			// Create HTML link for this URL
            $actions = html_writer::link($editurl, get_string('edit', 'term'), array('alt'=>'editterm'));
			// Create the edit icon
            $editicon = '<a title="'.get_string('edit').'" href="'.$editurl.'"><img src="'.$OUTPUT->pix_url('t/edit').'" class="iconsmall" alt="'.get_string('edit').'" /></a>';	   
			// Concat the edit icon behind the edit link
            $actions .= $editicon;
			// Concat with a seperator between edit and delete links
            $actions .='  '.'|'.'  ';
			// Create URL to delete topic
            $delurl = new moodle_url('deletetopic.php', array('id'=>$topic->id, 'sesskey'=>$USER->sesskey, 'cmid'=>$cm->id));
			// Create HTML link for this URL
            $actions .= html_writer::link($delurl, get_string('delete', 'term'), array('alt'=>'delete'));
            // Create the delete icon
			$delicon = '<a title="'.get_string('delete').'" href="'.$delurl.'"><img src="'.$OUTPUT->pix_url('t/delete').'" class="iconsmall" alt="'.get_string('delete').'" /></a>';	   
			// Concat the delete icon behind the delete link
            $actions .= $delicon;
			
			// Create the row and fill with its content
            $row = array($topic->id, $topicname, $subtopicselectbox, $actions);
            
			// Add the row to the table
            $table->add_data($row, null);
			
			$offset++;
        }
        $currentpos++;
    }
	
	// Set the row count
    $table->totalrows = count($topics);
	
	// Print table htmlcode
    $table->print_html();

} 

// Else if there are not topics, print no topics message instead of table
else {
	// Print it in a div container
    echo html_writer::tag('div', get_string('no_topics', 'term'), array('class'=>'nosubmisson'));
}

// Print site footer
echo $OUTPUT->footer();