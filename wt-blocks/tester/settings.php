<?php
/**
 * Tester Block Settings
 *
 * Comprehensive test block containing EVERY field type for testing purposes
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT\Blocks;

defined( 'ABSPATH' ) || exit;

class Tester_Block extends Custom_Block {

	/**
	 * Get block fields (shown in edit mode in Gutenberg editor)
	 * Contains EVERY field type for comprehensive testing
	 *
	 * @return array Field configuration
	 */
	public function get_fields(): array {
		return [
			// ============================================
			// TEXT INPUT FIELDS
			// ============================================
			[
				'type'        => 'text',
				'id'          => 'text_field',
				'label'       => __( 'Text Field', 'webtero' ),
				'default'     => '',
				'placeholder' => __( 'Enter text...', 'webtero' ),
				'description' => __( 'Basic text input field', 'webtero' ),
				'width'       => 50, // 50% width
			],

			// ============================================
			// NUMBER FIELD
			// ============================================
			[
				'type'        => 'number',
				'id'          => 'number_field',
				'label'       => __( 'Number Field', 'webtero' ),
				'default'     => 0,
				'min'         => 0,
				'max'         => 100,
				'step'        => 1,
				'description' => __( 'Number input with min/max', 'webtero' ),
				'width'       => 25, // 25% width
			],

			// ============================================
			// RANGE FIELD
			// ============================================
			[
				'type'        => 'range',
				'id'          => 'range_field',
				'label'       => __( 'Range Field', 'webtero' ),
				'default'     => 50,
				'min'         => 0,
				'max'         => 100,
				'step'        => 5,
				'suffix'      => 'px',
				'description' => __( 'Range slider with number input and suffix', 'webtero' ),
				'width'       => 25, // 25% width
			],

			// ============================================
			// TEXTAREA FIELD
			// ============================================
			[
				'type'        => 'textarea',
				'id'          => 'textarea_field',
				'label'       => __( 'Textarea Field', 'webtero' ),
				'default'     => '',
				'rows'        => 5,
				'placeholder' => __( 'Enter multiline text...', 'webtero' ),
				'description' => __( 'Multiline text input', 'webtero' ),
			],

			// ============================================
			// RADIO FIELD
			// ============================================
			[
				'type'        => 'radio',
				'id'          => 'radio_field',
				'label'       => __( 'Radio Field', 'webtero' ),
				'default'     => 'option1',
				'options'     => [
					'option1' => __( 'Option 1', 'webtero' ),
					'option2' => __( 'Option 2', 'webtero' ),
					'option3' => __( 'Option 3', 'webtero' ),
				],
				'description' => __( 'Radio button selection', 'webtero' ),
				'width'       => 33, // 33% width
			],

			// ============================================
			// CHECKBOX FIELD
			// ============================================
			[
				'type'           => 'checkbox',
				'id'             => 'checkbox_field',
				'label'          => __( 'Checkbox Field', 'webtero' ),
				'checkbox_label' => __( 'Enable this option', 'webtero' ),
				'default'        => false,
				'description'    => __( 'Single checkbox field', 'webtero' ),
				'width'          => 33, // 33% width
			],

			// ============================================
			// TOGGLE FIELD
			// ============================================
			[
				'type'        => 'toggle',
				'id'          => 'toggle_field',
				'label'       => __( 'Toggle Field', 'webtero' ),
				'label_on'    => __( 'Enabled', 'webtero' ),
				'label_off'   => __( 'Disabled', 'webtero' ),
				'default'     => false,
				'description' => __( 'Toggle switch field', 'webtero' ),
				'width'       => 33, // 33% width
			],

			// ============================================
			// BUTTON GROUP FIELD
			// ============================================
			[
				'type'        => 'button_group',
				'id'          => 'button_group_field',
				'label'       => __( 'Button Group Field', 'webtero' ),
				'default'     => 'left',
				'multiple'    => false,
				'options'     => [
					'left'   => __( 'Left', 'webtero' ),
					'center' => __( 'Center', 'webtero' ),
					'right'  => __( 'Right', 'webtero' ),
				],
				'description' => __( 'Button group selection', 'webtero' ),
			],

			// ============================================
			// BUTTON GROUP MULTIPLE
			// ============================================
			[
				'type'        => 'button_group',
				'id'          => 'button_group_multiple',
				'label'       => __( 'Button Group (Multiple)', 'webtero' ),
				'default'     => [ 'feature1' ],
				'multiple'    => true,
				'options'     => [
					'feature1' => __( 'Feature 1', 'webtero' ),
					'feature2' => __( 'Feature 2', 'webtero' ),
					'feature3' => __( 'Feature 3', 'webtero' ),
				],
				'description' => __( 'Multiple selection button group', 'webtero' ),
			],

			// ============================================
			// COLOR FIELD
			// ============================================
			[
				'type'        => 'color',
				'id'          => 'color_field',
				'label'       => __( 'Color Field', 'webtero' ),
				'default'     => '#3498db',
				'description' => __( 'Color picker field', 'webtero' ),
			],

			// ============================================
			// SELECT FIELD
			// ============================================
			[
				'type'        => 'select',
				'id'          => 'select_field',
				'label'       => __( 'Select Field', 'webtero' ),
				'default'     => 'value1',
				'options'     => [
					'value1' => __( 'Value 1', 'webtero' ),
					'value2' => __( 'Value 2', 'webtero' ),
					'value3' => __( 'Value 3', 'webtero' ),
					'value4' => __( 'Value 4', 'webtero' ),
				],
				'description' => __( 'Dropdown select field', 'webtero' ),
			],

			// ============================================
			// ENHANCED SELECT FIELD
			// ============================================
			[
				'type'        => 'enhanced_select',
				'id'          => 'enhanced_select_field',
				'label'       => __( 'Enhanced Select Field', 'webtero' ),
				'default'     => '',
				'searchable'  => true,
				'placeholder' => __( 'Search and select...', 'webtero' ),
				'options'     => [
					'alpha'   => __( 'Alpha', 'webtero' ),
					'beta'    => __( 'Beta', 'webtero' ),
					'gamma'   => __( 'Gamma', 'webtero' ),
					'delta'   => __( 'Delta', 'webtero' ),
					'epsilon' => __( 'Epsilon', 'webtero' ),
				],
				'description' => __( 'Enhanced select with search', 'webtero' ),
			],

			// ============================================
			// ENHANCED SELECT MULTIPLE
			// ============================================
			[
				'type'        => 'enhanced_select',
				'id'          => 'enhanced_select_multiple',
				'label'       => __( 'Enhanced Select (Multiple)', 'webtero' ),
				'default'     => [],
				'multiple'    => true,
				'searchable'  => true,
				'placeholder' => __( 'Select multiple...', 'webtero' ),
				'options'     => [
					'tag1' => __( 'Tag 1', 'webtero' ),
					'tag2' => __( 'Tag 2', 'webtero' ),
					'tag3' => __( 'Tag 3', 'webtero' ),
					'tag4' => __( 'Tag 4', 'webtero' ),
				],
				'description' => __( 'Multiple selection with search', 'webtero' ),
			],

			// ============================================
			// MEDIA FIELD
			// ============================================
			[
				'type'        => 'media',
				'id'          => 'media_field',
				'label'       => __( 'Media Field', 'webtero' ),
				'default'     => '',
				'description' => __( 'Image/media upload field', 'webtero' ),
			],

			// ============================================
			// TIPTAP EDITOR FIELD
			// ============================================
			[
				'type'        => 'tiptap',
				'id'          => 'tiptap_field',
				'label'       => __( 'TipTap Editor Field', 'webtero' ),
				'default'     => '',
				'placeholder' => __( 'Enter rich text content...', 'webtero' ),
				'description' => __( 'Rich text editor with formatting', 'webtero' ),
			],

			// ============================================
			// REPEATER FIELD (with all field types)
			// ============================================
			[
				'type'        => 'repeater',
				'id'          => 'repeater_field',
				'label'       => __( 'Repeater Field (All Types)', 'webtero' ),
				'default'     => [],
				'min'         => 0,
				'max'         => 10,
				'fields'      => [
					[
						'type'        => 'text',
						'id'          => 'rep_text',
						'label'       => __( 'Text', 'webtero' ),
						'default'     => '',
					],
					[
						'type'        => 'number',
						'id'          => 'rep_number',
						'label'       => __( 'Number', 'webtero' ),
						'default'     => 0,
					],
					[
						'type'        => 'textarea',
						'id'          => 'rep_textarea',
						'label'       => __( 'Textarea', 'webtero' ),
						'default'     => '',
					],
					[
						'type'        => 'color',
						'id'          => 'rep_color',
						'label'       => __( 'Color', 'webtero' ),
						'default'     => '#000000',
					],
					[
						'type'        => 'select',
						'id'          => 'rep_select',
						'label'       => __( 'Select', 'webtero' ),
						'default'     => 'opt1',
						'options'     => [
							'opt1' => __( 'Option 1', 'webtero' ),
							'opt2' => __( 'Option 2', 'webtero' ),
							'opt3' => __( 'Option 3', 'webtero' ),
						],
					],
					[
						'type'        => 'media',
						'id'          => 'rep_media',
						'label'       => __( 'Media', 'webtero' ),
						'default'     => '',
					],
					[
						'type'        => 'tiptap',
						'id'          => 'rep_tiptap',
						'label'       => __( 'TipTap', 'webtero' ),
						'default'     => '',
					],
				],
				'description' => __( 'Repeatable rows with multiple field types', 'webtero' ),
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
			'text_field'            => __( 'Sample text', 'webtero' ),
			'number_field'          => 42,
			'range_field'           => 50,
			'textarea_field'        => __( "Sample multiline text\nSecond line\nThird line", 'webtero' ),
			'radio_field'           => 'option2',
			'checkbox_field'        => true,
			'toggle_field'          => true,
			'button_group_field'    => 'center',
			'button_group_multiple' => [ 'feature1', 'feature2' ],
			'color_field'           => '#e74c3c',
			'select_field'          => 'value2',
			'enhanced_select_field' => 'gamma',
			'enhanced_select_multiple' => [ 'tag1', 'tag3' ],
			'tiptap_field'          => '<p>' . __( 'Sample <strong>rich text</strong> with <em>formatting</em>.', 'webtero' ) . '</p>',
			'repeater_field'        => [
				[
					'rep_text'     => __( 'Item 1 Text', 'webtero' ),
					'rep_number'   => 10,
					'rep_textarea' => __( 'Item 1 Description', 'webtero' ),
					'rep_color'    => '#3498db',
					'rep_select'   => 'opt1',
					'rep_tiptap'   => '<p>' . __( 'Item 1 content', 'webtero' ) . '</p>',
				],
				[
					'rep_text'     => __( 'Item 2 Text', 'webtero' ),
					'rep_number'   => 20,
					'rep_textarea' => __( 'Item 2 Description', 'webtero' ),
					'rep_color'    => '#e74c3c',
					'rep_select'   => 'opt2',
					'rep_tiptap'   => '<p>' . __( 'Item 2 content', 'webtero' ) . '</p>',
				],
			]
		];
	}
}
