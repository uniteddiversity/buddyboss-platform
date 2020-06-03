<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'bp_document_folder_after_save', 'bp_document_update_document_privacy' );
add_action( 'delete_attachment', 'bp_document_delete_attachment_document', 0 );

// Activity.
add_action( 'bp_after_directory_activity_list', 'bp_document_add_theatre_template' );
add_action( 'bp_after_member_activity_content', 'bp_document_add_theatre_template' );
add_action( 'bp_after_group_activity_content', 'bp_document_add_theatre_template' );
add_action( 'bp_after_single_activity_content', 'bp_document_add_theatre_template' );
add_action( 'bp_activity_entry_content', 'bp_document_activity_entry' );
add_action( 'bp_activity_after_comment_content', 'bp_document_activity_comment_entry' );
add_action( 'bp_activity_posted_update', 'bp_document_update_activity_document_meta', 10, 3 );
add_action( 'bp_groups_posted_update', 'bp_document_groups_activity_update_document_meta', 10, 4 );
add_action( 'bp_activity_comment_posted', 'bp_document_activity_comments_update_document_meta', 10, 3 );
add_action( 'bp_activity_comment_posted_notification_skipped', 'bp_document_activity_comments_update_document_meta', 10, 3 );
add_action( 'bp_activity_after_delete', 'bp_document_delete_activity_document' );
add_action( 'bp_activity_after_save', 'bp_document_activity_update_document_privacy', 2 );

// Search.
add_action( 'bp_search_after_result', 'bp_document_add_theatre_template', 99999 );

// Forums.
add_action( 'bbp_template_after_single_topic', 'bp_document_add_theatre_template' );
add_action( 'bbp_new_reply', 'bp_document_forums_new_post_document_save', 999 );
add_action( 'bbp_new_topic', 'bp_document_forums_new_post_document_save', 999 );
add_action( 'edit_post', 'bp_document_forums_new_post_document_save', 999 );

add_filter( 'bbp_get_reply_content', 'bp_document_forums_embed_attachments', 999999, 2 );
add_filter( 'bbp_get_topic_content', 'bp_document_forums_embed_attachments', 999999, 2 );

// Messages.
add_action( 'messages_message_sent', 'bp_document_attach_document_to_message' );
add_action( 'bp_messages_thread_after_delete', 'bp_document_messages_delete_attached_document', 10, 2 );
add_action( 'bp_messages_thread_messages_after_update', 'bp_document_user_messages_delete_attached_document', 10, 4 );

// Download Document.
add_action( 'bp_template_redirect', 'bp_document_download_url_file' );

// Sync Attachment data.
//add_action( 'edit_attachment', 'bp_document_sync_document_data', 99, 1 );

add_filter( 'bp_get_document_name', 'convert_chars' );
add_filter( 'bp_get_document_name', 'wptexturize' );
add_filter( 'bp_get_document_name', 'wp_filter_kses', 1 );
add_filter( 'bp_get_document_name', 'stripslashes' );

add_filter( 'bp_get_folder_title', 'wptexturize' );
add_filter( 'bp_get_folder_title', 'wp_filter_kses', 1 );
add_filter( 'bp_get_folder_title', 'stripslashes' );
add_filter( 'bp_get_folder_title', 'convert_chars' );

// Change label for global search.
add_filter( 'bp_search_label_search_type', 'bp_document_search_label_search' );

function bp_document_search_label_search( $type ) {

	if ( 'folders' === $type ) {
		$type = __( 'Document Folders', 'buddyboss' );
	} elseif ( 'documents' === $type ) {
		$type = __( 'Documents', 'buddyboss' );
	}

	return $type;
}

/**
 * Add document theatre template for activity pages
 */
function bp_document_add_theatre_template() {
	bp_get_template_part( 'document/theatre' );
}

/**
 * Get activity entry document to render on front end
 *
 * @BuddyBoss 1.2.5
 */
