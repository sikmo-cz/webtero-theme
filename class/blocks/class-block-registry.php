<?php
/**
 * Block Registry
 *
 * Auto-discovers and registers custom Webtero blocks from parent and child themes.
 * Handles block asset loading and category registration.
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT\Blocks;

defined( 'ABSPATH' ) || exit;

class Block_Registry {

    /**
     * Registered block instances
     *
     * @var array<string, Custom_Block>
     */
    private array $blocks = [];

    /**
     * Block category slug
     */
    private const CATEGORY_SLUG = 'webtero-blocks';

    /**
     * Constructor
     */
    public function __construct() {
        // Register block category
        add_filter( 'block_categories_all', [ $this, 'register_block_category' ], 10, 2 );

        // Discover and register blocks
        add_action( 'init', [ $this, 'discover_and_register_blocks' ] );

        // Disable core blocks (keep only essential ones)
        add_filter( 'allowed_block_types_all', [ $this, 'filter_allowed_blocks' ], 10, 2 );

        // Enqueue block assets
        add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
    }

    /**
     * Register custom block category
     *
     * @param array $categories Existing categories
     * @param \WP_Block_Editor_Context $context Editor context
     * @return array Modified categories
     */
    public function register_block_category( array $categories, $context ): array {
        return array_merge(
            [
                [
                    'slug'  => self::CATEGORY_SLUG,
                    'title' => __( 'Webtero Blocks', 'webtero' ),
                    'icon'  => 'layout',
                ],
            ],
            $categories
        );
    }

    /**
     * Discover blocks from parent and child themes
     *
     * @return array<string, string> Block name => Block class mapping
     */
    private function discover_blocks(): array {
        $blocks = [];

        // Parent theme blocks
        $parent_blocks_dir = get_template_directory() . '/wt-blocks';
        if ( is_dir( $parent_blocks_dir ) ) {
            $blocks = array_merge( $blocks, $this->scan_blocks_directory( $parent_blocks_dir, 'parent' ) );
        }

        // Child theme blocks
        if ( is_child_theme() ) {
            $child_blocks_dir = get_stylesheet_directory() . '/wt-blocks';
            if ( is_dir( $child_blocks_dir ) ) {
                $child_blocks = $this->scan_blocks_directory( $child_blocks_dir, 'child' );

                // Do NOT override - skip if already registered
                foreach ( $child_blocks as $block_name => $block_data ) {
                    if ( ! isset( $blocks[ $block_name ] ) ) {
                        $blocks[ $block_name ] = $block_data;
                    }
                }
            }
        }

        return $blocks;
    }

    /**
     * Scan directory for blocks
     *
     * @param string $directory Directory path to scan
     * @param string $theme_type 'parent' or 'child'
     * @return array<string, array> Block name => Block data mapping
     */
    private function scan_blocks_directory( string $directory, string $theme_type ): array {
        $blocks = [];

        if ( ! is_dir( $directory ) ) {
            return $blocks;
        }

        $items = scandir( $directory );

        foreach ( $items as $item ) {
            if ( $item === '.' || $item === '..' ) {
                continue;
            }

            $block_path = $directory . '/' . $item;

            if ( ! is_dir( $block_path ) ) {
                continue;
            }

            // Look for settings.php (new structure) or legacy class file
            $settings_file = $block_path . '/settings.php';

            // Determine which file to use
            if ( file_exists( $settings_file ) ) {
                $class_file = $settings_file;
            } else {
                // No valid block file found
                continue;
            }

            // Block name: wt/block-slug
            $block_name = 'wt/' . $item;

            // Expected class name: WT\Blocks\{Capitalized}_Block
            $class_parts = explode( '-', $item );
            $class_parts = array_map( 'ucfirst', $class_parts );
            $class_name = 'WT\\Blocks\\' . implode( '_', $class_parts ) . '_Block';

            $blocks[ $block_name ] = [
                'class_name' => $class_name,
                'class_file' => $class_file,
                'block_path' => $block_path,
                'theme_type' => $theme_type,
            ];
        }

        return $blocks;
    }

    /**
     * Discover and register all blocks
     */
    public function discover_and_register_blocks(): void {
        $discovered_blocks = $this->discover_blocks();

        // Allow plugins to register additional blocks
        $discovered_blocks = apply_filters( 'webtero_register_blocks', $discovered_blocks );

        foreach ( $discovered_blocks as $block_name => $block_data ) {
            $this->register_block( $block_name, $block_data );
        }
    }

    /**
     * Register a single block
     *
     * @param string $block_name Block name (e.g., 'wt/hero')
     * @param array $block_data Block data from discovery
     */
    private function register_block( string $block_name, array $block_data ): void {
        // Skip if already registered
        if ( isset( $this->blocks[ $block_name ] ) ) {
            return;
        }

        // Load block class file
        require_once $block_data['class_file'];

        // Check if class exists
        if ( ! class_exists( $block_data['class_name'] ) ) {
            return;
        }

        // Instantiate block
        $block_instance = new $block_data['class_name']();

        // Store instance
        $this->blocks[ $block_name ] = $block_instance;

        // Build attributes from PHP field definitions
        $attributes = [
            'webteroOptions' => [
                'type'    => 'string',
                'default' => '',
            ],
        ];

        // Get fields from block instance and convert to attributes
        $fields = $block_instance->get_fields();
        foreach ( $fields as $field ) {
            $field_id = $field['id'] ?? '';
            if ( empty( $field_id ) ) {
                continue;
            }

            // Determine attribute type based on field type
            $attr_type = 'string'; // Default
            if ( isset( $field['type'] ) ) {
                switch ( $field['type'] ) {
                    case 'number':
                        $attr_type = 'number';
                        break;
                    case 'checkbox':
                    case 'toggle':
                        $attr_type = 'boolean';
                        break;
                    case 'repeater':
                    case 'gallery':
                        $attr_type = 'array';
                        break;
                }
            }

            $attributes[ $field_id ] = [
                'type'    => $attr_type,
                'default' => $field['default'] ?? ( $attr_type === 'array' ? [] : '' ),
            ];
        }

        // Check if block.json exists
        $block_json_path = $block_data['block_path'] . '/block.json';

        if ( file_exists( $block_json_path ) ) {
            // Register using block.json - let block.json define attributes
            // Don't override attributes here, as it breaks saved values from loading
            register_block_type( $block_json_path, [
                'render_callback' => [ $block_instance, 'render' ],
            ] );
        } else {
            // Fallback: Register with manual attributes (only if no block.json)
            register_block_type( $block_name, [
                'attributes'      => $attributes,
                'render_callback' => [ $block_instance, 'render' ],
            ] );
        }
    }

    /**
     * Filter allowed blocks
     * Keep only Webtero blocks and essential core blocks
     *
     * @param bool|array $allowed_blocks Allowed blocks
     * @param \WP_Block_Editor_Context $context Editor context
     * @return array Filtered allowed blocks
     */
    public function filter_allowed_blocks( $allowed_blocks, $context ): array {
        // Get all registered Webtero blocks
        $wt_blocks = array_keys( $this->blocks );

        // Essential core blocks to keep (but hide paragraph from inserter via JS)
        $core_blocks = [
            'core/paragraph',      // Keep but will hide from inserter
            'core/block',          // Reusable blocks
        ];

        return array_merge( $wt_blocks, $core_blocks );
    }

    /**
     * Enqueue frontend block assets
     */
    public function enqueue_block_assets(): void {
        foreach ( $this->blocks as $block_name => $block_instance ) {
            $block_slug = str_replace( 'wt/', '', $block_name );
            $block_path = $this->get_block_path( $block_slug );
            $block_url = $this->get_block_url( $block_slug );

            // Enqueue block stylesheet
            $style_path = $block_path . '/style.css';
            if ( file_exists( $style_path ) ) {
                wp_enqueue_style(
                    "block-{$block_slug}",
                    $block_url . '/style.css',
                    [],
                    filemtime( $style_path )
                );
            }

            // Enqueue block script
            $script_path = $block_path . '/script.js';
            if ( file_exists( $script_path ) ) {
                wp_enqueue_script(
                    "block-{$block_slug}",
                    $block_url . '/script.js',
                    [],
                    filemtime( $script_path ),
                    true
                );
            }
        }
    }

    /**
     * Enqueue editor-only block assets
     * Uses universal editor instead of block-specific scripts
     */
    public function enqueue_block_editor_assets(): void {
        // Enqueue universal block editor (works for ALL blocks)
        wp_enqueue_script(
            'webtero-universal-block-editor',
            get_template_directory_uri() . '/admin/src/js/blocks/universal-block-editor.js',
            [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-api-fetch' ],
            THEME_VERSION,
            true
        );

        // Prepare blocks data for JavaScript
        $blocks_data = [];
        foreach ( $this->blocks as $block_name => $block_instance ) {
            $block_slug = str_replace( 'wt/', '', $block_name );

            // Get block.json data if it exists
            $block_json_path = $this->get_block_path( $block_slug ) . '/block.json';
            if ( file_exists( $block_json_path ) ) {
                $block_json = json_decode( file_get_contents( $block_json_path ), true );
                $blocks_data[] = [
                    'name'        => $block_name,
                    'title'       => $block_json['title'] ?? ucfirst( $block_slug ),
                    'icon'        => $block_json['icon'] ?? 'admin-page',
                    'category'    => $block_json['category'] ?? 'webtero-blocks',
                    'description' => $block_json['description'] ?? '',
                    'keywords'    => $block_json['keywords'] ?? [],
                ];
            } else {
                // Fallback if no block.json
                $blocks_data[] = [
                    'name'        => $block_name,
                    'title'       => ucfirst( str_replace( '-', ' ', $block_slug ) ),
                    'icon'        => 'admin-page',
                    'category'    => 'webtero-blocks',
                    'description' => '',
                    'keywords'    => [],
                ];
            }
        }

        // Enqueue auto-register script with blocks data
        wp_enqueue_script(
            'webtero-auto-register-blocks',
            get_template_directory_uri() . '/admin/src/js/blocks/auto-register-blocks.js',
            [ 'webtero-universal-block-editor' ],
            THEME_VERSION,
            true
        );

        // Pass blocks data to JavaScript
        wp_localize_script(
            'webtero-auto-register-blocks',
            'webteroBlocksData',
            [
                'blocks' => $blocks_data,
            ]
        );
    }

    /**
     * Get block filesystem path
     *
     * @param string $block_slug Block slug (without wt/ prefix)
     * @return string Block path
     */
    private function get_block_path( string $block_slug ): string {
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
     * Get block URL
     *
     * @param string $block_slug Block slug (without wt/ prefix)
     * @return string Block URL
     */
    private function get_block_url( string $block_slug ): string {
        // Check child theme first
        if ( is_child_theme() ) {
            $child_path = get_stylesheet_directory() . '/wt-blocks/' . $block_slug;
            if ( is_dir( $child_path ) ) {
                return get_stylesheet_directory_uri() . '/wt-blocks/' . $block_slug;
            }
        }

        // Fall back to parent theme
        return get_template_directory_uri() . '/wt-blocks/' . $block_slug;
    }

    /**
     * Get block instance by name
     *
     * @param string $block_name Block name (e.g., 'wt/hero')
     * @return Custom_Block|null Block instance or null if not found
     */
    public function get_block( string $block_name ): ?Custom_Block {
        return $this->blocks[ $block_name ] ?? null;
    }

    /**
     * Get all registered blocks
     *
     * @return array<string, Custom_Block> All blocks
     */
    public function get_all_blocks(): array {
        return $this->blocks;
    }
}
