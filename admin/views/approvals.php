<?php
/**
 * Admin Associate approvals view (pending + approved only).
 *
 * @package CTA_LMS
 *
 * @var WP_User[] $associates     Associates matching the current filter.
 * @var string    $current_status Filter: all|pending_approval|approved.
 * @var array     $status_counts  Counts keyed by status.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$flash          = sanitize_text_field( wp_unslash( $_GET['cta_approval'] ?? '' ) );
$current_status = isset( $current_status ) ? $current_status : 'all';
$status_counts  = isset( $status_counts ) && is_array( $status_counts ) ? $status_counts : array();
$associates     = isset( $associates ) && is_array( $associates ) ? $associates : array();

$base_url = admin_url( 'admin.php?page=cta-lms-approvals' );
$tabs     = array(
	'all'              => __( 'All', 'cta-lms' ),
	'pending_approval' => __( 'Pending', 'cta-lms' ),
	'approved'         => __( 'Approved', 'cta-lms' ),
);

$empty_messages = array(
	'all'              => __( 'No pending or approved Associates yet.', 'cta-lms' ),
	'pending_approval' => __( 'No Associates are currently pending approval.', 'cta-lms' ),
	'approved'         => __( 'No approved Associates yet.', 'cta-lms' ),
);
?>
<div class="wrap cta-admin-wrap">
	<h1><?php esc_html_e( 'Approvals', 'cta-lms' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Review Associate registrations. Approving unlocks booking, meeting links, and supervision resources. Rejected Associates are removed from this list; only Pending and Approved remain visible.', 'cta-lms' ); ?>
	</p>

	<?php if ( 'approved' === $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Associate approved. Supervision access is now unlocked.', 'cta-lms' ); ?></p></div>
	<?php elseif ( 'rejected' === $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Associate rejected and removed from this list. Access remains locked.', 'cta-lms' ); ?></p></div>
	<?php elseif ( 'error' === $flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Unable to update approval status. Please try again.', 'cta-lms' ); ?></p></div>
	<?php endif; ?>

	<div id="cta-approvals-notice" class="notice" hidden></div>

	<ul class="subsubsub cta-approvals-tabs">
		<?php
		$tab_keys  = array_keys( $tabs );
		$last_key  = end( $tab_keys );
		foreach ( $tabs as $slug => $label ) :
			$count = isset( $status_counts[ $slug ] ) ? (int) $status_counts[ $slug ] : 0;
			$url   = 'all' === $slug ? $base_url : add_query_arg( 'status', $slug, $base_url );
			$class = $current_status === $slug ? 'current' : '';
			?>
			<li class="<?php echo esc_attr( $slug ); ?>">
				<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>">
					<?php echo esc_html( $label ); ?>
					<span class="count">(<?php echo esc_html( (string) $count ); ?>)</span>
				</a>
				<?php if ( $slug !== $last_key ) : ?> | <?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<table class="widefat striped cta-admin-table" id="cta-pending-approvals-table" data-current-status="<?php echo esc_attr( $current_status ); ?>">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Associate', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Email', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Employer/Agency', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Agency Representative', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Registered', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Reviewed', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Status', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'cta-lms' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $associates ) ) : ?>
				<tr class="cta-approvals-empty">
					<td colspan="8"><?php echo esc_html( $empty_messages[ $current_status ] ?? $empty_messages['all'] ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $associates as $user ) : ?>
					<?php
					$agency_name     = (string) get_user_meta( $user->ID, 'cta_employer_agency_name', true );
					$rep_name        = (string) get_user_meta( $user->ID, 'cta_agency_representative_name', true );
					$rep_email       = (string) get_user_meta( $user->ID, 'cta_agency_representative_email', true );
					$approval_status = (string) get_user_meta( $user->ID, 'cta_approval_status', true );
					$reviewed_at     = (string) get_user_meta( $user->ID, 'cta_approval_reviewed_at', true );
					$status_label    = class_exists( 'CTA_Associate_Access' )
						? CTA_Associate_Access::get_status_label( $approval_status )
						: $approval_status;
					$is_approved     = 'approved' === $approval_status;
					?>
					<tr
						class="cta-approval-row"
						data-user-id="<?php echo esc_attr( $user->ID ); ?>"
						data-status="<?php echo esc_attr( $approval_status ); ?>"
					>
						<td>
							<strong><?php echo esc_html( $user->display_name ); ?></strong>
						</td>
						<td><?php echo esc_html( $user->user_email ); ?></td>
						<td><?php echo esc_html( $agency_name ? $agency_name : '—' ); ?></td>
						<td>
							<?php if ( $rep_name || $rep_email ) : ?>
								<?php if ( $rep_name ) : ?>
									<div><?php echo esc_html( $rep_name ); ?></div>
								<?php endif; ?>
								<?php if ( $rep_email ) : ?>
									<div><a href="mailto:<?php echo esc_attr( $rep_email ); ?>"><?php echo esc_html( $rep_email ); ?></a></div>
								<?php endif; ?>
							<?php else : ?>
								—
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( wp_date( 'M j, Y', strtotime( $user->user_registered ) ) ); ?></td>
						<td>
							<?php
							if ( $reviewed_at && $is_approved ) {
								echo esc_html( wp_date( 'M j, Y g:i a', strtotime( $reviewed_at ) ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td>
							<span class="cta-approval-status-badge cta-approval-status-badge--<?php echo esc_attr( $approval_status ); ?>">
								<?php echo esc_html( $status_label ); ?>
							</span>
						</td>
						<td class="cta-table-actions">
							<?php if ( ! $is_approved ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cta-approval-form cta-approval-form--approve">
									<input type="hidden" name="action" value="cta_approve_associate">
									<input type="hidden" name="user_id" value="<?php echo esc_attr( $user->ID ); ?>">
									<?php wp_nonce_field( 'cta_review_associate_' . $user->ID, 'cta_approval_nonce' ); ?>
									<button
										type="submit"
										class="button button-primary cta-approve-associate"
										data-user-id="<?php echo esc_attr( $user->ID ); ?>"
									>
										<?php esc_html_e( 'Approve', 'cta-lms' ); ?>
									</button>
								</form>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cta-approval-form cta-approval-form--reject">
									<input type="hidden" name="action" value="cta_reject_associate">
									<input type="hidden" name="user_id" value="<?php echo esc_attr( $user->ID ); ?>">
									<?php wp_nonce_field( 'cta_review_associate_' . $user->ID, 'cta_approval_nonce' ); ?>
									<button
										type="submit"
										class="button cta-reject-associate"
										data-user-id="<?php echo esc_attr( $user->ID ); ?>"
									>
										<?php esc_html_e( 'Reject', 'cta-lms' ); ?>
									</button>
								</form>
							<?php else : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cta-approval-form cta-approval-form--reject">
									<input type="hidden" name="action" value="cta_reject_associate">
									<input type="hidden" name="user_id" value="<?php echo esc_attr( $user->ID ); ?>">
									<?php wp_nonce_field( 'cta_review_associate_' . $user->ID, 'cta_approval_nonce' ); ?>
									<button
										type="submit"
										class="button cta-reject-associate"
										data-user-id="<?php echo esc_attr( $user->ID ); ?>"
									>
										<?php esc_html_e( 'Revoke', 'cta-lms' ); ?>
									</button>
								</form>
								<span class="cta-approval-action-note"><?php esc_html_e( 'Access unlocked', 'cta-lms' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
