<?php
	
/**
 * General utility functions that is used in web apps
 *
 * @author Draco Li
 * @version 1.0
 */
class GeneralUtils {
	
	/**
	 * Combines user supplied params with the default params
	 * @param $defaultParams 	The default params array
	 * @param $userParams 		The user params array
	 * @returns 				A combined params aray
	 */ 
	public static function getDefaults($defaultParams, $userParams)
	{
		$combinedArray = array();
		foreach ( $defaultParams as $key=>$value ) {
			if ( !array_key_exists($key, $userParams) || is_null($userParams[$key]) ) {
				// Default since user didnt supply
				$combinedArray[$key] = $value;
			}else {
				// Use user supplied param
				$combinedArray[$key] = $userParams[$key];
			}
		}
		return $combinedArray;
	}

	/**
	 * Parses a string into an int.
	 * This function removes all non-letters and commas.
	 */
	public static function parseInt($string) {
		
		// Remove all commas and dots as it maybe a number separator
		$string = preg_replace('/,/', '', $string);
		// Get the first number
		if(preg_match('/\d+/', $string, $array)) {
			return (int)$array[0];
		} else {
			return FALSE;
		}
	}
	
	/**
	 * Maps a key to mapper and get its value
	 */	
	public static function mapKeyToValue($mapper, $key)
	{
		if ( isset($mapper[$key]) ) {
			return $mapper[$key];
		}else {
			return $key;
		}
	}
	
	public static function printArray($array)
	{
		echo "<pre>";
		print_r($array);
		echo "</pre>";	
	}
	
	public static function getBaseURL($url)
	{
		$offset = strpos($url, 'http://');
		if ( $offset !== FALSE ) {
			$offset += strlen('http://');
		}else {
			$offset = 0;	
		}
		$endPos = strpos($url, '/', $offset);
		$baseURL = substr($url, 0, $endPos);
		return $baseURL;
	}
	
	/**
	 * Turns at date of formate m/d/y to stamp
	 */
	public static function naDateStringToStamp($dateString)
	{
		preg_match('/(\d+)\/(\d+)\/(\d+)/', $dateString, $matches);
		$adjustedString = $matches[3] . '-' . $matches[1] . '-' . $matches[2];
		$timeStamp = (int)strtotime($adjustedString);
		return $timeStamp;
	}
	
	/**
	 * Returns the DATETIME of a timestamp. Used to store in sql database
	 */
	public static function timeStampToMYSQLTime($timestamp) {
		return date('Y-m-d H:i:s', $timestamp);
	}
}
?>