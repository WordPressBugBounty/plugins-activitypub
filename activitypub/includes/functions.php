<?php
/**
 * Functions file.
 *
 * @package Activitypub
 */

namespace Activitypub;

use WP_Error;
use Activitypub\Activity\Activity;
use Activitypub\Activity\Base_Object;
use Activitypub\Collection\Actors;
use Activitypub\Collection\Outbox;
use Activitypub\Collection\Followers;
use Activitypub\Transformer\Post;
use Activitypub\Transformer\Factory as Transformer_Factory;

/**
 * Returns the ActivityPub default JSON-context.
 *
 * @return array The activitypub context.
 */
function get_context() {
	$context = Activity::JSON_LD_CONTEXT;

	/**
	 * Filters the ActivityPub JSON-LD context.
	 *
	 * This filter allows developers to modify or extend the JSON-LD context used
	 * in ActivityPub responses. The context defines the vocabulary and terms used
	 * in the ActivityPub JSON objects.
	 *
	 * @param array $context The default ActivityPub JSON-LD context array.
	 */
	return \apply_filters( 'activitypub_json_context', $context );
}

/**
 * Send a POST request to a remote server.
 *
 * @param string $url     The URL endpoint.
 * @param string $body    The Post Body.
 * @param int    $user_id The WordPress user ID.
 *
 * @return array|WP_Error The POST Response or an WP_Error.
 */
function safe_remote_post( $url, $body, $user_id ) {
	return Http::post( $url, $body, $user_id );
}

/**
 * Send a GET request to a remote server.
 *
 * @param string $url The URL endpoint.
 *
 * @return array|WP_Error The GET Response or an WP_Error.
 */
function safe_remote_get( $url ) {
	return Http::get( $url );
}

/**
 * Returns a users WebFinger "resource".
 *
 * @param int $user_id The user ID.
 *
 * @return string The User resource.
 */
function get_webfinger_resource( $user_id ) {
	return Webfinger::get_user_resource( $user_id );
}

/**
 * Requests the Meta-Data from the Actors profile.
 *
 * @param array|string $actor  The Actor array or URL.
 * @param bool         $cached Optional. Whether the result should be cached. Default true.
 *
 * @return array|WP_Error The Actor profile as array or WP_Error on failure.
 */
function get_remote_metadata_by_actor( $actor, $cached = true ) {
	/**
	 * Filters the metadata before it is retrieved from a remote actor.
	 *
	 * Passing a non-false value will effectively short-circuit the remote request,
	 * returning that value instead.
	 *
	 * @param mixed  $pre   The value to return instead of the remote metadata.
	 *                      Default false to continue with the remote request.
	 * @param string $actor The actor URL.
	 */
	$pre = apply_filters( 'pre_get_remote_metadata_by_actor', false, $actor );
	if ( $pre ) {
		return $pre;
	}

	return Http::get_remote_object( $actor, $cached );
}

/**
 * Returns the followers of a given user.
 *
 * @param int $user_id The user ID.
 *
 * @return array The followers.
 */
function get_followers( $user_id ) {
	return Followers::get_followers( $user_id );
}

/**
 * Count the number of followers for a given user.
 *
 * @param int $user_id The user ID.
 *
 * @return int The number of followers.
 */
function count_followers( $user_id ) {
	return Followers::count_followers( $user_id );
}

/**
 * Examine a url and try to determine the author ID it represents.
 *
 * Checks are supposedly from the hosted site blog.
 *
 * @param string $url Permalink to check.
 *
 * @return int|null User ID, or null on failure.
 */
function url_to_authorid( $url ) {
	global $wp_rewrite;

	// Check if url hase the same host.
	if ( \wp_parse_url( \home_url(), \PHP_URL_HOST ) !== \wp_parse_url( $url, \PHP_URL_HOST ) ) {
		return null;
	}

	// First, check to see if there is an 'author=N' to match against.
	if ( \preg_match( '/[?&]author=(\d+)/i', $url, $values ) ) {
		return \absint( $values[1] );
	}

	// Check to see if we are using rewrite rules.
	$rewrite = $wp_rewrite->wp_rewrite_rules();

	// Not using rewrite rules, and 'author=N' method failed, so we're out of options.
	if ( empty( $rewrite ) ) {
		return null;
	}

	// Generate rewrite rule for the author url.
	$author_rewrite = $wp_rewrite->get_author_permastruct();
	$author_regexp  = \str_replace( '%author%', '', $author_rewrite );

	// Match the rewrite rule with the passed url.
	if ( \preg_match( '/https?:\/\/(.+)' . \preg_quote( $author_regexp, '/' ) . '([^\/]+)/i', $url, $match ) ) {
		$user = \get_user_by( 'slug', $match[2] );
		if ( $user ) {
			return $user->ID;
		}
	}

	return null;
}

/**
 * Verify that url is a wp_ap_comment or a previously received remote comment.
 *
 * @return int|bool Comment ID or false if not found.
 */
function is_comment() {
	$comment_id = get_query_var( 'c', null );

	if ( ! is_null( $comment_id ) ) {
		$comment = \get_comment( $comment_id );

		if ( $comment ) {
			return $comment_id;
		}
	}

	return false;
}

/**
 * Check for Tombstone Objects.
 *
 * @see https://www.w3.org/TR/activitypub/#delete-activity-outbox
 *
 * @param WP_Error $wp_error A WP_Error-Response of an HTTP-Request.
 *
 * @return boolean True if HTTP-Code is 410 or 404.
 */
