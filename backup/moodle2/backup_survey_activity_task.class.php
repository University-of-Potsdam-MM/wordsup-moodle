<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/term/backup/moodle2/backup_term_stepslib.php');

/**
 * Provides all the settings and steps to perform one complete backup of the activity
 */
class backup_term_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the term.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_term_activity_structure_step('term_structure', 'term.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of terms
        $search="/(".$base."\/mod\/term\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@termINDEX*$2@$', $content);

        // Link to term view by moduleid
        $search="/(".$base."\/mod\/term\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@termVIEWBYID*$2@$', $content);

        return $content;
    }
}
