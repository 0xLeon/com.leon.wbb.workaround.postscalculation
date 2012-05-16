<?php
// wcf imports
require_once(WCF_DIR.'lib/system/event/EventListener.class.php');
require_once(WCF_DIR.'lib/data/user/rank/UserRank.class.php');

// wbb imports
require_once(WBB_DIR.'lib/data/user/WBBUser.class.php');

/**
 * Workaround for wrong calculation of user posts and activity points
 * 
 * @author	Stefan Hahn
 * @copyright	2012 Stefan Hahn
 * @license	
 * @package	com.leon.wbb.workaround.postscalculation
 * @subpackage 	system.event.listener
 * @category 	Burning Board
 */
class PostActionPageUserPostsCalculationWorkaroundEventListener implements EventListener {
	/**
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		if ($eventObj->action == 'enable') {
			if ($eventObj->board->getModeratorPermission('canEnableThread') && !$eventObj->post->everEnabled) {
				WBBUser::updateUserPosts($eventObj->post->userID, -1);
				
				if (ACTIVITY_POINTS_PER_POST) {
					UserRank::updateActivityPoints((ACTIVITY_POINTS_PER_POST * (-1)), $eventObj->post->userID);
				}
			}
		}
	}
}
