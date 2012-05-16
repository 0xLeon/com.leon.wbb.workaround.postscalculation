<?php
// wcf imports
require_once(WCF_DIR.'lib/system/event/EventListener.class.php');
require_once(WCF_DIR.'lib/data/user/rank/UserRank.class.php');

/**
 * Workaround for wrong calculation of user posts and activity points
 * 
 * @author	Stefan Hahn
 * @copyright	2012 Stefan Hahn
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.leon.wbb.workaround.postscalculation
 * @subpackage 	system.event.listener
 * @category 	Burning Board
 */
class UpdateUsersActionUserPostsCalculationWorkaroundEventListener implements EventListener {
	/**
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		// get userIDs
		$userIDs = '';
		$sql = "SELECT		userID
			FROM		wbb".WBB_N."_user
			ORDER BY	userID";
		$result = WCF::getDB()->sendQuery($sql, $eventObj->limit, ($eventObj->limit * $eventObj->loop));
		while ($row = WCF::getDB()->fetchArray($result)) {
			$userIDs .= ','.$row['userID'];
		}
		
		if (empty($userIDs)) {
			return;
		}
		
		// get boardIDs
		$boardIDs = '';
		$sql = "SELECT	boardID
			FROM	wbb".WBB_N."_board
			WHERE	boardType = 0
				AND countUserPosts = 1";
		$result2 = WCF::getDB()->sendQuery($sql);
		while ($row = WCF::getDB()->fetchArray($result2)) {
			$boardIDs .= ','.$row['boardID'];
		}
		
		// update users posts
		$sql = "UPDATE	wbb".WBB_N."_user user
			SET	posts = (
					SELECT		COUNT(*)
					FROM		wbb".WBB_N."_post post
					LEFT JOIN	wbb".WBB_N."_thread thread
					ON		(thread.threadID = post.threadID)
					WHERE		post.userID = user.userID
							AND thread.boardID IN (0".$boardIDs.")
				)
			WHERE	user.userID IN (0".$userIDs.")";
		WCF::getDB()->sendQuery($sql);
		
		// update activity points
		$sql = "SELECT		wbb_user.userID,
					wbb_user.posts,
					user.activityPoints,
					COUNT(thread.threadID) AS threads
			FROM		wbb".WBB_N."_user wbb_user
			LEFT JOIN	wcf".WCF_N."_user user
			ON		(user.userID = wbb_user.userID)
			LEFT JOIN	wbb".WBB_N."_thread thread
			ON		(thread.userID = wbb_user.userID AND thread.boardID IN (0".$boardIDs."))
			WHERE		wbb_user.userID IN (0".$userIDs.")
			GROUP BY	wbb_user.userID";
		$result2 = WCF::getDB()->sendQuery($sql);
		while ($row = WCF::getDB()->fetchArray($result2)) {
			$activityPoints = ($row['threads'] * ACTIVITY_POINTS_PER_THREAD) + (($row['posts'] - $row['threads']) * ACTIVITY_POINTS_PER_POST);
			// update activity points for this package
			$sql = "REPLACE INTO	wcf".WCF_N."_user_activity_point
						(userID, packageID, activityPoints)
				VALUES 		(".$row['userID'].", ".PACKAGE_ID.", ".$activityPoints.")";
			WCF::getDB()->sendQuery($sql);
		}
		
		// update global activity points
		$sql = "UPDATE	wcf".WCF_N."_user user
			SET	user.activityPoints = (
					SELECT	SUM(activityPoints)
					FROM	wcf".WCF_N."_user_activity_point
					WHERE	userID = user.userID
				)
			WHERE	user.userID IN (0".$userIDs.")";
		WCF::getDB()->sendQuery($sql);
		
		// update user rank
		WCF::getDB()->seekResult($result, 0);
		while ($row = WCF::getDB()->fetchArray($result)) {
			UserRank::updateActivityPoints(0, $row['userID']);
		}
	}
}
