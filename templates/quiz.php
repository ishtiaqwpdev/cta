<?php
/**
 * Course quiz page template.
 *
 * @package CTA_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cert_url = ( $certificate && class_exists( 'CTA_Certificates' ) )
	? CTA_Certificates::get_print_url( (int) $certificate->id, true )
	: '';
if ( empty( $evaluation_questions ) || ! is_array( $evaluation_questions ) ) {
	$evaluation_questions = CTA_Quiz::get_evaluation_questions();
}
?>
<div class="cta-plugin-wrapper">
<div
	class="cta-lms cta-quiz-page"
	id="cta-quiz-app"
	data-course-id="<?php echo esc_attr( $course->id ); ?>"
	data-quiz-id="<?php echo esc_attr( $quiz->id ); ?>"
	data-attempt-id="<?php echo esc_attr( $active_attempt ? $active_attempt->id : 0 ); ?>"
	data-time-limit="0"
	data-passing-score="<?php echo esc_attr( (int) $quiz->passing_score ?: 70 ); ?>"
	data-question-count="<?php echo esc_attr( $question_count ); ?>"
	data-view-state="<?php echo esc_attr( $view_state ); ?>"
>
	<div class="cta-quiz-header">
		<p class="course-player__back">
			<?php if ( $player_url ) : ?>
				<a href="<?php echo esc_url( $player_url ); ?>">&larr; <?php echo esc_html__( 'Back to Course', 'cta-lms' ); ?></a>
			<?php endif; ?>
		</p>
		<h1 class="cta-quiz-course-title"><?php echo esc_html( $course->title ); ?></h1>
		<div class="cta-quiz-timer" id="cta-quiz-timer" hidden aria-hidden="true"></div>
	</div>

	<div class="cta-quiz-panel <?php echo 'start' === $view_state ? 'cta-quiz-panel--active' : ''; ?>" data-quiz-panel="start" <?php echo 'start' !== $view_state ? 'hidden' : ''; ?>>
		<div class="card cta-quiz-start-card">
			<h2><?php echo esc_html( $quiz->title ); ?></h2>
			<div class="cta-quiz-info-grid">
				<div><strong><?php echo esc_html__( 'Questions', 'cta-lms' ); ?></strong><span><?php echo esc_html( (string) $question_count ); ?></span></div>
				<div><strong><?php echo esc_html__( 'Passing Score', 'cta-lms' ); ?></strong><span><?php echo esc_html( (int) $quiz->passing_score ?: 70 ); ?>%</span></div>
				<div><strong><?php echo esc_html__( 'Time Limit', 'cta-lms' ); ?></strong><span><?php echo esc_html( $time_limit_label ); ?></span></div>
				<div><strong><?php echo esc_html__( 'Attempts', 'cta-lms' ); ?></strong><span><?php echo esc_html( $attempts_label ); ?></span></div>
			</div>
			<?php if ( $attempt_count > 0 ) : ?>
				<p class="cta-quiz-last-attempt">
					<?php
					printf(
						/* translators: %d: number of previous attempts */
						esc_html__( 'Previous attempts: %d', 'cta-lms' ),
						(int) $attempt_count
					);
					?>
				</p>
			<?php endif; ?>
			<?php if ( $last_attempt ) : ?>
				<p class="cta-quiz-last-attempt">
					<?php
					$result_label = (int) $last_attempt->passed
						? esc_html__( 'Passed', 'cta-lms' )
						: esc_html__( 'Failed', 'cta-lms' );
					printf(
						/* translators: 1: score, 2: result */
						esc_html__( 'Last attempt: %1$d%% — %2$s', 'cta-lms' ),
						(int) $last_attempt->score,
						$result_label
					);
					?>
				</p>
			<?php endif; ?>
			<button type="button" class="btn btn-primary btn--lg" id="cta-start-quiz"><?php echo esc_html__( 'Start Quiz', 'cta-lms' ); ?></button>
		</div>
	</div>

	<div class="cta-quiz-panel <?php echo 'in_progress' === $view_state ? 'cta-quiz-panel--active' : ''; ?>" data-quiz-panel="questions" <?php echo 'in_progress' !== $view_state ? 'hidden' : ''; ?>>
		<p class="cta-quiz-progress" id="cta-quiz-progress"><?php echo esc_html__( 'Questions answered: 0 of 0', 'cta-lms' ); ?></p>
		<form id="cta-quiz-form" class="cta-quiz-form">
			<div id="cta-quiz-questions">
				<?php
				if ( 'in_progress' === $view_state && $active_attempt ) {
					echo $quiz_handler->render_quiz_questions( $quiz, $active_attempt, $questions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</div>
			<div class="cta-quiz-submit-section">
				<p class="cta-quiz-submit-warning"><?php echo esc_html__( 'Are you sure? You cannot change answers after submitting.', 'cta-lms' ); ?></p>
				<button type="button" class="btn btn-primary" id="cta-submit-quiz" disabled><?php echo esc_html__( 'Submit Quiz', 'cta-lms' ); ?></button>
			</div>
		</form>
	</div>

	<div class="cta-quiz-panel" data-quiz-panel="result" hidden>
		<div class="cta-quiz-result" id="cta-quiz-result"></div>
	</div>

	<div class="cta-quiz-panel <?php echo 'evaluation' === $view_state ? 'cta-quiz-panel--active' : ''; ?>" data-quiz-panel="evaluation" <?php echo 'evaluation' !== $view_state ? 'hidden' : ''; ?>>
		<div class="card cta-quiz-evaluation">
			<h2><?php echo esc_html__( 'Course Evaluation', 'cta-lms' ); ?></h2>
			<p><?php echo esc_html__( 'Complete the course evaluation to receive your certificate. Your certificate will not be available until this form is submitted.', 'cta-lms' ); ?></p>
			<form id="cta-evaluation-form" class="cta-evaluation-form" novalidate>
				<?php
				$current_section = '';
				foreach ( $evaluation_questions as $question ) :
					if ( $question['section'] !== $current_section ) :
						$current_section = $question['section'];
						?>
						<h3 class="cta-evaluation-section__title"><?php echo esc_html( $current_section ); ?></h3>
					<?php endif; ?>

					<div class="form-group cta-evaluation-question" data-question-id="<?php echo esc_attr( $question['id'] ); ?>" data-question-type="<?php echo esc_attr( $question['type'] ); ?>">
						<?php if ( 'textarea' === $question['type'] ) : ?>
							<label class="form-label" for="eval-<?php echo esc_attr( $question['id'] ); ?>">
								<?php echo esc_html( $question['label'] ); ?>
								<?php if ( ! empty( $question['required'] ) ) : ?>
									<span class="cta-required" aria-hidden="true">*</span>
								<?php endif; ?>
							</label>
							<textarea
								id="eval-<?php echo esc_attr( $question['id'] ); ?>"
								name="responses[<?php echo esc_attr( $question['id'] ); ?>]"
								class="form-input"
								rows="4"
								<?php echo ! empty( $question['required'] ) ? 'required' : ''; ?>
							></textarea>
						<?php else : ?>
							<span class="form-label" id="eval-label-<?php echo esc_attr( $question['id'] ); ?>">
								<?php echo esc_html( $question['label'] ); ?>
								<?php if ( ! empty( $question['required'] ) ) : ?>
									<span class="cta-required" aria-hidden="true">*</span>
								<?php endif; ?>
							</span>
							<div class="cta-evaluation-options" role="radiogroup" aria-labelledby="eval-label-<?php echo esc_attr( $question['id'] ); ?>">
								<?php
								$options = ! empty( $question['options'] ) && is_array( $question['options'] )
									? $question['options']
									: ( in_array( $question['type'], array( 'rating', 'likert' ), true )
										? ( class_exists( 'CTA_Evaluation_Questions' ) ? CTA_Evaluation_Questions::default_rating_options() : array() )
										: array() );
								foreach ( $options as $value => $option_label ) :
									?>
									<label class="cta-evaluation-option">
										<input
											type="radio"
											name="responses[<?php echo esc_attr( $question['id'] ); ?>]"
											value="<?php echo esc_attr( (string) $value ); ?>"
											<?php echo ! empty( $question['required'] ) ? 'required' : ''; ?>
										>
										<span><?php echo esc_html( $option_label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
				<button type="button" class="btn btn-primary" id="cta-submit-evaluation"><?php echo esc_html__( 'Submit Evaluation & Get Certificate', 'cta-lms' ); ?></button>
			</form>
		</div>
	</div>

	<div class="cta-quiz-panel <?php echo 'certificate_ready' === $view_state ? 'cta-quiz-panel--active' : ''; ?>" data-quiz-panel="certificate" <?php echo 'certificate_ready' !== $view_state ? 'hidden' : ''; ?>>
		<div class="cta-quiz-certificate-ready card">
			<div class="cta-quiz-certificate-ready__icon" aria-hidden="true">🏆</div>
			<h2><?php echo esc_html__( 'Your certificate is ready!', 'cta-lms' ); ?></h2>
			<?php if ( $certificate ) : ?>
				<p><?php echo esc_html__( 'Certificate number:', 'cta-lms' ); ?> <strong id="cta-certificate-number"><?php echo esc_html( $certificate->certificate_number ); ?></strong></p>
			<?php else : ?>
				<p><?php echo esc_html__( 'Certificate number:', 'cta-lms' ); ?> <strong id="cta-certificate-number"></strong></p>
			<?php endif; ?>
			<div id="cta-certificate-actions">
				<?php if ( $cert_url && $certificate ) : ?>
					<a href="<?php echo esc_url( $cert_url ); ?>" class="btn btn-primary cta-download-cert-btn" data-certificate-id="<?php echo esc_attr( $certificate->id ); ?>" target="_blank" rel="noopener">
						<?php echo esc_html__( 'Print / Save as PDF', 'cta-lms' ); ?>
					</a>
				<?php endif; ?>
			</div>
			<?php if ( $dashboard_url ) : ?>
				<a href="<?php echo esc_url( $dashboard_url ); ?>" class="btn btn-outline"><?php echo esc_html__( 'Return to Dashboard', 'cta-lms' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</div>
</div>
