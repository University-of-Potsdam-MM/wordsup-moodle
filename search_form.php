<?php

defined('MOODLE_INTERNAL') || die;

// Required files
require_once($CFG->libdir.'/formslib.php');
require_once('rest.php');

class term_search_form extends moodleform {
    
    function display() {
		
		// To import the global references into this function
        global $PAGE;
		
		// Load YUI Libraries for AJAX
        $PAGE->requires->yui2_lib(array('yahoo', 'dom', 'element', 'event', 'connection', 'json'));
        
		// Create form
		$mform = &$this->_form;
		
		// Call parent display function
        parent::display();
    }
	
	// This function defines the form to add mappings to a term
    function definition() {
		
		// To import the global references into this function
        global $REST;
		
		// Create form
        $mform = $this->_form;
        
		// Get passed ids
		$id = $this->_customdata['id'];
		$topicid = $this->_customdata['topicid'];
		
		// Set DB name for REST Client 
		$REST->setDBname($this->_customdata['dbname']);
		$dbx = $this->_customdata['dbname'];
		
		// Put the form in a bordered box with header
        $mform->addElement('header', 'General', get_string('filter_by', 'term'));
		
		// Get all topics
		$data = array('order'=>'topicname ASC');
		$topics = $REST->get("topics", $data);
        
		// Create topicarray needed for selectbox (id as index and name as content)
        $topicsarray = array(''=>get_string('choose_topic', 'term'));
		
		$check = (array) $topics[0];
		if (!array_key_exists("status", $check)) {
			
			foreach ($topics as $topic) {
				$topicsarray[$topic->id]= $topic->topicname;
			}
		}

		// Add selectbox to choose a topic. On change call javascript function to reload subtopics with AJAX
        $mform->addElement('select', 'topicid', get_string('filter_topic', 'term'), $topicsarray, array('onchange' => 'topicid_updated(this.value, \''.$dbx.'\');', 'style'=>'width:400px')); //7
		
		// AJAX
        $js = 
<<<EOS
	<script type="text/javascript">
		function topicid_updated(topicid, dbname) {
			update_subtopics(topicid, dbname);
			
			// Clear textfield
			var topicids = document.getElementById('id_topic');
			if(topicids == '') {
				id_subtopicid.disabled = true;
				id_subtopicid.value = '';
			} 
			else {
				id_subtopicid.disabled = false;
			}
		}
		
		function update_subtopics(topicid, dbname) {
			var optionsarray = [];
			var sUrl = 'brain.php?topicid='+topicid+'&db='+dbname;
			var handleSuccess = function(o){
				if(o.responseText !== undefined){
					document.getElementById("id_subtopicid").options.length=0;
					var opt = document.createElement("option");
					opt.text = 'Unterthema wählen';
					opt.value = '';
					document.getElementById("id_subtopicid").options.add(opt);
					
					var itemlist = YAHOO.lang.JSON.parse(o.responseText);
					for (var i = 0, len = itemlist.length; i < len; i=i+2) {
						var item = itemlist[i];
						var opt = document.createElement("option");
						opt.text = itemlist[i];
						opt.value = itemlist[i+1];
						document.getElementById("id_subtopicid").options.add(opt);
						optionsarray[i] = item;
					}
				}
				
				var subtopicid = document.getElementById('id_subtopicid');
			}
			var handleFailure = function(o){ }
			var callback = { 
				success:handleSuccess, 
				failure: handleFailure, 
				argument: { } 
			};         
			var request = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback); 
		}
	</script>
EOS;
        
		// Save js code in static form element
        $mform->addElement('static', 'ajax_filters', '', utf8_encode($js));
        // END AJAX
		
        // If a topicid was passed to the form
        if ($topicid) {
			// Prepare to get only the topic related subtopics
			$data = array('topicid'=>$topicid, 'order'=>'subtopicname ASC');
        }		
		
		// Else if no topicid was passed to the form
		else {
			// Prepare to get all subtopics
			$data = array('order'=>'subtopicname ASC');
        }
		
		// Get subtopics
		$subtopics = $REST->get("subtopics", $data);
		
		// Create subtopicarray needed for selectbox (id as index and name as content)
        $subtopicsarray = array(''=>get_string('choose_subtopic', 'term'));
		
		$check = (array) $subtopics[0];
		if (!array_key_exists("status", $check)) {
		
			foreach ($subtopics as $subtopic) {
				$subtopicsarray[$subtopic->id] = $subtopic->subtopicname;
			}
		}
		
		// Add selectbox to choose a subtopic
		$mform->addElement('select', 'subtopicid', get_string('filter_subtopic', 'term'), $subtopicsarray, array('style'=>'width:400px'));
        
		// Add selectbox to choose a level
        $levels = array(''=>get_string('choose_level', 'term'), '1'=>get_string('level_easy', 'term'), '2'=>get_string('level_normal', 'term'), '3'=>get_string('level_hard', 'term'));
		$mform->addElement('select', 'level', get_string('filter_level', 'term'), $levels, array('style'=>'width:400px'));
		
		// Add textfield to search a specific entry
		$mform->addElement('text', 'term', get_string('term', 'term'), array('style'=>'width:400px'));
		$mform->setDefault('term', '');
        $mform->setType('term', PARAM_TEXT);
		
		
		// Add ids as hidden element for sending it as GET parameter when submit form
        $mform->addElement('hidden', 'id', $id);
        
		// Add submit button for the form
        $mform->addElement('submit', 'submitbutton', 'Filter');
    }
}
