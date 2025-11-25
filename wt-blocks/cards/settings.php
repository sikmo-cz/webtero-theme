<?php
/**
 * Cards block settings
 *
 * Comprehensive test block containing EVERY field type for testing purposes
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT\Blocks;

defined( 'ABSPATH' ) || exit;

class Cards_Block extends Custom_Block {

	/**
	 * Contains tiptap field
	 *
	 * @return array Field configuration
	 */
	public function get_fields(): array {
		return [
			[
				'type'        => 'repeater',
				'id'          => 'cards_items',
				'label'       => __( 'Cards', 'webtero' ),
				'description' => __( 'Repeatable rows with text content', 'webtero' ),
				'default'     => [],
				'min'         => 0,
				'max'         => 10,
				'fields'      => [
					[
						'type'        => 'tiptap',
						'id'          => 'text_content',
						'label'       => __( 'Text content', 'webtero' ),
						'default'     => '',
					],
				],
			]
		];
	}

	/**
	 * Get placeholder data for preview when block is empty
	 *
	 * @return array Placeholder data
	 */
	protected function get_placeholder_data(): array {
		return [
			'cards_items' => [
				[
					'text_content' => '<p>' . __( 'Item 2 content', 'webtero' ) . '</p>',
				],
			]
		];
	}
}
