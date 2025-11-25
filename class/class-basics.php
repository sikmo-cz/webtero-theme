<?php
/**
 * Basics Class
 * 
 * @package webtero
 */


declare(strict_types = 1);

namespace WT;

defined('ABSPATH') || exit;

class Basics
{
    /**
     * The Constructor
     */
    public function __construct()
    {
        add_action( 'init', array( $this, 'addMenus' ));
        
        add_action( 'wp_enqueue_scripts', array( $this, 'removeUselessFiles' ), 99999);
        add_action( 'wp_enqueue_scripts', [ $this, 'scriptsStyles' ] );

		add_action( 'add_attachment', array( $this, 'remove_image_title' ) );
    }

	// Disable automatic image title generation
	public function remove_image_title( $attachmentID ): void
	{
		$attachment = get_post( $attachmentID );
		
		if ( $attachment ) {
			$attachment->post_title = '';
			wp_update_post( $attachment );
		}
	}

    public function addMenus(): void
    {
        $locations = array(
            'main-menu' => __( 'Main', 'jtkn' ),
        );

        register_nav_menus( $locations );
    }

    public function removeUselessFiles(): void
    {
		if( is_admin() ) return;

        // Styles
        $handles = array(
            // wp
            'common',
			'wp-block-library',
			// 'wc-blocks-style',
			'classic-theme-styles',
			'global-styles',
			'woocommerce-inline',
			'wp-emoji-styles',
        );

        foreach( $handles as $handle) {
            wp_deregister_style( $handle );
            wp_dequeue_style( $handle );
        }

        // Remove WP 5.5+ inline style
        add_filter('wp_img_tag_add_auto_sizes', '__return_false');

        //Scripts
        $handles = array(
            // wp
            'wc-blocks',
        );

        foreach ( $handles as $handle ) {
            wp_deregister_script( $handle );
            wp_dequeue_script( $handle );
        }
    }

    public function scriptsStyles(): void
    {
        $distPath = THEME_DIR . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR;

        // Enqueue CSS from root
        wp_enqueue_style( 'style',
            THEME_URL . '/dist/css/style.css',
            [],
            filemtime( $distPath . 'css' . DIRECTORY_SEPARATOR . 'style.css' ),
            'all'
        );

        // Enqueue JS from root
        wp_enqueue_script( 'script',
            THEME_URL .  '/dist/js/theme.js',
            [],
            filemtime( $distPath . 'js' . DIRECTORY_SEPARATOR .'theme.js' ),
            true 
        );
    }
}