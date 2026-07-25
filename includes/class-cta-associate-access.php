<?php
/**
 * Associate approval access checks for supervision privileges.
 *
 * Until an Associate is Approved, they cannot access:
 * - supervision booking / scheduling
 * - meeting / join links
 * - supervision resources (documents & logs)
 *
 * @package CTA_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CTA_Associate_Access
 */
if ( ! class_exists( 'CTA_Associate_Access' ) ) {

class CTA_Associate_Access {

	const STATUS_PENDING  = 'pending_approval';
	const STATUS_APPROVED = 'approved';
	const STATUS_REJECTED = 'rejected';

	/**
	 * Get approval status meta for a user.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function get_approval_status( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return '';
		}

		return (string) get_user_meta( $user_id, 'cta_approval_status', true );
	}

	/**
	 * Whether the user is an Associate (role check).
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_associate( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		return in_array( 'cta_associate', (array) $user->roles, true );
	}

	/**
	 * Whether the user may purchase supervision (or a supervision/hybrid plan).
	 *
	 * Registered Associates only. Administrators are allowed for testing.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function can_purchase_supervision( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		$roles = (array) $user->roles;

		if ( in_array( 'administrator', $roles, true ) ) {
			return true;
		}

		return in_array( 'cta_associate', $roles, true );
	}

	/**
	 * Login/register page URL opened on the registration form.
	 *
	 * @return string
	 */
	public static function get_associate_registration_url() {
		$page_id = absint( get_option( 'cta_login_page_id', 0 ) );
		$url     = $page_id ? get_permalink( $page_id ) : '';

		if ( ! $url ) {
			$url = wp_registration_url();
		}

		if ( ! $url ) {
			$url = home_url( '/' );
		}

		return add_query_arg( 'cta_auth', 'register', $url );
	}

	/**
	 * Message shown when a non-associate tries to buy supervision.
	 *
	 * @return string
	 */
	public static function get_associate_required_message() {
		return __(
			'Supervision is available only to Registered Associates (AMFT, ASW, APCC). Please register as a Registered Associate to continue.',
			'cta-lms'
		);
	}

	/**
	 * Deny a purchase AJAX request when the user is not a Registered Associate.
	 *
	 * @param int $user_id User ID.
	 */
	public static function require_associate_for_purchase( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( self::can_purchase_supervision( $user_id ) ) {
			return;
		}

		wp_send_json_error(
			array(
				'message'      => self::get_associate_required_message(),
				'code'         => 'associate_required',
				'register_url' => self::get_associate_registration_url(),
			)
		);
	}

	/**
	 * Whether the Associate account is Approved.
	 *
	 * Non-associates and administrators are not subject to this gate.
	 * Legacy associates with empty status are treated as approved.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_approved( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		$roles = (array) $user->roles;

		if ( in_array( 'administrator', $roles, true ) ) {
			return true;
		}

		if ( ! in_array( 'cta_associate', $roles, true ) ) {
			return true;
		}

		$status = self::get_approval_status( $user_id );

		// Legacy accounts created before the approval flow have no meta.
		if ( '' === $status ) {
			return true;
		}

		return self::STATUS_APPROVED === $status;
	}

	/**
	 * Get supervision plan status meta for a user.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function get_supervision_status( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return '';
		}

		return (string) get_user_meta( $user_id, 'cta_supervision_status', true );
	}

	/**
	 * Whether the user has an Active supervision plan.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function has_active_supervision( $user_id = 0 ) {
		return 'active' === self::get_supervision_status( $user_id );
	}

	/**
	 * Whether supervision access is still pending approval.
	 *
	 * True when account approval or supervision plan status is Pending Approval.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_supervision_pending( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( $user && in_array( 'administrator', (array) $user->roles, true ) ) {
			return false;
		}

		$approval = self::get_approval_status( $user_id );
		$plan     = self::get_supervision_status( $user_id );

		return self::STATUS_PENDING === $approval || self::STATUS_PENDING === $plan;
	}

	/**
	 * Whether the user may use any unlocked supervision features.
	 *
	 * Requires an Approved associate account and an Active supervision plan.
	 * Administrators always pass.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function can_access_supervision_features( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		if ( in_array( 'administrator', (array) $user->roles, true ) ) {
			return true;
		}

		return self::is_approved( $user_id ) && self::has_active_supervision( $user_id );
	}

	/**
	 * Whether the user may use supervision booking / scheduling.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function can_access_booking( $user_id = 0 ) {
		return self::can_access_supervision_features( $user_id );
	}

	/**
	 * Whether the user may see or use session meeting / join links.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function can_access_meeting_links( $user_id = 0 ) {
		return self::can_access_supervision_features( $user_id );
	}

	/**
	 * Whether the user may access supervision resources (documents & logs).
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function can_access_supervision_resources( $user_id = 0 ) {
		return self::can_access_supervision_features( $user_id );
	}

	/**
	 * Shared denial message for gated supervision privileges.
	 *
	 * @return string
	 */
	public static function get_pending_message() {
		return __( 'Your supervision application is under review. You will be notified once approved.', 'cta-lms' );
	}

	/**
	 * Deny a supervision AJAX request when access is not fully approved.
	 *
	 * @param int $user_id User ID.
	 * @return true|void Sends JSON error and exits when denied.
	 */
	public static function require_supervision_access( $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( self::can_access_supervision_features( $user_id ) ) {
			return true;
		}

		wp_send_json_error(
			array(
				'message' => self::get_pending_message(),
				'code'    => 'supervision_pending_approval',
			)
		);
	}

	/**
	 * Set an Associate's approval status.
	 *
	 * @param int    $user_id User ID.
	 * @param string $status  pending_approval|approved|rejected.
	 * @return bool
	 */
	public static function set_approval_status( $user_id, $status ) {
		$user_id = absint( $user_id );
		$status  = sanitize_text_field( $status );

		$allowed = array(
			self::STATUS_PENDING,
			self::STATUS_APPROVED,
			self::STATUS_REJECTED,
		);

		if ( ! $user_id || ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		if ( ! self::is_associate( $user_id ) ) {
			return false;
		}

		update_user_meta( $user_id, 'cta_approval_status', $status );
		update_user_meta( $user_id, 'cta_approval_reviewed_at', current_time( 'mysql' ) );
		update_user_meta( $user_id, 'cta_approval_reviewed_by', get_current_user_id() );
		clean_user_cache( $user_id );

		return true;
	}

	/**
	 * Approve an Associate account (unlocks booking, meeting links, resources).
	 *
	 * Also promotes a purchased supervision plan from Pending Approval to Active.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function approve( $user_id ) {
		$ok = self::set_approval_status( $user_id, self::STATUS_APPROVED );

		if ( $ok ) {
			self::activate_purchased_supervision( $user_id );
			delete_user_meta( $user_id, 'cta_approval_rejection_reason' );
			clean_user_cache( $user_id );
		}

		return $ok;
	}

	/**
	 * Mark a purchased supervision plan Active after Associate approval.
	 *
	 * @param int $user_id User ID.
	 */
	public static function activate_purchased_supervision( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return;
		}

		$supervision_status = (string) get_user_meta( $user_id, 'cta_supervision_status', true );

		if ( 'active' === $supervision_status ) {
			return;
		}

		$has_purchase = false;

		if ( class_exists( 'CTA_Database' ) ) {
			$payment = CTA_Database::get_user_supervision_payment( $user_id, 'completed' );
			$has_purchase = (bool) $payment;
		}

		if ( ! $has_purchase && get_user_meta( $user_id, 'cta_hybrid_plan_active', true ) ) {
			$has_purchase = true;
		}

		if ( ! $has_purchase && self::STATUS_PENDING !== $supervision_status ) {
			return;
		}

		update_user_meta( $user_id, 'cta_supervision_status', 'active' );
	}

	/**
	 * Reject an Associate account (keeps privileges locked).
	 *
	 * @param int    $user_id User ID.
	 * @param string $reason  Optional rejection reason.
	 * @return bool
	 */
	public static function reject( $user_id, $reason = '' ) {
		$ok = self::set_approval_status( $user_id, self::STATUS_REJECTED );

		if ( ! $ok ) {
			return false;
		}

		$reason = sanitize_textarea_field( $reason );

		if ( '' === $reason ) {
			delete_user_meta( $user_id, 'cta_approval_rejection_reason' );
		} else {
			update_user_meta( $user_id, 'cta_approval_rejection_reason', $reason );
		}

		update_user_meta( $user_id, 'cta_supervision_status', 'rejected' );
		clean_user_cache( $user_id );

		return true;
	}

	/**
	 * Get Associates currently awaiting approval.
	 *
	 * @param int $limit Max users to return.
	 * @return WP_User[]
	 */
	public static function get_pending_associates( $limit = 200 ) {
		return self::get_associates_for_approvals( self::STATUS_PENDING, $limit );
	}

	/**
	 * Get Associates for the admin approvals screen.
	 *
	 * Rejected Associates are never listed — only Pending and Approved.
	 *
	 * @param string $status Optional filter: pending_approval|approved|all|''.
	 * @param int    $limit  Max users to return.
	 * @return WP_User[]
	 */
	public static function get_associates_for_approvals( $status = 'all', $limit = 200 ) {
		$status  = sanitize_text_field( (string) $status );
		$visible = array(
			self::STATUS_PENDING,
			self::STATUS_APPROVED,
		);

		// Rejected (and any other status) are excluded from this screen.
		if ( self::STATUS_REJECTED === $status ) {
			return array();
		}

		$meta_query = array(
			array(
				'key'     => 'cta_approval_status',
				'value'   => $visible,
				'compare' => 'IN',
			),
		);

		if ( in_array( $status, $visible, true ) ) {
			$meta_query = array(
				array(
					'key'   => 'cta_approval_status',
					'value' => $status,
				),
			);
		}

		$query = new WP_User_Query(
			array(
				'role'       => 'cta_associate',
				'meta_query' => $meta_query,
				'number'     => absint( $limit ),
				'orderby'    => 'registered',
				'order'      => 'DESC',
			)
		);

		$users = $query->get_results();

		return $users ? $users : array();
	}

	/**
	 * Count Associates by approval status (for Approvals tabs).
	 *
	 * Rejected are counted internally but not exposed in the "all" total used by the UI.
	 *
	 * @return array{pending_approval:int,approved:int,all:int}
	 */
	public static function count_associates_by_approval_status() {
		$counts = array(
			self::STATUS_PENDING  => 0,
			self::STATUS_APPROVED => 0,
			'all'                 => 0,
		);

		foreach ( array( self::STATUS_PENDING, self::STATUS_APPROVED ) as $status ) {
			$query = new WP_User_Query(
				array(
					'role'        => 'cta_associate',
					'meta_key'    => 'cta_approval_status',
					'meta_value'  => $status,
					'fields'      => 'ID',
					'number'      => 1,
					'count_total' => true,
				)
			);
			$counts[ $status ] = (int) $query->get_total();
		}

		$counts['all'] = $counts[ self::STATUS_PENDING ] + $counts[ self::STATUS_APPROVED ];

		return $counts;
	}

	/**
	 * Human-readable approval status label.
	 *
	 * @param string $status Status slug.
	 * @return string
	 */
	public static function get_status_label( $status ) {
		switch ( $status ) {
			case self::STATUS_APPROVED:
				return __( 'Approved', 'cta-lms' );
			case self::STATUS_REJECTED:
				return __( 'Rejected', 'cta-lms' );
			case self::STATUS_PENDING:
			default:
				return __( 'Pending Approval', 'cta-lms' );
		}
	}
}
}