function is_tombstone( $wp_error ) {
	if ( ! is_wp_error( $wp_error ) ) {
		return false;
	}

	if ( in_array( (int) $wp_error->get_error_code(), array( 404, 410 ), true ) ) {
		return true;
	}

	return false;
}

/**
 * Get the REST URL relative to this plugin's namespace.
 *
 * @param string $path Optional. REST route path. Default ''.
 *
 * @return string REST URL relative to this plugin's namespace.
 */
function get_rest_url_by_path( $path = '' ) {
	// We'll handle the leading slash.
	$path            = ltrim( $path, '/' );
	$namespaced_path = sprintf( '/%s/%s', ACTIVITYPUB_REST_NAMESPACE, $path );
	return \get_rest_url( null, $namespaced_path );
}

/**
 * Convert a string from camelCase to snake_case.
 *
 * @param string $input The string to convert.
 *
 * @return string The converted string.
 */
function camel_to_snake_case( $input ) {
	return strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $input ) );
}

/**
 * Convert a string from snake_case to camelCase.
 *
 * @param string $input The string to convert.
 *
 * @return string The converted string.
 */
function snake_to_camel_case( $input ) {
	return lcfirst( str_replace( '_', '', ucwords( $input, '_' ) ) );
}

/**
 * Escapes a Tag, to be used as a hashtag.
 *
 * @param string $input The string to escape.
 *
 * @return string The escaped hashtag.
 */
function esc_hashtag( $input ) {

	$hashtag = \wp_specialchars_decode( $input, ENT_QUOTES );
	// Remove all characters that are not letters, numbers, or underscores.
	$hashtag = \preg_replace( '/emoji-regex(*SKIP)(?!)|[^\p{L}\p{Nd}_]+/u', '_', $hashtag );

	// Capitalize every letter that is preceded by an underscore.
	$hashtag = preg_replace_callback(
		'/_(.)/',
		function ( $matches ) {
			return strtoupper( $matches[1] );
		},
		$hashtag
	);

	// Add a hashtag to the beginning of the string.
	$hashtag = ltrim( $hashtag, '#' );
	$hashtag = '#' . $hashtag;

	/**
	 * Allow defining your own custom hashtag generation rules.
	 *
	 * @param string $hashtag The hashtag to be returned.
	 * @param string $input   The original string.
	 */
	$hashtag = apply_filters( 'activitypub_esc_hashtag', $hashtag, $input );

	return esc_html( $hashtag );
}

/**
 * Check if a request is for an ActivityPub request.
 *
 * @return bool False by default.
 */
function is_activitypub_request() {
	return Query::get_instance()->is_activitypub_request();
}

/**
 * Check if a post is disabled for ActivityPub.
 *
 * This function checks if the post type supports ActivityPub and if the post is set to be local.
 *
 * @param mixed $post The post object or ID.
 *
 * @return boolean True if the post is disabled, false otherwise.
 */
function is_post_disabled( $post ) {
	$post     = \get_post( $post );
	$disabled = false;

	if ( ! $post ) {
		return true;
	}

	$visibility = \get_post_meta( $post->ID, 'activitypub_content_visibility', true );

	if (
		ACTIVITYPUB_CONTENT_VISIBILITY_LOCAL === $visibility ||
		ACTIVITYPUB_CONTENT_VISIBILITY_PRIVATE === $visibility ||
		! \post_type_supports( $post->post_type, 'activitypub' ) ||
		'private' === $post->post_status ||
		! empty( $post->post_password )
	) {
		$disabled = true;
	}

	/**
	 * Allow plugins to disable posts for ActivityPub.
	 *
	 * @param boolean  $disabled True if the post is disabled, false otherwise.
	 * @param \WP_Post $post     The post object.
	 */
	return \apply_filters( 'activitypub_is_post_disabled', $disabled, $post );
}

/**
 * This function checks if a user is disabled for ActivityPub.
 *
 * @param int $user_id The user ID.
 *
 * @return boolean True if the user is disabled, false otherwise.
 */
function is_user_disabled( $user_id ) {
	$disabled = false;

	switch ( $user_id ) {
		// if the user is the application user, it's always enabled.
		case \Activitypub\Collection\Actors::APPLICATION_USER_ID:
			$disabled = false;
			break;
		// if the user is the blog user, it's only enabled in single-user mode.
		case \Activitypub\Collection\Actors::BLOG_USER_ID:
			if ( is_user_type_disabled( 'blog' ) ) {
				$disabled = true;
				break;
			}

			$disabled = false;
			break;
		// if the user is any other user, it's enabled if it can publish posts.
		default:
			if ( ! \get_user_by( 'id', $user_id ) ) {
				$disabled = true;
				break;
			}

			if ( is_user_type_disabled( 'user' ) ) {
				$disabled = true;
				break;
			}

			if ( ! \user_can( $user_id, 'activitypub' ) ) {
				$disabled = true;
				break;
			}

			$disabled = false;
			break;
	}

	/**
	 * Allow plugins to disable users for ActivityPub.
	 *
	 * @param boolean $disabled True if the user is disabled, false otherwise.
	 * @param int     $user_id  The User-ID.
	 */
	return apply_filters( 'activitypub_is_user_disabled', $disabled, $user_id );
}

/**
 * Checks if a User-Type is disabled for ActivityPub.
 *
 * This function is used to check if the 'blog' or 'user'
 * type is disabled for ActivityPub.
 *
 * @param string $type User type. 'blog' or 'user'.
 *
 * @return boolean True if the user type is disabled, false otherwise.
 */
