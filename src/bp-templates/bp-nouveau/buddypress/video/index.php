<?php
/**
 * BuddyBoss Video templates
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 * @version 1.0.0
 */

?>

	<?php bp_nouveau_before_video_directory_content(); ?>

	<?php bp_nouveau_template_notices(); ?>

	<div class="screen-content">

		<?php bp_nouveau_video_hook( 'before_directory', 'list' ); ?>

		<?php
		/**
		 * Fires before the display of the members list tabs.
		 *
		 * @since BuddyPress 1.8.0
		 */
		do_action( 'bp_before_directory_video_tabs' );
		?>

		<?php if ( ! bp_nouveau_is_object_nav_in_sidebar() ) : ?>

			<?php bp_get_template_part( 'common/nav/directory-nav' ); ?>

		<?php endif; ?>


		<div class="video-options">
			<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>
			<?php if ( is_user_logged_in() ) : ?>
				<a class="bb-add-videos button small" id="bp-add-video" href="#" ><i class="bb-icon-upload"></i><?php esc_html_e( 'Add Videos', 'buddyboss' ); ?></a>
				<a href="#" id="bb-create-video-album" class="bb-create-video-album button small"><i class="bb-icon-media"></i><?php esc_html_e( 'Create Album', 'buddyboss' ); ?></a>
				<?php bp_get_template_part( 'video/uploader' ); ?>
				<?php bp_get_template_part( 'video/create-album' ); ?>
			<?php endif; ?>
		</div>

		<?php bp_get_template_part( 'video/theatre' ); ?>

		<div id="video-stream" class="video" data-bp-list="video">
			<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-video-loading' ); ?></div>
		</div><!-- .video -->

		<?php bp_nouveau_after_video_directory_content(); ?>

	</div><!-- // .screen-content -->
