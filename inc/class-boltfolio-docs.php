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
	 * Render the docs sidebar as grouped disclosures.
	 *
	 * The reference tree runs to dozens of entries, so a flat list is
	 * unusable: every first-level branch becomes a <details> that opens
	 * only when the reader is inside it. Counts are shown so a collapsed
	 * group still communicates how much it holds.
	 *
	 * @param int        $root_id      Root doc whose children form the groups.
	 * @param int        $current_id   Currently viewed doc ID.
	 * @param array<int> $ancestor_ids Ancestors of the current doc.
	 * @return void
	 */
	public static function render_grouped_nav( int $root_id, int $current_id, array $ancestor_ids ): void {
		$groups = self::doc_children( $root_id );

		if ( ! $groups ) {
			return;
		}

		foreach ( $groups as $group ) {
			$group_id    = (int) $group->ID;
			$children    = self::doc_children( $group_id );
			$is_current  = $group_id === $current_id;
			$in_path     = in_array( $group_id, $ancestor_ids, true );
			$total       = $children ? self::count_descendants( $group_id ) : 0;

			// A group with no children is a plain link, not a disclosure.
			if ( ! $children ) {
				printf(
					'<li class="%1$s"><a href="%2$s">%3$s</a></li>',
					esc_attr( $is_current ? 'page_item current_page_item' : 'page_item' ),
					esc_url( get_permalink( $group ) ),
					esc_html( self::display_title( $group->post_title ) )
				);
				continue;
			}

			printf(
				'<li><details class="docnav-group"%1$s><summary>%2$s<span class="docnav-group__count">%3$d</span></summary><div class="docnav-group__body%4$s"><ul>',
				( $is_current || $in_path ) ? ' open' : '',
				esc_html( self::display_title( $group->post_title ) ),
				(int) $total,
				self::is_file_group( $children ) ? ' docnav-group__body--files' : ''
			);

			// Link the group's own page above its children.
			printf(
				'<li class="%1$s"><a href="%2$s">%3$s</a></li>',
				esc_attr( $is_current ? 'page_item current_page_item' : 'page_item' ),
				esc_url( get_permalink( $group ) ),
				esc_html__( 'Overview', 'boltfolio' )
			);

			self::render_children( $group_id, $current_id, $ancestor_ids );

			echo '</ul></div></details></li>';
		}
	}

	/**
	 * Render descendant links for one branch.
	 *
	 * A branch with children of its own becomes a nested disclosure
	 * rather than a wall of links: the core-classes reference alone runs
	 * to forty-odd pages, so only the active branch opens.
	 *
	 * @param int        $parent_id    Branch root.
	 * @param int        $current_id   Current doc ID.
	 * @param array<int> $ancestor_ids Ancestors of the current doc.
	 * @param int        $depth        Recursion guard.
	 * @return void
	 */
	private static function render_children( int $parent_id, int $current_id, array $ancestor_ids, int $depth = 0 ): void {
		if ( $depth >= 3 ) {
			return;
		}

		foreach ( self::doc_children( $parent_id ) as $child ) {
			$child_id      = (int) $child->ID;
			$grandchildren = self::doc_children( $child_id );
			$in_path       = $child_id === $current_id || in_array( $child_id, $ancestor_ids, true );
			$classes       = 'page_item';

			if ( $child_id === $current_id ) {
				$classes .= ' current_page_item';
			} elseif ( in_array( $child_id, $ancestor_ids, true ) ) {
				$classes .= ' current_page_ancestor';
			}

			$title = self::display_title( $child->post_title );
			$file  = self::looks_like_path( $child->post_title );

			if ( ! $grandchildren ) {
				printf(
					'<li class="%1$s"><a href="%2$s"%3$s>%4$s</a></li>',
					esc_attr( $classes ),
					esc_url( get_permalink( $child ) ),
					$file ? ' data-file="1"' : '',
					esc_html( $title )
				);
				continue;
			}

			printf(
				'<li><details class="docnav-sub"%1$s><summary>%2$s<span class="docnav-group__count">%3$d</span></summary><ul>',
				$in_path ? ' open' : '',
				esc_html( $title ),
				(int) self::count_descendants( $child_id )
			);

			printf(
				'<li class="%1$s"><a href="%2$s">%3$s</a></li>',
				esc_attr( $child_id === $current_id ? 'page_item current_page_item' : 'page_item' ),
				esc_url( get_permalink( $child ) ),
				esc_html__( 'Overview', 'boltfolio' )
			);

			self::render_children( $child_id, $current_id, $ancestor_ids, $depth + 1 );

			echo '</ul></details></li>';
		}
	}

	/**
	 * A group is a "file group" when its children are source paths,
	 * which read better in a monospace face.
	 *
	 * @param array<int, WP_Post> $children Child docs.
	 * @return bool
	 */
	private static function is_file_group( array $children ): bool {
		foreach ( $children as $child ) {
			if ( self::looks_like_path( $child->post_title ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a doc title is a source path rather than prose.
	 *
	 * @param string $title Doc title.
	 * @return bool
	 */
	public static function looks_like_path( string $title ): bool {
		return (bool) preg_match( '#^[a-z0-9_\-]+(/[a-z0-9_\-]+)*\.php$#i', trim( $title ) );
	}

	/**
	 * Titles that are source paths read better as the bare filename: the
	 * directory is already implied by the group they sit in.
	 *
	 * @param string $title Raw doc title.
	 * @return string
	 */
	public static function display_title( string $title ): string {
		$title = trim( $title );

		if ( self::looks_like_path( $title ) ) {
			$parts = explode( '/', $title );

			return (string) end( $parts );
		}

		return $title;
	}

	/**
	 * Total number of descendants under a doc, so a collapsed group can
	 * still say how much it contains.
	 *
	 * @param int $parent_id Parent doc ID.
	 * @return int
	 */
	public static function count_descendants( int $parent_id ): int {
		$children = self::doc_children( $parent_id );
		$count    = count( $children );

		foreach ( $children as $child ) {
			$count += self::count_descendants( (int) $child->ID );
		}

		return $count;
	}

	/**
	 * Ancestor chain of a doc, root first.
	 *
	 * @param int $doc_id Doc ID.
	 * @return array<int, WP_Post>
	 */
	public static function ancestors( int $doc_id ): array {
		$posts = array();

		foreach ( array_reverse( get_post_ancestors( $doc_id ) ) as $ancestor_id ) {
			$ancestor = get_post( $ancestor_id );

			if ( $ancestor instanceof WP_Post ) {
				$posts[] = $ancestor;
			}
		}

		return $posts;
	}

	/**
	 * The product root a doc belongs to (the top of its own tree).
	 *
	 * @param int $doc_id Doc ID.
	 * @return WP_Post|null
	 */
	public static function root_of( int $doc_id ): ?WP_Post {
		$ancestors = get_post_ancestors( $doc_id );

		if ( ! $ancestors ) {
			$post = get_post( $doc_id );

			return $post instanceof WP_Post ? $post : null;
		}

		$root = get_post( (int) end( $ancestors ) );

		return $root instanceof WP_Post ? $root : null;
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
