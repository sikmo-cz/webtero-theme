<?php
/**
 * FAQ block settings
 *
 * Frequently Asked Questions with repeatable items
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT\Blocks;

defined( 'ABSPATH' ) || exit;

class Faq_Block extends Custom_Block {

	/**
	 * Get block fields
	 *
	 * @return array Field configuration
	 */
	public function get_fields(): array {
		return [
			[
				'type'        => 'repeater',
				'id'          => 'faq_items',
				'label'       => __( 'FAQ Items', 'webtero' ),
				'description' => __( 'Add frequently asked questions', 'webtero' ),
				'default'     => [],
				'min'         => 0,
				'max'         => 50,
				'fields'      => [
					[
						'type'        => 'text',
						'id'          => 'heading',
						'label'       => __( 'Question', 'webtero' ),
						'description' => __( 'FAQ question/heading', 'webtero' ),
						'default'     => '',
					],
					[
						'type'        => 'tiptap',
						'id'          => 'text_content',
						'label'       => __( 'Answer', 'webtero' ),
						'description' => __( 'FAQ answer content', 'webtero' ),
						'default'     => '',
					],
				],
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
			'faq_items' => [
				[
					'heading'      => 'What is your return policy?',
					'text_content' => '<p>We offer a 30-day return policy on all items. Items must be in original condition with tags attached.</p>',
				],
				[
					'heading'      => 'How long does shipping take?',
					'text_content' => '<p>Standard shipping takes 5-7 business days. Express shipping is available for 2-3 business days.</p>',
				],
				[
					'heading'      => 'Do you ship internationally?',
					'text_content' => '<p>Yes, we ship to most countries worldwide. International shipping times vary by destination.</p>',
				],
			],
			'is_preview' => true,
		];
	}
}
