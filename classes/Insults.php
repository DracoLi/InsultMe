<?php

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(realpath(dirname(__FILE__)) . '/../../library'),
    get_include_path(),
)));

require_once('Zend/Loader.php');
require_once(dirname(__FILE__) . '/DatabaseManager.php');
require_once(dirname(__FILE__) . '/GeneralUtils.php');
Zend_Loader::loadClass('Zend_Gdata_YouTube');

class Insults {
	
	/**
	 * Returns a lists of youtube insults based on query
	 * @param String $query The query for youtube
	 * @returns json The results in json
	 */
	public static function getInsults($queryString)
	{
		// So everytime this method is called, we try to get most recent insults
		// This will reduce response time but make sure insults are new
		$results = Insults::getInsultsFromYoutube($queryString);
		Insults::saveInsultsToDatabase($results, $queryString);
		
		// Return all insults with this query in our database - user can specific count
		return Insults::getInsultsFromDatabase($queryString);
	}
	
	/**
	 * Like a insult or remove the like of the insult
	 */
	public static function likeInsult($youtubeID, $dislike = false)
	{
		// Get the insult from our database
		$dbm = new DatabaseManager();
		$youtubeID = $dbm->sanitizeData($youtubeID);
		$dbm->executeQuery("SELECT likes FROM comments WHERE youtubeID='" . $youtubeID . "'");
		$result = $dbm->getAllRows(MYSQLI_ASSOC);
		
		// How can we incease/decrease a like of a comment that does not exist?
		if ($result == NULL || count($result) == 0) {
			return;
		}else {
			$result = $result[0];
		}
		
		$likesCount = $dislike ? $result["likes"] - 1 : $result["likes"] + 1;
		
		// Cannot have negative likes!
		if ($likesCount < 0) {
			return;
		}
		
		$dbm->executeQuery("UPDATE comments SET likes=" . (int)$likesCount . " WHERE youtubeID='" . $youtubeID . "'");
	}
	
	public static function getLikes($youtubeID)
	{
		// Get # of likes for a comment
		$dbm = new DatabaseManager();
		$youtubeID = $dbm->sanitizeData($youtubeID);
		$dbm->executeQuery("SELECT likes FROM comments WHERE youtubeID='" . $youtubeID . "'");
		$result = $dbm->getAllRows(MYSQLI_ASSOC);
		
		if ($result == NULL || count($result) == 0) {
			return;
		}
		
		return $result[0]["likes"];
	}
	
	/**
	 * Save intsults to database, check for duplicates
	 */
	private static function saveInsultsToDatabase($insults, $queryString)
	{
		// Get our most recent insults for this query to check if there's duplicate
		$dbm = new DatabaseManager();
		$dbm->executeQuery("SELECT youtubeID FROM comments as A, queries as B WHERE A.queryID=B.id AND B.query='" . $queryString . "' ORDER BY createdDate DESC");
		$oldResults = $dbm->getAllRows(MYSQLI_NUM);
		$oldIDs = array();
		foreach ($oldResults as $oneResult) {
			$oldIDs[] = $oneResult[0];
		}
		
		// We got the insults, now insert unique ones into database
		for ( $i = 0; $i < count($insults); $i++ ) {
			// Insert our assoc array into the database only if its not in the database
			if ( !in_array($insults[$i]['youtubeID'], $oldIDs) ) {
				$dbm->insertRecords('comments', $insults[$i]);
				//echo "inserted!</br>";
			}else {
				//echo "duplicate!</br>";
			}
		}
	}

	/**
	 * Retrieves some insults from our database
	 */
	private static function getInsultsFromDatabase($query, $start = 0, $count = 30)
	{
		$end = $count + $start;
		$dbm = new DatabaseManager();
		$dbm->executeQuery("SELECT A.id, A.youtubeID, A.comment, A.author, A.createdDate, A.likes FROM comments as A, queries as B WHERE A.queryId=B.id AND B.query='" . $query . "' ORDER BY likes, createdDate DESC LIMIT 0, " . $end);
		return $dbm->getAllRows(MYSQLI_ASSOC);;
	}
	
	/**
	 * Connect to youtube to get insults for this queryString
	 */
	private static function getInsultsFromYoutube($queryString)
	{
		// Get insults for this query on youtube
		$yt = new Zend_Gdata_YouTube();
		$yt->setMajorProtocolVersion(2);
		$query = $yt->newVideoQuery();
		$query->setOrderBy("relevance");
		$query->setSafeSearch("none");
		
		// Get 50 relavant videos
		$query->setQuery($queryString);
		$query->setMaxResults(40);
		$videoFeed = $yt->getVideoFeed($query->getQueryUrl(2));
		
		// Get the query data of this query string. ALso increase count by one
		$queryData = Insults::getAndUpdateQueryFromDatabase($queryString);
		
		$results = array();
		foreach ($videoFeed as $videoEntry)
		{
			// Get 50 most recent comments
			$uri = Zend_Gdata_YouTube::VIDEO_URI . "/" . $videoEntry->getVideoId() . "/comments?max-results=50";
			$commentFeed = $yt->getVideoCommentFeed(null, $uri);
			
			// Find the insults in the comments
			Insults::findInsultsInComments($commentFeed, $results, $queryData["id"], $queryString);
		}
		return $results;
	}
	
