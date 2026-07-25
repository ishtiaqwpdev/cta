<?php
/**
 * Admin-configurable CE course evaluation questions.
 *
 * Question definitions live in cta_evaluation_questions so CAMFT/CTA can
 * change the approved form without code changes. Student submissions remain
 * in cta_evaluations (one row per user+course).
 *
 * @package CTA_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CTA_Evaluation_Questions
 */
if ( ! class_exists( 'CTA_Evaluation_Questions' ) ) {

class CTA_Evaluation_Questions {

	const TABLE = 'cta_evaluation_questions';

	/**
	 * Supported question types for the admin UI / student form.
	 *
	 * @return array type => label
	 */
	public static function get_types() {
		return array(
			'rating'           => __( 'Rating scale (1–5)', 'cta-lms' ),
			'multiple_choice'  => __( 'Multiple choice', 'cta-lms' ),
			'textarea'         => __( 'Open text', 'cta-lms' ),
		);
	}

	/**
	 * Default Likert options for rating questions.
	 *
	 * @return array
	 */
	public static function default_rating_options() {
		return array(
			'1' => __( '1 — Strongly Disagree', 'cta-lms' ),
			'2' => __( '2 — Disagree', 'cta-lms' ),
			'3' => __( '3 — Neutral', 'cta-lms' ),
			'4' => __( '4 — Agree', 'cta-lms' ),
			'5' => __( '5 — Strongly Agree', 'cta-lms' ),
		);
	}

	/**
	 * Table name with prefix.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Create / migrate the questions table and seed defaults when empty.
	 */
	public static function install() {
		global $wpdb;

		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  question_key varchar(100) NOT NULL,
  section_label varchar(255) NOT NULL DEFAULT '',
  label text NOT NULL,
  question_type varchar(40) NOT NULL DEFAULT 'rating',
  options_json longtext,
  is_required tinyint(1) NOT NULL DEFAULT 1,
  summary_field varchar(50) NOT NULL DEFAULT '',
  order_index int(11) NOT NULL DEFAULT 0,
  status varchar(20) NOT NULL DEFAULT 'active',
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY question_key (question_key),
  KEY status_order (status, order_index)
) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		self::seed_defaults_if_empty();
	}

	/**
	 * Seed placeholder default questions when the table has none.
	 */
	public static function seed_defaults_if_empty() {
		global $wpdb;

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		if ( $count > 0 ) {
			return;
		}

		$defaults = self::get_builtin_defaults();
		$order    = 0;

		foreach ( $defaults as $row ) {
			self::insert_question(
				array(
					'question_key'  => $row['id'],
					'section_label' => $row['section'],
					'label'         => $row['label'],
					'question_type' => self::normalize_type( $row['type'] ),
					'options'       => isset( $row['options'] ) ? $row['options'] : array(),
					'is_required'   => ! empty( $row['required'] ) ? 1 : 0,
					'summary_field' => isset( $row['summary'] ) ? $row['summary'] : '',
					'order_index'   => $order++,
					'status'        => 'active',
				)
			);
		}
	}

	/**
	 * Built-in placeholder questions (used as seed until admin edits).
	 *
	 * @return array
	 */
	public static function get_builtin_defaults() {
		$likert = self::default_rating_options();

		return array(
			array(
				'id'       => 'content_quality',
				'section'  => __( 'Course Content', 'cta-lms' ),
				'label'    => __( 'How would you rate the overall quality of the course content?', 'cta-lms' ),
				'type'     => 'rating',
				'required' => true,
				'options'  => $likert,
				'summary'  => 'content_quality',
			),
			array(
				'id'       => 'materials_clarity',
				'section'  => __( 'Course Content', 'cta-lms' ),
				'label'    => __( 'How clear and well organized were the instructional materials?', 'cta-lms' ),
				'type'     => 'rating',
				'required' => true,
				'options'  => $likert,
				'summary'  => 'rating',
			),
			array(
				'id'       => 'instructor_clarity',
				'section'  => __( 'Instruction', 'cta-lms' ),
				'label'    => __( 'How would you rate the clarity of the instructor / presentation?', 'cta-lms' ),
				'type'     => 'rating',
				'required' => true,
				'options'  => $likert,
				'summary'  => 'instructor_rating',
			),
			array(
				'id'       => 'would_recommend',
				'section'  => __( 'Overall', 'cta-lms' ),
				'label'    => __( 'Would you recommend this course to a colleague?', 'cta-lms' ),
				'type'     => 'multiple_choice',
				'required' => true,
				'options'  => array(
					'yes' => __( 'Yes', 'cta-lms' ),
					'no'  => __( 'No', 'cta-lms' ),
				),
				'summary'  => 'would_recommend',
			),
			array(
				'id'       => 'comments',
				'section'  => __( 'Additional Feedback', 'cta-lms' ),
				'label'    => __( 'Additional comments or suggestions (optional)', 'cta-lms' ),
				'type'     => 'textarea',
				'required' => false,
				'summary'  => 'comments',
			),
		);
	}

	/**
	 * Normalize legacy type names to admin types.
	 *
	 * @param string $type Raw type.
	 * @return string
	 */
	public static function normalize_type( $type ) {
		$type = sanitize_key( (string) $type );

		if ( 'likert' === $type ) {
			return 'rating';
		}
		if ( 'yes_no' === $type ) {
			return 'multiple_choice';
		}
		if ( ! isset( self::get_types()[ $type ] ) ) {
			return 'rating';
		}

		return $type;
	}

	/**
	 * Fetch questions for the student form (active only, ordered).
	 *
	 * Shape matches the previous hardcoded CTA_Quiz::get_evaluation_questions() array.
	 *
	 * @return array
	 */
	public static function get_form_questions() {
		self::seed_defaults_if_empty();

		$rows = self::get_questions( 'active' );

		if ( empty( $rows ) ) {
			return self::rows_to_form_questions( self::get_builtin_defaults_as_objects() );
		}

		return self::rows_to_form_questions( $rows );
	}

	/**
	 * Fetch question rows for admin (all statuses or filtered).
	 *
	 * @param string $status active|inactive|all.
	 * @return array
	 */
	public static function get_questions( $status = 'all' ) {
		global $wpdb;

		$table = self::table_name();

		if ( 'all' === $status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return (array) $wpdb->get_results(
				"SELECT * FROM {$table} ORDER BY order_index ASC, id ASC"
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE status = %s ORDER BY order_index ASC, id ASC",
				sanitize_key( $status )
			)
		);
	}

	/**
	 * Get one question by ID.
	 *
	 * @param int $id Question ID.
	 * @return object|null
	 */
	public static function get_question( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table_name() . ' WHERE id = %d',
				$id
			)
		);
	}

	/**
	 * Insert a question.
	 *
	 * @param array $data Question fields.
	 * @return int|WP_Error Insert ID or error.
	 */
	public static function insert_question( $data ) {
		global $wpdb;

		$prepared = self::prepare_row_data( $data, true );
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$ok = $wpdb->insert( self::table_name(), $prepared['data'], $prepared['formats'] );

		if ( ! $ok ) {
			return new WP_Error( 'cta_eval_insert', __( 'Could not save evaluation question.', 'cta-lms' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update a question.
	 *
	 * @param int   $id   Question ID.
	 * @param array $data Fields.
	 * @return true|WP_Error
	 */
	public static function update_question( $id, $data ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id || ! self::get_question( $id ) ) {
			return new WP_Error( 'cta_eval_missing', __( 'Question not found.', 'cta-lms' ) );
		}

		$prepared = self::prepare_row_data( $data, false );
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ok = $wpdb->update(
			self::table_name(),
			$prepared['data'],
			array( 'id' => $id ),
			$prepared['formats'],
			array( '%d' )
		);

		if ( false === $ok ) {
			return new WP_Error( 'cta_eval_update', __( 'Could not update evaluation question.', 'cta-lms' ) );
		}

		return true;
	}

	/**
	 * Delete a question definition (does not affect past submissions).
	 *
	 * @param int $id Question ID.
	 * @return true|WP_Error
	 */
	public static function delete_question( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return new WP_Error( 'cta_eval_missing', __( 'Question not found.', 'cta-lms' ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete( self::table_name(), array( 'id' => $id ), array( '%d' ) );

		if ( ! $deleted ) {
			return new WP_Error( 'cta_eval_delete', __( 'Could not delete evaluation question.', 'cta-lms' ) );
		}

		return true;
	}

	/**
	 * Reorder questions by ID list.
	 *
	 * @param array $ordered_ids Ordered question IDs.
	 */
	public static function reorder( $ordered_ids ) {
		global $wpdb;

		$table = self::table_name();
		foreach ( array_values( (array) $ordered_ids ) as $index => $qid ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array( 'order_index' => (int) $index ),
				array( 'id' => absint( $qid ) ),
				array( '%d' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Convert DB rows (or builtin arrays) into the form question shape.
	 *
	 * @param array $rows DB objects or builtin arrays.
	 * @return array
	 */
	public static function rows_to_form_questions( $rows ) {
		$out = array();

		foreach ( (array) $rows as $row ) {
			if ( is_array( $row ) ) {
				$type    = self::normalize_type( $row['type'] ?? 'rating' );
				$options = isset( $row['options'] ) && is_array( $row['options'] ) ? $row['options'] : array();
				$out[]   = array(
					'id'       => (string) $row['id'],
					'section'  => (string) ( $row['section'] ?? '' ),
					'label'    => (string) ( $row['label'] ?? '' ),
					'type'     => $type,
					'required' => ! empty( $row['required'] ),
					'options'  => $options ? $options : ( 'rating' === $type ? self::default_rating_options() : array() ),
					'summary'  => (string) ( $row['summary'] ?? '' ),
				);
				continue;
			}

			$type    = self::normalize_type( $row->question_type ?? 'rating' );
			$options = array();
			if ( ! empty( $row->options_json ) ) {
				$decoded = json_decode( (string) $row->options_json, true );
				if ( is_array( $decoded ) ) {
					$options = $decoded;
				}
			}
			if ( 'rating' === $type && empty( $options ) ) {
				$options = self::default_rating_options();
			}

			$out[] = array(
				'id'       => (string) $row->question_key,
				'section'  => (string) $row->section_label,
				'label'    => (string) $row->label,
				'type'     => $type,
				'required' => (int) $row->is_required === 1,
				'options'  => $options,
				'summary'  => (string) $row->summary_field,
			);
		}

		return $out;
	}

	/**
	 * Builtin defaults as pseudo-objects for fallback rendering.
	 *
	 * @return array
	 */
	private static function get_builtin_defaults_as_objects() {
		return self::get_builtin_defaults();
	}

	/**
	 * Prepare insert/update payload.
	 *
	 * @param array $data   Raw data.
	 * @param bool  $is_new Whether inserting.
	 * @return array|WP_Error { data, formats }
	 */
	private static function prepare_row_data( $data, $is_new ) {
		$label = sanitize_text_field( $data['label'] ?? '' );
		if ( '' === $label ) {
			return new WP_Error( 'cta_eval_label', __( 'Question label is required.', 'cta-lms' ) );
		}

		$type = self::normalize_type( $data['question_type'] ?? ( $data['type'] ?? 'rating' ) );
		$key  = sanitize_key( $data['question_key'] ?? '' );

		if ( '' === $key ) {
			$key = sanitize_key( substr( md5( $label . microtime( true ) ), 0, 12 ) );
			if ( '' === $key ) {
				$key = 'q_' . wp_generate_password( 8, false, false );
			}
		}

		$options = array();
		if ( isset( $data['options'] ) && is_array( $data['options'] ) ) {
			foreach ( $data['options'] as $opt_key => $opt_label ) {
				$opt_key = sanitize_key( (string) $opt_key );
				if ( '' === $opt_key ) {
					continue;
				}
				$options[ $opt_key ] = sanitize_text_field( (string) $opt_label );
			}
		} elseif ( ! empty( $data['options_text'] ) ) {
			$lines = preg_split( '/\r\n|\r|\n/', (string) $data['options_text'] );
			$i     = 1;
			foreach ( (array) $lines as $line ) {
				$line = trim( $line );
				if ( '' === $line ) {
					continue;
				}
				if ( false !== strpos( $line, '|' ) ) {
					list( $opt_key, $opt_label ) = array_map( 'trim', explode( '|', $line, 2 ) );
					$opt_key = sanitize_key( $opt_key );
					if ( $opt_key ) {
						$options[ $opt_key ] = sanitize_text_field( $opt_label );
					}
				} else {
					$options[ (string) $i ] = sanitize_text_field( $line );
					++$i;
				}
			}
		}

		if ( 'rating' === $type && empty( $options ) ) {
			$options = self::default_rating_options();
		}

		if ( 'multiple_choice' === $type && count( $options ) < 2 ) {
			return new WP_Error(
				'cta_eval_options',
				__( 'Multiple choice questions need at least two options (one per line, or value|Label).', 'cta-lms' )
			);
		}

		$status = sanitize_key( $data['status'] ?? 'active' );
		if ( ! in_array( $status, array( 'active', 'inactive' ), true ) ) {
			$status = 'active';
		}

		$summary = sanitize_key( $data['summary_field'] ?? ( $data['summary'] ?? '' ) );
		$allowed_summary = array( '', 'rating', 'content_quality', 'instructor_rating', 'would_recommend', 'comments' );
		if ( ! in_array( $summary, $allowed_summary, true ) ) {
			$summary = '';
		}

		$row = array(
			'question_key'  => $key,
			'section_label' => sanitize_text_field( $data['section_label'] ?? ( $data['section'] ?? '' ) ),
			'label'         => $label,
			'question_type' => $type,
			'options_json'  => wp_json_encode( $options ),
			'is_required'   => ! empty( $data['is_required'] ) || ! empty( $data['required'] ) ? 1 : 0,
			'summary_field' => $summary,
			'order_index'   => isset( $data['order_index'] ) ? absint( $data['order_index'] ) : 0,
			'status'        => $status,
		);

		$formats = array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s' );

		if ( ! $is_new ) {
			// On update, do not change question_key (keeps historical response keys stable).
			unset( $row['question_key'] );
			array_shift( $formats );
		}

		return array(
			'data'    => $row,
			'formats' => $formats,
		);
	}
}

}
