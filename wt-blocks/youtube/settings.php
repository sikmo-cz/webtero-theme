<?php
/**
 * YouTube block settings
 *
 * YouTube video embed with lazy loading
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT\Blocks;

defined( 'ABSPATH' ) || exit;

class Youtube_Block extends Custom_Block {

	/**
	 * Get block fields
	 *
	 * @return array Field configuration
	 */
	public function get_fields(): array {
		return [
			[
				'type'        => 'text',
				'id'          => 'youtube_url',
				'label'       => __( 'YouTube URL', 'webtero' ),
				'description' => __( 'Paste any YouTube video URL', 'webtero' ),
				'default'     => '',
			],
		];
	}

	/**
	 * Get placeholder data for preview when block is empty
	 *
	 * @return array Placeholder data
	 */
	protected function get_placeholder_data(): array {
		return [
			'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
			'is_preview'  => true,
		];
	}

	/**
	 * Extract YouTube video ID from various URL formats
	 *
	 * @param string $url YouTube URL
	 * @return string|null Video ID or null if not found
	 */
	public static function extract_video_id( string $url ): ?string {
		if ( empty( $url ) ) {
			return null;
		}

		// Patterns to match various YouTube URL formats
		$patterns = [
			// youtube.com/watch?v=VIDEO_ID
			'#(?:youtube\.com/watch\?v=|youtube\.com/watch\?.*&v=)([^&\s]+)#i',
			// youtube.com/watch/VIDEO_ID
			'#youtube\.com/watch/([^/\s?]+)#i',
			// youtu.be/VIDEO_ID
			'#youtu\.be/([^/\s?]+)#i',
			// youtube.com/v/VIDEO_ID
			'#youtube\.com/v/([^/\s?]+)#i',
			// youtube.com/embed/VIDEO_ID
			'#youtube\.com/embed/([^/\s?]+)#i',
			// youtube.com/e/VIDEO_ID
			'#youtube\.com/e/([^/\s?]+)#i',
			// youtube.com/shorts/VIDEO_ID
			'#youtube\.com/shorts/([^/\s?]+)#i',
			// youtube.com/live/VIDEO_ID
			'#youtube\.com/live/([^/\s?]+)#i',
			// youtube.com/oembed?url=...
			'#youtube\.com/oembed\?url=.*[?&]v=([^&\s]+)#i',
			// youtube.com/attribution_link?...u=/watch?v=VIDEO_ID
			'#youtube\.com/attribution_link\?.*u=.*[?&]v=([^&\s]+)#i',
		];

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $url, $matches ) ) {
				// Clean up video ID - remove any query parameters
				$video_id = $matches[1];
				$video_id = preg_replace( '/[?&].*$/', '', $video_id );
				return $video_id;
			}
		}

		return null;
	}
}
