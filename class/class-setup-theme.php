<?php
/**
 * Setup Theme Class
 * 
 * @package webtero
 */

declare(strict_types = 1);

namespace WT;

defined( 'ABSPATH' ) || exit;

class Setup_Theme {
	/**
	 * Tne Constructor.
	 */
	public function __construct()
	{
        add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ] );

        add_action( 'init', function(){
            load_theme_textdomain( 'webtero', THEME_DIR . '/languages' );
        });

        add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );
    }

    /**
     * Theme setup
     */
    public function after_setup_theme()
    {
        add_theme_support( 'custom-post-types' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'responsive-embeds' );
        add_theme_support( 'post-thumbnails' );

        add_post_type_support( 'page', 'excerpt' );
    }
}