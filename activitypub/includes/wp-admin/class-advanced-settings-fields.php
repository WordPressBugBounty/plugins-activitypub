<?php
/**
 * Advanced Settings Fields file.
 *
 * @package ActivityPub
 */

namespace ActivityPub\WP_Admin;

/**
 * Advanced Settings Fields class.
 */
class Advanced_Settings_Fields {

	/**
	 * Initialize.
	 */
	public static function init() {
		\add_action( 'load-settings_page_activitypub', array( self::class, 'register_advanced_fields' ) );
	}

	/**
	 * Register settings.
	 */
	public static function register_advanced_fields() {
		\add_settings_section(
			'activitypub_advanced_settings',
			\__( 'Advanced Settings', 'activitypub' ),
			array( self::class, 'render_advanced_settings_section' ),
			'activitypub_advanced_settings'
		);

		\add_settings_field(
			'activitypub_outbox_purge_days',
			\__( 'Outbox Retention Period', 'activitypub' ),
			array( self::class, 'render_outbox_purge_days_field' ),
			'activitypub_advanced_settings',
			'activitypub_advanced_settings',
			array( 'label_for' => 'activitypub_outbox_purge_days' )
		);

		if ( ! defined( 'ACTIVITYPUB_SEND_VARY_HEADER' ) ) {
			\add_settings_field(
				'activitypub_vary_header',
				\__( 'Vary Header', 'activitypub' ),
				array( self::class, 'render_vary_header_field' ),
				'activitypub_advanced_settings',
				'activitypub_advanced_settings',
				array( 'label_for' => 'activitypub_vary_header' )
			);
		}

		if ( ! defined( 'ACTIVITYPUB_AUTHORIZED_FETCH' ) ) {
			\add_settings_field(
				'activitypub_authorized_fetch',
				\__( 'Authorized Fetch', 'activitypub' ),
				array( self::class, 'render_authorized_fetch_field' ),
				'activitypub_advanced_settings',
				'activitypub_advanced_settings',
				array( 'label_for' => 'activitypub_authorized_fetch' )
			);
		}

		if ( ! defined( 'ACTIVITYPUB_SHARED_INBOX_FEATURE' ) ) {
			\add_settings_field(
				'activitypub_shared_inbox',
				\__( 'Shared Inbox (beta)', 'activitypub' ),
				array( self::class, 'render_shared_inbox_field' ),
				'activitypub_advanced_settings',
				'activitypub_advanced_settings',
				array( 'label_for' => 'activitypub_shared_inbox' )
			);
		}
	}

	/**
	 * Render Advanced Settings Section.
	 */
	public static function render_advanced_settings_section() {
		?>
		<p>
			<?php
			$allowed_html = array(
				'a' => array(
					'href'   => true,
					'target' => true,
				),
			);
			echo \wp_kses( \__( 'Advanced settings allow deep customization but can affect your site&#8217;s functionality, security, or performance if misconfigured. Only proceed if you fully understand the changes, and always back up your site beforehand. If unsure, consult <a href="https://github.com/Automattic/wordpress-activitypub/tree/trunk/docs" target="_blank">documentation</a> or seek <a href="https://wordpress.org/support/plugin/activitypub/" target="_blank">expert advice</a>.', 'activitypub' ), $allowed_html );
			?>
		</p>
		<?php
	}

	/**
	 * Render outbox purge days field.
	 */
	public static function render_outbox_purge_days_field() {
		$value = \get_option( 'activitypub_outbox_purge_days', 180 );
		echo '<input type="number" id="activitypub_outbox_purge_days" name="activitypub_outbox_purge_days" value="' . esc_attr( $value ) . '" class="small-text" min="0" max="365" />';
		echo '<p class="description">' . \wp_kses(
			sprintf(
				// translators: 1: Definition of Outbox; 2: Default value (180).
				\__( 'Maximum number of days to keep items in the <abbr title="%1$s">Outbox</abbr>. A lower value might be better for sites with lots of activity to maintain site performance. Default: <code>%2$s</code>', 'activitypub' ),
				\esc_attr__( 'A virtual location on a user&#8217;s profile where all the activities (posts, likes, replies) they publish are stored, acting as a feed that other users can access to see their publicly shared content', 'activitypub' ),
				\esc_html( 180 )
			),
			array(
				'abbr' => array( 'title' => array() ),
				'code' => array(),
			)
		) . '</p>';
	}

	/**
	 * Render vary header field.
	 */
	public static function render_vary_header_field() {
		$value = \get_option( 'activitypub_vary_header', '0' );
		?>
		<p>
			<label>
				<input type="checkbox" id="activitypub_vary_header" name="activitypub_vary_header" value="1" <?php checked( '1', $value ); ?> />
				<?php echo \wp_kses( \__( 'Help prevent incorrect caching of ActivityPub responses.', 'activitypub' ), array( 'code' => array() ) ); ?>
			</label>
		</p>
		<p class="description">
			<?php \esc_html_e( 'Enable this if you notice your site showing technical content instead of normal web pages, or if your ActivityPub connections seem unreliable. This setting helps your site deliver the right format of content to different services automatically.', 'activitypub' ); ?>
		</p>
		<?php
	}

	/**
	 * Render use Authorized Fetch field.
	 */
	public static function render_authorized_fetch_field() {
		$value = \get_option( 'activitypub_authorized_fetch', '0' );
		?>
		<p>
			<label>
				<input type="checkbox" id="activitypub_authorized_fetch" name="activitypub_authorized_fetch" value="1" <?php checked( '1', $value ); ?> />
				<?php \esc_html_e( 'Require HTTP signature authentication on ActivityPub representations of public posts and profiles.', 'activitypub' ); ?>
			</label>
		</p>
		<p class="description">
			<?php \esc_html_e( '⚠ Secure mode has its limitations, which is why it is not enabled by default. It is not fully supported by all software in the fediverse, and some features may break, especially when interacting with Mastodon servers older than version 3.0. Additionally, since it requires authentication for public content, caching is not possible, leading to higher computational costs.', 'activitypub' ); ?>
		</p>
		<p class="description">
			<?php \esc_html_e( '⚠ Secure mode does not hide the HTML representations of public posts and profiles. While HTML is a less consistent format (that potentially changes often) compared to first-class ActivityPub representations or the REST API, it still poses a potential risk for content scraping.', 'activitypub' ); ?>
		</p>
		<?php
	}

	/**
	 * Render shared inbox field.
	 */
	public static function render_shared_inbox_field() {
		$value = \get_option( 'activitypub_shared_inbox', '0' );
		?>
		<p>
			<label>
				<input type="checkbox" id="activitypub_shared_inbox" name="activitypub_shared_inbox" value="1" <?php checked( '1', $value ); ?> />
				<?php \esc_html_e( 'Use a shared inbox for incoming messages.', 'activitypub' ); ?>
			</label>
		</p>
		<p class="description">
			<?php \esc_html_e( 'Allows your site to handle incoming ActivityPub messages more efficiently, especially helpful for busy or multi-user sites. This feature is still in beta and may encounter issues.', 'activitypub' ); ?>
		</p>
		<?php
	}
}
