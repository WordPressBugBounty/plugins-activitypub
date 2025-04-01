<?php
/**
 * ActivityPub Blog Settings Fields Handler.
 *
 * @package ActivityPub
 */

namespace Activitypub\WP_Admin;

/**
 * Class to handle all blog settings fields and callbacks.
 */
class Blog_Settings_Fields {
	/**
	 * Initialize the settings fields.
	 */
	public static function init() {
		add_action( 'load-settings_page_activitypub', array( self::class, 'register_settings' ) );
	}

	/**
	 * Register all settings fields.
	 */
	public static function register_settings() {
		add_settings_section(
			'activitypub_blog_profile',
			__( 'Blog Profile', 'activitypub' ),
			'__return_empty_string',
			'activitypub_blog_settings'
		);

		add_settings_field(
			'activitypub_blog_avatar',
			__( 'Manage Avatar', 'activitypub' ),
			array( self::class, 'avatar_callback' ),
			'activitypub_blog_settings',
			'activitypub_blog_profile'
		);

		add_settings_field(
			'activitypub_header_image',
			__( 'Manage Header Image', 'activitypub' ),
			array( self::class, 'header_image_callback' ),
			'activitypub_blog_settings',
			'activitypub_blog_profile'
		);

		add_settings_field(
			'activitypub_blog_identifier',
			__( 'Change Profile ID', 'activitypub' ),
			array( self::class, 'profile_id_callback' ),
			'activitypub_blog_settings',
			'activitypub_blog_profile',
			array( 'label_for' => 'activitypub_blog_identifier' )
		);

		add_settings_field(
			'activitypub_blog_description',
			__( 'Change Description', 'activitypub' ),
			array( self::class, 'description_callback' ),
			'activitypub_blog_settings',
			'activitypub_blog_profile',
			array( 'label_for' => 'activitypub_blog_description' )
		);

		add_settings_field(
			'activitypub_extra_fields',
			__( 'Extra Fields', 'activitypub' ),
			array( self::class, 'extra_fields_callback' ),
			'activitypub_blog_settings',
			'activitypub_blog_profile'
		);

		add_settings_field(
			'activitypub_blog_user_also_known_as',
			__( 'Account Aliases', 'activitypub' ),
			array( self::class, 'also_known_as_callback' ),
			'activitypub_blog_settings',
			'activitypub_blog_profile'
		);
	}

