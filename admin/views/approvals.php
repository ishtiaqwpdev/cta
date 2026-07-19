<?php
/**
 * Admin supervision purchase approvals.
 *
 * @package CTA_LMS
 *
 * @var array  $purchase_records Supervision purchase/user records.
 * @var string $current_status   Filter: all|pending_approval|approved|rejected.
 * @var array  $status_counts    Counts keyed by status.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$flash            = sanitize_text_field( wp_unslash( $_GET['cta_approval'] ?? '' ) );
$current_status   = isset( $current_status ) ? $current_status : 'all';
$status_counts    = isset( $status_counts ) && is_array( $status_counts ) ? $status_counts : array();
$purchase_records = isset( $purchase_records ) && is_array( $purchase_records ) ? $purchase_records : array();

$base_url = admin_url( 'admin.php?page=cta-lms-approvals' );
$tabs     = array(
	'all'              => __( 'All', 'cta-lms' ),
	'pending_approval' => __( 'Pending Approval', 'cta-lms' ),
	'approved'         => __( 'Approved', 'cta-lms' ),
	'rejected'         => __( 'Rejected', 'cta-lms' ),
);

$empty_messages = array(
	'all'              => __( 'No Associates awaiting review yet.', 'cta-lms' ),
	'pending_approval' => __( 'No Associates are currently pending approval.', 'cta-lms' ),
	'approved'         => __( 'No approved Associates found.', 'cta-lms' ),
	'rejected'         => __( 'No rejected Associates found.', 'cta-lms' ),
);
?>
<div class="wrap cta-admin-wrap">
	<h1><?php esc_html_e( 'Supervision Approvals', 'cta-lms' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Review Registered Associates and supervision purchases. Approval unlocks the supervision dashboard, session booking, meeting links, and supervision materials.', 'cta-lms' ); ?>
	</p>

	<?php if ( 'approved' === $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Associate approved. Supervision access is now unlocked.', 'cta-lms' ); ?></p></div>
	<?php elseif ( 'rejected' === $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Associate rejected. Supervision access remains locked.', 'cta-lms' ); ?></p></div>
	<?php elseif ( 'error' === $flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Unable to update approval status. Please try again.', 'cta-lms' ); ?></p></div>
	<?php endif; ?>

	<div id="cta-approvals-notice" class="notice" hidden></div>

	<ul class="subsubsub cta-approvals-tabs">
		<?php
		$tab_keys = array_keys( $tabs );
		$last_key = end( $tab_keys );
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
				<th><?php esc_html_e( 'Purchased Plan', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Purchase Date', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Status', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'cta-lms' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $purchase_records ) ) : ?>
				<tr class="cta-approvals-empty">
					<td colspan="6"><?php echo esc_html( $empty_messages[ $current_status ] ?? $empty_messages['all'] ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $purchase_records as $record ) : ?>
					<?php
					$user             = $record['user'];
					$payment          = ! empty( $record['payment'] ) ? $record['payment'] : null;
					$approval_status  = $record['status'];
					$status_label     = CTA_Associate_Access::get_status_label( $approval_status );
					$is_approved      = CTA_Associate_Access::STATUS_APPROVED === $approval_status;
					$is_rejected      = CTA_Associate_Access::STATUS_REJECTED === $approval_status;
					$purchase_date    = ( $payment && ! empty( $payment->created_at ) )
						? wp_date( 'M j, Y g:i a', strtotime( $payment->created_at ) )
						: ( ! empty( $user->user_registered )
							? wp_date( 'M j, Y', strtotime( $user->user_registered ) )
							: '—' );
					$plan_details     = is_array( $record['plan_details'] ) ? $record['plan_details'] : array();
					$details_payload  = array(
						'user_name'        => $user->display_name,
						'user_email'       => $user->user_email,
						'plan_name'        => $record['plan_name'],
						'purchase_date'    => $payment ? $purchase_date : __( 'No purchase yet', 'cta-lms' ),
						'registered_date'  => ! empty( $user->user_registered )
							? wp_date( 'M j, Y g:i a', strtotime( $user->user_registered ) )
							: '',
						'amount'           => $payment
							? ( '$' . number_format( (float) $payment->amount, 2 ) . ' ' . strtoupper( (string) $payment->currency ) )
							: '',
						'billing'          => $payment
							? sanitize_text_field( (string) ( $plan_details['billing'] ?? $payment->payment_type ) )
							: '',
						'description'      => sanitize_text_field( (string) ( $plan_details['description'] ?? '' ) ),
						'stripe_reference' => $payment ? sanitize_text_field( (string) $payment->stripe_payment_id ) : '',
						'status'           => $status_label,
						'rejection_reason' => $record['rejection_reason'],
					);
					?>
					<tr
						class="cta-approval-row"
						data-user-id="<?php echo esc_attr( $user->ID ); ?>"
						data-status="<?php echo esc_attr( $approval_status ); ?>"
					>
						<td><strong><?php echo esc_html( $user->display_name ); ?></strong></td>
						<td><a href="mailto:<?php echo esc_attr( $user->user_email ); ?>"><?php echo esc_html( $user->user_email ); ?></a></td>
						<td><?php echo esc_html( $record['plan_name'] ); ?></td>
						<td><?php echo esc_html( $purchase_date ); ?></td>
						<td>
							<span class="cta-approval-status-badge cta-approval-status-badge--<?php echo esc_attr( $approval_status ); ?>">
								<?php echo esc_html( $status_label ); ?>
							</span>
						</td>
						<td class="cta-table-actions">
							<button
								type="button"
								class="button cta-view-supervision-purchase"
								data-purchase-details="<?php echo esc_attr( wp_json_encode( $details_payload ) ); ?>"
							>
								<?php esc_html_e( 'View Details', 'cta-lms' ); ?>
							</button>

							<?php if ( ! empty( $record['is_associate'] ) && ! $is_approved ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cta-approval-form cta-approval-form--approve">
									<input type="hidden" name="action" value="cta_approve_associate">
									<input type="hidden" name="user_id" value="<?php echo esc_attr( $user->ID ); ?>">
									<?php wp_nonce_field( 'cta_review_associate_' . $user->ID, 'cta_approval_nonce' ); ?>
									<button type="submit" class="button button-primary cta-approve-associate">
										<?php esc_html_e( 'Approve', 'cta-lms' ); ?>
									</button>
								</form>
							<?php endif; ?>

							<?php if ( ! empty( $record['is_associate'] ) && ! $is_rejected ) : ?>
								<button
									type="button"
									class="button cta-open-reject-associate"
									data-user-id="<?php echo esc_attr( $user->ID ); ?>"
									data-user-name="<?php echo esc_attr( $user->display_name ); ?>"
									data-review-nonce="<?php echo esc_attr( wp_create_nonce( 'cta_review_associate_' . $user->ID ) ); ?>"
								>
									<?php echo esc_html( $is_approved ? __( 'Revoke / Reject', 'cta-lms' ) : __( 'Reject', 'cta-lms' ) ); ?>
								</button>
							<?php elseif ( empty( $record['is_associate'] ) ) : ?>
								<span class="cta-approval-action-note"><?php esc_html_e( 'Not an Associate account', 'cta-lms' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<div id="cta-purchase-details-modal" class="cta-admin-modal" hidden>
		<div class="cta-admin-modal__content">
			<button type="button" class="cta-admin-modal__close" aria-label="<?php esc_attr_e( 'Close', 'cta-lms' ); ?>">&times;</button>
			<h2><?php esc_html_e( 'Supervision Purchase Details', 'cta-lms' ); ?></h2>
			<dl id="cta-purchase-details-list" class="cta-purchase-details-list"></dl>
		</div>
	</div>

	<div id="cta-reject-associate-modal" class="cta-admin-modal" hidden>
		<div class="cta-admin-modal__content">
			<button type="button" class="cta-admin-modal__close" aria-label="<?php esc_attr_e( 'Close', 'cta-lms' ); ?>">&times;</button>
			<h2><?php esc_html_e( 'Reject Associate', 'cta-lms' ); ?></h2>
			<p id="cta-reject-associate-name"></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cta-approval-form cta-approval-form--reject">
				<input type="hidden" name="action" value="cta_reject_associate">
				<input type="hidden" name="user_id" id="cta-reject-user-id" value="">
				<input type="hidden" name="cta_approval_nonce" id="cta-reject-nonce" value="">
				<p>
					<label for="cta-rejection-reason"><strong><?php esc_html_e( 'Reason (optional)', 'cta-lms' ); ?></strong></label>
				</p>
				<textarea id="cta-rejection-reason" name="reason" rows="5" class="large-text" placeholder="<?php echo esc_attr__( 'Add an internal reason for rejecting this supervision application.', 'cta-lms' ); ?>"></textarea>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Confirm Rejection', 'cta-lms' ); ?></button>
					<button type="button" class="button cta-admin-modal__close"><?php esc_html_e( 'Cancel', 'cta-lms' ); ?></button>
				</p>
			</form>
		</div>
	</div>
</div>
