<?php
/**
 * Gutenberg Block Option Class
 * 
 * @package webtero
 */

declare(strict_types = 1);

namespace WT;

defined( 'ABSPATH' ) || exit;

class Gutenberg_Block_Options {
    
    /**
     * Constructor - hooks into WordPress
     */
    public function __construct() {
        // Enqueue editor scripts and styles (using enqueue_block_assets for API v3 iframe compatibility)
        add_action( 'enqueue_block_assets', [ $this, 'enqueue_editor_assets' ] );
        
        // Disable block nesting (blocks inside blocks)
        add_filter( 'allowed_block_types_all', [ $this, 'disable_nested_blocks' ], 10, 2 );
        
        // Register REST API endpoint for modal content
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

        add_filter( 'render_block', [ $this, 'wrap_block' ], 10, 2 );

        // Remove styling options from core blocks (must run early)
        add_filter( 'register_block_type_args', [ $this, 'remove_core_block_styling' ], 999, 2 );

        // Remove Patterns menu from Appearance
        add_action( 'admin_menu', [ $this, 'remove_patterns_menu' ], 999 );
    }

    /**
     * Remove Patterns from Appearance menu
     */
    public function remove_patterns_menu()
    {
        remove_submenu_page( 'themes.php', 'site-editor.php?p=/pattern' );
    }
    
    /**
     * Get button classes for TipTap button modal
     */
    public function get_button_classes(): array {
        $classes = [
            '100% Width'   => 'btn-block',
            'Primary'      => 'btn-primary',
            'Secondary'    => 'btn-secondary',
            'White BG'     => 'btn-white',
            'Small'        => 'btn-sm',
            'Large'        => 'btn-lg',
            'Outline'      => 'btn-outline',
            'Rounded'      => 'btn-rounded',
        ];
        
        return apply_filters( 'webtero/tiptap/button_classes', $classes );
    }
    
