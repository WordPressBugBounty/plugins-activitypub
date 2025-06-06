<?php
/**
 * Actors collection file.
 *
 * @package Activitypub
 */

namespace Activitypub\Collection;

use WP_Error;
use WP_User_Query;
use Activitypub\Model\User;
use Activitypub\Model\Blog;
use Activitypub\Model\Application;

use function Activitypub\object_to_uri;
use function Activitypub\normalize_url;
use function Activitypub\normalize_host;
use function Activitypub\url_to_authorid;
use function Activitypub\is_user_type_disabled;
use function Activitypub\user_can_activitypub;

/**
 * Actors collection.
 */
class Actors {
	/**
	 * The ID of the Blog User.
	 *
	 * @var int
	 */
	const BLOG_USER_ID = 0;

	/**
	 * The ID of the Application User.
	 *
	 * @var int
	 */
	const APPLICATION_USER_ID = -1;

	/**
	 * Post type.
	 *
	 * The post type to store remote actors.
	 *
	 * @var string
	 */
	const POST_TYPE = 'ap_actor';

	/**
	 * Get the Actor by ID.
	 *
	 * @param int $user_id The User-ID.
	 *
	 * @return User|Blog|Application|WP_Error The Actor or WP_Error if user not found.
	 */
	public static function get_by_id( $user_id ) {
		if ( is_numeric( $user_id ) ) {
			$user_id = (int) $user_id;
		}

		if ( ! user_can_activitypub( $user_id ) ) {
			return new WP_Error(
				'activitypub_user_not_found',
				\__( 'Actor not found', 'activitypub' ),
				array( 'status' => 404 )
			);
		}

		switch ( $user_id ) {
			case self::BLOG_USER_ID:
				return new Blog();
			case self::APPLICATION_USER_ID:
				return new Application();
			default:
				return User::from_wp_user( $user_id );
		}
	}

	/**
	 * Get the Actor by username.
	 *
	 * @param string $username Name of the Actor.
	 *
	 * @return User|Blog|Application|WP_Error The Actor or WP_Error if user not found.
	 */
	public static function get_by_username( $username ) {
		/**
		 * Filter the username before we do anything else.
		 *
		 * @param null   $pre      The pre-existing value.
		 * @param string $username The username.
		 */
		$pre = apply_filters( 'activitypub_pre_get_by_username', null, $username );
		if ( null !== $pre ) {
			return $pre;
		}

		// Check for blog user.
		if (
			Blog::get_default_username() === $username ||
			\get_option( 'activitypub_blog_identifier' ) === $username
		) {
			if ( is_user_type_disabled( 'blog' ) ) {
				return new WP_Error(
					'activitypub_user_not_found',
					\__( 'Actor not found', 'activitypub' ),
					array( 'status' => 404 )
				);
			}

			return new Blog();
		}

		// Check for application user.
		if ( 'application' === $username ) {
			return new Application();
		}

		// Check for 'activitypub_username' meta.
		$user = new WP_User_Query(
			array(
				'count_total' => false,
				'number'      => 1,
				'hide_empty'  => true,
				'fields'      => 'ID',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'  => array(
					'relation' => 'OR',
					array(
						'key'     => '_activitypub_user_identifier',
						'value'   => $username,
						'compare' => 'LIKE',
					),
				),
			)
		);

		if ( $user->get_results() ) {
			$actor = self::get_by_id( $user->get_results()[0] );
			if ( ! \is_wp_error( $actor ) ) {
				return $actor;
			}
		}

		$username = str_replace( array( '*', '%' ), '', $username );

		// Check for login or nicename.
		$user = new WP_User_Query(
			array(
				'count_total'    => false,
				'search'         => $username,
				'search_columns' => array( 'user_login', 'user_nicename' ),
				'number'         => 1,
				'hide_empty'     => true,
				'fields'         => 'ID',
			)
		);

		if ( $user->get_results() ) {
			$actor = self::get_by_id( $user->get_results()[0] );
			if ( ! \is_wp_error( $actor ) ) {
				return $actor;
			}
		}