function bp_document_activity_entry() {

	$document_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_document_ids', true );

	// Add document to single activity page.
	$document_activity = bp_activity_get_meta( bp_get_activity_id(), 'bp_document_activity', true );
	if ( bp_is_single_activity() && ! empty( $document_activity ) && '1' === $document_activity && empty( $document_ids ) ) {
		$document_ids = BP_Document::get_activity_document_id( bp_get_activity_id() );
	}

	if ( ! empty( $document_ids ) && bp_has_document(
		array(
			'include'  => $document_ids,
			'order_by' => 'menu_order',
			'sort'     => 'ASC',
		)
	) ) { ?>
		<div class="bb-activity-media-wrap bb-media-length-1 ">
			<?php

			bp_get_template_part( 'document/activity-document-move' );
			while ( bp_document() ) {
				bp_the_document();
				bp_get_template_part( 'document/activity-entry' );
			}
			?>
		</div>
		<?php
	}
}

/**
 * Append the document content to activity read more content
 *
 * @param $content
 * @param $activity
 *
 * @return string
 * @since BuddyBoss 1.4.0
 */
function bp_document_activity_append_document( $content, $activity ) {

	$document_ids = bp_activity_get_meta( $activity->id, 'bp_document_ids', true );

	if ( ! empty( $document_ids ) && bp_has_document(
		array(
			'include'  => $document_ids,
			'order_by' => 'menu_order',
			'sort'     => 'ASC',
		)
	) ) {

		?>
		<?php ob_start(); ?>
		<div class="bb-activity-media-wrap bb-media-length-1 ">
			<?php
			bp_get_template_part( 'document/activity-document-move' );
			while ( bp_document() ) {
				bp_the_document();
				bp_get_template_part( 'document/activity-entry' );
			}
			?>
		</div>
		<?php
		$content .= ob_get_clean();
	}

	return $content;
}

/**
 * Get activity comment entry document to render on front end.
 *
 * @param $comment_id
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_activity_comment_entry( $comment_id ) {

	$document_ids = bp_activity_get_meta( $comment_id, 'bp_document_ids', true );

	if ( ! empty( $document_ids ) && bp_has_document(
		array(
			'include'  => $document_ids,
			'order_by' => 'menu_order',
			'sort'     => 'ASC',
		)
	) ) {

		?>
		<div class="bb-activity-media-wrap bb-media-length-1 ">
			<?php
			bp_get_template_part( 'document/activity-document-move' );
			while ( bp_document() ) {
				bp_the_document();
				bp_get_template_part( 'document/activity-entry' );
			}
			?>
		</div>
		<?php
	}
}

/**
 * Remove the inline preview in popup activity comment document.
 *
 * @param $display
 *
 * @return string
 * @since BuddyBoss 1.4.0
 */
function bp_document_music_preview_remove_in_comment( $display ) {
	return false;
}

/**
 * Remove the inline preview in popup activity comment document.
 *
 * @param $display
 *
 * @return string
 * @since BuddyBoss 1.4.0
 */
function bp_document_text_preview_remove_in_comment( $display ) {
	return false;
}

/**
 * Remove the inline preview in popup activity comment document.
 *
 * @param $display
 *
 * @return string
 * @since BuddyBoss 1.4.0
 */
function bp_document_image_preview_remove_in_comment( $display ) {
	return false;
}

/**
 * Change the text in activity comment document view.
 *
 * @param $text
 *
 * @return string|void
 * @since BuddyBoss 1.4.0
 */
function bp_document_change_popup_view_text_in_comment( $text ) {
	return __( 'View', 'buddyboss' );
}

/**
 * Change the text in activity comment document view.
 *
 * @param $text
 *
 * @return string|void
 * @since BuddyBoss 1.4.0
 */
function bp_document_change_popup_download_text_in_comment( $text ) {
	return __( 'Download', 'buddyboss' );
}

/**
 * Update document for activity.
 *
 * @param $content
 * @param $user_id
 * @param $activity_id
 *
 * @return bool
 * @since BuddyBoss 1.4.0
 */
