<?php
/**
 * ActivityPub Settings Fields Class.
 *
 * @package Activitypub
 */

namespace Activitypub\WP_Admin;

/**
 * Class Settings_Fields.
 */
class Settings_Fields {
	/**
	 * Initialize the settings fields.
	 */
	public static function init() {
		add_action( 'load-settings_page_activitypub', array( self::class, 'register_settings_fields' ) );
	}

	/**
	 * Register settings fields.
	 */
	public static function register_settings_fields() {
		// Add settings sections.
		add_settings_section(
			'activitypub_profiles',
			__( 'Profiles', 'activitypub' ),
			'__return_empty_string',
			'activitypub_settings'
		);

		add_settings_section(
			'activitypub_activities',
			__( 'Activities', 'activitypub' ),
			'__return_empty_string',
			'activitypub_settings'
		);

		add_settings_section(
			'activitypub_notifications',
			__( 'Notifications', 'activitypub' ),
			array( self::class, 'render_notifications_section' ),
			'activitypub_settings'
		);

		add_settings_section(
			'activitypub_general',
			__( 'General', 'activitypub' ),
			'__return_empty_string',
			'activitypub_settings'
		);

		// Add settings fields.
		add_settings_field(
			'activitypub_actor_mode',
			__( 'Enable profiles by type', 'activitypub' ),
			array( self::class, 'render_actor_mode_field' ),
			'activitypub_settings',
			'activitypub_profiles'
		);

		add_settings_field(
			'activitypub_object_type',
			__( 'Activity-Object-Type', 'activitypub' ),
			array( self::class, 'render_object_type_field' ),
			'activitypub_settings',
			'activitypub_activities'
		);

		$object_type = \get_option( 'activitypub_object_type', ACTIVITYPUB_DEFAULT_OBJECT_TYPE );
		if ( 'note' === $object_type ) {
			add_settings_field(
				'activitypub_custom_post_content',
				__( 'Post content', 'activitypub' ),
				array( self::class, 'render_custom_post_content_field' ),
				'activitypub_settings',
				'activitypub_activities',
				array( 'label_for' => 'activitypub_custom_post_content' )
			);
		}

		add_settings_field(
			'activitypub_max_image_attachments',
			__( 'Media attachments', 'activitypub' ),
			array( self::class, 'render_max_image_attachments_field' ),
			'activitypub_settings',
			'activitypub_activities',
			array( 'label_for' => 'activitypub_max_image_attachments' )
		);

		add_settings_field(
			'activitypub_support_post_types',
			__( 'Supported post types', 'activitypub' ),
			array( self::class, 'render_support_post_types_field' ),
			'activitypub_settings',
			'activitypub_activities'
		);

		add_settings_field(
			'activitypub_use_hashtags',
			__( 'Hashtags', 'activitypub' ),
			array( self::class, 'render_use_hashtags_field' ),
			'activitypub_settings',
			'activitypub_activities',
			array( 'label_for' => 'activitypub_use_hashtags' )
		);

		add_settings_field(
			'activitypub_use_opengraph',
			__( 'OpenGraph', 'activitypub' ),
			array( self::class, 'render_use_opengraph_field' ),
			'activitypub_settings',
			'activitypub_general',
			array( 'label_for' => 'activitypub_use_opengraph' )
		);

		add_settings_field(
			'activitypub_attribution_domains',
			__( 'Attribution Domains', 'activitypub' ),
			array( self::class, 'render_attribution_domains_field' ),
			'activitypub_settings',
			'activitypub_general',
			array( 'label_for' => 'activitypub_attribution_domains' )
		);

		add_settings_field(
			'activitypub_blocklist',
			__( 'Blocklist', 'activitypub' ),
			array( self::class, 'render_blocklist_field' ),
			'activitypub_settings',
			'activitypub_general'
		);

		add_settings_field(
			'activitypub_outbox_purge_days',
			__( 'Outbox Retention Period', 'activitypub' ),
			array( self::class, 'render_outbox_purge_days_field' ),
			'activitypub_settings',
			'activitypub_general',
			array( 'label_for' => 'activitypub_outbox_purge_days' )
		);

		if ( ! defined( 'ACTIVITYPUB_AUTHORIZED_FETCH' ) ) {
			add_settings_section(
				'activitypub_security',
				__( 'Security', 'activitypub' ),
				'__return_empty_string',
				'activitypub_settings'
			);

			add_settings_field(
				'activitypub_authorized_fetch',
				__( 'Authorized Fetch', 'activitypub' ),
				array( self::class, 'render_authorized_fetch_field' ),
				'activitypub_settings',
				'activitypub_security',
				array( 'label_for' => 'activitypub_authorized_fetch' )
			);
		}
	}

