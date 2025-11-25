<?php
/**
 * Text Content Block Settings
 *
 * Defines block fields and data preparation logic
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT\Blocks;

defined( 'ABSPATH' ) || exit;

class Text_Content_Block extends Custom_Block {

    /**
     * Get block fields (shown in edit mode in Gutenberg editor)
     * These are block-specific fields unique to this block
     *
     * @return array Field configuration
     */
    public function get_fields(): array {
        return [
            [
                'type'  => 'text',
                'id'    => 'title',
                'label' => __( 'Title', 'webtero' ),
                'default' => '',
                'placeholder' => __( 'Enter title...', 'webtero' ),
                'help' => __( 'Optional title for this content block', 'webtero' ),
            ],
            [
                'type'  => 'tiptap',
                'id'    => 'content',
                'label' => __( 'Content', 'webtero' ),
                'default' => '',
                'placeholder' => __( 'Enter your content here...', 'webtero' ),
                'help' => __( 'Main content with rich text editing', 'webtero' ),
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
            'title'   => __( 'Sample Title', 'webtero' ),
            'content' => '<p>' . __( 'This is sample content. Click "Edit" in the toolbar above to add your own content.', 'webtero' ) . '</p><p>' . __( 'You can use the rich text editor to format your text, add links, and more.', 'webtero' ) . '</p>',
        ];
    }
}
