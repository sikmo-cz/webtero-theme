<?php
/**
 * Spacer Block Render Template
 *
 * @package webtero
 */

defined( 'ABSPATH' ) || exit;

$height         = $block['height'] ?? 'default';
$custom_desktop = absint( $block['custom_desktop'] ?? 40 );
$custom_mobile  = absint( $block['custom_mobile'] ?? 20 );

// Height presets
$height_map = [
	'none'    => 0,
	'small'   => 20,
	'default' => 40,
	'big'     => 60,
	'large'   => 80,
];

// Determine height values
if ( $height === 'custom' ) {
	$desktop_height = $custom_desktop;
	$mobile_height  = $custom_mobile;
} else {
	$desktop_height = $height_map[ $height ] ?? 40;
	$mobile_height  = max( 0, floor( $desktop_height * 0.5 ) ); // Mobile is 50% of desktop for presets
}

// Build inline styles
$desktop_style = sprintf( 'height: %dpx;', $desktop_height );
$mobile_style  = sprintf( 'height: %dpx;', $mobile_height );

// Generate unique ID for media query
$spacer_id = 'webtero-spacer-' . uniqid();
?>

<div class="webtero-spacer <?php echo esc_attr( $spacer_id ); ?>" style="<?php echo esc_attr( $desktop_style ); ?>" aria-hidden="true"></div>

<?php if ( $desktop_height !== $mobile_height ) : ?>
	<style>
		@media (max-width: 768px) {
			.<?php echo esc_attr( $spacer_id ); ?> {
				<?php echo esc_attr( $mobile_style ); ?>
			}
		}
	</style>
<?php endif; ?>
