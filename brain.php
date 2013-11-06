<?php
// Required files
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('rest.php');

global $REST;

// Params
$topicid = optional_param('topicid', '', PARAM_INT);
$dbname = optional_param('db', '', PARAM_ALPHANUMEXT);

// Set databasename in REST Client got from course instance config
$REST->setDBname($dbname);

// Get subtopics of a given topic for async AJAX call 
$itemslist = $REST->get('subtopics', array('topicid'=>$topicid));

$results = array();
foreach($itemslist as $item) {
	$results[] = $item->subtopicname;
	$results[] = $item->id;
}

// return JSON encoded subtopics
echo json_encode($results);
?>