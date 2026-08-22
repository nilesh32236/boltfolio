<?php
/**
 * Project custom post type, taxonomy and meta registration.
 *
 * @package boltfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the portfolio Project post type, its taxonomy and link meta.
 */
final class Boltfolio_Projects {

	public const POST_TYPE = 'project';
	public const TAXONOMY  = 'project_type';

	/**
	 * Wire hooks.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'after_switch_theme', array( __CLASS__, 'flush_rewrite_rules' ) );
	}

	/**
	 * Register the `project` post type.
	 */
	public static function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'        => array(
					'name'               => __( 'Projects', 'boltfolio' ),
					'singular_name'      => __( 'Project', 'boltfolio' ),
					'add_new'            => __( 'Add New', 'boltfolio' ),
					'add_new_item'       => __( 'Add New Project', 'boltfolio' ),
					'edit_item'          => __( 'Edit Project', 'boltfolio' ),
					'new_item'           => __( 'New Project', 'boltfolio' ),
					'view_item'          => __( 'View Project', 'boltfolio' ),
					'search_items'       => __( 'Search Projects', 'boltfolio' ),
					'not_found'          => __( 'No projects found.', 'boltfolio' ),
					'all_items'          => __( 'All Projects', 'boltfolio' ),
					'menu_name'          => __( 'Projects', 'boltfolio' ),
				),
				'public'        => true,
				'has_archive'   => 'projects',
				'rewrite'       => array(
					'slug'       => 'projects',
					'with_front' => false,
				),
				'menu_icon'     => 'dashicons-hammer',
				'menu_position' => 21,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes' ),
				'show_in_rest'  => true,
			)
		);
	}

	/**
	 * Register the `project_type` taxonomy.
	 */
	public static function register_taxonomy(): void {
		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Project Types', 'boltfolio' ),
					'singular_name' => __( 'Project Type', 'boltfolio' ),
					'menu_name'     => __( 'Project Types', 'boltfolio' ),
				),
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'project-type',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Register project link meta exposed to REST/Gutenberg.
	 */
	public static function register_meta(): void {
		foreach ( array( 'github_url', 'live_url' ) as $key ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'sanitize_callback' => 'esc_url_raw',
					'auth_callback'     => static fn (): bool => current_user_can( 'edit_posts' ),
				)
			);
		}
	}

	/**
	 * Flush rewrite rules when the theme is activated so /projects/ resolves.
	 */
	public static function flush_rewrite_rules(): void {
		self::register_post_type();
		self::register_taxonomy();
		flush_rewrite_rules();
	}
}

Boltfolio_Projects::init();