		return new WP_Error(
			'activitypub_user_not_found',
			\__( 'Actor not found', 'activitypub' ),
			array( 'status' => 404 )
		);
	}

	/**
	 * Get the Actor by resource.
	 *
	 * @param string $uri The Actor resource.
	 *
	 * @return User|Blog|Application|WP_Error The Actor or WP_Error if user not found.
	 */
	public static function get_by_resource( $uri ) {
		$uri = object_to_uri( $uri );

		if ( ! $uri ) {
			return new WP_Error(
				'activitypub_no_uri',
				\__( 'No URI provided', 'activitypub' ),
				array( 'status' => 404 )
			);
		}

		$scheme = 'acct';
		$match  = array();
		// Try to extract the scheme and the host.
		if ( preg_match( '/^([a-zA-Z^:]+):(.*)$/i', $uri, $match ) ) {
			// Extract the scheme.
			$scheme = \esc_attr( $match[1] );
		}

		// @todo: handle old domain URIs here before we serve a new domain below when we shouldn't.
		// Although maybe passing through to ::get_by_username() is enough?

		switch ( $scheme ) {
			// Check for http(s) URIs.
			case 'http':
			case 'https':
				$resource_path = \wp_parse_url( $uri, PHP_URL_PATH );

				if ( $resource_path ) {
					$blog_path = \wp_parse_url( \home_url(), PHP_URL_PATH );

					if ( $blog_path ) {
						$resource_path = \str_replace( $blog_path, '', $resource_path );
					}

					$resource_path = \trim( $resource_path, '/' );

					// Check for http(s)://blog.example.com/@username.
					if ( str_starts_with( $resource_path, '@' ) ) {
						$identifier = \str_replace( '@', '', $resource_path );
						$identifier = \trim( $identifier, '/' );

						return self::get_by_username( $identifier );
					}
				}

				// Check for http(s)://blog.example.com/author/username.
				$user_id = url_to_authorid( $uri );

				if ( \is_int( $user_id ) ) {
					return self::get_by_id( $user_id );
				}

				// Check for http(s)://blog.example.com/.
				$normalized_uri = normalize_url( $uri );

				if (
					normalize_url( site_url() ) === $normalized_uri ||
					normalize_url( home_url() ) === $normalized_uri
				) {
					return self::get_by_id( self::BLOG_USER_ID );
				}

				return new WP_Error(
					'activitypub_no_user_found',
					\__( 'Actor not found', 'activitypub' ),
					array( 'status' => 404 )
				);
			// Check for acct URIs.
			case 'acct':
				$uri        = \str_replace( 'acct:', '', $uri );
				$identifier = \substr( $uri, 0, \strrpos( $uri, '@' ) );
				$host       = normalize_host( \substr( \strrchr( $uri, '@' ), 1 ) );
				$blog_host  = normalize_host( \wp_parse_url( \home_url( '/' ), \PHP_URL_HOST ) );

				if ( $blog_host !== $host && get_option( 'activitypub_old_host' ) !== $host ) {
					return new WP_Error(
						'activitypub_wrong_host',
						\__( 'Resource host does not match blog host', 'activitypub' ),
						array( 'status' => 404 )
					);
				}

				// Prepare wildcards https://github.com/mastodon/mastodon/issues/22213.
				if ( in_array( $identifier, array( '_', '*', '' ), true ) ) {
					return self::get_by_id( self::BLOG_USER_ID );
				}

				return self::get_by_username( $identifier );
			default:
				return new WP_Error(
					'activitypub_wrong_scheme',
					\__( 'Wrong scheme', 'activitypub' ),
					array( 'status' => 404 )
				);
		}
	}

	/**
	 * Get the Actor by resource.
	 *
	 * @param string $id The Actor resource.
	 *
	 * @return User|Blog|Application|WP_Error The Actor or WP_Error if user not found.
	 */
	public static function get_by_various( $id ) {
		if ( is_numeric( $id ) ) {
			$user = self::get_by_id( $id );
		} elseif (
			// Is URL.
			filter_var( $id, FILTER_VALIDATE_URL ) ||
			// Is acct.
			str_starts_with( $id, 'acct:' ) ||
			// Is email.
			filter_var( $id, FILTER_VALIDATE_EMAIL )
		) {
			$user = self::get_by_resource( $id );
		} else {
			$user = self::get_by_username( $id );
		}

		return $user;
	}

	/**
	 * Get the Actor collection.
	 *
	 * @return array The Actor collection.
	 */
	public static function get_collection() {
		if ( is_user_type_disabled( 'user' ) ) {
			return array();
		}

		$users = \get_users(
			array(
				'capability__in' => array( 'activitypub' ),
			)
		);

		$return = array();

		foreach ( $users as $user ) {
			$actor = User::from_wp_user( $user->ID );

			if ( \is_wp_error( $actor ) ) {
				continue;
			}

			$return[] = $actor;
		}

		return $return;
	}

	/**
	 * Get all active Actors including the Blog Actor.
	 *
	 * @return array The actor collection.
	 */
	public static function get_all() {
		$return = array();

		if ( ! is_user_type_disabled( 'user' ) ) {
			$users = \get_users(
				array(
					'capability__in' => array( 'activitypub' ),
				)
			);

			foreach ( $users as $user ) {
				$actor = User::from_wp_user( $user->ID );

				if ( \is_wp_error( $actor ) ) {
					continue;
				}

				$return[] = $actor;
			}
		}

		// Also include the blog actor if active.
		if ( ! is_user_type_disabled( 'blog' ) ) {
			$blog_actor = self::get_by_id( self::BLOG_USER_ID );
			if ( ! \is_wp_error( $blog_actor ) ) {
				$return[] = $blog_actor;
			}
		}

		return $return;
	}

	/**
	 * Returns the actor type based on the user ID.
	 *
	 * @param int $user_id The user ID to check.
	 * @return string The user type.
	 */
	public static function get_type_by_id( $user_id ) {
		$user_id = (int) $user_id;

		if ( self::APPLICATION_USER_ID === $user_id ) {
			return 'application';
		}

		if ( self::BLOG_USER_ID === $user_id ) {
			return 'blog';
		}

		return 'user';
	}
}
