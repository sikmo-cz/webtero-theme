<?php
/**
 * Spacer block settings
 *
 * Flexible spacing block with preset and custom heights
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT\Blocks;

defined( 'ABSPATH' ) || exit;

class Spacer_Block extends Custom_Block {

	/**
	 * Get block fields
	 *
	 * @return array Field configuration
	 */
	public function get_fields(): array {
		return [
			[
				'type'        => 'radio',
				'id'          => 'height',
				'label'       => __( 'Height', 'webtero' ),
				'description' => __( 'Select spacing height', 'webtero' ),
				'default'     => 'default',
				'options'     => [
					'none'    => __( 'None (0px)', 'webtero' ),
					'small'   => __( 'Small (20px)', 'webtero' ),
					'default' => __( 'Default (40px)', 'webtero' ),
					'big'     => __( 'Big (60px)', 'webtero' ),
					'large'   => __( 'Large (80px)', 'webtero' ),
					'custom'  => __( 'Custom', 'webtero' ),
				],
			],
			[
				'type'        => 'range',
				'id'          => 'custom_desktop',
				'label'       => __( 'Custom Desktop Height', 'webtero' ),
				'description' => __( 'Custom height in pixels for desktop (only when "Custom" is selected)', 'webtero' ),
				'default'     => 40,
				'min'         => 0,
				'max'         => 200,
				'step'        => 5,
			],
			[
				'type'        => 'range',
				'id'          => 'custom_mobile',
				'label'       => __( 'Custom Mobile Height', 'webtero' ),
				'description' => __( 'Custom height in pixels for mobile (only when "Custom" is selected)', 'webtero' ),
				'default'     => 20,
				'min'         => 0,
				'max'         => 200,
				'step'        => 5,
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
			'height'         => 'default',
			'custom_desktop' => 40,
			'custom_mobile'  => 20,
			'is_preview'     => true,
		];
	}
}
