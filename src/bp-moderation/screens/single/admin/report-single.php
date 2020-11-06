<?php
/**
 * @package BuddyBoss
 * Admin Single Reported item screen
 */

?>
<div class="wrap">
	<h1>
		<?php
		/* translators: accessibility text */
		printf( esc_html__( 'View Moderation Item', 'buddyboss' ) );
		?>
	</h1>

	<?php
	if ( ! empty( $moderation_request_data ) ) :
		?>
		<div id="poststuff">
			<div id="post-body"
				 class="metabox-holder columns-<?php echo 1 === (int) get_current_screen()->get_columns() ? '1' : '2'; ?>">
				<div id="post-body-content">
					<div id="postdiv">
						<div id="bp_moderation_action" class="postbox">
							<div class="inside">
								<table class="form-table">
									<tbody>
									<?php if ( ! empty( $_GET['tab'] ) && 'blocked-members' === $_GET['tab'] ) { ?>
										<tr>
											<th scope="row">
												<label>
													<?php
													/* translators: accessibility text */
													esc_html_e( 'Blocked Member', 'buddyboss' );
													?>
												</label>
											</th>
											<td>
												<?php
												$user_id = bp_moderation_get_content_owner_id( $moderation_request_data->item_id, $moderation_request_data->item_type );
												printf( '<strong>%s</strong>', wp_kses_post( bp_core_get_userlink( $user_id ) ) );
												?>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label>
													<?php
													/* translators: accessibility text */
													esc_html_e( 'Block (Count)', 'buddyboss' );
													?>
												</label>
											</th>
											<td>
												<?php
												/* translators: accessibility text */
												printf( _n( '%s time', '%s times', $moderation_request_data->count, 'buddyboss' ), esc_html( number_format_i18n( $moderation_request_data->count ) ) );
												?>
											</td>
										</tr>
									<?php } else { ?>
										<tr>
											<th scope="row">
												<label>
													<?php
													/* translators: accessibility text */
													esc_html_e( 'Content Type', 'buddyboss' );
													?>
												</label>
											</th>
											<td>
												<?php
												echo esc_html( bp_moderation_get_content_type( $moderation_request_data->item_type ) );
												?>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label>
													<?php
													/* translators: accessibility text */
													esc_html_e( 'Content ID', 'buddyboss' );
													?>
												</label>
											</th>
											<td>
												<?php
												echo esc_html( $moderation_request_data->item_id );
												?>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label>
													<?php
													/* translators: accessibility text */
													esc_html_e( 'Content Excerpt', 'buddyboss' );
													?>
												</label>
											</th>
											<td>
												<?php
												echo esc_html( 'Todo' );
												?>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label>
													<?php
													/* translators: accessibility text */
													esc_html_e( 'Content Owner', 'buddyboss' );
													?>
												</label>
											</th>
											<td>
												<?php
												$user_id = bp_moderation_get_content_owner_id( $moderation_request_data->item_id, $moderation_request_data->item_type );
												printf( '<strong>%s</strong>', wp_kses_post( bp_core_get_userlink( $user_id ) ) );
												?>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label>
													<?php
													/* translators: accessibility text */
													esc_html_e( 'Reported (Count)', 'buddyboss' );
													?>
												</label>
											</th>
											<td>
												<?php
												/* translators: accessibility text */
												printf( _n( '%s time', '%s times', $moderation_request_data->count, 'buddyboss' ), esc_html( number_format_i18n( $moderation_request_data->count ) ) );
												?>
											</td>
										</tr>
									<?php } ?>
									</tbody>
								</table>

								<?php
								$bp_moderation_report_list_table = new BP_Moderation_Report_List_Table();
								// Prepare the group items for display.
								$bp_moderation_report_list_table->prepare_items();
								$bp_moderation_report_list_table->views();
								$bp_moderation_report_list_table->display();

								$action_type  = ( 1 === (int) $moderation_request_data->hide_sitewide ) ? 'unhide' : 'hide';
								$action_label = ( 'unhide' === $action_type ) ? esc_html__( 'Unhide', 'buddyboss' ) : esc_html__( 'Hide', 'buddyboss' );
								?>
								<div class="bp-moderation-actions">
									<?php
									if ( ! isset( $_GET['tab'] ) || 'blocked-members' !== $_GET['tab'] ) {
										$user_id           = bp_moderation_get_content_owner_id( $moderation_request_data->item_id, $moderation_request_data->item_type );
										$user_action_type  = 'hide';
										$user_action_label = esc_html__( 'Hide', 'buddyboss' );
										$user_data         = BP_Moderation::get_specific_moderation( $user_id, 'user' );

										if ( ! empty( $user_data ) ) {
											$user_action_type  = ( 1 === (int) $user_data->hide_sitewide ) ? 'unhide' : 'hide';
											$user_action_label = ( 'unhide' === $user_action_type ) ? esc_html__( 'Unhide', 'buddyboss' ) : esc_html__( 'Hide', 'buddyboss' );
										}
										?>
										<a href="javascript:void(0);" class="button button-primary bp-hide-request" data-id="<?php echo esc_attr( $moderation_request_data->item_id ); ?>" data-type="<?php echo esc_attr( $moderation_request_data->item_type ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-hide-unhide-moderation' ) ); ?>" data-action="<?php echo esc_attr( $action_type ); ?>" title="<?php echo esc_html( $action_label ); ?>">
											<?php
											esc_html_e( 'Hide', 'buddyboss' );
											?>
										</a>
										<a href="javascript:void(0);" class="button button-primary bp-block-user" data-id="<?php echo esc_attr( $user_id ); ?>" data-type="user" data-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-hide-unhide-moderation' ) ); ?>" data-action="<?php echo esc_attr( $user_action_type ); ?>" title="<?php echo esc_attr( $user_action_label ); ?>">
											<?php
											esc_html_e( 'Suspend Content Author', 'buddyboss' );
											?>
										</a>
										<?php
									} else {
										?>
										<a href="javascript:void(0);" class="button button-primary bp-block-user" data-id="<?php echo esc_attr( $moderation_request_data->item_id ); ?>" data-type="user" data-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-hide-unhide-moderation' ) ); ?>" data-action="<?php echo esc_attr( $action_type ); ?>" title="<?php echo esc_attr( $action_label ); ?>">
											<?php
											esc_html_e( 'Suspend Member', 'buddyboss' );
											?>
										</a>
										<?php
									}
									?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php else : ?>
		<p>
			<?php
			printf(
				'%1$s <a href="%2$s">%3$s</a>',
				esc_html__( 'No moderation found with this ID.', 'buddyboss' ),
				esc_url( bp_get_admin_url( 'admin.php?page=bp-moderation' ) ),
				esc_html__( 'Go back and try again.', 'buddyboss' )
			);
			?>
		</p>
	<?php endif; ?>
</div>