function is_user_type_disabled( $type ) {
	switch ( $type ) {
		case 'blog':
			if ( \defined( 'ACTIVITYPUB_SINGLE_USER_MODE' ) ) {
				if ( ACTIVITYPUB_SINGLE_USER_MODE ) {
					$disabled = false;
					break;
				}
			}

			if ( \defined( 'ACTIVITYPUB_DISABLE_BLOG_USER' ) ) {
				$disabled = ACTIVITYPUB_DISABLE_BLOG_USER;
				break;
			}

			if ( ACTIVITYPUB_ACTOR_MODE === \get_option( 'activitypub_actor_mode', ACTIVITYPUB_ACTOR_MODE ) ) {
				$disabled = true;
				break;
			}

			$disabled = false;
			break;
		case 'user':
			if ( \defined( 'ACTIVITYPUB_SINGLE_USER_MODE' ) ) {
				if ( ACTIVITYPUB_SINGLE_USER_MODE ) {
					$disabled = true;
					break;
				}
			}

			if ( \defined( 'ACTIVITYPUB_DISABLE_USER' ) ) {
				$disabled = ACTIVITYPUB_DISABLE_USER;
				break;
			}

			if ( ACTIVITYPUB_BLOG_MODE === \get_option( 'activitypub_actor_mode', ACTIVITYPUB_ACTOR_MODE ) ) {
				$disabled = true;
				break;
			}

			$disabled = false;
			break;
		default:
			$disabled = new WP_Error(
				'activitypub_wrong_user_type',
				__( 'Wrong user type', 'activitypub' ),
				array( 'status' => 400 )
			);
			break;
	}

	/**
	 * Allow plugins to disable user types for ActivityPub.
	 *
	 * @param boolean $disabled True if the user type is disabled, false otherwise.
	 * @param string  $type     The User-Type.
	 */
	return apply_filters( 'activitypub_is_user_type_disabled', $disabled, $type );
}

/**
 * Check if the blog is in single-user mode.
 *
 * @return boolean True if the blog is in single-user mode, false otherwise.
 */
function is_single_user() {
	if (
		false === is_user_type_disabled( 'blog' ) &&
		true === is_user_type_disabled( 'user' )
	) {
		return true;
	}

	return false;
}

/**
 * Check if a site supports the block editor.
 *
 * @return boolean True if the site supports the block editor, false otherwise.
 */
function site_supports_blocks() {
	/**
	 * Allow plugins to disable block editor support,
	 * thus disabling blocks registered by the ActivityPub plugin.
	 *
	 * @param boolean $supports_blocks True if the site supports the block editor, false otherwise.
	 */
	return apply_filters( 'activitypub_site_supports_blocks', true );
}

/**
 * Check if data is valid JSON.
 *
 * @param string $data The data to check.
 *
 * @return boolean True if the data is JSON, false otherwise.
 */
function is_json( $data ) {
	return \is_array( \json_decode( $data, true ) ) ? true : false;
}

/**
 * Check whether a blog is public based on the `blog_public` option.
 *
 * @return bool True if public, false if not
 */
function is_blog_public() {
	/**
	 * Filter whether the blog is public.
	 *
	 * @param bool $public Whether the blog is public.
	 */
	return (bool) apply_filters( 'activitypub_is_blog_public', \get_option( 'blog_public', 1 ) );
}

/**
 * Extract recipient URLs from Activity object.
 *
 * @param array $data The Activity object as array.
 *
 * @return array The list of user URLs.
 */
function extract_recipients_from_activity( $data ) {
	$recipient_items = array();

	foreach ( array( 'to', 'bto', 'cc', 'bcc', 'audience' ) as $i ) {
		if ( array_key_exists( $i, $data ) ) {
			if ( is_array( $data[ $i ] ) ) {
				$recipient = $data[ $i ];
			} else {
				$recipient = array( $data[ $i ] );
			}
			$recipient_items = array_merge( $recipient_items, $recipient );
		}

		if ( is_array( $data['object'] ) && array_key_exists( $i, $data['object'] ) ) {
			if ( is_array( $data['object'][ $i ] ) ) {
				$recipient = $data['object'][ $i ];
			} else {
				$recipient = array( $data['object'][ $i ] );
			}
			$recipient_items = array_merge( $recipient_items, $recipient );
		}
	}

	$recipients = array();

	// Flatten array.
	foreach ( $recipient_items as $recipient ) {
		if ( is_array( $recipient ) ) {
			// Check if recipient is an object.
			if ( array_key_exists( 'id', $recipient ) ) {
				$recipients[] = $recipient['id'];
			}
		} else {
			$recipients[] = $recipient;
		}
	}

	return array_unique( $recipients );
}

/**
 * Check if passed Activity is Public.
 *
 * @param array $data The Activity object as array.
 *
 * @return boolean True if public, false if not.
 */
function is_activity_public( $data ) {
	$recipients = extract_recipients_from_activity( $data );

	return in_array( 'https://www.w3.org/ns/activitystreams#Public', $recipients, true );
}

/**
 * Check if passed Activity is a reply.
 *
 * @param array $data The Activity object as array.
 *
 * @return boolean True if a reply, false if not.
 */
function is_activity_reply( $data ) {
	return ! empty( $data['object']['inReplyTo'] );
}

/**
 * Get active users based on a given duration.
 *
 * @param int $duration Optional. The duration to check in month(s). Default 1.
 *
 * @return int The number of active users.
 */
