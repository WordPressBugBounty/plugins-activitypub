<?php
/**
 * Reject handler file.
 *
 * @package Activitypub
 */

namespace Activitypub\Handler;

use Activitypub\Notification;
use Activitypub\Collection\Actors;
use Activitypub\Collection\Following;
use Activitypub\Collection\Outbox;

use function Activitypub\object_to_uri;

/**
 * Handle "Reject" requests.
 */
class Reject {
	/**
	 * Initialize the class, registering WordPress hooks.
	 */
	public static function init() {
		\add_action(
			'activitypub_inbox_reject',
			array( self::class, 'handle_reject' ),
			10,
			2
		);

		\add_filter(
			'activitypub_validate_object',
			array( self::class, 'validate_object' ),
			10,
			3
		);
	}

	/**
	 * Handles "Reject" requests.
	 *
	 * @param array $reject  The activity-object.
	 * @param int   $user_id The id of the local blog-user.
	 */
	public static function handle_reject( $reject, $user_id ) {
		// Validate that there is a preceding Activity.
		$outbox_post = Outbox::get_by_guid( $reject['object']['id'] );

		if ( \is_wp_error( $outbox_post ) ) {
			return;
		}

		// We currently only support reject for Follow activities. But we will support more in the future.
		switch ( \get_post_meta( $outbox_post->ID, '_activitypub_activity_type', true ) ) {
			case 'Follow':
				self::reject_follow( $reject, $user_id );
				break;
			default:
				break;
		}
	}

	/**
	 * Reject a "Follow" request.
	 *
	 * @param array $reject  The activity-object.
	 * @param int   $user_id The id of the local blog-user.
	 */
	private static function reject_follow( $reject, $user_id ) {
		$actor_uri  = $reject['object']['actor'] ?? '';
		$actor_post = Actors::get_remote_by_uri( object_to_uri( $actor_uri ) );

		if ( \is_wp_error( $actor_post ) ) {
			return;
		}

		Following::reject( $actor_post, $user_id );

		// Send notification.
		$notification = new Notification(
			'reject',
			$actor_post->guid,
			$reject,
			$user_id
		);
		$notification->send();
	}

	/**
	 * Validate the object.
	 *
	 * @param bool             $valid   The validation state.
	 * @param string           $param   The object parameter.
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return bool The validation state: true if valid, false if not.
	 */
	public static function validate_object( $valid, $param, $request ) {
		$json_params = $request->get_json_params();

		if ( empty( $json_params['type'] ) ) {
			return false;
		}

		if (
			'Reject' !== $json_params['type'] ||
			\is_wp_error( $request )
		) {
			return $valid;
		}

		if ( empty( $json_params['actor'] ) || empty( $json_params['object'] ) ) {
			return false;
		}

		return $valid;
	}
}