    /**
     * Register REST API routes for getting modal content
     */
    public function register_rest_routes() {
        register_rest_route( 'webtero/v1', '/block-options-form', [
            'methods'  => 'GET',
            'callback' => [ $this, 'get_block_options_form' ],
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ]);

        // Endpoint for block-specific fields (HTML for modal)
        register_rest_route( 'webtero/v1', '/block-fields/(?P<blockName>[a-zA-Z0-9-_/]+)', [
            'methods'  => 'GET',
            'callback' => [ $this, 'get_block_fields' ],
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'args' => [
                'blockName' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'options' => [
                    'required' => false,
                    'type' => 'string',
                ],
            ],
        ]);

        // Endpoint for block fields as JSON (for React editor)
        register_rest_route( 'webtero/v1', '/block-fields-editor/(?P<blockName>[a-zA-Z0-9-_/]+)', [
            'methods'  => 'GET',
            'callback' => [ $this, 'get_block_fields_json' ],
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'args' => [
                'blockName' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);
    }
    
    /**
     * Return the HTML form for block options
     * This is where you define all your 50-60 form fields
     */
    public function get_block_options_form( $request ) {
        // Get block options if passed (for pre-filling form)
        $options = $request->get_param( 'options' );
        $options = $options ? json_decode( $options, true ) : [];
        
        // Build your form HTML here - add as many fields as you need
        ob_start();
        ?>
        
        <div class="webtero-field">
            <label for="webtero-option-title">Custom Title:</label>
            <input 
                type="text" 
                id="webtero-option-title" 
                class="webtero-autosave"
                data-option="customTitle"
                value="<?php echo esc_attr( $options['customTitle'] ?? '' ); ?>"
                placeholder="Enter custom title..."
            />
        </div>
        
        <div class="webtero-field">
            <label for="webtero-option-description">Description:</label>
            <textarea 
                id="webtero-option-description" 
                class="webtero-autosave"
                data-option="description"
                placeholder="Enter description..."
                rows="4"
            ><?php echo esc_textarea( $options['description'] ?? '' ); ?></textarea>
        </div>
        
        <!-- TipTap Rich Text Editor #1 -->
        <div class="webtero-field">
            <label>Rich Content Editor #1 (TipTap):</label>
            <div 
                id="tiptap-editor-1" 
                class="webtero-tiptap-container"
                data-tiptap-field="richContent1"
                data-tiptap-content="<?php echo esc_attr( $options['richContent1'] ?? '' ); ?>"
            ></div>
        </div>
        
        <!-- TipTap Rich Text Editor #2 -->
        <div class="webtero-field">
            <label>Rich Content Editor #2 (TipTap):</label>
            <div 
                id="tiptap-editor-2" 
                class="webtero-tiptap-container"
                data-tiptap-field="richContent2"
                data-tiptap-content="<?php echo esc_attr( $options['richContent2'] ?? '' ); ?>"
            ></div>
        </div>
        
        <div class="webtero-field">
            <label for="webtero-option-color">Background Color:</label>
            <input 
                type="color" 
                id="webtero-option-color" 
                class="webtero-autosave"
                data-option="backgroundColor"
                value="<?php echo esc_attr( $options['backgroundColor'] ?? '#ffffff' ); ?>"
            />
        </div>
        
        <div class="webtero-field">
            <label for="webtero-option-padding">Padding (px):</label>
            <input 
                type="number" 
                id="webtero-option-padding" 
                class="webtero-autosave"
                data-option="padding"
                value="<?php echo esc_attr( $options['padding'] ?? '0' ); ?>"
                min="0"
                max="100"
            />
        </div>
        
        <div class="webtero-field">
            <label for="webtero-option-animate">Enable Animation:</label>
            <select 
                id="webtero-option-animate" 
                class="webtero-autosave"
                data-option="animation"
            >
                <option value="" <?php selected( $options['animation'] ?? '', '' ); ?>>None</option>
                <option value="fade" <?php selected( $options['animation'] ?? '', 'fade' ); ?>>Fade</option>
                <option value="slide" <?php selected( $options['animation'] ?? '', 'slide' ); ?>>Slide</option>
                <option value="zoom" <?php selected( $options['animation'] ?? '', 'zoom' ); ?>>Zoom</option>
            </select>
        </div>
        
        <!-- Add your additional 50+ fields here following the same pattern -->
        <!-- Each field must have:
             1. class="webtero-autosave" for auto-save to work
             2. data-option="fieldname" to identify which option it saves
             
             For TipTap fields:
             1. Add class="webtero-tiptap-container"
             2. Add data-tiptap-field="fieldname" 
             3. Add data-tiptap-content="<?php echo esc_attr( $options['fieldname'] ?? '' ); ?>"
        -->
        
        <?php
        $form_html = ob_get_clean();
        
        return [
            'success' => true,
            'html'    => $form_html
        ];
    }

    /**
     * Get block-specific fields
     * Returns HTML for custom block configuration modal
     *
     * FOR ALL BLOCKS: Returns the same universal form fields
     * This ensures every block has identical "Block Options" modal
     *
     * @param \WP_REST_Request $request REST request
     * @return array Response data
     */
    public function get_block_fields( $request ): array {
        // For ALL blocks, return the universal form fields
        // This ensures every block has the same "Block Options" modal
        return $this->get_block_options_form( $request );
    }

    /**
     * Get block fields as JSON (for React editor)
     *
     * @param \WP_REST_Request $request REST request
     * @return array Response data
     */
    public function get_block_fields_json( $request ): array {
        $block_name = $request->get_param( 'blockName' );

        // Check if this is a custom wt/ block
        if ( strpos( $block_name, 'wt/' ) !== 0 ) {
            return [
                'success' => false,
                'fields'  => [],
                'message' => 'Not a custom block'
            ];
        }

        // Get block registry instance
        global $WT_Block_Registry;

        if ( ! $WT_Block_Registry ) {
            return [
                'success' => false,
                'fields'  => [],
                'message' => 'Block registry not initialized'
            ];
        }

        // Get block instance
        $block_instance = $WT_Block_Registry->get_block( $block_name );

        if ( ! $block_instance ) {
            return [
                'success' => false,
                'fields'  => [],
                'message' => 'Block not found: ' . $block_name
            ];
        }

        // Get block fields configuration
        $fields = $block_instance->get_fields();

        return [
            'success' => true,
            'fields'  => $fields
        ];
    }

    /**
     * Enqueue JavaScript and CSS for the block editor
     */
    public function enqueue_editor_assets() {
        // Only load in admin/editor context, not on frontend
        if ( ! is_admin() && ! wp_is_json_request() ) {
            return;
        }

        // Disable paragraph formatting (bold, italic, link, etc.)
        wp_enqueue_script(
            'webtero-disable-paragraph-formatting',
            THEME_URL . '/admin/src/js/disable-paragraph-formatting.js',
            [ 'wp-blocks', 'wp-dom-ready', 'wp-rich-text' ],
            THEME_VERSION,
            true
        );

        // Enqueue our custom TipTap editor script as ES module
        wp_enqueue_script(
            'webtero-tiptap-editor',
            THEME_URL . '/admin/src/js/tiptap-editor.js',
            [],
            THEME_VERSION,
            true
        );

        // Mark as ES module
        add_filter( 'script_loader_tag', [ $this, 'add_module_type' ], 10, 2 );

        // Enqueue custom JS for modal and block options
        wp_enqueue_script(
            'webtero-block-options',
            THEME_URL . '/admin/src/js/gutenberg-block-options.js',
            [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-hooks', 'wp-api-fetch' ],
            THEME_VERSION,
            true
        );

        // Pass REST API URL to JavaScript
        wp_localize_script(
            'webtero-block-options',
            'webteroBlockOptions',
            [
                'restUrl'       => rest_url( 'webtero/v1/block-options-form' ),
                'nonce'         => wp_create_nonce( 'wp_rest' ),
                'buttonClasses' => $this->get_button_classes()
            ]
        );

        // Enqueue TipTap CSS (original)
        wp_enqueue_style(
            'webtero-tiptap-editor',
            THEME_URL . '/admin/src/css/tiptap-editor.css',
            [],
            THEME_VERSION
        );

        // Enqueue modern TipTap CSS (new Gutenberg-style design)
        wp_enqueue_style(
            'webtero-tiptap-editor-modern',
            THEME_URL . '/admin/src/css/tiptap-editor-modern.css',
            ['webtero-tiptap-editor'],
            THEME_VERSION
        );

        // Enqueue custom CSS for modal styling
        wp_enqueue_style(
            'webtero-block-options',
            THEME_URL . '/admin/src/css/gutenberg-block-options.css',
            ['webtero-tiptap-editor-modern'],
            THEME_VERSION
        );
    }
    
    /**
     * Add type="module" to TipTap script tag
     */
    public function add_module_type( $tag, $handle ) {
        if ( 'webtero-tiptap-editor' === $handle ) {
            return str_replace( '<script', '<script type="module"', $tag );
        }
        return $tag;
    }
    
    /**
     * Disable nested blocks (blocks inside blocks)
     * Allows only top-level blocks
     */
    public function disable_nested_blocks( $allowed_blocks, $editor_context ) {
        // Get all registered blocks
        $all_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
        
        // Filter out container blocks that allow nesting
        $top_level_blocks = [];
        foreach ( $all_blocks as $block_name => $block_type ) {
            // Skip common container blocks
            if ( ! in_array( $block_name, [
                'core/group',
                'core/cover',
                'core/columns',
                'core/column',
                'core/media-text',
            ] ) ) {
                $top_level_blocks[] = $block_name;
            }
        }
        
        return $top_level_blocks;
    }

    /**
     * Wrap each Gutenberg block with custom section element
     * Includes block name, custom classes, anchor, and JSON options as data attribute
     *
     * @param string $block_content The block content
     * @param array $block The block data
     * @return string Modified block content
     */
    public function wrap_block( string $block_content, array $block ): string
    {
        // Skip empty blocks
        if ( empty( $block_content ) ) {
            return $block_content;
        }

        // Skip core/paragraph blocks - don't render them on frontend
        if ( isset( $block['blockName'] ) && $block['blockName'] === 'core/paragraph' ) {
            return '';
        }

        $options_array = array();
        
        // Get block name (e.g., "core/paragraph" becomes "core-paragraph")
        $block_name = str_replace( array( 'wt/', '/', ), array( '', '-' ), $block['blockName'] ?? 'unknown' );

        // don't know, don't care
        if( $block_name == 'unknown' ) {
            return '';
        }
        
        // Get custom classes from block settings (user can add via "Additional CSS class(es)")
        $custom_classes = $block['attrs']['className'] ?? '';
        
        // Get anchor/ID from block settings (user can add via "HTML anchor")
        $anchor = $block['attrs']['anchor'] ?? '';
        
        // Get Webtero custom options (our JSON data)
        $webtero_options = $block['attrs']['webteroOptions'] ?? '';
        
        // Build CSS classes
        $classes = [
            'block',
            'block--' . $block_name
        ];
        
        // Add custom classes if provided
        if ( ! empty( $custom_classes ) ) {
            $classes[] = $custom_classes;
        }
        
        $class_string = implode( ' ', $classes );
        
        // Build section attributes
        $section_attrs = [ 'class="' . esc_attr( $class_string ) . '"' ];
        
        // Add ID attribute if anchor is set
        if ( ! empty( $anchor ) ) {
            $section_attrs[] = 'id="' . esc_attr( $anchor ) . '"';
        }
        
        // Add data attribute with JSON options if available
        if ( ! empty( $webtero_options ) ) {
            // Decode to validate JSON, then re-encode for output
            $options_array = json_decode( $webtero_options, true );
        }

        $options_array = apply_filters( 'webtero/block/options', $options_array, $block, $block_content );
        
        // Build the wrapper
        $wrapper_open = '<section ' . implode( ' ', $section_attrs ) . '>';
        $wrapper_close = '</section>';
        
        return $wrapper_open . $block_content . $wrapper_close;
    }

    /**
     * Remove formatting options from core/paragraph block
     * Removes bold, italic, link, alignment, and other formatting
     *
     * @param array $args Block registration arguments
     * @param string $block_type Block type name
     * @return array Modified block arguments
     */
    public function remove_core_block_styling( array $args, string $block_type ): array
    {
        // Only modify core/paragraph block
        if ( $block_type !== 'core/paragraph' ) {
            return $args;
        }

        // Remove all formatting options
        if ( ! isset( $args['supports'] ) ) {
            return $args;
        }

        // Remove alignment controls
        $args['supports']['align'] = false;
        $args['supports']['alignWide'] = false;

        // Remove color controls
        $args['supports']['color'] = false;

        // Remove typography controls
        $args['supports']['typography'] = false;

        // Remove spacing
        $args['supports']['spacing'] = false;

        // Remove drop cap
        if ( isset( $args['supports']['__experimentalFeatures'] ) ) {
            unset( $args['supports']['__experimentalFeatures'] );
        }

        return $args;
    }
}