<?php
/**
 * WordPress admin panel for CTA LMS.
 *
 * @package CTA_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CTA_Admin
 */
if ( ! class_exists( 'CTA_Admin' ) ) {

class CTA_Admin {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		// Hide on admin_head (after core's access check, before the sidebar renders)
		// so the page stays directly accessible while staying out of the menu.
		add_action( 'admin_head', array( $this, 'hide_course_edit_submenu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_head', array( $this, 'print_admin_menu_icon_styles' ) );
		add_action( 'admin_init', array( $this, 'redirect_frontend_roles_from_admin' ) );

		add_action( 'admin_post_cta_save_course', array( $this, 'save_course' ) );
		add_action( 'admin_post_cta_delete_course', array( $this, 'delete_course' ) );
		add_action( 'admin_post_cta_toggle_course', array( $this, 'toggle_course_status' ) );
		add_action( 'admin_post_cta_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_cta_save_email_settings', array( $this, 'save_email_settings' ) );
		add_action( 'admin_post_cta_extend_exam_access', array( $this, 'extend_exam_access' ) );
		add_action( 'admin_post_cta_save_resource', array( $this, 'save_resource' ) );
		add_action( 'admin_post_cta_delete_resource', array( $this, 'delete_resource' ) );
		add_action( 'admin_post_cta_save_evaluation_question', array( $this, 'save_evaluation_question' ) );
		add_action( 'admin_post_cta_delete_evaluation_question', array( $this, 'delete_evaluation_question' ) );
		add_action( 'admin_post_cta_reorder_evaluation_questions', array( $this, 'reorder_evaluation_questions' ) );
		add_action( 'wp_ajax_cta_reorder_resources', array( $this, 'ajax_reorder_resources' ) );

		add_action( 'wp_ajax_cta_admin_get_stats', array( $this, 'ajax_get_stats' ) );
		add_action( 'wp_ajax_cta_admin_save_license', array( $this, 'ajax_save_user_license' ) );
		add_action( 'wp_ajax_cta_save_module', array( $this, 'ajax_save_module' ) );
		add_action( 'wp_ajax_cta_delete_module', array( $this, 'ajax_delete_module' ) );
		add_action( 'wp_ajax_cta_reorder_modules', array( $this, 'ajax_reorder_modules' ) );
		add_action( 'wp_ajax_cta_review_document', array( $this, 'ajax_review_document' ) );
		add_action( 'wp_ajax_cta_admin_add_session', array( $this, 'ajax_add_session' ) );
		add_action( 'wp_ajax_cta_admin_cancel_session', array( $this, 'ajax_cancel_session' ) );
		add_action( 'wp_ajax_cta_test_stripe_connection', array( $this, 'ajax_test_stripe_connection' ) );
		add_action( 'wp_ajax_cta_ensure_billing_portal', array( $this, 'ajax_ensure_billing_portal' ) );
		add_action( 'wp_ajax_cta_admin_cancel_subscription', array( $this, 'ajax_admin_cancel_subscription' ) );
		add_action( 'wp_ajax_cta_admin_reactivate_subscription', array( $this, 'ajax_admin_reactivate_subscription' ) );
		add_action( 'wp_ajax_cta_admin_sync_subscription', array( $this, 'ajax_admin_sync_subscription' ) );
		add_action( 'wp_ajax_cta_preview_certificate', array( $this, 'ajax_preview_certificate' ) );
		add_action( 'wp_ajax_cta_preview_email', array( $this, 'ajax_preview_email' ) );
		add_action( 'wp_ajax_cta_save_quiz', array( $this, 'ajax_save_quiz' ) );
		add_action( 'wp_ajax_cta_load_quiz', array( $this, 'ajax_load_quiz' ) );
		add_action( 'wp_ajax_cta_approve_associate', array( $this, 'ajax_approve_associate' ) );
		add_action( 'wp_ajax_cta_reject_associate', array( $this, 'ajax_reject_associate' ) );
		add_action( 'wp_ajax_cta_assign_associate_plan', array( $this, 'ajax_assign_associate_plan' ) );
		add_action( 'admin_post_cta_approve_associate', array( $this, 'handle_approve_associate' ) );
		add_action( 'admin_post_cta_reject_associate', array( $this, 'handle_reject_associate' ) );
		add_action( 'admin_post_cta_assign_associate_plan', array( $this, 'handle_assign_associate_plan' ) );
	}

	/**
	 * Register admin menus.
	 */
	public function register_menus() {
		$pending_approval_count = $this->get_pending_approval_count();
		$approvals_menu_title   = __( 'Approvals', 'cta-lms' );

		if ( $pending_approval_count > 0 ) {
			$approvals_menu_title .= sprintf(
				' <span class="awaiting-mod count-%1$d"><span class="pending-count">%1$d</span></span>',
				$pending_approval_count
			);
		}

		add_menu_page(
			__( 'CTA LMS', 'cta-lms' ),
			__( 'CTA LMS', 'cta-lms' ),
			'manage_options',
			'cta-lms',
			array( $this, 'render_dashboard' ),
			CTA_PLUGIN_URL . 'assets/img/admin-icon.svg',
			30
		);

		add_submenu_page(
			'cta-lms',
			__( 'Dashboard', 'cta-lms' ),
			__( 'Dashboard', 'cta-lms' ),
			'manage_options',
			'cta-lms',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'cta-lms',
			__( 'Courses', 'cta-lms' ),
			__( 'Courses', 'cta-lms' ),
			'manage_options',
			'cta-lms-courses',
			array( $this, 'render_courses' )
		);

		add_submenu_page(
			'cta-lms',
			__( 'Edit Course', 'cta-lms' ),
			__( 'Edit Course', 'cta-lms' ),
			'manage_options',
			'cta-lms-course-edit',
			array( $this, 'render_course_edit' )
		);

		add_submenu_page(
			'cta-lms',
			__( 'Users', 'cta-lms' ),
			__( 'Users', 'cta-lms' ),
			'manage_options',
			'cta-lms-users',
			array( $this, 'render_users' )
		);

		add_submenu_page(
			'cta-lms',
			__( 'Approvals', 'cta-lms' ),
			$approvals_menu_title,
			'manage_options',
			'cta-lms-approvals',
			array( $this, 'render_approvals' )
		);

		add_submenu_page(
			'cta-lms',
			__( 'Bookings', 'cta-lms' ),
			__( 'Bookings', 'cta-lms' ),
			'manage_options',
			'cta-lms-bookings',
			array( $this, 'render_bookings' )
		);

		add_submenu_page(
			'cta-lms',
			__( 'Settings', 'cta-lms' ),
			__( 'Settings', 'cta-lms' ),
			'manage_options',
			'cta-lms-settings',
			array( $this, 'render_settings' )
		);

		add_submenu_page(
			'cta-lms',
			__( 'Course Evaluation', 'cta-lms' ),
			__( 'Course Evaluation', 'cta-lms' ),
			'manage_options',
			'cta-lms-evaluation',
			array( $this, 'render_evaluation' )
		);

		add_submenu_page(
			'cta-lms',
			__( 'Email Settings', 'cta-lms' ),
			__( 'Email Settings', 'cta-lms' ),
			'manage_options',
			'cta-lms-email-settings',
			array( $this, 'render_email_settings' )
		);

		add_submenu_page(
			'cta-lms',
			__( 'Shortcodes', 'cta-lms' ),
			__( 'Shortcodes', 'cta-lms' ),
			'manage_options',
			'cta-lms-shortcodes',
			array( $this, 'render_shortcodes' )
		);
	}

	/**
	 * Count records currently waiting in the Approvals queue.
	 *
	 * @return int
	 */
	private function get_pending_approval_count() {
		if ( ! class_exists( 'CTA_Associate_Access' ) ) {
			return 0;
		}

		$count = 0;

		foreach ( $this->get_supervision_purchase_records() as $record ) {
			if ( CTA_Associate_Access::STATUS_PENDING === $record['status'] ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Keep course edit registered under CTA LMS, but hide it from the sidebar.
	 *
	 * Must run on admin_head, not admin_menu: WordPress runs its capability check
	 * (user_can_access_admin_page) at the end of the admin_menu cycle. Removing the
	 * submenu during admin_menu strips the parent link needed for that check, which
	 * denies administrators access ("Sorry, you are not allowed to access this page").
	 * admin_head fires after the check but before the sidebar is rendered, so the page
	 * stays reachable while remaining hidden from the menu.
	 */
	public function hide_course_edit_submenu() {
		remove_submenu_page( 'cta-lms', 'cta-lms-course-edit' );
	}

	/**
	 * Send CTA learner/associate roles to their frontend dashboard instead of wp-admin.
	 */
	public function redirect_frontend_roles_from_admin() {
		if ( ! is_user_logged_in() || wp_doing_ajax() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		$user  = wp_get_current_user();
		$roles = (array) $user->roles;

		$is_cta_frontend_role = in_array( 'cta_associate', $roles, true )
			|| in_array( 'cta_licensed_professional', $roles, true );

		if ( ! $is_cta_frontend_role ) {
			return;
		}

		$redirect = home_url( '/' );

		if ( in_array( 'cta_associate', $roles, true ) ) {
			$page_id = absint( get_option( 'cta_supervision_dashboard_page_id', 0 ) );
			if ( $page_id ) {
				$url = get_permalink( $page_id );
				if ( $url ) {
					$redirect = $url;
				}
			}
		} else {
			$page_id = absint( get_option( 'cta_student_dashboard_page_id', 0 ) );
			if ( $page_id ) {
				$url = get_permalink( $page_id );
				if ( $url ) {
					$redirect = $url;
				}
			}
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Ensure the CTA LMS admin menu icon renders at the correct size.
	 */
	public function print_admin_menu_icon_styles() {
		echo '<style>#adminmenu .toplevel_page_cta-lms .wp-menu-image img{width:20px;height:20px;padding:6px 0 0;opacity:.6}#adminmenu .toplevel_page_cta-lms.wp-has-current-submenu .wp-menu-image img,#adminmenu .toplevel_page_cta-lms.current .wp-menu-image img{opacity:1}</style>';
	}

	/**
	 * Enqueue admin assets on plugin pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( false === strpos( $hook, 'cta-lms' ) ) {
			return;
		}

		if ( class_exists( 'CTA_Database' ) ) {
			CTA_Database::ensure_tables();
		}

		wp_enqueue_style(
			'cta-admin-fonts',
			'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap',
			array(),
			null
		);

		wp_enqueue_style(
			'cta-admin',
			CTA_PLUGIN_URL . 'admin/assets/css/admin.css',
			array( 'cta-admin-fonts' ),
			CTA_VERSION
		);

		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script(
			'cta-admin',
			CTA_PLUGIN_URL . 'admin/assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			CTA_VERSION,
			true
		);

		wp_localize_script(
			'cta-admin',
			'ctaAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cta_admin_nonce' ),
				'i18n'    => array(
					'confirmDelete'  => __( 'Are you sure you want to delete this item?', 'cta-lms' ),
					'confirmCancel'  => __( 'Cancel this session and notify booked users?', 'cta-lms' ),
					'copied'         => __( 'Copied!', 'cta-lms' ),
					'stripeTesting'  => __( 'Testing connection...', 'cta-lms' ),
					'stripeSuccess'  => __( 'Stripe connection successful.', 'cta-lms' ),
					'stripeFailed'   => __( 'Stripe connection failed.', 'cta-lms' ),
					'approveConfirm' => __( 'Approve this Associate application? Dashboard access unlocks only after they also have a purchased or admin-assigned plan.', 'cta-lms' ),
					'rejectConfirm'  => __( 'Reject this Associate? They will remain locked out of booking, meeting links, and resources.', 'cta-lms' ),
					'approveSuccess' => __( 'Associate approved.', 'cta-lms' ),
					'rejectSuccess'  => __( 'Associate rejected.', 'cta-lms' ),
					'approveNoPlan'  => __( 'Approval saved. Plan is still required for dashboard access.', 'cta-lms' ),
					'assignSuccess'  => __( 'Plan assigned. If already Approved, supervision access is now active.', 'cta-lms' ),
					'assignConfirm'  => __( 'Assign this agency-paid plan to the Associate?', 'cta-lms' ),
					'actionFailed'   => __( 'Unable to update approval status. Please try again.', 'cta-lms' ),
				),
			)
		);

		if (
			'cta-lms_page_cta-lms-course-edit' === $hook
			|| 'cta-lms_page_cta-lms-email-settings' === $hook
		) {
			wp_enqueue_editor();
		}

		if ( 'cta-lms_page_cta-lms-course-edit' === $hook ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Render dashboard view.
	 */
	public function render_dashboard() {
		$this->load_view(
			'dashboard.php',
			array(
				'stats'               => self::get_dashboard_stats(),
				'recent_enrollments'  => self::get_recent_enrollments( 10 ),
				'recent_bookings'     => self::get_recent_bookings( 5 ),
			)
		);
	}

	/**
	 * Render courses list.
	 */
	public function render_courses() {
		global $wpdb;

		$status       = sanitize_text_field( wp_unslash( $_GET['status'] ?? 'all' ) );
		$search       = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$product_type = sanitize_text_field( wp_unslash( $_GET['product_type'] ?? 'ce' ) );
		if ( ! in_array( $product_type, array( 'ce', 'exam_prep', 'all' ), true ) ) {
			$product_type = 'ce';
		}

		$table  = $wpdb->prefix . 'cta_courses';
		$where  = array( '1=1' );
		$params = array();

		if ( in_array( $status, array( 'published', 'draft' ), true ) ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}

		if ( 'all' !== $product_type ) {
			$where[]  = 'product_type = %s';
			$params[] = $product_type;
		}

		if ( $search ) {
			$where[]  = 'title LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY created_at DESC';

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$courses = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$courses = $wpdb->get_results( $sql );
		}

		$enrollment_counts = array();
		$count_rows        = $wpdb->get_results(
			"SELECT course_id, COUNT(*) AS total FROM {$wpdb->prefix}cta_enrollments GROUP BY course_id"
		);

		foreach ( $count_rows as $row ) {
			$enrollment_counts[ (int) $row->course_id ] = (int) $row->total;
		}

		$access_counts = array();
		if ( 'exam_prep' === $product_type || 'all' === $product_type ) {
			$access_rows = $wpdb->get_results(
				"SELECT course_id, COUNT(*) AS total FROM {$wpdb->prefix}cta_exam_access GROUP BY course_id"
			);
			foreach ( (array) $access_rows as $row ) {
				$access_counts[ (int) $row->course_id ] = (int) $row->total;
			}
		}

		$this->load_view(
			'courses.php',
			array(
				'courses'           => $courses ? $courses : array(),
				'enrollment_counts' => $enrollment_counts,
				'access_counts'     => $access_counts,
				'status_filter'     => $status,
				'product_type'      => $product_type,
				'search'            => $search,
			)
		);
	}

	/**
	 * Render course add/edit form.
	 */
	public function render_course_edit() {
		$course_id = absint( wp_unslash( $_GET['course_id'] ?? 0 ) );
		$course    = $course_id ? CTA_Database::get_course( $course_id ) : null;
		$modules   = $course_id ? CTA_Database::get_course_modules( $course_id ) : array();
		$quiz      = $course_id ? $this->get_course_quiz( $course_id ) : null;
		$quiz_questions = ( $quiz ) ? CTA_Database::get_quiz_questions( (int) $quiz->id ) : array();
		$resources = $course_id ? CTA_Database::get_downloadable_resources( $course_id ) : array();
		$objectives = array();

		$default_product_type = sanitize_text_field( wp_unslash( $_GET['product_type'] ?? 'ce' ) );
		if ( ! in_array( $default_product_type, array( 'ce', 'exam_prep' ), true ) ) {
			$default_product_type = 'ce';
		}

		if ( $course && ! empty( $course->learning_objectives ) ) {
			$decoded = json_decode( (string) $course->learning_objectives, true );
			if ( is_array( $decoded ) ) {
				$objectives = $decoded;
			}
		}

		if ( empty( $objectives ) ) {
			$objectives = array( '' );
		}

		$exam_learners = array();
		if ( $course && CTA_Exam_Access::is_exam_prep( $course ) ) {
			global $wpdb;
			$exam_learners = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT a.*, u.display_name, u.user_email
					FROM {$wpdb->prefix}cta_exam_access a
					LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
					WHERE a.course_id = %d
					ORDER BY a.expires_at DESC, a.id DESC
					LIMIT 200",
					$course_id
				)
			);
		}

		$this->load_view(
			'courses-edit.php',
			array(
				'course'               => $course,
				'course_id'            => $course_id,
				'modules'              => $modules,
				'quiz'                 => $quiz,
				'quiz_questions'       => $quiz_questions,
				'resources'            => $resources,
				'objectives'           => $objectives,
				'categories'           => self::get_course_categories(),
				'default_product_type' => $default_product_type,
				'exam_learners'        => $exam_learners ? $exam_learners : array(),
			)
		);
	}

	/**
	 * Render users list.
	 */
	public function render_users() {
		$role_filter    = sanitize_text_field( wp_unslash( $_GET['role'] ?? 'all' ) );
		$search         = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$license_filter = sanitize_text_field( wp_unslash( $_GET['license'] ?? 'all' ) );
		$supervision_filter = sanitize_text_field( wp_unslash( $_GET['supervision'] ?? 'all' ) );
		if ( ! in_array( $license_filter, array( 'all', 'missing', 'present' ), true ) ) {
			$license_filter = 'all';
		}
		$allowed_supervision = array( 'all', 'active', 'past_due', 'locked', 'cancelled', 'pending_approval', 'none' );
		if ( ! in_array( $supervision_filter, $allowed_supervision, true ) ) {
			$supervision_filter = 'all';
		}

		$args = array(
			'number'  => 200,
			'orderby' => 'registered',
			'order'   => 'DESC',
		);

		if ( 'licensed' === $role_filter ) {
			$args['role'] = 'cta_licensed_professional';
		} elseif ( 'associate' === $role_filter ) {
			$args['role'] = 'cta_associate';
		} elseif ( 'administrator' === $role_filter ) {
			$args['role'] = 'administrator';
		} else {
			$args['role__in'] = array( 'cta_licensed_professional', 'cta_associate', 'administrator' );
		}

		if ( $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		$meta_query = array();

		if ( 'missing' === $license_filter ) {
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => 'cta_license_number',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'cta_license_number',
					'value'   => '',
					'compare' => '=',
				),
			);
		} elseif ( 'present' === $license_filter ) {
			$meta_query[] = array(
				'key'     => 'cta_license_number',
				'value'   => '',
				'compare' => '!=',
			);
		}

		if ( 'none' === $supervision_filter ) {
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => 'cta_supervision_status',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'cta_supervision_status',
					'value'   => '',
					'compare' => '=',
				),
			);
		} elseif ( 'all' !== $supervision_filter ) {
			$meta_query[] = array(
				'key'     => 'cta_supervision_status',
				'value'   => $supervision_filter,
				'compare' => '=',
			);
		}

		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		} elseif ( 1 === count( $meta_query ) ) {
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$user_query = new WP_User_Query( $args );
		$users      = $user_query->get_results();

		// Count students missing license info (for badge on filter).
		$missing_count_query = new WP_User_Query(
			array(
				'role__in'   => array( 'cta_licensed_professional', 'cta_associate' ),
				'number'     => 1,
				'count_total'=> true,
				'fields'     => 'ID',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => 'cta_license_number',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'cta_license_number',
						'value'   => '',
						'compare' => '=',
					),
				),
			)
		);
		$missing_license_count = (int) $missing_count_query->get_total();

		$this->load_view(
			'users.php',
			array(
				'users'                 => $users ? $users : array(),
				'role_filter'           => $role_filter,
				'search'                => $search,
				'license_filter'        => $license_filter,
				'supervision_filter'    => $supervision_filter,
				'missing_license_count' => $missing_license_count,
				'license_types'         => cta_lms_get_license_types(),
			)
		);
	}

	/**
	 * AJAX: admin save/correct a student's license number and type.
	 *
	 * Writes the same user meta keys the student Account Settings form uses
	 * (`cta_license_number`, `cta_license_type`).
	 */
	public function ajax_save_user_license() {
		$this->verify_admin_ajax();

		$user_id        = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
		$license_number = cta_lms_sanitize_license_number( wp_unslash( $_POST['license_number'] ?? '' ) );
		$license_type   = sanitize_text_field( wp_unslash( $_POST['license_type'] ?? '' ) );
		$allowed_types  = cta_lms_get_license_types();

		if ( ! $user_id || ! get_userdata( $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user.', 'cta-lms' ) ) );
		}

		if ( ! cta_lms_is_valid_license_number( $license_number ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'License number looks invalid. Include at least one letter or number.', 'cta-lms' ),
				)
			);
		}

		if ( '' !== $license_type && ! in_array( $license_type, $allowed_types, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid license type.', 'cta-lms' ) ) );
		}

		update_user_meta( $user_id, 'cta_license_number', $license_number );

		if ( '' === $license_type ) {
			delete_user_meta( $user_id, 'cta_license_type' );
		} else {
			update_user_meta( $user_id, 'cta_license_type', $license_type );
		}

		if ( class_exists( 'CTA_Certificates' ) ) {
			CTA_Certificates::refresh_user_certificates( $user_id );
		}

		wp_send_json_success(
			array(
				'message'         => __( 'License information updated.', 'cta-lms' ),
				'user_id'         => $user_id,
				'license_number'  => $license_number,
				'license_type'    => $license_type,
				'has_license'     => '' !== $license_number,
			)
		);
	}

	/**
	 * Render supervision purchase approvals.
	 */
	public function render_approvals() {
		$status = sanitize_text_field( wp_unslash( $_GET['status'] ?? 'all' ) );
		$allowed_status = array( 'all', 'pending_approval', 'approved', 'rejected' );

		if ( ! in_array( $status, $allowed_status, true ) ) {
			$status = 'all';
		}

		$purchase_records = array();
		$counts           = array(
			'pending_approval' => 0,
			'approved'         => 0,
			'rejected'         => 0,
			'all'              => 0,
		);

		if ( class_exists( 'CTA_Associate_Access' ) ) {
			$purchase_records = $this->get_supervision_purchase_records();

			foreach ( $purchase_records as $record ) {
				if ( isset( $counts[ $record['status'] ] ) ) {
					$counts[ $record['status'] ]++;
				}
			}

			$counts['all'] = count( $purchase_records );

			if ( 'all' !== $status ) {
				$purchase_records = array_values(
					array_filter(
						$purchase_records,
						static function ( $record ) use ( $status ) {
							return $status === $record['status'];
						}
					)
				);
			}
		}

		$this->load_view(
			'approvals.php',
			array(
				'purchase_records'=> $purchase_records,
				'current_status'  => $status,
				'status_counts'   => $counts,
			)
		);
	}

	/**
	 * Build Approvals queue rows.
	 *
	 * Includes:
	 * 1. Registered Associates with an approval status (even before purchase)
	 * 2. Users with a completed supervision / hybrid purchase
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_supervision_purchase_records() {
		global $wpdb;

		$by_user = array();

		// All Registered Associates (purchase optional — registration alone can be Pending Approval).
		$associate_query = new WP_User_Query(
			array(
				'role'    => 'cta_associate',
				'number'  => 500,
				'orderby' => 'registered',
				'order'   => 'DESC',
			)
		);

		foreach ( (array) $associate_query->get_results() as $user ) {
			$by_user[ (int) $user->ID ] = $this->build_approval_record( $user, null );
		}

		// Also catch anyone flagged pending/rejected/approved via meta even if role lookup missed them.
		$meta_statuses = array(
			CTA_Associate_Access::STATUS_PENDING,
			CTA_Associate_Access::STATUS_APPROVED,
			CTA_Associate_Access::STATUS_REJECTED,
		);

		foreach ( $meta_statuses as $meta_status ) {
			$meta_query = new WP_User_Query(
				array(
					'number'     => 500,
					'fields'     => 'all',
					'meta_key'   => 'cta_approval_status',
					'meta_value' => $meta_status,
				)
			);

			foreach ( (array) $meta_query->get_results() as $user ) {
				$user_id = (int) $user->ID;
				if ( isset( $by_user[ $user_id ] ) ) {
					continue;
				}
				$by_user[ $user_id ] = $this->build_approval_record( $user, null );
			}
		}

		// Pending supervision plan without a completed payment row yet.
		$pending_plan_users = new WP_User_Query(
			array(
				'number'     => 500,
				'meta_key'   => 'cta_supervision_status',
				'meta_value' => 'pending_approval',
			)
		);

		foreach ( (array) $pending_plan_users->get_results() as $user ) {
			$user_id = (int) $user->ID;
			if ( isset( $by_user[ $user_id ] ) ) {
				continue;
			}
			$by_user[ $user_id ] = $this->build_approval_record( $user, null );
		}

		// Completed supervision / hybrid purchases (may overlap associates above).
		$table = $wpdb->prefix . 'cta_payments';
		$rows  = $wpdb->get_results(
			"SELECT payment.*
			FROM {$table} payment
			INNER JOIN (
				SELECT user_id, MAX(id) AS latest_id
				FROM {$table}
				WHERE status IN ('completed', 'pending')
				AND (
					product_type = 'supervision'
					OR (
						product_type = 'bundle'
						AND (
							plan_details LIKE '%\"plan_slug\":\"hybrid\"%'
							OR plan_name LIKE '%Hybrid%'
							OR plan_name LIKE '%All-Access Program%'
							OR plan_name LIKE '%Supervision%'
						)
					)
				)
				GROUP BY user_id
			) latest ON latest.latest_id = payment.id
			ORDER BY payment.created_at DESC, payment.id DESC"
		);

		foreach ( $rows as $payment ) {
			$user_id = (int) $payment->user_id;
			$user    = isset( $by_user[ $user_id ] )
				? $by_user[ $user_id ]['user']
				: get_user_by( 'id', $user_id );

			if ( ! $user ) {
				continue;
			}

			$by_user[ $user_id ] = $this->build_approval_record( $user, $payment );
		}

		$records = array_values( $by_user );

		usort(
			$records,
			static function ( $a, $b ) {
				$a_time = ! empty( $a['payment']->created_at )
					? strtotime( (string) $a['payment']->created_at )
					: strtotime( (string) $a['user']->user_registered );
				$b_time = ! empty( $b['payment']->created_at )
					? strtotime( (string) $b['payment']->created_at )
					: strtotime( (string) $b['user']->user_registered );

				return $b_time <=> $a_time;
			}
		);

		return $records;
	}

	/**
	 * Normalize one Approvals table row.
	 *
	 * @param WP_User     $user    User object.
	 * @param object|null $payment Optional payment row.
	 * @return array<string,mixed>
	 */
	private function build_approval_record( $user, $payment = null ) {
		$approval_status = CTA_Associate_Access::get_approval_status( $user->ID );

		if ( ! in_array(
			$approval_status,
			array(
				CTA_Associate_Access::STATUS_PENDING,
				CTA_Associate_Access::STATUS_APPROVED,
				CTA_Associate_Access::STATUS_REJECTED,
			),
			true
		) ) {
			$supervision_status = (string) get_user_meta( $user->ID, 'cta_supervision_status', true );

			if ( 'rejected' === $supervision_status ) {
				$approval_status = CTA_Associate_Access::STATUS_REJECTED;
			} elseif ( 'pending_approval' === $supervision_status ) {
				$approval_status = CTA_Associate_Access::STATUS_PENDING;
			} elseif (
				'active' === $supervision_status
				&& CTA_Associate_Access::has_qualifying_plan( $user->ID )
			) {
				// Legacy active+plan accounts without approval meta → treat as approved.
				$approval_status = CTA_Associate_Access::STATUS_APPROVED;
			} else {
				$approval_status = CTA_Associate_Access::STATUS_PENDING;
			}
		}

		// Keep approval meta as-is: Approved without a plan is valid (vetting passed;
		// access stays locked until purchase or admin assignment).

		if ( ! $payment && class_exists( 'CTA_Database' ) ) {
			$payment = CTA_Database::get_user_supervision_payment( $user->ID, 'completed' );
			if ( ! $payment ) {
				$payment = CTA_Database::get_user_supervision_payment( $user->ID );
			}
		}

		$plan_details = array();
		$plan_name    = '';
		$has_plan     = CTA_Associate_Access::has_qualifying_plan( $user->ID );
		$plan_status_key = CTA_Associate_Access::get_plan_status_key( $user->ID );
		$plan_status_label = CTA_Associate_Access::get_plan_status_label( $user->ID );

		if ( $payment && 'completed' === (string) ( $payment->status ?? '' ) ) {
			$decoded = json_decode( (string) ( $payment->plan_details ?? '' ), true );
			if ( is_array( $decoded ) ) {
				$plan_details = $decoded;
			}
			$plan_name = sanitize_text_field( (string) ( $payment->plan_name ?? '' ) );
		}

		if ( '' === $plan_name ) {
			$plan_name = CTA_Associate_Access::get_plan_display_name( $user->ID );
		} elseif ( class_exists( 'CTA_Supervision_Plans' ) ) {
			$plan_name = CTA_Supervision_Plans::canonicalize_name( $plan_name );
		}

		if ( '' === $plan_name && ! $has_plan ) {
			$plan_name = __( 'No Plan', 'cta-lms' );
		} elseif ( '' === $plan_name ) {
			$plan_name = $plan_status_label;
		}

		$access_granted = CTA_Associate_Access::can_access_supervision_features( $user->ID );

		return array(
			'user'               => $user,
			'payment'            => $payment,
			'plan_name'          => $plan_name,
			'plan_details'       => $plan_details,
			'has_plan'           => $has_plan,
			'plan_status_key'    => $plan_status_key,
			'plan_status_label'  => $plan_status_label,
			'access_granted'     => $access_granted,
			'status'             => $approval_status,
			'rejection_reason'   => (string) get_user_meta( $user->ID, 'cta_approval_rejection_reason', true ),
			'is_associate'       => CTA_Associate_Access::is_associate( $user->ID ),
			'registered_at'      => (string) $user->user_registered,
			'admin_plan_audit'   => CTA_Associate_Access::get_admin_assigned_plan_audit( $user->ID ),
		);
	}

	/**
	 * Render bookings management.
	 */
	public function render_bookings() {
		global $wpdb;

		$tab   = sanitize_text_field( wp_unslash( $_GET['tab'] ?? 'upcoming' ) );
		$today = cta_lms_current_date( 'Y-m-d' );

		if ( 'history' === $tab ) {
			$sessions = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT b.*, u.display_name
					FROM {$wpdb->prefix}cta_bookings b
					LEFT JOIN {$wpdb->users} u ON u.ID = b.user_id
					WHERE b.user_id > 0
					AND (b.session_date < %s OR b.status IN ('cancelled','completed'))
					ORDER BY b.session_date DESC, b.session_time DESC
					LIMIT 100",
					$today
				)
			);
		} else {
			$sessions = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}cta_bookings
					WHERE user_id = 0
					AND status = 'open'
					AND session_date >= %s
					ORDER BY session_date ASC, session_time ASC",
					$today
				)
			);
		}

		$this->load_view(
			'bookings.php',
			array(
				'sessions' => $sessions ? $sessions : array(),
				'tab'      => $tab,
			)
		);
	}

	/**
	 * Render settings form.
	 */
	public function render_settings() {
		$this->load_view(
			'settings.php',
			array(
				'pages'        => get_pages( array( 'sort_column' => 'post_title', 'sort_order' => 'ASC' ) ),
				'webhook_url'  => rest_url( 'cta-lms/v1/stripe-webhook' ),
				'page_options' => self::get_page_option_map(),
			)
		);
	}

	/**
	 * Render CE course evaluation question bank.
	 */
	public function render_evaluation() {
		if ( ! class_exists( 'CTA_Evaluation_Questions' ) ) {
			wp_die( esc_html__( 'Evaluation questions module is unavailable.', 'cta-lms' ) );
		}

		CTA_Evaluation_Questions::install();

		$edit_id       = absint( wp_unslash( $_GET['edit'] ?? 0 ) );
		$edit_question = $edit_id ? CTA_Evaluation_Questions::get_question( $edit_id ) : null;

		$this->load_view(
			'evaluation.php',
			array(
				'questions'     => CTA_Evaluation_Questions::get_questions( 'all' ),
				'edit_question' => $edit_question,
				'notice'        => sanitize_text_field( wp_unslash( $_GET['cta_notice'] ?? '' ) ),
			)
		);
	}

	/**
	 * Admin: save / update an evaluation question.
	 */
	public function save_evaluation_question() {
		$this->verify_admin_request( 'cta_save_evaluation_question' );

		$question_id = absint( wp_unslash( $_POST['question_id'] ?? 0 ) );
		$data        = array(
			'section_label' => cta_lms_sanitize_utf8_text( (string) wp_unslash( $_POST['section_label'] ?? '' ) ),
			'label'         => cta_lms_sanitize_utf8_text( (string) wp_unslash( $_POST['label'] ?? '' ) ),
			'question_type' => wp_unslash( $_POST['question_type'] ?? 'rating' ),
			'options_text'  => cta_lms_sanitize_utf8_text( (string) wp_unslash( $_POST['options_text'] ?? '' ) ),
			'is_required'   => ! empty( $_POST['is_required'] ) ? 1 : 0,
			'status'        => wp_unslash( $_POST['status'] ?? 'active' ),
			'summary_field' => wp_unslash( $_POST['summary_field'] ?? '' ),
		);

		if ( $question_id ) {
			$result = CTA_Evaluation_Questions::update_question( $question_id, $data );
		} else {
			$existing = CTA_Evaluation_Questions::get_questions( 'all' );
			$data['order_index'] = count( $existing );
			$result = CTA_Evaluation_Questions::insert_question( $data );
		}

		$notice = is_wp_error( $result ) ? 'save_failed' : 'saved';

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'cta-lms-evaluation',
					'cta_notice' => $notice,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Admin: delete an evaluation question definition.
	 */
	public function delete_evaluation_question() {
		$this->verify_admin_request( 'cta_delete_evaluation_question' );

		$question_id = absint( wp_unslash( $_GET['question_id'] ?? 0 ) );
		CTA_Evaluation_Questions::delete_question( $question_id );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'cta-lms-evaluation',
					'cta_notice' => 'deleted',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Admin: reorder evaluation questions.
	 */
	public function reorder_evaluation_questions() {
		$this->verify_admin_request( 'cta_reorder_evaluation_questions' );

		$order = array();
		if ( ! empty( $_POST['order_csv'] ) ) {
			$parts = explode( ',', (string) wp_unslash( $_POST['order_csv'] ) );
			foreach ( $parts as $part ) {
				$id = absint( trim( $part ) );
				if ( $id ) {
					$order[] = $id;
				}
			}
		} elseif ( ! empty( $_POST['order'] ) && is_array( $_POST['order'] ) ) {
			foreach ( wp_unslash( $_POST['order'] ) as $id ) {
				$id = absint( $id );
				if ( $id ) {
					$order[] = $id;
				}
			}
		}

		if ( ! empty( $order ) ) {
			CTA_Evaluation_Questions::reorder( $order );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'cta-lms-evaluation',
					'cta_notice' => 'reordered',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render configurable automated email settings.
	 */
	public function render_email_settings() {
		$this->load_view(
			'email-settings.php',
			array(
				'email_types' => CTA_Emails::get_configurable_types(),
			)
		);
	}

	/**
	 * Render shortcodes reference.
	 */
	public function render_shortcodes() {
		$this->load_view( 'shortcodes.php', array( 'shortcodes' => self::get_shortcode_reference() ) );
	}

	/**
	 * Save course from admin form.
	 */
	public function save_course() {
		$this->verify_admin_request( 'cta_save_course' );

		global $wpdb;

		if ( ! CTA_Database::ensure_tables() ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'       => 'cta-lms-course-edit',
						'course_id'  => absint( wp_unslash( $_POST['course_id'] ?? 0 ) ),
						'cta_notice' => 'course_save_failed',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		$course_id  = absint( wp_unslash( $_POST['course_id'] ?? 0 ) );
		$title       = cta_lms_sanitize_utf8_text( sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ) );
		$slug        = sanitize_title( wp_unslash( $_POST['slug'] ?? $title ) );
		$category    = cta_lms_sanitize_utf8_text( sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) ) );
		$ce_hours    = (float) wp_unslash( $_POST['ce_hours'] ?? 0 );
		$price       = (float) wp_unslash( $_POST['price'] ?? 0 );
		$description = cta_lms_sanitize_utf8_html( wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ) );
		$thumbnail  = esc_url_raw( wp_unslash( $_POST['thumbnail_url'] ?? '' ) );
		$video_type = sanitize_text_field( wp_unslash( $_POST['course_video_type'] ?? 'vimeo' ) );
		$video_raw  = sanitize_text_field( wp_unslash( $_POST['course_video_value'] ?? '' ) );
		$video_url  = esc_url_raw( wp_unslash( $_POST['course_video_url'] ?? '' ) );
		$vimeo_id   = '';
		$allowed_video_types = array( 'vimeo', 'youtube', 'wordpress', 'url' );

		$product_type = sanitize_text_field( wp_unslash( $_POST['product_type'] ?? 'ce' ) );
		if ( ! in_array( $product_type, array( 'ce', 'exam_prep' ), true ) ) {
			$product_type = 'ce';
		}

		$access_period_months = absint( wp_unslash( $_POST['access_period_months'] ?? 6 ) );
		if ( $access_period_months < 1 ) {
			$access_period_months = 6;
		}

		// Exam prep never awards CE hours or certificates.
		if ( 'exam_prep' === $product_type ) {
			$ce_hours            = 0;
			$awards_ce_hours     = 0;
			$has_ce_certificate  = 0;
			if ( '' === $category ) {
				$category = 'Exam Preparation';
			}
		} else {
			$awards_ce_hours    = 1;
			$has_ce_certificate = 1;
			$access_period_months = 6;
		}

		if ( ! in_array( $video_type, $allowed_video_types, true ) ) {
			$video_type = 'vimeo';
		}

		if ( 'vimeo' === $video_type ) {
			$vimeo_id = preg_replace( '/\D/', '', $video_raw );
			$video_url = $vimeo_id ? 'https://vimeo.com/' . $vimeo_id : '';
		} elseif ( 'youtube' === $video_type ) {
			$video_url = esc_url_raw( $video_raw );
			$vimeo_id  = '';
		} elseif ( 'wordpress' === $video_type || 'url' === $video_type ) {
			$video_url = $video_url ? $video_url : esc_url_raw( $video_raw );
			$vimeo_id  = '';
		}
		$status     = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'draft' ) );
		$status     = in_array( $status, array( 'published', 'draft' ), true ) ? $status : 'draft';

		$objectives_in = isset( $_POST['learning_objectives'] ) ? wp_unslash( $_POST['learning_objectives'] ) : array();
		$objectives    = array();

		if ( is_array( $objectives_in ) ) {
			foreach ( $objectives_in as $objective ) {
				$objective = cta_lms_sanitize_utf8_text( sanitize_text_field( $objective ) );
				if ( '' !== $objective ) {
					$objectives[] = $objective;
				}
			}
		}

		if ( '' === $title ) {
			wp_die( esc_html__( 'Course title is required.', 'cta-lms' ) );
		}

		if ( '' === $slug ) {
			$slug = sanitize_title( $title );
		}

		$data = array(
			'title'                => $title,
			'slug'                 => $slug,
			'category'             => $category,
			'ce_hours'             => $ce_hours,
			'price'                => $price,
			'description'          => $description,
			'learning_objectives'  => wp_json_encode( $objectives ),
			'thumbnail_url'        => $thumbnail,
			'vimeo_id'             => $vimeo_id,
			'video_url'            => $video_url,
			'status'               => $status,
			'product_type'         => $product_type,
			'access_period_months' => $access_period_months,
			'awards_ce_hours'      => $awards_ce_hours,
			'has_ce_certificate'   => $has_ce_certificate,
		);

		$table = $wpdb->prefix . 'cta_courses';
		$saved = false;
		$formats = array( '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d' );

		if ( $course_id ) {
			$saved = false !== $wpdb->update(
				$table,
				$data,
				array( 'id' => $course_id ),
				$formats,
				array( '%d' )
			);
		} else {
			$saved = false !== $wpdb->insert(
				$table,
				$data,
				$formats
			);
			$course_id = (int) $wpdb->insert_id;
		}

		if ( ! $saved || ! $course_id ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'       => 'cta-lms-course-edit',
						'course_id'  => absint( wp_unslash( $_POST['course_id'] ?? 0 ) ),
						'cta_notice' => 'course_save_failed',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		$module_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}cta_course_modules WHERE course_id = %d",
				$course_id
			)
		);

		$wpdb->update(
			$table,
			array( 'modules_count' => $module_count ),
			array( 'id' => $course_id ),
			array( '%d' ),
			array( '%d' )
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'cta-lms-course-edit',
					'course_id'  => $course_id,
					'cta_notice' => 'course_saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Admin: manually extend exam prep access for a learner.
	 */
	public function extend_exam_access() {
		$this->verify_admin_request( 'cta_extend_exam_access' );

		$course_id    = absint( wp_unslash( $_POST['course_id'] ?? 0 ) );
		$user_id      = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
		$extra_months = absint( wp_unslash( $_POST['extra_months'] ?? 1 ) );
		$notes        = sanitize_textarea_field( wp_unslash( $_POST['extension_notes'] ?? '' ) );

		$result = CTA_Exam_Access::extend_access(
			$user_id,
			$course_id,
			$extra_months,
			get_current_user_id(),
			$notes
		);

		$notice = is_wp_error( $result ) ? 'exam_extend_failed' : 'exam_extended';

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'cta-lms-course-edit',
					'course_id'  => $course_id,
					'cta_notice' => $notice,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Admin: save a downloadable resource (workbook / practice test / handout).
	 */
	public function save_resource() {
		$this->verify_admin_request( 'cta_save_resource' );

		global $wpdb;

		$course_id        = absint( wp_unslash( $_POST['course_id'] ?? 0 ) );
		$resource_id      = absint( wp_unslash( $_POST['resource_id'] ?? 0 ) );
		$module_id        = absint( wp_unslash( $_POST['resource_module_id'] ?? 0 ) );
		$attachment_id    = absint( wp_unslash( $_POST['resource_attachment_id'] ?? 0 ) );
		$title            = cta_lms_sanitize_utf8_text( sanitize_text_field( wp_unslash( $_POST['resource_title'] ?? '' ) ) );
		$file_url         = esc_url_raw( wp_unslash( $_POST['resource_file_url'] ?? '' ) );
		$file_type        = sanitize_text_field( wp_unslash( $_POST['resource_file_type'] ?? '' ) );
		$order_index      = absint( wp_unslash( $_POST['resource_order_index'] ?? 0 ) );
		$is_practice_test = ! empty( $_POST['is_practice_test'] ) ? 1 : 0;
		$file_path        = '';

		if ( ! $course_id || '' === $title ) {
			wp_die( esc_html__( 'Resource title is required.', 'cta-lms' ) );
		}

		$redirect_fail = static function ( $notice_key ) use ( $course_id ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'       => 'cta-lms-course-edit',
						'course_id'  => $course_id,
						'cta_notice' => $notice_key,
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		};

		// Prefer Media Library selection → copy into protected storage.
		if ( $attachment_id && class_exists( 'CTA_Course_Materials' ) ) {
			$imported = CTA_Course_Materials::import_attachment_to_protected( $attachment_id, $course_id );
			if ( is_wp_error( $imported ) ) {
				$code = $imported->get_error_code();
				if ( 'cta_resource_too_large' === $code ) {
					$redirect_fail( 'resource_too_large' );
				}
				if ( 'cta_resource_invalid_type' === $code ) {
					$redirect_fail( 'resource_invalid_type' );
				}
				$redirect_fail( 'resource_save_failed' );
			}
			$file_path = $imported['relative_path'];
			$file_url  = $imported['file_url'];
			if ( '' === $file_type ) {
				$file_type = $imported['file_type'];
			}
		}

		$existing = $resource_id ? CTA_Database::get_downloadable_resource( $resource_id ) : null;

		// Keep previous protected file when editing without a new upload.
		if ( $existing && ! $attachment_id && '' === $file_url ) {
			$file_url  = (string) $existing->file_url;
			$file_path = (string) ( $existing->file_path ?? '' );
			$attachment_id = (int) ( $existing->attachment_id ?? 0 );
			if ( '' === $file_type ) {
				$file_type = (string) $existing->file_type;
			}
		}

		if ( '' === $file_url && ! $file_path ) {
			wp_die( esc_html__( 'Please select or upload a file for this material.', 'cta-lms' ) );
		}

		if ( '' === $file_type ) {
			$path      = wp_parse_url( $file_url, PHP_URL_PATH );
			$ext       = $path ? strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) : '';
			$file_type = $ext ? $ext : 'file';
		}

		// Validate module belongs to this course when set.
		if ( $module_id ) {
			$module_ok = false;
			foreach ( CTA_Database::get_course_modules( $course_id ) as $module ) {
				if ( (int) $module->id === $module_id ) {
					$module_ok = true;
					break;
				}
			}
			if ( ! $module_ok ) {
				$module_id = 0;
			}
		}

		if ( ! $resource_id ) {
			$max_order = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(MAX(order_index), -1) FROM {$wpdb->prefix}cta_downloadable_resources WHERE course_id = %d",
					$course_id
				)
			);
			$order_index = $max_order + 1;
		}

		$table = $wpdb->prefix . 'cta_downloadable_resources';
		$data  = array(
			'course_id'        => $course_id,
			'module_id'        => $module_id,
			'attachment_id'    => $attachment_id,
			'title'            => $title,
			'file_url'         => $file_url ? $file_url : 'cta-protected://' . $file_path,
			'file_path'        => $file_path,
			'file_type'        => $file_type,
			'order_index'      => $order_index,
			'is_practice_test' => $is_practice_test,
		);
		$formats = array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d' );

		if ( $resource_id ) {
			// Preserve file_path if not replacing.
			if ( $existing && empty( $file_path ) && ! empty( $existing->file_path ) ) {
				$data['file_path'] = $existing->file_path;
			}
			$wpdb->update(
				$table,
				$data,
				array( 'id' => $resource_id ),
				$formats,
				array( '%d' )
			);
		} else {
			$wpdb->insert( $table, $data, $formats );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'cta-lms-course-edit',
					'course_id'  => $course_id,
					'cta_notice' => 'resource_saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * AJAX: reorder downloadable resources.
	 */
	public function ajax_reorder_resources() {
		check_ajax_referer( 'cta_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'cta-lms' ) ) );
		}

		global $wpdb;

		$course_id = absint( wp_unslash( $_POST['course_id'] ?? 0 ) );
		$order     = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : array();

		if ( ! $course_id || ! is_array( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid reorder request.', 'cta-lms' ) ) );
		}

		$table = $wpdb->prefix . 'cta_downloadable_resources';

		foreach ( array_values( $order ) as $index => $resource_id ) {
			$wpdb->update(
				$table,
				array( 'order_index' => (int) $index ),
				array(
					'id'        => absint( $resource_id ),
					'course_id' => $course_id,
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
		}

		wp_send_json_success( array( 'message' => __( 'Resources reordered.', 'cta-lms' ) ) );
	}

	/**
	 * Admin: delete a downloadable resource.
	 */
	public function delete_resource() {
		$this->verify_admin_request( 'cta_delete_resource' );

		global $wpdb;

		$resource_id = absint( wp_unslash( $_GET['resource_id'] ?? 0 ) );
		$course_id   = absint( wp_unslash( $_GET['course_id'] ?? 0 ) );

		if ( $resource_id ) {
			$wpdb->delete(
				$wpdb->prefix . 'cta_downloadable_resources',
				array( 'id' => $resource_id ),
				array( '%d' )
			);
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'cta-lms-course-edit',
					'course_id'  => $course_id,
					'cta_notice' => 'resource_deleted',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Delete a course.
	 */
	public function delete_course() {
		$this->verify_admin_request( 'cta_delete_course' );

		$course_id = absint( wp_unslash( $_GET['course_id'] ?? 0 ) );

		if ( ! $course_id ) {
			wp_die( esc_html__( 'Invalid course.', 'cta-lms' ) );
		}

		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'cta_course_modules', array( 'course_id' => $course_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'cta_downloadable_resources', array( 'course_id' => $course_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'cta_courses', array( 'id' => $course_id ), array( '%d' ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'cta-lms-courses',
					'cta_notice' => 'course_deleted',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Toggle course published/draft status.
	 */
	public function toggle_course_status() {
		$this->verify_admin_request( 'cta_toggle_course' );

		$course_id = absint( wp_unslash( $_GET['course_id'] ?? 0 ) );
		$course    = CTA_Database::get_course( $course_id );

		if ( ! $course ) {
			wp_die( esc_html__( 'Course not found.', 'cta-lms' ) );
		}

		global $wpdb;

		$new_status = 'published' === $course->status ? 'draft' : 'published';

		$wpdb->update(
			$wpdb->prefix . 'cta_courses',
			array( 'status' => $new_status ),
			array( 'id' => $course_id ),
			array( '%s' ),
			array( '%d' )
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'cta-lms-courses',
					'cta_notice' => 'status_updated',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Save plugin settings.
	 */
	public function save_settings() {
		$this->verify_admin_request( 'cta_save_settings' );

		update_option( 'cta_stripe_mode', sanitize_text_field( wp_unslash( $_POST['cta_stripe_mode'] ?? 'test' ) ) );
		update_option( 'cta_stripe_secret_key', sanitize_text_field( wp_unslash( $_POST['cta_stripe_secret_key'] ?? '' ) ) );
		update_option( 'cta_stripe_publishable_key', sanitize_text_field( wp_unslash( $_POST['cta_stripe_publishable_key'] ?? '' ) ) );
		update_option( 'cta_stripe_webhook_secret', sanitize_text_field( wp_unslash( $_POST['cta_stripe_webhook_secret'] ?? '' ) ) );
		update_option( 'cta_payments_bypass', isset( $_POST['cta_payments_bypass'] ) ? 'yes' : 'no' );

		foreach ( self::get_page_option_map() as $option_key => $label ) {
			update_option( $option_key, absint( wp_unslash( $_POST[ $option_key ] ?? 0 ) ) );
		}

		update_option( 'cta_camft_provider_number', cta_lms_sanitize_utf8_text( sanitize_text_field( wp_unslash( $_POST['cta_camft_provider_number'] ?? '' ) ) ) );
		update_option( 'cta_admin_name', cta_lms_sanitize_utf8_text( sanitize_text_field( wp_unslash( $_POST['cta_admin_name'] ?? '' ) ) ) );
		update_option( 'cta_support_email', sanitize_email( wp_unslash( $_POST['cta_support_email'] ?? '' ) ) );

		$timezone = sanitize_text_field( wp_unslash( $_POST['cta_timezone'] ?? 'America/Los_Angeles' ) );
		try {
			new DateTimeZone( $timezone );
		} catch ( Exception $e ) {
			$timezone = 'America/Los_Angeles';
		}
		update_option( 'cta_timezone', $timezone );

		update_option( 'cta_certificate_header_text', cta_lms_sanitize_utf8_text( sanitize_text_field( wp_unslash( $_POST['cta_certificate_header_text'] ?? '' ) ) ) );
		update_option( 'cta_certificate_footer_text', cta_lms_sanitize_utf8_text( sanitize_text_field( wp_unslash( $_POST['cta_certificate_footer_text'] ?? '' ) ) ) );
		update_option( 'cta_certificate_signature_name', cta_lms_sanitize_utf8_text( sanitize_text_field( wp_unslash( $_POST['cta_certificate_signature_name'] ?? '' ) ) ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'cta-lms-settings',
					'cta_notice' => 'settings_saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Save automated email settings.
	 */
	public function save_email_settings() {
		$this->verify_admin_request( 'cta_save_email_settings' );

		update_option( 'cta_admin_name', cta_lms_sanitize_utf8_text( sanitize_text_field( wp_unslash( $_POST['cta_admin_name'] ?? '' ) ) ) );
		update_option( 'cta_support_email', sanitize_email( wp_unslash( $_POST['cta_support_email'] ?? '' ) ) );

		$submitted = isset( $_POST['emails'] ) && is_array( $_POST['emails'] )
			? wp_unslash( $_POST['emails'] )
			: array();

		foreach ( CTA_Emails::get_configurable_types() as $type => $config ) {
			$email = isset( $submitted[ $type ] ) && is_array( $submitted[ $type ] )
				? $submitted[ $type ]
				: array();
			$subject = cta_lms_sanitize_utf8_text( sanitize_text_field( $email['subject'] ?? $config['default_subject'] ) );
			$body    = cta_lms_sanitize_utf8_html( wp_kses_post( $email['body'] ?? $config['default_body'] ) );

			// Keep empty options for untouched defaults so the original PHP
			// template remains the fallback (including its conditional content).
			$saved_subject = $config['default_subject'] === $subject ? '' : $subject;
			$normalized_body = str_replace(
				array( '%7B', '%7D', '%7b', '%7d' ),
				array( '{', '}', '{', '}' ),
				$body
			);
			$saved_body = trim( wp_kses_post( $config['default_body'] ) ) === trim( $normalized_body )
				? ''
				: $body;

			update_option(
				CTA_Emails::get_email_option_key( $type, 'enabled' ),
				isset( $email['enabled'] ) ? 'yes' : 'no'
			);
			update_option(
				CTA_Emails::get_email_option_key( $type, 'subject' ),
				$saved_subject
			);
			update_option(
				CTA_Emails::get_email_option_key( $type, 'body' ),
				$saved_body
			);
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'cta-lms-email-settings',
					'cta_notice' => 'email_settings_saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * AJAX: user stats for admin users table.
	 */
	public function ajax_get_stats() {
		$this->verify_admin_ajax();

		$user_id = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user.', 'cta-lms' ) ) );
		}

		global $wpdb;

		$courses_enrolled = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}cta_enrollments WHERE user_id = %d",
				$user_id
			)
		);

		$courses_completed = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}cta_enrollments WHERE user_id = %d AND status = 'completed'",
				$user_id
			)
		);

		$certificates_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}cta_certificates WHERE user_id = %d",
				$user_id
			)
		);

		$total_paid = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}cta_payments WHERE user_id = %d AND status = 'completed'",
				$user_id
			)
		);

		wp_send_json_success(
			array(
				'courses_enrolled'   => $courses_enrolled,
				'courses_completed'  => $courses_completed,
				'certificates_count' => $certificates_count,
				'supervision_status' => (string) get_user_meta( $user_id, 'cta_supervision_status', true ),
				'total_paid'         => number_format( $total_paid, 2 ),
			)
		);
	}

	/**
	 * AJAX: save course module.
	 */
	public function ajax_save_module() {
		$this->verify_admin_ajax();

		global $wpdb;

		$module_id   = absint( wp_unslash( $_POST['module_id'] ?? 0 ) );
		$course_id   = absint( wp_unslash( $_POST['course_id'] ?? 0 ) );
		$title       = cta_lms_sanitize_utf8_text( sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ) );
		$description = cta_lms_sanitize_utf8_text( sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ) );
		$video_url   = esc_url_raw( wp_unslash( $_POST['video_url'] ?? '' ) );
		$duration    = absint( wp_unslash( $_POST['duration_mins'] ?? 0 ) );
		$is_locked   = ! empty( $_POST['is_locked'] ) ? 1 : 0;

		if ( ! $course_id || '' === $title ) {
			wp_send_json_error( array( 'message' => __( 'Course and module title are required.', 'cta-lms' ) ) );
		}

		$table = $wpdb->prefix . 'cta_course_modules';
		$data  = array(
			'course_id'     => $course_id,
			'title'         => $title,
			'description'   => $description,
			'video_url'     => $video_url,
			'duration_mins' => $duration,
			'is_locked'     => $is_locked,
		);

		if ( $module_id ) {
			$wpdb->update(
				$table,
				$data,
				array( 'id' => $module_id ),
				array( '%d', '%s', '%s', '%s', '%d', '%d' ),
				array( '%d' )
			);
			$module = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $module_id ) );
		} else {
			$max_order = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(order_index) FROM {$table} WHERE course_id = %d",
					$course_id
				)
			);
			$data['order_index'] = $max_order + 1;

			$wpdb->insert(
				$table,
				$data,
				array( '%d', '%s', '%s', '%s', '%d', '%d', '%d' )
			);
			$module = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $wpdb->insert_id ) );
		}

		$module_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE course_id = %d",
				$course_id
			)
		);

		$wpdb->update(
			$wpdb->prefix . 'cta_courses',
			array( 'modules_count' => $module_count ),
			array( 'id' => $course_id ),
			array( '%d' ),
			array( '%d' )
		);

		wp_send_json_success(
			array(
				'module_id' => (int) $module->id,
				'html'      => $this->render_module_row_html( $module ),
			)
		);
	}

	/**
	 * AJAX: delete course module.
	 */
	public function ajax_delete_module() {
		$this->verify_admin_ajax();

		$module_id = absint( wp_unslash( $_POST['module_id'] ?? 0 ) );
		$course_id = absint( wp_unslash( $_POST['course_id'] ?? 0 ) );

		if ( ! $module_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid module.', 'cta-lms' ) ) );
		}

		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'cta_course_modules', array( 'id' => $module_id ), array( '%d' ) );

		if ( $course_id ) {
			$module_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}cta_course_modules WHERE course_id = %d",
					$course_id
				)
			);

			$wpdb->update(
				$wpdb->prefix . 'cta_courses',
				array( 'modules_count' => $module_count ),
				array( 'id' => $course_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: reorder modules.
	 */
	public function ajax_reorder_modules() {
		$this->verify_admin_ajax();

		$course_id = absint( wp_unslash( $_POST['course_id'] ?? 0 ) );
		$order     = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : array();

		if ( ! $course_id || ! is_array( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order data.', 'cta-lms' ) ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'cta_course_modules';

		foreach ( $order as $index => $module_id ) {
			$wpdb->update(
				$table,
				array( 'order_index' => (int) $index ),
				array(
					'id'        => absint( $module_id ),
					'course_id' => $course_id,
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: review uploaded document.
	 */
	public function ajax_review_document() {
		$this->verify_admin_ajax();

		$document_id = absint( wp_unslash( $_POST['document_id'] ?? 0 ) );
		$status      = sanitize_text_field( wp_unslash( $_POST['review_status'] ?? '' ) );

		if ( ! in_array( $status, array( 'approved', 'rejected', 'pending' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid review status.', 'cta-lms' ) ) );
		}

		global $wpdb;

		$updated = $wpdb->update(
			$wpdb->prefix . 'cta_documents',
			array(
				'review_status' => $status,
				'reviewed_at'   => current_time( 'mysql' ),
				'reviewed_by'   => get_current_user_id(),
			),
			array( 'id' => $document_id ),
			array( '%s', '%s', '%d' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			wp_send_json_error( array( 'message' => __( 'Unable to update document.', 'cta-lms' ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: add supervision session slot.
	 */
	public function ajax_add_session() {
		$this->verify_admin_ajax();

		$session_date = sanitize_text_field( wp_unslash( $_POST['session_date'] ?? '' ) );
		$session_time = sanitize_text_field( wp_unslash( $_POST['session_time'] ?? '' ) );
		$session_type = sanitize_text_field( wp_unslash( $_POST['session_type'] ?? 'group' ) );
		$seats_total  = absint( wp_unslash( $_POST['seats_total'] ?? 8 ) );
		$duration     = absint( wp_unslash( $_POST['duration_mins'] ?? 120 ) );

		if ( ! $session_date || ! $session_time ) {
			wp_send_json_error( array( 'message' => __( 'Date and time are required.', 'cta-lms' ) ) );
		}

		$dt = cta_lms_session_datetime( $session_date, $session_time );

		if ( ! $dt || $dt->getTimestamp() <= time() ) {
			wp_send_json_error( array( 'message' => __( 'Session must be in the future.', 'cta-lms' ) ) );
		}

		if ( 'group' === $session_type ) {
			$seats_total = min( 8, max( 1, $seats_total ) );
			$duration    = 120;
		} else {
			$session_type = 'individual';
			$seats_total  = 1;
			$duration     = 60;
		}

		global $wpdb;

		$inserted = $wpdb->insert(
			$wpdb->prefix . 'cta_bookings',
			array(
				'user_id'       => 0,
				'session_type'  => $session_type,
				'session_date'  => $session_date,
				'session_time'  => $session_time,
				'duration_mins' => $duration,
				'seats_total'   => $seats_total,
				'seats_booked'  => 0,
				'status'        => 'open',
			),
			array( '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s' )
		);

		if ( ! $inserted ) {
			wp_send_json_error( array( 'message' => __( 'Unable to create session.', 'cta-lms' ) ) );
		}

		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}cta_bookings WHERE id = %d",
				(int) $wpdb->insert_id
			)
		);

		wp_send_json_success(
			array(
				'html' => $this->render_session_row_html( $session ),
			)
		);
	}

	/**
	 * AJAX: cancel open session and notify booked users.
	 */
	public function ajax_cancel_session() {
		$this->verify_admin_ajax();

		$session_id = absint( wp_unslash( $_POST['session_id'] ?? 0 ) );

		global $wpdb;
		$table = $wpdb->prefix . 'cta_bookings';

		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND user_id = 0",
				$session_id
			)
		);

		if ( ! $session ) {
			wp_send_json_error( array( 'message' => __( 'Session not found.', 'cta-lms' ) ) );
		}

		$bookings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE session_date = %s
				AND session_time = %s
				AND session_type = %s
				AND user_id > 0
				AND status = 'confirmed'",
				$session->session_date,
				$session->session_time,
				$session->session_type
			)
		);

		foreach ( $bookings as $booking ) {
			$user = get_userdata( (int) $booking->user_id );
			if ( $user && is_email( $user->user_email ) ) {
				wp_mail(
					$user->user_email,
					__( 'Supervision Session Cancelled', 'cta-lms' ),
					sprintf(
						/* translators: 1: date, 2: time */
						__( "Hi %1\$s,\n\nYour supervision session on %2\$s at %3\$s has been cancelled.\n\nPlease book another session from your dashboard.\n\nCTA Team", 'cta-lms' ),
						$user->display_name,
						$session->session_date,
						$session->session_time
					)
				);
			}

			$wpdb->update(
				$table,
				array( 'status' => 'cancelled' ),
				array( 'id' => (int) $booking->id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		$wpdb->update(
			$table,
			array( 'status' => 'cancelled' ),
			array( 'id' => $session_id ),
			array( '%s' ),
			array( '%d' )
		);

		wp_send_json_success();
	}

	/**
	 * AJAX: test Stripe API connection.
	 */
	public function ajax_test_stripe_connection() {
		$this->verify_admin_ajax();

		if ( ! class_exists( '\Stripe\Stripe' ) ) {
			wp_send_json_error( array( 'message' => __( 'Stripe SDK not installed. Run composer install.', 'cta-lms' ) ) );
		}

		$secret = sanitize_text_field( wp_unslash( $_POST['secret_key'] ?? get_option( 'cta_stripe_secret_key', '' ) ) );

		if ( '' === $secret ) {
			wp_send_json_error( array( 'message' => __( 'Secret key is required.', 'cta-lms' ) ) );
		}

		try {
			\Stripe\Stripe::setApiKey( $secret );
			$account = \Stripe\Account::retrieve();

			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: %s: Stripe account ID */
						__( 'Connected to Stripe account %s', 'cta-lms' ),
						isset( $account->id ) ? $account->id : ''
					),
					'account' => array(
						'id'      => $account->id ?? '',
						'country' => $account->country ?? '',
						'email'   => $account->email ?? '',
					),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: ensure Stripe Customer Portal configuration exists.
	 */
	public function ajax_ensure_billing_portal() {
		$this->verify_admin_ajax();

		if ( CTA_Stripe::is_payments_bypass_enabled() ) {
			wp_send_json_error(
				array(
					'message' => __( 'Turn off Testing Mode (payment bypass) before configuring the billing portal.', 'cta-lms' ),
				)
			);
		}

		$stripe = cta_get_stripe();

		if ( ! $stripe || ! $stripe->is_configured() ) {
			wp_send_json_error( array( 'message' => __( 'Configure and save Stripe API keys first.', 'cta-lms' ) ) );
		}

		$result = $stripe->ensure_billing_portal_configuration();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( '' === $result ) {
			wp_send_json_success(
				array(
					'message' => __( 'Stripe will use your Dashboard default Customer Portal settings.', 'cta-lms' ),
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %s: Stripe portal configuration ID */
					__( 'Customer Portal ready (%s).', 'cta-lms' ),
					$result
				),
				'configuration_id' => $result,
			)
		);
	}

	/**
	 * AJAX: admin cancel a student's Stripe subscription.
	 */
	public function ajax_admin_cancel_subscription() {
		$this->verify_admin_ajax();

		$user_id = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
		$mode    = sanitize_text_field( wp_unslash( $_POST['mode'] ?? 'at_period_end' ) );
		$stripe  = cta_get_stripe();

		if ( ! $stripe ) {
			wp_send_json_error( array( 'message' => __( 'Stripe is not available.', 'cta-lms' ) ) );
		}

		$result = $stripe->admin_cancel_subscription( $user_id, $mode );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$cancel_pending = '1' === (string) get_user_meta( $user_id, 'cta_supervision_cancel_at_period_end', true );

		wp_send_json_success(
			array(
				'message' => ( 'immediately' === $mode )
					? __( 'Subscription cancelled immediately in Stripe and locally.', 'cta-lms' )
					: __( 'Subscription set to cancel at period end. Access remains until the paid period ends.', 'cta-lms' ),
				'user_id'             => $user_id,
				'supervision_status'  => (string) get_user_meta( $user_id, 'cta_supervision_status', true ),
				'cancel_at_period_end'=> $cancel_pending,
			)
		);
	}

	/**
	 * AJAX: admin reactivate a subscription that was set to cancel at period end.
	 */
	public function ajax_admin_reactivate_subscription() {
		$this->verify_admin_ajax();

		$user_id = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
		$stripe  = cta_get_stripe();

		if ( ! $stripe ) {
			wp_send_json_error( array( 'message' => __( 'Stripe is not available.', 'cta-lms' ) ) );
		}

		$result = $stripe->admin_reactivate_subscription( $user_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message'            => __( 'Subscription reactivated. Auto-renewal is on again.', 'cta-lms' ),
				'user_id'            => $user_id,
				'supervision_status' => (string) get_user_meta( $user_id, 'cta_supervision_status', true ),
				'cancel_at_period_end' => false,
			)
		);
	}

	/**
	 * AJAX: pull latest Stripe subscription status into local meta.
	 */
	public function ajax_admin_sync_subscription() {
		$this->verify_admin_ajax();

		$user_id = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
		$stripe  = cta_get_stripe();

		if ( ! $stripe ) {
			wp_send_json_error( array( 'message' => __( 'Stripe is not available.', 'cta-lms' ) ) );
		}

		$result = $stripe->sync_user_subscription_from_stripe( $user_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message'              => __( 'Subscription synced from Stripe.', 'cta-lms' ),
				'user_id'              => $user_id,
				'supervision_status'   => (string) get_user_meta( $user_id, 'cta_supervision_status', true ),
				'cancel_at_period_end' => '1' === (string) get_user_meta( $user_id, 'cta_supervision_cancel_at_period_end', true ),
			)
		);
	}

	/**
	 * AJAX: preview certificate with sample data.
	 */
	public function ajax_preview_certificate() {
		$this->verify_admin_ajax();

		$student_name       = 'Sample Student, LMFT';
		$course_title       = 'Sample CE Course';
		$ce_hours           = '2';
		$completion_date    = cta_lms_format_local_date( null, 'F j, Y', cta_lms_get_timezone() );
		$license_number     = 'LMFT12345';
		$provider_number    = (string) get_option( 'cta_camft_provider_number', get_option( 'cta_cepa_provider_number', '' ) );
		$certificate_number = 'CTA-' . cta_lms_current_date( 'Y' ) . '-000000';
		$header_text        = (string) get_option( 'cta_certificate_header_text', __( 'Certificate of Completion', 'cta-lms' ) );
		$footer_text        = (string) get_option( 'cta_certificate_footer_text', 'clinicaltrainingacademy.com' );
		$signature_name     = (string) get_option( 'cta_certificate_signature_name', '' );
		if ( '' === $signature_name ) {
			$signature_name = (string) get_option( 'cta_admin_name', 'Candice Fuimaono, MS, LMFT' );
		}
		$organization_name   = __( 'Clinical Training and Supervision Academy', 'cta-lms' );
		$administrator_title = __( 'Program Administrator', 'cta-lms' );
		$logo_url            = class_exists( 'CTA_Certificates' ) ? CTA_Certificates::get_logo_data_uri() : '';
		if ( '' === $logo_url ) {
			$logo_url = cta_lms_get_logo_url();
		}
		$auto_print = false;

		ob_start();
		include CTA_PLUGIN_DIR . 'templates/certificate.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: preview a configurable email with safe sample data.
	 */
	public function ajax_preview_email() {
		$this->verify_admin_ajax();

		$type    = sanitize_key( wp_unslash( $_POST['email_type'] ?? '' ) );
		$subject = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
		$body    = wp_kses_post( wp_unslash( $_POST['body'] ?? '' ) );
		$preview = CTA_Emails::preview_email( $type, $subject, $body );

		if ( is_wp_error( $preview ) ) {
			wp_send_json_error( array( 'message' => $preview->get_error_message() ) );
		}

		wp_send_json_success( $preview );
	}

	/**
	 * AJAX: load quiz questions for the visual builder.
	 */
	public function ajax_load_quiz() {
		$this->verify_admin_ajax();

		$course_id = absint( wp_unslash( $_POST['course_id'] ?? 0 ) );

		if ( ! $course_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid course.', 'cta-lms' ) ) );
		}

		$quiz = $this->get_course_quiz( $course_id );

		if ( ! $quiz ) {
			wp_send_json_success(
				array(
					'quiz'      => null,
					'questions' => array(),
				)
			);
		}

		$questions = CTA_Database::get_quiz_questions( (int) $quiz->id );
		$payload   = array();

		foreach ( $questions as $question ) {
			$payload[] = array(
				'question_text'  => $question->question_text,
				'option_a'       => $question->option_a,
				'option_b'       => $question->option_b,
				'option_c'       => $question->option_c,
				'option_d'       => $question->option_d,
				'correct_option' => $question->correct_option,
				'explanation'    => $question->explanation,
				'order_index'    => (int) $question->order_index,
			);
		}

		wp_send_json_success(
			array(
				'quiz'      => array(
					'id'    => (int) $quiz->id,
					'title' => $quiz->title,
				),
				'questions' => $payload,
			)
		);
	}

	/**
	 * AJAX: create/update quiz and import questions JSON.
	 */
	public function ajax_save_quiz() {
		$this->verify_admin_ajax();

		$course_id   = absint( wp_unslash( $_POST['course_id'] ?? 0 ) );
		$quiz_title  = sanitize_text_field( wp_unslash( $_POST['quiz_title'] ?? '' ) );
		$questions_json = wp_unslash( $_POST['questions_json'] ?? '' );

		if ( ! $course_id ) {
			wp_send_json_error( array( 'message' => __( 'Course is required.', 'cta-lms' ) ) );
		}

		global $wpdb;

		$quiz = $this->get_course_quiz( $course_id );
		$course = CTA_Database::get_course( $course_id );
		$title = $quiz_title ? $quiz_title : ( $course ? $course->title . ' Quiz' : 'Course Quiz' );

		if ( $quiz ) {
			$quiz_id = (int) $quiz->id;
			$wpdb->update(
				$wpdb->prefix . 'cta_quizzes',
				array(
					'title'           => $title,
					'status'          => 'active',
					'passing_score'   => 70,
					'time_limit_mins' => 0,
					'max_attempts'    => 0,
				),
				array( 'id' => $quiz_id ),
				array( '%s', '%s', '%d', '%d', '%d' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$wpdb->prefix . 'cta_quizzes',
				array(
					'course_id'       => $course_id,
					'title'           => $title,
					'status'          => 'active',
					'passing_score'   => 70,
					'time_limit_mins' => 0,
					'max_attempts'    => 0,
				),
				array( '%d', '%s', '%s', '%d', '%d', '%d' )
			);
			$quiz_id = (int) $wpdb->insert_id;
		}

		if ( $questions_json ) {
			$questions = json_decode( $questions_json, true );

			if ( ! is_array( $questions ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Invalid quiz questions format.', 'cta-lms' ),
					)
				);
			}

			if ( is_array( $questions ) ) {
				$wpdb->delete( $wpdb->prefix . 'cta_quiz_questions', array( 'quiz_id' => $quiz_id ), array( '%d' ) );

				foreach ( $questions as $index => $question ) {
					if ( empty( $question['question_text'] ) ) {
						continue;
					}

					$correct = sanitize_text_field( $question['correct_option'] ?? 'a' );
					$correct = in_array( $correct, array( 'a', 'b', 'c', 'd' ), true ) ? $correct : 'a';

					$wpdb->insert(
						$wpdb->prefix . 'cta_quiz_questions',
						array(
							'quiz_id'        => $quiz_id,
							'question_text'  => cta_lms_sanitize_utf8_text( sanitize_textarea_field( $question['question_text'] ) ),
							'option_a'       => cta_lms_sanitize_utf8_text( sanitize_text_field( $question['option_a'] ?? '' ) ),
							'option_b'       => cta_lms_sanitize_utf8_text( sanitize_text_field( $question['option_b'] ?? '' ) ),
							'option_c'       => cta_lms_sanitize_utf8_text( sanitize_text_field( $question['option_c'] ?? '' ) ),
							'option_d'       => cta_lms_sanitize_utf8_text( sanitize_text_field( $question['option_d'] ?? '' ) ),
							'correct_option' => $correct,
							'explanation'    => cta_lms_sanitize_utf8_text( sanitize_textarea_field( $question['explanation'] ?? '' ) ),
							'order_index'    => isset( $question['order_index'] ) ? absint( $question['order_index'] ) : $index,
						),
						array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
					);
				}
			}
		}

		wp_send_json_success(
			array(
				'quiz_id'   => $quiz_id,
				'message'   => __( 'Quiz saved successfully.', 'cta-lms' ),
				'quiz'      => array(
					'id'    => $quiz_id,
					'title' => $title,
				),
				'questions' => CTA_Database::get_quiz_questions( $quiz_id ),
			)
		);
	}

	/**
	 * Render module row HTML for AJAX responses.
	 *
	 * @param object $module Module row.
	 * @return string
	 */
	public function render_module_row_html( $module ) {
		ob_start();
		?>
		<tr
			class="cta-module-row"
			data-module-id="<?php echo esc_attr( $module->id ); ?>"
			data-title="<?php echo esc_attr( $module->title ); ?>"
			data-description="<?php echo esc_attr( wp_strip_all_tags( (string) $module->description ) ); ?>"
			data-video-url="<?php echo esc_url( (string) $module->video_url ); ?>"
			data-duration="<?php echo esc_attr( (string) $module->duration_mins ); ?>"
			data-locked="<?php echo esc_attr( (string) $module->is_locked ); ?>"
		>
			<td class="cta-module-row__handle" aria-label="<?php esc_attr_e( 'Drag to reorder', 'cta-lms' ); ?>">⋮⋮</td>
			<td><?php echo esc_html( (string) $module->order_index ); ?></td>
			<td><?php echo esc_html( $module->title ); ?></td>
			<td><?php echo esc_html( (string) $module->duration_mins ); ?> <?php esc_html_e( 'mins', 'cta-lms' ); ?></td>
			<td class="cta-table-actions">
				<button type="button" class="button button-small cta-edit-module" data-module-id="<?php echo esc_attr( $module->id ); ?>"><?php esc_html_e( 'Edit', 'cta-lms' ); ?></button>
				<button type="button" class="button button-small button-link-delete cta-delete-module" data-module-id="<?php echo esc_attr( $module->id ); ?>"><?php esc_html_e( 'Delete', 'cta-lms' ); ?></button>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render upcoming session row HTML.
	 *
	 * @param object $session Session row.
	 * @return string
	 */
	public function render_session_row_html( $session ) {
		ob_start();
		?>
		<tr data-session-id="<?php echo esc_attr( $session->id ); ?>">
			<td><?php echo esc_html( cta_lms_format_session_date( $session->session_date, 'M j, Y' ) ); ?></td>
			<td><?php echo esc_html( cta_lms_format_session_time( $session->session_date, $session->session_time, 'g:i A T' ) ); ?></td>
			<td><?php echo esc_html( ucfirst( $session->session_type ) ); ?></td>
			<td><?php echo esc_html( (int) $session->seats_booked . ' / ' . (int) $session->seats_total ); ?></td>
			<td><span class="cta-status-badge cta-status-badge--open"><?php echo esc_html( ucfirst( $session->status ) ); ?></span></td>
			<td class="cta-table-actions">
				<button type="button" class="button button-small button-link-delete cta-cancel-session" data-session-id="<?php echo esc_attr( $session->id ); ?>"><?php esc_html_e( 'Cancel', 'cta-lms' ); ?></button>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Dashboard stats from database.
	 *
	 * @return array
	 */
	public static function get_dashboard_stats() {
		global $wpdb;

		return array(
			'total_courses'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}cta_courses WHERE status = 'published'" ),
			'total_enrolled'      => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}cta_enrollments" ),
			'total_completions'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}cta_enrollments WHERE status = 'completed'" ),
			'total_revenue'       => (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}cta_payments WHERE status = 'completed'" ),
			'active_subscribers'  => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
					'cta_supervision_status',
					'active'
				)
			),
			'certificates_issued' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}cta_certificates" ),
		);
	}

	/**
	 * Recent enrollments for dashboard.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public static function get_recent_enrollments( $limit = 10 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT e.*, u.display_name, c.title AS course_title, p.status AS payment_status
				FROM {$wpdb->prefix}cta_enrollments e
				LEFT JOIN {$wpdb->users} u ON u.ID = e.user_id
				LEFT JOIN {$wpdb->prefix}cta_courses c ON c.id = e.course_id
				LEFT JOIN {$wpdb->prefix}cta_payments p ON p.stripe_payment_id = e.payment_id
				ORDER BY e.enrolled_at DESC
				LIMIT %d",
				$limit
			)
		);
	}

	/**
	 * Recent user bookings for dashboard.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public static function get_recent_bookings( $limit = 5 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.*, u.display_name
				FROM {$wpdb->prefix}cta_bookings b
				LEFT JOIN {$wpdb->users} u ON u.ID = b.user_id
				WHERE b.user_id > 0
				ORDER BY b.created_at DESC
				LIMIT %d",
				$limit
			)
		);
	}

	/**
	 * Course category options.
	 *
	 * @return array
	 */
	public static function get_course_categories() {
		return array(
			'Law & Ethics'        => __( 'Law & Ethics', 'cta-lms' ),
			'Clinical Skills'     => __( 'Clinical Skills', 'cta-lms' ),
			'Specialized Topics'  => __( 'Specialized Topics', 'cta-lms' ),
			'Supervision'         => __( 'Supervision', 'cta-lms' ),
			'Exam Preparation'    => __( 'Exam Preparation', 'cta-lms' ),
		);
	}

	/**
	 * Page assignment option map.
	 *
	 * @return array
	 */
	public static function get_page_option_map() {
		return array(
			'cta_login_page_id'                => __( 'Login Page', 'cta-lms' ),
			'cta_courses_page_id'              => __( 'Courses Page', 'cta-lms' ),
			'cta_single_course_page_id'        => __( 'Single Course Page', 'cta-lms' ),
			'cta_supervision_page_id'          => __( 'Supervision Page', 'cta-lms' ),
			'cta_memberships_page_id'          => __( 'Memberships Page', 'cta-lms' ),
			'cta_student_dashboard_page_id'    => __( 'CE Dashboard', 'cta-lms' ),
			'cta_supervision_dashboard_page_id'=> __( 'Supervision Dashboard', 'cta-lms' ),
			'cta_course_player_page_id'        => __( 'Course Player Page', 'cta-lms' ),
			'cta_quiz_page_id'                 => __( 'Quiz Page', 'cta-lms' ),
		);
	}

	/**
	 * Shortcode reference data.
	 *
	 * @return array
	 */
	public static function get_shortcode_reference() {
		return array(
			array(
				'code'        => '[cta_header]',
				'description' => __( 'Site header with navigation', 'cta-lms' ),
				'usage'       => __( 'Add to any page top', 'cta-lms' ),
			),
			array(
				'code'        => '[cta_footer]',
				'description' => __( 'Site footer', 'cta-lms' ),
				'usage'       => __( 'Add to any page bottom', 'cta-lms' ),
			),
			array(
				'code'        => '[cta_auth_button]',
				'description' => __( 'Login / Dashboard button (changes when user is logged in)', 'cta-lms' ),
				'usage'       => __( 'Any page or Elementor. Optional: login_url, dashboard_url, login_text, dashboard_text, style="outline|primary", size="sm".', 'cta-lms' ),
			),
			array(
				'code'        => '[cta_login_form]',
				'description' => __( 'Login and register forms', 'cta-lms' ),
				'usage'       => __( 'Login page', 'cta-lms' ),
			),
			array(
				'code'        => '[cta_course_catalog]',
				'description' => __( 'Full CE courses grid', 'cta-lms' ),
				'usage'       => __( 'Courses page. Use limit="3" for featured only.', 'cta-lms' ),
			),
			array(
				'code'        => '[cta_single_course]',
				'description' => __( 'Individual course detail page', 'cta-lms' ),
				'usage'       => __( 'Single course page. Requires ?course_id=X in URL.', 'cta-lms' ),
			),
			array(
				'code'        => '[cta_supervision_booking]',
				'description' => __( 'Supervision services + booking', 'cta-lms' ),
				'usage'       => __( 'Supervision page', 'cta-lms' ),
			),
			array(
				'code'        => '[cta_membership_pricing]',
				'description' => __( 'Bundles and pricing cards', 'cta-lms' ),
				'usage'       => __( 'Memberships page', 'cta-lms' ),
			),
			array(
				'code'        => '[cta_student_dashboard]',
				'description' => __( 'CE student portal', 'cta-lms' ),
				'usage'       => __( 'CE Dashboard page', 'cta-lms' ),
			),
			array(
				'code'        => '[cta_supervision_dashboard]',
				'description' => __( 'Supervision associate portal', 'cta-lms' ),
				'usage'       => __( 'Supervision Dashboard page', 'cta-lms' ),
			),
			array(
				'code'        => '[cta_course_player]',
				'description' => __( 'CE course module player', 'cta-lms' ),
				'usage'       => __( 'Course Player page. Requires ?course_id=X in URL.', 'cta-lms' ),
			),
			array(
				'code'        => '[cta_quiz]',
				'description' => __( 'Course quiz + evaluation', 'cta-lms' ),
				'usage'       => __( 'Quiz page. Requires ?course_id=X. Linked from course player.', 'cta-lms' ),
			),
		);
	}

	/**
	 * Fetch quiz row for a course (admin — any status).
	 *
	 * @param int $course_id Course ID.
	 * @return object|null
	 */
	private function get_course_quiz( $course_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}cta_quizzes WHERE course_id = %d LIMIT 1",
				$course_id
			)
		);
	}

	/**
	 * Load an admin view template.
	 *
	 * @param string $file View filename.
	 * @param array  $vars Variables for template.
	 */
	private function load_view( $file, $vars = array() ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cta-lms' ) );
		}

		$path = CTA_PLUGIN_DIR . 'admin/views/' . $file;

		if ( ! file_exists( $path ) ) {
			wp_die( esc_html__( 'Admin view not found.', 'cta-lms' ) );
		}

		$admin = $this;
		extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		include $path;
	}

	/**
	 * Verify admin POST request.
	 *
	 * @param string $action Nonce action.
	 */
	private function verify_admin_request( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'cta-lms' ) );
		}

		check_admin_referer( $action );
	}

	/**
	 * AJAX: approve a pending Associate.
	 */
	public function ajax_approve_associate() {
		$this->verify_admin_ajax();

		$user_id = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
		$result  = $this->review_associate_approval( $user_id, 'approve' );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message'             => CTA_Associate_Access::has_qualifying_plan( $user_id )
					? __( 'Associate approved. Supervision access is now unlocked.', 'cta-lms' )
					: __( 'Associate approved. They still need a purchased or admin-assigned plan before dashboard access unlocks.', 'cta-lms' ),
				'user_id'             => $user_id,
				'status'              => CTA_Associate_Access::get_approval_status( $user_id ),
				'supervision_status'  => CTA_Associate_Access::get_supervision_status( $user_id ),
				'access_granted'      => CTA_Associate_Access::can_access_supervision_features( $user_id ),
			)
		);
	}

	/**
	 * AJAX: assign an agency-paid supervision plan to an Associate.
	 */
	public function ajax_assign_associate_plan() {
		$this->verify_admin_ajax();

		$user_id   = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
		$plan_slug = sanitize_text_field( wp_unslash( $_POST['plan_slug'] ?? 'group' ) );
		$note      = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );
		$result    = CTA_Associate_Access::assign_plan( $user_id, $plan_slug, $note );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message'   => __( 'Agency-paid plan assigned.', 'cta-lms' ),
				'user_id'   => $user_id,
				'plan_name' => CTA_Associate_Access::get_plan_display_name( $user_id ),
				'has_plan'  => true,
			)
		);
	}

	/**
	 * Admin-post: assign agency-paid plan (works without JavaScript).
	 */
	public function handle_assign_associate_plan() {
		$user_id   = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
		$plan_slug = sanitize_text_field( wp_unslash( $_POST['plan_slug'] ?? 'group' ) );
		$note      = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );

		check_admin_referer( 'cta_assign_plan_' . $user_id, 'cta_assign_plan_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'cta-lms' ) );
		}

		$result = CTA_Associate_Access::assign_plan( $user_id, $plan_slug, $note );
		$flash  = is_wp_error( $result ) ? 'error' : 'assigned';

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => 'cta-lms-approvals',
					'cta_approval' => $flash,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * AJAX: reject a pending Associate (keeps privileges locked).
	 */
	public function ajax_reject_associate() {
		$this->verify_admin_ajax();

		$user_id = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
		$reason  = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );
		$result  = $this->review_associate_approval( $user_id, 'reject', $reason );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Associate rejected. Access remains locked.', 'cta-lms' ),
				'user_id' => $user_id,
				'status'  => CTA_Associate_Access::STATUS_REJECTED,
			)
		);
	}

	/**
	 * Admin-post: approve Associate (works without JavaScript).
	 */
	public function handle_approve_associate() {
		$this->handle_associate_review_post( 'approve' );
	}

	/**
	 * Admin-post: reject Associate (works without JavaScript).
	 */
	public function handle_reject_associate() {
		$this->handle_associate_review_post( 'reject' );
	}

	/**
	 * Process Approve/Reject form submissions.
	 *
	 * @param string $decision approve|reject.
	 */
	private function handle_associate_review_post( $decision ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'cta-lms' ) );
		}

		$user_id = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
		$reason  = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );
		check_admin_referer( 'cta_review_associate_' . $user_id, 'cta_approval_nonce' );

		$result = $this->review_associate_approval( $user_id, $decision, $reason );
		$flash  = is_wp_error( $result ) ? 'error' : ( 'approve' === $decision ? 'approved' : 'rejected' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => 'cta-lms-approvals',
					'cta_approval' => $flash,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Shared Approve/Reject business logic.
	 *
	 * @param int    $user_id  User ID.
	 * @param string $decision approve|reject.
	 * @param string $reason   Optional rejection reason.
	 * @return true|WP_Error
	 */
	private function review_associate_approval( $user_id, $decision, $reason = '' ) {
		$user_id  = absint( $user_id );
		$decision = sanitize_key( $decision );
		$reason   = sanitize_textarea_field( $reason );

		if ( ! $user_id || ! CTA_Associate_Access::is_associate( $user_id ) ) {
			return new WP_Error( 'invalid_associate', __( 'Invalid Associate account.', 'cta-lms' ) );
		}

		$status = CTA_Associate_Access::get_approval_status( $user_id );

		if ( 'approve' === $decision ) {
			// Approval is vetting only — a plan is not required. Access stays locked until purchase/assignment.
			if ( CTA_Associate_Access::STATUS_APPROVED === $status ) {
				return new WP_Error( 'already_approved', __( 'This Associate is already approved.', 'cta-lms' ) );
			}

			$ok = CTA_Associate_Access::approve( $user_id );

			if ( is_wp_error( $ok ) ) {
				return $ok;
			}

			if ( ! $ok ) {
				return new WP_Error( 'approve_failed', __( 'Unable to approve this Associate.', 'cta-lms' ) );
			}

			return true;
		}

		// Reject/revoke from any state except when already rejected.
		if ( CTA_Associate_Access::STATUS_REJECTED === $status ) {
			return new WP_Error( 'already_rejected', __( 'This Associate is already rejected.', 'cta-lms' ) );
		}

		$ok = CTA_Associate_Access::reject( $user_id, $reason );

		if ( ! $ok ) {
			return new WP_Error( 'update_failed', __( 'Unable to reject this Associate.', 'cta-lms' ) );
		}

		return true;
	}

	/**
	 * Verify admin AJAX request.
	 */
	private function verify_admin_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'cta-lms' ) ) );
		}

		check_ajax_referer( 'cta_admin_nonce', 'nonce' );
	}
}
}