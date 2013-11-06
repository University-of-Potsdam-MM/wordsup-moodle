<?php
// Required files
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('rest.php');

global $REST;

// Params
$topicid = optional_param('topicid', '', PARAM_INT);
$termid = optional_param('termid', '', PARAM_INT);
$dbname = optional_param('db', '', PARAM_ALPHANUMEXT);

// Set databasename in REST Client got from course instance config
$REST->setDBname($dbname);

// Get not used subtopics of a given topic for async AJAX call 
$data = array('topicid'=>$topicid, 'termid'=>$termid);
$itemslist = $REST->get("notusedsubtopics", $data);

$results = array();
foreach($itemslist as $item) {
    $results[] = $item->subtopicname;
}

// return JSON encoded subtopics
echo json_encode($results);
?>