function get_active_users( $duration = 1 ) {

	$duration      = intval( $duration );
	$transient_key = sprintf( 'monthly_active_users_%d', $duration );
	$count         = get_transient( $transient_key );

	if ( false === $count ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT( DISTINCT post_author ) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND post_date <= DATE_SUB( NOW(), INTERVAL %d MONTH )",
				$duration
			)
		);

		set_transient( $transient_key, $count, DAY_IN_SECONDS );
	}

	// If 0 authors where active.
	if ( 0 === $count ) {
		return 0;
	}

	// If single user mode.
	if ( is_single_user() ) {
		return 1;
	}

	// If blog user is disabled.
	if ( is_user_disabled( Actors::BLOG_USER_ID ) ) {
		return (int) $count;
	}

	// Also count blog user.
	return (int) $count + 1;
}

/**
 * Get the total number of users.
 *
 * @return int The total number of users.
 */
function get_total_users() {
	// If single user mode.
	if ( is_single_user() ) {
		return 1;
	}

	$users = \get_users(
		array(
			'capability__in' => array( 'activitypub' ),
		)
	);

	if ( is_array( $users ) ) {
		$users = count( $users );
	} else {
		$users = 1;
	}

	// If blog user is disabled.
	if ( is_user_disabled( Actors::BLOG_USER_ID ) ) {
		return (int) $users;
	}

	return (int) $users + 1;
}

/**
 * Examine a comment ID and look up an existing comment it represents.
 *
 * @param string $id ActivityPub object ID (usually a URL) to check.
 *
 * @return \WP_Comment|boolean Comment, or false on failure.
 */
function object_id_to_comment( $id ) {
	return Comment::object_id_to_comment( $id );
}

/**
 * Verify that URL is a local comment or a previously received remote comment.
 * (For threading comments locally)
 *
 * @param string $url The URL to check.
 *
 * @return string|null Comment ID or null if not found
 */
function url_to_commentid( $url ) {
	return Comment::url_to_commentid( $url );
}

/**
 * Get the URI of an ActivityPub object.
 *
 * @param array|string $data The ActivityPub object.
 *
 * @return string The URI of the ActivityPub object
 */
function object_to_uri( $data ) {
	// Check whether it is already simple.
	if ( ! $data || is_string( $data ) ) {
		return $data;
	}

	if ( is_object( $data ) ) {
		$data = $data->to_array();
	}

	/*
	 * Check if it is a list, then take first item.
	 * This plugin does not support collections.
	 */
	if ( array_is_list( $data ) ) {
		$data = $data[0];
	}

	// Check if it is simplified now.
	if ( is_string( $data ) ) {
		return $data;
	}

	$type = 'Object';
	if ( isset( $data['type'] ) ) {
		$type = $data['type'];
	}

	// Return part of Object that makes most sense.
	switch ( $type ) {
		case 'Image':
			$data = $data['url'];
			break;
		case 'Link':
			$data = $data['href'];
			break;
		default:
			$data = $data['id'];
			break;
	}

	return $data;
}

/**
 * Check if a comment should be federated.
 *
 * We consider a comment should be federated if it is authored by a user that is
 * not disabled for federation and if it is a reply directly to the post or to a
 * federated comment.
 *
 * @param mixed $comment Comment object or ID.
 *
 * @return boolean True if the comment should be federated, false otherwise.
 */
function should_comment_be_federated( $comment ) {
	return Comment::should_be_federated( $comment );
}

/**
 * Check if a comment was federated.
 *
 * This function checks if a comment was federated via ActivityPub.
 *
 * @param mixed $comment Comment object or ID.
 *
 * @return boolean True if the comment was federated, false otherwise.
 */
function was_comment_sent( $comment ) {
	return Comment::was_sent( $comment );
}

/**
 * Check if a comment is federated.
 *
 * We consider a comment federated if comment was received via ActivityPub.
 *
 * Use this function to check if it is comment that was received via ActivityPub.
 *
 * @param mixed $comment Comment object or ID.
 *
 * @return boolean True if the comment is federated, false otherwise.
 */
function was_comment_received( $comment ) {
	return Comment::was_received( $comment );
}

/**
 * Check if a comment is local only.
 *
 * This function checks if a comment is local only and was not sent or received via ActivityPub.
 *
 * @param mixed $comment Comment object or ID.
 *
 * @return boolean True if the comment is local only, false otherwise.
 */
function is_local_comment( $comment ) {
	return Comment::is_local( $comment );
}

/**
 * Mark a WordPress object as federated.
 *
 * @param \WP_Comment|\WP_Post $wp_object The WordPress object.
 * @param string               $state     The state of the object.
 */
function set_wp_object_state( $wp_object, $state ) {
	$meta_key = 'activitypub_status';

	if ( $wp_object instanceof \WP_Post ) {
		\update_post_meta( $wp_object->ID, $meta_key, $state );
	} elseif ( $wp_object instanceof \WP_Comment ) {
		\update_comment_meta( $wp_object->comment_ID, $meta_key, $state );
	} else {
		/**
		 * Allow plugins to mark WordPress objects as federated.
		 *
		 * @param \WP_Comment|\WP_Post $wp_object The WordPress object.
		 * @param string               $state     The state of the object.
		 */
		\apply_filters( 'activitypub_mark_wp_object_as_federated', $wp_object );
	}
}

/**
 * Get the federation state of a WordPress object.
 *
 * @param \WP_Comment|\WP_Post $wp_object The WordPress object.
 *
 * @return string|false The state of the object or false if not found.
 */
