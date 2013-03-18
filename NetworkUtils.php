<?php

require_once('URLConnect.php');

class NetworkUtils {

	/**
	 * Get html content from a url. Timeout in 1min.
	 * If unsucessful, print out a error page.
	 */
	public static function getContentFromUrl($url) {
		if ( $url == NULL || strlen($url) == 0 ) return NULL;

		$urlconnect = new URLConnect($url, 60, FALSE);
		if ( $urlconnect->getHTTPCode() != 200 ) {
			
			RestUtils::sendResponse($urlconnect->getHTTPCode());
			exit;
		}
		
		return $urlconnect->getContent();
	}	
	
}

?>