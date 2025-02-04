<?php
/**
 * ActivityPub Interaction REST-Class file.
 *
 * @package Activitypub
 */

namespace Activitypub\Rest;

use WP_REST_Response;
use Activitypub\Http;

/**
 * Interaction class.
 */
class Interaction {
	/**
	 * Initialize the class, registering WordPress hooks.
	 */
	public static function init() {
		self::register_routes();
	}

	/**
	 * Register routes
	 */
	public static function register_routes() {
		\register_rest_route(
			ACTIVITYPUB_REST_NAMESPACE,
			'/interactions',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'uri' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'esc_url',
						),
					),
				),
			)
		);
	}

	/**
	 * Handle GET request.
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response Redirect to the editor or die.
	 */
	public static function get( $request ) {
		$uri          = $request->get_param( 'uri' );
		$redirect_url = null;
		$object       = Http::get_remote_object( $uri );

		if (
			\is_wp_error( $object ) ||
			! isset( $object['type'] )
		) {
			\wp_die(
				\esc_html__(
					'The URL is not supported!',
					'activitypub'
				),
				400
			);
		}

		if ( ! empty( $object['url'] ) ) {
			$uri = \esc_url( $object['url'] );
		}

		switch ( $object['type'] ) {
			case 'Group':
			case 'Person':
			case 'Service':
			case 'Application':
			case 'Organization':
				/**
				 * Filters the URL used for following an ActivityPub actor.
				 *
				 * @param string $redirect_url The URL to redirect to.
				 * @param string $uri          The URI of the actor to follow.
				 * @param array  $object       The full actor object data.
				 */
				$redirect_url = \apply_filters( 'activitypub_interactions_follow_url', $redirect_url, $uri, $object );
				break;
			default:
				$redirect_url = \admin_url( 'post-new.php?in_reply_to=' . $uri );
				/**
				 * Filters the URL used for replying to an ActivityPub object.
				 *
				 * By default, this redirects to the WordPress post editor with the in_reply_to parameter set.
				 *
				 * @param string $redirect_url The URL to redirect to.
				 * @param string $uri          The URI of the object to reply to.
				 * @param array  $object       The full object data being replied to.
				 */
				$redirect_url = \apply_filters( 'activitypub_interactions_reply_url', $redirect_url, $uri, $object );
		}

		/**
		 * Filter the redirect URL.
		 *
		 * @param string $redirect_url The URL to redirect to.
		 * @param string $uri          The URI of the object.
		 * @param array  $object       The object.
		 */
		$redirect_url = \apply_filters( 'activitypub_interactions_url', $redirect_url, $uri, $object );

		// Check if hook is implemented.
		if ( ! $redirect_url ) {
			\wp_die(
				esc_html__(
					'This Interaction type is not supported yet!',
					'activitypub'
				),
				400
			);
		}

		return new WP_REST_Response(
			null,
			302,
			array(
				'Location' => \esc_url( $redirect_url ),
			)
		);
	}
}
