<?php

defined('MOODLE_INTERNAL') || die;

// Required files
require_once($CFG->libdir.'/formslib.php');
require_once('rest.php');

class term_mappings_form extends moodleform {
    
    function display() {
		
		// To import the global references into this function
        global $CFG, $PAGE;
		
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
		$termid = $this->_customdata['termid'];
		$topicid = $this->_customdata['topicid'];
		
		// Set databasename in REST Client got from course instance config
		$REST->setDBname($this->_customdata['dbname']);
        $dbx = $this->_customdata['dbname'];
		
		// Put the form in a bordered box with header
        $mform->addElement('header', 'General', get_string('mappings', 'term'));
        
		// Get all topics
		$topics = $REST->get('topics', array('order'=>'topicname ASC'));
        
		// Create topicarray needed for selectbox (id as index and name as content)
        $topicsarray = array(''=>get_string('choose_topic', 'term'));
		
		$check = (array) $topics[0];
		if (!array_key_exists("status", $check)) {
			foreach ($topics as $topic) {
				$topicsarray[$topic->id]= $topic->topicname;
			}
		}
        
		// Add selectbox to choose a topic and set it as required. On change call javascript function to reload subtopics with AJAX
        $mform->addElement('select', 'topicid', get_string('topic', 'term'), $topicsarray, array('onchange' => 'topicid_updated(this.value, '.$termid.', \''.$dbx.'\');', 'style'=>'width:400px'));
        $mform->addRule('topicid', get_string('required', 'term'), 'required', null, 'client');
		
		// AJAX
        $js = 
<<<EOS
	<script type="text/javascript">
		function topicid_updated(topicid, termid, dbname) {
			update_subtopics(topicid, termid, dbname);
			
			// Clear textfield
			var topicids = document.getElementById('id_topic');
			if (topicids == '') {
				id_subtopics.disabled = true;
				id_subtopics.value = '';
			} 
			else {
				id_subtopics.disabled = false;
			}
		}
		
		function update_subtopics(topicid, termid, dbname) {
			var sUrl = 'brain2.php?topicid='+topicid+'&termid='+termid+'&db='+dbname;
			var handleSuccess = function(o){
				if (o.responseText !== undefined){
					document.getElementById("id_subtopics").options.length=0;
					var opt = document.createElement("option");
					opt.text = 'Unterthema wählen';
					opt.value = '';
					document.getElementById("id_subtopics").options.add(opt);
					
					var itemlist = YAHOO.lang.JSON.parse(o.responseText);
					for (var i = 0, len = itemlist.length; i < len; ++i) {
						var item = itemlist[i];
						var opt = document.createElement("option");
						opt.text = itemlist[i];
						opt.value = itemlist[i];
						document.getElementById("id_subtopics").options.add(opt);
					}
				}
				
				var subtopics = document.getElementById('id_subtopics');
			}
			var handleFailure = function(o){ }
			var callback = { 
				success: handleSuccess, 
				failure: handleFailure, 
				argument: { } 
			};         
			var request = YAHOO.util.Connect.asyncRequest('GET', sUrl, callback); 
		}
	</script>
EOS;
				
		// Save js code in static form element
        $mform->addElement('static', 'ajax_mappings', '', utf8_encode($js));
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
				$subtopicsarray[$subtopic->subtopicname] = $subtopic->subtopicname;
			}
		}
		
		// Add selectbox to choose a subtopic and set it as required
		$mform->addElement('select', 'subtopics', get_string('subtopic', 'term'), $subtopicsarray, array('style'=>'width:400px'));
        $mform->addRule('subtopics', get_string('required', 'term'), 'required', null, 'client');
		
		// Add ids as hidden element for sending them as GET parameter when submit form
        $mform->addElement('hidden', 'id', $id);
		$mform->addElement('hidden', 'termid', $termid);
        
		// Add submit button for the form
        $mform->addElement('submit', 'submitbutton', get_string('add_mapping', 'term'));
    }
}
