<?php
/**
 * Global Blocks
 *
 * Custom post type for reusable Gutenberg block collections
 *
 * @package webtero
 */

declare(strict_types = 1);

namespace WT;

defined( 'ABSPATH' ) || exit;

class Global_Blocks {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Register Global Blocks custom post type
	 */
	public function register_post_type(): void {
		$labels = [
			'name'                  => __( 'Global Blocks', 'webtero' ),
			'singular_name'         => __( 'Global Block', 'webtero' ),
			'menu_name'             => __( 'Global Blocks', 'webtero' ),
			'add_new'               => __( 'Add New', 'webtero' ),
			'add_new_item'          => __( 'Add New Global Block', 'webtero' ),
			'new_item'              => __( 'New Global Block', 'webtero' ),
			'edit_item'             => __( 'Edit Global Block', 'webtero' ),
			'view_item'             => __( 'View Global Block', 'webtero' ),
			'all_items'             => __( 'All Global Blocks', 'webtero' ),
			'search_items'          => __( 'Search Global Blocks', 'webtero' ),
			'not_found'             => __( 'No global blocks found.', 'webtero' ),
			'not_found_in_trash'    => __( 'No global blocks found in Trash.', 'webtero' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true, // Enable Gutenberg
			'rest_base'           => 'global_blocks',
			'query_var'           => true,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-layout',
			'supports'            => [ 'title', 'editor' ],
			'show_in_admin_bar'   => true,
		];

		register_post_type( 'global_blocks', $args );
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'webtero/v1',
			'/posts/autocomplete',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'autocomplete_posts' ],
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
				'args'                => [
					'search'     => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'post_types' => [
						'type'              => 'string',
						'default'           => 'global_blocks',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'per_page'   => [
						'type'    => 'integer',
						'default' => 20,
					],
				],
			]
		);
	}

	/**
	 * Autocomplete endpoint for post search
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return array Posts array
	 */
	public function autocomplete_posts( $request ): array {
		$search     = $request->get_param( 'search' );
		$post_types = $request->get_param( 'post_types' );
		$per_page   = absint( $request->get_param( 'per_page' ) );

		// Convert comma-separated post types to array
		$post_types = array_map( 'trim', explode( ',', $post_types ) );

		$args = [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => min( $per_page, 50 ), // Max 50 results
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		// Add search if provided
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$query = new \WP_Query( $args );

		$results = [];
		foreach ( $query->posts as $post ) {
			$results[] = [
				'id'        => $post->ID,
				'title'     => $post->post_title,
				'post_type' => $post->post_type,
				'edit_link' => get_edit_post_link( $post->ID, 'raw' ),
			];
		}

		return $results;
	}
}
