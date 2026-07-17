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
	 * Whether the user may use supervision booking / scheduling.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function can_access_booking( $user_id = 0 ) {
		return self::is_approved( $user_id );
	}

	/**
	 * Whether the user may see or use session meeting / join links.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function can_access_meeting_links( $user_id = 0 ) {
		return self::is_approved( $user_id );
	}

	/**
	 * Whether the user may access supervision resources (documents & logs).
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function can_access_supervision_resources( $user_id = 0 ) {
		return self::is_approved( $user_id );
	}

	/**
	 * Shared denial message for gated supervision privileges.
	 *
	 * @return string
	 */
	public static function get_pending_message() {
		return __( 'Your account is pending approval. Supervision booking, meeting links, and resources will be available once your account is approved.', 'cta-lms' );
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

		return true;
	}

	/**
	 * Approve an Associate account (unlocks booking, meeting links, resources).
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function approve( $user_id ) {
		return self::set_approval_status( $user_id, self::STATUS_APPROVED );
	}

	/**
	 * Reject an Associate account (keeps privileges locked).
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function reject( $user_id ) {
		return self::set_approval_status( $user_id, self::STATUS_REJECTED );
	}

	/**
	 * Get Associates currently awaiting approval.
	 *
	 * @param int $limit Max users to return.
	 * @return WP_User[]
	 */
	public static function get_pending_associates( $limit = 200 ) {
		$query = new WP_User_Query(
			array(
				'role'       => 'cta_associate',
				'meta_key'   => 'cta_approval_status',
				'meta_value' => self::STATUS_PENDING,
				'number'     => absint( $limit ),
				'orderby'    => 'registered',
				'order'      => 'DESC',
			)
		);

		$users = $query->get_results();

		return $users ? $users : array();
	}
}
}
