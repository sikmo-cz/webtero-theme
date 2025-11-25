<?php
/**
 * Custom Block Base Class
 *
 * Abstract base class that all custom Webtero blocks extend.
 * Provides common functionality for field configuration, rendering, and data handling.
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT\Blocks;

defined( 'ABSPATH' ) || exit;

abstract class Custom_Block {

    /**
     * Get block field configuration
     * Returns fields shown in edit mode (block-specific fields)
     *
     * @return array Field configuration array
     */
    public function get_fields(): array {
        return [];
    }

    /**
     * Get block slug from class name
     * E.g., Text_Content_Block => text-content
     *
     * @return string Block slug
     */
    protected function get_block_slug(): string {
        $class_name = get_class( $this );
        $class_name = substr( $class_name, strrpos( $class_name, '\\' ) + 1 );
        $class_name = str_replace( '_Block', '', $class_name );
        $slug = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $class_name ) );
        return str_replace( '_', '-', $slug );
    }

    /**
     * Get path to block directory
     *
     * @return string Block directory path
     */
    protected function get_block_path(): string {
        $block_slug = $this->get_block_slug();

        // Check child theme first
        if ( is_child_theme() ) {
            $child_path = get_stylesheet_directory() . '/wt-blocks/' . $block_slug;
            if ( is_dir( $child_path ) ) {
                return $child_path;
            }
        }

        // Fall back to parent theme
        return get_template_directory() . '/wt-blocks/' . $block_slug;
    }

    /**
     * Prepare block data for rendering
     * Override this method to transform attributes before rendering
     *
     * @param array $attributes Block attributes
     * @return array Prepared data for template
     */
    protected function prepare_render_data( array $attributes ): array {
        return $attributes;
    }

    /**
     * Check if we're in editor preview mode
     *
     * @return bool True if in editor preview
     */
    protected function is_editor_preview(): bool {
        // Check if this is a REST API request for block rendering
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            // Check if it's the block renderer endpoint
            $route = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
            if ( strpos( $route, '/wp/v2/block-renderer/' ) !== false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get placeholder/dummy data for preview when block is empty
     * Override this method in child classes to provide sample data
     *
     * @return array Placeholder data
     */
    protected function get_placeholder_data(): array {
        return [];
    }

    /**
     * Check if block attributes are empty (excluding webteroOptions)
     *
     * @param array $attributes Block attributes
     * @return bool True if block has no content
     */
    protected function is_block_empty( array $attributes ): bool {
        foreach ( $attributes as $key => $value ) {
            // Skip webteroOptions and internal WordPress attributes
            if ( in_array( $key, [ 'webteroOptions', 'webteroOptionsRaw', 'className', 'anchor' ], true ) ) {
                continue;
            }

            // Check if value is not empty
            if ( ! empty( $value ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Universal render method
     * Loads render.php from block directory with prepared data
     *
     * @param array $attributes Block attributes
     * @return string Rendered HTML
     */
    public function render( array $attributes ): string {
        $block_slug = $this->get_block_slug();

        // Decode webteroOptions JSON into separate array
        if ( ! empty( $attributes['webteroOptions'] ) ) {
            $webtero_options = json_decode( $attributes['webteroOptions'], true );
            if ( is_array( $webtero_options ) ) {
                // Keep raw JSON as webteroOptionsRaw
                $attributes['webteroOptionsRaw'] = $attributes['webteroOptions'];
                // Replace webteroOptions with decoded array
                $attributes['webteroOptions'] = $webtero_options;
            }
        } else {
            // No options set, use empty array
            $attributes['webteroOptions'] = [];
        }

        // Check if we need to use placeholder data (preview mode + empty block)
        $is_preview = $this->is_editor_preview();
        if ( $is_preview && $this->is_block_empty( $attributes ) ) {
            $placeholder_data = $this->get_placeholder_data();
            if ( ! empty( $placeholder_data ) ) {
                // Merge placeholder data (don't override existing values)
                $attributes = array_merge( $placeholder_data, $attributes );
            }
        }

        // Apply before_render hook
        $attributes = apply_filters( "webtero/blocks/{$block_slug}/before_render", $attributes, $this );

        // Prepare data for template and make globally accessible
        global $block;
        $block = $this->prepare_render_data( $attributes );

        // Add preview flag to block data
        $block['is_preview'] = $is_preview;

        // Apply data manipulation hook
        $block = apply_filters( "webtero/blocks/{$block_slug}/render_data", $block, $attributes, $this );

        // Get path to render.php
        $render_file = $this->get_block_path() . '/render.php';

        // Check if render.php exists
        if ( ! file_exists( $render_file ) ) {
            return '<!-- Block render template not found: ' . esc_html( $render_file ) . ' -->';
        }

        // Render template with data
        ob_start();
        include $render_file;
        $html = ob_get_clean();

        // Apply after_render hook
        $html = apply_filters( "webtero/blocks/{$block_slug}/after_render", $html, $attributes, $this );

        return $html;
    }

    /**
     * Get block supports
     *
     * @return array Block supports configuration
     */
    public function get_supports(): array {
        return [
            'anchor'    => true,
            'className' => true,
        ];
    }

    /**
     * Get attachment URL by ID
     *
     * Helper method for future image/media blocks
     *
     * @param int $attachment_id Attachment ID
     * @param string $size Image size
     * @return string Attachment URL or empty string
     */
    protected function get_attachment_url( int $attachment_id, string $size = 'full' ): string {
        if ( empty( $attachment_id ) ) {
            return '';
        }

        $url = wp_get_attachment_image_url( $attachment_id, $size );

        return $url ? $url : '';
    }

    /**
     * Get attachment image tag
     *
     * Helper method for future image/media blocks
     *
     * @param int $attachment_id Attachment ID
     * @param string $size Image size
     * @param array $attr Additional attributes
     * @return string Image tag or empty string
     */
    protected function get_attachment_image( int $attachment_id, string $size = 'full', array $attr = [] ): string {
        if ( empty( $attachment_id ) ) {
            return '';
        }

        return wp_get_attachment_image( $attachment_id, $size, false, $attr );
    }
}
