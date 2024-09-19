<?php

use MediaWiki\MediaWikiServices;

/**
 * sourceProtection
 *
 *
 * @license https://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 *
 */
class SourceProtection {

	/**
	 * Dive into the skin. Check if a user may edit. If not, remove tabs.
	 *
	 * @param SkinTemplate $sktemplate
	 * @param array $links
	 *
	 * @return bool
	 */
	public static function hideSource( SkinTemplate &$sktemplate, array &$links ) {
		// always remove viewsource tab
		$removeUs = array( 'viewsource' );
		foreach ( $removeUs as $view ) {
			if ( isset( $links['views'][ $view ] ) ) {
				unset( $links['views'][ $view ] );
			}
		}
		// grab user permissions
		$title         = $sktemplate->getTitle();
		$user          = RequestContext::getMain()->getUser();
		$user_can_edit = MediaWikiServices::getInstance()->getPermissionManager()->userCan( 'edit',
			$user,
			$title );

		//remove form_edit and history when edit is disabled
		if ( $user_can_edit === false ) {
			$rem = array(
				'form_edit',
				'history'
			);
			foreach ( $rem as $v ) {
				if ( isset( $links['views'][ $v ] ) ) {
					unset( $links['views'][ $v ] );
				}
			}
		}

		return true;
	}

	/**
	 * If a user has no edit rights, then make sure it is hard for them to view the source of a document
	 *
	 * @param title $title
	 * @param User $user
	 * @param $action
	 * @param $result
	 *
	 * @return mixed
	 */
	public static function disableActions( Title $title, User $user, $action, &$result ) {
		if ( $title->isSpecialPage() || !$title->exists() ) {
			return true;
		}
		$rights = MediaWikiServices::getInstance()->getPermissionManager()->getUserPermissions( $user );
		if ( in_array( 'edit',
			$rights,
			true ) ) {
			return true;
		} else {
			// define the actions to be blocked
			$actionNotAllowed = array(
				'edit',
				'move',
				'history',
				'info',
				'raw',
				'delete',
				'revert',
				'revisiondelete',
				'rollback',
				'markpatrolled'
			);
			// Also disable the version difference options
			if ( isset( $_GET['diff'] ) ) {
				$result = wfMessage( 'sourceprotection-no-access');
				return false;
			}
			if ( isset( $_GET['action'] ) ) {
				$getAction = $_GET['action'];
				if ( in_array( $getAction, $actionNotAllowed ) ) {
					$result = wfMessage( 'sourceprotection-no-access');
					return false;
				}
			}

			// Any other action is fine
			return true;
		}
	}

	/**
	 * Prevent ShowReadOnly form to be shown. We should never get here anymore, but just in case.
	 *
	 * @param EditPage $editPage
	 * @param OutputPage $output
	 *
	 * @return OutputPage
	 */
	public static function doNotShowReadOnlyForm( EditPage $editPage, OutputPage $output ) {

		$title         = $editPage->getTitle();
		$user          = RequestContext::getMain()->getUser();
		$user_can_edit = MediaWikiServices::getInstance()->getPermissionManager()->userCan( 'edit',
			$user,
			$title );
		if ( !$user_can_edit ) {
			$output->redirect( $editPage->getContextTitle() );
		}

		return $output;
	}

}
