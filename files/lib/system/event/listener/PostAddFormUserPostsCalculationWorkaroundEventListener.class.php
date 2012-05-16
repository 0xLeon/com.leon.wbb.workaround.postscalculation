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
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.leon.wbb.workaround.postscalculation
 * @subpackage 	system.event.listener
 * @category 	Burning Board
 */
class PostAddFormUserPostsCalculationWorkaroundEventListener implements EventListener {
	/**
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		if (WCF::getUser()->userID && $eventObj->board->countUserPosts && $eventObj->disablePost) {
			WBBUser::updateUserPosts(WCF::getUser()->userID, 1);
			
			if (ACTIVITY_POINTS_PER_POST) {
				UserRank::updateActivityPoints(ACTIVITY_POINTS_PER_POST);
			}
		}
	}
}
