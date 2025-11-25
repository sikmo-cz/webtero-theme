<?php
/**
 * Two Columns block settings
 *
 * Two column layout with text content
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT\Blocks;

defined( 'ABSPATH' ) || exit;

class Two_Columns_Block extends Custom_Block {

	/**
	 * Get block fields
	 *
	 * @return array Field configuration
	 */
	public function get_fields(): array {
		return [
			[
				'type'        => 'tiptap',
				'id'          => 'left_content',
				'label'       => __( 'Left Column', 'webtero' ),
				'description' => __( 'Content for the left column', 'webtero' ),
				'default'     => '',
				'width'       => 50,
			],
			[
				'type'        => 'tiptap',
				'id'          => 'right_content',
				'label'       => __( 'Right Column', 'webtero' ),
				'description' => __( 'Content for the right column', 'webtero' ),
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
			'left_content'  => '<h3>Left Column</h3><p>This is the content for the left column. Add your text here.</p>',
			'right_content' => '<h3>Right Column</h3><p>This is the content for the right column. Add your text here.</p>',
			'is_preview'    => true,
		];
	}
}
