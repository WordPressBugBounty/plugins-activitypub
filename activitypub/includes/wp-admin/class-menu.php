<?php
/**
 * Menu file.
 *
 * @package Activitypub
 */

namespace Activitypub\WP_Admin;

use function Activitypub\user_can_activitypub;

/**
 * ActivityPub Menu Class.
 */
class Menu {

	/**
	 * Add admin menu entry.
	 */
	public static function admin_menu() {
		$settings_page = \add_options_page(
			'Welcome',
			'ActivityPub',
			'manage_options',
			'activitypub',
			array( Settings::class, 'settings_page' )
		);

		\add_action( 'load-' . $settings_page, array( Settings::class, 'add_settings_help_tab' ) );

		// User has to be able to publish posts.
		if ( user_can_activitypub( \get_current_user_id() ) ) {
			$followers_list_page = \add_users_page(
				\__( 'Followers ⁂', 'activitypub' ),
				\__( 'Followers ⁂', 'activitypub' ),
				'activitypub',
				'activitypub-followers-list',
				array( Admin::class, 'followers_list_page' )
			);

			\add_action( 'load-' . $followers_list_page, array( Admin::class, 'add_followers_list_help_tab' ) );

			\add_users_page(
				\__( 'Extra Fields ⁂', 'activitypub' ),
				\__( 'Extra Fields ⁂', 'activitypub' ),
				'activitypub',
				\esc_url( \admin_url( '/edit.php?post_type=ap_extrafield' ) )
			);
		}
	}
}
