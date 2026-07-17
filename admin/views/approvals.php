<?php
/**
 * Admin pending Associate approvals view.
 *
 * @package CTA_LMS
 *
 * @var WP_User[] $pending_associates Associates awaiting approval.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$flash = sanitize_text_field( wp_unslash( $_GET['cta_approval'] ?? '' ) );
?>
<div class="wrap cta-admin-wrap">
	<h1><?php esc_html_e( 'Pending Approvals', 'cta-lms' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Review Associates waiting for approval. Approving unlocks booking, meeting links, and supervision resources. Rejecting keeps access locked.', 'cta-lms' ); ?>
	</p>

	<?php if ( 'approved' === $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Associate approved. Supervision access is now unlocked.', 'cta-lms' ); ?></p></div>
	<?php elseif ( 'rejected' === $flash ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Associate rejected. Access remains locked.', 'cta-lms' ); ?></p></div>
	<?php elseif ( 'error' === $flash ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Unable to update approval status. Please try again.', 'cta-lms' ); ?></p></div>
	<?php endif; ?>

	<div id="cta-approvals-notice" class="notice" hidden></div>

	<table class="widefat striped cta-admin-table" id="cta-pending-approvals-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Associate', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Email', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Employer/Agency', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Agency Representative', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Registered', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Status', 'cta-lms' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'cta-lms' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $pending_associates ) ) : ?>
				<tr class="cta-approvals-empty">
					<td colspan="7"><?php esc_html_e( 'No Associates are currently pending approval.', 'cta-lms' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $pending_associates as $user ) : ?>
					<?php
					$agency_name = (string) get_user_meta( $user->ID, 'cta_employer_agency_name', true );
					$rep_name    = (string) get_user_meta( $user->ID, 'cta_agency_representative_name', true );
					$rep_email   = (string) get_user_meta( $user->ID, 'cta_agency_representative_email', true );
					?>
					<tr class="cta-approval-row" data-user-id="<?php echo esc_attr( $user->ID ); ?>">
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
							<span class="cta-approval-status-badge"><?php esc_html_e( 'Pending Approval', 'cta-lms' ); ?></span>
						</td>
						<td class="cta-table-actions">
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
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
