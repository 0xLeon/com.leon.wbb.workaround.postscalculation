<?php
// wcf imports
require_once(WCF_DIR.'lib/system/event/EventListener.class.php');
require_once(WCF_DIR.'lib/data/user/rank/UserRank.class.php');

// wbb imports
require_once(WBB_DIR.'lib/data/user/WBBUser.class.php');
require_once(WBB_DIR.'lib/data/post/Post.class.php');

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
class ThreadActionPageUserPostsCalculationWorkaroundEventListener implements EventListener {
	/**
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		if ($eventObj->action == 'enable') {
			if ($eventObj->thread->userID && $eventObj->board->countUserPosts && $eventObj->board->getModeratorPermission('canEnableThread') && !$eventObj->thread->everEnabled) {
				WBBUser::updateUserPosts($eventObj->thread->userID, -1);
				
				if (ACTIVITY_POINTS_PER_THREAD) {
					UserRank::updateActivityPoints((ACTIVITY_POINTS_PER_THREAD * (-1)), $eventObj->thread->userID);
				}
				
				$postIDs = explode(',', ThreadEditor::getAllPostIDs((string)$eventObj->thread->threadID));
				
				foreach ($postIDs as $postID) {
					$post = new Post($postID);
					
					if (($post->postID != $eventObj->thread->firstPostID) && !$post->everEnabled) {
						WBBUser::updateUserPosts($post->userID, -1);
						
						if (ACTIVITY_POINTS_PER_POST) {
							UserRank::updateActivityPoints((ACTIVITY_POINTS_PER_POST * (-1)), $post->userID);
						}
					}
				}
			}
		}
	}
}
