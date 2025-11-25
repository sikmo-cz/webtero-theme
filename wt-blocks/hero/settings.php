<?php
/**
 * Hero block settings
 *
 * Hero section with text content and background image
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT\Blocks;

defined( 'ABSPATH' ) || exit;

class Hero_Block extends Custom_Block {

	/**
	 * Get block fields
	 *
	 * @return array Field configuration
	 */
	public function get_fields(): array {
		return [
			[
				'type'        => 'tiptap',
				'id'          => 'text_content',
				'label'       => __( 'Text Content', 'webtero' ),
				'description' => __( 'Main hero text content', 'webtero' ),
				'default'     => '',
				'width'       => 66,
			],
			[
				'type'        => 'media',
				'id'          => 'background_image',
				'label'       => __( 'Background Image', 'webtero' ),
				'description' => __( 'Hero background image', 'webtero' ),
				'default'     => '',
				'width'       => 33,
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
			'text_content'     => '<h1>Welcome to Our Site</h1><p>This is a hero section with compelling content that engages your visitors.</p>',
			'background_image' => '',
			'is_preview'       => true,
		];
	}
}
