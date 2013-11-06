<?php

defined('MOODLE_INTERNAL') || die();

// If update of capabilities, internal databases or icons is needed, increase version no. +1

$module->version   = 2013043005; // The current module version (Date: YYYYMMDDXX)
$module->requires  = 2012061700; // Requires this Moodle version
$module->component = 'mod_term'; // Full name of the plugin (used for diagnostics)
$module->cron      = 0;
