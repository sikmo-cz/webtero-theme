<?php
/**
 * Admin Class
 * 
 * @package webtero
 */

declare(strict_types = 1);

namespace WT;

defined( 'ABSPATH' ) || exit;

class Admin {
	
	/**
	 * Tne Constructor.
	 */
	public function __construct() {		
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
     * Enqueue admin assets
     */
    public function enqueue_assets( $hook ) {    
        // Custom CSS
        wp_enqueue_style(
            'webtero-general',
            THEME_URL . '/admin/src/css/general.css',
            [],
            THEME_VERSION
        );
    }
}