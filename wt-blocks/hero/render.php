<?php
/**
 * Hero Block Render Template
 *
 * @package webtero
 */

defined( 'ABSPATH' ) || exit;

$text_content     = $block['text_content'] ?? '';
$background_image = $block['background_image'] ?? '';
$is_preview       = $block['is_preview'] ?? false;

// Get background image URL if ID is provided
$bg_image_url = '';
if ( $background_image ) {
	$bg_image_url = wp_get_attachment_image_url( absint( $background_image ), 'full' );
}

// Build inline styles
$inline_styles = '';
if ( $bg_image_url ) {
	$inline_styles = sprintf(
		'background-image: url(%s); background-size: cover; background-position: center;',
		esc_url( $bg_image_url )
	);
}
?>

<div class="webtero-hero" <?php echo $inline_styles ? 'style="' . esc_attr( $inline_styles ) . '"' : ''; ?>>
	<div class="webtero-hero__content">
		<?php
		if ( ! empty( $text_content ) ) {
			echo wp_kses_post( $text_content );
		} elseif ( $is_preview ) {
			echo '<h1>' . esc_html__( 'Hero Title', 'webtero' ) . '</h1>';
			echo '<p>' . esc_html__( 'Add your hero content here.', 'webtero' ) . '</p>';
		}
		?>
	</div>
</div>
