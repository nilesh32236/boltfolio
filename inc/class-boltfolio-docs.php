<?php
/**
 * Documentation custom post type: hierarchical docs with per-product taxonomy.
 *
 * Registers the `docs` CPT (block editor, hierarchical, per-product
 * organization via the `doc_project` taxonomy), the admin list columns,
 * and the default "Performance Optimisation" product term.
 *
 * @package boltfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Boltfolio_Docs {

	public const POST_TYPE         = 'docs';
	public const TAXONOMY          = 'doc_project';
	public const DEFAULT_TERM_SLUG = 'performance-optimisation';

	/**
	 * Wire hooks.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
		add_action( 'init', array( __CLASS__, 'ensure_default_term' ), 20 );
		add_filter( 'manage_docs_posts_columns', array( __CLASS__, 'admin_columns' ) );
		add_action( 'manage_docs_posts_custom_column', array( __CLASS__, 'admin_column_content' ), 10, 2 );
	}

	/**
	 * Register the hierarchical docs CPT.
	 */
	public static function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Docs', 'boltfolio' ),
					'singular_name'      => __( 'Doc', 'boltfolio' ),
					'menu_name'          => __( 'Docs', 'boltfolio' ),
					'all_items'          => __( 'All Docs', 'boltfolio' ),
					'add_new'            => __( 'Add New', 'boltfolio' ),
					'add_new_item'       => __( 'Add New Doc', 'boltfolio' ),
					'edit_item'          => __( 'Edit Doc', 'boltfolio' ),
					'new_item'           => __( 'New Doc', 'boltfolio' ),
					'view_item'          => __( 'View Doc', 'boltfolio' ),
					'view_items'         => __( 'View Docs', 'boltfolio' ),
					'search_items'       => __( 'Search Docs', 'boltfolio' ),
					'not_found'          => __( 'No docs found.', 'boltfolio' ),
					'not_found_in_trash' => __( 'No docs found in Trash.', 'boltfolio' ),
					'parent_item_colon'  => __( 'Parent Doc:', 'boltfolio' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'rest_base'           => 'docs',
				'hierarchical'        => true,
				'has_archive'         => true,
				'rewrite'             => array(
					'slug'       => 'docs',
					'with_front' => false,
				),
				'menu_position'       => 21,
				'menu_icon'           => 'dashicons-book',
				'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'revisions', 'page-attributes' ),
				'exclude_from_search' => false,
				'capability_type'     => 'page',
				'map_meta_cap'        => true,
				'template_lock'       => false,
			)
		);
	}

	/**
	 * Register the per-product docs taxonomy.
	 */
	public static function register_taxonomy(): void {
		register_taxonomy(
			self::TAXONOMY,
			array( self::POST_TYPE ),
			array(
				'labels'            => array(
					'name'          => __( 'Doc projects', 'boltfolio' ),
					'singular_name' => __( 'Doc project', 'boltfolio' ),
					'menu_name'     => __( 'Doc projects', 'boltfolio' ),
					'search_items'  => __( 'Search doc projects', 'boltfolio' ),
					'all_items'     => __( 'All doc projects', 'boltfolio' ),
					'edit_item'     => __( 'Edit doc project', 'boltfolio' ),
					'update_item'   => __( 'Update doc project', 'boltfolio' ),
					'add_new_item'  => __( 'Add new doc project', 'boltfolio' ),
					'new_item_name' => __( 'New doc project name', 'boltfolio' ),
				),
				'hierarchical'      => false,
				'public'            => true,
				'publicly_queryable' => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'doc-project',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Ensure the default product term exists (created once).
	 */
	public static function ensure_default_term(): void {
		if ( ! taxonomy_exists( self::TAXONOMY ) ) {
			return;
		}

		if ( term_exists( self::DEFAULT_TERM_SLUG, self::TAXONOMY ) ) {
			return;
		}

		wp_insert_term(
			__( 'Performance Optimisation', 'boltfolio' ),
			self::TAXONOMY,
			array( 'slug' => self::DEFAULT_TERM_SLUG )
		);
	}

	/**
	 * Admin list columns. The product term column is provided natively via
	 * show_admin_column on the taxonomy; only menu order is added here.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public static function admin_columns( array $columns ): array {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['menu_order'] = __( 'Order', 'boltfolio' );
			}
		}

		return $new;
	}

	/**
	 * Render the menu order column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function admin_column_content( string $column, int $post_id ): void {
		if ( 'menu_order' !== $column ) {
			return;
		}

		echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
	}

	/**
	 * Render the docs sidebar navigation tree (children of a parent doc),
	 * in document order, as `<li>` elements compatible with the existing
	 * `.docs-nav` styles (current_page_item / current_page_ancestor).
	 *
	 * @param int           $parent_id    Parent doc ID whose children are listed.
	 * @param int           $current_id   Currently viewed doc ID.
	 * @param array<int>    $ancestor_ids Ancestor IDs of the current doc.
	 * @param int           $depth        Current tree depth.
	 * @param bool          $include_root Whether to render the parent itself as the first item.
	 * @return void
	 */
	public static function render_nav_tree( int $parent_id, int $current_id, array $ancestor_ids, int $depth = 0, bool $include_root = false ): void {
		if ( $depth >= 4 ) {
			return;
		}

		if ( $include_root ) {
			$classes = 'page_item';

			if ( $current_id === $parent_id ) {
				$classes .= ' current_page_item';
			} elseif ( in_array( $parent_id, $ancestor_ids, true ) ) {
				$classes .= ' current_page_ancestor';
			}

			printf(
				'<li class="%1$s"><a href="%2$s">%3$s</a>',
				esc_attr( $classes ),
				esc_url( get_permalink( $parent_id ) ),
				esc_html( get_the_title( $parent_id ) )
			);

			ob_start();
			self::render_nav_tree( $parent_id, $current_id, $ancestor_ids, $depth + 1 );
			$nested = (string) ob_get_clean();

			if ( '' !== $nested ) {
				echo '<ul class="children">' . $nested . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- composed of escaped fragments.
			}

			echo '</li>';
			return;
		}

		foreach ( self::doc_children( $parent_id ) as $child ) {
			$child_id = (int) $child->ID;
			$classes  = 'page_item';

			if ( $child_id === $current_id ) {
				$classes .= ' current_page_item';
			} elseif ( in_array( $child_id, $ancestor_ids, true ) ) {
				$classes .= ' current_page_ancestor';
			}

			printf(
				'<li class="%1$s"><a href="%2$s">%3$s</a>',
				esc_attr( $classes ),
				esc_url( get_permalink( $child ) ),
				esc_html( get_the_title( $child ) )
			);

			ob_start();
			self::render_nav_tree( $child_id, $current_id, $ancestor_ids, $depth + 1 );
			$nested = (string) ob_get_clean();

			if ( '' !== $nested ) {
				echo '<ul class="children">' . $nested . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- composed of escaped fragments.
			}

			echo '</li>';
		}
	}

	/**
	 * Published child docs of a parent, ordered by menu order then title.
	 *
	 * @param int $parent_id Parent doc ID.
	 * @return array<int, WP_Post>
	 */
	private static function doc_children( int $parent_id ): array {
		return get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post_parent'    => $parent_id,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			)
		);
	}
}

Boltfolio_Docs::init();