	/**
	 * Render notifications section.
	 */
	public static function render_notifications_section() {
		?>
		<p>
			<?php \esc_html_e( 'Choose which notifications you want to receive. The plugin currently only supports e-mail notifications, but we will add more options in the future.', 'activitypub' ); ?>
		</p>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="col">
					<?php \esc_html_e( 'Type', 'activitypub' ); ?>
				</th>
				<th scope="col">
					<?php \esc_html_e( 'E-Mail', 'activitypub' ); ?>
				</th>
			</tr>
			<tr>
				<td>
					<?php \esc_html_e( 'New followers', 'activitypub' ); ?>
				</td>
				<td>
					<label>
						<input type="checkbox" name="activitypub_mailer_new_follower" id="activitypub_mailer_new_follower" value="1" <?php \checked( '1', \get_option( 'activitypub_mailer_new_follower', '0' ) ); ?> />
					</label>
				</td>
			</tr>
			<tr>
				<td>
					<?php \esc_html_e( 'Direct Messages', 'activitypub' ); ?>
				</td>
				<td>
					<label>
						<input type="checkbox" name="activitypub_mailer_new_dm" id="activitypub_mailer_new_dm" value="1" <?php \checked( '1', \get_option( 'activitypub_mailer_new_dm', '0' ) ); ?> />
					</label>
				</td>
			</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render actor mode field.
	 */
	public static function render_actor_mode_field() {
		$value = get_option( 'activitypub_actor_mode', ACTIVITYPUB_ACTOR_MODE );
		?>
		<p>
			<label>
				<input type="radio" name="activitypub_actor_mode" value="<?php echo esc_attr( ACTIVITYPUB_ACTOR_MODE ); ?>" <?php checked( ACTIVITYPUB_ACTOR_MODE, $value ); ?> />
				<strong><?php esc_html_e( 'Author Profiles Only', 'activitypub' ); ?></strong>
			</label>
		</p>
		<p class="description">
			<?php echo wp_kses( __( 'Every author on this blog (with the <code>activitypub</code> capability) gets their own ActivityPub profile.', 'activitypub' ), array( 'code' => array() ) ); ?>
			<strong>
			<?php
			echo wp_kses(
				sprintf(
					// translators: %s is a URL.
					__( 'You can add/remove the capability in the <a href="%s">user settings.</a>', 'activitypub' ),
					admin_url( '/users.php' )
				),
				array( 'a' => array( 'href' => array() ) )
			);
			?>
			</strong>
			<?php echo wp_kses( __( 'Select all the users you want to update, choose the method from the drop-down list and click on the "Apply" button.', 'activitypub' ), array( 'code' => array() ) ); ?>
		</p>
		<p>
			<label>
				<input type="radio" name="activitypub_actor_mode" value="<?php echo esc_attr( ACTIVITYPUB_BLOG_MODE ); ?>" <?php checked( ACTIVITYPUB_BLOG_MODE, $value ); ?> />
				<strong><?php esc_html_e( 'Blog profile only', 'activitypub' ); ?></strong>
			</label>
		</p>
		<p class="description">
			<?php esc_html_e( 'Your blog becomes a single ActivityPub profile and every post will be published under this profile instead of the individual author profiles.', 'activitypub' ); ?>
		</p>
		<p>
			<label>
				<input type="radio" name="activitypub_actor_mode" value="<?php echo esc_attr( ACTIVITYPUB_ACTOR_AND_BLOG_MODE ); ?>" <?php checked( ACTIVITYPUB_ACTOR_AND_BLOG_MODE, $value ); ?> />
				<strong><?php esc_html_e( 'Both author and blog profiles', 'activitypub' ); ?></strong>
			</label>
		</p>
		<p class="description">
			<?php esc_html_e( "This combines both modes. Users can be followed individually, while following the blog will show boosts of individual user's posts.", 'activitypub' ); ?>
		</p>
		<?php
	}

	/**
	 * Render object type field.
	 */
	public static function render_object_type_field() {
		$value = get_option( 'activitypub_object_type', ACTIVITYPUB_DEFAULT_OBJECT_TYPE );
		?>
		<p>
			<label>
				<input type="radio" name="activitypub_object_type" value="wordpress-post-format" <?php checked( 'wordpress-post-format', $value ); ?> />
				<?php esc_html_e( 'Automatic (default)', 'activitypub' ); ?>
				-
				<span class="description">
					<?php esc_html_e( 'Let the plugin choose the best possible format for you.', 'activitypub' ); ?>
				</span>
			</label>
		</p>
		<p>
			<label>
				<input type="radio" name="activitypub_object_type" value="note" <?php checked( 'note', $value ); ?> />
				<?php esc_html_e( 'Note', 'activitypub' ); ?>
				-
				<span class="description">
					<?php esc_html_e( 'Should work with most platforms.', 'activitypub' ); ?>
				</span>
			</label>
		</p>
		<?php
	}

	/**
	 * Render custom post content field.
	 */
	public static function render_custom_post_content_field() {
		$value = get_option( 'activitypub_custom_post_content', ACTIVITYPUB_CUSTOM_POST_CONTENT );
		?>
		<p><strong><?php esc_html_e( 'These settings only apply if you use the "Note" Object-Type setting above.', 'activitypub' ); ?></strong></p>
		<p>
			<textarea id="activitypub_custom_post_content" name="activitypub_custom_post_content" rows="10" cols="50" class="large-text" placeholder="<?php echo esc_attr( ACTIVITYPUB_CUSTOM_POST_CONTENT ); ?>"><?php echo esc_textarea( wp_kses( $value, 'post' ) ); ?></textarea>
			<details>
				<summary><?php esc_html_e( 'See a list of ActivityPub Template Tags.', 'activitypub' ); ?></summary>
				<div class="description">
					<ul>
						<li><code>[ap_title]</code> - <?php esc_html_e( 'The post&#8217;s title.', 'activitypub' ); ?></li>
						<li><code>[ap_content]</code> - <?php esc_html_e( 'The post&#8217;s content.', 'activitypub' ); ?></li>
						<li><code>[ap_excerpt]</code> - <?php esc_html_e( 'The post&#8217;s excerpt (may be truncated).', 'activitypub' ); ?></li>
						<li><code>[ap_permalink]</code> - <?php esc_html_e( 'The post&#8217;s permalink.', 'activitypub' ); ?></li>
						<li><code>[ap_shortlink]</code> - <?php echo wp_kses( __( 'The post&#8217;s shortlink. I can recommend <a href="https://wordpress.org/plugins/hum/" target="_blank">Hum</a>.', 'activitypub' ), 'default' ); ?></li>
						<li><code>[ap_hashtags]</code> - <?php esc_html_e( 'The post&#8217;s tags as hashtags.', 'activitypub' ); ?></li>
					</ul>
					<p><?php esc_html_e( 'You can find the full list with all possible attributes in the help section on the top-right of the screen.', 'activitypub' ); ?></p>
				</div>
			</details>
		</p>
		<?php
	}

	/**
	 * Render max image attachments field.
	 */
	public static function render_max_image_attachments_field() {
		$value = get_option( 'activitypub_max_image_attachments', ACTIVITYPUB_MAX_IMAGE_ATTACHMENTS );
		?>
		<input id="activitypub_max_image_attachments" value="<?php echo esc_attr( $value ); ?>" name="activitypub_max_image_attachments" type="number" min="0" class="small-text" />
		<p class="description">
			<?php
			echo wp_kses(
				sprintf(
					// translators: %s is a number.
					__( 'The number of media (images, audio, video) to attach to posts. Default: <code>%s</code>', 'activitypub' ),
					esc_html( ACTIVITYPUB_MAX_IMAGE_ATTACHMENTS )
				),
				'default'
			);
			?>
		</p>
		<p class="description">
			<em>
				<?php esc_html_e( 'Note: audio and video attachments are only supported from Block Editor.', 'activitypub' ); ?>
			</em>
		</p>
		<?php
	}

	/**
	 * Render support post types field.
	 */
	public static function render_support_post_types_field() {
		$post_types           = get_post_types( array( 'public' => true ), 'objects' );
		$supported_post_types = (array) get_option( 'activitypub_support_post_types', array( 'post' ) );
		?>
		<fieldset>
			<?php esc_html_e( 'Automatically publish items of the selected post types to the fediverse:', 'activitypub' ); ?>
			<ul>
			<?php foreach ( $post_types as $post_type ) : ?>
				<li>
					<input type="checkbox" id="activitypub_support_post_type_<?php echo esc_attr( $post_type->name ); ?>" name="activitypub_support_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $supported_post_types, true ) ); ?> />
					<label for="activitypub_support_post_type_<?php echo esc_attr( $post_type->name ); ?>"><?php echo esc_html( $post_type->label ); ?></label>
					<span class="description">
						<?php echo esc_html( \Activitypub\get_post_type_description( $post_type ) ); ?>
					</span>
				</li>
			<?php endforeach; ?>
			</ul>
		</fieldset>
		<?php
	}

	/**
	 * Render use hashtags field.
	 */
	public static function render_use_hashtags_field() {
		$value = get_option( 'activitypub_use_hashtags', '1' );
		?>
		<p>
			<label>
				<input type="checkbox" id="activitypub_use_hashtags" name="activitypub_use_hashtags" value="1" <?php checked( '1', $value ); ?> />
				<?php echo wp_kses( __( 'Add hashtags in the content as native tags and replace the <code>#tag</code> with the tag link.', 'activitypub' ), 'default' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Render use OpenGraph field.
	 */
	public static function render_use_opengraph_field() {
		$value = get_option( 'activitypub_use_opengraph', '1' );
		?>
		<p>
			<label>
				<input type="checkbox" id="activitypub_use_opengraph" name="activitypub_use_opengraph" value="1" <?php checked( '1', $value ); ?> />
				<?php echo wp_kses( __( 'Automatically add <code>&lt;meta name="fediverse:creator" /&gt;</code> tags for Authors and the Blog-User. You can read more about the feature on the <a href="https://blog.joinmastodon.org/2024/07/highlighting-journalism-on-mastodon/" target="_blank">Mastodon Blog</a>.', 'activitypub' ), 'post' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Render attribution domains field.
	 */
	public static function render_attribution_domains_field() {
		$value = get_option( 'activitypub_attribution_domains', \Activitypub\home_host() );
		?>
		<textarea id="activitypub_attribution_domains" name="activitypub_attribution_domains" class="large-text" cols="50" rows="5" placeholder="<?php echo esc_attr( \Activitypub\home_host() ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Websites allowed to credit you, one per line. Protects from false attributions.', 'activitypub' ); ?></p>
		<?php
	}

	/**
	 * Render blocklist field.
	 */
	public static function render_blocklist_field() {
		?>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					// translators: %s is a URL.
					__( 'To block servers, add the host of the server to the "<a href="%s">Disallowed Comment Keys</a>" list.', 'activitypub' ),
					esc_url( admin_url( 'options-discussion.php#disallowed_keys' ) )
				),
				'default'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render outbox purge days field.
	 */
	public static function render_outbox_purge_days_field() {
		$value = get_option( 'activitypub_outbox_purge_days', 180 );
		echo '<input type="number" id="activitypub_outbox_purge_days" name="activitypub_outbox_purge_days" value="' . esc_attr( $value ) . '" class="small-text" min="0" max="365" />';
		echo '<p class="description">' . wp_kses(
			sprintf(
				// translators: 1: Definition of Outbox; 2: Default value (180).
				__( 'Maximum number of days to keep items in the <abbr title="%1$s">Outbox</abbr>. A lower value might be better for sites with lots of activity to maintain site performance. Default: <code>%2$s</code>', 'activitypub' ),
				esc_attr__( 'A virtual location on a user&#8217;s profile where all the activities (posts, likes, replies) they publish are stored, acting as a feed that other users can access to see their publicly shared content', 'activitypub' ),
				esc_html( 180 )
			),
			array(
				'abbr' => array( 'title' => array() ),
				'code' => array(),
			)
		) . '</p>';
	}

	/**
	 * Render use hashtags field.
	 */
	public static function render_authorized_fetch_field() {
		$value = get_option( 'activitypub_authorized_fetch', '1' );
		?>
		<p>
			<label>
				<input type="checkbox" id="activitypub_authorized_fetch" name="activitypub_authorized_fetch" value="1" <?php checked( '1', $value ); ?> />
				<?php esc_html_e( 'Require HTTP signature authentication on ActivityPub representations of public posts and profiles.', 'activitypub' ); ?>
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
}