function bp_document_update_activity_document_meta( $content, $user_id, $activity_id ) {

	if ( ! isset( $_POST['document'] ) || empty( $_POST['document'] ) ) {
		return false;
	}

	$_POST['documents']          = $_POST['document'];
	$_POST['bp_activity_update'] = true;
	$_POST['bp_activity_id']     = $activity_id;

	remove_action( 'bp_activity_posted_update', 'bp_document_update_activity_document_meta', 10, 3 );
	remove_action( 'bp_groups_posted_update', 'bp_document_groups_activity_update_document_meta', 10, 4 );
	remove_action( 'bp_activity_comment_posted', 'bp_document_activity_comments_update_document_meta', 10, 3 );
	remove_action( 'bp_activity_comment_posted_notification_skipped', 'bp_document_activity_comments_update_document_meta', 10, 3 );

	$document_ids = bp_document_add_handler();

	add_action( 'bp_activity_posted_update', 'bp_document_update_activity_document_meta', 10, 3 );
	add_action( 'bp_groups_posted_update', 'bp_document_groups_activity_update_document_meta', 10, 4 );
	add_action( 'bp_activity_comment_posted', 'bp_document_activity_comments_update_document_meta', 10, 3 );
	add_action( 'bp_activity_comment_posted_notification_skipped', 'bp_document_activity_comments_update_document_meta', 10, 3 );

	// save document meta for activity.
	if ( ! empty( $activity_id ) ) {
		bp_activity_update_meta( $activity_id, 'bp_document_ids', implode( ',', $document_ids ) );
	}
}

