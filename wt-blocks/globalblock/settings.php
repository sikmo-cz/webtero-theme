<?php
/**
 * Global Block settings
 *
 * Renders content from a selected Global Block post
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT\Blocks;

defined( 'ABSPATH' ) || exit;

class Globalblock_Block extends Custom_Block {

	/**
	 * Recursion depth tracker (static to persist across instances)
	 *
	 * @var int
	 */
	private static $recursion_depth = 0;

	/**
	 * Maximum recursion depth
	 */
	private const MAX_RECURSION_DEPTH = 1;

	/**
	 * Get block fields
	 *
	 * @return array Field configuration
	 */
	public function get_fields(): array {
		return [
			[
				'type'        => 'post_object',
				'id'          => 'global_block_id',
				'label'       => __( 'Select Global Block', 'webtero' ),
				'description' => __( 'Choose a global block to display', 'webtero' ),
				'default'     => '',
				'post_types'  => 'global_blocks',
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
			'global_block_id' => '',
			'is_preview'      => true,
		];
	}

	/**
	 * Prepare block data for rendering
	 *
	 * @param array $attributes Block attributes
	 * @return array Prepared data
	 */
	protected function prepare_render_data( array $attributes ): array {
		$data = parent::prepare_render_data( $attributes );

		// Add recursion depth to data
		$data['recursion_depth'] = self::$recursion_depth;

		return $data;
	}

	/**
	 * Override render to handle recursion
	 *
	 * @param array $attributes Block attributes
	 * @return string Rendered output
	 */
	public function render( array $attributes ): string {
		// Increment recursion depth
		self::$recursion_depth++;

		// Check recursion limit
		if ( self::$recursion_depth > self::MAX_RECURSION_DEPTH ) {
			self::$recursion_depth--;
			return '<div class="webtero-global-block-error"><p>' .
				esc_html__( 'Global Block: Maximum recursion depth reached. Cannot nest global blocks more than 1 level deep.', 'webtero' ) .
				'</p></div>';
		}

		// Render normally
		$output = parent::render( $attributes );

		// Decrement recursion depth
		self::$recursion_depth--;

		return $output;
	}
}
