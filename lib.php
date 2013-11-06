<?php

 require_once ($CFG->dirroot.'/mod/term/rest.php');
 
/// Standard functions /////////////////////////////////////////////////////////

/**
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $term
 * @return object|null
 */
function term_user_outline($course, $user, $mod, $term) {
    global $DB;
    if ($answer = $DB->get_record('term_answers', array('termid' => $term->id, 'userid' => $user->id))) {
        $result = new stdClass();
        $result->info = "'".format_string(term_get_option_text($term, $answer->optionid))."'";
        $result->time = $answer->timemodified;
        return $result;
    }
    return NULL;
}

/**
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $term
 * @return string|void
 */
function term_user_complete($course, $user, $mod, $term) {
    global $DB;
    if ($answer = $DB->get_record('term_answers', array("termid" => $term->id, "userid" => $user->id))) {
        $result = new stdClass();
        $result->info = "'".format_string(term_get_option_text($term, $answer->optionid))."'";
        $result->time = $answer->timemodified;
        echo get_string("answered", "term").": $result->info. ".get_string("updated", '', userdate($result->time));
    } else {
        print_string("notanswered", "term");
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $term
 * @return int
 */
function term_add_instance($term) {
    global $DB, $REST;

    $term->timemodified = time();

	if ($term->dbnames == 'new')
		$REST->meta($term->dbname);
	else
		$term->dbname = $term->dbnames;
	
	
    //insert answers
    $term->id = $DB->insert_record("term", $term);

    return $term->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $term
 * @return bool
 */
function term_update_instance($term) {
    global $DB, $REST;

    $term->id = $term->instance;
    $term->timemodified = time();

	if ($term->dbnames == 'new')
		$REST->meta($term->dbname);
	else
		$term->dbname = $term->dbnames;
	

    if (empty($term->timerestrict)) {
        $term->timeopen = 0;
        $term->timeclose = 0;
    }
	/*
    //update, delete or insert answers
    foreach ($term->option as $key => $value) {
        $value = trim($value);
        $option = new stdClass();
        $option->text = $value;
        $option->termid = $term->id;
        if (isset($term->limit[$key])) {
            $option->maxanswers = $term->limit[$key];
        }
        $option->timemodified = time();
        if (isset($term->optionid[$key]) && !empty($term->optionid[$key])){//existing term record
            $option->id=$term->optionid[$key];
            if (isset($value) && $value <> '') {
                $DB->update_record("term_options", $option);
            } else { //empty old option - needs to be deleted.
                $DB->delete_records("term_options", array("id"=>$option->id));
            }
        } else {
            if (isset($value) && $value <> '') {
                $DB->insert_record("term_options", $option);
            }
        }
    }*/

    return $DB->update_record('term', $term);

}

/**
 * @global object
 * @param object $term
 * @param object $user
 * @param object $coursemodule
 * @param array $allresponses
 * @return array
 */
function term_prepare_options($term, $user, $coursemodule, $allresponses) {
    global $DB;

    $cdisplay = array('options'=>array());

    $cdisplay['limitanswers'] = true;
    $context = get_context_instance(CONTEXT_MODULE, $coursemodule->id);

    foreach ($term->option as $optionid => $text) {
        if (isset($text)) { //make sure there are no dud entries in the db with blank text values.
            $option = new stdClass;
            $option->attributes = new stdClass;
            $option->attributes->value = $optionid;
            $option->text = $text;
            $option->maxanswers = $term->maxanswers[$optionid];
            $option->displaylayout = $term->display;

            if (isset($allresponses[$optionid])) {
                $option->countanswers = count($allresponses[$optionid]);
            } else {
                $option->countanswers = 0;
            }
            if ($DB->record_exists('term_answers', array('termid' => $term->id, 'userid' => $user->id, 'optionid' => $optionid))) {
                $option->attributes->checked = true;
            }
            if ( $term->limitanswers && ($option->countanswers >= $option->maxanswers) && empty($option->attributes->checked)) {
                $option->attributes->disabled = true;
            }
            $cdisplay['options'][] = $option;
        }
    }

    $cdisplay['hascapability'] = is_enrolled($context, NULL, 'mod/term:choose'); //only enrolled users are allowed to make a term

    if ($term->allowupdate && $DB->record_exists('term_answers', array('termid'=> $term->id, 'userid'=> $user->id))) {
        $cdisplay['allowupdate'] = true;
    }

    return $cdisplay;
}

/**
 * @global object
 * @param int $formanswer
 * @param object $term
 * @param int $userid
 * @param object $course Course object
 * @param object $cm
 */
function term_user_submit_response($formanswer, $term, $userid, $course, $cm) {
    global $DB, $CFG;
    require_once($CFG->libdir.'/completionlib.php');

    $current = $DB->get_record('term_answers', array('termid' => $term->id, 'userid' => $userid));
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $countanswers=0;
    if($term->limitanswers) {
        // Find out whether groups are being used and enabled
        if (groups_get_activity_groupmode($cm) > 0) {
            $currentgroup = groups_get_activity_group($cm);
        } else {
            $currentgroup = 0;
        }
        if($currentgroup) {
            // If groups are being used, retrieve responses only for users in
            // current group
            global $CFG;
            $answers = $DB->get_records_sql("
SELECT
    ca.*
FROM
    {term_answers} ca
    INNER JOIN {groups_members} gm ON ca.userid=gm.userid
WHERE
    optionid=?
    AND gm.groupid=?", array($formanswer, $currentgroup));
        } else {
            // Groups are not used, retrieve all answers for this option ID
            $answers = $DB->get_records("term_answers", array("optionid" => $formanswer));
        }

        if ($answers) {
            foreach ($answers as $a) { //only return enrolled users.
                if (is_enrolled($context, $a->userid, 'mod/term:choose')) {
                    $countanswers++;
                }
            }
        }
        $maxans = $term->maxanswers[$formanswer];
    }

    if (!($term->limitanswers && ($countanswers >= $maxans) )) {
        if ($current) {

            $newanswer = $current;
            $newanswer->optionid = $formanswer;
            $newanswer->timemodified = time();
            $DB->update_record("term_answers", $newanswer);
            add_to_log($course->id, "term", "choose again", "view.php?id=$cm->id", $term->id, $cm->id);
        } else {
            $newanswer = new stdClass();
            $newanswer->termid = $term->id;
            $newanswer->userid = $userid;
            $newanswer->optionid = $formanswer;
            $newanswer->timemodified = time();
            $DB->insert_record("term_answers", $newanswer);

            // Update completion state
            $completion = new completion_info($course);
            if ($completion->is_enabled($cm) && $term->completionsubmit) {
                $completion->update_state($cm, COMPLETION_COMPLETE);
            }
            add_to_log($course->id, "term", "choose", "view.php?id=$cm->id", $term->id, $cm->id);
        }
    } else {
        if (!($current->optionid==$formanswer)) { //check to see if current term already selected - if not display error
            print_error('termfull', 'term');
        }
    }
}

/**
 * @param array $user
 * @param object $cm
 * @return void Output is echo'd
 */
function term_show_reportlink($user, $cm) {
    $responsecount =0;
    foreach($user as $optionid => $userlist) {
        if ($optionid) {
            $responsecount += count($userlist);
        }
    }

    echo '<div class="reportlink">';
    echo "<a href=\"report.php?id=$cm->id\">".get_string("viewallresponses", "term", $responsecount)."</a>";
    echo '</div>';
}

/**
 * @global object
 * @param object $term
 * @param object $course
 * @param object $coursemodule
 * @param array $allresponses

 *  * @param bool $allresponses
 * @return object
 */
function prepare_term_show_results($term, $course, $cm, $allresponses, $forcepublish=false) {
    global $CFG, $term_COLUMN_HEIGHT, $FULLSCRIPT, $PAGE, $OUTPUT, $DB;

    $display = clone($term);
    $display->coursemoduleid = $cm->id;
    $display->courseid = $course->id;

    //overwrite options value;
    $display->options = array();
    $totaluser = 0;
    foreach ($term->option as $optionid => $optiontext) {
        $display->options[$optionid] = new stdClass;
        $display->options[$optionid]->text = $optiontext;
        $display->options[$optionid]->maxanswer = $term->maxanswers[$optionid];

        if (array_key_exists($optionid, $allresponses)) {
            $display->options[$optionid]->user = $allresponses[$optionid];
            $totaluser += count($allresponses[$optionid]);
        }
    }
    unset($display->option);
    unset($display->maxanswers);

    $display->numberofuser = $totaluser;
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $display->viewresponsecapability = has_capability('mod/term:viewterms', $context);
    $display->deleterepsonsecapability = has_capability('mod/term:deleteresponses',$context);
    $display->fullnamecapability = has_capability('moodle/site:viewfullnames', $context);

    if (empty($allresponses)) {
        echo $OUTPUT->heading(get_string("nousersyet"));
        return false;
    }


    $totalresponsecount = 0;
    foreach ($allresponses as $optionid => $userlist) {
        if ($term->showunanswered || $optionid) {
            $totalresponsecount += count($userlist);
        }
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $hascapfullnames = has_capability('moodle/site:viewfullnames', $context);

    $viewresponses = has_capability('mod/term:viewterms', $context);
    switch ($forcepublish) {
        case term_PUBLISH_NAMES:
            echo '<div id="tablecontainer">';
            if ($viewresponses) {
                echo '<form id="attemptsform" method="post" action="'.$FULLSCRIPT.'" onsubmit="var menu = document.getElementById(\'menuaction\'); return (menu.options[menu.selectedIndex].value == \'delete\' ? \''.addslashes_js(get_string('deleteattemptcheck','quiz')).'\' : true);">';
                echo '<div>';
                echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
                echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
                echo '<input type="hidden" name="mode" value="overview" />';
            }

            echo "<table cellpadding=\"5\" cellspacing=\"10\" class=\"results names\">";
            echo "<tr>";

            $columncount = array(); // number of votes in each column
            if ($term->showunanswered) {
                $columncount[0] = 0;
                echo "<th class=\"col0 header\" scope=\"col\">";
                print_string('notanswered', 'term');
                echo "</th>";
            }
            $count = 1;
            foreach ($term->option as $optionid => $optiontext) {
                $columncount[$optionid] = 0; // init counters
                echo "<th class=\"col$count header\" scope=\"col\">";
                echo format_string($optiontext);
                echo "</th>";
                $count++;
            }
            echo "</tr><tr>";

            if ($term->showunanswered) {
                echo "<td class=\"col$count data\" >";
                // added empty row so that when the next iteration is empty,
                // we do not get <table></table> error from w3c validator
                // MDL-7861
                echo "<table class=\"termresponse\"><tr><td></td></tr>";
                if (!empty($allresponses[0])) {
                    foreach ($allresponses[0] as $user) {
                        echo "<tr>";
                        echo "<td class=\"picture\">";
                        echo $OUTPUT->user_picture($user, array('courseid'=>$course->id));
                        echo "</td><td class=\"fullname\">";
                        echo "<a href=\"$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$course->id\">";
                        echo fullname($user, $hascapfullnames);
                        echo "</a>";
                        echo "</td></tr>";
                    }
                }
                echo "</table></td>";
            }
            $count = 1;
            foreach ($term->option as $optionid => $optiontext) {
                    echo '<td class="col'.$count.' data" >';

                    // added empty row so that when the next iteration is empty,
                    // we do not get <table></table> error from w3c validator
                    // MDL-7861
                    echo '<table class="termresponse"><tr><td></td></tr>';
                    if (isset($allresponses[$optionid])) {
                        foreach ($allresponses[$optionid] as $user) {
                            $columncount[$optionid] += 1;
                            echo '<tr><td class="attemptcell">';
                            if ($viewresponses and has_capability('mod/term:deleteresponses',$context)) {
                                echo '<input type="checkbox" name="attemptid[]" value="'. $user->id. '" />';
                            }
                            echo '</td><td class="picture">';
                            echo $OUTPUT->user_picture($user, array('courseid'=>$course->id));
                            echo '</td><td class="fullname">';
                            echo "<a href=\"$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$course->id\">";
                            echo fullname($user, $hascapfullnames);
                            echo '</a>';
                            echo '</td></tr>';
                       }
                    }
                    $count++;
                    echo '</table></td>';
            }
            echo "</tr><tr>";
            $count = 1;

            if ($term->showunanswered) {
                echo "<td></td>";
            }

            foreach ($term->option as $optionid => $optiontext) {
                echo "<td align=\"center\" class=\"col$count count\">";
                if ($term->limitanswers) {
                    echo get_string("taken", "term").":";
                    echo $columncount[$optionid];
                    echo "<br/>";
                    echo get_string("limit", "term").":";
                    echo $term->maxanswers[$optionid];
                } else {
                    if (isset($columncount[$optionid])) {
                        echo $columncount[$optionid];
                    }
                }
                echo "</td>";
                $count++;
            }
            echo "</tr>";

            /// Print "Select all" etc.
            if ($viewresponses and has_capability('mod/term:deleteresponses',$context)) {
                echo '<tr><td></td><td>';
                echo '<a href="javascript:select_all_in(\'DIV\',null,\'tablecontainer\');">'.get_string('selectall').'</a> / ';
                echo '<a href="javascript:deselect_all_in(\'DIV\',null,\'tablecontainer\');">'.get_string('deselectall').'</a> ';
                echo '&nbsp;&nbsp;';
                echo html_writer::label(get_string('withselected', 'term'), 'menuaction');
                echo html_writer::select(array('delete' => get_string('delete')), 'action', '', array(''=>get_string('withselectedusers')), array('id'=>'menuaction'));
                $PAGE->requires->js_init_call('M.util.init_select_autosubmit', array('attemptsform', 'menuaction', ''));
                echo '<noscript id="noscriptmenuaction" style="display:inline">';
                echo '<div>';
                echo '<input type="submit" value="'.get_string('go').'" /></div></noscript>';
                echo '</td><td></td></tr>';
            }

            echo "</table></div>";
            if ($viewresponses) {
                echo "</form></div>";
            }
            break;
    }
    return $display;
}

/**
 * @global object
 * @param array $attemptids
 * @param object $term term main table row
 * @param object $cm Course-module object
 * @param object $course Course object
 * @return bool
 */
function term_delete_responses($attemptids, $term, $cm, $course) {
    global $DB, $CFG;
    require_once($CFG->libdir.'/completionlib.php');

    if(!is_array($attemptids) || empty($attemptids)) {
        return false;
    }

    foreach($attemptids as $num => $attemptid) {
        if(empty($attemptid)) {
            unset($attemptids[$num]);
        }
    }

    $completion = new completion_info($course);
    foreach($attemptids as $attemptid) {
        if ($todelete = $DB->get_record('term_answers', array('termid' => $term->id, 'userid' => $attemptid))) {
            $DB->delete_records('term_answers', array('termid' => $term->id, 'userid' => $attemptid));
            // Update completion state
            if ($completion->is_enabled($cm) && $term->completionsubmit) {
                $completion->update_state($cm, COMPLETION_INCOMPLETE, $attemptid);
            }
        }
    }
    return true;
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function term_delete_instance($id) {
    global $DB;

    if (! $term = $DB->get_record("term", array("id"=>"$id"))) {
        return false;
    }

    $result = true;
/*
    if (! $DB->delete_records("term_answers", array("termid"=>"$term->id"))) {
        $result = false;
    }

    if (! $DB->delete_records("term_options", array("termid"=>"$term->id"))) {
        $result = false;
    }
*/
    if (! $DB->delete_records("term", array("id"=>"$term->id"))) {
        $result = false;
    }

    return $result;
}

/**
 * Returns text string which is the answer that matches the id
 *
 * @global object
 * @param object $term
 * @param int $id
 * @return string
 */
function term_get_option_text($term, $id) {
    global $DB;

    if ($result = $DB->get_record("term_options", array("id" => $id))) {
        return $result->text;
    } else {
        return get_string("notanswered", "term");
    }
}

/**
 * Gets a full term record
 *
 * @global object
 * @param int $termid
 * @return object|bool The term or false
 */
function term_get_term($termid) {
    global $DB;

    if ($term = $DB->get_record("term", array("id" => $termid))) {
        if ($options = $DB->get_records("term_options", array("termid" => $termid), "id")) {
            foreach ($options as $option) {
                $term->option[$option->id] = $option->text;
                $term->maxanswers[$option->id] = $option->maxanswers;
            }
            return $term;
        }
    }
    return false;
}

/**
 * @return array
 */
function term_get_view_actions() {
    return array('view','view all','report');
}

/**
 * @return array
 */
function term_get_post_actions() {
    return array('choose','choose again');
}


/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the term.
 *
 * @param object $mform form passed by reference
 */
function term_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'termheader', get_string('modulenameplural', 'term'));
    $mform->addElement('advcheckbox', 'reset_term', get_string('removeresponses','term'));
}

/**
 * Course reset form defaults.
 *
 * @return array
 */
function term_reset_course_form_defaults($course) {
    return array('reset_term'=>1);
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * term responses for course $data->courseid.
 *
 * @global object
 * @global object
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function term_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('modulenameplural', 'term');
    $status = array();

    if (!empty($data->reset_term)) {
        $termssql = "SELECT ch.id
                       FROM {term} ch
                       WHERE ch.course=?";

        $DB->delete_records_select('term_answers', "termid IN ($termssql)", array($data->courseid));
        $status[] = array('component'=>$componentstr, 'item'=>get_string('removeresponses', 'term'), 'error'=>false);
    }

    /// updating dates - shift may be negative too
    if ($data->timeshift) {
        shift_course_mod_dates('term', array('timeopen', 'timeclose'), $data->timeshift, $data->courseid);
        $status[] = array('component'=>$componentstr, 'item'=>get_string('datechanged'), 'error'=>false);
    }

    return $status;
}

/**
 * @global object
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @param object $term
 * @param object $cm
 * @param int $groupmode
 * @return array
 */
function term_get_response_data($term, $cm, $groupmode) {
    global $CFG, $USER, $DB;

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
/*
/// Get the current group
    if ($groupmode > 0) {
        $currentgroup = groups_get_activity_group($cm);
    } else {
        $currentgroup = 0;
    }

/// Initialise the returned array, which is a matrix:  $allresponses[responseid][userid] = responseobject
    $allresponses = array();

/// First get all the users who have access here
/// To start with we assume they are all "unanswered" then move them later
    $allresponses[0] = get_enrolled_users($context, 'mod/term:choose', $currentgroup, user_picture::fields('u', array('idnumber')), 'u.lastname ASC,u.firstname ASC');

/// Get all the recorded responses for this term
    $rawresponses = $DB->get_records('term_answers', array('termid' => $term->id));

/// Use the responses to move users into the correct column

    if ($rawresponses) {
        foreach ($rawresponses as $response) {
            if (isset($allresponses[0][$response->userid])) {   // This person is enrolled and in correct group
                $allresponses[0][$response->userid]->timemodified = $response->timemodified;
                $allresponses[$response->optionid][$response->userid] = clone($allresponses[0][$response->userid]);
                unset($allresponses[0][$response->userid]);   // Remove from unanswered column
            }
        }
    }
    return $allresponses;
 * 
 */
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function term_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function term_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $termnode The node to add module settings to
 */


/**
 * Obtains the automatic completion state for this term based on any conditions
 * in forum settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function term_get_completion_state($course, $cm, $userid, $type) {
    global $CFG,$DB;

    // Get term details
    $term = $DB->get_record('term', array('id'=>$cm->instance), '*',
            MUST_EXIST);

    // If completion option is enabled, evaluate it and return true/false
    if($term->completionsubmit) {
        return $DB->record_exists('term_answers', array(
                'termid'=>$term->id, 'userid'=>$userid));
    } else {
        // Completion option is not enabled so just return $type
        return $type;
    }
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function term_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-term-*'=>get_string('page-mod-term-x', 'term'));
    return $module_pagetype;
}
