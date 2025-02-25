<?php
/**
 * ActivityPub settings template.
 *
 * @package Activitypub
 */

?>

<div class="activitypub-settings activitypub-settings-page hide-if-no-js">
	<form method="post" action="options.php">
		<?php \settings_fields( 'activitypub' ); ?>
		<?php \do_settings_sections( 'activitypub_settings' ); ?>
		<?php \submit_button(); ?>
	</form>
</div>
