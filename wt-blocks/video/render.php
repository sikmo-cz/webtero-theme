<?php
/**
 * Video Block Render Template
 *
 * @package webtero
 */

defined( 'ABSPATH' ) || exit;

$video_file   = $block['video_file'] ?? '';
$poster_image = $block['poster_image'] ?? '';
$is_preview   = $block['is_preview'] ?? false;

// Get video URL from media ID
$video_url = '';
if ( $video_file ) {
	$video_url = wp_get_attachment_url( absint( $video_file ) );
}

// Get poster image URL from media ID
$poster_url = '';
if ( $poster_image ) {
	$poster_url = wp_get_attachment_image_url( absint( $poster_image ), 'large' );
}

// Show placeholder in preview mode if no video
if ( empty( $video_url ) ) {
	if ( $is_preview ) {
		echo '<div class="webtero-video webtero-video--placeholder">';
		echo '<p>' . esc_html__( 'No video file selected. Please select an MP4 video.', 'webtero' ) . '</p>';
		echo '</div>';
	}
	return;
}
?>

<div class="webtero-video">
	<video
		class="webtero-video__player"
		controls
		<?php echo $poster_url ? 'poster="' . esc_url( $poster_url ) . '"' : ''; ?>
		preload="metadata"
	>
		<source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4">
		<?php esc_html_e( 'Your browser does not support the video tag.', 'webtero' ); ?>
	</video>
</div>
