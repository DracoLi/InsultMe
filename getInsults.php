<?php

require_once(dirname(__FILE__) . "/classes/Insults.php");
require_once(dirname(__FILE__) . "/RestUtils.php");

$query = array_key_exists('query', $_GET) ? $_GET['query'] : "Draco";

$results = Insults::getInsults($query);
$jsonResult = json_encode($results);
//RestUtils::getPrintTestArray($results);
RestUtils::sendResponse(200, $jsonResult, null, "application/json");

?>