function get_wp_object_state( $wp_object ) {
	$meta_key = 'activitypub_status';

	if ( $wp_object instanceof \WP_Post ) {
		return \get_post_meta( $wp_object->ID, $meta_key, true );
	} elseif ( $wp_object instanceof \WP_Comment ) {
		return \get_comment_meta( $wp_object->comment_ID, $meta_key, true );
	} else {
		/**
		 * Allow plugins to get the federation state of a WordPress object.
		 *
		 * @param \WP_Comment|\WP_Post $wp_object The WordPress object.
		 */
		return \apply_filters( 'activitypub_get_wp_object_state', false, $wp_object );
	}
}

/**
 * Get the description of a post type.
 *
 * Set some default descriptions for the default post types.
 *
 * @param \WP_Post_Type $post_type The post type object.
 *
 * @return string The description of the post type.
 */
function get_post_type_description( $post_type ) {
	$description = '';

	switch ( $post_type->name ) {
		case 'post':
			$description = '';
			break;
		case 'page':
			$description = '';
			break;
		case 'attachment':
			$description = ' - ' . __( 'The attachments that you have uploaded to a post (images, videos, documents or other files).', 'activitypub' );
			break;
		default:
			if ( ! empty( $post_type->description ) ) {
				$description = ' - ' . $post_type->description;
			}
	}

	/**
	 * Allow plugins to get the description of a post type.
	 *
	 * @param string        $description The description of the post type.
	 * @param \WP_Post_Type $post_type   The post type object.
	 */
	return apply_filters( 'activitypub_post_type_description', $description, $post_type->name, $post_type );
}

/**
 * Get the masked WordPress version to only show the major and minor version.
 *
 * @return string The masked version.
 */
function get_masked_wp_version() {
	// Only show the major and minor version.
	$version = get_bloginfo( 'version' );
	// Strip the RC or beta part.
	$version = preg_replace( '/-.*$/', '', $version );
	$version = explode( '.', $version );
	$version = array_slice( $version, 0, 2 );

	return implode( '.', $version );
}

/**
 * Get the enclosures of a post.
 *
 * @param int $post_id The post ID.
 *
 * @return array The enclosures.
 */
function get_enclosures( $post_id ) {
	$enclosures = get_post_meta( $post_id, 'enclosure', false );

	if ( ! $enclosures ) {
		return array();
	}

	$enclosures = array_map(
		function ( $enclosure ) {
			// Check if the enclosure is a string.
			if ( ! $enclosure || ! is_string( $enclosure ) ) {
				return false;
			}

			$attributes = explode( "\n", $enclosure );

			if ( ! isset( $attributes[0] ) || ! \wp_http_validate_url( $attributes[0] ) ) {
				return false;
			}

			return array(
				'url'       => $attributes[0],
				'length'    => $attributes[1] ?? null,
				'mediaType' => $attributes[2] ?? 'application/octet-stream',
			);
		},
		$enclosures
	);

	return array_filter( $enclosures );
}

/**
 * Retrieves the IDs of the ancestors of a comment.
 *
 * Adaption of `get_post_ancestors` from WordPress core.
 *
 * @see https://developer.wordpress.org/reference/functions/get_post_ancestors/
 *
 * @param int|\WP_Comment $comment Comment ID or comment object.
 *
 * @return int[] Array of ancestor IDs.
 */
function get_comment_ancestors( $comment ) {
	$comment = \get_comment( $comment );

	if ( ! $comment || empty( $comment->comment_parent ) || (int) $comment->comment_parent === (int) $comment->comment_ID ) {
		return array();
	}

	$ancestors = array();

	$id          = (int) $comment->comment_parent;
	$ancestors[] = $id;

	while ( $id > 0 ) {
		$ancestor  = \get_comment( $id );
		$parent_id = (int) $ancestor->comment_parent;

		// Loop detection: If the ancestor has been seen before, break.
		if ( empty( $parent_id ) || ( $parent_id === (int) $comment->comment_ID ) || in_array( $parent_id, $ancestors, true ) ) {
			break;
		}

		$id          = $parent_id;
		$ancestors[] = $id;
	}

	return $ancestors;
}

/**
 * Change the display of large numbers on the site.
 *
 * @author Jeremy Herve
 *
 * @see https://wordpress.org/support/topic/abbreviate-numbers-with-k/
 *
 * @param string $formatted Converted number in string format.
 * @param float  $number    The number to convert based on locale.
 * @param int    $decimals  Precision of the number of decimal places.
 *
 * @return string Converted number in string format.
 */
function custom_large_numbers( $formatted, $number, $decimals ) {
	global $wp_locale;

	$decimals      = 0;
	$decimal_point = '.';
	$thousands_sep = ',';

	if ( isset( $wp_locale ) ) {
		$decimals      = (int) $wp_locale->number_format['decimal_point'];
		$decimal_point = $wp_locale->number_format['decimal_point'];
		$thousands_sep = $wp_locale->number_format['thousands_sep'];
	}

	if ( $number < 1000 ) { // Any number less than a Thousand.
		return \number_format( $number, $decimals, $decimal_point, $thousands_sep );
	} elseif ( $number < 1000000 ) { // Any number less than a million.
		return \number_format( $number / 1000, $decimals, $decimal_point, $thousands_sep ) . 'K';
	} elseif ( $number < 1000000000 ) { // Any number less than a billion.
		return \number_format( $number / 1000000, $decimals, $decimal_point, $thousands_sep ) . 'M';
	} else { // At least a billion.
		return \number_format( $number / 1000000000, $decimals, $decimal_point, $thousands_sep ) . 'B';
	}

	// Default fallback. We should not get here.
	return $formatted;
}

/**
 * Registers a ActivityPub comment type.
 *
 * @param string $comment_type Key for comment type.
 * @param array  $args         Optional. Array of arguments for registering a comment type. Default empty array.
 *
 * @return array The registered Activitypub comment type.
 */
