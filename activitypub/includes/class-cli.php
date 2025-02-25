<?php
/**
 * WP-CLI file.
 *
 * @package Activitypub
 */

namespace Activitypub;

use Activitypub\Collection\Outbox;
use Activitypub\Scheduler\Comment;
use Activitypub\Scheduler\Post;
use WP_CLI;
use WP_CLI_Command;

/**
 * WP-CLI commands.
 *
 * @package Activitypub
 */
class Cli extends WP_CLI_Command {

	/**
	 * Remove the entire blog from the Fediverse.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp activitypub self-destruct
	 *
	 * @param array|null $args       The arguments.
	 * @param array|null $assoc_args The associative arguments.
	 *
	 * @return void
	 */
	public function self_destruct( $args, $assoc_args ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		WP_CLI::warning( 'Self-Destructing is not implemented yet.' );
	}

	/**
	 * Delete or Update a Post, Page, Custom Post Type or Attachment.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : The action to perform. Either `delete` or `update`.
	 * ---
	 * options:
	 *   - delete
	 *   - update
	 * ---
	 *
	 * <id>
	 * : The id of the Post, Page, Custom Post Type or Attachment.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp activitypub post delete 1
	 *
	 * @synopsis <action> <id>
	 *
	 * @param array $args The arguments.
	 */
	public function post( $args ) {
		$post = get_post( $args[1] );

		if ( ! $post ) {
			WP_CLI::error( 'Post not found.' );
		}

		switch ( $args[0] ) {
			case 'delete':
				WP_CLI::confirm( 'Do you really want to delete the (Custom) Post with the ID: ' . $args[1] );
				Post::schedule_post_activity( 'trash', 'publish', $post );
				WP_CLI::success( '"Delete" activity is queued.' );
				break;
			case 'update':
				Post::schedule_post_activity( 'publish', 'publish', $post );
				WP_CLI::success( '"Update" activity is queued.' );
				break;
			default:
				WP_CLI::error( 'Unknown action.' );
		}
	}

	/**
	 * Delete or Update a Comment.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : The action to perform. Either `delete` or `update`.
	 * ---
	 * options:
	 *   - delete
	 *   - update
	 * ---
	 *
	 * <id>
	 * : The id of the Comment.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp activitypub comment delete 1
	 *
	 * @synopsis <action> <id>
	 *
	 * @param array $args The arguments.
	 */
	public function comment( $args ) {
		$comment = get_comment( $args[1] );

		if ( ! $comment ) {
			WP_CLI::error( 'Comment not found.' );
		}

		if ( was_comment_received( $comment ) ) {
			WP_CLI::error( 'This comment was received via ActivityPub and cannot be deleted or updated.' );
		}

		switch ( $args[0] ) {
			case 'delete':
				WP_CLI::confirm( 'Do you really want to delete the Comment with the ID: ' . $args[1] );
				Comment::schedule_comment_activity( 'trash', 'approved', $comment );
				WP_CLI::success( '"Delete" activity is queued.' );
				break;
			case 'update':
				Comment::schedule_comment_activity( 'approved', 'approved', $comment );
				WP_CLI::success( '"Update" activity is queued.' );
				break;
			default:
				WP_CLI::error( 'Unknown action.' );
		}
	}

	/**
	 * Undo an activity that was sent to the Fediverse.
	 *
	 * ## OPTIONS
	 *
	 * <outbox_item_id>
	 *     The ID or URL of the outbox item to undo.
	 *
	 * ## EXAMPLES
	 *
	 *    $ wp activitypub undo 123
	 *    $ wp activitypub undo "https://example.com/?post_type=ap_outbox&p=123"
	 *
	 * @synopsis <outbox_item_id>
	 *
	 * @param array $args The arguments.
	 */
	public function undo( $args ) {
		$outbox_item_id = $args[0];
		if ( ! is_numeric( $outbox_item_id ) ) {
			$outbox_item_id = url_to_postid( $outbox_item_id );
		}

		$outbox_item_id = get_post( $outbox_item_id );
		if ( ! $outbox_item_id ) {
			WP_CLI::error( 'Activity not found.' );
		}

		$undo_id = Outbox::undo( $outbox_item_id );
		if ( ! $undo_id ) {
			WP_CLI::error( 'Failed to undo activity.' );
		}
		WP_CLI::success( 'Undo activity scheduled.' );
	}

	/**
	 * Re-Schedule an activity that was sent to the Fediverse before.
	 *
	 * ## OPTIONS
	 *
	 * <outbox_item_id>
	 *     The ID or URL of the outbox item to reschedule.
	 *
	 * ## EXAMPLES
	 *
	 *    $ wp activitypub reschedule 123
	 *    $ wp activitypub reschedule "https://example.com/?post_type=ap_outbox&p=123"
	 *
	 * @synopsis <outbox_item_id>
	 *
	 * @param array $args The arguments.
	 */
	public function reschedule( $args ) {
		$outbox_item_id = $args[0];
		if ( ! is_numeric( $outbox_item_id ) ) {
			$outbox_item_id = url_to_postid( $outbox_item_id );
		}

		$outbox_item_id = get_post( $outbox_item_id );
		if ( ! $outbox_item_id ) {
			WP_CLI::error( 'Activity not found.' );
		}

		Outbox::reschedule( $outbox_item_id );

		WP_CLI::success( 'Rescheduled activity.' );
	}
}
