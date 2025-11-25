<?php
/**
 * FAQ Block Render Template
 *
 * @package webtero
 */

defined( 'ABSPATH' ) || exit;

$faq_items  = $block['faq_items'] ?? [];
$is_preview = $block['is_preview'] ?? false;

if ( empty( $faq_items ) ) {
	if ( $is_preview ) {
		echo '<p>' . esc_html__( 'No FAQ items added yet.', 'webtero' ) . '</p>';
	}
	return;
}
?>

<div class="webtero-faq">
	<?php foreach ( $faq_items as $index => $item ) :
		$heading      = $item['heading'] ?? '';
		$text_content = $item['text_content'] ?? '';

		if ( empty( $heading ) && empty( $text_content ) ) {
			continue;
		}

		$item_id = 'faq-item-' . esc_attr( uniqid() );
	?>
		<div class="webtero-faq__item">
			<button
				class="webtero-faq__question"
				aria-expanded="false"
				aria-controls="<?php echo $item_id; ?>"
				type="button"
			>
				<span class="webtero-faq__question-text">
					<?php echo esc_html( $heading ); ?>
				</span>
				<span class="webtero-faq__icon" aria-hidden="true">+</span>
			</button>
			<div
				class="webtero-faq__answer"
				id="<?php echo $item_id; ?>"
				hidden
			>
				<div class="webtero-faq__answer-content">
					<?php echo wp_kses_post( $text_content ); ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<script>
(function() {
	document.querySelectorAll('.webtero-faq__question').forEach(function(button) {
		button.addEventListener('click', function() {
			const expanded = this.getAttribute('aria-expanded') === 'true';
			const answer = this.nextElementSibling;
			const icon = this.querySelector('.webtero-faq__icon');

			this.setAttribute('aria-expanded', !expanded);
			answer.hidden = expanded;
			icon.textContent = expanded ? '+' : '−';
		});
	});
})();
</script>
