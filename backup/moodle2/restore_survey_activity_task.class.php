<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/term/backup/moodle2/restore_term_stepslib.php'); // Because it exists (must)

/**
 * term restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_term_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // term only has one structure step
        $this->add_step(new restore_term_activity_structure_step('term_structure', 'term.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('term', array('intro'), 'term');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('termVIEWBYID', '/mod/term/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('termINDEX', '/mod/term/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * term logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('term', 'add', 'view.php?id={course_module}', '{term}');
        $rules[] = new restore_log_rule('term', 'update', 'view.php?id={course_module}', '{term}');
        $rules[] = new restore_log_rule('term', 'view', 'view.php?id={course_module}', '{term}');
        $rules[] = new restore_log_rule('term', 'download', 'download.php?id={course_module}&type=[type]&group=[group]', '{term}');
        $rules[] = new restore_log_rule('term', 'view report', 'report.php?id={course_module}', '{term}');
        $rules[] = new restore_log_rule('term', 'submit', 'view.php?id={course_module}', '{term}');
        $rules[] = new restore_log_rule('term', 'view graph', 'view.php?id={course_module}', '{term}');
        $rules[] = new restore_log_rule('term', 'view form', 'view.php?id={course_module}', '{term}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('term', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
