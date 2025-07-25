<?php
/**
 * Followers Table-Class file.
 *
 * @package Activitypub
 */

namespace Activitypub\WP_Admin\Table;

use Activitypub\Collection\Actors;
use Activitypub\Collection\Followers as Follower_Collection;
use Activitypub\Collection\Following;
use Activitypub\Sanitize;
use Activitypub\Webfinger;

use function Activitypub\object_to_uri;

if ( ! \class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Followers Table-Class.
 */
class Followers extends \WP_List_Table {
	use Actor_List_Table;

	/**
	 * User ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Follow URL.
	 *
	 * @var string
	 */
	public $follow_url;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( get_current_screen()->id === 'settings_page_activitypub' ) {
			$this->user_id    = Actors::BLOG_USER_ID;
			$this->follow_url = \admin_url( 'options-general.php?page=activitypub&tab=following' );
		} else {
			$this->user_id    = \get_current_user_id();
			$this->follow_url = \admin_url( 'users.php?page=activitypub-following' );

			\add_action( 'admin_notices', array( $this, 'process_admin_notices' ) );
		}

		parent::__construct(
			array(
				'singular' => \__( 'Follower', 'activitypub' ),
				'plural'   => \__( 'Followers', 'activitypub' ),
				'ajax'     => false,
			)
		);

		\add_action( 'load-' . get_current_screen()->id, array( $this, 'process_action' ), 20 );
	}

	/**
	 * Process action.
	 */
	public function process_action() {
		if ( ! \current_user_can( 'edit_user', $this->user_id ) ) {
			return;
		}

		if ( ! $this->current_action() ) {
			return;
		}

		$redirect_to = \add_query_arg(
			array(
				'settings-updated' => true,  // Tell WordPress to load settings errors transient.
				'action'           => false, // Remove action parameter to prevent redirect loop.
			)
		);

		switch ( $this->current_action() ) {
			case 'delete':
				$redirect_to = \remove_query_arg( array( 'follower', 'followers' ), $redirect_to );

				// Handle single follower deletion.
				if ( isset( $_GET['follower'], $_GET['_wpnonce'] ) ) {
					$follower = \absint( $_GET['follower'] );
					$nonce    = \sanitize_text_field( \wp_unslash( $_GET['_wpnonce'] ) );

					if ( \wp_verify_nonce( $nonce, 'delete-follower_' . $follower ) ) {
						Follower_Collection::remove( $follower, $this->user_id );

						\add_settings_error( 'activitypub', 'follower_deleted', \__( 'Follower deleted.', 'activitypub' ), 'success' );
					}
				}

				// Handle bulk actions.
				if ( isset( $_REQUEST['followers'], $_REQUEST['_wpnonce'] ) ) {
					$nonce = \sanitize_text_field( \wp_unslash( $_REQUEST['_wpnonce'] ) );

					if ( \wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
						$followers = \array_map( 'absint', \wp_unslash( $_REQUEST['followers'] ) );
						foreach ( $followers as $follower ) {
							Follower_Collection::remove( $follower, $this->user_id );
						}

						$count = \count( $followers );
						/* translators: %d: Number of followers deleted. */
						$message = \_n( '%d follower deleted.', '%d followers deleted.', $count, 'activitypub' );
						$message = \sprintf( $message, \number_format_i18n( $count ) );

						\add_settings_error( 'activitypub', 'followers_deleted', $message, 'success' );
					}
				}
				break;

			default:
				break;
		}

		\set_transient( 'settings_errors', get_settings_errors(), 30 ); // 30 seconds.

		\wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Process admin notices based on query parameters.
	 */
	public function process_admin_notices() {
		\settings_errors( 'activitypub' );
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'username'   => \esc_html__( 'Username', 'activitypub' ),
			'post_title' => \esc_html__( 'Name', 'activitypub' ),
			'webfinger'  => \esc_html__( 'Profile', 'activitypub' ),
			'modified'   => \esc_html__( 'Last updated', 'activitypub' ),
		);
	}

	/**
	 * Returns sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'username'   => array( 'username', true ),
			'post_title' => array( 'post_title', true ),
			'modified'   => array( 'modified', false ),
		);
	}

	/**
	 * Prepare items.
	 */
	public function prepare_items() {
		$page_num = $this->get_pagenum();
		$per_page = $this->get_items_per_page( 'activitypub_followers_per_page' );
		$args     = array();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['orderby'] ) ) {
			$args['orderby'] = \sanitize_text_field( \wp_unslash( $_GET['orderby'] ) );
		}

		if ( isset( $_GET['order'] ) ) {
			$args['order'] = \sanitize_text_field( \wp_unslash( $_GET['order'] ) );
		}

		if ( ! empty( $_GET['s'] ) ) {
			$args['s'] = self::normalize_search_term( \wp_unslash( $_GET['s'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		$followers_with_count = Follower_Collection::get_followers_with_count( $this->user_id, $per_page, $page_num, $args );
		$followers            = $followers_with_count['followers'];
		$counter              = $followers_with_count['total'];

		$this->items = array();
		$this->set_pagination_args(
			array(
				'total_items' => $counter,
				'total_pages' => ceil( $counter / $per_page ),
				'per_page'    => $per_page,
			)
		);

		foreach ( $followers as $follower ) {
			$actor = Actors::get_actor( $follower );

			if ( \is_wp_error( $actor ) ) {
				continue;
			}

			$url = object_to_uri( $actor->get_url() ?? $actor->get_id() );

			$this->items[] = array(
				'id'         => $follower->ID,
				'icon'       => $actor->get_icon()['url'] ?? '',
				'post_title' => $actor->get_name() ?? $actor->get_preferred_username(),
				'username'   => $actor->get_preferred_username(),
				'url'        => $url,
				'webfinger'  => self::get_webfinger( $actor ),
				'identifier' => $actor->get_id(),
				'modified'   => $follower->post_modified_gmt,
			);
		}
	}

	/**
	 * Returns views.
	 *
	 * @return string[]
	 */
	public function get_views() {
		$count = Follower_Collection::count_followers( $this->user_id );

		$path = 'users.php?page=activitypub-followers-list';
		if ( Actors::BLOG_USER_ID === $this->user_id ) {
			$path = 'options-general.php?page=activitypub&tab=followers';
		}

		$links = array(
			'all' => array(
				'url'     => admin_url( $path ),
				'label'   => sprintf(
					/* translators: %s: Number of users. */
					\_nx(
						'All <span class="count">(%s)</span>',
						'All <span class="count">(%s)</span>',
						$count,
						'users',
						'activitypub'
					),
					number_format_i18n( $count )
				),
				'current' => true,
			),
		);

		return $this->get_views_links( $links );
	}

	/**
	 * Returns bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => \__( 'Delete', 'activitypub' ),
		);
	}

	/**
	 * Column default.
	 *
	 * @param array  $item        Item.
	 * @param string $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		if ( ! array_key_exists( $column_name, $item ) ) {
			return \esc_html__( 'None', 'activitypub' );
		}

		return \esc_html( $item[ $column_name ] );
	}

	/**
	 * Column cb.
	 *
	 * @param array $item Item.
	 * @return string
	 */
	public function column_cb( $item ) {
		return \sprintf( '<input type="checkbox" name="followers[]" value="%s" />', \esc_attr( $item['id'] ) );
	}

	/**
	 * Column username.
	 *
	 * @param array $item Item.
	 * @return string
	 */
	public function column_username( $item ) {
		return \sprintf(
			'<img src="%1$s" width="32" height="32" alt="%2$s" loading="lazy"/> <strong><a href="%3$s" target="_blank">%4$s</a></strong><br />',
			\esc_url( $item['icon'] ),
			\esc_attr( $item['username'] ),
			\esc_url( $item['url'] ),
			\esc_html( $item['username'] )
		);
	}

	/**
	 * Column webfinger.
	 *
	 * @param array $item Item.
	 * @return string
	 */
	public function column_webfinger( $item ) {
		$webfinger = Sanitize::webfinger( $item['webfinger'] );

		return \sprintf(
			'<a href="%1$s" target="_blank" title="%1$s">@%2$s</a>',
			\esc_url( $item['url'] ),
			\esc_html( $webfinger )
		);
	}

	/**
	 * Column modified.
	 *
	 * @param array $item Item.
	 * @return string
	 */
	public function column_modified( $item ) {
		$modified = \strtotime( $item['modified'] );
		return \sprintf(
			'<time datetime="%1$s">%2$s</time>',
			\esc_attr( \gmdate( 'c', $modified ) ),
			\esc_html( \gmdate( \get_option( 'date_format' ), $modified ) )
		);
	}

	/**
	 * Message to be displayed when there are no followers.
	 */
	public function no_items() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search         = \sanitize_text_field( \wp_unslash( $_GET['s'] ?? '' ) );
		$actor_or_false = $this->_is_followable( $search );

		if ( $actor_or_false ) {
			\printf(
				/* translators: %s: Actor name. */
				\esc_html__( '%1$s is not following you, would you like to %2$s instead?', 'activitypub' ),
				\esc_html( $actor_or_false->post_title ),
				\sprintf(
					'<a href="%s">%s</a>',
					\esc_url( \add_query_arg( 'resource', $search, $this->follow_url ) ),
					\esc_html__( 'follow them', 'activitypub' )
				)
			);
		} else {
			\esc_html_e( 'No followers found.', 'activitypub' );
		}
	}

	/**
	 * Handles the row actions for each follower item.
	 *
	 * @param array  $item        The current follower item.
	 * @param string $column_name The current column name.
	 * @param string $primary     The primary column name.
	 * @return string HTML for the row actions.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $column_name !== $primary ) {
			return '';
		}

		$actions = array(
			'delete' => sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				\wp_nonce_url(
					\add_query_arg(
						array(
							'action'   => 'delete',
							'follower' => $item['id'],
						)
					),
					'delete-follower_' . $item['id']
				),
				/* translators: %s: username. */
				\esc_attr( \sprintf( \__( 'Delete %s', 'activitypub' ), $item['username'] ) ),
				\esc_html__( 'Delete', 'activitypub' )
			),
		);

		return $this->row_actions( $actions );
	}

	/**
	 * Checks if the searched actor can be followed.
	 *
	 * @param string $search The search string.
	 *
	 * @return \WP_Post|false The actor post or false.
	 */
	private function _is_followable( $search ) { // phpcs:ignore
		if ( empty( $search ) ) {
			return false;
		}

		$search = Sanitize::webfinger( $search );
		if ( ! \filter_var( $search, FILTER_VALIDATE_EMAIL ) ) {
			return false;
		}

		$search = Webfinger::resolve( $search );
		if ( \is_wp_error( $search ) || ! \filter_var( $search, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$actor = Actors::fetch_remote_by_uri( $search );
		if ( \is_wp_error( $actor ) ) {
			return false;
		}

		$does_follow = Following::check_status( $this->user_id, $actor->ID );
		if ( $does_follow ) {
			return false;
		}

		return $actor;
	}
}
