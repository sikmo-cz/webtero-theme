<?php
/**
 * Local Fonts Manager Class
 *
 * Manages downloading and serving Google Fonts locally from fonts.webtero.com
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT;

defined( 'ABSPATH' ) || exit;

class Local_Fonts {

    /**
     * Font list URL
     */
    private const FONT_LIST_URL = 'https://fonts.webtero.com/';

    /**
     * Font base URL
     */
    private const FONT_BASE_URL = 'https://fonts.webtero.com/fonts/';

    /**
     * Option name for storing font list
     */
    private const FONT_LIST_OPTION = 'webtero_google_fonts_list';

    /**
     * Option name for storing last update time
     */
    private const LAST_UPDATE_OPTION = 'webtero_google_fonts_last_update';

    /**
     * Child theme directory for fonts
     */
    private string $fonts_dir;

    /**
     * Child theme URL for fonts
     */
    private string $fonts_url;

    /**
     * Constructor
     */
    public function __construct() {
        // Set fonts directory in webtero-child theme
        $child_theme_dir = get_stylesheet_directory(); // Always gets child theme if active
        $child_theme_url = get_stylesheet_directory_uri();

        $this->fonts_dir = $child_theme_dir . '/fonts';
        $this->fonts_url = $child_theme_url . '/fonts';

        // Create fonts directory if it doesn't exist
        if ( ! file_exists( $this->fonts_dir ) ) {
            wp_mkdir_p( $this->fonts_dir );
        }

        // Schedule daily font list update
        add_action( 'init', [ $this, 'schedule_font_list_update' ] );
        add_action( 'webtero_update_font_list', [ $this, 'update_font_list' ] );

        // Hook into theme options save to download fonts
        add_action( 'webtero/theme/options/saved', [ $this, 'process_saved_fonts' ], 10, 2 );

        // Enqueue fonts on frontend
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_local_fonts' ], 1 );
    }

    /**
     * Schedule daily font list update
     */
    public function schedule_font_list_update() {
        if( ! is_admin() && ! wp_doing_cron() ) {
            return;
        }

        if ( ! wp_next_scheduled( 'webtero_update_font_list' ) ) {
            wp_schedule_event( time(), 'daily', 'webtero_update_font_list' );
        }

        // Update on first run if never updated
        $last_update = get_option( self::LAST_UPDATE_OPTION );

        if ( ! $last_update ) {
            $this->update_font_list();
        }
    }

    /**
     * Update font list from remote JSON
     */
    public function update_font_list() {
        $response = wp_remote_get( self::FONT_LIST_URL, [
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) {
            error_log( 'Webtero Fonts: Failed to fetch font list - ' . $response->get_error_message() );
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $fonts = json_decode( $body, true );

        if ( ! $fonts || ! is_array( $fonts ) ) {
            error_log( 'Webtero Fonts: Invalid JSON response' );
            return false;
        }

        // Save font list
        update_option( self::FONT_LIST_OPTION, $fonts, false );
        update_option( self::LAST_UPDATE_OPTION, time(), false );

        return true;
    }

    /**
     * Get available fonts
     *
     * @return array Font list
     */
    public function get_fonts(): array {
        $fonts = get_option( self::FONT_LIST_OPTION, [] );

        if ( empty( $fonts ) ) {
            // Try to update if empty
            $this->update_font_list();
            $fonts = get_option( self::FONT_LIST_OPTION, [] );
        }

        return $fonts;
    }

    /**
     * Get fonts formatted for select field options
     *
     * @return array Formatted options
     */
    public function get_font_options(): array {
        $fonts = $this->get_fonts();
        $options = [ '' => __( 'Default', 'webtero' ) ];

        foreach ( $fonts as $slug => $font_data ) {
            $options[ $slug ] = $font_data['name'] ?? $slug;
        }

        return $options;
    }

    /**
     * Process saved fonts from theme options
     *
     * @param array $options Saved options
     * @param string $instance_id Instance ID (language code or empty for general)
     */
    public function process_saved_fonts( array $options, string $instance_id ) {
        // Find all font-family fields in the options
        $font_fields = [];

        foreach ( $options as $key => $value ) {
            // Check if field contains 'font_family' and has a value
            if ( strpos( $key, 'font_family' ) !== false && ! empty( $value ) ) {
                $font_fields[] = $value;
            }
        }

        // Download each unique font
        $font_fields = array_unique( $font_fields );

        foreach ( $font_fields as $font_slug ) {
            $this->download_font( $font_slug );
        }
    }

    /**
     * Download font CSS and WOFF files
     *
     * @param string $font_slug Font slug (e.g., 'work-sans')
     * @return bool Success status
     */
    public function download_font( string $font_slug ): bool {
        if ( empty( $font_slug ) ) {
            return false;
        }

        // Create font directory
        $font_dir = $this->fonts_dir . '/' . $font_slug;
        if ( ! file_exists( $font_dir ) ) {
            wp_mkdir_p( $font_dir );
        }

        // Download CSS file
        $css_url = self::FONT_BASE_URL . $font_slug . '/font.css';
        $css_path = $font_dir . '/font.css';

        $css_response = wp_remote_get( $css_url, [ 'timeout' => 30 ] );

        if ( is_wp_error( $css_response ) ) {
            error_log( 'Webtero Fonts: Failed to download CSS for ' . $font_slug );
            return false;
        }

        $css_content = wp_remote_retrieve_body( $css_response );

        // Update CSS to use local font paths
        $css_content = $this->update_css_font_paths( $css_content, $font_slug );

        // Save CSS file
        file_put_contents( $css_path, $css_content );

        // Download WOFF files (regular and bold)
        $this->download_woff_file( $font_slug, 'regular' );
        $this->download_woff_file( $font_slug, 'bold' );

        return true;
    }

    /**
     * Download WOFF font file
     *
     * @param string $font_slug Font slug
     * @param string $variant Font variant (regular or bold)
     * @return bool Success status
     */
    private function download_woff_file( string $font_slug, string $variant ): bool {
        $woff_url = self::FONT_BASE_URL . $font_slug . '/' . $font_slug . '-' . $variant . '.woff';
        $woff_path = $this->fonts_dir . '/' . $font_slug . '/' . $font_slug . '-' . $variant . '.woff';

        // Check if file already exists
        if ( file_exists( $woff_path ) ) {
            return true;
        }

        $response = wp_remote_get( $woff_url, [
            'timeout' => 60,
            'stream' => true,
            'filename' => $woff_path
        ] );

        if ( is_wp_error( $response ) ) {
            error_log( 'Webtero Fonts: Failed to download ' . $variant . ' WOFF for ' . $font_slug );
            return false;
        }

        return true;
    }

    /**
     * Update CSS content to use local font paths
     *
     * @param string $css_content Original CSS content
     * @param string $font_slug Font slug
     * @return string Updated CSS content
     */
    private function update_css_font_paths( string $css_content, string $font_slug ): string {
        // Replace remote URLs with local URLs
        $remote_pattern = '#https://fonts\.webtero\.com/fonts/' . preg_quote( $font_slug, '#' ) . '/#';
        $local_url = $this->fonts_url . '/' . $font_slug . '/';

        $css_content = preg_replace( $remote_pattern, $local_url, $css_content );

        return $css_content;
    }

    /**
     * Get all fonts used in theme options
     *
     * @return array Array of font slugs
     */
    public function get_used_fonts(): array {
        $used_fonts = [];

        // Get general options (using active version)
        $general_options = $this->get_theme_options();
        $this->extract_fonts_from_options( $general_options, $used_fonts );

        return array_unique( $used_fonts );
    }

    /**
     * Get theme options using active version
     *
     * @return array Theme options
     */
    private function get_theme_options(): array {
        // Build option name for active version
        $base = 'webtero-theme-options';
        $active_option_name = $base . '_active';

        // Get active version timestamp
        $active_version = get_option( $active_option_name );

        if ( ! $active_version ) {
            // Fallback: try to get latest version
            $versions_option_name = $base . '_versions';
            $versions = get_option( $versions_option_name, [] );

            if ( ! empty( $versions ) ) {
                $active_version = max( array_keys( $versions ) );
            } else {
                return [];
            }
        }

        // Get options for active version
        $option_name = $base . '_' . $active_version;
        $data = get_option( $option_name, '{}' );

        return json_decode( $data, true ) ?: [];
    }

    /**
     * Extract font values from options array
     *
     * @param array $options Options array
     * @param array &$fonts Reference to fonts array
     */
    private function extract_fonts_from_options( array $options, array &$fonts ) {
        foreach ( $options as $key => $value ) {
            if ( strpos( $key, 'font_family' ) !== false && ! empty( $value ) ) {
                $fonts[] = $value;
            }
        }
    }

    /**
     * Enqueue local fonts on frontend
     */
    public function enqueue_local_fonts() {
        $used_fonts = $this->get_used_fonts();

        foreach ( $used_fonts as $font_slug ) {
            if ( empty( $font_slug ) ) {
                continue;
            }

            $css_file = $this->fonts_dir . '/' . $font_slug . '/font.css';

            if ( file_exists( $css_file ) ) {
                wp_enqueue_style(
                    'webtero-font-' . $font_slug,
                    $this->fonts_url . '/' . $font_slug . '/font.css',
                    [],
                    filemtime( $css_file )
                );
            } else {
                // Font not downloaded yet, try to download it
                $this->download_font( $font_slug );
            }
        }
    }

    /**
     * Clean up unused fonts (optional - call manually or via cron)
     */
    public function cleanup_unused_fonts() {
        $used_fonts = $this->get_used_fonts();

        // Get all font directories
        $font_dirs = glob( $this->fonts_dir . '/*', GLOB_ONLYDIR );

        foreach ( $font_dirs as $font_dir ) {
            $font_slug = basename( $font_dir );

            // If font is not in use, delete it
            if ( ! in_array( $font_slug, $used_fonts ) ) {
                $this->delete_font_directory( $font_dir );
            }
        }
    }

    /**
     * Recursively delete directory
     *
     * @param string $dir Directory path
     */
    private function delete_font_directory( string $dir ) {
        if ( ! is_dir( $dir ) ) {
            return;
        }

        $files = array_diff( scandir( $dir ), [ '.', '..' ] );

        foreach ( $files as $file ) {
            $path = $dir . '/' . $file;
            is_dir( $path ) ? $this->delete_font_directory( $path ) : unlink( $path );
        }

        rmdir( $dir );
    }
}
