<?php

defined('MOODLE_INTERNAL') || die;

// Required files
require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once ($CFG->dirroot.'/mod/term/rest.php');


// Form for activity instance configuration
class mod_term_mod_form extends moodleform_mod {
	
    function definition() {
		
		// To import the global references into this function
		global $CFG, $PAGE, $REST;
		
		// Load YUI Libraries for AJAX
		$PAGE->requires->yui2_lib(array('yahoo', 'dom', 'element', 'event', 'connection', 'json'));
		
		// Create form
		$mform = $this->_form;
		
		// Create Box Header
		$mform->addElement('header', 'general', get_string('general', 'form'));
		
		// Add textbox to input instance name and format string and validate with rules
        $mform->addElement('text', 'name', get_string('termname', 'term'), array('size'=>'64'));
		
		if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } 
		else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        
		$mform->addRule('name', null, 'required', null, 'client');

		// Add textarea to input some additional descriptions
        $this->add_intro_editor(true, get_string('termintro', 'term'));
		
		// Get all databases from the db server
		$dblist = $REST->get('databases', array());
		$dblist = $dblist->database;
		
		// Prepare options for selectbox to choose db
        $dboptions = array(''=>'');
		
		$i = 0;
		$dboptions['new'] = get_string('setnewdb', 'term');
		
		foreach ($dblist as $dbname) {
			$dboptions[$dbname->name] = $dbname->name;
			$i++;
		}
		
		// Add selectbox to choose db with AJAX functions
		$mform->addElement('select', 'dbnames', get_string('choosedb', 'term'), $dboptions, array('style'=>'width:418px', 'onchange' => 'dbname_changed(this.value);'));
		$mform->addRule('dbnames', get_string('required', 'term'), 'required', null, 'client');
		
		// Add hidden textbox, only show it if in selectbox the option "# set new db" is chosen (via AJAX DOM manipulation)
		$mform->addElement('text', 'dbname', null, array('size'=>'64', 'style'=>'display:none'));
		// AJAX
        $js = 
<<<EOS
	<script type="text/javascript">
		function dbname_changed(dbname) {
			var sb = document.getElementById('id_dbnames');
			var ib = document.getElementById('id_dbname');
			if (sb.value == 'new') {
				//ib.disabled = false;
				ib.style.display = 'block';
			}
			else {
				//ib.disabled = true;
				ib.style.display = 'none';
			}
			
		}
	</script>
EOS;
				
        // Add JS code as static form element
		$mform->addElement('static', 'ajax_dbs', '', utf8_encode($js));
        // END AJAX
		
		// Add standard instance config elements and action buttons
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
