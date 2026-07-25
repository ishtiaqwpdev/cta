<?php
/**
 * Admin: CE course evaluation question bank.
 *
 * @package CTA_LMS
 *
 * @var array  $questions Question rows.
 * @var string $notice    Optional notice key.
 * @var object|null $edit_question Question being edited.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$questions     = isset( $questions ) ? $questions : array();
$notice        = isset( $notice ) ? $notice : '';
$edit_question = isset( $edit_question ) ? $edit_question : null;
$types         = CTA_Evaluation_Questions::get_types();
$is_editing    = $edit_question && ! empty( $edit_question->id );

$edit_options_text = '';
if ( $is_editing && ! empty( $edit_question->options_json ) ) {
	$decoded = json_decode( (string) $edit_question->options_json, true );
	if ( is_array( $decoded ) ) {
		$lines = array();
		foreach ( $decoded as $key => $label ) {
			$lines[] = $key . '|' . $label;
		}
		$edit_options_text = implode( "\n", $lines );
	}
}
?>
<div class="wrap cta-admin-wrap">
	<h1><?php esc_html_e( 'Course Evaluation Form', 'cta-lms' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Build the structured CE course evaluation students must complete after passing the quiz and before their certificate is issued. Add, edit, reorder, or remove questions here — no code changes required when the final approved question set arrives.', 'cta-lms' ); ?>
	</p>

	<?php if ( 'saved' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Evaluation question saved.', 'cta-lms' ); ?></p></div>
	<?php elseif ( 'deleted' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Evaluation question deleted. Past student submissions were not changed.', 'cta-lms' ); ?></p></div>
	<?php elseif ( 'reordered' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Question order updated.', 'cta-lms' ); ?></p></div>
	<?php elseif ( 'save_failed' === $notice ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Could not save the evaluation question. Check required fields and options.', 'cta-lms' ); ?></p></div>
	<?php endif; ?>

	<div class="cta-admin-panel">
		<h2><?php echo $is_editing ? esc_html__( 'Edit Question', 'cta-lms' ) : esc_html__( 'Add Question', 'cta-lms' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cta-admin-form">
			<?php wp_nonce_field( 'cta_save_evaluation_question' ); ?>
			<input type="hidden" name="action" value="cta_save_evaluation_question">
			<input type="hidden" name="question_id" value="<?php echo esc_attr( $is_editing ? (string) $edit_question->id : '0' ); ?>">

			<table class="form-table">
				<tr>
					<th><label for="cta-eval-section"><?php esc_html_e( 'Section', 'cta-lms' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="cta-eval-section" name="section_label" value="<?php echo esc_attr( $is_editing ? $edit_question->section_label : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Course Content', 'cta-lms' ); ?>">
					</td>
				</tr>
				<tr>
					<th><label for="cta-eval-label"><?php esc_html_e( 'Question', 'cta-lms' ); ?></label></th>
					<td>
						<textarea class="large-text" rows="2" id="cta-eval-label" name="label" required><?php echo esc_textarea( $is_editing ? $edit_question->label : '' ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th><label for="cta-eval-type"><?php esc_html_e( 'Type', 'cta-lms' ); ?></label></th>
					<td>
						<select id="cta-eval-type" name="question_type">
							<?php foreach ( $types as $type_key => $type_label ) : ?>
								<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $is_editing ? $edit_question->question_type : 'rating', $type_key ); ?>><?php echo esc_html( $type_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="cta-eval-options"><?php esc_html_e( 'Options', 'cta-lms' ); ?></label></th>
					<td>
						<textarea class="large-text" rows="5" id="cta-eval-options" name="options_text" placeholder="<?php esc_attr_e( "yes|Yes\nno|No\n\nor one label per line", 'cta-lms' ); ?>"><?php echo esc_textarea( $edit_options_text ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Required for Multiple choice. Optional for Rating (defaults to 1–5 Likert). Use value|Label per line, or one label per line. Leave blank for Open text.', 'cta-lms' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Required', 'cta-lms' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="is_required" value="1" <?php checked( ! $is_editing || (int) $edit_question->is_required === 1 ); ?>>
							<?php esc_html_e( 'Student must answer this question', 'cta-lms' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><label for="cta-eval-status"><?php esc_html_e( 'Status', 'cta-lms' ); ?></label></th>
					<td>
						<select id="cta-eval-status" name="status">
							<option value="active" <?php selected( $is_editing ? $edit_question->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'cta-lms' ); ?></option>
							<option value="inactive" <?php selected( $is_editing ? $edit_question->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'cta-lms' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="cta-eval-summary"><?php esc_html_e( 'Summary mapping (optional)', 'cta-lms' ); ?></label></th>
					<td>
						<select id="cta-eval-summary" name="summary_field">
							<option value=""><?php esc_html_e( 'None', 'cta-lms' ); ?></option>
							<?php
							$summary_choices = array(
								'rating'            => 'rating',
								'content_quality'   => 'content_quality',
								'instructor_rating' => 'instructor_rating',
								'would_recommend'   => 'would_recommend',
								'comments'          => 'comments',
							);
							$current_summary = $is_editing ? (string) $edit_question->summary_field : '';
							foreach ( $summary_choices as $skey => $slabel ) :
								?>
								<option value="<?php echo esc_attr( $skey ); ?>" <?php selected( $current_summary, $skey ); ?>><?php echo esc_html( $slabel ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Optional: map into legacy summary columns on the submission record. Full answers are always stored in the responses JSON.', 'cta-lms' ); ?></p>
					</td>
				</tr>
			</table>

			<p>
				<button type="submit" class="button button-primary"><?php echo $is_editing ? esc_html__( 'Update Question', 'cta-lms' ) : esc_html__( 'Add Question', 'cta-lms' ); ?></button>
				<?php if ( $is_editing ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=cta-lms-evaluation' ) ); ?>"><?php esc_html_e( 'Cancel', 'cta-lms' ); ?></a>
				<?php endif; ?>
			</p>
		</form>
	</div>

	<div class="cta-admin-panel">
		<h2><?php esc_html_e( 'Current Questions', 'cta-lms' ); ?></h2>
		<?php if ( empty( $questions ) ) : ?>
			<p><?php esc_html_e( 'No evaluation questions yet. Add one above (defaults are seeded automatically on first install).', 'cta-lms' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th style="width:40px;"><?php esc_html_e( '#', 'cta-lms' ); ?></th>
						<th><?php esc_html_e( 'Section', 'cta-lms' ); ?></th>
						<th><?php esc_html_e( 'Question', 'cta-lms' ); ?></th>
						<th><?php esc_html_e( 'Type', 'cta-lms' ); ?></th>
						<th><?php esc_html_e( 'Required', 'cta-lms' ); ?></th>
						<th><?php esc_html_e( 'Status', 'cta-lms' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'cta-lms' ); ?></th>
					</tr>
				</thead>
				<tbody id="cta-eval-questions-list">
					<?php foreach ( $questions as $index => $q ) : ?>
						<tr data-question-id="<?php echo esc_attr( (string) $q->id ); ?>">
							<td><?php echo esc_html( (string) ( (int) $index + 1 ) ); ?></td>
							<td><?php echo esc_html( $q->section_label ); ?></td>
							<td><strong><?php echo esc_html( $q->label ); ?></strong></td>
							<td><?php echo esc_html( isset( $types[ $q->question_type ] ) ? $types[ $q->question_type ] : $q->question_type ); ?></td>
							<td><?php echo (int) $q->is_required ? esc_html__( 'Yes', 'cta-lms' ) : esc_html__( 'No', 'cta-lms' ); ?></td>
							<td><?php echo esc_html( $q->status ); ?></td>
							<td>
								<a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=cta-lms-evaluation&edit=' . (int) $q->id ) ); ?>"><?php esc_html_e( 'Edit', 'cta-lms' ); ?></a>
								<a class="button button-small button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cta_delete_evaluation_question&question_id=' . (int) $q->id ), 'cta_delete_evaluation_question' ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this question from the form?', 'cta-lms' ) ); ?>');"><?php esc_html_e( 'Delete', 'cta-lms' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description"><?php esc_html_e( 'Tip: reorder by editing order_index via Save after moving items, or use the Move up/down controls if available. New questions are appended at the end.', 'cta-lms' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px;">
				<?php wp_nonce_field( 'cta_reorder_evaluation_questions' ); ?>
				<input type="hidden" name="action" value="cta_reorder_evaluation_questions">
				<?php foreach ( $questions as $index => $q ) : ?>
					<input type="hidden" name="order[]" value="<?php echo esc_attr( (string) $q->id ); ?>">
				<?php endforeach; ?>
				<label for="cta-eval-reorder"><?php esc_html_e( 'Reorder (comma-separated IDs, left = first)', 'cta-lms' ); ?></label><br>
				<input type="text" class="large-text" id="cta-eval-reorder" name="order_csv" value="<?php echo esc_attr( implode( ',', wp_list_pluck( $questions, 'id' ) ) ); ?>">
				<p><button type="submit" class="button"><?php esc_html_e( 'Save Order', 'cta-lms' ); ?></button></p>
			</form>
		<?php endif; ?>
	</div>
</div>