function register_comment_type( $comment_type, $args = array() ) {
	global $activitypub_comment_types;

	if ( ! is_array( $activitypub_comment_types ) ) {
		$activitypub_comment_types = array();
	}

	// Sanitize comment type name.
	$comment_type = sanitize_key( $comment_type );

	$activitypub_comment_types[ $comment_type ] = $args;

	/**
	 * Fires after a ActivityPub comment type is registered.
	 *
	 * @param string $comment_type Comment type.
	 * @param array  $args         Arguments used to register the comment type.
	 */
	do_action( 'activitypub_registered_comment_type', $comment_type, $args );

	return $args;
}

/**
 * Normalize a URL.
 *
 * @param string $url The URL.
 *
 * @return string The normalized URL.
 */
function normalize_url( $url ) {
	$url = \untrailingslashit( $url );
	$url = \str_replace( 'https://', '', $url );
	$url = \str_replace( 'http://', '', $url );
	$url = \str_replace( 'www.', '', $url );

	return $url;
}

/**
 * Normalize a host.
 *
 * @param string $host The host.
 *
 * @return string The normalized host.
 */
function normalize_host( $host ) {
	return \str_replace( 'www.', '', $host );
}

/**
 * Get the reply intent URI as a JavaScript URI.
 *
 * @return string The reply intent URI.
 */
function get_reply_intent_js() {
	return sprintf(
		'javascript:(()=>{window.open(\'%s\'+encodeURIComponent(window.location.href));})();',
		get_reply_intent_url()
	);
}

/**
 * Get the reply intent URI.
 *
 * @return string The reply intent URI.
 */
function get_reply_intent_url() {
	/**
	 * Filters the reply intent parameters.
	 *
	 * @param array $params The reply intent parameters.
	 */
	$params = \apply_filters( 'activitypub_reply_intent_params', array() );

	$params += array( 'in_reply_to' => '' );
	$query   = \http_build_query( $params );
	$path    = 'post-new.php?' . $query;
	$url     = \admin_url( $path );

	/**
	 * Filters the reply intent URL.
	 *
	 * @param string $url The reply intent URL.
	 */
	$url = \apply_filters( 'activitypub_reply_intent_url', $url );

	return esc_url_raw( $url );
}

/**
 * Replace content with links, mentions or hashtags by Regex callback and not affect protected tags.
 *
 * @param string   $content        The content that should be changed.
 * @param string   $regex          The regex to use.
 * @param callable $regex_callback Callback for replacement logic.
 *
 * @return string The content with links, mentions, hashtags, etc.
 */
function enrich_content_data( $content, $regex, $regex_callback ) {
	// Small protection against execution timeouts: limit to 1 MB.
	if ( mb_strlen( $content ) > MB_IN_BYTES ) {
		return $content;
	}
	$tag_stack          = array();
	$protected_tags     = array(
		'pre',
		'code',
		'textarea',
		'style',
		'a',
	);
	$content_with_links = '';
	$in_protected_tag   = false;
	foreach ( wp_html_split( $content ) as $chunk ) {
		if ( preg_match( '#^<!--[\s\S]*-->$#i', $chunk, $m ) ) {
			$content_with_links .= $chunk;
			continue;
		}

		if ( preg_match( '#^<(/)?([a-z-]+)\b[^>]*>$#i', $chunk, $m ) ) {
			$tag = strtolower( $m[2] );
			if ( '/' === $m[1] ) {
				// Closing tag.
				$i = array_search( $tag, $tag_stack, true );
				// We can only remove the tag from the stack if it is in the stack.
				if ( false !== $i ) {
					$tag_stack = array_slice( $tag_stack, 0, $i );
				}
			} else {
				// Opening tag, add it to the stack.
				$tag_stack[] = $tag;
			}

			// If we're in a protected tag, the tag_stack contains at least one protected tag string.
			// The protected tag state can only change when we encounter a start or end tag.
			$in_protected_tag = array_intersect( $tag_stack, $protected_tags );

			// Never inspect tags.
			$content_with_links .= $chunk;
			continue;
		}

		if ( $in_protected_tag ) {
			// Don't inspect a chunk inside an inspected tag.
			$content_with_links .= $chunk;
			continue;
		}

		// Only reachable when there is no protected tag in the stack.
		$content_with_links .= \preg_replace_callback( $regex, $regex_callback, $chunk );
	}

	return $content_with_links;
}

/**
 * Generate a summary of a post.
 *
 * This function generates a summary of a post by extracting:
 *
 * 1. The post excerpt if it exists.
 * 2. The first part of the post content if it contains the <!--more--> tag.
 * 3. An excerpt of the post content if it is longer than the specified length.
 *
 * @param int|\WP_Post $post   The post ID or post object.
 * @param integer      $length The maximum length of the summary.
 *                             Default is 500. It will be ignored if the post excerpt
 *                             and the content above the <!--more--> tag.
 *
 * @return string The generated post summary.
 */
