<?php
/**
 * Helpers Class
 * 
 * @package webtero
 */


declare(strict_types = 1);

namespace WT;

defined( 'ABSPATH' ) || exit;

class Helpers {	
	public static function getHomeURI(): string
	{
		return esc_url( home_url( '/' ) );
	}

	public static function getInlineSVG( string $name, string $default_path = '/assets/images/', string $customPath = null ): string
	{
		$customPath = ( is_null( $customPath ) ) ? get_stylesheet_directory() : $customPath;
		$file   	= $customPath . "{$default_path}{$name}";
        $output 	= '';

		if ( file_exists( $file ) ) {
            ob_start();
			    
                include $file;

            $output = ob_get_contents();
            ob_end_clean();
		}

        return $output;
	}

	public static function getMenu( string $location = 'primary', string $css_class_prefix = 'navigation', string $menu_id = 'site-navigation' ): string 
	{
		$output = '';

		$args = array(
			'theme_location' => $location,
			'menu_class'     => $css_class_prefix . '__menu',
			'menu_id'        => $menu_id,
			'container'      => false,
			// 'walker'         => new Walker_Nav_Menu_BEM( $css_class_prefix ),
		);

		ob_start();

		if ( has_nav_menu( $location ) ) {
			wp_nav_menu( $args );
		} else {
			printf( __( 'Please assign a menu to this location (%s)', 'webtero' ), $location );
		}

		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}
}