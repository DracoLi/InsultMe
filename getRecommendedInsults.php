<?php

require_once("classes/Insults.php");
require_once("RestUtils.php");

$amount = array_key_exists('amount', $_GET) ? $_GET['amount'] : 5;

$results = Insults::getReccommendedQueries($amount);
$jsonResult = json_encode($results);
RestUtils::sendResponse(200, $jsonResult, null, "application/json");

?>