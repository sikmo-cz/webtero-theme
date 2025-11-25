<?php
/**
 * Global Block Render Template
 *
 * @package webtero
 */

defined( 'ABSPATH' ) || exit;

$global_block_id  = $block['global_block_id'] ?? '';
$is_preview       = $block['is_preview'] ?? false;
$recursion_depth  = $block['recursion_depth'] ?? 0;

// Convert to integer
$global_block_id = absint( $global_block_id );

// No block selected
if ( empty( $global_block_id ) ) {
	if ( $is_preview ) {
		echo '<div class="webtero-global-block webtero-global-block--placeholder">';
		echo '<p>' . esc_html__( 'No global block selected. Use the sidebar to choose a global block.', 'webtero' ) . '</p>';
		echo '</div>';
	}
	return;
}

// Get the global block post
$global_block_post = get_post( $global_block_id );

// Check if post exists and is published
if ( ! $global_block_post || $global_block_post->post_status !== 'publish' || $global_block_post->post_type !== 'global_blocks' ) {
	if ( $is_preview ) {
		echo '<div class="webtero-global-block webtero-global-block--error">';
		echo '<p>' . esc_html__( 'Selected global block not found or is not published.', 'webtero' ) . '</p>';
		echo '</div>';
	}
	return;
}

// Render the global block content
?>

<div class="webtero-global-block" data-global-block-id="<?php echo esc_attr( $global_block_id ); ?>">
	<?php
	// Apply the_content filter to render Gutenberg blocks
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo apply_filters( 'the_content', $global_block_post->post_content );
	?>
</div>
