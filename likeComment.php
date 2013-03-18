<?php

require_once(dirname(__FILE__) . "/classes/Insults.php");
require_once(dirname(__FILE__) . "/RestUtils.php");

$youtubeID = array_key_exists('youtubeID', $_GET) ? $_GET['youtubeID'] : "Do not exist";
$dislike = array_key_exists('dislike', $_GET) ? true : false;

Insults::likeInsult($youtubeID, $dislike);

$body = RestUtils::getHTMLBody("<h3>" . Insults::getLikes($youtubeID) . " likes</h3>");
RestUtils::sendResponse(200, $body);

?>