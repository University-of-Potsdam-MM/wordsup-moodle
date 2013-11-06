<?php
	// This file to be included, so assume config.php has already been included.
	// Also assume that $user, $course, $currenttab have been set.
	
	// Check setted properties if scriptcall is allowed
    if (empty($currenttab) or empty($term) or empty($course)) {
        print_error('cannotcallscript');
    }
	
	// Get context for checking capabilities
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
	
	// Create tabs and rows
    $tabs = array();
    $row = array();
	
	// Terms Tabs always visible for all roles
    $row[] = new tabobject('termdatabase', $CFG->wwwroot.'/mod/term/view.php?id='.$cm->id.'&amp;mode=single', get_string('manage_terms','term'));
    $row[] = new tabobject('add', $CFG->wwwroot.'/mod/term/addterm.php?id='.$cm->id, get_string('add_term','term'));
	
	// Topics/Subtopics Tabs only visible if permissions set
    if (has_capability('mod/term:viewtopics', $context)) {
		$row[] = new tabobject('addtopic', $CFG->wwwroot.'/mod/term/addtopic.php?id='.$cm->id.'&amp;mode=asearch', get_string('add_topic', 'term'));
		$row[] = new tabobject('managetopics', $CFG->wwwroot.'/mod/term/managetopics.php?id='.$cm->id, get_string('manage_topics','term'));
		$row[] = new tabobject('managesubtopics', $CFG->wwwroot.'/mod/term/managesubtopics.php?id='.$cm->id, get_string('manage_subtopics','term'));
    }
	
	// Resultstatistics Tab only visible if permissions set
	if (has_capability('mod/term:viewresults', $context)) {
		$row[] = new tabobject('resultstatics', $CFG->wwwroot.'/mod/term/resultstatics.php?id='.$cm->id, get_string('result_stats', 'term'));
	}
	
	// Mappingstatistics Tab only visible if permissions set
	if (has_capability('mod/term:viewstatics', $context)) {
		$row[] = new tabobject('statics', $CFG->wwwroot.'/mod/term/statics.php?id='.$cm->id, get_string('mapping_stats', 'term'));
	}
	
	// Only one row of tabs needed
    $tabs[] = $row;
	
	// Print tabs
    print_tabs($tabs, $currenttab, null, null);


