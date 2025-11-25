<?php
/**
 * Clear Head Class
 * 
 * @package webtero
 */

declare(strict_types = 1);

namespace WT;

defined( 'ABSPATH' ) || exit;

class Clear_Head {
	/**
	 * The Constructor.
	 */
	public function __construct() {  		
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );		
		add_filter( 'the_generator', array( $this, 'remove_wp_version' ) );
		remove_action( 'wp_head', 'wp_shortlink_wp_head');	
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10);
		remove_action( 'template_redirect', 'rest_output_link_header', 11, 0);
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10);	
		remove_action( 'wp_head', 'feed_links', 2);	
		remove_action( 'wp_head', 'feed_links_extra', 2);	
		remove_action( 'wp_head', 'rel_canonical');

		// polylang
		add_filter( 'pll_rel_hreflang_attributes', array( $this, 'filter_pll_rel_hreflang_attributes' ), 10, 1 ); 
		add_filter( 'wp_robots', array( $this, 'filter_pll_rel_hreflang_attributes' ), 9999 ); 

		// EMOJI
		add_action( 'init', array( $this, 'remove_emoji') );
	}

	public function remove_emoji() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );	
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

		// Remove from TinyMCE
		add_filter( 'tiny_mce_plugins', array( $this, 'disable_emojis_tinymce' ) );
	}

	public function disable_emojis_tinymce($plugins) {
		if (is_array($plugins)) {
			return array_diff($plugins, array('wpemoji'));
		} else {
			return array();
		}
	}

	public function wp_robots() {
		return array();
	}

	public function filter_pll_rel_hreflang_attributes( $hreflangs ) {
		return array();
	}

	public function remove_wp_version() {
		return '';
	}
}