function generate_post_summary( $post, $length = 500 ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	$content = \sanitize_post_field( 'post_excerpt', $post->post_excerpt, $post->ID );

	if ( $content ) {
		/** This filter is documented in wp-includes/post-template.php */
		return \apply_filters( 'the_excerpt', $content );
	}

	$content       = \sanitize_post_field( 'post_content', $post->post_content, $post->ID );
	$content_parts = \get_extended( $content );

	/**
	 * Filters the excerpt more value.
	 *
	 * @param string $excerpt_more The excerpt more.
	 */
	$excerpt_more = \apply_filters( 'activitypub_excerpt_more', '[…]' );
	$length       = $length - strlen( $excerpt_more );

	// Check for the <!--more--> tag.
	if (
		! empty( $content_parts['extended'] ) &&
		! empty( $content_parts['main'] )
	) {
		$content = $content_parts['main'] . ' ' . $excerpt_more;
		$length  = null;
	}

	$content = \html_entity_decode( $content );
	$content = \wp_strip_all_tags( $content );
	$content = \trim( $content );
	$content = \preg_replace( '/\R+/m', "\n\n", $content );
	$content = \preg_replace( '/[\r\t]/', '', $content );

	if ( $length && \strlen( $content ) > $length ) {
		$content = \wordwrap( $content, $length, '</activitypub-summary>' );
		$content = \explode( '</activitypub-summary>', $content, 2 );
		$content = $content[0] . ' ' . $excerpt_more;
	}

	/*
	Removed until this is merged: https://github.com/mastodon/mastodon/pull/28629
	/** This filter is documented in wp-includes/post-template.php
	return \apply_filters( 'the_excerpt', $content );
	*/
	return $content;
}

/**
 * Get the content warning of a post.
 *
 * @param int|\WP_Post $post_id The post ID or post object.
 *
 * @return string|false The content warning or false if not found.
 */
function get_content_warning( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return false;
	}

	$warning = get_post_meta( $post->ID, 'activitypub_content_warning', true );
	if ( empty( $warning ) ) {
		return false;
	}

	return $warning;
}

/**
 * Get the ActivityPub ID of a User by the WordPress User ID.
 *
 * @param int $id The WordPress User ID.
 *
 * @return string The ActivityPub ID (a URL) of the User.
 */
function get_user_id( $id ) {
	$user = Actors::get_by_id( $id );

	if ( ! $user ) {
		return false;
	}

	return $user->get_id();
}

/**
 * Get the ActivityPub ID of a Post by the WordPress Post ID.
 *
 * @param int $id The WordPress Post ID.
 *
 * @return string The ActivityPub ID (a URL) of the Post.
 */
function get_post_id( $id ) {
	$post = get_post( $id );

	if ( ! $post ) {
		return false;
	}

	$transformer = new Post( $post );
	return $transformer->get_id();
}

/**
 * Check if a URL is from the same domain as the site.
 *
 * @param string $url The URL to check.
 *
 * @return boolean True if the URL is from the same domain, false otherwise.
 */
function is_same_domain( $url ) {
	$remote = \wp_parse_url( $url, PHP_URL_HOST );

	if ( ! $remote ) {
		return false;
	}

	$remote = normalize_host( $remote );
	$self   = normalize_host( home_host() );

	return $remote === $self;
}

/**
 * Get the visibility of a post.
 *
 * @param int $post_id The post ID.
 *
 * @return string|false The visibility of the post or false if not found.
 */
function get_content_visibility( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return false;
	}

	$visibility  = \get_post_meta( $post->ID, 'activitypub_content_visibility', true );
	$_visibility = ACTIVITYPUB_CONTENT_VISIBILITY_PUBLIC;
	$options     = array(
		ACTIVITYPUB_CONTENT_VISIBILITY_QUIET_PUBLIC,
		ACTIVITYPUB_CONTENT_VISIBILITY_PRIVATE,
		ACTIVITYPUB_CONTENT_VISIBILITY_LOCAL,
	);

	if ( in_array( $visibility, $options, true ) ) {
		$_visibility = $visibility;
	}

	/**
	 * Filters the visibility of a post.
	 *
	 * @param string   $_visibility The visibility of the post. Possible values are:
	 *                              - 'public': Post is public and federated.
	 *                              - 'quiet_public': Post is public but not federated.
	 *                              - 'local': Post is only visible locally.
	 * @param \WP_Post $post        The post object.
	 */
	return \apply_filters( 'activitypub_content_visibility', $_visibility, $post );
}

/**
 * Retrieves the Host for the current site where the front end is accessible.
 *
 * @return string The host for the current site.
 */
function home_host() {
	return \wp_parse_url( \home_url(), PHP_URL_HOST );
}

/**
 * Returns the website hosts allowed to credit this blog.
 *
 * @return array|null The attribution domains or null if not found.
 */
function get_attribution_domains() {
	if ( '1' !== \get_option( 'activitypub_use_opengraph', '1' ) ) {
		return null;
	}

	$domains = \get_option( 'activitypub_attribution_domains', home_host() );
	$domains = explode( PHP_EOL, $domains );

	if ( ! $domains ) {
		$domains = null;
	}

	return $domains;
}

/**
 * Get the base URL for uploads.
 *
 * @return string The upload base URL.
 */
function get_upload_baseurl() {
	/**
	 * Early filter to allow plugins to set the upload base URL.
	 *
	 * @param string|false $maybe_upload_dir The upload base URL or false if not set.
	 */
	$maybe_upload_dir = apply_filters( 'pre_activitypub_get_upload_baseurl', false );
	if ( false !== $maybe_upload_dir ) {
		return $maybe_upload_dir;
	}

	$upload_dir = \wp_get_upload_dir();

	/**
	 * Filters the upload base URL.
	 *
	 * @param string $upload_dir The upload base URL. Default \wp_get_upload_dir()['baseurl']
	 */
	return apply_filters( 'activitypub_get_upload_baseurl', $upload_dir['baseurl'] );
}

/**
 * Check if Authorized-Fetch is enabled.
 *
 * @see https://docs.joinmastodon.org/admin/config/#authorized_fetch
 *
 * @return boolean True if Authorized-Fetch is enabled, false otherwise.
 */
