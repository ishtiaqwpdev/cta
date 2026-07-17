<?php
/**
 * AJAX authentication and login form shortcode.
 *
 * @package CTA_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CTA_Auth
 */
if ( ! class_exists( 'CTA_Auth' ) ) {

class CTA_Auth {

	/**
	 * Register AJAX handlers and shortcode.
	 */
	public function __construct() {
		add_action( 'wp_ajax_cta_login', array( $this, 'handle_login' ) );
		add_action( 'wp_ajax_nopriv_cta_login', array( $this, 'handle_login' ) );

		add_action( 'wp_ajax_cta_register', array( $this, 'handle_register' ) );
		add_action( 'wp_ajax_nopriv_cta_register', array( $this, 'handle_register' ) );

		add_shortcode( 'cta_login_form', array( $this, 'render_login_form' ) );
	}

	/**
	 * Render the login/register form shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_login_form( $atts ) {
		$is_logged_in  = is_user_logged_in();
		$dashboard_url = home_url( '/' );
		$user          = null;
		$home_url      = home_url( '/' );
		$logo_url      = CTA_PLUGIN_URL . 'assets/img/logo.png';
		$site_name     = get_bloginfo( 'name' );
		$lost_password_url = wp_lostpassword_url();
		$logout_url    = wp_logout_url( home_url() );

		if ( $is_logged_in ) {
			$user          = wp_get_current_user();
			$dashboard_url = $this->get_dashboard_url( $user );
		}

		ob_start();
		include CTA_PLUGIN_DIR . 'templates/login.php';
		return ob_get_clean();
	}

	/**
	 * Handle login AJAX request.
	 */
	public function handle_login() {
		check_ajax_referer( 'cta_login_action', 'nonce' );

		$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$password = wp_unslash( $_POST['password'] ?? '' );

		if ( empty( $email ) || empty( $password ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please enter your email and password.', 'cta-lms' ),
				)
			);
		}

		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			wp_send_json_error(
				array(
					'message' => __( 'No account found with this email address.', 'cta-lms' ),
				)
			);
		}

		$credentials = array(
			'user_login'    => $user->user_login,
			'user_password' => $password,
			'remember'      => true,
		);

		$result = wp_signon( $credentials, is_ssl() );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Incorrect password. Please try again.', 'cta-lms' ),
				)
			);
		}

		$redirect_url = $this->get_dashboard_url( $result );

		wp_send_json_success(
			array(
				'message'      => __( 'Login successful! Redirecting...', 'cta-lms' ),
				'redirect_url' => $redirect_url,
			)
		);
	}

	/**
	 * Handle registration AJAX request.
	 */
	public function handle_register() {
		check_ajax_referer( 'cta_register_action', 'nonce' );

		$fullname  = sanitize_text_field( wp_unslash( $_POST['fullname'] ?? '' ) );
		$email     = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$password  = wp_unslash( $_POST['password'] ?? '' );
		$confirm   = wp_unslash( $_POST['confirm_password'] ?? '' );
		$user_type = sanitize_text_field( wp_unslash( $_POST['user_type'] ?? '' ) );

		$employer_agency_name         = sanitize_text_field( wp_unslash( $_POST['employer_agency_name'] ?? '' ) );
		$agency_representative_name   = sanitize_text_field( wp_unslash( $_POST['agency_representative_name'] ?? '' ) );
		$agency_representative_email  = sanitize_email( wp_unslash( $_POST['agency_representative_email'] ?? '' ) );

		if ( empty( $fullname ) || empty( $email ) || empty( $password ) || empty( $user_type ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please fill in all required fields.', 'cta-lms' ),
				)
			);
		}

		if ( $password !== $confirm ) {
			wp_send_json_error(
				array(
					'message' => __( 'Passwords do not match.', 'cta-lms' ),
				)
			);
		}

		if ( strlen( $password ) < 8 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Password must be at least 8 characters.', 'cta-lms' ),
				)
			);
		}

		$allowed_roles = array(
			'cta_licensed_professional',
			'cta_associate',
		);

		if ( ! in_array( $user_type, $allowed_roles, true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please select a valid account type.', 'cta-lms' ),
				)
			);
		}

		if ( 'cta_associate' === $user_type ) {
			if (
				empty( $employer_agency_name ) ||
				empty( $agency_representative_name ) ||
				empty( $agency_representative_email )
			) {
				wp_send_json_error(
					array(
						'message' => __( 'Please fill in all agency fields.', 'cta-lms' ),
					)
				);
			}

			if ( ! is_email( $agency_representative_email ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Please enter a valid agency representative email.', 'cta-lms' ),
					)
				);
			}
		}

		if ( email_exists( $email ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'An account with this email already exists.', 'cta-lms' ),
				)
			);
		}

		$username = sanitize_user( strstr( $email, '@', true ), true );

		if ( username_exists( $username ) ) {
			$username = $username . '_' . time();
		}

		$user_id = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Registration failed. Please try again.', 'cta-lms' ),
				)
			);
		}

		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => $fullname,
			)
		);

		$user_obj = new WP_User( $user_id );
		$user_obj->set_role( $user_type );

		if ( 'cta_associate' === $user_type ) {
			update_user_meta( $user_id, 'cta_employer_agency_name', $employer_agency_name );
			update_user_meta( $user_id, 'cta_agency_representative_name', $agency_representative_name );
			update_user_meta( $user_id, 'cta_agency_representative_email', $agency_representative_email );
			update_user_meta( $user_id, 'cta_approval_status', 'pending_approval' );
		}

		// Refresh user so role/meta are available to email helpers.
		clean_user_cache( $user_id );
		$user = get_user_by( 'id', $user_id );

		CTA_Emails::send( 'welcome', $user_id );

		if ( 'cta_associate' === $user_type ) {
			CTA_Emails::send(
				'agency_representative_approval',
				$user_id,
				array(
					'employer_agency_name'        => $employer_agency_name,
					'agency_representative_name'  => $agency_representative_name,
					'agency_representative_email' => $agency_representative_email,
				)
			);
		}

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );

		$redirect_url = $this->get_dashboard_url( $user );

		wp_send_json_success(
			array(
				'message'      => __( 'Account created successfully! Redirecting...', 'cta-lms' ),
				'redirect_url' => $redirect_url,
			)
		);
	}

	/**
	 * Get frontend client dashboard URL based on user role.
	 *
	 * Never sends users to wp-admin from the login/register UI.
	 *
	 * @param WP_User $user WordPress user object.
	 * @return string
	 */
	private function get_dashboard_url( $user ) {
		$roles = (array) $user->roles;

		if ( in_array( 'cta_associate', $roles, true ) ) {
			return $this->resolve_dashboard_page_url(
				'cta_supervision_dashboard_page_id',
				'cta_supervision_dashboard'
			);
		}

		if ( in_array( 'cta_licensed_professional', $roles, true ) ) {
			return $this->resolve_dashboard_page_url(
				'cta_student_dashboard_page_id',
				'cta_student_dashboard'
			);
		}

		// Administrators and other roles: prefer the frontend client dashboards.
		$student_url = $this->resolve_dashboard_page_url(
			'cta_student_dashboard_page_id',
			'cta_student_dashboard'
		);

		if ( $this->is_usable_frontend_url( $student_url ) ) {
			return $student_url;
		}

		$supervision_url = $this->resolve_dashboard_page_url(
			'cta_supervision_dashboard_page_id',
			'cta_supervision_dashboard'
		);

		if ( $this->is_usable_frontend_url( $supervision_url ) ) {
			return $supervision_url;
		}

		return home_url( '/' );
	}

	/**
	 * Resolve a CTA dashboard page URL from option or shortcode fallback.
	 *
	 * @param string $option_name Option key storing page ID.
	 * @param string $shortcode   Shortcode tag used to find the page.
	 * @return string
	 */
	private function resolve_dashboard_page_url( $option_name, $shortcode ) {
		$page_id = absint( get_option( $option_name, 0 ) );

		if ( ! $page_id && function_exists( 'cta_lms_find_page_id_by_shortcode' ) ) {
			$page_id = absint( cta_lms_find_page_id_by_shortcode( $shortcode ) );
		}

		if ( ! $page_id ) {
			return home_url( '/' );
		}

		$url = get_permalink( $page_id );

		return $url ? $url : home_url( '/' );
	}

	/**
	 * Whether a URL is a usable frontend destination (not empty/home-only fallback).
	 *
	 * @param string $url Candidate URL.
	 * @return bool
	 */
	private function is_usable_frontend_url( $url ) {
		$url = (string) $url;

		if ( '' === $url ) {
			return false;
		}

		$home = untrailingslashit( home_url( '/' ) );
		$cand = untrailingslashit( $url );

		return $cand && $cand !== $home;
	}
}
}