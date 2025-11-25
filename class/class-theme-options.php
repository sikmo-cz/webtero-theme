<?php
/**
 * Theme Options Class
 * 
 * @package webtero
 */

declare(strict_types = 1);

namespace WT;

defined( 'ABSPATH' ) || exit;

class Theme_Options {

    /**
     * Base option ID
     */
    private const BASE_ID = 'webtero-theme-options';
    
    /**
     * Current instance ID
     */
    private string $instance_id;
    
    /**
     * Page slug
     */
    private string $page_slug;
    
    /**
     * Page title
     */
    private string $page_title;
    
    /**
     * Field configuration
     */
    private array $fields = [];

    /**
     * Menu iccon
     */
    private static string $menu_icon = '<svg width="84" height="62" viewBox="0 0 84 62" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.67645 61.0269L0 0H12.612L20.114 50.1292H25.0066L34.5743 5.66678H49.1433L58.711 50.1292H63.6036L71.1056 0H83.7176L74.0411 61.0269H50.8829L41.8588 19.1799L32.8347 61.0269H9.67645Z" fill="white"/></svg>';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_pages' ] );
        add_action( 'admin_init', [ $this, 'handle_form_submission' ] );
        add_action( 'admin_init', [ $this, 'handle_version_restore' ] );
        add_action( 'admin_init', [ $this, 'handle_version_delete' ] );
        add_action( 'admin_init', [ $this, 'handle_clear_history' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_init', [ $this, 'add_theme_options_capability' ] );
    }

    /**
     * Add custom capability to administrator role
     */
    public function add_theme_options_capability() {
        // Only run once
        if ( get_option( 'webtero_capabilities_added' ) ) {
            return;
        }
        
        $role = get_role( 'administrator' );

        if ( $role ) {
            $role->add_cap( 'webtero_theme_options' );
        }
        
        update_option( 'webtero_capabilities_added', true );
    }

    /**
     * Get required capability (static)
     */
    public static function get_capability(): string {
        return apply_filters( 'webtero/theme/options/capability', 'webtero_theme_options' );
    }
    
    /**
     * Get all instances with their types
     */
    private function get_instances(): array {
        $instances = [
            // Global settings (always present)
            '' => [
                'label' => __( 'General Options', 'webtero' ),
                'type' => 'global'
            ]
        ];
        
        // Add language instances
        $language_instances = $this->get_language_instances();

        foreach ( $language_instances as $lang_code => $lang_name ) {
            $instances[ $lang_code ] = [
                'label' => $lang_name,
                'type' => 'language'
            ];
        }
        
        // Allow custom instances
        $custom_instances = apply_filters( 'webtero/theme/options/custom_instances', [] );

        foreach ( $custom_instances as $custom_id => $custom_config ) {
            $instances[ $custom_id ] = [
                'label' => $custom_config['label'] ?? $custom_id,
                'type' => 'custom'
            ];
        }
        
        return $instances;
    }

    /**
     * Get language instances
     * Can be filtered to integrate with Polylang, WPML, etc.
     */
    private function get_language_instances(): array {
        // Default: WordPress locale
        $default_languages = [
            get_locale() => $this->get_language_name( get_locale() )
        ];
        
        return apply_filters( 'webtero/theme/options/languages', $default_languages );
    }

    /**
     * Get friendly language name from locale
     */
    private function get_language_name( string $locale ): string {
        // English is default and not in translations list
        if ( $locale === 'en_US' ) {
            return 'English (United States)';
        }
        
        // Get all available translations
        $translations = wp_get_available_translations();
        
        if ( isset( $translations[ $locale ]['native_name'] ) ) {
            return $translations[ $locale ]['native_name'];
        }
        
        // Fallback: try to get from locale object
        require_once ABSPATH . 'wp-admin/includes/translation-install.php';
        $translations = wp_get_available_translations();
        
        if ( isset( $translations[ $locale ]['native_name'] ) ) {
            return $translations[ $locale ]['native_name'];
        }
        
        // Last fallback: return locale code
        return $locale;
    }

    /**
     * Register a single admin page as submenu
     */
    private function register_single_page( string $id, array $config ) {
        $page_slug = self::BASE_ID . ( $id ? '-' . $id : '' );
        $label = $config['label'];
        
        add_submenu_page(
            'webtero',
            $label,
            $label,
            self::get_capability(),
            $page_slug,
            function() use ( $id, $label, $config ) {
                $this->render_admin_page( $id, $label, $config['type'] );
            }
        );
    }

    /**
     * Register admin pages for all instances
     */
    public function register_admin_pages() {
        // Register parent menu page (informational only)
        add_menu_page(
            __( 'webtero', 'webtero' ),
            __( 'webtero', 'webtero' ),
            self::get_capability(),
            'webtero',
            [ $this, 'render_info_page' ],
            'data:image/svg+xml;base64,' . base64_encode( self::$menu_icon ),
            99
        );
        
        // Register all instances as subpages
        $instances = $this->get_instances();
        
        foreach ( $instances as $id => $config ) {
            $this->register_single_page( $id, $config );
        }
    }

    /**
     * Render info page (parent page)
     */
    public function render_info_page() {
        $instances = $this->get_instances();
        $grouped = [
            'global' => [],
            'language' => [],
            'custom' => []
        ];
        
        foreach ( $instances as $id => $config ) {
            $grouped[ $config['type'] ][] = [
                'id' => $id,
                'label' => $config['label']
            ];
        }
        ?>
        <div class="wrap webtero-info-page">
            <h1><?php _e( 'Webtero Theme', 'webtero' ); ?></h1>
            
            <div class="postbox" style="max-width: 800px; margin-top: 20px;">
                <div class="inside" style="padding: 0 20px;">
                    
                    <h3><?php _e( 'Global Settings', 'webtero' ); ?></h3>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <?php foreach ( $grouped['global'] as $instance ) :
                            $page_slug = self::BASE_ID . ( $instance['id'] ? '-' . $instance['id'] : '' );
                            $url = admin_url( 'admin.php?page=' . $page_slug );
                        ?>
                            <li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $instance['label'] ); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <?php if ( ! empty( $grouped['language'] ) ) : ?>
                        <h3><?php _e( 'Language Settings', 'webtero' ); ?></h3>
                        <ul style="list-style: disc; margin-left: 20px;">
                            <?php foreach ( $grouped['language'] as $instance ) :
                                $page_slug = self::BASE_ID . '-' . $instance['id'];
                                $url = admin_url( 'admin.php?page=' . $page_slug );
                            ?>
                                <li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $instance['label'] ); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $grouped['custom'] ) ) : ?>
                        <h3><?php _e( 'Custom Settings', 'webtero' ); ?></h3>
                        <ul style="list-style: disc; margin-left: 20px;">
                            <?php foreach ( $grouped['custom'] as $instance ) :
                                $page_slug = self::BASE_ID . '-' . $instance['id'];
                                $url = admin_url( 'admin.php?page=' . $page_slug );
                            ?>
                                <li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $instance['label'] ); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <hr style="margin: 20px 0;">
                    
                    <h3><?php _e( 'Theme Information', 'webtero' ); ?></h3>
                    <p><strong><?php _e( 'Version:', 'webtero' ); ?></strong> <?php echo THEME_VERSION; ?></p>
                    <p><strong><?php _e( 'Active Child Theme:', 'webtero' ); ?></strong> <?php echo is_child_theme() ? wp_get_theme()->get('Name') : __( 'None', 'webtero' ); ?></p>
                    
                    <?php do_action( 'webtero/theme/info_page/content' ); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render admin page
     */
    private function render_admin_page( string $instance_id, string $page_title, string $instance_type ) {
        $this->instance_id = $instance_id;
        $this->page_slug = self::BASE_ID . ( $instance_id ? '-' . $instance_id : '' );
        $this->page_title = $page_title;
        $this->fields = $this->get_fields( $instance_id, $instance_type );
        
        $current_tab = $_GET['tab'] ?? array_key_first( $this->fields );
        $current_data = $this->get_current_data();
        $versions = $this->get_versions();
        
        ?>
        <div class="wrap webtero-theme-options">
            <h1><?php echo esc_html( $page_title ); ?></h1>
            
            <?php $this->render_notices(); ?>
            
            <form method="post" action="" id="webtero-options-form">
                <?php wp_nonce_field( 'webtero_save_options', 'webtero_nonce' ); ?>
                <input type="hidden" name="webtero_action" value="save_options">
                <input type="hidden" name="webtero_instance" value="<?php echo esc_attr( $this->instance_id ); ?>">
                <input type="hidden" name="webtero_current_tab" id="webtero-current-tab" value="<?php echo esc_attr( $current_tab ); ?>">
                
                <div class="webtero-header">
                    <div class="webtero-versions">
                        <label><?php _e( 'Current Version:', 'webtero' ); ?></label>
                        <span class="webtero-version-info">
                            <?php echo $this->get_active_version_display( $versions ); ?>
                        </span>
                    </div>
                    <?php submit_button( __( 'Save Changes', 'webtero' ), 'primary', 'submit', false ); ?>
                </div>
                
                <div class="webtero-tabs-wrapper">
                    <nav class="nav-tab-wrapper">
                        <?php foreach ( $this->fields as $tab_id => $tab_config ) : ?>
                            <a href="#" data-tab="<?php echo esc_attr( $tab_id ); ?>"
                            class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                                <?php echo esc_html( $tab_config['label'] ?? $tab_id ); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <?php foreach ( $this->fields as $tab_id => $tab_config ) : ?>
                        <div class="webtero-tab-content" data-tab-content="<?php echo esc_attr( $tab_id ); ?>" style="<?php echo $current_tab !== $tab_id ? 'display:none;' : ''; ?>">
                            <?php $this->render_tab_content( $tab_id, $current_data ); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
            
            <?php $this->render_version_manager( $versions ); ?>
        </div>
        <?php
    }
    
    /**
     * Render tab content
     */
    private function render_tab_content( string $tab_id, array $data ) {
        if ( ! isset( $this->fields[ $tab_id ] ) ) {
            return;
        }
        
        $tab = $this->fields[ $tab_id ];
        $fields = $tab['fields'] ?? [];
        
        // Group fields into metaboxes if specified
        $metaboxes = [];
        foreach ( $fields as $field ) {
            $metabox = $field['metabox'] ?? 'default';
            $metaboxes[ $metabox ][] = $field;
        }
        
        echo '<div class="metabox-holder">';
        
        foreach ( $metaboxes as $metabox_id => $metabox_fields ) {
            // $metabox_title = $metabox_id === 'default' ? $tab['label'] : ucfirst( str_replace( '_', ' ', $metabox_id ) );

            $metabox_title = $metabox_id === 'default' ? false : ucfirst( str_replace( '_', ' ', $metabox_id ) );
            
            echo '<div class="postbox">';
                echo ( $metabox_title !== false ) ? '<h2 class="hndle"><span>' . esc_html( $metabox_title ) . '</span></h2>' : '';
                
                echo '<div class="inside">';
                    echo '<table class="form-table">';
            
            foreach ( $metabox_fields as $field ) {
                $this->render_field( $field, $data );
            }
            
                    echo '</table>';
                echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render a single field
     */
    private function render_field( array $field, array $data ) {
        $type = $field['type'] ?? 'text';
        $id = $field['id'] ?? '';
        $name = $field['name'] ?? $id;
        $label = $field['label'] ?? '';
        $description = $field['description'] ?? '';
        $default = $field['default'] ?? '';
        $value = $data[ $id ] ?? $default;
        $width = $field['width'] ?? '100';
        
        if ( $type === 'repeater' ) {
            $this->render_repeater_field( $field, $value );
            return;
        }
        
        echo '<tr data-field-width="' . esc_attr( $width ) . '">';
        echo '<th scope="row">';
        if ( $label ) {
            echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
        }
        echo '</th>';
        echo '<td>';
        
        switch ( $type ) {
            case 'text':
                $this->render_text_field( $field, $value );
                break;
            case 'number':
                $this->render_number_field( $field, $value );
                break;
            case 'range':
                $this->render_range_field( $field, $value );
                break;
            case 'textarea':
                $this->render_textarea_field( $field, $value );
                break;
            case 'radio':
                $this->render_radio_field( $field, $value );
                break;
            case 'checkbox':
                $this->render_checkbox_field( $field, $value );
                break;
            case 'toggle':
                $this->render_toggle_field( $field, $value );
                break;
            case 'button_group':
                $this->render_button_group_field( $field, $value );
                break;
            case 'color':
                $this->render_color_field( $field, $value );
                break;
            case 'select':
                $this->render_select_field( $field, $value );
                break;
            case 'enhanced_select':
                $this->render_enhanced_select_field( $field, $value );
                break;
            case 'media':
                $this->render_media_field( $field, $value );
                break;
            case 'tiptap':
                $this->render_tiptap_field( $field, $value );
                break;
            case 'code':
                $this->render_code_field( $field, $value );
                break;
        }

        if ( $description ) {
            echo '<p class="description">' . esc_html( $description ) . '</p>';
        }
        
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render text field
     */
    private function render_text_field( array $field, $value ) {
        $attrs = $this->get_field_attributes( $field );
        ?>
        <input 
            type="text" 
            id="<?php echo esc_attr( $field['id'] ); ?>"
            name="webtero_options[<?php echo esc_attr( $field['id'] ); ?>]"
            value="<?php echo esc_attr( $value ); ?>"
            class="regular-text <?php echo esc_attr( $field['class'] ?? '' ); ?>"
            placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
            <?php echo $attrs; ?>
        />
        <?php
    }
    
    /**
     * Render number field
     */
    private function render_number_field( array $field, $value ) {
        $attrs = $this->get_field_attributes( $field );
        ?>
        <input 
            type="number" 
            id="<?php echo esc_attr( $field['id'] ); ?>"
            name="webtero_options[<?php echo esc_attr( $field['id'] ); ?>]"
            value="<?php echo esc_attr( $value ); ?>"
            class="small-text <?php echo esc_attr( $field['class'] ?? '' ); ?>"
            min="<?php echo esc_attr( $field['min'] ?? '' ); ?>"
            max="<?php echo esc_attr( $field['max'] ?? '' ); ?>"
            step="<?php echo esc_attr( $field['step'] ?? '1' ); ?>"
            <?php echo $attrs; ?>
        />
        <?php
    }
    
    /**
     * Render range field
     */
    private function render_range_field( array $field, $value ) {
        $attrs = $this->get_field_attributes( $field );
        $suffix = $field['suffix'] ?? ''; // Optional suffix (e.g., 'px', '%', 'em')
        ?>
        <div class="webtero-range-field-wrapper">
            <input
                type="range"
                id="<?php echo esc_attr( $field['id'] ); ?>"
                name="webtero_options[<?php echo esc_attr( $field['id'] ); ?>]"
                value="<?php echo esc_attr( $value ); ?>"
                class="webtero-range-slider <?php echo esc_attr( $field['class'] ?? '' ); ?>"
                min="<?php echo esc_attr( $field['min'] ?? '0' ); ?>"
                max="<?php echo esc_attr( $field['max'] ?? '100' ); ?>"
                step="<?php echo esc_attr( $field['step'] ?? '1' ); ?>"
                <?php echo $attrs; ?>
            />
            <div class="webtero-range-input-group">
                <input
                    type="number"
                    class="webtero-range-number"
                    value="<?php echo esc_attr( $value ); ?>"
                    min="<?php echo esc_attr( $field['min'] ?? '0' ); ?>"
                    max="<?php echo esc_attr( $field['max'] ?? '100' ); ?>"
                    step="<?php echo esc_attr( $field['step'] ?? '1' ); ?>"
                    data-range-id="<?php echo esc_attr( $field['id'] ); ?>"
                />
                <?php if ( ! empty( $suffix ) ) : ?>
                    <span class="webtero-range-suffix"><?php echo esc_html( $suffix ); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render textarea field
     */
    private function render_textarea_field( array $field, $value ) {
        $attrs = $this->get_field_attributes( $field );
        ?>
        <textarea 
            id="<?php echo esc_attr( $field['id'] ); ?>"
            name="webtero_options[<?php echo esc_attr( $field['id'] ); ?>]"
            class="large-text <?php echo esc_attr( $field['class'] ?? '' ); ?>"
            rows="<?php echo esc_attr( $field['rows'] ?? '5' ); ?>"
            placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
            <?php echo $attrs; ?>
        ><?php echo esc_textarea( $value ); ?></textarea>
        <?php
    }
    
    /**
     * Render radio field
     */
    private function render_radio_field( array $field, $value ) {
        $options = $field['options'] ?? [];
        $attrs = $this->get_field_attributes( $field );
        
        foreach ( $options as $option_value => $option_label ) {
            ?>
            <label>
                <input 
                    type="radio" 
                    name="webtero_options[<?php echo esc_attr( $field['id'] ); ?>]"
                    value="<?php echo esc_attr( $option_value ); ?>"
                    <?php checked( $value, $option_value ); ?>
                    <?php echo $attrs; ?>
                />
                <?php echo esc_html( $option_label ); ?>
            </label><br>
            <?php
        }
    }
    
    /**
     * Render checkbox field
     */
    private function render_checkbox_field( array $field, $value ) {
        $attrs = $this->get_field_attributes( $field );
        ?>
        <!-- Hidden field to ensure 0 is sent when unchecked -->
        <input type="hidden" name="webtero_options[<?php echo esc_attr( $field['id'] ); ?>]" value="0">
        <label>
            <input 
                type="checkbox" 
                id="<?php echo esc_attr( $field['id'] ); ?>"
                name="webtero_options[<?php echo esc_attr( $field['id'] ); ?>]"
                value="1"
                <?php checked( $value, 1 ); ?>
                <?php echo $attrs; ?>
            />
            <?php echo esc_html( $field['checkbox_label'] ?? '' ); ?>
        </label>
        <?php
    }
    
    /**
     * Render color field
     */
    private function render_color_field( array $field, $value ) {
        $attrs = $this->get_field_attributes( $field );
        ?>
        <input 
            type="text" 
            id="<?php echo esc_attr( $field['id'] ); ?>"
            name="webtero_options[<?php echo esc_attr( $field['id'] ); ?>]"
            value="<?php echo esc_attr( $value ); ?>"
            class="webtero-color-picker <?php echo esc_attr( $field['class'] ?? '' ); ?>"
            <?php echo $attrs; ?>
        />
        <?php
    }
    
    /**
     * Render select field
     */
    private function render_select_field( array $field, $value ) {
        $options = $field['options'] ?? [];
        $attrs = $this->get_field_attributes( $field );
        ?>
        <select 
            id="<?php echo esc_attr( $field['id'] ); ?>"
            name="webtero_options[<?php echo esc_attr( $field['id'] ); ?>]"
            class="<?php echo esc_attr( $field['class'] ?? '' ); ?>"
            <?php echo $attrs; ?>
        >
            <?php foreach ( $options as $option_value => $option_label ) : ?>
                <option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
                    <?php echo esc_html( $option_label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Render media field
     */
    private function render_media_field( array $field, $value ) {
        $attrs = $this->get_field_attributes( $field );
        $image_url = $value ? wp_get_attachment_url( $value ) : '';
        ?>
        <div class="webtero-media-field">
            <input 
                type="hidden" 
                id="<?php echo esc_attr( $field['id'] ); ?>"
                name="webtero_options[<?php echo esc_attr( $field['id'] ); ?>]"
                value="<?php echo esc_attr( $value ); ?>"
                class="webtero-media-id"
                <?php echo $attrs; ?>
            />
            <div class="webtero-media-preview">
                <?php if ( $image_url ) : ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" alt="">
                <?php endif; ?>
            </div>
            <button type="button" class="button webtero-media-upload">
                <?php _e( 'Select Image', 'webtero' ); ?>
            </button>
            <button type="button" class="button webtero-media-remove" <?php echo $value ? '' : 'style="display:none;"'; ?>>
                <?php _e( 'Remove', 'webtero' ); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render TipTap editor field
     */
    private function render_tiptap_field( array $field, $value ) {
        $id = $field['id'];
        ?>
        <div
            id="tiptap-editor-<?php echo esc_attr( $id ); ?>"
            class="webtero-tiptap-container"
            data-tiptap-field="<?php echo esc_attr( $id ); ?>"
            data-tiptap-content="<?php echo esc_attr( $value ); ?>"
        ></div>
        <?php
    }

    /**
     * Render code editor field
     */
    private function render_code_field( array $field, $value ) {
        $id = $field['id'];
        $mode = $field['mode'] ?? 'htmlmixed'; // htmlmixed, css, javascript, xml
        $attrs = $this->get_field_attributes( $field );
        ?>
        <div class="webtero-code-editor-wrapper">
            <textarea
                id="<?php echo esc_attr( $id ); ?>"
                name="webtero_options[<?php echo esc_attr( $id ); ?>]"
                class="webtero-code-editor"
                data-mode="<?php echo esc_attr( $mode ); ?>"
                <?php echo $attrs; ?>
            ><?php echo esc_textarea( $value ); ?></textarea>
        </div>
        <?php
    }

    /**
     * Render repeater field
     */
    private function render_repeater_field( array $field, $value ) {
        $id = $field['id'];
        $label = $field['label'] ?? '';
        $sub_fields = $field['fields'] ?? [];
        $values = is_array( $value ) ? $value : [];
        $width = $field['width'] ?? '100';
        
        echo '<tr data-field-width="' . esc_attr( $width ) . '">';
        echo '<th scope="row" colspan="2">';
        echo '<strong>' . esc_html( $label ) . '</strong>';
        echo '</th>';
        echo '</tr>';
        echo '<tr class="repeater-row" data-field-width="100">';
        echo '<td colspan="2">';
        echo '<div class="webtero-repeater" data-field-id="' . esc_attr( $id ) . '">';
        echo '<div class="webtero-repeater-items">';
        
        if ( ! empty( $values ) ) {
            foreach ( $values as $index => $row_data ) {
                // Ensure row_data is an array
                if ( ! is_array( $row_data ) ) {
                    $row_data = [];
                }
                $this->render_repeater_row( $id, $sub_fields, $row_data, $index );
            }
        }
        
        echo '</div>';
        echo '<button type="button" class="button webtero-repeater-add">' . __( 'Add Row', 'webtero' ) . '</button>';
        echo '</div>';
        
        // Template for new rows
        echo '<script type="text/html" id="webtero-repeater-template-' . esc_attr( $id ) . '">';
        $this->render_repeater_row( $id, $sub_fields, [], '{{INDEX}}' );
        echo '</script>';
        
        echo '</td>';
        echo '</tr>';
    }
    
    /**
     * Render repeater row
     */
    private function render_repeater_row( string $parent_id, array $fields, array $data, $index ) {
        ?>
        <div class="webtero-repeater-item">
            <div class="webtero-repeater-handle">
                <span class="dashicons dashicons-menu"></span>
            </div>
            <div class="webtero-repeater-content">
                <?php foreach ( $fields as $field ) : 
                    $field_id = $field['id'];
                    $field_value = $data[ $field_id ] ?? ( $field['default'] ?? '' );
                    $field_name = "webtero_options[{$parent_id}][{$index}][{$field_id}]";
                ?>
                    <div class="webtero-repeater-field">
                        <label><?php echo esc_html( $field['label'] ?? $field_id ); ?></label>
                        <?php $this->render_repeater_field_input( $field, $field_name, $field_value ); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="webtero-repeater-actions">
                <button type="button" class="button webtero-repeater-remove">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render repeater field input
     */
    private function render_repeater_field_input( array $field, string $name, $value ) {
        $type = $field['type'] ?? 'text';

        switch ( $type ) {
            case 'text':
                echo '<input type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
                break;
            case 'number':
                echo '<input type="number" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="small-text">';
                break;
            case 'textarea':
                echo '<textarea name="' . esc_attr( $name ) . '" class="large-text" rows="3">' . esc_textarea( $value ) . '</textarea>';
                break;
            case 'color':
                echo '<input type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="webtero-color-picker">';
                break;
            case 'select':
                echo '<select name="' . esc_attr( $name ) . '">';
                foreach ( $field['options'] ?? [] as $opt_val => $opt_label ) {
                    echo '<option value="' . esc_attr( $opt_val ) . '" ' . selected( $value, $opt_val, false ) . '>' . esc_html( $opt_label ) . '</option>';
                }
                echo '</select>';
                break;
            case 'button_group':
                $options = $field['options'] ?? [];
                $default = $field['default'] ?? '';

                // Use default if no value is set
                if ( empty( $value ) && ! empty( $default ) ) {
                    $value = $default;
                }

                echo '<div class="webtero-button-group" data-multiple="0">';
                foreach ( $options as $option_value => $option_label ) {
                    $is_checked = ( $value == $option_value );
                    echo '<label class="webtero-button-group-item ' . ( $is_checked ? 'active' : '' ) . '">';
                    echo '<input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $option_value ) . '" ' . checked( $is_checked, true, false ) . '>';
                    echo '<span class="webtero-button-text">' . esc_html( $option_label ) . '</span>';
                    echo '</label>';
                }
                echo '</div>';
                break;
            case 'media':
                $image_url = $value ? wp_get_attachment_url( $value ) : '';
                echo '<div class="webtero-media-field">';
                echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="webtero-media-id">';
                echo '<div class="webtero-media-preview">';
                if ( $image_url ) {
                    echo '<img src="' . esc_url( $image_url ) . '" alt="" style="max-width: 100px;">';
                }
                echo '</div>';
                echo '<button type="button" class="button webtero-media-upload">' . __( 'Select Image', 'webtero' ) . '</button>';
                echo '<button type="button" class="button webtero-media-remove" ' . ( $value ? '' : 'style="display:none;"' ) . '>' . __( 'Remove', 'webtero' ) . '</button>';
                echo '</div>';
                break;
            case 'toggle':
                $checked = ! empty( $value );
                echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="0">';
                echo '<div class="webtero-toggle-wrapper">';
                echo '<label class="webtero-toggle" style="--toggle-width: 60px;">';
                echo '<input type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( $checked, true, false ) . '>';
                echo '<span class="webtero-toggle-track">';
                echo '<span class="webtero-toggle-handle"></span>';
                echo '<span class="webtero-toggle-labels">';
                echo '<span class="webtero-toggle-label-off">' . __( 'No', 'webtero' ) . '</span>';
                echo '<span class="webtero-toggle-label-on">' . __( 'Yes', 'webtero' ) . '</span>';
                echo '</span>';
                echo '</span>';
                echo '</label>';
                echo '</div>';
                break;
            case 'range':
                $min = $field['min'] ?? 0;
                $max = $field['max'] ?? 100;
                $step = $field['step'] ?? 1;
                echo '<input type="range" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" step="' . esc_attr( $step ) . '" class="webtero-range-slider">';
                echo '<span class="webtero-range-value">' . esc_html( $value ) . '</span>';
                break;
            case 'checkbox':
                echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="0">';
                echo '<label>';
                echo '<input type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( $value, 1, false ) . '>';
                echo '</label>';
                break;
            case 'radio':
                $options = $field['options'] ?? [];
                foreach ( $options as $opt_val => $opt_label ) {
                    echo '<label style="margin-right: 10px;">';
                    echo '<input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $opt_val ) . '" ' . checked( $value, $opt_val, false ) . '> ';
                    echo esc_html( $opt_label );
                    echo '</label>';
                }
                break;
            case 'tiptap':
                echo '<div class="webtero-tiptap-container" data-tiptap-field="' . esc_attr( $name ) . '" data-tiptap-content="' . esc_attr( $value ) . '"></div>';
                break;
            case 'code':
                $mode = $field['mode'] ?? 'htmlmixed';
                echo '<div class="webtero-code-editor-wrapper">';
                echo '<textarea name="' . esc_attr( $name ) . '" class="webtero-code-editor" data-mode="' . esc_attr( $mode ) . '">' . esc_textarea( $value ) . '</textarea>';
                echo '</div>';
                break;
        }
    }

    /**
     * Render toggle field (pretty true/false)
     */
    private function render_toggle_field( array $field, $value ) {
        $attrs = $this->get_field_attributes( $field );
        $checked = ! empty( $value );
        $label_on = $field['label_on'] ?? __( 'Yes', 'webtero' );
        $label_off = $field['label_off'] ?? __( 'No', 'webtero' );
        
        // Calculate width based on longest label
        $max_length = max( mb_strlen( $label_on ), mb_strlen( $label_off ) );
        $toggle_width = max( 60, ( $max_length * 8 ) + 30 ); // Min 60px, 8px per char + 30px for handle
        ?>
        <!-- Hidden field to ensure 0 is sent when unchecked -->
        <input type="hidden" name="webtero_options[<?php echo esc_attr( $field['id'] ); ?>]" value="0">
        <div class="webtero-toggle-wrapper">
            <label class="webtero-toggle" style="--toggle-width: <?php echo $toggle_width; ?>px;">
                <input 
                    type="checkbox" 
                    id="<?php echo esc_attr( $field['id'] ); ?>"
                    name="webtero_options[<?php echo esc_attr( $field['id'] ); ?>]"
                    value="1"
                    <?php checked( $checked ); ?>
                    <?php echo $attrs; ?>
                />
                <span class="webtero-toggle-track">
                    <span class="webtero-toggle-handle"></span>
                    <span class="webtero-toggle-labels">
                        <span class="webtero-toggle-label-off"><?php echo esc_html( $label_off ); ?></span>
                        <span class="webtero-toggle-label-on"><?php echo esc_html( $label_on ); ?></span>
                    </span>
                </span>
            </label>
            <?php if ( ! empty( $field['toggle_label'] ) ) : ?>
                <span class="webtero-toggle-description"><?php echo esc_html( $field['toggle_label'] ); ?></span>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render button group field (pretty checkboxes/radios)
     */
    private function render_button_group_field( array $field, $value ) {
        $options = $field['options'] ?? [];
        $multiple = $field['multiple'] ?? false;
        $attrs = $this->get_field_attributes( $field );
        $values = $multiple && is_array( $value ) ? $value : [ $value ];
        ?>
        <div class="webtero-button-group" data-multiple="<?php echo $multiple ? '1' : '0'; ?>">
            <?php foreach ( $options as $option_value => $option_config ) :
                $option_label = is_array( $option_config ) ? ( $option_config['label'] ?? $option_value ) : $option_config;
                $option_icon = is_array( $option_config ) ? ( $option_config['icon'] ?? '' ) : '';
                $is_checked = in_array( $option_value, $values );
                $input_type = $multiple ? 'checkbox' : 'radio';
                $input_name = $multiple 
                    ? "webtero_options[{$field['id']}][]" 
                    : "webtero_options[{$field['id']}]";
            ?>
                <label class="webtero-button-group-item <?php echo $is_checked ? 'active' : ''; ?>">
                    <input 
                        type="<?php echo $input_type; ?>"
                        name="<?php echo esc_attr( $input_name ); ?>"
                        value="<?php echo esc_attr( $option_value ); ?>"
                        <?php checked( $is_checked ); ?>
                        <?php echo $attrs; ?>
                    />
                    <?php if ( $option_icon ) : ?>
                        <span class="webtero-button-icon"><?php echo $option_icon; ?></span>
                    <?php endif; ?>
                    <span class="webtero-button-text"><?php echo esc_html( $option_label ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render select2-like field (enhanced select)
     */
    private function render_enhanced_select_field( array $field, $value ) {
        $options = $field['options'] ?? [];
        $multiple = $field['multiple'] ?? false;
        $searchable = $field['searchable'] ?? true;
        $attrs = $this->get_field_attributes( $field );
        $values = $multiple && is_array( $value ) ? $value : [ $value ];
        ?>
        <select 
            id="<?php echo esc_attr( $field['id'] ); ?>"
            name="webtero_options[<?php echo esc_attr( $field['id'] ); ?>]<?php echo $multiple ? '[]' : ''; ?>"
            class="webtero-enhanced-select <?php echo esc_attr( $field['class'] ?? '' ); ?>"
            <?php echo $multiple ? 'multiple' : ''; ?>
            data-placeholder="<?php echo esc_attr( $field['placeholder'] ?? __( 'Select...', 'webtero' ) ); ?>"
            data-searchable="<?php echo $searchable ? '1' : '0'; ?>"
            <?php echo $attrs; ?>
        >
            <?php if ( ! $multiple ) : ?>
                <option value=""><?php echo esc_html( $field['placeholder'] ?? __( '— Select —', 'webtero' ) ); ?></option>
            <?php endif; ?>
            
            <?php foreach ( $options as $option_value => $option_label ) : ?>
                <option 
                    value="<?php echo esc_attr( $option_value ); ?>" 
                    <?php echo in_array( $option_value, $values ) ? 'selected' : ''; ?>
                >
                    <?php echo esc_html( $option_label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Get field attributes
     */
    private function get_field_attributes( array $field ): string {
        $attributes = $field['attributes'] ?? [];
        $attrs = [];
        
        foreach ( $attributes as $key => $value ) {
            $attrs[] = esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
        }
        
        return implode( ' ', $attrs );
    }

    /**
     * Get font options from Local_Fonts class
     *
     * @return array Font options for select field
     */
    private function get_font_options(): array {
        // Fallback if Local_Fonts not available
        if ( ! class_exists( 'WT\Local_Fonts' ) ) {
            return [
                '' => __( 'Default', 'webtero' ),
                'work-sans' => __( 'Work Sans', 'webtero' ),
            ];
        }

        // Get fonts from Local_Fonts
        $fonts = get_option( 'webtero_google_fonts_list', [] );
        $font_options = [ '' => __( 'Default', 'webtero' ) ];

        foreach ( $fonts as $slug => $font_data ) {
            $font_options[ $slug ] = $font_data['name'] ?? $slug;
        }

        return $font_options;        
    }

    /**
     * Get fields configuration based on instance type
     */
    private function get_fields( string $instance_id, string $instance_type ): array {
        
        switch ( $instance_type ) {
            case 'global':
                $fields = $this->get_global_fields();
                break;
                
            case 'language':
                $fields = $this->get_language_fields();
                break;
                
            case 'custom':
                $fields = [];
                break;
                
            default:
                $fields = [];
        }
        
        // Allow filtering by instance ID
        return apply_filters( 'webtero/theme/options/fields/' . $instance_id, $fields, $instance_type );
    }
    
    /**
     * Get fields configuration
     */
    private function get_global_fields(): array {
        return [
            'colors' => [
                'label' => __( 'Colors scheme', 'webtero' ),
                'fields' => [
                    [
                        'type' => 'color',
                        'id' => 'site_background_color',
                        'label' => __( 'Site background', 'webtero' ),
                        'width' => 50,
                    ],
                    [
                        'type' => 'color',
                        'id' => 'text_color',
                        'label' => __( 'Text color', 'webtero' ),
                        'width' => 50,
                    ],
                    [
                        'type' => 'color',
                        'id' => 'primary_color',
                        'label' => __( 'Primary color', 'webtero' ),
                        'width' => 50,
                    ],
                    [
                        'type' => 'color',
                        'id' => 'primary_color_contrast',
                        'label' => __( 'Primary color contrast', 'webtero' ),
                        'width' => 50,
                    ],
                    [
                        'type' => 'color',
                        'id' => 'secondary_color',
                        'label' => __( 'Secondary color', 'webtero' ),
                        'width' => 50,
                    ],
                    [
                        'type' => 'color',
                        'id' => 'secondary_color_contrast',
                        'label' => __( 'Secondary color contrast', 'webtero' ),
                        'width' => 50,
                    ],
                    [
                        'type' => 'repeater',
                        'id' => 'custom_colors',
                        'label' => __( 'Custom colors', 'webtero' ),
                        'fields' => [
                            [
                                'type' => 'text',
                                'id' => 'name',
                                'label' => __( 'Name', 'webtero' )
                            ],
                            [
                                'type' => 'color',
                                'id' => 'value',
                                'label' => __( 'Color', 'webtero' )
                            ]
                        ]
                    ]
                ]
            ],
            'header' => [
                'label' => __( 'Header', 'webtero' ),
                'fields' => [
                    [
                        'type' => 'button_group',
                        'id' => 'header_position',
                        'name' => 'header_position',
                        'label' => __( 'Header position', 'webtero' ),
                        'default' => 'absolute',
                        'options' => [
                            'absolute' => __( 'Absolute', 'webtero' ),
                            'fixed' => __( 'Fixed', 'webtero' ),
                        ],
                        'width' => 50,
                    ],
                    [
                        'type' => 'toggle',
                        'id' => 'overlap_first_block',
                        'label' => __( 'Overlap the first block', 'webtero' ),
                        'default' => 0,
                        'width' => 50,
                    ],
                    [
                        'type' => 'button_group',
                        'id' => 'header_size',
                        'name' => 'header_size',
                        'label' => __( 'Header size', 'webtero' ),
                        'default' => 'normal',
                        'options' => [
                            'small' => __( 'Small', 'webtero' ),
                            'normal' => __( 'Normal', 'webtero' ),
                            'large' => __( 'Large', 'webtero' ),
                        ],
                        'width' => 50,
                    ],
                    [
                        'type' => 'button_group',
                        'id' => 'header_color_scheme',
                        'name' => 'header_color_scheme',
                        'label' => __( 'Header color scheme', 'webtero' ),
                        'default' => 'default',
                        'options' => [
                            'default' => __( 'Default', 'webtero' ),
                            'primary' => __( 'Primary', 'webtero' ),
                            'secondary' => __( 'Secondary', 'webtero' ),
                            'custom' => __( 'Custom', 'webtero' ),
                        ],
                        'width' => 50,
                    ],
                    [
                        'type' => 'color',
                        'id' => 'header_custom_text_color',
                        'label' => __( 'Text color', 'webtero' ),
                        'width' => 50,
                    ],
                    [
                        'type' => 'color',
                        'id' => 'header_custom_background_color',
                        'label' => __( 'Background color', 'webtero' ),
                        'width' => 50,
                    ],
                ]
            ],
            'header-message' => [
                'label' => __( 'Header special message', 'webtero' ),
                'fields' => [
                    [
                        'type' => 'button_group',
                        'id' => 'header_message_position',
                        'name' => 'header_message_position',
                        'label' => __( 'Header Position', 'webtero' ),
                        'default' => 'top',
                        'options' => [
                            'top' => __( 'Top', 'webtero' ),
                            'bottom' => __( 'Bottom', 'webtero' ),
                        ],
                        'width' => 50,
                    ],
                    [
                        'type' => 'toggle',
                        'id' => 'header_message_disable_container',
                        'label' => __( 'Disable container', 'webtero' ),
                        'default' => 0,
                        'width' => 50,
                    ],
                    [
                        'type' => 'color',
                        'id' => 'header_message_background_color',
                        'label' => __( 'Background color', 'webtero' ),
                        'width' => 50,
                    ],
                    [
                        'type' => 'color',
                        'id' => 'header_message_text_color',
                        'label' => __( 'Text color', 'webtero' ),
                        'width' => 50,
                    ],
                ]
            ],
            'buttons' => [
                'label' => __( 'Buttons', 'webtero' ),
                'fields' => [
                    [
                        'type' => 'range',
                        'id' => 'button_border_radius',
                        'label' => __( 'Border radius', 'webtero' ),
                        'default' => 60,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'width' => 50,
                    ],
                    [
                        'type' => 'range',
                        'id' => 'button_font_size',
                        'label' => __( 'Font size', 'webtero' ),
                        'default' => 16,
                        'min' => 10,
                        'max' => 30,
                        'step' => 1,
                        'width' => 50,
                    ],
                    [
                        'type' => 'enhanced_select',
                        'id' => 'button_font_family',
                        'label' => __( 'Font family', 'webtero' ),
                        'default' => '',
                        'options' => $this->get_font_options(),
                        'width' => 100,
                    ],
                    [
                        'type' => 'range',
                        'id' => 'button_small_horizontal_offset',
                        'label' => __( 'Horizontal inner offset', 'webtero' ),
                        'default' => 20,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'width' => 50,
                        'metabox' => 'small_button',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'button_small_vertical_offset',
                        'label' => __( 'Vertical inner offset', 'webtero' ),
                        'default' => 10,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'width' => 50,
                        'metabox' => 'small_button',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'button_default_horizontal_offset',
                        'label' => __( 'Horizontal inner offset', 'webtero' ),
                        'default' => 22,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'width' => 50,
                        'metabox' => 'default_button',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'button_default_vertical_offset',
                        'label' => __( 'Vertical inner offset', 'webtero' ),
                        'default' => 12,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'width' => 50,
                        'metabox' => 'default_button',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'button_large_horizontal_offset',
                        'label' => __( 'Horizontal inner offset', 'webtero' ),
                        'default' => 32,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'width' => 50,
                        'metabox' => 'large_button',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'button_large_vertical_offset',
                        'label' => __( 'Vertical inner offset', 'webtero' ),
                        'default' => 16,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'width' => 50,
                        'metabox' => 'large_button',
                    ],
                ]
            ],
            'headings' => [
                'label' => __( 'Headings', 'webtero' ),
                'fields' => [
                    [
                        'type' => 'color',
                        'id' => 'heading_1_color',
                        'label' => __( 'Color', 'webtero' ),
                        'width' => 50,
                        'metabox' => 'heading_1',
                    ],
                    [
                        'type' => 'enhanced_select',
                        'id' => 'heading_1_font_family',
                        'label' => __( 'Font family', 'webtero' ),
                        'default' => '',
                        'options' => $this->get_font_options(),
                        'width' => 50,
                        'metabox' => 'heading_1',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'heading_1_font_size',
                        'label' => __( 'Font size', 'webtero' ),
                        'default' => 62,
                        'min' => 10,
                        'max' => 100,
                        'step' => 1,
                        'width' => 100,
                        'suffix' => 'px',
                        'metabox' => 'heading_1',
                    ],
                    [
                        'type' => 'color',
                        'id' => 'heading_2_color',
                        'label' => __( 'Color', 'webtero' ),
                        'width' => 50,
                        'metabox' => 'heading_2',
                    ],
                    [
                        'type' => 'enhanced_select',
                        'id' => 'heading_2_font_family',
                        'label' => __( 'Font family', 'webtero' ),
                        'default' => '',
                        'options' => $this->get_font_options(),
                        'width' => 50,
                        'metabox' => 'heading_2',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'heading_2_font_size',
                        'label' => __( 'Font size', 'webtero' ),
                        'default' => 40,
                        'min' => 10,
                        'max' => 100,
                        'step' => 1,
                        'width' => 100,
                        'metabox' => 'heading_2',
                    ],
                    [
                        'type' => 'color',
                        'id' => 'heading_3_color',
                        'label' => __( 'Color', 'webtero' ),
                        'width' => 50,
                        'metabox' => 'heading_3',
                    ],
                    [
                        'type' => 'enhanced_select',
                        'id' => 'heading_3_font_family',
                        'label' => __( 'Font family', 'webtero' ),
                        'default' => '',
                        'options' => $this->get_font_options(),
                        'width' => 50,
                        'metabox' => 'heading_3',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'heading_3_font_size',
                        'label' => __( 'Font size', 'webtero' ),
                        'default' => 30,
                        'min' => 10,
                        'max' => 100,
                        'step' => 1,
                        'width' => 100,
                        'metabox' => 'heading_3',
                    ],
                    [
                        'type' => 'color',
                        'id' => 'heading_4_color',
                        'label' => __( 'Color', 'webtero' ),
                        'width' => 50,
                        'metabox' => 'heading_4',
                    ],
                    [
                        'type' => 'enhanced_select',
                        'id' => 'heading_4_font_family',
                        'label' => __( 'Font family', 'webtero' ),
                        'default' => '',
                        'options' => $this->get_font_options(),
                        'width' => 50,
                        'metabox' => 'heading_4',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'heading_4_font_size',
                        'label' => __( 'Font size', 'webtero' ),
                        'default' => 20,
                        'min' => 10,
                        'max' => 100,
                        'step' => 1,
                        'width' => 100,
                        'metabox' => 'heading_4',
                    ],
                ]
            ],
            'text-options' => [
                'label' => __( 'Text options', 'webtero' ),
                'fields' => [
                    [
                        'type' => 'range',
                        'id' => 'text_font_size',
                        'label' => __( 'Font size', 'webtero' ),
                        'default' => 16,
                        'min' => 10,
                        'max' => 30,
                        'step' => 1,
                        'width' => 50,
                    ],
                    [
                        'type' => 'enhanced_select',
                        'id' => 'text_font_family',
                        'label' => __( 'Font family', 'webtero' ),
                        'default' => '',
                        'options' => $this->get_font_options(),
                        'width' => 50,
                    ],
                ]
            ],
            'lists' => [
                'label' => __( 'Lists', 'webtero' ),
                'fields' => [
                    [
                        'type' => 'repeater',
                        'id' => 'unordered_list_icons',
                        'label' => __( 'Unordered list', 'webtero' ),
                        'fields' => [
                            [
                                'type' => 'text',
                                'id' => 'name',
                                'label' => __( 'Name', 'webtero' )
                            ],
                            [
                                'type' => 'button_group',
                                'id' => 'type',
                                'label' => __( 'Type', 'webtero' ),
                                'default' => 'defaults',
                                'options' => [
                                    'defaults' => __( 'Defaults', 'webtero' ),
                                    'custom' => __( 'Custom', 'webtero' ),
                                ]
                            ],
                            [
                                'type' => 'button_group',
                                'id' => 'defaults',
                                'label' => __( 'Defaults', 'webtero' ),
                                'default' => 'circle',
                                'options' => [
                                    'circle' => __( 'Circle', 'webtero' ),
                                    'disc' => __( 'Disc', 'webtero' ),
                                    'none' => __( 'None', 'webtero' ),
                                ]
                            ],
                            [
                                'type' => 'media',
                                'id' => 'icon',
                                'label' => __( 'Icon', 'webtero' ),
                                'description' => __( 'SVG, PNG', 'webtero' )
                            ]
                        ]
                    ]
                ]
            ],
            'block-offsets' => [
                'label' => __( 'Block offsets', 'webtero' ),
                'fields' => [
                    [
                        'type' => 'range',
                        'id' => 'block_offset_small',
                        'label' => __( 'Small offset', 'webtero' ),
                        'default' => 15,
                        'min' => 0,
                        'max' => 200,
                        'step' => 1,
                        'width' => 50,
                        'metabox' => 'block_offsets',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'block_offset_default',
                        'label' => __( 'Default offset', 'webtero' ),
                        'default' => 35,
                        'min' => 0,
                        'max' => 200,
                        'step' => 1,
                        'width' => 50,
                        'metabox' => 'block_offsets',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'block_offset_big',
                        'label' => __( 'Big offset', 'webtero' ),
                        'default' => 80,
                        'min' => 0,
                        'max' => 200,
                        'step' => 1,
                        'width' => 50,
                        'metabox' => 'block_offsets',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'block_offset_large',
                        'label' => __( 'Large offset', 'webtero' ),
                        'default' => 160,
                        'min' => 0,
                        'max' => 300,
                        'step' => 1,
                        'width' => 50,
                        'metabox' => 'block_offsets',
                    ],
                ]
            ],
            'advanced-settings' => [
                'label' => __( 'Advanced settings', 'webtero' ),
                'fields' => [
                    [
                        'type' => 'range',
                        'id' => 'gutter',
                        'label' => __( 'Gutter', 'webtero' ),
                        'default' => 16,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'width' => 50,
                    ],
                    [
                        'type' => 'range',
                        'id' => 'small_container_width',
                        'label' => __( 'Small container width', 'webtero' ),
                        'default' => 990,
                        'min' => 500,
                        'max' => 1500,
                        'step' => 10,
                        'width' => 50,
                    ],
                    [
                        'type' => 'range',
                        'id' => 'before_content_offset',
                        'label' => __( 'Before content offset', 'webtero' ),
                        'default' => 30,
                        'min' => 0,
                        'max' => 200,
                        'step' => 1,
                        'width' => 50,
                    ],
                    [
                        'type' => 'toggle',
                        'id' => 'allow_blocks_animations',
                        'label' => __( 'Allow blocks animations', 'webtero' ),
                        'default' => 0,
                        'width' => 50,
                    ],
                ]
            ],
            'cards-design' => [
                'label' => __( 'Cards global design', 'webtero' ),
                'fields' => [
                    // Inner offsets
                    [
                        'type' => 'range',
                        'id' => 'card_inner_horizontal_offset',
                        'label' => __( 'Inner horizontal offset', 'webtero' ),
                        'default' => 40,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'width' => 50,
                        'metabox' => 'card_spacing',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'card_inner_vertical_offset',
                        'label' => __( 'Inner vertical offset', 'webtero' ),
                        'default' => 40,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'width' => 50,
                        'metabox' => 'card_spacing',
                    ],
                    // Background
                    [
                        'type' => 'button_group',
                        'id' => 'card_background_color',
                        'label' => __( 'Background color', 'webtero' ),
                        'default' => 'default',
                        'options' => [
                            'default' => __( 'Default', 'webtero' ),
                            'primary' => __( 'Primary', 'webtero' ),
                            'secondary' => __( 'Secondary', 'webtero' ),
                            'custom' => __( 'Custom', 'webtero' ),
                        ],
                        'width' => 50,
                        'metabox' => 'card_background',
                    ],
                    [
                        'type' => 'color',
                        'id' => 'card_custom_background_color',
                        'label' => __( 'Custom background color', 'webtero' ),
                        'width' => 50,
                        'metabox' => 'card_background',
                    ],
                    [
                        'type' => 'button_group',
                        'id' => 'card_text_color',
                        'label' => __( 'Text color', 'webtero' ),
                        'default' => 'default',
                        'options' => [
                            'default' => __( 'Default', 'webtero' ),
                            'primary' => __( 'Primary', 'webtero' ),
                            'secondary' => __( 'Secondary', 'webtero' ),
                            'custom' => __( 'Custom', 'webtero' ),
                        ],
                        'width' => 50,
                        'metabox' => 'card_background',
                    ],
                    [
                        'type' => 'color',
                        'id' => 'card_custom_text_color',
                        'label' => __( 'Custom text color', 'webtero' ),
                        'width' => 50,
                        'metabox' => 'card_background',
                    ],
                    // Media background
                    [
                        'type' => 'button_group',
                        'id' => 'card_media_background',
                        'label' => __( 'Media background', 'webtero' ),
                        'default' => 'none',
                        'options' => [
                            'none' => __( 'None', 'webtero' ),
                            'image' => __( 'Image', 'webtero' ),
                        ],
                        'width' => 100,
                        'metabox' => 'card_media',
                    ],
                    [
                        'type' => 'media',
                        'id' => 'card_media_background_image',
                        'label' => __( 'Background image', 'webtero' ),
                        'width' => 25,
                        'metabox' => 'card_media',
                    ],
                    [
                        'type' => 'select',
                        'id' => 'card_image_repeat',
                        'label' => __( 'Image repeat', 'webtero' ),
                        'default' => 'repeat',
                        'options' => [
                            'repeat' => __( 'Repeat', 'webtero' ),
                            'no-repeat' => __( 'No repeat', 'webtero' ),
                            'repeat-x' => __( 'Repeat X', 'webtero' ),
                            'repeat-y' => __( 'Repeat Y', 'webtero' ),
                        ],
                        'width' => 25,
                        'metabox' => 'card_media',
                    ],
                    [
                        'type' => 'select',
                        'id' => 'card_image_position',
                        'label' => __( 'Image position', 'webtero' ),
                        'default' => 'center',
                        'options' => [
                            'center' => __( 'Center - Center', 'webtero' ),
                            'top' => __( 'Top', 'webtero' ),
                            'bottom' => __( 'Bottom', 'webtero' ),
                            'left' => __( 'Left', 'webtero' ),
                            'right' => __( 'Right', 'webtero' ),
                        ],
                        'width' => 25,
                        'metabox' => 'card_media',
                    ],
                    [
                        'type' => 'select',
                        'id' => 'card_image_size',
                        'label' => __( 'Image size', 'webtero' ),
                        'default' => 'cover',
                        'options' => [
                            'cover' => __( 'Cover', 'webtero' ),
                            'contain' => __( 'Contain', 'webtero' ),
                            'auto' => __( 'Auto', 'webtero' ),
                        ],
                        'width' => 25,
                        'metabox' => 'card_media',
                    ],
                    [
                        'type' => 'toggle',
                        'id' => 'card_media_background_overlay',
                        'label' => __( 'Media background overlay', 'webtero' ),
                        'default' => 1,
                        'width' => 50,
                        'metabox' => 'card_media',
                    ],
                    [
                        'type' => 'color',
                        'id' => 'card_media_overlay_color',
                        'label' => __( 'Overlay color', 'webtero' ),
                        'width' => 50,
                        'metabox' => 'card_media',
                    ],
                    // Box shadow
                    [
                        'type' => 'range',
                        'id' => 'card_shadow_blur',
                        'label' => __( 'Blur', 'webtero' ),
                        'default' => 15,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'width' => 33,
                        'metabox' => 'card_shadow',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'card_shadow_vertical_offset',
                        'label' => __( 'Vertical offset', 'webtero' ),
                        'default' => 5,
                        'min' => -50,
                        'max' => 50,
                        'step' => 1,
                        'width' => 33,
                        'metabox' => 'card_shadow',
                    ],
                    [
                        'type' => 'color',
                        'id' => 'card_shadow_color',
                        'label' => __( 'Color', 'webtero' ),
                        'width' => 33,
                        'metabox' => 'card_shadow',
                    ],
                    // Border
                    [
                        'type' => 'range',
                        'id' => 'card_border_width',
                        'label' => __( 'Width', 'webtero' ),
                        'default' => 0,
                        'min' => 0,
                        'max' => 20,
                        'step' => 1,
                        'width' => 33,
                        'metabox' => 'card_border',
                    ],
                    [
                        'type' => 'color',
                        'id' => 'card_border_color',
                        'label' => __( 'Color', 'webtero' ),
                        'width' => 33,
                        'metabox' => 'card_border',
                    ],
                    [
                        'type' => 'range',
                        'id' => 'card_border_radius',
                        'label' => __( 'Radius', 'webtero' ),
                        'default' => 8,
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                        'width' => 33,
                        'metabox' => 'card_border',
                    ],
                ]
            ],
        ];
    }

    /**
     * Get language-specific fields (logo, menus, contact info)
     */
    private function get_language_fields(): array {
        return [
            'header' => [
                'label' => __( 'Header', 'webtero' ),
                'fields' => [
                    [
                        'type' => 'media',
                        'id' => 'logo',
                        'label' => __( 'Logo', 'webtero' ),
                        'description' => __( 'Upload your site logo', 'webtero' )
                    ],
                ]
            ],
            'special-header-message' => [
                'label' => __( 'Special header message', 'webtero' ),
                'fields' => [
                    [
                        'type' => 'tiptap',
                        'id' => 'special_header_message',
                        'label' => __( 'Message', 'webtero' ),
                    ],
                ]
            ],
            'header-sub-block' => [
                'label' => __( 'Header sub block', 'webtero' ),
                'fields' => [
                    
                ]
            ],
            'scroll-top-button' => [
                'label' => __( 'Scroll top button', 'webtero' ),
                'fields' => [
                    [
                        'type' => 'button_group',
                        'id' => 'scroll_top_button_type',
                        'label' => __( 'Scroll top button', 'webtero' ),
                        'default' => 'disable',
                        'options' => [
                            'disable' => __( 'Disable', 'webtero' ),
                            'image' => __( 'Image', 'webtero' ),
                            'text' => __( 'Text', 'webtero' ),
                        ],
                        'width' => 100,
                    ],
                    [
                        'type' => 'media',
                        'id' => 'scroll_top_button_image',
                        'label' => __( 'Image', 'webtero' ),
                        'width' => 100,
                    ],
                    [
                        'type' => 'text',
                        'id' => 'scroll_top_button_text',
                        'label' => __( 'Text', 'webtero' ),
                        'width' => 100,
                    ],
                ]
            ],
            'footer' => [
                'label' => __( 'Footer', 'webtero' ),
                'fields' => [
                    
                ]
            ],
            'global-codes' => [
                'label' => __( 'Global codes', 'webtero' ),
                'fields' => [
                    [
                        'type' => 'code',
                        'id' => 'header_code',
                        'name' => 'header_code',
                        'label' => __( 'Code in header', 'webtero' ),
                        'placeholder' => __( 'any html code ...', 'webtero' ),
                        'default' => '',
                        'rows' => 5,
                    ],
                    [
                        'type' => 'code',
                        'id' => 'body_start_code',
                        'name' => 'body_start_code',
                        'label' => __( 'Code at the start of body', 'webtero' ),
                        'placeholder' => __( 'any html code ...', 'webtero' ),
                        'default' => '',
                        'rows' => 5,
                    ],
                    [
                        'type' => 'code',
                        'id' => 'body_end_code',
                        'name' => 'body_end_code',
                        'label' => __( 'Code at the end of body', 'webtero' ),
                        'placeholder' => __( 'any html code ...', 'webtero' ),
                        'default' => '',
                        'rows' => 5,
                    ],
                ]
            ],
        ];
    }
    /**
     * Get current data
     */
    private function get_current_data(): array {
        $active_version = $this->get_active_version();
        
        if ( ! $active_version ) {
            return [];
        }
        
        $option_name = $this->get_option_name( $active_version );
        $data = get_option( $option_name, '{}' );
        
        return json_decode( $data, true ) ?: [];
    }
    
    /**
     * Get active version timestamp
     */
    private function get_active_version(): ?int {
        $option_name = $this->get_option_name( 'active' );
        $active = get_option( $option_name );
        
        if ( ! $active ) {
            // Get latest version
            $versions = $this->get_versions();
            return ! empty( $versions ) ? max( array_keys( $versions ) ) : null;
        }
        
        return (int) $active;
    }
    
    /**
     * Get all versions
     */
    private function get_versions(): array {
        $option_name = $this->get_option_name( 'versions' );
        return get_option( $option_name, [] );
    }
    
    /**
     * Get option name
     */
    private function get_option_name( $suffix = '' ): string {
        $base = self::BASE_ID . ( $this->instance_id ? '_' . $this->instance_id : '' );
        return $suffix ? $base . '_' . $suffix : $base;
    }
    
    /**
     * Handle form submission
     */
    public function handle_form_submission() {
        if ( ! isset( $_POST[ 'webtero_action' ] ) || $_POST['webtero_action'] !== 'save_options' ) {
            return;
        }
        
        if ( ! isset( $_POST[ 'webtero_nonce' ] ) || ! wp_verify_nonce( $_POST['webtero_nonce'], 'webtero_save_options' ) ) {
            wp_die( __( 'Security check failed', 'webtero' ) );
        }
        
        if ( ! current_user_can( self::get_capability() ) ) {
            wp_die( __( 'You do not have permission to access this page', 'webtero' ) );
        }
        
        $this->instance_id = sanitize_text_field( $_POST['webtero_instance'] ?? '' );
        $options = $_POST['webtero_options'] ?? [];

        // Get instance info to load fields
        $instances = $this->get_instances();
        $instance_type = $instances[ $this->instance_id ]['type'] ?? 'global';

        // Load fields so get_field_type() works during sanitization
        $this->fields = $this->get_fields( $this->instance_id, $instance_type );

        // Sanitize and validate
        $sanitized = $this->sanitize_options( $options );
        
        // Save as new version
        $timestamp = time();
        $option_name = $this->get_option_name( (string) $timestamp );
        
        update_option( $option_name, wp_json_encode( $sanitized ) );
        
        // Update versions list
        $versions = $this->get_versions();
        $versions[ $timestamp ] = [
            'timestamp' => $timestamp,
            'date' => current_time( 'mysql' ),
            'user' => get_current_user_id()
        ];
        update_option( $this->get_option_name( 'versions' ), $versions );
        
        // Set as active version
        update_option( $this->get_option_name( 'active' ), $timestamp );

        // Fire action after options are saved (for Local_Fonts to download fonts)
        do_action( 'webtero/theme/options/saved', $sanitized, $this->instance_id );

        // Get current tab from POST data (set by JavaScript)
        $current_tab = isset( $_POST['webtero_current_tab'] ) ? sanitize_text_field( $_POST['webtero_current_tab'] ) : '';

        // Redirect with success message
        $redirect_url = add_query_arg( [
            'page' => self::BASE_ID . ( $this->instance_id ? '-' . $this->instance_id : '' ),
            'saved' => '1'
        ], admin_url( 'admin.php' ) );

        // Add tab parameter if it exists
        if ( $current_tab ) {
            $redirect_url = add_query_arg( 'tab', $current_tab, $redirect_url );
        }
        
        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Handle version restoration
     */
    public function handle_version_restore() {
        // Check if this is a version restore request
        if ( ! isset( $_GET['webtero_action'] ) || $_GET['webtero_action'] !== 'set_active_version' ) {
            return;
        }

        // Verify nonce
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'webtero_theme_options' ) ) {
            wp_die( __( 'Security check failed', 'webtero' ) );
        }

        // Check capability
        if ( ! current_user_can( self::get_capability() ) ) {
            wp_die( __( 'You do not have permission to access this page', 'webtero' ) );
        }

        // Get version from URL
        $version = isset( $_GET['version'] ) ? intval( $_GET['version'] ) : 0;

        if ( ! $version ) {
            wp_die( __( 'Invalid version', 'webtero' ) );
        }

        // Get instance from page parameter
        $page = $_GET['page'] ?? '';
        $instance_id = str_replace( self::BASE_ID . '-', '', $page );
        if ( $instance_id === self::BASE_ID ) {
            $instance_id = '';
        }

        $this->instance_id = sanitize_text_field( $instance_id );

        // Verify version exists
        $versions = $this->get_versions();
        if ( ! isset( $versions[ $version ] ) ) {
            wp_die( __( 'Version does not exist', 'webtero' ) );
        }

        // Set as active version
        update_option( $this->get_option_name( 'active' ), $version );

        // Redirect back with success message
        $redirect_url = add_query_arg( [
            'page' => $page,
            'restored' => '1'
        ], admin_url( 'admin.php' ) );

        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Handle version deletion
     */
    public function handle_version_delete() {
        // Check if this is a delete request
        if ( ! isset( $_GET['webtero_action'] ) || $_GET['webtero_action'] !== 'delete_version' ) {
            return;
        }

        // Verify nonce
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'webtero_theme_options' ) ) {
            wp_die( __( 'Security check failed', 'webtero' ) );
        }

        // Check capability
        if ( ! current_user_can( self::get_capability() ) ) {
            wp_die( __( 'You do not have permission to access this page', 'webtero' ) );
        }

        // Get version
        $version = isset( $_GET['version'] ) ? intval( $_GET['version'] ) : 0;
        if ( ! $version ) {
            wp_die( __( 'Invalid version', 'webtero' ) );
        }

        // Get instance
        $page = $_GET['page'] ?? '';
        $instance_id = str_replace( self::BASE_ID . '-', '', $page );
        if ( $instance_id === self::BASE_ID ) {
            $instance_id = '';
        }
        $this->instance_id = sanitize_text_field( $instance_id );

        // Get versions list
        $versions = $this->get_versions();

        // Cannot delete active version
        $active = $this->get_active_version();
        if ( $version === $active ) {
            wp_die( __( 'Cannot delete the active version. Please restore a different version first.', 'webtero' ) );
        }

        // Cannot delete if it's the only version
        if ( count( $versions ) <= 1 ) {
            wp_die( __( 'Cannot delete the only version.', 'webtero' ) );
        }

        // Delete version data
        delete_option( $this->get_option_name( (string) $version ) );

        // Remove from versions list
        unset( $versions[ $version ] );
        update_option( $this->get_option_name( 'versions' ), $versions );

        // Redirect with success message
        $redirect_url = add_query_arg( [
            'page' => $page,
            'deleted' => '1'
        ], admin_url( 'admin.php' ) );

        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Handle clear all history
     */
    public function handle_clear_history() {
        // Check if this is a clear history request
        if ( ! isset( $_POST['webtero_action'] ) || $_POST['webtero_action'] !== 'clear_history' ) {
            return;
        }

        // Verify nonce
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'webtero_clear_history' ) ) {
            wp_die( __( 'Security check failed', 'webtero' ) );
        }

        // Check capability
        if ( ! current_user_can( self::get_capability() ) ) {
            wp_die( __( 'You do not have permission to access this page', 'webtero' ) );
        }

        // Get instance from POST
        $instance_id = isset( $_POST['instance_id'] ) ? sanitize_text_field( $_POST['instance_id'] ) : '';
        $this->instance_id = $instance_id;

        // Get current active version and versions list
        $active = $this->get_active_version();
        $versions = $this->get_versions();

        // Delete all versions EXCEPT the active one
        foreach ( $versions as $timestamp => $meta ) {
            if ( $timestamp !== $active ) {
                delete_option( $this->get_option_name( (string) $timestamp ) );
            }
        }

        // Keep only active version in list
        $versions = [ $active => $versions[ $active ] ];
        update_option( $this->get_option_name( 'versions' ), $versions );

        // Redirect with success message
        $page = self::BASE_ID . ( $instance_id ? '-' . $instance_id : '' );
        $redirect_url = add_query_arg( [
            'page' => $page,
            'history_cleared' => '1'
        ], admin_url( 'admin.php' ) );

        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Sanitize options
     */
    private function sanitize_options( array $options ): array {
        $sanitized = [];

        foreach ( $options as $key => $value ) {
            if ( is_array( $value ) ) {
                // Check if it's a numeric array AND contains scalar values (not arrays)
                // This differentiates simple multi-value fields from repeaters
                if ( $this->is_numeric_array( $value ) && $this->is_scalar_array( $value ) ) {
                    // It's a multi-value field (e.g., button_group multiple), sanitize each value individually
                    $sanitized[ $key ] = array_map( 'sanitize_text_field', $value );
                } else {
                    // It's a nested structure (e.g., repeater or associative array), recurse
                    $sanitized[ $key ] = $this->sanitize_options( $value );
                }
            } else {
                $sanitized[ $key ] = $this->sanitize_field_value( (string) $key, $value );
            }
        }

        return apply_filters( 'webtero/theme/options/sanitize', $sanitized );
    }

    /**
     * Check if array has numeric keys (multi-value field)
     */
    private function is_numeric_array( array $array ): bool {
        if ( empty( $array ) ) {
            return false;
        }
        return array_keys( $array ) === range( 0, count( $array ) - 1 );
    }

    /**
     * Check if array contains only scalar values (not arrays)
     */
    private function is_scalar_array( array $array ): bool {
        foreach ( $array as $value ) {
            if ( is_array( $value ) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Sanitize field value
     */
    private function sanitize_field_value( string $field_id, $value ) {
        // Try to find field type
        $field_type = $this->get_field_type( $field_id );
        
        // Apply type-specific sanitization
        switch ( $field_type ) {
            case 'number':
            case 'range':
                return (int) $value;
            case 'checkbox':
                return (bool) $value;
            case 'email':
                return sanitize_email( $value );
            case 'url':
                return esc_url_raw( $value );
            case 'textarea':
                return sanitize_textarea_field( $value );
            case 'tiptap':
                return wp_kses_post( $value );
            case 'code':
                // For code fields, allow all HTML/JS/CSS - just ensure it's a string
                // Use wp_unslash to remove WordPress's added slashes
                return wp_unslash( (string) $value );
            case 'color':
                return sanitize_hex_color( $value );
            case 'media':
                return (int) $value;
            default:
                return sanitize_text_field( $value );
        }
        
        return apply_filters( 'webtero/theme/options/validate/' . $field_type, $value, $field_id );
    }
    
    /**
     * Get field type by ID
     */
    private function get_field_type( string $field_id ): ?string {
        foreach ( $this->fields as $tab ) {
            foreach ( $tab['fields'] ?? [] as $field ) {
                if ( $field['id'] === $field_id ) {
                    return $field['type'] ?? 'text';
                }
                
                // Check in repeater sub-fields
                if ( $field['type'] === 'repeater' ) {
                    foreach ( $field['fields'] ?? [] as $sub_field ) {
                        if ( $sub_field['id'] === $field_id ) {
                            return $sub_field['type'] ?? 'text';
                        }
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Render admin notices
     */
    private function render_notices() {
        if ( isset( $_GET['saved'] ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Options saved successfully!', 'webtero' ); ?></p>
            </div>
            <?php
        }

        // Version restored notice
        if ( isset( $_GET['restored'] ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Version restored successfully!', 'webtero' ); ?></p>
            </div>
            <?php
        }

        // Version deleted notice
        if ( isset( $_GET['deleted'] ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Version deleted successfully!', 'webtero' ); ?></p>
            </div>
            <?php
        }

        // History cleared notice
        if ( isset( $_GET['history_cleared'] ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Version history cleared successfully!', 'webtero' ); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * Get active version display
     */
    private function get_active_version_display( array $versions ): string {
        $active = $this->get_active_version();
        
        if ( ! $active || ! isset( $versions[ $active ] ) ) {
            return __( 'No version', 'webtero' );
        }
        
        return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $active );
    }
    
    /**
     * Render version manager
     */
    private function render_version_manager( array $versions ) {
        if ( empty( $versions ) ) {
            return;
        }

        $active = $this->get_active_version();
        ?>
        <div class="postbox" style="margin-top: 20px;">
            <h2 class="hndle"><span><?php _e( 'Version History', 'webtero' ); ?></span></h2>
            <div class="inside">
                <?php if ( count( $versions ) > 1 ) : ?>
                    <form method="post" style="margin-bottom: 15px;"
                          onsubmit="return confirm('<?php esc_attr_e( 'Are you sure you want to delete all version history except the active version? This action cannot be undone.', 'webtero' ); ?>');">
                        <input type="hidden" name="webtero_action" value="clear_history">
                        <input type="hidden" name="instance_id" value="<?php echo esc_attr( $this->instance_id ); ?>">
                        <?php wp_nonce_field( 'webtero_clear_history', '_wpnonce' ); ?>
                        <button type="submit" class="button button-secondary">
                            <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
                            <?php _e( 'Clear Version History', 'webtero' ); ?>
                        </button>
                        <span class="description">
                            <?php _e( 'Delete all saved versions except the currently active one.', 'webtero' ); ?>
                        </span>
                    </form>
                <?php endif; ?>

                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e( 'Version', 'webtero' ); ?></th>
                            <th><?php _e( 'Date', 'webtero' ); ?></th>
                            <th><?php _e( 'Actions', 'webtero' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        krsort( $versions );
                        foreach ( $versions as $timestamp => $meta ) :
                        ?>
                            <tr>
                                <td>
                                    <?php echo esc_html( date_i18n( 'Y-m-d H:i:s', $timestamp ) ); ?>
                                    <?php if ( $timestamp === $active ) : ?>
                                        <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $meta['date'] ); ?></td>
                                <td>
                                    <?php if ( $timestamp === $active ) : ?>
                                        <button class="button button-small" disabled>
                                            <?php _e( 'Active Version', 'webtero' ); ?>
                                        </button>
                                    <?php else : ?>
                                        <a href="#" class="button button-small webtero-version-restore"
                                           data-version="<?php echo esc_attr( $timestamp ); ?>">
                                            <?php _e( 'Restore This Version', 'webtero' ); ?>
                                        </a>
                                        <a href="#" class="button button-small button-link-delete webtero-version-delete"
                                           data-version="<?php echo esc_attr( $timestamp ); ?>"
                                           style="color: #b32d2e;">
                                            <?php _e( 'Delete', 'webtero' ); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_assets( $hook ) {
        // Only load on our admin pages
        if ( strpos( $hook, self::BASE_ID ) === false ) {
            return;
        }
        
        // WordPress color picker
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        
        // WordPress media uploader
        wp_enqueue_media();
        
        // jQuery UI for sortable
        wp_enqueue_script( 'jquery-ui-sortable' );

        // Choices.js (lightweight select2 alternative)
        wp_enqueue_style(
            'choices-js',
            'https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css',
            [],
            '10.2.0'
        );

        wp_enqueue_script(
            'choices-js',
            'https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js',
            [],
            '10.2.0',
            true
        );

        // CodeMirror for code editor fields
        wp_enqueue_style(
            'codemirror',
            'https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.css',
            [],
            '5.65.16'
        );

        wp_enqueue_style(
            'codemirror-theme',
            'https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/monokai.min.css',
            [ 'codemirror' ],
            '5.65.16'
        );

        wp_enqueue_script(
            'codemirror',
            'https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.js',
            [],
            '5.65.16',
            true
        );

        // CodeMirror modes
        wp_enqueue_script(
            'codemirror-mode-htmlmixed',
            'https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.min.js',
            [ 'codemirror' ],
            '5.65.16',
            true
        );

        wp_enqueue_script(
            'codemirror-mode-xml',
            'https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.min.js',
            [ 'codemirror' ],
            '5.65.16',
            true
        );

        wp_enqueue_script(
            'codemirror-mode-javascript',
            'https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.min.js',
            [ 'codemirror' ],
            '5.65.16',
            true
        );

        wp_enqueue_script(
            'codemirror-mode-css',
            'https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.min.js',
            [ 'codemirror' ],
            '5.65.16',
            true
        );
        
        // TipTap CSS (original)
        wp_enqueue_style(
            'webtero-tiptap-editor',
            THEME_URL . '/admin/src/css/tiptap-editor.css',
            [],
            THEME_VERSION
        );

        // TipTap Modern CSS (Gutenberg-style design)
        wp_enqueue_style(
            'webtero-tiptap-editor-modern',
            THEME_URL . '/admin/src/css/tiptap-editor-modern.css',
            [ 'webtero-tiptap-editor' ],
            THEME_VERSION
        );

        // Custom CSS
        wp_enqueue_style(
            'webtero-theme-options',
            THEME_URL . '/admin/src/css/theme-options.css',
            [ 'choices-js', 'webtero-tiptap-editor-modern' ],
            THEME_VERSION
        );

        // TipTap editor script as ES module
        wp_enqueue_script(
            'webtero-tiptap-editor',
            THEME_URL . '/admin/src/js/tiptap-editor.js',
            [],
            THEME_VERSION,
            true
        );

        // Custom JS
        wp_enqueue_script(
            'webtero-theme-options',
            THEME_URL . '/admin/src/js/theme-options.js',
            [ 'jquery', 'wp-color-picker', 'jquery-ui-sortable', 'choices-js' ],
            THEME_VERSION,
            true
        );

        // Add type="module" to TipTap script
        add_filter( 'script_loader_tag', function( $tag, $handle ) {
            if ( 'webtero-tiptap-editor' === $handle ) {
                return str_replace( '<script', '<script type="module"', $tag );
            }
            return $tag;
        }, 10, 2 );

        wp_localize_script(
            'webtero-theme-options',
            'webteroThemeOptions',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'webtero_theme_options' ),
                'pageId' => $hook, // Used for localStorage key to differentiate between different option pages
                'strings' => [
                    'confirmDelete' => __( 'Are you sure you want to remove this item?', 'webtero' ),
                    'selectImage' => __( 'Select Image', 'webtero' ),
                    'useImage' => __( 'Use This Image', 'webtero' )
                ],
                'buttonClasses' => $this->get_button_classes()
            ]
        );
    }

    /**
     * Get button classes for TipTap editor
     */
    private function get_button_classes(): array {
        $classes = [
            'Primary'      => 'btn-primary',
            'Secondary'    => 'btn-secondary',
            'Small'        => 'btn-sm',
            'Large'        => 'btn-lg',
            'Outline'      => 'btn-outline',
            'Rounded'      => 'btn-rounded',
        ];

        return apply_filters( 'webtero/tiptap/button_classes', $classes );
    }
    
    /**
     * Static method to get option value
     * 
     * @param string $key Option key
     * @param string $instance Instance ID (empty for default)
     * @param int|null $version Specific version timestamp (null for active)
     * @return mixed
     */
    public static function get_option( string $key, string $instance = '', ?int $version = null ) {
        $base = self::BASE_ID . ( $instance ? '_' . $instance : '' );
        
        // Get version
        if ( $version === null ) {
            $active = get_option( $base . '_active' );
            if ( ! $active ) {
                $versions = get_option( $base . '_versions', [] );
                $version = ! empty( $versions ) ? max( array_keys( $versions ) ) : null;
            } else {
                $version = (int) $active;
            }
        }
        
        if ( ! $version ) {
            return null;
        }
        
        // Get data
        $option_name = $base . '_' . $version;
        $data = get_option( $option_name, '{}' );
        $decoded = json_decode( $data, true );
        
        return $decoded[ $key ] ?? null;
    }
}