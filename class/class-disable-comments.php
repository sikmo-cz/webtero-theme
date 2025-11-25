<?php
/**
 * Disable Comments Class
 * 
 * @package webtero
 */

declare(strict_types = 1);

namespace WT;

defined( 'ABSPATH' ) || exit;

class Disable_Comments {
	/**
	 * The Constructor.
	 */
	public function __construct() {
		// Check if comments should be disabled
		if ( ! $this->should_disable_comments() ) {
			return;
		}
		
		add_action( 'admin_init', array( $this, 'hide_comments' ) );

        // Close comments on the front-end
        add_filter( 'comments_open', '__return_false', 20, 2 );
        add_filter( 'pings_open', '__return_false', 20, 2 );

        // Hide existing comments
        add_filter( 'comments_array', '__return_empty_array', 10, 2);

        // Remove comments page in menu
        add_action( 'admin_menu', function () {
            remove_menu_page( 'edit-comments.php' );
        });

        // Remove comments links from admin bar
        add_action( 'init', function () {
            if ( is_admin_bar_showing() ) {
                remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
            }
        });

        add_action( 'admin_bar_menu', array( $this, 'remove_admin_bar_options' ), 998 );
	}
	
	/**
	 * Check if comments should be disabled
	 * 
	 * @return bool
	 */
	private function should_disable_comments(): bool {
		// Check constant first (highest priority)
		if ( defined( 'WP_DISABLE_COMMENTS' ) ) {
			return (bool) WP_DISABLE_COMMENTS;
		}
		
		// Check filter (allows dynamic control)
		return apply_filters( 'webtero/theme/disable_comments', true );
	}

    public function remove_admin_bar_options() {
		global $wp_admin_bar;
		
		$wp_admin_bar->remove_menu( 'comments' );
	}

    public function hide_comments() {
        // Redirect any user trying to access comments page
        global $pagenow;
        
        if ($pagenow === 'edit-comments.php') {
            wp_redirect(admin_url());
            exit;
        }

        // Remove comments metabox from dashboard
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

        // Disable support for comments and trackbacks in post types
        foreach (get_post_types() as $post_type) {
            if (post_type_supports($post_type, 'comments')) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }
}