/**
 * Update document for group activity.
 *
 * @param $content
 * @param $user_id
 * @param $group_id
 * @param $activity_id
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_groups_activity_update_document_meta( $content, $user_id, $group_id, $activity_id ) {
	bp_document_update_activity_document_meta( $content, $user_id, $activity_id );
}

/**
 * Update document for activity comment.
 *
 * @param $comment_id
 * @param $r
 * @param $activity
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_activity_comments_update_document_meta( $comment_id, $r, $activity ) {
	global $bp_new_activity_comment;
	$bp_new_activity_comment = true;
	bp_document_update_activity_document_meta( false, false, $comment_id );
}

/**
 * Delete document when related activity is deleted.
 *
 * @param $activities
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_delete_activity_document( $activities ) {
	if ( ! empty( $activities ) ) {
		remove_action( 'bp_activity_after_delete', 'bp_document_delete_activity_document' );
		foreach ( $activities as $activity ) {
			$activity_id       = $activity->id;
			$document_activity = bp_activity_get_meta( $activity_id, 'bp_document_activity', true );
			if ( ! empty( $document_activity ) && '1' == $document_activity ) {
				bp_document_delete( array( 'activity_id' => $activity_id ) );
			}

			// get document ids attached to activity.
			$document_ids = bp_activity_get_meta( $activity_id, 'bp_document_ids', true );
			if ( ! empty( $document_ids ) ) {
				$document_ids = explode( ',', $document_ids );
				foreach ( $document_ids as $document_id ) {
					bp_document_delete( array( 'id' => $document_id ) );
				}
			}
		}
		add_action( 'bp_activity_after_delete', 'bp_document_delete_activity_document' );
	}
}

/**
 * Update document privacy according to folder's privacy.
 *
 * @param $folder
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_update_document_privacy( &$folder ) {

	if ( ! empty( $folder->id ) ) {

		$privacy      = $folder->privacy;
		$document_ids = BP_Document::get_folder_document_ids( $folder->id );
		$activity_ids = array();

		if ( ! empty( $document_ids ) ) {
			foreach ( $document_ids as $document ) {
				$document_obj          = new BP_Document( $document );
				$document_obj->privacy = $privacy;
				$document_obj->save();

				$attachment_id    = $document_obj->attachment_id;
				$main_activity_id = get_post_meta( $attachment_id, 'bp_document_parent_activity_id', true );

				if ( ! empty( $main_activity_id ) ) {
					$activity_ids[] = $main_activity_id;
				}
			}
		}

		if ( ! empty( $activity_ids ) ) {
			foreach ( $activity_ids as $activity_id ) {
				$activity = new BP_Activity_Activity( $activity_id );

				if ( ! empty( $activity ) ) {
					$activity->privacy = $privacy;
					$activity->save();
				}
			}
		}
	}
}

/**
 * Save document when new topic or reply is saved
 *
 * @param $post_id
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_forums_new_post_document_save( $post_id ) {

	if ( ! empty( $_POST['bbp_document'] ) ) {

		// save activity id if it is saved in forums and enabled in platform settings.
		$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

		// save document.
		$documents = json_decode( stripslashes( $_POST['bbp_document'] ), true );

		// fetch currently uploaded document ids.
		$existing_document                = array();
		$existing_document_ids            = get_post_meta( $post_id, 'bp_document_ids', true );
		$existing_document_attachment_ids = array();
		if ( ! empty( $existing_document_ids ) ) {
			$existing_document_ids = explode( ',', $existing_document_ids );

			foreach ( $existing_document_ids as $existing_document_id ) {
				$existing_document[ $existing_document_id ] = new BP_Document( $existing_document_id );

				if ( ! empty( $existing_document[ $existing_document_id ]->attachment_id ) ) {
					$existing_document_attachment_ids[] = $existing_document[ $existing_document_id ]->attachment_id;
				}
			}
		}

		$document_ids = array();
		foreach ( $documents as $document ) {

			$title                = ! empty( $document['name'] ) ? $document['name'] : '';
			$attachment_id        = ! empty( $document['id'] ) ? $document['id'] : 0;
			$attached_document_id = ! empty( $document['document_id'] ) ? $document['document_id'] : 0;
			$folder_id            = ! empty( $document['folder_id'] ) ? $document['folder_id'] : 0;
			$group_id             = ! empty( $document['group_id'] ) ? $document['group_id'] : 0;
			$forum_id             = ! empty( $document['forum_id'] ) ? $document['forum_id'] : 0;
			$topic_id             = ! empty( $document['topic_id'] ) ? $document['topic_id'] : 0;
			$reply_id             = ! empty( $document['reply_id'] ) ? $document['reply_id'] : 0;
			$menu_order           = ! empty( $document['menu_order'] ) ? $document['menu_order'] : 0;

			if ( ! empty( $existing_document_attachment_ids ) ) {
				$index = array_search( $attachment_id, $existing_document_attachment_ids );
				if ( ! empty( $attachment_id ) && $index !== false && ! empty( $existing_document[ $attached_document_id ] ) ) {

					$existing_document[ $attached_document_id ]->menu_order = $menu_order;
					$existing_document[ $attached_document_id ]->save();

					unset( $existing_document_ids[ $index ] );
					$document_ids[] = $attached_document_id;
					continue;
				}
			}

			if ( 0 === $reply_id && bbp_get_reply_post_type() === get_post_type( $post_id ) ) {
				$reply_id = $post_id;
				$topic_id = bbp_get_reply_topic_id( $reply_id );
				$forum_id = bbp_get_topic_forum_id( $topic_id );
			} elseif ( 0 === $topic_id && bbp_get_topic_post_type() === get_post_type( $post_id ) ) {
				$topic_id = $post_id;
				$forum_id = bbp_get_topic_forum_id( $topic_id );
			} elseif ( 0 === $forum_id && bbp_get_forum_post_type() === get_post_type( $post_id ) ) {
				$forum_id = $post_id;
			}

			$attachment_data = get_post( $document['id'] );
			$file            = get_attached_file( $document['id'] );
			$file_type       = wp_check_filetype( $file );
			$file_name       = basename( $file );

			$document_id = bp_document_add(
				array(
					'attachment_id' => $attachment_id,
					'title'         => $title,
					'folder_id'     => $folder_id,
					'group_id'      => $group_id,
					'privacy'       => 'forums',
					'error_type'    => 'wp_error',
					'menu_order'    => $menu_order,
				)
			);

			if ( ! is_wp_error( $document_id ) && ! empty( $document_id ) ) {
				$document_ids[] = $document_id;

				// save document meta.
				bp_document_update_meta( $document_id, 'forum_id', $forum_id );
				bp_document_update_meta( $document_id, 'topic_id', $topic_id );
				bp_document_update_meta( $document_id, 'reply_id', $reply_id );
				bp_document_update_meta( $document_id, 'file_name', $file_name );
				bp_document_update_meta( $document_id, 'extension', '.' . $file_type['ext'] );

				// save document is saved in attachment.
				update_post_meta( $attachment_id, 'bp_document_saved', true );
			}
		}

		$document_ids = implode( ',', $document_ids );

		// Save all attachment ids in forums post meta.
		update_post_meta( $post_id, 'bp_document_ids', $document_ids );

		// save document meta for activity.
		if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
			bp_activity_update_meta( $main_activity_id, 'bp_document_ids', $document_ids );
		}

		// delete documents which were not saved or removed from form.
		if ( ! empty( $existing_document_ids ) ) {
			foreach ( $existing_document_ids as $document_id ) {
				bp_document_delete( array( 'id' => $document_id ) );
			}
		}
	}
}

/**
 * Embed topic or reply attachments in a post.
 *
 * @param $content
 * @param $id
 *
 * @return string
 * @since BuddyBoss 1.4.0
 */