	/**
	 * Find the insults in the comments, then return the adjusted comments
	 */
	private static function findInsultsInComments($commentFeed, &$results, $queryID, $queryString)
	{
		$pattern = '/^(\s*[0-9]+\s*(people|youtuber|nincompoop|guy|person|disliker|persona)\S*)/';
		foreach ($commentFeed as $commentEntry) {
			$comment = $commentEntry->content->text;
			$match = preg_match($pattern, $comment, $matches);
			if ($match == 1) {
				$newEntry = array();
				$newEntry["comment"] = Insults::adjustComment($comment, $matches[1], $queryString);
				$newEntry["author"] = $commentEntry->author[0]->name->text;
				$newEntry["createdDate"] = GeneralUtils::timeStampToMYSQLTime(strtotime($commentEntry->updated->text));
				$newEntry["youtubeID"] = $commentEntry->id->text;
				$newEntry["queryID"] = (int)$queryID;
				$results[] = $newEntry;
			}
		}
	}
	
	private static function getAndUpdateQueryFromDatabase($queryString)
	{
		$dbm = new DatabaseManager();
		$dbm->executeQuery("SELECT id, count FROM queries WHERE query='" . $queryString . "'");
		$result = $dbm->getAllRows(MYSQLI_ASSOC);
		
		// If this query does not exist, we add it
		if ($result == NULL || count($result) == 0) {
			$newQuery = array("query" => $queryString);
			$dbm->insertRecords('queries', $newQuery);
			$result = array("id" => $dbm->getInsertID(), "count" => 0);
		}else {
			$result = $result[0];
		}
		
		// Update query count and date		
		$result['count'] = $result['count'] + 1;
		$time = GeneralUtils::timeStampToMYSQLTime(time());
		$dbm->executeQuery("UPDATE queries SET count=" . $result['count'] . ", lastQuery='" . 
												$time . "' WHERE query='" . $queryString . "'");
		return $result;
	}
	
	/**
	 * Returns an array of popular queries from database
	 */
	public static function getPopularQueries($count)
	{
		// For now we just set some defaults
		$popularArray = array();
		
		$popularArray[] = "orange";
		$popularArray[] = "meetballs";
		$popularArray[] = "michael jackson";
		$popularArray[] = "britney spears";
		$popularArray[] = "WoW";
		$popularArray[] = "justin bieber";
		
		return $popularArray;
	}
	
	/**
	 * Returns an array of recent queries from database
	 */
	public static function getReccommendedQueries($count)
	{
		// For now we just set some defaults
		$recommendedArray = array();
		
		$recommendedArray[] = "bill gates";
		$recommendedArray[] = "steve jobs";
		$recommendedArray[] = "boobs";
		$recommendedArray[] = "gizmodo";
		$recommendedArray[] = "kindle fire";
		
		return $recommendedArray;
	}
	
	/**
	 * Adjust the comment so it makes sense
	 */
	private static function adjustComment($comment, $match, $queryString)
	{
		$adjustedComment = $comment;
		
		// Replace number and person with You
		$adjustedComment = preg_replace("/" . $match . "/", "You", $adjustedComment);
		
		// Replace any mention of they with you
		$adjustedComment = preg_replace("/\b(they)\b/i", "you", $adjustedComment);
		
		// Replace their're or their or they're with your
		$adjustedComment = preg_replace("/\b(their|their're|they're)\b/i", "your", $adjustedComment);
		
		// Change the action "clicked" to entered
		$adjustedComment = preg_replace("/\b(clicked)\b/i", "entered", $adjustedComment);
		
		// Change mentions of "this video" to the matched query
		$adjustedComment = preg_replace("/this video/i", $queryString, $adjustedComment);
		
		return $adjustedComment;
	}
	 
	public static function printVideoEntry($videoEntry) 
	{
		// the videoEntry object contains many helper functions
		// that access the underlying mediaGroup object
		echo 'Video: ' . $videoEntry->getVideoTitle() . "\n";
		echo 'Video ID: ' . $videoEntry->getVideoId() . "\n";
		echo 'Updated: ' . $videoEntry->getUpdated() . "\n";
		echo 'Description: ' . $videoEntry->getVideoDescription() . "\n";
		echo 'Category: ' . $videoEntry->getVideoCategory() . "\n";
		echo 'Tags: ' . implode(", ", $videoEntry->getVideoTags()) . "\n";
		echo 'Watch page: ' . $videoEntry->getVideoWatchPageUrl() . "\n";
		echo 'Flash Player Url: ' . $videoEntry->getFlashPlayerUrl() . "\n";
		echo 'Duration: ' . $videoEntry->getVideoDuration() . "\n";
		echo 'View count: ' . $videoEntry->getVideoViewCount() . "\n";
		echo 'Rating: ' . $videoEntry->getVideoRatingInfo() . "\n";
		echo 'Geo Location: ' . $videoEntry->getVideoGeoLocation() . "\n";
		echo 'Recorded on: ' . $videoEntry->getVideoRecorded() . "\n";
		
		// see the paragraph above this function for more information on the 
		// 'mediaGroup' object. in the following code, we use the mediaGroup
		// object directly to retrieve its 'Mobile RSTP link' child
		foreach ($videoEntry->mediaGroup->content as $content) {
			if ($content->type === "video/3gpp") {
				echo 'Mobile RTSP link: ' . $content->url . "\n";
			}
		}
		
		echo "Thumbnails:\n";
		$videoThumbnails = $videoEntry->getVideoThumbnails();
	
		foreach($videoThumbnails as $videoThumbnail) {
			echo $videoThumbnail['time'] . ' - ' . $videoThumbnail['url'];
			echo ' height=' . $videoThumbnail['height'];
			echo ' width=' . $videoThumbnail['width'] . "\n";
		}
	}
}

?>