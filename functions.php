<?php

    declare(strict_types = 1);
   
    defined( 'ABSPATH' ) || exit;  // Prevent direct access

    // Define theme constants
    define( 'THEME_VERSION', '0.0.2');
    define( 'THEME_DIR', get_template_directory() );
    define( 'THEME_URL', get_template_directory_uri() );
    define( 'THEME_CLASSES_DIR', THEME_DIR . '/class/');

    require_once THEME_DIR . '/includes/functions.php';

    // Register the autoloader
    spl_autoload_register( 'theme_autoloader' );

    if( class_exists( 'WT\\Setup_Theme' ) ) new WT\Setup_Theme();
    if( class_exists( 'WT\\Helpers' ) ) $WT_Helpers = new WT\Helpers();
    if( class_exists( 'WT\\Gutenberg_Block_Options' ) ) new WT\Gutenberg_Block_Options();
    if( class_exists( 'WT\\Local_Fonts' ) ) new WT\Local_Fonts();
    if( class_exists( 'WT\\Theme_Options' ) )  new WT\Theme_Options();
    if( class_exists( 'WT\\Disable_Comments' ) ) new WT\Disable_Comments();
    if( class_exists( 'WT\\Global_Blocks' ) ) new WT\Global_Blocks();

    // Initialize Block Registry (must be global so REST API can access it)
    if( class_exists( 'WT\\Blocks\\Block_Registry' ) ) $WT_Block_Registry = new WT\Blocks\Block_Registry();

    if( ! is_admin() ) {
        if( class_exists( 'WT\\Clear_Head' ) ) new WT\Clear_Head();
        if( class_exists( 'WT\\Basics' ) ) new WT\Basics();
    }

    if( is_admin() ) {
        if( class_exists( 'WT\\Admin' ) ) new WT\Admin();
    }