function use_authorized_fetch() {
	$use = false;

	// Prefer the constant over the option.
	if ( \defined( 'ACTIVITYPUB_AUTHORIZED_FETCH' ) ) {
		$use = ACTIVITYPUB_AUTHORIZED_FETCH;
	} else {
		$use = (bool) \get_option( 'activitypub_authorized_fetch', '0' );
	}

	/**
	 * Filters whether to use Authorized-Fetch.
	 *
	 * @param boolean $use_authorized_fetch True if Authorized-Fetch is enabled, false otherwise.
	 */
	return apply_filters( 'activitypub_use_authorized_fetch', $use );
}

/**
 * Check if an ID is from the same domain as the site.
 *
 * @param string $id The ID URI to check.
 *
 * @return boolean True if the ID is a self-pint, false otherwise.
 */
function is_self_ping( $id ) {
	$query_string = \wp_parse_url( $id, PHP_URL_QUERY );

	if ( ! $query_string ) {
		return false;
	}

	$query = array();
	\parse_str( $query_string, $query );

	if (
		is_same_domain( $id ) &&
		in_array( 'c', array_keys( $query ), true )
	) {
		return true;
	}

	return false;
}

/**
 * Add an object to the outbox.
 *
 * @param mixed   $data               The object to add to the outbox.
 * @param string  $activity_type      The type of the Activity.
 * @param integer $user_id            The User-ID.
 * @param string  $content_visibility The visibility of the content. See `constants.php` for possible values: `ACTIVITYPUB_CONTENT_VISIBILITY_*`.
 *
 * @return boolean|int The ID of the outbox item or false on failure.
 */
function add_to_outbox( $data, $activity_type = 'Create', $user_id = 0, $content_visibility = null ) {
	$transformer = Transformer_Factory::get_transformer( $data );

	if ( ! $transformer || is_wp_error( $transformer ) ) {
		return false;
	}

	if ( $content_visibility ) {
		$transformer->set_content_visibility( $content_visibility );
	} else {
		$content_visibility = $transformer->get_content_visibility();
	}

	$activity_object = $transformer->to_object();

	if ( ! $activity_object || \is_wp_error( $activity_object ) ) {
		return false;
	}

	// If the user is disabled, fall back to the blog user when available.
	if ( is_user_disabled( $user_id ) ) {
		if ( is_user_disabled( Actors::BLOG_USER_ID ) ) {
			return false;
		} else {
			$user_id = Actors::BLOG_USER_ID;
		}
	}

	$outbox_activity_id = Outbox::add( $activity_object, $activity_type, $user_id, $content_visibility );

	if ( ! $outbox_activity_id ) {
		return false;
	}

	/**
	 * Action triggered after an object has been added to the outbox.
	 *
	 * @param int                               $outbox_activity_id The ID of the outbox item.
	 * @param \Activitypub\Activity\Base_Object $activity_object    The activity object.
	 * @param int                               $user_id            The User-ID.
	 * @param string                            $content_visibility The visibility of the content. See `constants.php` for possible values: `ACTIVITYPUB_CONTENT_VISIBILITY_*`.
	 */
	\do_action( 'post_activitypub_add_to_outbox', $outbox_activity_id, $activity_object, $user_id, $content_visibility );

	set_wp_object_state( $data, 'federated' );

	return $outbox_activity_id;
}

/**
 * Check if an `$data` is an Activity.
 *
 * @see https://www.w3.org/ns/activitystreams#activities
 *
 * @param array|object|string $data The data to check.
 *
 * @return boolean True if the `$data` is an Activity, false otherwise.
 */
function is_activity( $data ) {
	/**
	 * Filters the activity types.
	 *
	 * @param array $types The activity types.
	 */
	$types = apply_filters(
		'activitypub_activity_types',
		array(
			'Accept',
			'Add',
			'Announce',
			'Arrive',
			'Block',
			'Create',
			'Delete',
			'Dislike',
			'Follow',
			'Flag',
			'Ignore',
			'Invite',
			'Join',
			'Leave',
			'Like',
			'Listen',
			'Move',
			'Offer',
			'Read',
			'Reject',
			'Remove',
			'TentativeAccept',
			'TentativeReject',
			'Travel',
			'Undo',
			'Update',
			'View',
		)
	);

	if ( is_string( $data ) ) {
		return in_array( $data, $types, true );
	}

	if ( is_array( $data ) && isset( $data['type'] ) ) {
		return in_array( $data['type'], $types, true );
	}

	if ( is_object( $data ) && $data instanceof Base_Object ) {
		return in_array( $data->get_type(), $types, true );
	}

	return false;
}

/**
 * Check if an `$data` is an Actor.
 *
 * @see https://www.w3.org/ns/activitystreams#actor
 *
 * @param array|object|string $data The data to check.
 *
 * @return boolean True if the `$data` is an Actor, false otherwise.
 */
function is_actor( $data ) {
	/**
	 * Filters the actor types.
	 *
	 * @param array $types The actor types.
	 */
	$types = apply_filters(
		'activitypub_actor_types',
		array(
			'Application',
			'Group',
			'Organization',
			'Person',
			'Service',
		)
	);

	if ( is_string( $data ) ) {
		return in_array( $data, $types, true );
	}

	if ( is_array( $data ) && isset( $data['type'] ) ) {
		return in_array( $data['type'], $types, true );
	}

	if ( is_object( $data ) && $data instanceof Base_Object ) {
		return in_array( $data->get_type(), $types, true );
	}

	return false;
}
