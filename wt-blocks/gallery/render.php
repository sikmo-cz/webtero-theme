<?php
/**
 * Gallery Block Render Template
 *
 * Displays gallery images in a grid
 *
 * Displays all field values for testing purposes
 * Available variables:
 * - $block array - Prepared block data from prepare_render_data()
 * - $attributes array - Raw block attributes
 *
 * @package webtero
 */

defined( 'ABSPATH' ) || exit;

$gallery_images = $block['gallery_images'] ?? [];

if ( empty( $gallery_images ) ) {
	if ( $block['is_preview'] ?? false ) {
		echo '<p>' . esc_html__( 'No images selected', 'webtero' ) . '</p>';
	}
	return;
}
?>

<div class="webtero-gallery">
	<?php foreach ( $gallery_images as $image_id ) :
		$image_id = absint( $image_id );
		if ( ! $image_id ) {
			continue;
		}

		$image_url = wp_get_attachment_image_url( $image_id, 'large' );
		$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

		if ( ! $image_url ) {
			continue;
		}
	?>
		<div class="webtero-gallery__item">
			<img
				src="<?php echo esc_url( $image_url ); ?>"
				alt="<?php echo esc_attr( $image_alt ); ?>"
				loading="lazy"
			>
		</div>
	<?php endforeach; ?>
</div>
