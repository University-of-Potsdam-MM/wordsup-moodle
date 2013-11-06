<?php

defined('MOODLE_INTERNAL') || die;

// Ask admin while install how large the table rows standard is and should be set

if ($ADMIN->fulltree) {
	$settings->add(new admin_setting_heading('term_method_heading', get_string('config_general', 'term'), get_string('config_desc', 'term')));
	$settings->add(new admin_setting_configtext('term_tablerows', get_string('table_rows', 'term'), get_string('config_rows', 'term'), 10, PARAM_INT));
}
