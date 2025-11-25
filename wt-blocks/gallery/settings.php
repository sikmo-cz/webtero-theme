<?php
/**
 * Gallery block settings
 *
 * Block with gallery field for multiple image selection and reordering
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT\Blocks;

defined( 'ABSPATH' ) || exit;

class Gallery_Block extends Custom_Block {

	/**
	 * Get block fields
	 *
	 * @return array Field configuration
	 */
	public function get_fields(): array {
		return [
			[
				'type'        => 'gallery',
				'id'          => 'gallery_images',
				'label'       => __( 'Gallery Images', 'webtero' ),
				'description' => __( 'Select multiple images and reorder them', 'webtero' ),
				'default'     => [],
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
			'gallery_images' => [],
		];
	}
}
