<?php
/**
 * Video block settings
 *
 * Video player with MP4 file and poster image
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT\Blocks;

defined( 'ABSPATH' ) || exit;

class Video_Block extends Custom_Block {

	/**
	 * Get block fields
	 *
	 * @return array Field configuration
	 */
	public function get_fields(): array {
		return [
			[
				'type'          => 'file',
				'id'            => 'video_file',
				'label'         => __( 'Video File', 'webtero' ),
				'description'   => __( 'Select MP4 video file', 'webtero' ),
				'default'       => '',
				'allowed_types' => [ 'video/mp4', 'video' ],
				'width'       => 50,
			],
			[
				'type'        => 'media',
				'id'          => 'poster_image',
				'label'       => __( 'Poster Image', 'webtero' ),
				'description' => __( 'Thumbnail image shown before video plays', 'webtero' ),
				'default'     => '',
				'width'       => 50,
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
			'video_file'   => '',
			'poster_image' => '',
			'is_preview'   => true,
		];
	}
}
