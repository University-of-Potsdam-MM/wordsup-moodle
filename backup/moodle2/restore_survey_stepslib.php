<?php

/**
 * Define all the restore steps that will be used by the restore_term_activity_task
 */

/**
 * Structure step to restore one term activity
 */
class restore_term_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('term', '/activity/term');
        if ($userinfo) {
            $paths[] = new restore_path_element('term_answer', '/activity/term/answers/answer');
            $paths[] = new restore_path_element('term_analys', '/activity/term/analysis/analys');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_term($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        // insert the term record
        $newitemid = $DB->insert_record('term', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_term_analys($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->term = $this->get_new_parentid('term');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('term_analysis', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }

    protected function process_term_answer($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->term = $this->get_new_parentid('term');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->time = $this->apply_date_offset($data->time);

        $newitemid = $DB->insert_record('term_answers', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }

    protected function after_execute() {
        // Add term related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_term', 'intro', null);
    }
}