function bp_document_forums_embed_attachments( $content, $id ) {

	// Do not embed attachment in wp-admin area.
	if ( is_admin() ) {
		return $content;
	}

	$document_ids = get_post_meta( $id, 'bp_document_ids', true );

	if ( ! empty( $document_ids ) && bp_has_document(
		array(
			'include'  => $document_ids,
			'order_by' => 'menu_order',
			'sort'     => 'ASC',
			'privacy'  => array( 'forums' ),
		)
	) ) {
		ob_start();
		?>
		<div class="bb-activity-media-wrap forums-media-wrap">
			<?php
			while ( bp_document() ) {
				bp_the_document();
				bp_get_template_part( 'document/activity-entry' );
			}
			?>
		</div>
		<?php
		$content .= ob_get_clean();
	}

	return $content;
}

/**
 * Attach document to the message object.
 *
 * @param $message
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_attach_document_to_message( &$message ) {

	if ( bp_is_messages_document_support_enabled() && ! empty( $message->id ) && ! empty( $_POST['document'] ) ) {

		remove_action( 'bp_document_add', 'bp_activity_document_add', 9 );
		remove_filter( 'bp_document_add_handler', 'bp_activity_create_parent_document_activity', 9 );

		$document_list = $_POST['document'];
		$document_ids  = array();

		foreach ( $document_list as $document_index => $document ) {
			$title         = ! empty( $document['name'] ) ? $document['name'] : '&nbsp;';
			$attachment_id = ! empty( $document['id'] ) ? $document['id'] : 0;
			$menu_order	   = ! empty( $document['menu_order'] ) ? $document['menu_order'] : 0;

			$attachment_data = get_post( $document['id'] );
			$file            = get_attached_file( $document['id'] );
			$file_type       = wp_check_filetype( $file );
			$file_name       = basename( $file );

			$document_id = bp_document_add(
				array(
					'attachment_id' => $attachment_id,
					'title'         => $title,
					'privacy'       => 'message',
					'error_type'    => 'wp_error',
					'menu_order'    => $menu_order,
				)
			);

			if ( ! empty( $document_id ) && ! is_wp_error( $document_id ) ) {
				$document_ids[] = $document_id;

				// save document meta.
				bp_document_update_meta( $document_id, 'file_name', $file_name );
				bp_document_update_meta( $document_id, 'thread_id', $message->thread_id );
				bp_document_update_meta( $document_id, 'extension', '.' . $file_type['ext'] );

				// save document is saved in attachment.
				update_post_meta( $attachment_id, 'bp_document_saved', true );
			}
		}

		$document_ids = implode( ',', $document_ids );

		// save document meta for message.
		bp_messages_update_meta( $message->id, 'bp_document_ids', $document_ids );

		add_action( 'bp_document_add', 'bp_activity_document_add', 9 );
		add_filter( 'bp_document_add_handler', 'bp_activity_create_parent_document_activity', 9 );
	}
}

/**
 * Delete document attached to messages.
 *
 * @param $thread_id
 * @param $message_ids
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_messages_delete_attached_document( $thread_id, $message_ids ) {

	if ( ! empty( $message_ids ) ) {
		foreach ( $message_ids as $message_id ) {

			// get document ids attached to message.
			$document_ids = bp_messages_get_meta( $message_id, 'bp_document_ids', true );

			if ( ! empty( $document_ids ) ) {
				$document_ids = explode( ',', $document_ids );
				foreach ( $document_ids as $document_id ) {
					bp_document_delete( array( 'id' => $document_id ) );
				}
			}
		}
	}
}

/**
 * Delete document attached to messages.
 *
 * @param $thread_id
 * @param $message_ids
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_user_messages_delete_attached_document( $thread_id, $message_ids, $user_id, $update_message_ids ) {

	if ( ! empty( $update_message_ids ) ) {
		foreach ( $update_message_ids as $message_id ) {

			// get document ids attached to message.
			$document_ids = bp_messages_get_meta( $message_id, 'bp_document_ids', true );

			if ( ! empty( $document_ids ) ) {
				$document_ids = explode( ',', $document_ids );
				foreach ( $document_ids as $document_id ) {
					bp_document_delete( array( 'id' => $document_id ) );
				}
			}
		}
	}
}

/**
 * Delete document entries attached to the attachment.
 *
 * @param int $attachment_id ID of the attachment being deleted.
 *
 * @return bool
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_delete_attachment_document( $attachment_id ) {
	global $wpdb;

	$bp = buddypress();

	$document = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->document->table_name} WHERE attachment_id = %d", $attachment_id ) ); // db call ok; no-cache ok;
	if ( ! $document ) {
		return false;
	}
	remove_action( 'delete_attachment', 'bp_document_delete_attachment_document', 0 );
	bp_document_delete( array( 'id' => $document->id ), 'attachment' );
	add_action( 'delete_attachment', 'bp_document_delete_attachment_document', 0 );
}

/**
 * Check if user have a access to download the file. If not redirect to homepage.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_download_url_file() {
	if ( isset( $_GET['attachment_id'] ) && isset( $_GET['download_document_file'] ) && isset( $_GET['document_file'] ) && isset( $_GET['document_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'folder' !== $_GET['document_type'] ) {
			$document_privacy = bp_document_user_can_manage_document( $_GET['document_file'], bp_loggedin_user_id() ); // phpcs:ignore WordPress.Security.NonceVerification
			$can_download_btn = ( true === (bool) $document_privacy['can_download'] ) ? true : false;
		} else {
			$folder_privacy   = bp_document_user_can_manage_folder( $_GET['document_file'], bp_loggedin_user_id() ); // phpcs:ignore WordPress.Security.NonceVerification
			$can_download_btn = ( true === (bool) $folder_privacy['can_download'] ) ? true : false;
		}
		if ( $can_download_btn ) {
			bp_document_download_file( $_GET['attachment_id'], $_GET['document_type'] ); // phpcs:ignore WordPress.Security.NonceVerification
		} else {
			wp_safe_redirect( site_url() );
		}
	}
}

/** Sync the description of the document with the media attachment.
 *
 * @param $attachment_id
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_sync_document_data( $attachment_id ) {
	if ( ! is_admin() || wp_doing_ajax() ) {
		return;
	}
	global $wpdb, $bp;
	// Check if document is attached to a document.
	$document = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->document->table_name} WHERE attachment_id = %d", $attachment_id ) ); // db call ok; no-cache ok;
	if ( $document ) {
		$document_post = get_post( $attachment_id );
		$document = bp_document_rename_file( $document->id, $attachment_id, $document_post->post_title, true );
	}
}

/**
 * Update document privacy when activity is updated.
 *
 * @param $activity Activity object.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_activity_update_document_privacy( $activity ) {
	$document_ids = bp_activity_get_meta( $activity->id, 'bp_document_ids', true );

	if ( ! empty( $document_ids ) ) {
		$document_ids = explode( ',', $document_ids );

		foreach ( $document_ids as $document_id ) {
			$document          = new BP_Document( $document_id );
			$document->privacy = $activity->privacy;
			$document->save();
		}
	}
}