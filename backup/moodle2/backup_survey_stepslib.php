<?php

/**
 * Define all the backup steps that will be used by the backup_term_activity_task
 */

/**
 * Define the complete term structure for backup, with file and id annotations
 */
class backup_term_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $term = new backup_nested_element('term', array('id'), array(
            'name', 'intro', 'introformat', 'template',
            'questions', 'days', 'timecreated', 'timemodified'));

        $answers = new backup_nested_element('answers');

        $answer = new backup_nested_element('answer', array('id'), array(
            'userid', 'question', 'time', 'answer1',
            'answer2'));

        $analysis = new backup_nested_element('analysis');

        $analys = new backup_nested_element('analys', array('id'), array(
            'userid', 'notes'));

        // Build the tree
        $term->add_child($answers);
        $answers->add_child($answer);

        $term->add_child($analysis);
        $analysis->add_child($analys);

        // Define sources
        $term->set_source_table('term', array('id' => backup::VAR_ACTIVITYID));

        $answer->set_source_table('term_answers', array('term' => backup::VAR_PARENTID));

        $analys->set_source_table('term_analysis', array('term' => backup::VAR_PARENTID));

        // Define id annotations
        $answer->annotate_ids('user', 'userid');
        $analys->annotate_ids('user', 'userid');

        // Define file annotations
        $term->annotate_files('mod_term', 'intro', null); // This file area hasn't itemid

        // Return the root element (term), wrapped into standard activity structure
        return $this->prepare_activity_structure($term);
    }
}