	/**
	 * Avatar field callback.
	 */
	public static function avatar_callback() {
		?>
		<?php if ( has_site_icon() ) : ?>
			<p><img src="<?php echo esc_url( get_site_icon_url( 50 ) ); ?>" alt="" /></p>
		<?php endif; ?>
		<p class="description">
			<?php
			echo wp_kses(
				sprintf(
					// translators: %s is a URL.
					__( 'The ActivityPub plugin uses the WordPress Site Icon as Avatar for the Blog-Profile, you can change the Site Icon in the "<a href="%s">General Settings</a>" of WordPress.', 'activitypub' ),
					esc_url( admin_url( 'options-general.php' ) )
				),
				'default'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Header image field callback.
	 */
	public static function header_image_callback() {
		$classes_for_button           = 'button upload-button button-add-media button-add-header-image';
		$classes_for_button_on_change = 'button';
		$classes_for_wrapper          = ' hidden';

		if ( (int) get_option( 'activitypub_header_image', 0 ) ) {
			$classes_for_wrapper          = ' has-header-image';
			$classes_for_button_on_change = $classes_for_button;
			$classes_for_button           = 'button';
		}
		?>
		<div id="activitypub-header-image-preview-wrapper" class="<?php echo esc_attr( $classes_for_wrapper ); ?>">
			<img id="activitypub-header-image-preview" src="<?php echo esc_url( wp_get_attachment_url( get_option( 'activitypub_header_image' ) ) ); ?>" style="max-width: 100%;" alt="" />
		</div>
		<button
			type="button"
			id="activitypub-choose-from-library-button"
			class="<?php echo esc_attr( $classes_for_button ); ?>"
			data-alt-classes="<?php echo esc_attr( $classes_for_button_on_change ); ?>"
			data-choose-text="<?php esc_attr_e( 'Choose a Header Image', 'activitypub' ); ?>"
			data-update-text="<?php esc_attr_e( 'Change Header Icon', 'activitypub' ); ?>"
			data-update="<?php esc_attr_e( 'Set as Header Image', 'activitypub' ); ?>"
			data-width="1500"
			data-height="500"
			data-state="<?php echo esc_attr( (int) get_option( 'activitypub_header_image', 0 ) ); ?>">
			<?php if ( (int) get_option( 'activitypub_header_image', 0 ) ) : ?>
				<?php esc_html_e( 'Change Header Image', 'activitypub' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Choose a Header Image', 'activitypub' ); ?>
			<?php endif; ?>
		</button>
		<button
			id="activitypub-remove-header-image"
			type="button"
			<?php echo (int) get_option( 'activitypub_header_image', 0 ) ? 'class="button button-secondary reset"' : 'class="button button-secondary reset hidden"'; ?>>
			<?php esc_html_e( 'Remove Header Image', 'activitypub' ); ?>
		</button>
		<input type="hidden" name="activitypub_header_image" id="activitypub_header_image" value="<?php echo esc_attr( get_option( 'activitypub_header_image' ) ); ?>">
		<?php
	}

	/**
	 * Profile ID field callback.
	 */
	public static function profile_id_callback() {
		?>
		<label for="activitypub_blog_identifier">
			<input id="activitypub_blog_identifier" class="blog-user-identifier" name="activitypub_blog_identifier" type="text" value="<?php echo esc_attr( get_option( 'activitypub_blog_identifier', \Activitypub\Model\Blog::get_default_username() ) ); ?>" />
			@<?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'This profile name will federate all posts written on your blog, regardless of the author who posted it.', 'activitypub' ); ?>
		</p>
		<p class="description">
			<strong>
				<?php esc_html_e( 'Please avoid using an existing author&#8217;s name as the blog profile ID. Fediverse platforms might use caching and this could break the functionality completely.', 'activitypub' ); ?>
			</strong>
		</p>
		<?php
	}

	/**
	 * Description field callback.
	 */
	public static function description_callback() {
		?>
		<label for="activitypub_blog_description">
			<textarea
				class="blog-user-description large-text"
				rows="5"
				name="activitypub_blog_description"
				id="activitypub_blog_description"
				placeholder="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>"
			><?php echo esc_textarea( get_option( 'activitypub_blog_description' ) ); ?></textarea>
		</label>
		<p class="description">
			<?php esc_html_e( 'By default the ActivityPub plugin uses the WordPress tagline as a description for the blog profile.', 'activitypub' ); ?>
		</p>
		<?php
	}

	/**
	 * Extra fields callback.
	 */
	public static function extra_fields_callback() {
		?>
		<p class="description">
			<?php esc_html_e( 'Your homepage, social profiles, pronouns, age, anything you want.', 'activitypub' ); ?>
		</p>

		<table class="widefat striped activitypub-extra-fields" role="presentation" style="margin: 15px 0;">
		<?php
		$extra_fields = \Activitypub\Collection\Extra_Fields::get_actor_fields( \Activitypub\Collection\Actors::BLOG_USER_ID );

		if ( empty( $extra_fields ) ) :
			?>
			<tr>
				<td colspan="3">
					<?php esc_html_e( 'No extra fields found.', 'activitypub' ); ?>
				</td>
			</tr>
			<?php
		endif;

		foreach ( $extra_fields as $extra_field ) :
			?>
			<tr>
				<td><?php echo esc_html( $extra_field->post_title ); ?></td>
				<td><?php echo wp_kses_post( get_the_excerpt( $extra_field ) ); ?></td>
				<td>
					<a href="<?php echo esc_url( get_edit_post_link( $extra_field->ID ) ); ?>" class="button">
						<?php esc_html_e( 'Edit', 'activitypub' ); ?>
					</a>
				</td>
			</tr>
		<?php endforeach; ?>
		</table>

		<p>
			<a href="<?php echo esc_url( admin_url( '/post-new.php?post_type=ap_extrafield_blog' ) ); ?>" class="button">
				<?php esc_html_e( 'Add new', 'activitypub' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( '/edit.php?post_type=ap_extrafield_blog' ) ); ?>">
				<?php esc_html_e( 'Manage all', 'activitypub' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Also Known As field callback.
	 */
	public static function also_known_as_callback() {
		$also_known_as = \get_option( 'activitypub_blog_user_also_known_as' );
		?>
		<label for="activitypub_blog_user_also_known_as">
			<textarea
				class="large-text"
				id="activitypub_blog_user_also_known_as"
				name="activitypub_blog_user_also_known_as"
				rows="5"
			><?php echo esc_textarea( implode( PHP_EOL, (array) $also_known_as ) ); ?></textarea>
		</label>
		<p class="description">
			<?php esc_html_e( 'If you’re moving from another account to this one, you’ll need to create an alias here first before transferring your followers. This step is safe, reversible, and doesn’t affect anything on its own. The migration itself is initiated from your old account.', 'activitypub' ); ?>
		</p>
		<p class="description">
			<?php esc_html_e( 'Enter one URL per line.', 'activitypub' ); ?>
		</p>
		<?php
	}
}
