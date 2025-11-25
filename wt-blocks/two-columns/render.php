<?php
/**
 * Two Columns Block Render Template
 *
 * @package webtero
 */

defined( 'ABSPATH' ) || exit;

$left_content  = $block['left_content'] ?? '';
$right_content = $block['right_content'] ?? '';
$is_preview    = $block['is_preview'] ?? false;
?>

<div class="webtero-two-columns">
	<div class="webtero-two-columns__column">
		<?php
		if ( ! empty( $left_content ) ) {
			echo wp_kses_post( $left_content );
		} elseif ( $is_preview ) {
			echo '<h3>' . esc_html__( 'Left Column', 'webtero' ) . '</h3>';
			echo '<p>' . esc_html__( 'Add your content here.', 'webtero' ) . '</p>';
		}
		?>
	</div>
	<div class="webtero-two-columns__column">
		<?php
		if ( ! empty( $right_content ) ) {
			echo wp_kses_post( $right_content );
		} elseif ( $is_preview ) {
			echo '<h3>' . esc_html__( 'Right Column', 'webtero' ) . '</h3>';
			echo '<p>' . esc_html__( 'Add your content here.', 'webtero' ) . '</p>';
		}
		?>
	</div>
</div>
