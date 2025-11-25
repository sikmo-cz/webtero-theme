<?php
/**
 * YouTube Block Render Template
 *
 * @package webtero
 */

defined( 'ABSPATH' ) || exit;

$youtube_url = $block['youtube_url'] ?? '';
$is_preview  = $block['is_preview'] ?? false;

// Extract video ID
$video_id = \WT\Blocks\Youtube_Block::extract_video_id( $youtube_url );

if ( empty( $video_id ) ) {
	if ( $is_preview ) {
		echo '<div class="webtero-youtube webtero-youtube--placeholder">';
		echo '<p>' . esc_html__( 'Please enter a valid YouTube URL', 'webtero' ) . '</p>';
		echo '</div>';
	}
	return;
}

// YouTube thumbnail URL
$thumbnail_url = sprintf( 'https://i3.ytimg.com/vi/%s/maxresdefault.jpg', esc_attr( $video_id ) );
// Fallback to hqdefault if maxresdefault doesn't exist
$thumbnail_fallback = sprintf( 'https://i3.ytimg.com/vi/%s/hqdefault.jpg', esc_attr( $video_id ) );

// YouTube embed URL
$embed_url = sprintf( 'https://www.youtube.com/embed/%s?autoplay=1', esc_attr( $video_id ) );
?>

<div class="webtero-youtube" data-video-id="<?php echo esc_attr( $video_id ); ?>">
	<div class="webtero-youtube__thumbnail">
		<img
			class="webtero-youtube__thumbnail-image"
			src="<?php echo esc_url( $thumbnail_url ); ?>"
			onerror="this.src='<?php echo esc_url( $thumbnail_fallback ); ?>'"
			alt="<?php esc_attr_e( 'YouTube video thumbnail', 'webtero' ); ?>"
			loading="lazy"
		>
		<button
			class="webtero-youtube__play-button"
			type="button"
			aria-label="<?php esc_attr_e( 'Play video', 'webtero' ); ?>"
			data-embed-url="<?php echo esc_attr( $embed_url ); ?>"
		>
			<svg width="68" height="48" viewBox="0 0 68 48" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C.06 13.05 0 24 0 24s.06 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.64-3.26 5.42-6.19C67.94 34.95 68 24 68 24s-.06-10.95-1.48-16.26z" fill="red"/>
				<path d="M45 24L27 14v20" fill="white"/>
			</svg>
		</button>
	</div>
	<div class="webtero-youtube__iframe-container" style="display: none;"></div>
</div>
