<?php
/**
 * Theme functions.
 *
 * @package BHDT_Wirecutter_Clone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Debug route for function menu
function bhdt_wirecutter_debug_route() {
	if ( strpos( $_SERVER['REQUEST_URI'], '/debug-function-menu' ) === 0 ) {
		// Allow access without authentication for debugging
		$pages = get_posts( array(
			'post_type'      => 'page',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_bhdt_page_function_menu_parent_url',
					'compare' => 'EXISTS',
				),
			),
		) );

		echo '<div style="background:#f5f5f5; padding:20px; margin:20px; border:1px solid #ddd; font-family:monospace; font-size:13px; line-height:1.6;">';
		echo '<h2>🔍 FUNCTION MENU DEBUG</h2>';
		echo '<hr>';
		echo '<h3>Pages với meta function menu:</h3>';
		
		foreach ( $pages as $page ) {
			$parent_url = get_post_meta( $page->ID, '_bhdt_page_function_menu_parent_url', true );
			$parent_slug = get_post_meta( $page->ID, '_bhdt_page_function_menu_parent_slug', true );
			echo '<div style="background:white; padding:10px; margin:10px 0; border-left:3px solid #0073aa;">';
			echo '<strong>ID ' . $page->ID . ':</strong> ' . esc_html( $page->post_title ) . '<br>';
			echo 'Parent URL: <code>' . esc_html( $parent_url ) . '</code><br>';
			echo 'Parent Slug: <code>' . esc_html( $parent_slug ) . '</code><br>';
			echo 'Page URL: <a href="' . esc_url( get_permalink( $page->ID ) ) . '" target="_blank">' . esc_html( get_permalink( $page->ID ) ) . '</a>';
			echo '</div>';
		}

		echo '<hr>';
		echo '<h3>Children Map (from get_page_link_children_map):</h3>';
		
		if ( function_exists( 'bhdt_wirecutter_get_page_link_children_map' ) ) {
			$map = bhdt_wirecutter_get_page_link_children_map( '_bhdt_page_function_menu_parent_url' );
			echo '<pre style="background:white; padding:10px; border:1px solid #ddd; overflow-x:auto;">';
			foreach ( $map as $key => $children ) {
				echo '<strong>' . esc_html( $key ) . '</strong> (' . count( $children ) . " children)\n";
				foreach ( $children as $child ) {
					echo "  - {$child['label']} ({$child['url']})\n";
				}
			}
			echo '</pre>';
		}

		echo '<hr>';
		echo '<h3>Function Menu Blocks (after merge):</h3>';
		
		if ( function_exists( 'bhdt_wirecutter_get_function_menu_blocks' ) ) {
			$blocks = bhdt_wirecutter_get_function_menu_blocks();
			echo '<pre style="background:white; padding:10px; border:1px solid #ddd; overflow-x:auto;">';
			foreach ( $blocks as $block ) {
				$children_count = count( $block['children'] ?? array() );
				echo '<strong>' . esc_html( $block['label_vi'] ) . '</strong> (' . $children_count . " children)\n";
				if ( ! empty( $block['children'] ) ) {
					foreach ( $block['children'] as $child ) {
						echo "  - {$child['label_vi']} ({$child['url']})\n";
					}
				}
			}
			echo '</pre>';
		}
		
		echo '</div>';
		exit;
	}
}
add_action( 'template_redirect', 'bhdt_wirecutter_debug_route', 5 );

function bhdt_wirecutter_theme_setup() {
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );

	register_nav_menus(
		array(
			'primary' => __( 'Menu chính', 'bhdt-wirecutter' ),
		)
	);
	
	// Load theme translations
	load_theme_textdomain( 'bhdt-wirecutter', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'bhdt_wirecutter_theme_setup' );

function bhdt_wirecutter_register_editorial_post_types() {
	register_post_type(
		'bhdt_review',
		array(
			'labels' => array(
				'name'               => 'Review',
				'singular_name'      => 'Review',
				'menu_name'          => 'Review',
				'all_items'          => 'Review',
				'add_new'            => 'Thêm bài review',
				'add_new_item'       => 'Thêm bài review',
				'name_admin_bar'     => 'Review',
				'edit_item'          => __( 'Sửa bài review', 'bhdt-wirecutter' ),
				'new_item'           => __( 'Bài review mới', 'bhdt-wirecutter' ),
				'view_item'          => __( 'Xem bài review', 'bhdt-wirecutter' ),
				'search_items'       => __( 'Tìm bài review', 'bhdt-wirecutter' ),
				'not_found'          => __( 'Không có bài review nào', 'bhdt-wirecutter' ),
				'not_found_in_trash' => __( 'Không có bài review trong thùng rác', 'bhdt-wirecutter' ),
			),
			'public'             => true,
			'has_archive'        => true,
			'rewrite'            => array( 'slug' => 'review' ),
			'show_in_rest'       => true,
			'menu_position'      => 22,
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ),
			'taxonomies'         => array( 'category', 'post_tag' ),
			'show_in_nav_menus'  => true,
			'publicly_queryable' => true,
		)
	);

	register_post_type(
		'bhdt_comparison',
		array(
			'labels' => array(
				'name'               => __( 'Bài so sánh', 'bhdt-wirecutter' ),
				'singular_name'      => __( 'Bài so sánh', 'bhdt-wirecutter' ),
				'add_new'            => __( 'Thêm mới', 'bhdt-wirecutter' ),
				'add_new_item'       => __( 'Thêm bài so sánh mới', 'bhdt-wirecutter' ),
				'edit_item'          => __( 'Sửa bài so sánh', 'bhdt-wirecutter' ),
				'new_item'           => __( 'Bài so sánh mới', 'bhdt-wirecutter' ),
				'view_item'          => __( 'Xem bài so sánh', 'bhdt-wirecutter' ),
				'search_items'       => __( 'Tìm bài so sánh', 'bhdt-wirecutter' ),
				'not_found'          => __( 'Không có bài so sánh nào', 'bhdt-wirecutter' ),
				'not_found_in_trash' => __( 'Không có bài so sánh trong thùng rác', 'bhdt-wirecutter' ),
				'menu_name'          => __( 'So sánh', 'bhdt-wirecutter' ),
			),
			'public'             => true,
			'has_archive'        => true,
			'rewrite'            => array( 'slug' => 'so-sanh' ),
			'show_in_rest'       => true,
			'menu_position'      => 23,
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ),
			'taxonomies'         => array( 'category', 'post_tag' ),
			'show_in_nav_menus'  => true,
			'publicly_queryable' => true,
		)
	);
}
add_action( 'init', 'bhdt_wirecutter_register_editorial_post_types', 9 );

function bhdt_wirecutter_maybe_flush_rewrite_rules() {
	$rewrite_version = '2026-05-18-1';
	$saved_version   = (string) get_option( 'bhdt_wire_rewrite_rules_version', '' );

	if ( $rewrite_version === $saved_version ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( 'bhdt_wire_rewrite_rules_version', $rewrite_version, false );
}
add_action( 'init', 'bhdt_wirecutter_maybe_flush_rewrite_rules', 20 );

function bhdt_wirecutter_home_block_post_types() {
	return array(
		'post'            => __( 'Bài viết thường', 'bhdt-wirecutter' ),
		'bhdt_review'     => __( 'Bài review', 'bhdt-wirecutter' ),
		'bhdt_comparison' => __( 'Bài so sánh', 'bhdt-wirecutter' ),
	);
}

function bhdt_wirecutter_normalize_home_block_post_types( $value ) {
	$allowed_post_types = array_keys( bhdt_wirecutter_home_block_post_types() );
	$post_types = is_array( $value ) ? $value : array( $value );
	$post_types = array_values(
		array_unique(
			array_filter(
				array_map(
					static function ( $post_type ) {
						return sanitize_key( (string) $post_type );
					},
					$post_types
				)
			)
		)
	);
	$post_types = array_values( array_intersect( $post_types, $allowed_post_types ) );

	return empty( $post_types ) ? array( 'post' ) : $post_types;
}

function bhdt_wirecutter_get_home_block_post_types_from_row( $row ) {
	if ( ! is_array( $row ) ) {
		return array( 'post' );
	}

	if ( isset( $row['post_types'] ) ) {
		return bhdt_wirecutter_normalize_home_block_post_types( $row['post_types'] );
	}

	return bhdt_wirecutter_normalize_home_block_post_types( $row['post_type'] ?? 'post' );
}

function bhdt_wirecutter_format_home_block_post_type_labels( $post_types ) {
	$choices = bhdt_wirecutter_home_block_post_types();
	$labels = array();

	foreach ( bhdt_wirecutter_normalize_home_block_post_types( $post_types ) as $post_type ) {
		$labels[] = $choices[ $post_type ] ?? $post_type;
	}

	return implode( ', ', $labels );
}

function bhdt_wirecutter_clean_home_block_slug_aliases( $value ) {
	$aliases = is_array( $value ) ? $value : explode( ',', (string) $value );
	$aliases = array_map(
		static function ( $alias ) {
			return sanitize_title( (string) $alias );
		},
		$aliases
	);

	return array_values( array_unique( array_filter( $aliases ) ) );
}

function bhdt_wirecutter_clean_home_block_layout( $value ) {
	$layout = (int) $value;
	if ( $layout < 1 || $layout > 3 ) {
		$layout = 1;
	}

	return $layout;
}

function bhdt_wirecutter_home_block_slug_suggestions() {
	$post_type_choices = bhdt_wirecutter_home_block_post_types();
	$base_terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $base_terms ) ) {
		$base_terms = array();
	}

	$terms_by_id = array();
	foreach ( $base_terms as $term ) {
		$terms_by_id[ (int) $term->term_id ] = $term;
	}

	$saved_rows = get_option( 'bhdt_wire_home_category_groups', array() );
	if ( ! is_array( $saved_rows ) ) {
		$saved_rows = array();
	}

	global $wpdb;
	$suggestions = array();

	foreach ( $post_type_choices as $post_type => $label ) {
		$post_type_key = sanitize_key( $post_type );
		$sql = $wpdb->prepare(
			"SELECT DISTINCT tt.term_id
			 FROM {$wpdb->term_relationships} tr
			 INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			 INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id
			 WHERE tt.taxonomy = 'category'
			   AND p.post_type = %s
			   AND p.post_status = 'publish'",
			$post_type_key
		);

		$term_ids = array_map( 'intval', (array) $wpdb->get_col( $sql ) );
		$bucket = array();

		foreach ( $term_ids as $term_id ) {
			if ( isset( $terms_by_id[ $term_id ] ) ) {
				$term = $terms_by_id[ $term_id ];
				$bucket[ $term->slug ] = array(
					'slug'  => $term->slug,
					'label' => $term->name . ' (' . $term->slug . ')',
				);
			}
		}

		foreach ( $saved_rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$row_post_types = bhdt_wirecutter_get_home_block_post_types_from_row( $row );
			$row_slug = isset( $row['slug'] ) ? sanitize_title( $row['slug'] ) : '';
			if ( ! in_array( $post_type_key, $row_post_types, true ) || '' === $row_slug ) {
				continue;
			}

			if ( ! isset( $bucket[ $row_slug ] ) ) {
				$term = get_term_by( 'slug', $row_slug, 'category' );
				$label_text = $row_slug;
				if ( $term && ! is_wp_error( $term ) ) {
					$label_text = $term->name . ' (' . $term->slug . ')';
				}
				$bucket[ $row_slug ] = array(
					'slug'  => $row_slug,
					'label' => $label_text,
				);
			}
		}

		if ( empty( $bucket ) ) {
			foreach ( $base_terms as $term ) {
				$bucket[ $term->slug ] = array(
					'slug'  => $term->slug,
					'label' => $term->name . ' (' . $term->slug . ')',
				);
			}
		}

		$suggestions[ $post_type_key ] = array_values( $bucket );
	}

	return $suggestions;
}

function bhdt_wirecutter_default_home_category_groups() {
	return array(
		array(
			'name_vi' => 'Linh kiện điện tử',
			'name_en' => 'Electronics',
			'desc_vi' => 'Gợi ý linh kiện, module và thiết bị đo cho dự án thực tế.',
			'desc_en' => 'Recommended components, modules, and measuring tools for real projects.',
			'post_type' => 'post',
			'post_types' => array( 'post' ),
			'slug'    => 'linh-kien-dien-tu',
			'order'   => 10,
			'enabled' => 1,
		),
		array(
			'name_vi' => 'Robot thông minh',
			'name_en' => 'Smart Robots',
			'desc_vi' => 'Thiết kế robot, điều khiển chuyển động và cảm biến thông minh.',
			'desc_en' => 'Robot design, motion control, and smart sensor integrations.',
			'post_type' => 'bhdt_review',
			'post_types' => array( 'bhdt_review' ),
			'slug'    => 'robot-thong-minh',
			'order'   => 20,
			'enabled' => 1,
		),
		array(
			'name_vi' => 'Thiết bị nhúng',
			'name_en' => 'Embedded Devices',
			'desc_vi' => 'Vi điều khiển, firmware và giải pháp nhúng tối ưu hiệu năng.',
			'desc_en' => 'Microcontrollers, firmware, and performance-focused embedded solutions.',
			'post_type' => 'bhdt_comparison',
			'post_types' => array( 'bhdt_comparison' ),
			'slug'    => 'thiet-bi-nhung',
			'order'   => 30,
			'enabled' => 1,
		),
	);
}

function bhdt_wirecutter_get_home_category_groups() {
	$saved = get_option( 'bhdt_wire_home_category_groups', array() );
	if ( ! is_array( $saved ) || empty( $saved ) ) {
		$saved = bhdt_wirecutter_default_home_category_groups();
	}

	$groups = array();
	$is_en  = function_exists( 'pll_current_language' ) && 'en' === pll_current_language( 'slug' );
	$allowed_post_types = array_keys( bhdt_wirecutter_home_block_post_types() );

	foreach ( $saved as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$name_vi = isset( $item['name_vi'] ) ? sanitize_text_field( $item['name_vi'] ) : '';
		$name_en = isset( $item['name_en'] ) ? sanitize_text_field( $item['name_en'] ) : '';
		$desc_vi = isset( $item['desc_vi'] ) ? sanitize_text_field( $item['desc_vi'] ) : '';
		$desc_en = isset( $item['desc_en'] ) ? sanitize_text_field( $item['desc_en'] ) : '';
		$post_types = bhdt_wirecutter_get_home_block_post_types_from_row( $item );
		$post_type = $post_types[0];
		$slug    = isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : '';
		$slug_aliases = bhdt_wirecutter_clean_home_block_slug_aliases( $item['slug_aliases'] ?? array() );
		$layout = bhdt_wirecutter_clean_home_block_layout( $item['layout'] ?? 1 );
		$order   = isset( $item['order'] ) ? (int) $item['order'] : 0;
		$enabled = isset( $item['enabled'] ) ? (int) $item['enabled'] : 1;

		if ( '' === $name_vi ) {
			continue;
		}

		if ( '' === $slug ) {
			$slug = sanitize_title( $name_vi );
		}

		$label = $name_vi;
		if ( $is_en && '' !== $name_en ) {
			$label = $name_en;
		}

		$subtitle = $desc_vi;
		if ( $is_en && '' !== $desc_en ) {
			$subtitle = $desc_en;
		}

		if ( 1 !== $enabled ) {
			continue;
		}

		$groups[] = array(
			'name'  => $name_vi,
			'label' => $label,
			'subtitle' => $subtitle,
			'post_type' => $post_type,
			'post_types' => $post_types,
			'slug'  => $slug,
			'slug_aliases' => $slug_aliases,
			'layout' => $layout,
			'order' => $order,
		);
	}

	usort(
		$groups,
		static function ( $a, $b ) {
			return (int) $a['order'] <=> (int) $b['order'];
		}
	);

	if ( empty( $groups ) ) {
		$defaults = bhdt_wirecutter_default_home_category_groups();
		foreach ( $defaults as $item ) {
			$groups[] = array(
				'name'  => $item['name_vi'],
				'label' => $is_en ? $item['name_en'] : $item['name_vi'],
				'subtitle' => $is_en ? ( $item['desc_en'] ?? '' ) : ( $item['desc_vi'] ?? '' ),
				'post_type' => isset( $item['post_type'] ) && in_array( $item['post_type'], $allowed_post_types, true ) ? $item['post_type'] : 'post',
				'post_types' => bhdt_wirecutter_get_home_block_post_types_from_row( $item ),
				'slug'  => $item['slug'],
				'slug_aliases' => bhdt_wirecutter_clean_home_block_slug_aliases( $item['slug_aliases'] ?? array() ),
				'layout' => bhdt_wirecutter_clean_home_block_layout( $item['layout'] ?? 1 ),
				'order' => (int) $item['order'],
			);
		}
	}

	return $groups;
}

function bhdt_wirecutter_build_home_category_block_key( $post_type, $slug ) {
	$post_type = sanitize_key( (string) $post_type );
	$slug      = sanitize_title( (string) $slug );

	if ( '' === $post_type || '' === $slug ) {
		return '';
	}

	return $post_type . '|' . $slug;
}

function bhdt_wirecutter_get_home_category_block_choices() {
	$choices = array();

	foreach ( bhdt_wirecutter_get_home_category_groups() as $group ) {
		$post_types = bhdt_wirecutter_normalize_home_block_post_types( $group['post_types'] ?? ( $group['post_type'] ?? 'post' ) );
		$post_type = $post_types[0];
		$slug      = sanitize_title( $group['slug'] ?? '' );
		$key       = $slug;

		if ( '' === $key ) {
			continue;
		}

		$legacy_keys = array();
		$choice_slugs = array_merge( array( $slug ), bhdt_wirecutter_clean_home_block_slug_aliases( $group['slug_aliases'] ?? array() ) );
		foreach ( array_unique( array_filter( $choice_slugs ) ) as $choice_slug ) {
			foreach ( $post_types as $choice_post_type ) {
				$legacy_key = bhdt_wirecutter_build_home_category_block_key( $choice_post_type, $choice_slug );
				if ( '' !== $legacy_key ) {
					$legacy_keys[] = $legacy_key;
				}
			}
		}

		$choices[] = array(
			'key'       => $key,
			'post_type' => $post_type,
			'post_types' => $post_types,
			'slug'      => $slug,
			'slug_aliases' => bhdt_wirecutter_clean_home_block_slug_aliases( $group['slug_aliases'] ?? array() ),
			'legacy_keys' => array_values( array_unique( $legacy_keys ) ),
			'label'     => (string) ( $group['label'] ?? $group['name'] ?? $slug ),
			'subtitle'  => (string) ( $group['subtitle'] ?? '' ),
			'option'    => trim(
				(string) ( $group['label'] ?? $group['name'] ?? $slug ) . ' (' . bhdt_wirecutter_format_home_block_post_type_labels( $post_types ) . ')'
			),
		);
	}

	return $choices;
}

function bhdt_wirecutter_find_home_category_block_by_key( $key ) {
	$key = sanitize_text_field( (string) $key );
	if ( '' === $key ) {
		return null;
	}

	foreach ( bhdt_wirecutter_get_home_category_block_choices() as $choice ) {
		if ( isset( $choice['key'] ) && $choice['key'] === $key ) {
			return $choice;
		}

		if ( isset( $choice['legacy_keys'] ) && is_array( $choice['legacy_keys'] ) && in_array( $key, $choice['legacy_keys'], true ) ) {
			return $choice;
		}
	}

	return null;
}

function bhdt_wirecutter_home_category_block_url( $group ) {
	$slug = is_array( $group ) ? sanitize_title( $group['slug'] ?? '' ) : sanitize_title( (string) $group );
	if ( '' === $slug ) {
		return home_url( '/' );
	}

	return trailingslashit( bhdt_wirecutter_current_lang_home_url() . $slug );
}

function bhdt_wirecutter_get_home_category_block_public_slug( $target_group ) {
	if ( ! is_array( $target_group ) ) {
		return '';
	}

	$target_slug = sanitize_title( $target_group['slug'] ?? '' );
	if ( '' !== $target_slug ) {
		return $target_slug;
	}

	$used_slugs = array();

	foreach ( bhdt_wirecutter_get_home_category_groups() as $group ) {
		$base_slug = sanitize_title( $group['label'] ?? $group['name'] ?? $group['slug'] ?? '' );
		if ( '' === $base_slug ) {
			$base_slug = sanitize_title( $group['slug'] ?? '' );
		}

		if ( '' === $base_slug ) {
			continue;
		}

		$public_slug = $base_slug;
		$suffix = 2;
		while ( in_array( $public_slug, $used_slugs, true ) ) {
			$public_slug = $base_slug . '-' . $suffix;
			$suffix++;
		}
		$used_slugs[] = $public_slug;

		if ( $target_slug === sanitize_title( $group['slug'] ?? '' ) ) {
			return $public_slug;
		}
	}

	return sanitize_title( $target_group['label'] ?? $target_group['name'] ?? $target_group['slug'] ?? '' );
}

function bhdt_wirecutter_get_home_category_group_by_slug( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug ) {
		return null;
	}

	foreach ( bhdt_wirecutter_get_home_category_groups() as $group ) {
		$public_slug = bhdt_wirecutter_get_home_category_block_public_slug( $group );
		$slug_aliases = bhdt_wirecutter_clean_home_block_slug_aliases( $group['slug_aliases'] ?? array() );
		if ( $slug === $public_slug || $slug === sanitize_title( $group['slug'] ?? '' ) || in_array( $slug, $slug_aliases, true ) ) {
			return $group;
		}
	}

	return null;
}

function bhdt_wirecutter_get_home_category_block_key_variants( $group ) {
	if ( ! is_array( $group ) ) {
		return array();
	}

	$post_types = bhdt_wirecutter_normalize_home_block_post_types( $group['post_types'] ?? ( $group['post_type'] ?? 'post' ) );
	$base_slugs = array(
		$group['slug'] ?? '',
		$group['label'] ?? '',
		$group['name'] ?? '',
		$group['name_vi'] ?? '',
		$group['name_en'] ?? '',
	);
	$base_slugs = array_merge( $base_slugs, bhdt_wirecutter_clean_home_block_slug_aliases( $group['slug_aliases'] ?? array() ) );
	$base_slugs = array_values(
		array_unique(
			array_filter(
				array_map(
					static function ( $value ) {
						return sanitize_title( (string) $value );
					},
					$base_slugs
				)
			)
		)
	);

	$keys = $base_slugs;
	foreach ( $base_slugs as $base_slug ) {
		foreach ( $post_types as $post_type ) {
			$legacy_key = bhdt_wirecutter_build_home_category_block_key( $post_type, $base_slug );
			if ( '' !== $legacy_key ) {
				$keys[] = $legacy_key;
			}
		}
	}

	return array_values( array_unique( $keys ) );
}

function bhdt_wirecutter_home_category_block_choice_matches_group( $choice, $group ) {
	if ( ! is_array( $choice ) || ! is_array( $group ) ) {
		return false;
	}

	$choice_slugs = array(
		$choice['key'] ?? '',
		$choice['slug'] ?? '',
		$choice['label'] ?? '',
	);
	$group_slugs = array(
		$group['slug'] ?? '',
		$group['label'] ?? '',
		$group['name'] ?? '',
		bhdt_wirecutter_get_home_category_block_public_slug( $group ),
	);
	$group_slugs = array_merge( $group_slugs, bhdt_wirecutter_clean_home_block_slug_aliases( $group['slug_aliases'] ?? array() ) );

	$choice_slugs = array_values(
		array_unique(
			array_filter(
				array_map(
					static function ( $value ) {
						return sanitize_title( (string) $value );
					},
					$choice_slugs
				)
			)
		)
	);
	$group_slugs = array_values(
		array_unique(
			array_filter(
				array_map(
					static function ( $value ) {
						return sanitize_title( (string) $value );
					},
					$group_slugs
				)
			)
		)
	);

	return ! empty( array_intersect( $choice_slugs, $group_slugs ) );
}

function bhdt_wirecutter_get_home_category_block_posts( $group ) {
	if ( ! is_array( $group ) ) {
		return array();
	}

	$slug = sanitize_title( $group['slug'] ?? '' );
	if ( '' === $slug ) {
		return array();
	}

	$block_keys = bhdt_wirecutter_get_home_category_block_key_variants( $group );

	$post_ids = get_posts(
		array(
			'post_type'      => bhdt_wirecutter_get_menu_linkable_post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'suppress_filters' => true,
			'meta_query'     => array(
				array(
					'key'     => '_bhdt_home_category_block_key',
					'value'   => $block_keys,
					'compare' => 'IN',
				),
			),
		)
	);

	global $wpdb;
	$linked_post_ids = array_map(
		'intval',
		(array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
				'_bhdt_home_category_block_key'
			)
		)
	);

	foreach ( $linked_post_ids as $linked_post_id ) {
		$linked_post = get_post( (int) $linked_post_id );
		if ( ! $linked_post || 'publish' !== $linked_post->post_status || ! in_array( $linked_post->post_type, bhdt_wirecutter_get_menu_linkable_post_types(), true ) ) {
			continue;
		}

		$meta_values = get_post_meta( (int) $linked_post_id, '_bhdt_home_category_block_key', false );
		foreach ( $meta_values as $meta_value ) {
			$choice = bhdt_wirecutter_find_home_category_block_by_key( $meta_value );
			if ( $choice && bhdt_wirecutter_home_category_block_choice_matches_group( $choice, $group ) ) {
				$post_ids[] = (int) $linked_post_id;
				break;
			}
		}
	}

	$term = get_term_by( 'slug', $slug, 'category' );
	if ( $term && ! is_wp_error( $term ) ) {
		$term_post_ids = get_posts(
			array(
				'post_type'      => bhdt_wirecutter_get_menu_linkable_post_types(),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'suppress_filters' => true,
				'tax_query'      => array(
					array(
						'taxonomy' => 'category',
						'field'    => 'term_id',
						'terms'    => array( (int) $term->term_id ),
					),
				),
			)
		);
		$post_ids = array_merge( $post_ids, $term_post_ids );
	}

	$post_ids = array_values( array_unique( array_map( 'intval', $post_ids ) ) );
	if ( empty( $post_ids ) ) {
		return array();
	}

	$posts = array_values(
		array_filter(
			array_map(
				static function ( $post_id ) {
					$post = get_post( (int) $post_id );
					if ( ! $post || 'publish' !== $post->post_status || ! in_array( $post->post_type, bhdt_wirecutter_get_menu_linkable_post_types(), true ) ) {
						return null;
					}

					return $post;
				},
				$post_ids
			)
		)
	);

	usort(
		$posts,
		static function ( $a, $b ) {
			return strcmp( (string) $b->post_modified_gmt, (string) $a->post_modified_gmt );
		}
	);

	return $posts;
}

function bhdt_wirecutter_render_home_category_block_archive() {
	if ( is_admin() ) {
		return;
	}

	$slug = '';
	if ( ! empty( $_GET['bhdt_home_block'] ) ) {
		$slug = sanitize_title( wp_unslash( $_GET['bhdt_home_block'] ) );
	} else {
		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
		$parts = array_values( array_filter( explode( '/', $path ) ) );
		if ( ! empty( $parts ) && 'en' === $parts[0] ) {
			array_shift( $parts );
		}
		if ( 1 === count( $parts ) ) {
			$slug = sanitize_title( $parts[0] );
		}
	}

	if ( '' === $slug ) {
		return;
	}

	$group = bhdt_wirecutter_get_home_category_group_by_slug( $slug );
	if ( ! $group ) {
		return;
	}

	$posts = bhdt_wirecutter_get_home_category_block_posts( $group );

	status_header( 200 );
	nocache_headers();
	get_header();
	?>
	<main class="bhdt-home-grid bhdt-home-block-archive">
		<section class="bhdt-archive-hero">
			<p class="bhdt-card-kicker"><?php esc_html_e( 'Khối danh mục', 'bhdt-wirecutter' ); ?></p>
			<h1><?php echo esc_html( $group['label'] ?? $group['name'] ?? $slug ); ?></h1>
			<?php if ( ! empty( $group['subtitle'] ) ) : ?>
				<p><?php echo esc_html( $group['subtitle'] ); ?></p>
			<?php endif; ?>
			<p class="bhdt-card-meta"><?php echo esc_html( sprintf( __( '%d bài trong khối này', 'bhdt-wirecutter' ), count( $posts ) ) ); ?></p>
		</section>

		<?php foreach ( $posts as $post ) : ?>
			<?php
			$post_id = (int) $post->ID;
			$type_label = bhdt_wirecutter_get_post_type_menu_label( get_post_type( $post_id ) );
			$excerpt = get_post_field( 'post_excerpt', $post_id );
			if ( empty( trim( $excerpt ) ) ) {
				$excerpt = get_post_field( 'post_content', $post_id );
			}
			?>
			<article class="bhdt-block-archive-card">
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="bhdt-block-archive-thumb" aria-hidden="true" tabindex="-1">
					<?php if ( has_post_thumbnail( $post_id ) ) : ?>
						<?php echo wp_kses_post( get_the_post_thumbnail( $post_id, 'large' ) ); ?>
					<?php else : ?>
						<div class="bhdt-wire-thumb-fallback"><?php esc_html_e( 'Không có hình ảnh', 'bhdt-wirecutter' ); ?></div>
					<?php endif; ?>
				</a>
				<div class="bhdt-block-archive-copy">
					<p class="bhdt-card-kicker"><?php echo esc_html( $type_label ); ?></p>
					<h2><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h2>
					<p class="bhdt-block-archive-excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $excerpt ), 28, '...' ) ); ?></p>
					<p class="bhdt-card-meta"><?php echo esc_html( sprintf( __( 'Cập nhật %s', 'bhdt-wirecutter' ), get_the_modified_date( 'M j, Y', $post_id ) ) ); ?></p>
				</div>
			</article>
		<?php endforeach; ?>
	</main>
	<?php
	get_footer();
	exit;
}
add_action( 'template_redirect', 'bhdt_wirecutter_render_home_category_block_archive', -2 );

function bhdt_wirecutter_get_home_category_linked_posts_map() {
	$map = array();

	$posts = get_posts(
		array(
			'post_type'      => bhdt_wirecutter_get_menu_linkable_post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'     => '_bhdt_home_category_block_key',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$key = sanitize_text_field( (string) get_post_meta( $post->ID, '_bhdt_home_category_block_key', true ) );
		$choice = bhdt_wirecutter_find_home_category_block_by_key( $key );
		if ( '' === $key || ! $choice ) {
			continue;
		}
		$key = $choice['key'];

		if ( ! isset( $map[ $key ] ) ) {
			$map[ $key ] = array();
		}

		$map[ $key ][] = (int) $post->ID;
	}

	return $map;
}

function bhdt_wirecutter_seed_home_category_block_posts() {
	return;

	$author_id = get_current_user_id();
	if ( $author_id <= 0 ) {
		$author_id = 1;
	}

	foreach ( bhdt_wirecutter_get_home_category_groups() as $group ) {
		$slug  = sanitize_title( $group['slug'] ?? '' );
		$label = sanitize_text_field( $group['label'] ?? $group['name'] ?? $slug );
		if ( '' === $slug || '' === $label ) {
			continue;
		}

		$linked_posts = get_posts(
			array(
				'post_type'      => array( 'post', 'page', 'bhdt_review', 'bhdt_comparison' ),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => '_bhdt_home_category_block_key',
						'value'   => $slug,
					),
				),
			)
		);

		// Delete existing posts for this block
		foreach ( $linked_posts as $post ) {
			wp_delete_post( $post->ID, true );
		}

		// Create 4 new posts with mixed content types
		$term = get_term_by( 'slug', $slug, 'category' );
		$post_types_cycle = array( 'bhdt_review', 'post', 'post' );

		for ( $i = 0; $i < 4; $i++ ) {
			$is_featured    = 0 === $i;
			$post_type_to_try = isset( $post_types_cycle[ $i ] ) ? $post_types_cycle[ $i ] : 'post';
			
			// Check if custom post type exists, fallback to post if not
			$current_post_type = post_type_exists( $post_type_to_try ) ? $post_type_to_try : 'post';
			
			$title          = $is_featured ? sprintf( '%s - Bài nổi bật [%s] {attempted}', $label, $current_post_type ) : sprintf( '%s - Bài phụ %d [%s]', $label, $i + 1, $current_post_type );
			$content        = $is_featured
				? sprintf( "Bai viet mo dau cho chu de %s. Anh Tuan co the sua noi dung nay bat cu luc nao. Type: %s (tried: %s)", $label, $current_post_type, $post_type_to_try )
				: sprintf( "Bai viet phu %d cho chu de %s. Noi dung duoc tao san de gan lien ket that. Type: %s (tried: %s)", $i + 1, $label, $current_post_type, $post_type_to_try );

			$post_id = wp_insert_post(
				array(
					'post_type'    => $current_post_type,
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_content' => $content,
					'post_excerpt' => wp_trim_words( $content, 24, '...' ),
					'post_author'  => $author_id,
				)
			);

			// Add debug info to title for i=0
			if ( $is_featured && $i === 0 ) {
				error_log( sprintf( 'Seed i=0: post_id=%s, type=%s, author=%d', var_export( $post_id, true ), $current_post_type, $author_id ) );
			}

			if ( is_wp_error( $post_id ) ) {
				error_log( 'Seed error: ' . $post_id->get_error_message() );
				continue;
			}
			if ( $post_id <= 0 ) {
				error_log( 'Seed error: Invalid post ID ' . $post_id . ' for type ' . $current_post_type . ' (i=' . $i . ')' );
				continue;
			}

			update_post_meta( $post_id, '_bhdt_home_category_block_key', $slug );

			if ( $term && ! is_wp_error( $term ) && is_object_in_taxonomy( $current_post_type, 'category' ) ) {
				wp_set_object_terms( $post_id, array( (int) $term->term_id ), 'category', true );
			}
		}
	}
}

function bhdt_wirecutter_ensure_home_category_block_posts( $group, $target_count = 4 ) {
	if ( ! is_array( $group ) ) {
		return;
	}

	$slug = sanitize_title( $group['slug'] ?? '' );
	$label = sanitize_text_field( $group['label'] ?? $group['name'] ?? $group['name_vi'] ?? $slug );
	if ( '' === $slug || '' === $label ) {
		return;
	}

	$existing_posts = bhdt_wirecutter_get_home_category_block_posts( $group );
	if ( count( $existing_posts ) >= $target_count ) {
		return;
	}

	$author_id = get_current_user_id();
	if ( $author_id <= 0 ) {
		$author_id = 1;
	}

	$term = get_term_by( 'slug', $slug, 'category' );
	if ( ! $term || is_wp_error( $term ) ) {
		$term_result = wp_insert_term( $label, 'category', array( 'slug' => $slug ) );
		if ( ! is_wp_error( $term_result ) && ! empty( $term_result['term_id'] ) ) {
			$term = get_term( (int) $term_result['term_id'], 'category' );
		}
	}

	$posts_to_create = max( 0, (int) $target_count - count( $existing_posts ) );
	for ( $i = 0; $i < $posts_to_create; $i++ ) {
		$display_index = count( $existing_posts ) + $i;
		$is_featured = 0 === $display_index;
		$title = $is_featured ? sprintf( '%s - Bài chính', $label ) : sprintf( '%s - Bài phụ %d', $label, $display_index );
		$content = $is_featured
			? sprintf( 'Bài viết chính cho khối danh mục %s. Bạn có thể sửa tiêu đề, nội dung, ảnh đại diện và liên kết của bài này trong tab Posts.', $label )
			: sprintf( 'Bài viết phụ %d cho khối danh mục %s. Đây là bài post thật được tạo tự động để khối có dữ liệu ban đầu.', $display_index, $label );

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => $content,
				'post_excerpt' => wp_trim_words( $content, 24, '...' ),
				'post_author'  => $author_id,
			),
			true
		);

		if ( is_wp_error( $post_id ) || (int) $post_id <= 0 ) {
			continue;
		}

		update_post_meta( (int) $post_id, '_bhdt_home_category_block_key', $slug );

		if ( $term && ! is_wp_error( $term ) && is_object_in_taxonomy( 'post', 'category' ) ) {
			wp_set_object_terms( (int) $post_id, array( (int) $term->term_id ), 'category', true );
		}
	}
}

function bhdt_wirecutter_ensure_home_category_blocks_posts() {
	foreach ( bhdt_wirecutter_get_home_category_groups() as $group ) {
		bhdt_wirecutter_ensure_home_category_block_posts( $group, 4 );
	}
}

function bhdt_wirecutter_home_post_type_label( $post_id ) {
	$post_type = get_post_type( $post_id );
	if ( 'page' === $post_type ) {
		return 'Page';
	}
	if ( 'bhdt_review' === $post_type ) {
		return 'Review';
	}
	if ( 'bhdt_comparison' === $post_type ) {
		return 'So sánh';
	}
	if ( 'bhdt_project' === $post_type ) {
		return 'Dự án';
	}

	return 'Post';
}

function bhdt_wirecutter_home_post_excerpt( $post_id, $words = 18 ) {
	$excerpt = get_post_field( 'post_excerpt', $post_id );
	if ( empty( trim( (string) $excerpt ) ) ) {
		$excerpt = get_post_field( 'post_content', $post_id );
	}

	return wp_trim_words( wp_strip_all_tags( (string) $excerpt ), $words, '...' );
}

function bhdt_wirecutter_render_home_post_thumb( $post_id, $size = 'medium', $class = 'bhdt-wire-product-thumb' ) {
	?>
	<a class="<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" aria-label="<?php echo esc_attr( get_the_title( $post_id ) ); ?>">
		<?php if ( has_post_thumbnail( $post_id ) ) : ?>
			<?php echo wp_kses_post( get_the_post_thumbnail( $post_id, $size ) ); ?>
		<?php else : ?>
			<div class="bhdt-wire-thumb-fallback"><?php esc_html_e( 'Không có hình ảnh', 'bhdt-wirecutter' ); ?></div>
		<?php endif; ?>
	</a>
	<?php
}

function bhdt_wirecutter_render_home_post_card( $post, $class = 'bhdt-wire-product-card' ) {
	$post_id = (int) $post->ID;
	?>
	<article class="<?php echo esc_attr( $class ); ?>">
		<?php bhdt_wirecutter_render_home_post_thumb( $post_id, 'medium' ); ?>
		<h3><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h3>
		<p class="bhdt-wire-meta"><?php echo esc_html( sprintf( __( 'Cập nhật %s', 'bhdt-wirecutter' ), get_the_modified_date( 'M j, Y', $post_id ) ) ); ?></p>
		<p><?php echo esc_html( bhdt_wirecutter_home_post_excerpt( $post_id, 18 ) ); ?></p>
	</article>
	<?php
}

function bhdt_wirecutter_render_home_post_media_item( $post ) {
	$post_id = (int) $post->ID;
	?>
	<article class="bhdt-wire-category-media-item">
		<?php bhdt_wirecutter_render_home_post_thumb( $post_id, 'medium', 'bhdt-wire-category-media-thumb' ); ?>
		<div class="bhdt-wire-category-media-copy">
			<h3><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h3>
			<p class="bhdt-wire-meta"><?php echo esc_html( sprintf( __( 'Cập nhật %s', 'bhdt-wirecutter' ), get_the_modified_date( 'M j, Y', $post_id ) ) ); ?></p>
			<p><?php echo esc_html( bhdt_wirecutter_home_post_excerpt( $post_id, 24 ) ); ?></p>
		</div>
	</article>
	<?php
}

function bhdt_wirecutter_render_home_post_list( $posts, $group ) {
	$posts = array_slice( array_values( (array) $posts ), 0, 3 );
	if ( empty( $posts ) ) {
		return;
	}
	?>
	<aside class="bhdt-wire-older-list bhdt-wire-category-layout-list" aria-label="<?php echo esc_attr( sprintf( __( 'Bài khác trong %s', 'bhdt-wirecutter' ), $group['label'] ?? $group['name'] ?? '' ) ); ?>">
		<?php foreach ( $posts as $post ) : ?>
			<?php $post_id = (int) $post->ID; ?>
			<a class="bhdt-wire-older-item" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
				<strong><?php echo esc_html( get_the_title( $post_id ) ); ?></strong>
				<small><?php echo esc_html( get_the_modified_date( 'M j, Y', $post_id ) ); ?></small>
			</a>
		<?php endforeach; ?>
		<a class="bhdt-wire-see-all" href="<?php echo esc_url( bhdt_wirecutter_home_category_block_url( $group ) ); ?>">
			<?php esc_html_e( 'Xem thêm', 'bhdt-wirecutter' ); ?>
			<span><?php esc_html_e( 'See all', 'bhdt-wirecutter' ); ?></span>
		</a>
	</aside>
	<?php
}

function bhdt_wirecutter_render_home_category_posts( $group, $posts ) {
	$posts = array_values( (array) $posts );
	if ( empty( $posts ) ) {
		return;
	}

	$layout = bhdt_wirecutter_clean_home_block_layout( $group['layout'] ?? 1 );
	$featured = array_shift( $posts );
	$featured_id = (int) $featured->ID;
	$list_posts = 1 === $layout ? array_slice( $posts, 3, 3 ) : array_slice( $posts, 0, 3 );
	$media_posts = 2 === $layout ? array_slice( $posts, 0, 3 ) : array();
	$card_posts = 1 === $layout ? array_slice( $posts, 0, 3 ) : array_slice( $posts, 3, 3 );
	if ( 3 === $layout ) {
		$card_posts = array_slice( array_merge( array( $featured ), $posts ), 0, 3 );
		$list_posts = array_slice( $posts, 3, 3 );
	}
	?>
	<div class="bhdt-wire-category-layout bhdt-wire-category-layout-<?php echo esc_attr( (string) $layout ); ?>">
		<?php if ( 2 === $layout ) : ?>
			<div class="bhdt-wire-category-layout-main">
				<article class="bhdt-wire-category-layout-feature">
					<?php bhdt_wirecutter_render_home_post_thumb( $featured_id, 'large', 'bhdt-wire-category-lead-thumb' ); ?>
					<h3><a href="<?php echo esc_url( get_permalink( $featured_id ) ); ?>"><?php echo esc_html( get_the_title( $featured_id ) ); ?></a></h3>
					<p class="bhdt-wire-meta"><?php echo esc_html( sprintf( __( 'Cập nhật %s', 'bhdt-wirecutter' ), get_the_modified_date( 'M j, Y', $featured_id ) ) ); ?></p>
					<p><?php echo esc_html( bhdt_wirecutter_home_post_excerpt( $featured_id, 24 ) ); ?></p>
				</article>
				<?php bhdt_wirecutter_render_home_post_list( $list_posts, $group ); ?>
			</div>
			<?php if ( ! empty( $media_posts ) ) : ?>
				<div class="bhdt-wire-category-media-list">
					<?php foreach ( $media_posts as $post ) : ?>
						<?php bhdt_wirecutter_render_home_post_media_item( $post ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php elseif ( 3 === $layout ) : ?>
			<div class="bhdt-wire-category-layout-card-row">
				<?php foreach ( $card_posts as $post ) : ?>
					<?php bhdt_wirecutter_render_home_post_card( $post ); ?>
				<?php endforeach; ?>
			</div>
			<?php bhdt_wirecutter_render_home_post_list( $list_posts, $group ); ?>
		<?php else : ?>
			<article class="bhdt-wire-category-lead">
				<?php if ( ! empty( $list_posts ) ) : ?>
					<?php bhdt_wirecutter_render_home_post_list( $list_posts, $group ); ?>
				<?php endif; ?>
				<?php bhdt_wirecutter_render_home_post_thumb( $featured_id, 'large', 'bhdt-wire-category-lead-thumb' ); ?>
				<div class="bhdt-wire-category-lead-copy">
					<h3><a href="<?php echo esc_url( get_permalink( $featured_id ) ); ?>"><?php echo esc_html( get_the_title( $featured_id ) ); ?></a></h3>
					<p class="bhdt-wire-meta"><?php echo esc_html( sprintf( __( 'Cập nhật %s', 'bhdt-wirecutter' ), get_the_modified_date( 'M j, Y', $featured_id ) ) ); ?></p>
					<p><?php echo esc_html( bhdt_wirecutter_home_post_excerpt( $featured_id, 30 ) ); ?></p>
				</div>
			</article>
			<?php if ( ! empty( $card_posts ) ) : ?>
				<div class="bhdt-wire-sub-grid">
					<?php foreach ( $card_posts as $post ) : ?>
						<?php bhdt_wirecutter_render_home_post_card( $post ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}

function bhdt_wirecutter_default_hot_keys() {
	return array(
		array( 'label_vi' => 'Thiết bị nhúng', 'label_en' => 'Embedded devices', 'keyword' => 'thiết bị nhúng', 'order' => 10, 'enabled' => 1 ),
		array( 'label_vi' => 'Linh kiện điện tử', 'label_en' => 'Electronic components', 'keyword' => 'linh kiện điện tử', 'order' => 20, 'enabled' => 1 ),
		array( 'label_vi' => 'Vi điều khiển', 'label_en' => 'Microcontrollers', 'keyword' => 'vi điều khiển', 'order' => 30, 'enabled' => 1 ),
		array( 'label_vi' => 'Robot thông minh', 'label_en' => 'Smart robots', 'keyword' => 'robot thông minh', 'order' => 40, 'enabled' => 1 ),
	);
}

function bhdt_wirecutter_clean_hot_keys( $rows ) {
	if ( ! is_array( $rows ) ) {
		return array();
	}

	$clean = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$label_vi = isset( $row['label_vi'] ) ? sanitize_text_field( $row['label_vi'] ) : '';
		$label_en = isset( $row['label_en'] ) ? sanitize_text_field( $row['label_en'] ) : '';
		$keyword  = isset( $row['keyword'] ) ? sanitize_text_field( $row['keyword'] ) : $label_vi;
		$order    = isset( $row['order'] ) ? (int) $row['order'] : 0;
		$enabled  = isset( $row['enabled'] ) ? (int) $row['enabled'] : 1;

		if ( '' === $label_vi ) {
			continue;
		}
		if ( '' === $keyword ) {
			$keyword = $label_vi;
		}

		$clean[] = array(
			'label_vi' => $label_vi,
			'label_en' => $label_en,
			'keyword'  => $keyword,
			'order'    => $order,
			'enabled'  => $enabled,
		);
	}

	usort(
		$clean,
		static function ( $a, $b ) {
			return (int) $a['order'] <=> (int) $b['order'];
		}
	);

	return $clean;
}

function bhdt_wirecutter_get_hot_keys( $include_disabled = false ) {
	$saved = get_option( 'bhdt_wire_hot_keys', array() );
	if ( ! is_array( $saved ) || empty( $saved ) ) {
		$saved = bhdt_wirecutter_default_hot_keys();
	}

	$rows = bhdt_wirecutter_clean_hot_keys( $saved );
	$is_en = function_exists( 'pll_current_language' ) && 'en' === pll_current_language( 'slug' );

	foreach ( $rows as $index => $row ) {
		if ( ! $include_disabled && 1 !== (int) $row['enabled'] ) {
			unset( $rows[ $index ] );
			continue;
		}

		$rows[ $index ]['label'] = $is_en && '' !== $row['label_en'] ? $row['label_en'] : $row['label_vi'];
		$rows[ $index ]['url'] = home_url( '/' . sanitize_title( $row['keyword'] ) . '/' );
	}

	return array_values( $rows );
}

function bhdt_wirecutter_find_hot_key_by_slug( $slug ) {
	$slug = sanitize_title( $slug );
	if ( '' === $slug ) {
		return null;
	}

	foreach ( bhdt_wirecutter_get_hot_keys() as $hot_key ) {
		if ( sanitize_title( $hot_key['keyword'] ?? '' ) === $slug || sanitize_title( $hot_key['label_vi'] ?? '' ) === $slug ) {
			return $hot_key;
		}
	}

	return null;
}

function bhdt_wirecutter_current_hot_key_request() {
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( '' === $path || false !== strpos( $path, '/' ) ) {
		return null;
	}

	return bhdt_wirecutter_find_hot_key_by_slug( $path );
}

function bhdt_wirecutter_hot_key_document_title_parts( $parts ) {
	$hot_key = bhdt_wirecutter_current_hot_key_request();
	if ( ! is_array( $hot_key ) ) {
		return $parts;
	}

	$title = (string) ( $hot_key['label'] ?? $hot_key['label_vi'] ?? $hot_key['keyword'] ?? '' );
	if ( '' !== $title ) {
		$parts['title'] = $title;
	}

	return $parts;
}
add_filter( 'document_title_parts', 'bhdt_wirecutter_hot_key_document_title_parts', 20 );

function bhdt_wirecutter_admin_home_blocks_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	add_theme_page(
		'Khối danh mục trang chủ',
		'Khối danh mục',
		'manage_options',
		'bhdt-home-category-blocks',
		'bhdt_wirecutter_render_home_blocks_admin_page'
	);

	add_theme_page(
		'Khối Menu',
		'Khối Menu',
		'manage_options',
		'bhdt-menu-blocks',
		'bhdt_wirecutter_render_menu_blocks_admin_page'
	);

	add_theme_page(
		'Khối hot keys',
		'Khối hot keys',
		'manage_options',
		'bhdt-hot-keys',
		'bhdt_wirecutter_render_hot_keys_admin_page'
	);

	add_theme_page(
		'Menu Chức Năng',
		'Menu Chức Năng',
		'manage_options',
		'bhdt-function-menu',
		'bhdt_wirecutter_render_function_menu_admin_page'
	);

	add_theme_page(
		'Footer Settings',
		'Footer Settings',
		'manage_options',
		'bhdt-footer-settings',
		'bhdt_wirecutter_render_footer_settings_admin_page'
	);
}
add_action( 'admin_menu', 'bhdt_wirecutter_admin_home_blocks_page' );

function bhdt_wirecutter_default_footer_settings() {
	return array(
		'logo_text'   => 'Banhangdientu',
		'logo_image'  => '',
		'description' => 'Bố cục biên tập gọn gàng, cảm hứng từ Wirecutter.',
		'middle_text' => 'Hướng dẫn thực tế, đánh giá linh kiện, ghi chú dự án.',
		'copyright'   => '© {year} Banhangdientu',
		'facebook'    => '',
		'youtube'     => '',
		'tiktok'      => '',
		'zalo'        => '',
		'bg_color'    => '#181818',
		'text_color'  => '#ffffff',
		'muted_color' => '#eeeeee',
	);
}

function bhdt_wirecutter_clean_footer_settings( $settings ) {
	$defaults = bhdt_wirecutter_default_footer_settings();
	$settings = is_array( $settings ) ? $settings : array();
	$clean = array();

	foreach ( array( 'logo_text', 'description', 'middle_text', 'copyright' ) as $key ) {
		$clean[ $key ] = isset( $settings[ $key ] ) ? sanitize_text_field( wp_unslash( $settings[ $key ] ) ) : $defaults[ $key ];
	}

	foreach ( array( 'logo_image', 'facebook', 'youtube', 'tiktok', 'zalo' ) as $key ) {
		$clean[ $key ] = isset( $settings[ $key ] ) ? esc_url_raw( wp_unslash( $settings[ $key ] ) ) : '';
	}

	foreach ( array( 'bg_color', 'text_color', 'muted_color' ) as $key ) {
		$value = isset( $settings[ $key ] ) ? sanitize_hex_color( wp_unslash( $settings[ $key ] ) ) : '';
		$clean[ $key ] = $value ? $value : $defaults[ $key ];
	}

	return $clean;
}

function bhdt_wirecutter_get_footer_settings() {
	$saved = get_option( 'bhdt_wire_footer_settings', array() );
	return array_merge(
		bhdt_wirecutter_default_footer_settings(),
		bhdt_wirecutter_clean_footer_settings( is_array( $saved ) ? $saved : array() )
	);
}

function bhdt_wirecutter_handle_footer_settings_save() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-footer-settings' !== $_GET['page'] ) {
		return;
	}

	if ( ! isset( $_POST['bhdt_save_footer_settings'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_save_footer_settings_nonce' );
	update_option( 'bhdt_wire_footer_settings', bhdt_wirecutter_clean_footer_settings( $_POST ), false );
	wp_safe_redirect( admin_url( 'themes.php?page=bhdt-footer-settings&updated=1' ) );
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_footer_settings_save' );

function bhdt_wirecutter_render_footer_settings_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập.', 'bhdt-wirecutter' ) );
	}

	$settings = bhdt_wirecutter_get_footer_settings();
	?>
	<div class="wrap">
		<h1>Footer Settings</h1>
		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Đã lưu Footer Settings.</p></div>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'bhdt_save_footer_settings_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="bhdt-footer-logo-text">Logo text</label></th>
					<td><input class="regular-text" id="bhdt-footer-logo-text" name="logo_text" type="text" value="<?php echo esc_attr( $settings['logo_text'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="bhdt-footer-logo-image">Logo image URL</label></th>
					<td>
						<input class="regular-text" id="bhdt-footer-logo-image" name="logo_image" type="url" value="<?php echo esc_url( $settings['logo_image'] ); ?>">
						<p class="description">Dán URL ảnh logo nếu muốn dùng ảnh; để trống sẽ dùng Logo text.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bhdt-footer-description">Mô tả cột trái</label></th>
					<td><textarea class="large-text" rows="3" id="bhdt-footer-description" name="description"><?php echo esc_textarea( $settings['description'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="bhdt-footer-middle-text">Nội dung cột giữa</label></th>
					<td><textarea class="large-text" rows="3" id="bhdt-footer-middle-text" name="middle_text"><?php echo esc_textarea( $settings['middle_text'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="bhdt-footer-copyright">Copyright</label></th>
					<td>
						<input class="regular-text" id="bhdt-footer-copyright" name="copyright" type="text" value="<?php echo esc_attr( $settings['copyright'] ); ?>">
						<p class="description">Dùng <code>{year}</code> để tự thay bằng năm hiện tại.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Social links</th>
					<td>
						<p><label>Facebook<br><input class="regular-text" name="facebook" type="url" value="<?php echo esc_url( $settings['facebook'] ); ?>"></label></p>
						<p><label>YouTube<br><input class="regular-text" name="youtube" type="url" value="<?php echo esc_url( $settings['youtube'] ); ?>"></label></p>
						<p><label>TikTok<br><input class="regular-text" name="tiktok" type="url" value="<?php echo esc_url( $settings['tiktok'] ); ?>"></label></p>
						<p><label>Zalo<br><input class="regular-text" name="zalo" type="url" value="<?php echo esc_url( $settings['zalo'] ); ?>"></label></p>
					</td>
				</tr>
				<tr>
					<th scope="row">Màu footer</th>
					<td>
						<label>Nền <input name="bg_color" type="color" value="<?php echo esc_attr( $settings['bg_color'] ); ?>"></label>
						<label style="margin-left:16px;">Chữ <input name="text_color" type="color" value="<?php echo esc_attr( $settings['text_color'] ); ?>"></label>
						<label style="margin-left:16px;">Chữ phụ <input name="muted_color" type="color" value="<?php echo esc_attr( $settings['muted_color'] ); ?>"></label>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Lưu Footer Settings', 'primary', 'bhdt_save_footer_settings' ); ?>
		</form>
	</div>
	<?php
}

function bhdt_wirecutter_handle_home_blocks_save() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-home-category-blocks' !== $_GET['page'] ) {
		return;
	}

	$is_save_action    = isset( $_POST['bhdt_save_home_blocks'] );
	$is_refresh_action = isset( $_POST['bhdt_refresh_home_block_slugs'] );
	if ( ! $is_save_action && ! $is_refresh_action ) {
		return;
	}

	check_admin_referer( 'bhdt_save_home_blocks_nonce' );

	$name_vi_list = isset( $_POST['group_name_vi'] ) && is_array( $_POST['group_name_vi'] ) ? $_POST['group_name_vi'] : array();
	$name_en_list = isset( $_POST['group_name_en'] ) && is_array( $_POST['group_name_en'] ) ? $_POST['group_name_en'] : array();
	$desc_vi_list = isset( $_POST['group_desc_vi'] ) && is_array( $_POST['group_desc_vi'] ) ? $_POST['group_desc_vi'] : array();
	$desc_en_list = isset( $_POST['group_desc_en'] ) && is_array( $_POST['group_desc_en'] ) ? $_POST['group_desc_en'] : array();
	$post_type_list = isset( $_POST['group_post_type'] ) && is_array( $_POST['group_post_type'] ) ? wp_unslash( $_POST['group_post_type'] ) : array();
	$slug_list    = isset( $_POST['group_slug'] ) && is_array( $_POST['group_slug'] ) ? $_POST['group_slug'] : array();
	$slug_aliases_list = isset( $_POST['group_slug_aliases'] ) && is_array( $_POST['group_slug_aliases'] ) ? wp_unslash( $_POST['group_slug_aliases'] ) : array();
	$layout_list  = isset( $_POST['group_layout'] ) && is_array( $_POST['group_layout'] ) ? wp_unslash( $_POST['group_layout'] ) : array();
	$order_list   = isset( $_POST['group_order'] ) && is_array( $_POST['group_order'] ) ? $_POST['group_order'] : array();
	$enabled_list = isset( $_POST['group_enabled'] ) && is_array( $_POST['group_enabled'] ) ? $_POST['group_enabled'] : array();
	$total = max( count( $name_vi_list ), count( $name_en_list ), count( $desc_vi_list ), count( $desc_en_list ), count( $post_type_list ), count( $slug_list ), count( $layout_list ), count( $order_list ) );
	$clean = array();

	for ( $i = 0; $i < $total; $i++ ) {
		$name_vi = isset( $name_vi_list[ $i ] ) ? sanitize_text_field( $name_vi_list[ $i ] ) : '';
		$name_en = isset( $name_en_list[ $i ] ) ? sanitize_text_field( $name_en_list[ $i ] ) : '';
		$desc_vi = isset( $desc_vi_list[ $i ] ) ? sanitize_text_field( $desc_vi_list[ $i ] ) : '';
		$desc_en = isset( $desc_en_list[ $i ] ) ? sanitize_text_field( $desc_en_list[ $i ] ) : '';
		$post_types = bhdt_wirecutter_normalize_home_block_post_types( $post_type_list[ $i ] ?? 'post' );
		$post_type = $post_types[0];
		$old_slug = isset( $slug_list[ $i ] ) ? sanitize_title( $slug_list[ $i ] ) : '';
		$slug    = $old_slug;
		$slug_aliases = bhdt_wirecutter_clean_home_block_slug_aliases( $slug_aliases_list[ $i ] ?? array() );
		$layout = bhdt_wirecutter_clean_home_block_layout( $layout_list[ $i ] ?? 1 );
		$order   = isset( $order_list[ $i ] ) ? (int) $order_list[ $i ] : 0;
		$enabled = isset( $enabled_list[ $i ] ) ? 1 : 0;

		if ( '' === $name_vi ) {
			continue;
		}

		if ( $is_refresh_action || '' === $slug ) {
			$slug = sanitize_title( $name_vi );
		}
		if ( $is_refresh_action && '' !== $old_slug && $old_slug !== $slug ) {
			$slug_aliases[] = $old_slug;
			$slug_aliases = bhdt_wirecutter_clean_home_block_slug_aliases( $slug_aliases );
		}

		$clean[] = array(
			'name_vi' => $name_vi,
			'name_en' => $name_en,
			'desc_vi' => $desc_vi,
			'desc_en' => $desc_en,
			'post_type' => $post_type,
			'post_types' => $post_types,
			'slug'    => $slug,
			'slug_aliases' => array_values( array_diff( $slug_aliases, array( $slug ) ) ),
			'layout'  => $layout,
			'order'   => $order,
			'enabled' => $enabled,
		);
	}

	usort(
		$clean,
		static function ( $a, $b ) {
			return (int) $a['order'] <=> (int) $b['order'];
		}
	);

	update_option( 'bhdt_wire_home_category_groups', $clean, false );
	bhdt_wirecutter_ensure_home_category_blocks_posts();
	wp_safe_redirect( admin_url( 'themes.php?page=bhdt-home-category-blocks&updated=1' . ( $is_refresh_action ? '&refreshed=1' : '' ) ) );
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_home_blocks_save' );

function bhdt_wirecutter_handle_hot_keys_save() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-hot-keys' !== $_GET['page'] ) {
		return;
	}

	if ( ! isset( $_POST['bhdt_save_hot_keys'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_save_hot_keys_nonce' );

	$labels_vi = isset( $_POST['hot_key_label_vi'] ) && is_array( $_POST['hot_key_label_vi'] ) ? wp_unslash( $_POST['hot_key_label_vi'] ) : array();
	$labels_en = isset( $_POST['hot_key_label_en'] ) && is_array( $_POST['hot_key_label_en'] ) ? wp_unslash( $_POST['hot_key_label_en'] ) : array();
	$keywords  = isset( $_POST['hot_key_keyword'] ) && is_array( $_POST['hot_key_keyword'] ) ? wp_unslash( $_POST['hot_key_keyword'] ) : array();
	$orders    = isset( $_POST['hot_key_order'] ) && is_array( $_POST['hot_key_order'] ) ? wp_unslash( $_POST['hot_key_order'] ) : array();
	$enabled   = isset( $_POST['hot_key_enabled'] ) && is_array( $_POST['hot_key_enabled'] ) ? wp_unslash( $_POST['hot_key_enabled'] ) : array();

	$total = max( count( $labels_vi ), count( $labels_en ), count( $keywords ), count( $orders ) );
	$rows = array();
	for ( $i = 0; $i < $total; $i++ ) {
		$rows[] = array(
			'label_vi' => $labels_vi[ $i ] ?? '',
			'label_en' => $labels_en[ $i ] ?? '',
			'keyword'  => $keywords[ $i ] ?? '',
			'order'    => $orders[ $i ] ?? ( ( $i + 1 ) * 10 ),
			'enabled'  => isset( $enabled[ $i ] ) ? 1 : 0,
		);
	}

	update_option( 'bhdt_wire_hot_keys', bhdt_wirecutter_clean_hot_keys( $rows ), false );
	wp_safe_redirect( admin_url( 'themes.php?page=bhdt-hot-keys&updated=1' ) );
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_hot_keys_save' );

function bhdt_wirecutter_render_hot_keys_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập.', 'bhdt-wirecutter' ) );
	}

	$rows = bhdt_wirecutter_get_hot_keys( true );
	if ( empty( $rows ) ) {
		$rows = bhdt_wirecutter_default_hot_keys();
	}
	?>
	<div class="wrap">
		<h1>Khối hot keys</h1>
		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Đã lưu Khối hot keys.</p></div>
		<?php endif; ?>
		<form method="post" id="bhdt-hot-keys-form">
			<?php wp_nonce_field( 'bhdt_save_hot_keys_nonce' ); ?>
			<table class="widefat striped" id="bhdt-hot-keys-table">
				<thead>
					<tr>
						<th style="width:58px;">Kéo</th>
						<th style="width:90px;">Hiển thị</th>
						<th>Tên VI</th>
						<th>Tên EN</th>
						<th>Từ khóa</th>
						<th style="width:90px;">Thứ tự</th>
						<th style="width:80px;">Xóa</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_values( $rows ) as $idx => $row ) : ?>
					<tr draggable="true">
						<td><span class="bhdt-drag-handle">↕</span></td>
						<td><input type="checkbox" name="hot_key_enabled[<?php echo esc_attr( $idx ); ?>]" value="1" <?php checked( ! empty( $row['enabled'] ) ); ?>></td>
						<td><input class="regular-text" type="text" name="hot_key_label_vi[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['label_vi'] ?? '' ); ?>"></td>
						<td><input class="regular-text" type="text" name="hot_key_label_en[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['label_en'] ?? '' ); ?>"></td>
						<td><input class="regular-text" type="text" name="hot_key_keyword[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['keyword'] ?? '' ); ?>"></td>
						<td><input class="small-text bhdt-hot-key-order" readonly type="number" name="hot_key_order[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( (string) ( $row['order'] ?? 0 ) ); ?>"></td>
						<td><button type="button" class="button bhdt-remove-hot-key">Xóa</button></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p><button type="button" class="button" id="bhdt-add-hot-key">Thêm hot key</button></p>
			<?php submit_button( 'Lưu Khối hot keys', 'primary', 'bhdt_save_hot_keys' ); ?>
		</form>
	</div>
	<script>
	(function () {
		const table = document.getElementById('bhdt-hot-keys-table');
		const addButton = document.getElementById('bhdt-add-hot-key');
		if (!table || !addButton) return;
		const tbody = table.querySelector('tbody');
		const escapeAttr = (value) => String(value || '').replace(/[&<>'"]/g, (char) => ({
			'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
		}[char]));
		const rowHtml = (idx) => '' +
			'<tr draggable="true">' +
				'<td><span class="bhdt-drag-handle">↕</span></td>' +
				'<td><input type="checkbox" name="hot_key_enabled[' + idx + ']" value="1" checked></td>' +
				'<td><input class="regular-text" type="text" name="hot_key_label_vi[' + idx + ']" value=""></td>' +
				'<td><input class="regular-text" type="text" name="hot_key_label_en[' + idx + ']" value=""></td>' +
				'<td><input class="regular-text" type="text" name="hot_key_keyword[' + idx + ']" value=""></td>' +
				'<td><input class="small-text bhdt-hot-key-order" readonly type="number" name="hot_key_order[' + idx + ']" value="' + ((idx + 1) * 10) + '"></td>' +
				'<td><button type="button" class="button bhdt-remove-hot-key">Xóa</button></td>' +
			'</tr>';
		const reorder = () => {
			Array.from(tbody.querySelectorAll('tr')).forEach((row, idx) => {
				[
					['hot_key_enabled[', 'input[name^="hot_key_enabled["]'],
					['hot_key_label_vi[', 'input[name^="hot_key_label_vi["]'],
					['hot_key_label_en[', 'input[name^="hot_key_label_en["]'],
					['hot_key_keyword[', 'input[name^="hot_key_keyword["]'],
					['hot_key_order[', 'input[name^="hot_key_order["]']
				].forEach(([prefix, selector]) => {
					const input = row.querySelector(selector);
					if (input) input.name = prefix + idx + ']';
				});
				const order = row.querySelector('.bhdt-hot-key-order');
				if (order) order.value = String((idx + 1) * 10);
			});
		};
		const bindRow = (row) => {
			row.querySelector('.bhdt-remove-hot-key')?.addEventListener('click', () => {
				row.remove();
				reorder();
			});
			row.addEventListener('dragstart', () => row.classList.add('is-dragging'));
			row.addEventListener('dragend', () => {
				row.classList.remove('is-dragging');
				reorder();
			});
		};
		tbody.addEventListener('dragover', (event) => {
			event.preventDefault();
			const dragging = tbody.querySelector('.is-dragging');
			if (!dragging) return;
			const rows = Array.from(tbody.querySelectorAll('tr:not(.is-dragging)'));
			let next = null;
			for (const row of rows) {
				const rect = row.getBoundingClientRect();
				if (event.clientY < rect.top + rect.height / 2) {
					next = row;
					break;
				}
			}
			if (next) tbody.insertBefore(dragging, next);
			else tbody.appendChild(dragging);
		});
		tbody.querySelectorAll('tr').forEach(bindRow);
		addButton.addEventListener('click', () => {
			const idx = tbody.querySelectorAll('tr').length;
			tbody.insertAdjacentHTML('beforeend', rowHtml(idx));
			bindRow(tbody.lastElementChild);
			reorder();
		});
		reorder();
	})();
	</script>
	<style>
	#bhdt-hot-keys-table .bhdt-drag-handle {
		cursor: move;
		display: inline-block;
		font-size: 16px;
		line-height: 1;
		padding: 6px 2px;
	}
	#bhdt-hot-keys-table tr.is-dragging {
		opacity: 0.55;
		background: #f6f7f7;
	}
	</style>
	<?php
}

function bhdt_wirecutter_render_home_blocks_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập.', 'bhdt-wirecutter' ) );
	}

	$rows = get_option( 'bhdt_wire_home_category_groups', array() );
	if ( ! is_array( $rows ) || empty( $rows ) ) {
		$rows = bhdt_wirecutter_default_home_category_groups();
	}
	$post_type_choices = bhdt_wirecutter_home_block_post_types();
	$slug_suggestion_map = bhdt_wirecutter_home_block_slug_suggestions();
	?>
	<div class="wrap">
		<h1>Khối danh mục trang chủ</h1>
		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo isset( $_GET['refreshed'] ) ? 'Đã refresh slug chuyên mục theo tên tiêu đề.' : 'Đã lưu cấu hình khối danh mục.'; ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['invalid_slug'] ) ) : ?>
			<?php
			$invalid_row = isset( $_GET['invalid_row'] ) ? (int) $_GET['invalid_row'] : 0;
			$invalid_type = isset( $_GET['invalid_post_type'] ) ? sanitize_key( (string) $_GET['invalid_post_type'] ) : 'post';
			$invalid_value = isset( $_GET['invalid_value'] ) ? sanitize_title( (string) $_GET['invalid_value'] ) : '';
			$post_type_label = isset( $post_type_choices[ $invalid_type ] ) ? $post_type_choices[ $invalid_type ] : $invalid_type;
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php
					echo esc_html(
						sprintf(
							'Slug "%1$s" không hợp lệ cho loại nội dung "%2$s" (dòng %3$d).',
							$invalid_value,
							$post_type_label,
							$invalid_row
						)
					);
					?>
				</p>
			</div>
		<?php endif; ?>
		<div class="bhdt-home-blocks-tools">
			<label for="bhdt-filter-post-type">Lọc theo loại nội dung:</label>
			<select id="bhdt-filter-post-type">
				<option value="">Tất cả</option>
				<?php foreach ( $post_type_choices as $pt_value => $pt_label ) : ?>
					<option value="<?php echo esc_attr( $pt_value ); ?>"><?php echo esc_html( $pt_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<form method="post">
			<?php wp_nonce_field( 'bhdt_save_home_blocks_nonce' ); ?>
			<p class="bhdt-home-blocks-tools">
				<button type="submit" class="button button-secondary" name="bhdt_refresh_home_block_slugs" value="1">Refresh slug theo tiêu đề</button>
				<span class="description">Tự đổi slug chuyên mục theo Tên VI, dùng dấu gạch ngang thay cho khoảng trắng.</span>
			</p>
			<table class="widefat striped" id="bhdt-home-blocks-table">
				<thead>
					<tr>
						<th style="width:72px;">Kéo thả</th>
						<th>Hiển thị</th>
						<th>Tên VI</th>
						<th>Tên EN</th>
						<th>Bố cục</th>
						<th>Loại nội dung</th>
						<th>Mô tả VI</th>
						<th>Mô tả EN</th>
						<th>Slug chuyên mục</th>
						<th>Thứ tự</th>
						<th>Nhân bản</th>
						<th>Xóa</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_values( $rows ) as $idx => $row ) : ?>
						<?php $row_post_types = bhdt_wirecutter_get_home_block_post_types_from_row( $row ); ?>
						<tr draggable="true" class="bhdt-sortable-row" data-post-types="<?php echo esc_attr( implode( ' ', $row_post_types ) ); ?>">
							<td><span class="bhdt-drag-handle" title="Kéo để đổi vị trí" aria-label="Kéo để đổi vị trí">↕</span></td>
							<td><input type="checkbox" name="group_enabled[<?php echo esc_attr( $idx ); ?>]" value="1" <?php checked( ! empty( $row['enabled'] ) ); ?>></td>
							<td><input class="regular-text" type="text" name="group_name_vi[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['name_vi'] ?? '' ); ?>"></td>
							<td><input class="regular-text" type="text" name="group_name_en[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['name_en'] ?? '' ); ?>"></td>
							<td>
								<select name="group_layout[<?php echo esc_attr( $idx ); ?>]">
									<?php $bhdt_layout = bhdt_wirecutter_clean_home_block_layout( $row['layout'] ?? 1 ); ?>
									<option value="1" <?php selected( 1, $bhdt_layout ); ?>>Bố cục 1</option>
									<option value="2" <?php selected( 2, $bhdt_layout ); ?>>Bố cục 2</option>
									<option value="3" <?php selected( 3, $bhdt_layout ); ?>>Bố cục 3</option>
								</select>
							</td>
							<td>
								<div class="bhdt-post-type-checks">
									<?php foreach ( $post_type_choices as $pt_value => $pt_label ) : ?>
										<label>
											<input type="checkbox" name="group_post_type[<?php echo esc_attr( $idx ); ?>][]" value="<?php echo esc_attr( $pt_value ); ?>" <?php checked( in_array( $pt_value, $row_post_types, true ) ); ?>>
											<?php echo esc_html( $pt_label ); ?>
										</label>
									<?php endforeach; ?>
								</div>
							</td>
							<td><input class="regular-text" type="text" name="group_desc_vi[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['desc_vi'] ?? '' ); ?>"></td>
							<td><input class="regular-text" type="text" name="group_desc_en[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['desc_en'] ?? '' ); ?>"></td>
							<td>
								<input class="regular-text bhdt-group-slug" type="text" name="group_slug[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['slug'] ?? '' ); ?>" list="bhdt-slug-list-<?php echo esc_attr( $idx ); ?>">
								<input class="bhdt-group-slug-aliases" type="hidden" name="group_slug_aliases[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( implode( ',', bhdt_wirecutter_clean_home_block_slug_aliases( $row['slug_aliases'] ?? array() ) ) ); ?>">
								<datalist class="bhdt-slug-list" id="bhdt-slug-list-<?php echo esc_attr( $idx ); ?>"></datalist>
								<p class="description bhdt-slug-hint">Chọn gợi ý hoặc nhập slug mới.</p>
							</td>
							<td><input type="number" min="0" step="1" readonly name="group_order[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( (string) ( $row['order'] ?? 0 ) ); ?>"></td>
							<td><button type="button" class="button bhdt-duplicate-row">Nhân bản</button></td>
							<td><button type="button" class="button bhdt-remove-row">Xóa dòng</button></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p style="margin-top:12px;">
				<button type="button" class="button" id="bhdt-add-row">Thêm khối</button>
			</p>
			<?php submit_button( 'Lưu cấu hình', 'primary', 'bhdt_save_home_blocks' ); ?>
		</form>
	</div>
	<script>
	(function () {
		const tableBody = document.querySelector('#bhdt-home-blocks-table tbody');
		const addBtn = document.getElementById('bhdt-add-row');
		const postTypeFilter = document.getElementById('bhdt-filter-post-type');
		const postTypeChoices = <?php echo wp_json_encode( $post_type_choices ); ?>;
		const slugSuggestionMap = <?php echo wp_json_encode( $slug_suggestion_map ); ?>;
		if (!tableBody || !addBtn) return;

		const escapeAttr = (value) => String(value || '').replace(/[&<>'"]/g, (char) => ({
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#39;'
		}[char]));

		const slugify = (value) => String(value || '')
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-+|-+$/g, '');

		const syncSlugFromTitle = (row) => {
			const titleInput = row?.querySelector('input[name^="group_name_vi["]');
			const slugInput = row?.querySelector('input.bhdt-group-slug');
			if (!titleInput || !slugInput) return;
			const previousTitle = titleInput.dataset.bhdtLastTitle || titleInput.value;
			const previousSlug = slugify(previousTitle);
			const currentSlug = slugify(slugInput.value);
			if (!slugInput.value || currentSlug === previousSlug) {
				slugInput.value = slugify(titleInput.value);
			}
			titleInput.dataset.bhdtLastTitle = titleInput.value;
		};

		const getSelectedPostTypes = (row) => {
			const values = Array.from(row.querySelectorAll('input[name^="group_post_type["]:checked')).map((input) => input.value);
			return values.length ? values : ['post'];
		};

		const syncPostTypesDataset = (row) => {
			row.dataset.postTypes = getSelectedPostTypes(row).join(' ');
		};

		const renderPostTypeChecks = (idx, selected = ['post']) => {
			const selectedSet = new Set(Array.isArray(selected) && selected.length ? selected : ['post']);
			return '<div class="bhdt-post-type-checks">' + Object.entries(postTypeChoices || {}).map(([value, label]) => {
				const checked = selectedSet.has(value) ? ' checked' : '';
				return '<label><input type="checkbox" name="group_post_type[' + idx + '][]" value="' + escapeAttr(value) + '"' + checked + '> ' + escapeAttr(label) + '</label>';
			}).join('') + '</div>';
		};

		const reorderFieldNames = () => {
			const rows = Array.from(tableBody.querySelectorAll('tr'));
			rows.forEach((row, idx) => {
				row.dataset.rowIndex = String(idx);
				const orderInput = row.querySelector('input[name^="group_order["]');
				if (orderInput) {
					orderInput.name = 'group_order[' + idx + ']';
					orderInput.value = String((idx + 1) * 10);
				}
				const fieldMap = {
					'group_enabled[': 'input[type="checkbox"][name^="group_enabled["]',
					'group_name_vi[': 'input[name^="group_name_vi["]',
					'group_name_en[': 'input[name^="group_name_en["]',
					'group_layout[': 'select[name^="group_layout["]',
					'group_desc_vi[': 'input[name^="group_desc_vi["]',
					'group_desc_en[': 'input[name^="group_desc_en["]',
					'group_slug[': 'input[name^="group_slug["]',
					'group_slug_aliases[': 'input[name^="group_slug_aliases["]'
				};
				Object.entries(fieldMap).forEach(([prefix, selector]) => {
					const input = row.querySelector(selector);
					if (input) input.name = prefix + idx + ']';
				});

				row.querySelectorAll('input[name^="group_post_type["]').forEach((input) => {
					input.name = 'group_post_type[' + idx + '][]';
				});
				syncPostTypesDataset(row);

				const slugInput = row.querySelector('input.bhdt-group-slug');
				const slugList = row.querySelector('datalist.bhdt-slug-list');
				if (slugInput && slugList) {
					slugList.id = 'bhdt-slug-list-' + idx;
					slugInput.setAttribute('list', slugList.id);
				}
			});
		};

		const optionsForPostTypes = (postTypes) => {
			const bucket = new Map();
			(postTypes.length ? postTypes : ['post']).forEach((postType) => {
				const suggestions = Array.isArray(slugSuggestionMap[postType]) ? slugSuggestionMap[postType] : [];
				suggestions.forEach((item) => {
					if (item && item.slug && !bucket.has(item.slug)) {
						bucket.set(item.slug, item);
					}
				});
			});
			return Array.from(bucket.values());
		};

		const setSlugValidity = (row, isValid, message = '') => {
			const slugInput = row.querySelector('input.bhdt-group-slug');
			const hint = row.querySelector('.bhdt-slug-hint');
			if (!slugInput || !hint) return;

			slugInput.classList.toggle('bhdt-invalid-slug', !isValid);
			hint.classList.toggle('bhdt-invalid-text', !isValid);
			if (!isValid) {
				hint.textContent = message || 'Slug không hợp lệ cho loại nội dung đã chọn.';
			}
		};

		const syncSlugSuggestions = (row) => {
			if (!row) return;
			const slugList = row.querySelector('datalist.bhdt-slug-list');
			const hint = row.querySelector('.bhdt-slug-hint');
			if (!slugList) return;

			const suggestions = optionsForPostTypes(getSelectedPostTypes(row));
			syncPostTypesDataset(row);
			slugList.innerHTML = '';

			suggestions.forEach((item) => {
				const option = document.createElement('option');
				option.value = item.slug;
				option.label = item.label || item.slug;
				slugList.appendChild(option);
			});

			if (hint) {
				const topLabels = suggestions.slice(0, 3).map((item) => item.label || item.slug);
				hint.textContent = topLabels.length
					? 'Gợi ý: ' + topLabels.join(' | ')
					: 'Chưa có gợi ý, bạn có thể nhập slug thủ công.';
				hint.classList.remove('bhdt-invalid-text');
			}

			setSlugValidity(row, true);
		};

		const validateRowSlug = (row) => {
			if (row) {
				setSlugValidity(row, true);
			}
			return true;
		};

		const applyPostTypeFilter = () => {
			if (!postTypeFilter) return;
			const selectedType = postTypeFilter.value;
			tableBody.querySelectorAll('tr').forEach((row) => {
				const rowTypes = (row.dataset.postTypes || 'post').split(/\s+/);
				row.style.display = (!selectedType || rowTypes.includes(selectedType)) ? '' : 'none';
			});
		};

		const createRowHtml = (idx, values = {}) => {
			const defaultOrder = String((idx + 1) * 10);
			const checked = values.enabled === false ? '' : ' checked';
			return '' +
				'<td><span class="bhdt-drag-handle" title="Kéo để đổi vị trí" aria-label="Kéo để đổi vị trí">↕</span></td>' +
				'<td><input type="checkbox" name="group_enabled[' + idx + ']" value="1"' + checked + '></td>' +
				'<td><input class="regular-text" type="text" name="group_name_vi[' + idx + ']" value="' + escapeAttr(values.name_vi) + '"></td>' +
				'<td><input class="regular-text" type="text" name="group_name_en[' + idx + ']" value="' + escapeAttr(values.name_en) + '"></td>' +
				'<td><select name="group_layout[' + idx + ']"><option value="1"' + (String(values.layout || '1') === '1' ? ' selected' : '') + '>Bố cục 1</option><option value="2"' + (String(values.layout || '1') === '2' ? ' selected' : '') + '>Bố cục 2</option><option value="3"' + (String(values.layout || '1') === '3' ? ' selected' : '') + '>Bố cục 3</option></select></td>' +
				'<td>' + renderPostTypeChecks(idx, values.post_types || ['post']) + '</td>' +
				'<td><input class="regular-text" type="text" name="group_desc_vi[' + idx + ']" value="' + escapeAttr(values.desc_vi) + '"></td>' +
				'<td><input class="regular-text" type="text" name="group_desc_en[' + idx + ']" value="' + escapeAttr(values.desc_en) + '"></td>' +
				'<td>' +
					'<input class="regular-text bhdt-group-slug" type="text" name="group_slug[' + idx + ']" value="' + escapeAttr(values.slug) + '" list="bhdt-slug-list-' + idx + '">' +
					'<input class="bhdt-group-slug-aliases" type="hidden" name="group_slug_aliases[' + idx + ']" value="' + escapeAttr(values.slug_aliases) + '">' +
					'<datalist class="bhdt-slug-list" id="bhdt-slug-list-' + idx + '"></datalist>' +
					'<p class="description bhdt-slug-hint">Chọn gợi ý hoặc nhập slug mới.</p>' +
				'</td>' +
				'<td><input type="number" min="0" step="1" readonly name="group_order[' + idx + ']" value="' + defaultOrder + '"></td>' +
				'<td><button type="button" class="button bhdt-duplicate-row">Nhân bản</button></td>' +
				'<td><button type="button" class="button bhdt-remove-row">Xóa dòng</button></td>';
		};

		const bindRemove = (btn) => {
			if (!btn) return;
			btn.addEventListener('click', function () {
				const row = this.closest('tr');
				if (row) {
					row.remove();
					reorderFieldNames();
				}
			});
		};

		const bindDuplicate = (btn) => {
			if (!btn) return;
			btn.addEventListener('click', function () {
				const row = this.closest('tr');
				if (!row) return;

				const idx = tableBody.querySelectorAll('tr').length;
				const clone = document.createElement('tr');
				clone.className = 'bhdt-sortable-row';
				clone.setAttribute('draggable', 'true');

				clone.innerHTML = createRowHtml(idx, {
					name_vi: row.querySelector('input[name^="group_name_vi["]')?.value || '',
					name_en: row.querySelector('input[name^="group_name_en["]')?.value || '',
					layout: row.querySelector('select[name^="group_layout["]')?.value || '1',
					desc_vi: row.querySelector('input[name^="group_desc_vi["]')?.value || '',
					desc_en: row.querySelector('input[name^="group_desc_en["]')?.value || '',
					slug: row.querySelector('input[name^="group_slug["]')?.value || '',
					slug_aliases: row.querySelector('input[name^="group_slug_aliases["]')?.value || '',
					post_types: getSelectedPostTypes(row),
					enabled: row.querySelector('input[type="checkbox"][name^="group_enabled["]')?.checked !== false
				});

				row.insertAdjacentElement('afterend', clone);
				bindRow(clone);
				reorderFieldNames();
			});
		};

		const bindDrag = (row) => {
			if (!row) return;
			row.addEventListener('dragstart', function () {
				row.classList.add('is-dragging');
			});
			row.addEventListener('dragend', function () {
				row.classList.remove('is-dragging');
				reorderFieldNames();
			});
		};

		const bindRow = (row) => {
			bindRemove(row.querySelector('.bhdt-remove-row'));
			bindDuplicate(row.querySelector('.bhdt-duplicate-row'));
			bindDrag(row);
			const postTypeInputs = row.querySelectorAll('input[name^="group_post_type["]');
			const titleInput = row.querySelector('input[name^="group_name_vi["]');
			const slugInput = row.querySelector('input.bhdt-group-slug');
			if (titleInput) {
				titleInput.dataset.bhdtLastTitle = titleInput.value;
				titleInput.addEventListener('input', () => {
					syncSlugFromTitle(row);
					validateRowSlug(row);
				});
			}
			if (postTypeInputs.length) {
				postTypeInputs.forEach((input) => input.addEventListener('change', () => {
					syncSlugSuggestions(row);
					validateRowSlug(row);
					applyPostTypeFilter();
				}));
			}
			if (slugInput) {
				slugInput.addEventListener('input', () => {
					validateRowSlug(row);
				});
				slugInput.addEventListener('blur', () => {
					validateRowSlug(row);
				});
			}
			syncSlugSuggestions(row);
			validateRowSlug(row);
		};

		tableBody.addEventListener('dragover', function (event) {
			event.preventDefault();
			const dragging = tableBody.querySelector('.is-dragging');
			if (!dragging) return;

			const rows = Array.from(tableBody.querySelectorAll('tr:not(.is-dragging)'));
			let next = null;
			for (const row of rows) {
				const rect = row.getBoundingClientRect();
				if (event.clientY < rect.top + rect.height / 2) {
					next = row;
					break;
				}
			}

			if (next) {
				tableBody.insertBefore(dragging, next);
			} else {
				tableBody.appendChild(dragging);
			}
		});

		document.querySelectorAll('#bhdt-home-blocks-table tbody tr').forEach(bindRow);

		if (postTypeFilter) {
			postTypeFilter.addEventListener('change', applyPostTypeFilter);
		}

		const form = tableBody.closest('form');
		if (form) {
			form.addEventListener('submit', (event) => {
				let firstInvalidInput = null;
				const allRows = Array.from(tableBody.querySelectorAll('tr'));
				allRows.forEach((row) => {
					if (!validateRowSlug(row) && !firstInvalidInput) {
						firstInvalidInput = row.querySelector('input.bhdt-group-slug');
					}
				});
				if (firstInvalidInput) {
					event.preventDefault();
					firstInvalidInput.focus();
				}
			});
		}

		addBtn.addEventListener('click', function () {
			const idx = tableBody.querySelectorAll('tr').length;
			const tr = document.createElement('tr');
			tr.className = 'bhdt-sortable-row';
			tr.setAttribute('draggable', 'true');
			tr.innerHTML = createRowHtml(idx);
			tableBody.appendChild(tr);
			bindRow(tr);
			reorderFieldNames();
			applyPostTypeFilter();
		});

		reorderFieldNames();
		applyPostTypeFilter();
	})();
	</script>
	<style>
	.bhdt-home-blocks-tools {
		margin: 10px 0 14px;
		display: flex;
		align-items: center;
		gap: 10px;
	}
	#bhdt-home-blocks-table .bhdt-drag-handle {
		cursor: move;
		display: inline-block;
		font-size: 16px;
		line-height: 1;
		padding: 6px 2px;
	}
	#bhdt-home-blocks-table tr.is-dragging {
		opacity: 0.55;
		background: #f6f7f7;
	}
	#bhdt-home-blocks-table .bhdt-post-type-checks {
		display: grid;
		gap: 4px;
		min-width: 135px;
	}
	#bhdt-home-blocks-table .bhdt-post-type-checks label {
		display: flex;
		align-items: center;
		gap: 6px;
		margin: 0;
		white-space: nowrap;
	}
	#bhdt-home-blocks-table .bhdt-group-slug.bhdt-invalid-slug {
		border-color: #d63638;
		box-shadow: 0 0 0 1px #d63638;
	}
	#bhdt-home-blocks-table .bhdt-slug-hint.bhdt-invalid-text {
		color: #d63638;
	}
	</style>
	<?php
}

function bhdt_wirecutter_default_menu_blocks() {
	$review_archive = get_post_type_archive_link( 'bhdt_review' );
	if ( ! $review_archive ) {
		$review_archive = home_url( '/?post_type=bhdt_review' );
	}

	$comparison_search = static function ( $keyword ) {
		return add_query_arg(
			array(
				'post_type' => 'bhdt_comparison',
				's'         => $keyword,
			),
			home_url( '/' )
		);
	};

	$review_search = static function ( $keyword ) {
		return add_query_arg(
			array(
				'post_type' => 'bhdt_review',
				's'         => $keyword,
			),
			home_url( '/' )
		);
	};

	return array(
		array(
			'label_vi' => 'Nguồn & Sạc',
			'label_en' => 'Power & Charging',
			'icon'     => '⚡',
			'url'      => $review_search( 'nguon adapter' ),
			'order'    => 10,
			'enabled'  => 1,
			'children' => array(
				array( 'label_vi' => 'Review bộ nguồn tổ ong', 'label_en' => 'Switching power supply reviews', 'url' => $review_search( 'bo nguon to ong' ), 'order' => 10, 'enabled' => 1 ),
				array( 'label_vi' => 'Adapter DC tốt nhất', 'label_en' => 'Best DC adapters', 'url' => $review_search( 'adapter dc' ), 'order' => 20, 'enabled' => 1 ),
				array( 'label_vi' => 'So sánh module sạc pin', 'label_en' => 'Battery charger module comparisons', 'url' => $comparison_search( 'module sac pin' ), 'order' => 30, 'enabled' => 1 ),
				array( 'label_vi' => 'Tổng hợp review mạch nguồn', 'label_en' => 'Power board review roundup', 'url' => $review_archive, 'order' => 40, 'enabled' => 1 ),
			),
		),
		array(
			'label_vi' => 'Đo lường & Test',
			'label_en' => 'Measurement & Test',
			'icon'     => '⌁',
			'url'      => $review_search( 'dong ho van nang' ),
			'order'    => 20,
			'enabled'  => 1,
			'children' => array(
				array( 'label_vi' => 'Top đồng hồ vạn năng', 'label_en' => 'Top multimeters', 'url' => $review_search( 'dong ho van nang' ), 'order' => 10, 'enabled' => 1 ),
				array( 'label_vi' => 'Review máy hiện sóng mini', 'label_en' => 'Mini oscilloscope reviews', 'url' => $review_search( 'may hien song' ), 'order' => 20, 'enabled' => 1 ),
				array( 'label_vi' => 'So sánh máy đo LCR', 'label_en' => 'LCR meter comparisons', 'url' => $comparison_search( 'lcr meter' ), 'order' => 30, 'enabled' => 1 ),
				array( 'label_vi' => 'Tổng hợp review thiết bị đo', 'label_en' => 'Test gear roundup', 'url' => $review_archive, 'order' => 40, 'enabled' => 1 ),
			),
		),
		array(
			'label_vi' => 'Vi điều khiển',
			'label_en' => 'Microcontrollers',
			'icon'     => '▣',
			'url'      => $review_search( 'esp32 stm32 arduino' ),
			'order'    => 30,
			'enabled'  => 1,
			'children' => array(
				array( 'label_vi' => 'Review board ESP32', 'label_en' => 'ESP32 board reviews', 'url' => $review_search( 'esp32 devkit' ), 'order' => 10, 'enabled' => 1 ),
				array( 'label_vi' => 'Review board STM32', 'label_en' => 'STM32 board reviews', 'url' => $review_search( 'stm32' ), 'order' => 20, 'enabled' => 1 ),
				array( 'label_vi' => 'So sánh Arduino vs ESP32', 'label_en' => 'Arduino vs ESP32 comparisons', 'url' => $comparison_search( 'arduino esp32' ), 'order' => 30, 'enabled' => 1 ),
				array( 'label_vi' => 'Tổng hợp review MCU', 'label_en' => 'MCU review roundup', 'url' => $review_archive, 'order' => 40, 'enabled' => 1 ),
			),
		),
		array(
			'label_vi' => 'Cảm biến',
			'label_en' => 'Sensors',
			'icon'     => '◇',
			'url'      => $review_search( 'cam bien nhiet do do am' ),
			'order'    => 40,
			'enabled'  => 1,
			'children' => array(
				array( 'label_vi' => 'Review cảm biến nhiệt độ', 'label_en' => 'Temperature sensor reviews', 'url' => $review_search( 'cam bien nhiet do' ), 'order' => 10, 'enabled' => 1 ),
				array( 'label_vi' => 'Review cảm biến chuyển động', 'label_en' => 'Motion sensor reviews', 'url' => $review_search( 'cam bien chuyen dong' ), 'order' => 20, 'enabled' => 1 ),
				array( 'label_vi' => 'So sánh cảm biến khí', 'label_en' => 'Gas sensor comparisons', 'url' => $comparison_search( 'cam bien khi' ), 'order' => 30, 'enabled' => 1 ),
				array( 'label_vi' => 'Tổng hợp review cảm biến', 'label_en' => 'Sensor review roundup', 'url' => $review_archive, 'order' => 40, 'enabled' => 1 ),
			),
		),
		array(
			'label_vi' => 'IoT & Kết nối',
			'label_en' => 'IoT & Connectivity',
			'icon'     => '◌',
			'url'      => $review_search( 'wifi ble zigbee' ),
			'order'    => 50,
			'enabled'  => 1,
			'children' => array(
				array( 'label_vi' => 'Review module WiFi', 'label_en' => 'WiFi module reviews', 'url' => $review_search( 'module wifi' ), 'order' => 10, 'enabled' => 1 ),
				array( 'label_vi' => 'Review module BLE', 'label_en' => 'BLE module reviews', 'url' => $review_search( 'bluetooth ble' ), 'order' => 20, 'enabled' => 1 ),
				array( 'label_vi' => 'So sánh Zigbee và LoRa', 'label_en' => 'Zigbee and LoRa comparisons', 'url' => $comparison_search( 'zigbee lora' ), 'order' => 30, 'enabled' => 1 ),
				array( 'label_vi' => 'Tổng hợp review IoT', 'label_en' => 'IoT review roundup', 'url' => $review_archive, 'order' => 40, 'enabled' => 1 ),
			),
		),
		array(
			'label_vi' => 'Hiển thị',
			'label_en' => 'Displays',
			'icon'     => '▤',
			'url'      => $review_search( 'lcd oled tft' ),
			'order'    => 60,
			'enabled'  => 1,
			'children' => array(
				array( 'label_vi' => 'Review màn hình OLED', 'label_en' => 'OLED display reviews', 'url' => $review_search( 'oled' ), 'order' => 10, 'enabled' => 1 ),
				array( 'label_vi' => 'Review màn hình TFT', 'label_en' => 'TFT display reviews', 'url' => $review_search( 'tft' ), 'order' => 20, 'enabled' => 1 ),
				array( 'label_vi' => 'So sánh LCD và OLED', 'label_en' => 'LCD and OLED comparisons', 'url' => $comparison_search( 'lcd oled' ), 'order' => 30, 'enabled' => 1 ),
				array( 'label_vi' => 'Tổng hợp review hiển thị', 'label_en' => 'Display review roundup', 'url' => $review_archive, 'order' => 40, 'enabled' => 1 ),
			),
		),
		array(
			'label_vi' => 'Âm thanh',
			'label_en' => 'Audio',
			'icon'     => '◍',
			'url'      => $review_search( 'amp dac loa module' ),
			'order'    => 70,
			'enabled'  => 1,
			'children' => array(
				array( 'label_vi' => 'Review mạch khuếch đại', 'label_en' => 'Amplifier board reviews', 'url' => $review_search( 'mach khuech dai' ), 'order' => 10, 'enabled' => 1 ),
				array( 'label_vi' => 'Review module DAC', 'label_en' => 'DAC module reviews', 'url' => $review_search( 'dac module' ), 'order' => 20, 'enabled' => 1 ),
				array( 'label_vi' => 'So sánh loa mini', 'label_en' => 'Mini speaker comparisons', 'url' => $comparison_search( 'loa mini' ), 'order' => 30, 'enabled' => 1 ),
				array( 'label_vi' => 'Tổng hợp review âm thanh', 'label_en' => 'Audio review roundup', 'url' => $review_archive, 'order' => 40, 'enabled' => 1 ),
			),
		),
		array(
			'label_vi' => 'Robot & Điều khiển',
			'label_en' => 'Robotics & Control',
			'icon'     => '✦',
			'url'      => $review_search( 'servo step motor driver' ),
			'order'    => 80,
			'enabled'  => 1,
			'children' => array(
				array( 'label_vi' => 'Review driver động cơ', 'label_en' => 'Motor driver reviews', 'url' => $review_search( 'driver dong co' ), 'order' => 10, 'enabled' => 1 ),
				array( 'label_vi' => 'Review servo & step motor', 'label_en' => 'Servo and stepper reviews', 'url' => $review_search( 'servo step motor' ), 'order' => 20, 'enabled' => 1 ),
				array( 'label_vi' => 'So sánh kit robot', 'label_en' => 'Robot kit comparisons', 'url' => $comparison_search( 'kit robot' ), 'order' => 30, 'enabled' => 1 ),
				array( 'label_vi' => 'Tổng hợp review robot', 'label_en' => 'Robot review roundup', 'url' => $review_archive, 'order' => 40, 'enabled' => 1 ),
			),
		),
		array(
			'label_vi' => 'Linh kiện cơ bản',
			'label_en' => 'Basic Components',
			'icon'     => '▥',
			'url'      => $review_search( 'dien tro tu dien diode transistor' ),
			'order'    => 90,
			'enabled'  => 1,
			'children' => array(
				array( 'label_vi' => 'Review tụ điện thông dụng', 'label_en' => 'Common capacitor reviews', 'url' => $review_search( 'tu dien' ), 'order' => 10, 'enabled' => 1 ),
				array( 'label_vi' => 'Review transistor và MOSFET', 'label_en' => 'Transistor and MOSFET reviews', 'url' => $review_search( 'transistor mosfet' ), 'order' => 20, 'enabled' => 1 ),
				array( 'label_vi' => 'So sánh diode chỉnh lưu', 'label_en' => 'Rectifier diode comparisons', 'url' => $comparison_search( 'diode chinh luu' ), 'order' => 30, 'enabled' => 1 ),
				array( 'label_vi' => 'Tổng hợp review linh kiện nền tảng', 'label_en' => 'Core components roundup', 'url' => $review_archive, 'order' => 40, 'enabled' => 1 ),
			),
		),
		array(
			'label_vi' => 'Phụ kiện Maker',
			'label_en' => 'Maker Accessories',
			'icon'     => '◆',
			'url'      => $review_search( 'breadboard day dup kep han' ),
			'order'    => 100,
			'enabled'  => 1,
			'children' => array(
				array( 'label_vi' => 'Review breadboard', 'label_en' => 'Breadboard reviews', 'url' => $review_search( 'breadboard' ), 'order' => 10, 'enabled' => 1 ),
				array( 'label_vi' => 'Review mỏ hàn và phụ kiện hàn', 'label_en' => 'Soldering iron and accessory reviews', 'url' => $review_search( 'mo han' ), 'order' => 20, 'enabled' => 1 ),
				array( 'label_vi' => 'So sánh bộ dây Dupont', 'label_en' => 'Dupont wire kit comparisons', 'url' => $comparison_search( 'day dupont' ), 'order' => 30, 'enabled' => 1 ),
				array( 'label_vi' => 'Tổng hợp review phụ kiện maker', 'label_en' => 'Maker accessory roundup', 'url' => $review_archive, 'order' => 40, 'enabled' => 1 ),
			),
		),
	);
}

function bhdt_wirecutter_clean_menu_blocks( $rows ) {
	if ( ! is_array( $rows ) ) {
		return array();
	}

	$clean = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$label_vi = isset( $row['label_vi'] ) ? sanitize_text_field( $row['label_vi'] ) : '';
		$label_en = isset( $row['label_en'] ) ? sanitize_text_field( $row['label_en'] ) : '';
		$url      = isset( $row['url'] ) ? esc_url_raw( $row['url'] ) : '';
		$icon     = isset( $row['icon'] ) ? sanitize_text_field( $row['icon'] ) : '';
		$order    = isset( $row['order'] ) ? (int) $row['order'] : 0;
		$enabled  = isset( $row['enabled'] ) ? (int) $row['enabled'] : 1;

		if ( '' === $label_vi ) {
			continue;
		}

		$children = array();
		if ( isset( $row['children'] ) && is_array( $row['children'] ) ) {
			foreach ( $row['children'] as $child ) {
				if ( ! is_array( $child ) ) {
					continue;
				}
				$child_label_vi = isset( $child['label_vi'] ) ? sanitize_text_field( $child['label_vi'] ) : '';
				$child_label_en = isset( $child['label_en'] ) ? sanitize_text_field( $child['label_en'] ) : '';
				$child_url      = isset( $child['url'] ) ? esc_url_raw( $child['url'] ) : '';
				if ( '' === $child_label_vi ) {
					continue;
				}
				$children[] = array(
					'label_vi' => $child_label_vi,
					'label_en' => $child_label_en,
					'url'      => '' !== $child_url ? $child_url : '#',
					'order'    => isset( $child['order'] ) ? (int) $child['order'] : 0,
					'enabled'  => isset( $child['enabled'] ) ? (int) $child['enabled'] : 1,
				);
			}
		}

		usort(
			$children,
			static function ( $a, $b ) {
				return (int) $a['order'] <=> (int) $b['order'];
			}
		);

		$clean[] = array(
			'label_vi' => $label_vi,
			'label_en' => $label_en,
			'icon'     => '' !== $icon ? $icon : '◆',
			'url'      => '' !== $url ? $url : '#',
			'order'    => $order,
			'enabled'  => $enabled,
			'children' => $children,
		);
	}

	usort(
		$clean,
		static function ( $a, $b ) {
			return (int) $a['order'] <=> (int) $b['order'];
		}
	);

	return $clean;
}

function bhdt_wirecutter_normalize_menu_url_key( $url ) {
	$normalized = bhdt_wirecutter_normalize_url_for_active( $url );

	return '' !== $normalized ? $normalized : esc_url_raw( (string) $url );
}

function bhdt_wirecutter_get_menu_linkable_post_types() {
	return apply_filters(
		'bhdt_wirecutter_menu_linkable_post_types',
		array( 'page', 'post', 'bhdt_review', 'bhdt_comparison' )
	);
}

function bhdt_wirecutter_get_post_type_menu_label( $post_type ) {
	$post_type_object = get_post_type_object( $post_type );
	if ( $post_type_object && ! empty( $post_type_object->labels->singular_name ) ) {
		return (string) $post_type_object->labels->singular_name;
	}

	return __( 'Nội dung', 'bhdt-wirecutter' );
}

function bhdt_wirecutter_get_content_link_children_map( $meta_key, $post_types = array() ) {
	$map = array();
	if ( '' === $meta_key ) {
		return $map;
	}

	$post_types = array_values( array_filter( array_map( 'sanitize_key', (array) $post_types ) ) );
	if ( empty( $post_types ) ) {
		$post_types = array( 'page' );
	}

	$slug_meta_key = '_bhdt_page_menu_block_parent_url' === $meta_key ? '_bhdt_page_menu_block_parent_slug' : '_bhdt_page_function_menu_parent_slug';

	$items = get_posts(
		array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => $meta_key,
					'compare' => 'EXISTS',
				),
				array(
					'key'     => $meta_key,
					'value'   => '',
					'compare' => '!=',
				),
			),
		)
	);

	foreach ( $items as $index => $item ) {
		$parent_url  = (string) get_post_meta( $item->ID, $meta_key, true );
		$parent_slug = sanitize_title( (string) get_post_meta( $item->ID, $slug_meta_key, true ) );
		if ( '' === $parent_slug && '' !== $parent_url ) {
			$parent_slug = sanitize_title( (string) wp_parse_url( $parent_url, PHP_URL_PATH ) );
			$query = (string) wp_parse_url( $parent_url, PHP_URL_QUERY );
			if ( '' !== $query ) {
				parse_str( $query, $query_args );
				if ( isset( $query_args['bhdt_menu_parent'] ) ) {
					$parent_slug = sanitize_title( (string) $query_args['bhdt_menu_parent'] );
				}
			}
		}
		$parent_key = bhdt_wirecutter_normalize_menu_url_key( $parent_url );
		$item_url   = get_permalink( $item->ID );
		if ( '' === $parent_key && '' === $parent_slug ) {
			continue;
		}
		if ( ! $item_url ) {
			continue;
		}

		$title = get_the_title( $item->ID );
		if ( '' === $title ) {
			continue;
		}

		$child_row = array(
			'label'          => $title,
			'label_vi'       => $title,
			'label_en'       => $title,
			'url'            => $item_url,
			'order'          => 9000 + ( (int) $item->menu_order * 10 ) + (int) $index,
			'enabled'        => 1,
			'source'         => 'content_link',
			'linked_post_id' => (int) $item->ID,
			'linked_post_type' => $item->post_type,
		);

		if ( '' !== $parent_key ) {
			if ( ! isset( $map[ $parent_key ] ) ) {
				$map[ $parent_key ] = array();
			}
			$map[ $parent_key ][] = $child_row;
		}

		if ( '' !== $parent_slug ) {
			$slug_key = 'slug:' . $parent_slug;
			if ( ! isset( $map[ $slug_key ] ) ) {
				$map[ $slug_key ] = array();
			}
			$map[ $slug_key ][] = $child_row;
		}
	}

	return $map;
}

function bhdt_wirecutter_get_page_link_children_map( $meta_key ) {
	return bhdt_wirecutter_get_content_link_children_map( $meta_key, array( 'page' ) );
}

function bhdt_wirecutter_get_menu_blocks( $include_disabled = false, $include_linked_children = null ) {
	$saved = get_option( 'bhdt_wire_menu_blocks', array() );
	if ( ! is_array( $saved ) || empty( $saved ) ) {
		$saved = bhdt_wirecutter_default_menu_blocks();
	}

	$rows  = bhdt_wirecutter_clean_menu_blocks( $saved );
	$is_en = function_exists( 'pll_current_language' ) && 'en' === pll_current_language( 'slug' );
	if ( null === $include_linked_children ) {
		$include_linked_children = ! $include_disabled;
	}
	$linked_children_map = $include_linked_children ? bhdt_wirecutter_get_content_link_children_map( '_bhdt_page_menu_block_parent_url', bhdt_wirecutter_get_menu_linkable_post_types() ) : array();

	// DEBUG: Log mapping keys and children for troubleshooting when WP_DEBUG is enabled.
	$debug_log = "=== FUNCTION MENU DEBUG " . date('Y-m-d H:i:s') . " ===\n";
	$debug_log .= "Total parents: " . count($rows) . "\n";
	$debug_log .= "Linked children map keys: " . implode(', ', array_keys($linked_children_map)) . "\n\n";
	foreach ($rows as $row) {
		$parent_key = bhdt_wirecutter_normalize_menu_url_key($row['url'] ?? '');
		$parent_slug_key = 'slug:' . sanitize_title($row['label_vi'] ?? '');
		$debug_log .= "Parent: {$row['label_vi']} | URL: {$row['url']}\n";
		$debug_log .= "  parent_key: $parent_key\n";
		$debug_log .= "  parent_slug_key: $parent_slug_key\n";
		if (isset($linked_children_map[$parent_key])) {
			$debug_log .= "  ✓ Found " . count($linked_children_map[$parent_key]) . " children by parent_key\n";
			foreach ($linked_children_map[$parent_key] as $child) {
				$debug_log .= "    - {$child['label_vi']} ({$child['url']})\n";
			}
		}
		if (isset($linked_children_map[$parent_slug_key])) {
			$debug_log .= "  ✓ Found " . count($linked_children_map[$parent_slug_key]) . " children by parent_slug_key\n";
			foreach ($linked_children_map[$parent_slug_key] as $child) {
				$debug_log .= "    - {$child['label_vi']} ({$child['url']})\n";
			}
		}
		$debug_log .= "\n";
	}
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		file_put_contents( WP_CONTENT_DIR . '/function_menu_debug.log', $debug_log, FILE_APPEND );
	}

	foreach ( $rows as $row_index => $row ) {
		if ( ! $include_disabled && 1 !== (int) $row['enabled'] ) {
			unset( $rows[ $row_index ] );
			continue;
		}

		$parent_key = bhdt_wirecutter_normalize_menu_url_key( $row['url'] ?? '' );
		$parent_landing_key = bhdt_wirecutter_normalize_menu_url_key( bhdt_wirecutter_build_parent_landing_url( 'mega', $row['label_vi'] ?? $row['label'] ?? '' ) );
		$parent_slug_key = 'slug:' . sanitize_title( $row['label_vi'] ?? '' );
		$linked_children = array();
		if ( isset( $linked_children_map[ $parent_key ] ) ) {
			$linked_children = array_merge( $linked_children, $linked_children_map[ $parent_key ] );
		}
		if ( isset( $linked_children_map[ $parent_landing_key ] ) ) {
			$linked_children = array_merge( $linked_children, $linked_children_map[ $parent_landing_key ] );
		}
		foreach ( bhdt_wirecutter_parent_alias_url_keys( 'mega', $row ) as $parent_alias_key ) {
			if ( isset( $linked_children_map[ $parent_alias_key ] ) ) {
				$linked_children = array_merge( $linked_children, $linked_children_map[ $parent_alias_key ] );
			}
		}
		if ( isset( $linked_children_map[ $parent_slug_key ] ) ) {
			$linked_children = array_merge( $linked_children, $linked_children_map[ $parent_slug_key ] );
		}
		if ( ! empty( $linked_children ) ) {
			$rows[ $row_index ]['children'] = array_merge( $rows[ $row_index ]['children'], $linked_children );
			$unique_children = array();
			foreach ( $rows[ $row_index ]['children'] as $child_row ) {
				$unique_key = (string) ( $child_row['url'] ?? '' ) . '|' . (string) ( $child_row['label_vi'] ?? '' );
				$unique_children[ $unique_key ] = $child_row;
			}
			$rows[ $row_index ]['children'] = array_values( $unique_children );
			usort(
				$rows[ $row_index ]['children'],
				static function ( $a, $b ) {
					return (int) $a['order'] <=> (int) $b['order'];
				}
			);
		}

		$rows[ $row_index ]['label'] = $is_en && '' !== $row['label_en'] ? $row['label_en'] : $row['label_vi'];
		foreach ( $row['children'] as $child_index => $child ) {
			if ( ! $include_disabled && 1 !== (int) $child['enabled'] ) {
				unset( $rows[ $row_index ]['children'][ $child_index ] );
				continue;
			}
			$rows[ $row_index ]['children'][ $child_index ]['label'] = $is_en && '' !== $child['label_en'] ? $child['label_en'] : $child['label_vi'];
		}
		$rows[ $row_index ]['children'] = array_values( $rows[ $row_index ]['children'] );
	}

	return array_values( $rows );
}

function bhdt_wirecutter_normalize_url_for_active( $url ) {
	$parts = wp_parse_url( html_entity_decode( (string) $url ) );
	if ( empty( $parts ) || ! is_array( $parts ) ) {
		return '';
	}

	$path = isset( $parts['path'] ) ? '/' . trim( $parts['path'], '/' ) : '/';
	$query = isset( $parts['query'] ) ? $parts['query'] : '';
	if ( '' !== $query ) {
		parse_str( $query, $query_args );
		ksort( $query_args );
		$query = http_build_query( $query_args );
	}

	return untrailingslashit( $path ) . ( '' !== $query ? '?' . $query : '' );
}

function bhdt_wirecutter_is_menu_url_active( $url ) {
	if ( '#' === $url || '' === $url ) {
		return false;
	}

	$current_url = home_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) );
	return bhdt_wirecutter_normalize_url_for_active( $current_url ) === bhdt_wirecutter_normalize_url_for_active( $url );
}

function bhdt_wirecutter_build_parent_landing_url( $type, $label ) {
	$slug      = sanitize_title( (string) $label );
	if ( '' === $slug ) {
		$slug = 'menu';
	}

	return home_url( '/' . $slug . '/' );
}

function bhdt_wirecutter_build_menu_label_url( $label ) {
	$slug = sanitize_title( (string) $label );
	if ( '' === $slug ) {
		$slug = 'menu';
	}

	return home_url( '/' . $slug . '/' );
}

function bhdt_wirecutter_menu_url_matches_label( $url, $label ) {
	$slug = sanitize_title( (string) $label );
	if ( '' === $slug || '' === (string) $url ) {
		return false;
	}

	$decoded_url = html_entity_decode( (string) $url );
	if ( false !== strpos( $decoded_url, '%20' ) || '' !== (string) wp_parse_url( $decoded_url, PHP_URL_QUERY ) ) {
		return false;
	}

	$path = (string) wp_parse_url( $decoded_url, PHP_URL_PATH );
	$path_slug = sanitize_title( basename( trim( rawurldecode( $path ), '/' ) ) );

	return $slug === $path_slug;
}

function bhdt_wirecutter_refresh_menu_block_urls( $rows ) {
	if ( ! is_array( $rows ) ) {
		return array();
	}

	foreach ( $rows as $row_index => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$label = $row['label_vi'] ?? '';
		if ( '' !== (string) $label && ! bhdt_wirecutter_menu_url_matches_label( $row['url'] ?? '', $label ) ) {
			$rows[ $row_index ]['url'] = bhdt_wirecutter_build_menu_label_url( $label );
		}

		if ( empty( $row['children'] ) || ! is_array( $row['children'] ) ) {
			continue;
		}

		foreach ( $row['children'] as $child_index => $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}

			$child_label = $child['label_vi'] ?? '';
			if ( '' !== (string) $child_label && ! bhdt_wirecutter_menu_url_matches_label( $child['url'] ?? '', $child_label ) ) {
				$rows[ $row_index ]['children'][ $child_index ]['url'] = bhdt_wirecutter_build_menu_label_url( $child_label );
			}
		}
	}

	return $rows;
}

function bhdt_wirecutter_parent_alias_url_keys( $type, $row ) {
	$label = sanitize_title( $row['label_vi'] ?? $row['label'] ?? '' );
	$aliases = array();

	if ( 'function' === $type ) {
		if ( 'cong-cu' === $label ) {
			$aliases[] = home_url( '/tien-ich-ky-thuat/' );
		} elseif ( 'du-an' === $label ) {
			$aliases[] = home_url( '/du-an-diy/' );
		} elseif ( 'bai-danh-gia' === $label ) {
			$aliases[] = get_post_type_archive_link( 'bhdt_review' ) ?: home_url( '/reviews-linh-kien/' );
		}
	}

	$keys = array();
	foreach ( $aliases as $alias ) {
		$key = bhdt_wirecutter_normalize_menu_url_key( $alias );
		if ( '' !== $key ) {
			$keys[] = $key;
		}
	}

	return array_values( array_unique( $keys ) );
}

function bhdt_wirecutter_force_existing_page_request( $query_vars ) {
	if ( is_admin() ) {
		return $query_vars;
	}

	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( '' === $path || false !== strpos( $path, '/' ) ) {
		return $query_vars;
	}

	global $wpdb;
	$page_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' AND post_name = %s LIMIT 1",
			$path
		)
	);

	if ( $page_id <= 0 ) {
		return $query_vars;
	}

	return array(
		'page_id' => $page_id,
	);
}
add_filter( 'request', 'bhdt_wirecutter_force_existing_page_request', 0 );

function bhdt_wirecutter_current_path_page_id() {
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( '' === $path || false !== strpos( $path, '/' ) ) {
		return 0;
	}

	global $wpdb;
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' AND post_name = %s LIMIT 1",
			$path
		)
	);
}

function bhdt_wirecutter_render_hot_key_landing_page() {
	if ( is_admin() ) {
		return;
	}

	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( '' === $path || false !== strpos( $path, '/' ) ) {
		return;
	}

	$hot_key = bhdt_wirecutter_find_hot_key_by_slug( $path );
	if ( ! is_array( $hot_key ) ) {
		return;
	}

	$keyword = (string) ( $hot_key['keyword'] ?? $hot_key['label_vi'] ?? '' );
	$title   = (string) ( $hot_key['label'] ?? $hot_key['label_vi'] ?? $keyword );
	if ( '' === $keyword ) {
		return;
	}

	$query = new WP_Query(
		array(
			'post_type'           => array( 'post', 'page', 'bhdt_review', 'bhdt_project', 'bhdt_comparison' ),
			'post_status'         => 'publish',
			'posts_per_page'      => 20,
			's'                   => $keyword,
			'ignore_sticky_posts' => true,
		)
	);

	global $wp_query;
	$wp_query->is_404 = false;
	$wp_query->is_page = false;
	$wp_query->is_home = false;
	$wp_query->is_front_page = false;

	status_header( 200 );
	nocache_headers();
	get_header();
	echo '<main class="bhdt-entry bhdt-hot-key-page">';
	echo '<article>';
	echo '<header class="bhdt-entry-header">';
	echo '<p class="bhdt-wire-kicker">' . esc_html__( 'Hot key', 'bhdt-wirecutter' ) . '</p>';
	echo '<h1 class="bhdt-entry-title">' . esc_html( $title ) . '</h1>';
	echo '<div class="bhdt-entry-meta">' . esc_html__( 'Các liên kết liên quan tới từ khóa này', 'bhdt-wirecutter' ) . '</div>';
	echo '</header>';
	echo '<div class="bhdt-entry-content">';

	if ( $query->have_posts() ) {
		echo '<ul class="bhdt-hot-key-results">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$excerpt = get_the_excerpt();
			if ( '' === trim( $excerpt ) ) {
				$excerpt = wp_trim_words( wp_strip_all_tags( get_the_content() ), 24, '...' );
			}
			echo '<li>';
			echo '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
			if ( '' !== trim( $excerpt ) ) {
				echo '<p>' . esc_html( $excerpt ) . '</p>';
			}
			echo '</li>';
		}
		echo '</ul>';
		wp_reset_postdata();
	} else {
		echo '<p>' . esc_html__( 'Chưa có liên kết phù hợp với từ khóa này.', 'bhdt-wirecutter' ) . '</p>';
		echo '<p><a href="' . esc_url( home_url( '/?s=' . rawurlencode( $keyword ) ) ) . '">' . esc_html__( 'Tìm kiếm toàn site', 'bhdt-wirecutter' ) . '</a></p>';
	}

	echo '</div>';
	echo '</article>';
	echo '</main>';
	get_footer();
	exit;
}
add_action( 'template_redirect', 'bhdt_wirecutter_render_hot_key_landing_page', -1 );

function bhdt_wirecutter_force_existing_page_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$page_id = bhdt_wirecutter_current_path_page_id();
	if ( $page_id <= 0 ) {
		return;
	}

	$query->set( 'page_id', $page_id );
	$query->set( 'pagename', '' );
	$query->set( 'name', '' );
	$query->set( 'post_type', 'page' );
}
add_action( 'pre_get_posts', 'bhdt_wirecutter_force_existing_page_query', 999 );

function bhdt_wirecutter_prevent_existing_page_404( $preempt, $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return $preempt;
	}

	$page_id = bhdt_wirecutter_current_path_page_id();
	if ( $page_id <= 0 ) {
		return $preempt;
	}

	$page = get_post( $page_id );
	if ( ! $page instanceof WP_Post ) {
		return $preempt;
	}

	$query->posts = array( $page );
	$query->post = $page;
	$query->queried_object = $page;
	$query->queried_object_id = $page_id;
	$query->found_posts = 1;
	$query->post_count = 1;
	$query->max_num_pages = 1;
	$query->is_page = true;
	$query->is_singular = true;
	$query->is_404 = false;
	$query->is_home = false;
	$query->is_front_page = false;

	return true;
}
add_filter( 'pre_handle_404', 'bhdt_wirecutter_prevent_existing_page_404', 0, 2 );

function bhdt_wirecutter_mark_existing_page_status() {
	if ( is_admin() ) {
		return;
	}

	$page_id = bhdt_wirecutter_current_path_page_id();
	if ( $page_id <= 0 ) {
		return;
	}

	$page = get_post( $page_id );
	if ( ! $page instanceof WP_Post ) {
		return;
	}

	global $wp_query;
	$wp_query->queried_object = $page;
	$wp_query->queried_object_id = $page_id;
	$wp_query->is_page = true;
	$wp_query->is_singular = true;
	$wp_query->is_404 = false;
	$wp_query->is_home = false;
	$wp_query->is_front_page = false;

	status_header( 200 );
}
add_action( 'wp', 'bhdt_wirecutter_mark_existing_page_status', 0 );

function bhdt_wirecutter_render_front_edit_button() {
	if ( is_admin() || ! is_singular() ) {
		return;
	}

	$post_id = get_queried_object_id();
	if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$edit_url = get_edit_post_link( $post_id, '' );
	if ( ! $edit_url ) {
		return;
	}
	?>
	<a class="bhdt-front-edit-button" href="<?php echo esc_url( $edit_url ); ?>">Sửa chữa</a>
	<?php
}
add_action( 'wp_footer', 'bhdt_wirecutter_render_front_edit_button' );

function bhdt_wirecutter_template_for_existing_page_path( $template ) {
	if ( is_admin() ) {
		return $template;
	}

	$page_id = bhdt_wirecutter_current_path_page_id();
	if ( $page_id <= 0 ) {
		return $template;
	}

	$page = get_post( $page_id );
	if ( ! $page instanceof WP_Post ) {
		return $template;
	}

	global $wp_query, $post;

	$post = $page;
	setup_postdata( $post );

	$wp_query->posts = array( $page );
	$wp_query->post = $page;
	$wp_query->queried_object = $page;
	$wp_query->queried_object_id = $page_id;
	$wp_query->found_posts = 1;
	$wp_query->post_count = 1;
	$wp_query->max_num_pages = 1;
	$wp_query->current_post = -1;
	$wp_query->is_page = true;
	$wp_query->is_singular = true;
	$wp_query->is_404 = false;
	$wp_query->is_home = false;
	$wp_query->is_front_page = false;

	status_header( 200 );

	$page_template = get_page_template();
	return $page_template ? $page_template : $template;
}
add_filter( 'template_include', 'bhdt_wirecutter_template_for_existing_page_path', 0 );

function bhdt_wirecutter_render_existing_page_on_404() {
	if ( is_admin() ) {
		return;
	}

	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( '' === $path ) {
		return;
	}

	$page = get_page_by_path( $path, OBJECT, 'page' );
	if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
		return;
	}
	if ( is_page( (int) $page->ID ) ) {
		return;
	}

	global $wp_query, $post;

	$wp_query = new WP_Query(
		array(
			'page_id'   => (int) $page->ID,
			'post_type' => 'page',
		)
	);

	if ( ! $wp_query->have_posts() ) {
		return;
	}

	$wp_query->the_post();
	$post = get_post( $page->ID );
	status_header( 200 );
	include get_page_template();
	exit;
}
add_action( 'template_redirect', 'bhdt_wirecutter_render_existing_page_on_404', 0 );

function bhdt_wirecutter_render_parent_landing_page() {
	if ( is_admin() ) {
		return;
	}

	$menu_type = isset( $_GET['bhdt_menu_type'] ) ? sanitize_key( wp_unslash( $_GET['bhdt_menu_type'] ) ) : '';
	$slug      = isset( $_GET['bhdt_menu_parent'] ) ? sanitize_title( wp_unslash( $_GET['bhdt_menu_parent'] ) ) : '';

	if ( '' === $slug && is_404() ) {
		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
		if ( '' !== $path && false === strpos( $path, '/' ) ) {
			$slug = sanitize_title( $path );
		}
	}

	if ( '' === $menu_type || '' === $slug ) {
		$menu_type = '';
	}
	if ( '' === $slug ) {
		return;
	}

	$found_sections = array();
	$sources = array(
		'mega'     => bhdt_wirecutter_get_menu_blocks(),
		'function' => bhdt_wirecutter_get_function_menu_blocks(),
	);

	foreach ( $sources as $source_type => $sections ) {
		if ( '' !== $menu_type && $source_type !== $menu_type ) {
			continue;
		}
		foreach ( $sections as $section ) {
			$section_slug = sanitize_title( $section['label_vi'] ?? $section['label'] ?? '' );
			if ( $section_slug !== $slug ) {
				continue;
			}
			$found_sections[] = array(
				'title'    => $section['label'] ?? ( $section['label_vi'] ?? '' ),
				'children' => isset( $section['children'] ) && is_array( $section['children'] ) ? $section['children'] : array(),
			);
		}
	}

	if ( empty( $found_sections ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		return;
	}

	status_header( 200 );
	nocache_headers();
	get_header();
	echo '<main class="bhdt-content"><div class="bhdt-content-inner">';
	foreach ( $found_sections as $section ) {
		echo '<section class="bhdt-archive-intro">';
		echo '<h1>' . esc_html( $section['title'] ) . '</h1>';
		echo '<p>' . esc_html__( 'Danh sách liên kết mục con', 'bhdt-wirecutter' ) . '</p>';
		echo '</section>';

		if ( empty( $section['children'] ) ) {
			echo '<p>' . esc_html__( 'Chưa có mục con nào.', 'bhdt-wirecutter' ) . '</p>';
		} else {
			echo '<ul class="bhdt-taxonomy-list">';
			foreach ( $section['children'] as $child ) {
				$child_label = isset( $child['label'] ) ? $child['label'] : ( $child['label_vi'] ?? '' );
				$child_url   = isset( $child['url'] ) ? $child['url'] : '#';
				echo '<li><a href="' . esc_url( $child_url ) . '">' . esc_html( $child_label ) . '</a></li>';
			}
			echo '</ul>';
		}
	}

	echo '</div></main>';
	get_footer();
	exit;
}
add_action( 'template_redirect', 'bhdt_wirecutter_render_parent_landing_page', 5 );

function bhdt_wirecutter_handle_menu_blocks_save() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-menu-blocks' !== $_GET['page'] ) {
		return;
	}

	$is_save_action    = isset( $_POST['bhdt_save_menu_blocks'] );
	$is_refresh_action = isset( $_POST['bhdt_refresh_menu_block_urls'] );
	if ( ! $is_save_action && ! $is_refresh_action ) {
		return;
	}

	check_admin_referer( 'bhdt_save_menu_blocks_nonce' );

	$parent_labels_vi = isset( $_POST['menu_parent_label_vi'] ) && is_array( $_POST['menu_parent_label_vi'] ) ? wp_unslash( $_POST['menu_parent_label_vi'] ) : array();
	$parent_labels_en = isset( $_POST['menu_parent_label_en'] ) && is_array( $_POST['menu_parent_label_en'] ) ? wp_unslash( $_POST['menu_parent_label_en'] ) : array();
	$parent_icons     = isset( $_POST['menu_parent_icon'] ) && is_array( $_POST['menu_parent_icon'] ) ? wp_unslash( $_POST['menu_parent_icon'] ) : array();
	$parent_urls      = isset( $_POST['menu_parent_url'] ) && is_array( $_POST['menu_parent_url'] ) ? wp_unslash( $_POST['menu_parent_url'] ) : array();
	$parent_orders    = isset( $_POST['menu_parent_order'] ) && is_array( $_POST['menu_parent_order'] ) ? wp_unslash( $_POST['menu_parent_order'] ) : array();
	$parent_enabled   = isset( $_POST['menu_parent_enabled'] ) && is_array( $_POST['menu_parent_enabled'] ) ? wp_unslash( $_POST['menu_parent_enabled'] ) : array();
	$child_labels_vi  = isset( $_POST['menu_child_label_vi'] ) && is_array( $_POST['menu_child_label_vi'] ) ? wp_unslash( $_POST['menu_child_label_vi'] ) : array();
	$child_labels_en  = isset( $_POST['menu_child_label_en'] ) && is_array( $_POST['menu_child_label_en'] ) ? wp_unslash( $_POST['menu_child_label_en'] ) : array();
	$child_urls       = isset( $_POST['menu_child_url'] ) && is_array( $_POST['menu_child_url'] ) ? wp_unslash( $_POST['menu_child_url'] ) : array();
	$child_orders     = isset( $_POST['menu_child_order'] ) && is_array( $_POST['menu_child_order'] ) ? wp_unslash( $_POST['menu_child_order'] ) : array();
	$child_enabled    = isset( $_POST['menu_child_enabled'] ) && is_array( $_POST['menu_child_enabled'] ) ? wp_unslash( $_POST['menu_child_enabled'] ) : array();

	$total = max( count( $parent_labels_vi ), count( $parent_labels_en ), count( $parent_icons ), count( $parent_urls ), count( $parent_orders ) );
	$rows = array();

	for ( $i = 0; $i < $total; $i++ ) {
		$children = array();
		$child_total = isset( $child_labels_vi[ $i ] ) && is_array( $child_labels_vi[ $i ] ) ? count( $child_labels_vi[ $i ] ) : 0;
		for ( $j = 0; $j < $child_total; $j++ ) {
			$children[] = array(
				'label_vi' => $child_labels_vi[ $i ][ $j ] ?? '',
				'label_en' => $child_labels_en[ $i ][ $j ] ?? '',
				'url'      => $child_urls[ $i ][ $j ] ?? '',
				'order'    => $child_orders[ $i ][ $j ] ?? ( ( $j + 1 ) * 10 ),
				'enabled'  => isset( $child_enabled[ $i ][ $j ] ) ? 1 : 0,
			);
		}

		$rows[] = array(
			'label_vi' => $parent_labels_vi[ $i ] ?? '',
			'label_en' => $parent_labels_en[ $i ] ?? '',
			'icon'     => $parent_icons[ $i ] ?? '',
			'url'      => $parent_urls[ $i ] ?? '',
			'order'    => $parent_orders[ $i ] ?? ( ( $i + 1 ) * 10 ),
			'enabled'  => isset( $parent_enabled[ $i ] ) ? 1 : 0,
			'children' => $children,
		);
	}

	$rows = bhdt_wirecutter_clean_menu_blocks( $rows );
	if ( $is_refresh_action ) {
		$rows = bhdt_wirecutter_clean_menu_blocks( bhdt_wirecutter_refresh_menu_block_urls( $rows ) );
	}

	update_option( 'bhdt_wire_menu_blocks', $rows, false );
	wp_safe_redirect( admin_url( 'themes.php?page=bhdt-menu-blocks&updated=1' . ( $is_refresh_action ? '&refreshed=1' : '' ) ) );
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_menu_blocks_save' );

function bhdt_wirecutter_render_menu_blocks_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập.', 'bhdt-wirecutter' ) );
	}

	$rows = bhdt_wirecutter_get_menu_blocks( true );
	if ( empty( $rows ) ) {
		$rows = bhdt_wirecutter_default_menu_blocks();
	}
	?>
	<div class="wrap">
		<h1>Khối Menu</h1>
		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo isset( $_GET['refreshed'] ) ? 'Đã refresh URL theo tên tiêu đề.' : 'Đã lưu cấu hình Khối Menu.'; ?></p></div>
		<?php endif; ?>
		<form method="post" id="bhdt-menu-blocks-form">
			<?php wp_nonce_field( 'bhdt_save_menu_blocks_nonce' ); ?>
			<p class="bhdt-menu-admin-tools">
				<button type="submit" class="button button-secondary" name="bhdt_refresh_menu_block_urls" value="1">Refresh URL theo tiêu đề</button>
				<span class="description">Tự đổi URL cha/con chưa khớp về dạng slug gạch ngang, ví dụ: /nguon-sac/.</span>
			</p>
			<div id="bhdt-menu-blocks-list">
				<?php foreach ( array_values( $rows ) as $idx => $row ) : ?>
					<div class="bhdt-menu-admin-parent" draggable="true">
						<div class="bhdt-menu-admin-parent-head">
							<span class="bhdt-menu-admin-drag">↕</span>
							<label><input type="checkbox" name="menu_parent_enabled[<?php echo esc_attr( $idx ); ?>]" value="1" <?php checked( ! empty( $row['enabled'] ) ); ?>> Hiển thị</label>
							<label>Icon <input class="small-text" type="text" name="menu_parent_icon[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['icon'] ?? '' ); ?>"></label>
							<label>Tên VI <input class="regular-text" type="text" name="menu_parent_label_vi[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['label_vi'] ?? '' ); ?>"></label>
							<label>Tên EN <input class="regular-text" type="text" name="menu_parent_label_en[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['label_en'] ?? '' ); ?>"></label>
						</div>
						<div class="bhdt-menu-admin-parent-fields">
							<label>URL cha <input class="large-text" type="text" name="menu_parent_url[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['url'] ?? '' ); ?>"></label>
							<label>Thứ tự <input class="small-text bhdt-parent-order" readonly type="number" name="menu_parent_order[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( (string) ( $row['order'] ?? 0 ) ); ?>"></label>
							<button type="button" class="button bhdt-add-child-row">Thêm thư mục con</button>
							<button type="button" class="button bhdt-remove-parent-row">Xóa thư mục cha</button>
						</div>
						<table class="widefat striped bhdt-menu-admin-children">
							<thead><tr><th>Hiển thị</th><th>Tên VI</th><th>Tên EN</th><th>URL con</th><th>Thứ tự</th><th>Xóa</th></tr></thead>
							<tbody>
							<?php foreach ( array_values( $row['children'] ?? array() ) as $child_idx => $child ) : ?>
								<tr>
									<td><input type="checkbox" name="menu_child_enabled[<?php echo esc_attr( $idx ); ?>][<?php echo esc_attr( $child_idx ); ?>]" value="1" <?php checked( ! empty( $child['enabled'] ) ); ?>></td>
									<td><input class="regular-text" type="text" name="menu_child_label_vi[<?php echo esc_attr( $idx ); ?>][<?php echo esc_attr( $child_idx ); ?>]" value="<?php echo esc_attr( $child['label_vi'] ?? '' ); ?>"></td>
									<td><input class="regular-text" type="text" name="menu_child_label_en[<?php echo esc_attr( $idx ); ?>][<?php echo esc_attr( $child_idx ); ?>]" value="<?php echo esc_attr( $child['label_en'] ?? '' ); ?>"></td>
									<td><input class="large-text" type="text" name="menu_child_url[<?php echo esc_attr( $idx ); ?>][<?php echo esc_attr( $child_idx ); ?>]" value="<?php echo esc_attr( $child['url'] ?? '' ); ?>"></td>
									<td><input class="small-text bhdt-child-order" readonly type="number" name="menu_child_order[<?php echo esc_attr( $idx ); ?>][<?php echo esc_attr( $child_idx ); ?>]" value="<?php echo esc_attr( (string) ( $child['order'] ?? 0 ) ); ?>"></td>
									<td><button type="button" class="button bhdt-remove-child-row">Xóa</button></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>
			</div>
			<p><button type="button" class="button" id="bhdt-add-menu-parent">Thêm thư mục cha</button></p>
			<?php submit_button( 'Lưu Khối Menu', 'primary', 'bhdt_save_menu_blocks' ); ?>
		</form>
	</div>
	<script>
	(function () {
		const list = document.getElementById('bhdt-menu-blocks-list');
		const addParentBtn = document.getElementById('bhdt-add-menu-parent');
		if (!list || !addParentBtn) return;
		const parentLandingBase = <?php echo wp_json_encode( home_url( '/' ) ); ?>;

		const slugify = (value) => String(value || '')
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-+|-+$/g, '') || 'menu';

		const buildParentLandingUrl = (label) => {
			const slug = slugify(label);
			const trimmedBase = String(parentLandingBase || '').replace(/\/+$/, '');
			return trimmedBase + '/' + slug + '/';
		};

		const urlMatchesLabel = (url, label) => {
			const slug = slugify(label);
			if (!slug || !url) return false;
			try {
				const parsed = new URL(url, parentLandingBase);
				const parts = parsed.pathname.replace(/^\/+|\/+$/g, '').split('/');
				return slugify(decodeURIComponent(parts.pop() || '')) === slug;
			} catch (error) {
				return false;
			}
		};

		const isLegacyGeneratedUrl = (url) => {
			if (!url) return true;
			try {
				const parsed = new URL(url, parentLandingBase);
				return Boolean(parsed.search) || /%20/i.test(url);
			} catch (error) {
				return /(?:\?|%20)/i.test(String(url));
			}
		};

		const shouldSyncUrl = (urlInput, previousLabel) => {
			const url = urlInput?.value || '';
			return !url || isLegacyGeneratedUrl(url) || urlMatchesLabel(url, previousLabel);
		};

		const syncParentUrl = (parent) => {
			const labelInput = parent.querySelector('input[name^="menu_parent_label_vi["]');
			const urlInput = parent.querySelector('input[name^="menu_parent_url["]');
			if (!labelInput || !urlInput) return;
			const previousLabel = labelInput.dataset.bhdtLastLabel || labelInput.value;
			if (shouldSyncUrl(urlInput, previousLabel)) {
				urlInput.value = buildParentLandingUrl(labelInput.value);
			}
			labelInput.dataset.bhdtLastLabel = labelInput.value;
		};

		const syncChildUrl = (row) => {
			const labelInput = row?.querySelector('input[name^="menu_child_label_vi["]');
			const urlInput = row?.querySelector('input[name^="menu_child_url["]');
			if (!labelInput || !urlInput) return;
			const previousLabel = labelInput.dataset.bhdtLastLabel || labelInput.value;
			if (shouldSyncUrl(urlInput, previousLabel)) {
				urlInput.value = buildParentLandingUrl(labelInput.value);
			}
			labelInput.dataset.bhdtLastLabel = labelInput.value;
		};

		const escapeAttr = (value) => String(value || '').replace(/[&<>'"]/g, (char) => ({
			'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
		}[char]));

		const childRowHtml = (parentIdx, childIdx, values = {}) => '' +
			'<tr>' +
				'<td><input type="checkbox" name="menu_child_enabled[' + parentIdx + '][' + childIdx + ']" value="1"' + (values.enabled === false ? '' : ' checked') + '></td>' +
				'<td><input class="regular-text" type="text" name="menu_child_label_vi[' + parentIdx + '][' + childIdx + ']" value="' + escapeAttr(values.label_vi) + '"></td>' +
				'<td><input class="regular-text" type="text" name="menu_child_label_en[' + parentIdx + '][' + childIdx + ']" value="' + escapeAttr(values.label_en) + '"></td>' +
				'<td><input class="large-text" type="text" name="menu_child_url[' + parentIdx + '][' + childIdx + ']" value="' + escapeAttr(values.url) + '"></td>' +
				'<td><input class="small-text bhdt-child-order" readonly type="number" name="menu_child_order[' + parentIdx + '][' + childIdx + ']" value="' + ((childIdx + 1) * 10) + '"></td>' +
				'<td><button type="button" class="button bhdt-remove-child-row">Xóa</button></td>' +
			'</tr>';

		const parentHtml = (idx) => '' +
			'<div class="bhdt-menu-admin-parent" draggable="true">' +
				'<div class="bhdt-menu-admin-parent-head">' +
					'<span class="bhdt-menu-admin-drag">↕</span>' +
					'<label><input type="checkbox" name="menu_parent_enabled[' + idx + ']" value="1" checked> Hiển thị</label>' +
					'<label>Icon <input class="small-text" type="text" name="menu_parent_icon[' + idx + ']" value="◆"></label>' +
					'<label>Tên VI <input class="regular-text" type="text" name="menu_parent_label_vi[' + idx + ']" value=""></label>' +
					'<label>Tên EN <input class="regular-text" type="text" name="menu_parent_label_en[' + idx + ']" value=""></label>' +
				'</div>' +
				'<div class="bhdt-menu-admin-parent-fields">' +
					'<label>URL cha <input class="large-text" type="text" name="menu_parent_url[' + idx + ']" value=""></label>' +
					'<label>Thứ tự <input class="small-text bhdt-parent-order" readonly type="number" name="menu_parent_order[' + idx + ']" value="' + ((idx + 1) * 10) + '"></label>' +
					'<button type="button" class="button bhdt-add-child-row">Thêm thư mục con</button> ' +
					'<button type="button" class="button bhdt-remove-parent-row">Xóa thư mục cha</button>' +
				'</div>' +
				'<table class="widefat striped bhdt-menu-admin-children">' +
					'<thead><tr><th>Hiển thị</th><th>Tên VI</th><th>Tên EN</th><th>URL con</th><th>Thứ tự</th><th>Xóa</th></tr></thead>' +
					'<tbody></tbody>' +
				'</table>' +
			'</div>';

		const reorder = () => {
			Array.from(list.querySelectorAll('.bhdt-menu-admin-parent')).forEach((parent, parentIdx) => {
				const parentFields = {
					'menu_parent_enabled[': 'input[name^="menu_parent_enabled["]',
					'menu_parent_label_vi[': 'input[name^="menu_parent_label_vi["]',
					'menu_parent_label_en[': 'input[name^="menu_parent_label_en["]',
					'menu_parent_url[': 'input[name^="menu_parent_url["]',
					'menu_parent_order[': 'input[name^="menu_parent_order["]'
				};
				Object.entries(parentFields).forEach(([prefix, selector]) => {
					const input = parent.querySelector(selector);
					if (input) input.name = prefix + parentIdx + ']';
				});
				const orderInput = parent.querySelector('.bhdt-parent-order');
				if (orderInput) orderInput.value = String((parentIdx + 1) * 10);

				Array.from(parent.querySelectorAll('.bhdt-menu-admin-children tbody tr')).forEach((row, childIdx) => {
					const childFields = {
						'menu_child_enabled[': 'input[name^="menu_child_enabled["]',
						'menu_child_label_vi[': 'input[name^="menu_child_label_vi["]',
						'menu_child_label_en[': 'input[name^="menu_child_label_en["]',
						'menu_child_url[': 'input[name^="menu_child_url["]',
						'menu_child_order[': 'input[name^="menu_child_order["]'
					};
					Object.entries(childFields).forEach(([prefix, selector]) => {
						const input = row.querySelector(selector);
						if (input) input.name = prefix + parentIdx + '][' + childIdx + ']';
					});
					const childOrder = row.querySelector('.bhdt-child-order');
					if (childOrder) childOrder.value = String((childIdx + 1) * 10);
				});
			});
		};

		const bindParent = (parent) => {
			const labelInput = parent.querySelector('input[name^="menu_parent_label_vi["]');
			if (labelInput) labelInput.dataset.bhdtLastLabel = labelInput.value;
			labelInput?.addEventListener('input', () => syncParentUrl(parent));
			syncParentUrl(parent);

			parent.querySelector('.bhdt-remove-parent-row')?.addEventListener('click', () => {
				parent.remove();
				reorder();
			});
			parent.querySelector('.bhdt-add-child-row')?.addEventListener('click', () => {
				const tbody = parent.querySelector('tbody');
				const parentIdx = Array.from(list.children).indexOf(parent);
				const childIdx = tbody ? tbody.querySelectorAll('tr').length : 0;
				if (tbody) {
					tbody.insertAdjacentHTML('beforeend', childRowHtml(parentIdx, childIdx));
					bindChild(tbody.lastElementChild);
					reorder();
				}
			});
			parent.addEventListener('dragstart', () => parent.classList.add('is-dragging'));
			parent.addEventListener('dragend', () => {
				parent.classList.remove('is-dragging');
				reorder();
			});
			parent.querySelectorAll('.bhdt-remove-child-row').forEach((btn) => bindChild(btn.closest('tr')));
		};

		const bindChild = (row) => {
			const labelInput = row?.querySelector('input[name^="menu_child_label_vi["]');
			if (labelInput) labelInput.dataset.bhdtLastLabel = labelInput.value;
			labelInput?.addEventListener('input', () => syncChildUrl(row));
			syncChildUrl(row);
			row?.querySelector('.bhdt-remove-child-row')?.addEventListener('click', () => {
				row.remove();
				reorder();
			});
		};

		list.addEventListener('dragover', (event) => {
			event.preventDefault();
			const dragging = list.querySelector('.is-dragging');
			if (!dragging) return;
			const rows = Array.from(list.querySelectorAll('.bhdt-menu-admin-parent:not(.is-dragging)'));
			let next = null;
			for (const row of rows) {
				const rect = row.getBoundingClientRect();
				if (event.clientY < rect.top + rect.height / 2) {
					next = row;
					break;
				}
			}
			if (next) {
				list.insertBefore(dragging, next);
			} else {
				list.appendChild(dragging);
			}
		});

		list.querySelectorAll('.bhdt-menu-admin-parent').forEach(bindParent);
		addParentBtn.addEventListener('click', () => {
			const idx = list.querySelectorAll('.bhdt-menu-admin-parent').length;
			list.insertAdjacentHTML('beforeend', parentHtml(idx));
			bindParent(list.lastElementChild);
			reorder();
		});
		reorder();
	})();
	</script>
	<style>
	.bhdt-menu-admin-tools {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 10px;
		margin: 12px 0;
	}
	#bhdt-menu-blocks-list {
		display: grid;
		gap: 14px;
		margin-top: 14px;
	}
	.bhdt-menu-admin-parent {
		padding: 14px;
		border: 1px solid #c3c4c7;
		background: #fff;
	}
	.bhdt-menu-admin-parent.is-dragging {
		opacity: 0.55;
	}
	.bhdt-menu-admin-parent-head,
	.bhdt-menu-admin-parent-fields {
		display: flex;
		flex-wrap: wrap;
		gap: 10px 14px;
		align-items: center;
		margin-bottom: 12px;
	}
	.bhdt-menu-admin-parent label {
		font-weight: 600;
	}
	.bhdt-menu-admin-drag {
		cursor: move;
		font-size: 18px;
	}
	.bhdt-menu-admin-children th:first-child,
	.bhdt-menu-admin-children td:first-child,
	.bhdt-menu-admin-children th:nth-child(5),
	.bhdt-menu-admin-children td:nth-child(5),
	.bhdt-menu-admin-children th:last-child,
	.bhdt-menu-admin-children td:last-child {
		width: 86px;
	}
	</style>
	<?php
}

function bhdt_wirecutter_default_function_menu_blocks() {
	$review_archive = get_post_type_archive_link( 'bhdt_review' );
	if ( ! $review_archive ) {
		$review_archive = home_url( '/?post_type=bhdt_review' );
	}

	$project_archive = get_post_type_archive_link( 'bhdt_project' );
	if ( ! $project_archive ) {
		$project_archive = home_url( '/?post_type=bhdt_project' );
	}

	return array(
		array(
			'label_vi' => 'Bài đánh giá',
			'label_en' => 'Reviews',
			'url'      => $review_archive,
			'order'    => 10,
			'enabled'  => 1,
			'children' => array(),
		),
		array(
			'label_vi' => 'Dự án',
			'label_en' => 'Projects',
			'url'      => $project_archive,
			'order'    => 20,
			'enabled'  => 1,
			'children' => array(),
		),
		array(
			'label_vi' => 'Linh kiện điện tử',
			'label_en' => 'Electronics',
			'url'      => home_url( '/?s=esp32' ),
			'order'    => 30,
			'enabled'  => 1,
			'children' => array(),
		),
		array(
			'label_vi' => 'Công cụ',
			'label_en' => 'Tools',
			'url'      => home_url( '/tien-ich-ky-thuat/' ),
			'order'    => 40,
			'enabled'  => 1,
			'children' => array(),
		),
	);
}

function bhdt_wirecutter_clean_function_menu_blocks( $rows ) {
	if ( ! is_array( $rows ) ) {
		return array();
	}

	$clean = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$label_vi = isset( $row['label_vi'] ) ? sanitize_text_field( $row['label_vi'] ) : '';
		$label_en = isset( $row['label_en'] ) ? sanitize_text_field( $row['label_en'] ) : '';
		$url      = isset( $row['url'] ) ? esc_url_raw( $row['url'] ) : '';
		$order    = isset( $row['order'] ) ? (int) $row['order'] : 0;
		$enabled  = isset( $row['enabled'] ) ? (int) $row['enabled'] : 1;

		if ( '' === $label_vi ) {
			continue;
		}

		$children = array();
		if ( isset( $row['children'] ) && is_array( $row['children'] ) ) {
			foreach ( $row['children'] as $child ) {
				if ( ! is_array( $child ) ) {
					continue;
				}
				$child_label_vi = isset( $child['label_vi'] ) ? sanitize_text_field( $child['label_vi'] ) : '';
				$child_label_en = isset( $child['label_en'] ) ? sanitize_text_field( $child['label_en'] ) : '';
				$child_url      = isset( $child['url'] ) ? esc_url_raw( $child['url'] ) : '';
				if ( '' === $child_label_vi ) {
					continue;
				}
				$children[] = array(
					'label_vi' => $child_label_vi,
					'label_en' => $child_label_en,
					'url'      => '' !== $child_url ? $child_url : '#',
					'order'    => isset( $child['order'] ) ? (int) $child['order'] : 0,
					'enabled'  => isset( $child['enabled'] ) ? (int) $child['enabled'] : 1,
				);
			}
		}

		usort(
			$children,
			static function ( $a, $b ) {
				return (int) $a['order'] <=> (int) $b['order'];
			}
		);

		$clean[] = array(
			'label_vi' => $label_vi,
			'label_en' => $label_en,
			'url'      => '' !== $url ? $url : '#',
			'order'    => $order,
			'enabled'  => $enabled,
			'children' => $children,
		);
	}

	usort(
		$clean,
		static function ( $a, $b ) {
			return (int) $a['order'] <=> (int) $b['order'];
		}
	);

	return $clean;
}

function bhdt_wirecutter_get_function_menu_blocks( $include_disabled = false, $include_linked_children = null ) {
	$saved = get_option( 'bhdt_wire_function_menu_blocks', array() );
	if ( ! is_array( $saved ) || empty( $saved ) ) {
		$saved = bhdt_wirecutter_default_function_menu_blocks();
	}

	$rows  = bhdt_wirecutter_clean_function_menu_blocks( $saved );
	$is_en = function_exists( 'pll_current_language' ) && 'en' === pll_current_language( 'slug' );
	if ( null === $include_linked_children ) {
		$include_linked_children = ! $include_disabled;
	}
	$linked_children_map = $include_linked_children ? bhdt_wirecutter_get_content_link_children_map( '_bhdt_page_function_menu_parent_url', bhdt_wirecutter_get_menu_linkable_post_types() ) : array();

	// DEBUG: Log mapping keys and children for troubleshooting when WP_DEBUG is enabled.
	$debug_log = "=== FUNCTION MENU DEBUG " . date('Y-m-d H:i:s') . " ===\n";
	$debug_log .= "Total parents: " . count($rows) . "\n";
	$debug_log .= "Linked children map keys: " . implode(', ', array_keys($linked_children_map)) . "\n\n";
	foreach ($rows as $row) {
		$parent_key = bhdt_wirecutter_normalize_menu_url_key($row['url'] ?? '');
		$parent_slug_key = 'slug:' . sanitize_title($row['label_vi'] ?? '');
		$debug_log .= "Parent: {$row['label_vi']} | URL: {$row['url']}\n";
		$debug_log .= "  parent_key: $parent_key\n";
		$debug_log .= "  parent_slug_key: $parent_slug_key\n";
		if (isset($linked_children_map[$parent_key])) {
			$debug_log .= "  ✓ Found " . count($linked_children_map[$parent_key]) . " children by parent_key\n";
			foreach ($linked_children_map[$parent_key] as $child) {
				$debug_log .= "    - {$child['label_vi']} ({$child['url']})\n";
			}
		}
		if (isset($linked_children_map[$parent_slug_key])) {
			$debug_log .= "  ✓ Found " . count($linked_children_map[$parent_slug_key]) . " children by parent_slug_key\n";
			foreach ($linked_children_map[$parent_slug_key] as $child) {
				$debug_log .= "    - {$child['label_vi']} ({$child['url']})\n";
			}
		}
		$debug_log .= "\n";
	}
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		file_put_contents( WP_CONTENT_DIR . '/function_menu_debug.log', $debug_log, FILE_APPEND );
	}

	foreach ( $rows as $row_index => $row ) {
		if ( ! $include_disabled && 1 !== (int) $row['enabled'] ) {
			unset( $rows[ $row_index ] );
			continue;
		}

		$parent_key = bhdt_wirecutter_normalize_menu_url_key( $row['url'] ?? '' );
		$parent_landing_key = bhdt_wirecutter_normalize_menu_url_key( bhdt_wirecutter_build_parent_landing_url( 'function', $row['label_vi'] ?? $row['label'] ?? '' ) );
		$parent_slug_key = 'slug:' . sanitize_title( $row['label_vi'] ?? '' );
		$linked_children = array();
		if ( isset( $linked_children_map[ $parent_key ] ) ) {
			$linked_children = array_merge( $linked_children, $linked_children_map[ $parent_key ] );
		}
		if ( isset( $linked_children_map[ $parent_landing_key ] ) ) {
			$linked_children = array_merge( $linked_children, $linked_children_map[ $parent_landing_key ] );
		}
		foreach ( bhdt_wirecutter_parent_alias_url_keys( 'function', $row ) as $parent_alias_key ) {
			if ( isset( $linked_children_map[ $parent_alias_key ] ) ) {
				$linked_children = array_merge( $linked_children, $linked_children_map[ $parent_alias_key ] );
			}
		}
		if ( isset( $linked_children_map[ $parent_slug_key ] ) ) {
			$linked_children = array_merge( $linked_children, $linked_children_map[ $parent_slug_key ] );
		}
		if ( ! empty( $linked_children ) ) {
			$rows[ $row_index ]['children'] = array_merge( $rows[ $row_index ]['children'], $linked_children );
			$unique_children = array();
			foreach ( $rows[ $row_index ]['children'] as $child_row ) {
				$unique_key = (string) ( $child_row['url'] ?? '' ) . '|' . (string) ( $child_row['label_vi'] ?? '' );
				$unique_children[ $unique_key ] = $child_row;
			}
			$rows[ $row_index ]['children'] = array_values( $unique_children );
			usort(
				$rows[ $row_index ]['children'],
				static function ( $a, $b ) {
					return (int) $a['order'] <=> (int) $b['order'];
				}
			);
		}

		$rows[ $row_index ]['label'] = $is_en && '' !== $row['label_en'] ? $row['label_en'] : $row['label_vi'];
		foreach ( $row['children'] as $child_index => $child ) {
			if ( ! $include_disabled && 1 !== (int) $child['enabled'] ) {
				unset( $rows[ $row_index ]['children'][ $child_index ] );
				continue;
			}
			$rows[ $row_index ]['children'][ $child_index ]['label'] = $is_en && '' !== $child['label_en'] ? $child['label_en'] : $child['label_vi'];
		}
		$rows[ $row_index ]['children'] = array_values( $rows[ $row_index ]['children'] );
	}

	return array_values( $rows );
}

function bhdt_wirecutter_handle_function_menu_save() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-function-menu' !== $_GET['page'] ) {
		return;
	}

	$is_save_action    = isset( $_POST['bhdt_save_function_menu'] );
	$is_refresh_action = isset( $_POST['bhdt_refresh_function_menu_urls'] );
	if ( ! $is_save_action && ! $is_refresh_action ) {
		return;
	}

	check_admin_referer( 'bhdt_save_function_menu_nonce' );

	$parent_labels_vi = isset( $_POST['function_parent_label_vi'] ) && is_array( $_POST['function_parent_label_vi'] ) ? wp_unslash( $_POST['function_parent_label_vi'] ) : array();
	$parent_labels_en = isset( $_POST['function_parent_label_en'] ) && is_array( $_POST['function_parent_label_en'] ) ? wp_unslash( $_POST['function_parent_label_en'] ) : array();
	$parent_urls      = isset( $_POST['function_parent_url'] ) && is_array( $_POST['function_parent_url'] ) ? wp_unslash( $_POST['function_parent_url'] ) : array();
	$parent_orders    = isset( $_POST['function_parent_order'] ) && is_array( $_POST['function_parent_order'] ) ? wp_unslash( $_POST['function_parent_order'] ) : array();
	$parent_enabled   = isset( $_POST['function_parent_enabled'] ) && is_array( $_POST['function_parent_enabled'] ) ? wp_unslash( $_POST['function_parent_enabled'] ) : array();
	$child_labels_vi  = isset( $_POST['function_child_label_vi'] ) && is_array( $_POST['function_child_label_vi'] ) ? wp_unslash( $_POST['function_child_label_vi'] ) : array();
	$child_labels_en  = isset( $_POST['function_child_label_en'] ) && is_array( $_POST['function_child_label_en'] ) ? wp_unslash( $_POST['function_child_label_en'] ) : array();
	$child_urls       = isset( $_POST['function_child_url'] ) && is_array( $_POST['function_child_url'] ) ? wp_unslash( $_POST['function_child_url'] ) : array();
	$child_orders     = isset( $_POST['function_child_order'] ) && is_array( $_POST['function_child_order'] ) ? wp_unslash( $_POST['function_child_order'] ) : array();
	$child_enabled    = isset( $_POST['function_child_enabled'] ) && is_array( $_POST['function_child_enabled'] ) ? wp_unslash( $_POST['function_child_enabled'] ) : array();

	$total = max( count( $parent_labels_vi ), count( $parent_labels_en ), count( $parent_urls ), count( $parent_orders ) );
	$rows = array();
	$linked_child_urls = array();
	foreach ( bhdt_wirecutter_get_page_link_children_map( '_bhdt_page_function_menu_parent_url' ) as $linked_children ) {
		foreach ( $linked_children as $linked_child ) {
			$linked_url_key = bhdt_wirecutter_normalize_menu_url_key( $linked_child['url'] ?? '' );
			if ( '' !== $linked_url_key ) {
				$linked_child_urls[ $linked_url_key ] = true;
			}
		}
	}

	for ( $i = 0; $i < $total; $i++ ) {
		$children = array();
		$child_total = isset( $child_labels_vi[ $i ] ) && is_array( $child_labels_vi[ $i ] ) ? count( $child_labels_vi[ $i ] ) : 0;
		for ( $j = 0; $j < $child_total; $j++ ) {
			$child_url = $child_urls[ $i ][ $j ] ?? '';
			$child_url_key = bhdt_wirecutter_normalize_menu_url_key( $child_url );
			if ( isset( $linked_child_urls[ $child_url_key ] ) ) {
				continue;
			}

			$child_order = isset( $child_orders[ $i ][ $j ] ) ? (int) $child_orders[ $i ][ $j ] : ( ( $j + 1 ) * 10 );
			if ( $child_order >= 9000 ) {
				continue;
			}

			$children[] = array(
				'label_vi' => $child_labels_vi[ $i ][ $j ] ?? '',
				'label_en' => $child_labels_en[ $i ][ $j ] ?? '',
				'url'      => $child_url,
				'order'    => $child_order,
				'enabled'  => isset( $child_enabled[ $i ][ $j ] ) ? 1 : 0,
			);
		}

		$rows[] = array(
			'label_vi' => $parent_labels_vi[ $i ] ?? '',
			'label_en' => $parent_labels_en[ $i ] ?? '',
			'url'      => $parent_urls[ $i ] ?? '',
			'order'    => $parent_orders[ $i ] ?? ( ( $i + 1 ) * 10 ),
			'enabled'  => isset( $parent_enabled[ $i ] ) ? 1 : 0,
			'children' => $children,
		);
	}

	$rows = bhdt_wirecutter_clean_function_menu_blocks( $rows );
	if ( $is_refresh_action ) {
		$rows = bhdt_wirecutter_clean_function_menu_blocks( bhdt_wirecutter_refresh_menu_block_urls( $rows ) );
	}

	update_option( 'bhdt_wire_function_menu_blocks', $rows, false );
	wp_safe_redirect( admin_url( 'themes.php?page=bhdt-function-menu&updated=1' . ( $is_refresh_action ? '&refreshed=1' : '' ) ) );
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_function_menu_save' );

function bhdt_wirecutter_render_function_menu_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập.', 'bhdt-wirecutter' ) );
	}

	$rows = bhdt_wirecutter_get_function_menu_blocks( true, true );
	if ( empty( $rows ) ) {
		$rows = bhdt_wirecutter_default_function_menu_blocks();
	}
	?>
	<div class="wrap">
		<h1>Menu Chức Năng</h1>
		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo isset( $_GET['refreshed'] ) ? 'Đã refresh URL theo tên tiêu đề.' : 'Đã lưu cấu hình Menu Chức Năng.'; ?></p></div>
		<?php endif; ?>
		<form method="post" id="bhdt-function-menu-form">
			<?php wp_nonce_field( 'bhdt_save_function_menu_nonce' ); ?>
			<p class="bhdt-menu-admin-tools">
				<button type="submit" class="button button-secondary" name="bhdt_refresh_function_menu_urls" value="1">Refresh URL theo tiêu đề</button>
				<span class="description">Tự đổi URL cha/con chưa khớp về dạng slug gạch ngang, ví dụ: /bai-danh-gia/.</span>
			</p>
			<div id="bhdt-function-menu-list">
				<?php foreach ( array_values( $rows ) as $idx => $row ) : ?>
					<div class="bhdt-menu-admin-parent" draggable="true">
						<div class="bhdt-menu-admin-parent-head">
							<span class="bhdt-menu-admin-drag">↕</span>
							<label><input type="checkbox" name="function_parent_enabled[<?php echo esc_attr( $idx ); ?>]" value="1" <?php checked( ! empty( $row['enabled'] ) ); ?>> Hiển thị</label>
							<label>Tên VI <input class="regular-text" type="text" name="function_parent_label_vi[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['label_vi'] ?? '' ); ?>"></label>
							<label>Tên EN <input class="regular-text" type="text" name="function_parent_label_en[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['label_en'] ?? '' ); ?>"></label>
						</div>
						<div class="bhdt-menu-admin-parent-fields">
							<label>URL cha <input class="large-text" type="text" name="function_parent_url[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( $row['url'] ?? '' ); ?>"></label>
							<label>Thứ tự <input class="small-text bhdt-parent-order" readonly type="number" name="function_parent_order[<?php echo esc_attr( $idx ); ?>]" value="<?php echo esc_attr( (string) ( $row['order'] ?? 0 ) ); ?>"></label>
							<button type="button" class="button bhdt-add-child-row">Thêm mục con</button>
							<button type="button" class="button bhdt-remove-parent-row">Xóa mục cha</button>
						</div>
						<table class="widefat striped bhdt-menu-admin-children">
							<thead><tr><th>Hiển thị</th><th>Tên VI</th><th>Tên EN</th><th>URL con</th><th>Thứ tự</th><th>Xóa</th></tr></thead>
							<tbody>
							<?php foreach ( array_values( $row['children'] ?? array() ) as $child_idx => $child ) : ?>
								<tr>
									<td><input type="checkbox" name="function_child_enabled[<?php echo esc_attr( $idx ); ?>][<?php echo esc_attr( $child_idx ); ?>]" value="1" <?php checked( ! empty( $child['enabled'] ) ); ?>></td>
									<td><input class="regular-text" type="text" name="function_child_label_vi[<?php echo esc_attr( $idx ); ?>][<?php echo esc_attr( $child_idx ); ?>]" value="<?php echo esc_attr( $child['label_vi'] ?? '' ); ?>"></td>
									<td><input class="regular-text" type="text" name="function_child_label_en[<?php echo esc_attr( $idx ); ?>][<?php echo esc_attr( $child_idx ); ?>]" value="<?php echo esc_attr( $child['label_en'] ?? '' ); ?>"></td>
									<td><input class="large-text" type="text" name="function_child_url[<?php echo esc_attr( $idx ); ?>][<?php echo esc_attr( $child_idx ); ?>]" value="<?php echo esc_attr( $child['url'] ?? '' ); ?>"></td>
									<td><input class="small-text bhdt-child-order" readonly type="number" name="function_child_order[<?php echo esc_attr( $idx ); ?>][<?php echo esc_attr( $child_idx ); ?>]" value="<?php echo esc_attr( (string) ( $child['order'] ?? 0 ) ); ?>"></td>
									<td><button type="button" class="button bhdt-remove-child-row">Xóa</button></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>
			</div>
			<p><button type="button" class="button" id="bhdt-add-function-parent">Thêm mục cha</button></p>
			<?php submit_button( 'Lưu Menu Chức Năng', 'primary', 'bhdt_save_function_menu' ); ?>
		</form>
	</div>
	<script>
	(function () {
		const list = document.getElementById('bhdt-function-menu-list');
		const addParentBtn = document.getElementById('bhdt-add-function-parent');
		if (!list || !addParentBtn) return;
		const parentLandingBase = <?php echo wp_json_encode( home_url( '/' ) ); ?>;

		const slugify = (value) => String(value || '')
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-+|-+$/g, '') || 'menu';

		const buildParentLandingUrl = (label) => {
			const slug = slugify(label);
			const trimmedBase = String(parentLandingBase || '').replace(/\/+$/, '');
			return trimmedBase + '/' + slug + '/';
		};

		const urlMatchesLabel = (url, label) => {
			const slug = slugify(label);
			if (!slug || !url) return false;
			try {
				const parsed = new URL(url, parentLandingBase);
				const parts = parsed.pathname.replace(/^\/+|\/+$/g, '').split('/');
				return slugify(decodeURIComponent(parts.pop() || '')) === slug;
			} catch (error) {
				return false;
			}
		};

		const isLegacyGeneratedUrl = (url) => {
			if (!url) return true;
			try {
				const parsed = new URL(url, parentLandingBase);
				return Boolean(parsed.search) || /%20/i.test(url);
			} catch (error) {
				return /(?:\?|%20)/i.test(String(url));
			}
		};

		const shouldSyncUrl = (urlInput, previousLabel) => {
			const url = urlInput?.value || '';
			return !url || isLegacyGeneratedUrl(url) || urlMatchesLabel(url, previousLabel);
		};

		const syncParentUrl = (parent) => {
			const labelInput = parent.querySelector('input[name^="function_parent_label_vi["]');
			const urlInput = parent.querySelector('input[name^="function_parent_url["]');
			if (!labelInput || !urlInput) return;
			const previousLabel = labelInput.dataset.bhdtLastLabel || labelInput.value;
			if (shouldSyncUrl(urlInput, previousLabel)) {
				urlInput.value = buildParentLandingUrl(labelInput.value);
			}
			labelInput.dataset.bhdtLastLabel = labelInput.value;
		};

		const syncChildUrl = (row) => {
			const labelInput = row?.querySelector('input[name^="function_child_label_vi["]');
			const urlInput = row?.querySelector('input[name^="function_child_url["]');
			if (!labelInput || !urlInput) return;
			const previousLabel = labelInput.dataset.bhdtLastLabel || labelInput.value;
			if (shouldSyncUrl(urlInput, previousLabel)) {
				urlInput.value = buildParentLandingUrl(labelInput.value);
			}
			labelInput.dataset.bhdtLastLabel = labelInput.value;
		};

		const escapeAttr = (value) => String(value || '').replace(/[&<>'"]/g, (char) => ({
			'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
		}[char]));

		const childRowHtml = (parentIdx, childIdx, values = {}) => '' +
			'<tr>' +
				'<td><input type="checkbox" name="function_child_enabled[' + parentIdx + '][' + childIdx + ']" value="1"' + (values.enabled === false ? '' : ' checked') + '></td>' +
				'<td><input class="regular-text" type="text" name="function_child_label_vi[' + parentIdx + '][' + childIdx + ']" value="' + escapeAttr(values.label_vi) + '"></td>' +
				'<td><input class="regular-text" type="text" name="function_child_label_en[' + parentIdx + '][' + childIdx + ']" value="' + escapeAttr(values.label_en) + '"></td>' +
				'<td><input class="large-text" type="text" name="function_child_url[' + parentIdx + '][' + childIdx + ']" value="' + escapeAttr(values.url) + '"></td>' +
				'<td><input class="small-text bhdt-child-order" readonly type="number" name="function_child_order[' + parentIdx + '][' + childIdx + ']" value="' + ((childIdx + 1) * 10) + '"></td>' +
				'<td><button type="button" class="button bhdt-remove-child-row">Xóa</button></td>' +
			'</tr>';

		const parentHtml = (idx) => '' +
			'<div class="bhdt-menu-admin-parent" draggable="true">' +
				'<div class="bhdt-menu-admin-parent-head">' +
					'<span class="bhdt-menu-admin-drag">↕</span>' +
					'<label><input type="checkbox" name="function_parent_enabled[' + idx + ']" value="1" checked> Hiển thị</label>' +
					'<label>Tên VI <input class="regular-text" type="text" name="function_parent_label_vi[' + idx + ']" value=""></label>' +
					'<label>Tên EN <input class="regular-text" type="text" name="function_parent_label_en[' + idx + ']" value=""></label>' +
				'</div>' +
				'<div class="bhdt-menu-admin-parent-fields">' +
					'<label>URL cha <input class="large-text" type="text" name="function_parent_url[' + idx + ']" value=""></label>' +
					'<label>Thứ tự <input class="small-text bhdt-parent-order" readonly type="number" name="function_parent_order[' + idx + ']" value="' + ((idx + 1) * 10) + '"></label>' +
					'<button type="button" class="button bhdt-add-child-row">Thêm mục con</button> ' +
					'<button type="button" class="button bhdt-remove-parent-row">Xóa mục cha</button>' +
				'</div>' +
				'<table class="widefat striped bhdt-menu-admin-children">' +
					'<thead><tr><th>Hiển thị</th><th>Tên VI</th><th>Tên EN</th><th>URL con</th><th>Thứ tự</th><th>Xóa</th></tr></thead>' +
					'<tbody></tbody>' +
				'</table>' +
			'</div>';

		const reorder = () => {
			Array.from(list.querySelectorAll('.bhdt-menu-admin-parent')).forEach((parent, parentIdx) => {
				const parentFields = {
					'function_parent_enabled[': 'input[name^="function_parent_enabled["]',
					'function_parent_label_vi[': 'input[name^="function_parent_label_vi["]',
					'function_parent_label_en[': 'input[name^="function_parent_label_en["]',
					'function_parent_url[': 'input[name^="function_parent_url["]',
					'function_parent_order[': 'input[name^="function_parent_order["]'
				};
				Object.entries(parentFields).forEach(([prefix, selector]) => {
					const input = parent.querySelector(selector);
					if (input) input.name = prefix + parentIdx + ']';
				});
				const orderInput = parent.querySelector('.bhdt-parent-order');
				if (orderInput) orderInput.value = String((parentIdx + 1) * 10);

				Array.from(parent.querySelectorAll('.bhdt-menu-admin-children tbody tr')).forEach((row, childIdx) => {
					const childFields = {
						'function_child_enabled[': 'input[name^="function_child_enabled["]',
						'function_child_label_vi[': 'input[name^="function_child_label_vi["]',
						'function_child_label_en[': 'input[name^="function_child_label_en["]',
						'function_child_url[': 'input[name^="function_child_url["]',
						'function_child_order[': 'input[name^="function_child_order["]'
					};
					Object.entries(childFields).forEach(([prefix, selector]) => {
						const input = row.querySelector(selector);
						if (input) input.name = prefix + parentIdx + '][' + childIdx + ']';
					});
					const childOrder = row.querySelector('.bhdt-child-order');
					if (childOrder) childOrder.value = String((childIdx + 1) * 10);
				});
			});
		};

		const bindParent = (parent) => {
			const labelInput = parent.querySelector('input[name^="function_parent_label_vi["]');
			if (labelInput) labelInput.dataset.bhdtLastLabel = labelInput.value;
			labelInput?.addEventListener('input', () => syncParentUrl(parent));
			syncParentUrl(parent);

			parent.querySelector('.bhdt-remove-parent-row')?.addEventListener('click', () => {
				parent.remove();
				reorder();
			});
			parent.querySelector('.bhdt-add-child-row')?.addEventListener('click', () => {
				const tbody = parent.querySelector('tbody');
				const parentIdx = Array.from(list.children).indexOf(parent);
				const childIdx = tbody ? tbody.querySelectorAll('tr').length : 0;
				if (tbody) {
					tbody.insertAdjacentHTML('beforeend', childRowHtml(parentIdx, childIdx));
					bindChild(tbody.lastElementChild);
					reorder();
				}
			});
			parent.addEventListener('dragstart', () => parent.classList.add('is-dragging'));
			parent.addEventListener('dragend', () => {
				parent.classList.remove('is-dragging');
				reorder();
			});
			parent.querySelectorAll('.bhdt-remove-child-row').forEach((btn) => bindChild(btn.closest('tr')));
		};

		const bindChild = (row) => {
			const labelInput = row?.querySelector('input[name^="function_child_label_vi["]');
			if (labelInput) labelInput.dataset.bhdtLastLabel = labelInput.value;
			labelInput?.addEventListener('input', () => syncChildUrl(row));
			syncChildUrl(row);
			row?.querySelector('.bhdt-remove-child-row')?.addEventListener('click', () => {
				row.remove();
				reorder();
			});
		};

		list.addEventListener('dragover', (event) => {
			event.preventDefault();
			const dragging = list.querySelector('.is-dragging');
			if (!dragging) return;
			const rows = Array.from(list.querySelectorAll('.bhdt-menu-admin-parent:not(.is-dragging)'));
			let next = null;
			for (const row of rows) {
				const rect = row.getBoundingClientRect();
				if (event.clientY < rect.top + rect.height / 2) {
					next = row;
					break;
				}
			}
			if (next) {
				list.insertBefore(dragging, next);
			} else {
				list.appendChild(dragging);
			}
		});

		list.querySelectorAll('.bhdt-menu-admin-parent').forEach(bindParent);
		addParentBtn.addEventListener('click', () => {
			const idx = list.querySelectorAll('.bhdt-menu-admin-parent').length;
			list.insertAdjacentHTML('beforeend', parentHtml(idx));
			bindParent(list.lastElementChild);
			reorder();
		});
		reorder();
	})();
	</script>
	<style>
	.bhdt-menu-admin-tools {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 10px;
		margin: 12px 0;
	}
	#bhdt-function-menu-list {
		display: grid;
		gap: 14px;
		margin-top: 14px;
	}
	.bhdt-menu-admin-parent {
		padding: 14px;
		border: 1px solid #c3c4c7;
		background: #fff;
	}
	.bhdt-menu-admin-parent.is-dragging {
		opacity: 0.55;
	}
	.bhdt-menu-admin-parent-head,
	.bhdt-menu-admin-parent-fields {
		display: flex;
		flex-wrap: wrap;
		gap: 10px 14px;
		align-items: center;
		margin-bottom: 12px;
	}
	.bhdt-menu-admin-parent label {
		font-weight: 600;
	}
	.bhdt-menu-admin-drag {
		cursor: move;
		font-size: 18px;
	}
	.bhdt-menu-admin-children th:first-child,
	.bhdt-menu-admin-children td:first-child,
	.bhdt-menu-admin-children th:nth-child(5),
	.bhdt-menu-admin-children td:nth-child(5),
	.bhdt-menu-admin-children th:last-child,
	.bhdt-menu-admin-children td:last-child {
		width: 86px;
	}
	</style>
	<?php
}

function bhdt_wirecutter_render_page_menu_link_meta_box( $post ) {
	wp_nonce_field( 'bhdt_save_page_menu_links', 'bhdt_page_menu_links_nonce' );

	$current_menu_parent     = (string) get_post_meta( $post->ID, '_bhdt_page_menu_block_parent_url', true );
	$current_function_parent = (string) get_post_meta( $post->ID, '_bhdt_page_function_menu_parent_url', true );
	$current_menu_parent_key     = bhdt_wirecutter_normalize_menu_url_key( $current_menu_parent );
	$current_function_parent_key = bhdt_wirecutter_normalize_menu_url_key( $current_function_parent );
	$menu_parents                = bhdt_wirecutter_get_menu_blocks( true );
	$function_parents            = bhdt_wirecutter_get_function_menu_blocks( true );
	$post_type_label             = bhdt_wirecutter_get_post_type_menu_label( $post->post_type );
	?>
	<p><?php echo esc_html( sprintf( __( 'Chọn mục cha để tự động thêm %s này thành mục con hover.', 'bhdt-wirecutter' ), $post_type_label ) ); ?></p>
	<p>
		<label for="bhdt_page_menu_block_parent_url"><strong><?php esc_html_e( 'Menu cha', 'bhdt-wirecutter' ); ?></strong></label><br>
		<select name="bhdt_page_menu_block_parent_url" id="bhdt_page_menu_block_parent_url" style="width:100%;max-width:460px;">
			<option value="">Không liên kết vào menu cha</option>
			<?php foreach ( $menu_parents as $parent ) : ?>
				<?php $parent_url = bhdt_wirecutter_build_parent_landing_url( 'function', $parent['label_vi'] ?? $parent['label'] ?? '' ); ?>
				<option value="<?php echo esc_attr( $parent_url ); ?>" <?php selected( $current_menu_parent_key, bhdt_wirecutter_normalize_menu_url_key( $parent_url ) ); ?>><?php echo esc_html( $parent['label_vi'] ?? '' ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="bhdt_page_function_menu_parent_url"><strong><?php esc_html_e( 'Menu chức năng', 'bhdt-wirecutter' ); ?></strong></label><br>
		<select name="bhdt_page_function_menu_parent_url" id="bhdt_page_function_menu_parent_url" style="width:100%;max-width:460px;">
			<option value="">Không liên kết vào menu chức năng</option>
			<?php foreach ( $function_parents as $parent ) : ?>
				<?php $parent_url = (string) ( $parent['url'] ?? '' ); ?>
				<option value="<?php echo esc_attr( $parent_url ); ?>" <?php selected( $current_function_parent_key, bhdt_wirecutter_normalize_menu_url_key( $parent_url ) ); ?>><?php echo esc_html( $parent['label_vi'] ?? '' ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<?php
}

function bhdt_wirecutter_register_page_menu_link_meta_box() {
	foreach ( bhdt_wirecutter_get_menu_linkable_post_types() as $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			continue;
		}

		add_meta_box(
			'bhdt_page_menu_links',
			__( 'Liên kết menu cho nội dung', 'bhdt-wirecutter' ),
			'bhdt_wirecutter_render_page_menu_link_meta_box',
			$post_type,
			'side',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'bhdt_wirecutter_register_page_menu_link_meta_box' );

function bhdt_wirecutter_render_page_menu_link_quick_edit( $column_name, $post_type ) {
	if ( 'bhdt_page_menu_link_summary' !== $column_name || ! in_array( $post_type, bhdt_wirecutter_get_menu_linkable_post_types(), true ) ) {
		return;
	}

	static $printed = array();
	if ( isset( $printed[ $post_type ] ) ) {
		return;
	}
	$printed[ $post_type ] = true;

	$menu_parents     = bhdt_wirecutter_get_menu_blocks( true );
	$function_parents = bhdt_wirecutter_get_function_menu_blocks( true );
	$post_type_object  = get_post_type_object( $post_type );
	$post_type_label   = bhdt_wirecutter_get_post_type_menu_label( $post_type );
	?>
	<fieldset class="inline-edit-col-right bhdt-page-menu-links-quick-edit">
		<div class="inline-edit-col">
			<h4><?php echo esc_html( sprintf( __( 'Liên kết menu cho %s', 'bhdt-wirecutter' ), $post_type_label ) ); ?></h4>
			<label class="alignleft">
				<span class="title"><?php esc_html_e( 'Menu cha', 'bhdt-wirecutter' ); ?></span>
				<select name="bhdt_page_menu_block_parent_url">
					<option value="">Không liên kết</option>
					<?php foreach ( $menu_parents as $parent ) : ?>
						<?php $parent_url = bhdt_wirecutter_build_parent_landing_url( 'function', $parent['label_vi'] ?? $parent['label'] ?? '' ); ?>
						<option value="<?php echo esc_attr( $parent_url ); ?>"><?php echo esc_html( $parent['label_vi'] ?? '' ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="alignleft" style="margin-top:8px;">
				<span class="title"><?php esc_html_e( 'Menu chức năng', 'bhdt-wirecutter' ); ?></span>
				<select name="bhdt_page_function_menu_parent_url">
					<option value="">Không liên kết</option>
					<?php foreach ( $function_parents as $parent ) : ?>
						<option value="<?php echo esc_attr( $parent['url'] ?? '' ); ?>"><?php echo esc_html( $parent['label_vi'] ?? '' ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>
	</fieldset>
	<?php
}
add_action( 'quick_edit_custom_box', 'bhdt_wirecutter_render_page_menu_link_quick_edit', 10, 2 );

function bhdt_wirecutter_print_page_menu_link_quick_edit_script() {
	global $pagenow, $typenow, $wp_query;

	if ( 'edit.php' !== $pagenow ) {
		return;
	}

	$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : $typenow;
	if ( '' === $post_type ) {
		$post_type = 'post';
	}

	if ( ! in_array( $post_type, bhdt_wirecutter_get_menu_linkable_post_types(), true ) ) {
		return;
	}

	$map = array();
	if ( isset( $wp_query->posts ) && is_array( $wp_query->posts ) ) {
		foreach ( $wp_query->posts as $item ) {
			if ( ! $item instanceof WP_Post || ! in_array( $item->post_type, bhdt_wirecutter_get_menu_linkable_post_types(), true ) ) {
				continue;
			}

			$map[ $item->ID ] = array(
				'menu_parent'     => (string) get_post_meta( $item->ID, '_bhdt_page_menu_block_parent_url', true ),
				'function_parent' => (string) get_post_meta( $item->ID, '_bhdt_page_function_menu_parent_url', true ),
				'post_type'       => $item->post_type,
			);
		}
	}
	?>
	<script>
	window.bhdtMenuQuickEditMap = <?php echo wp_json_encode( $map ); ?>;
	window.bhdtMenuHomeUrl = <?php echo wp_json_encode( home_url( '/' ) ); ?>;
	(function ($) {
		if (typeof inlineEditPost === 'undefined') {
			return;
		}

		const normalizeMenuUrl = (value) => {
			let raw = String(value || '').trim();
			if (!raw) return '';
			try {
				raw = new URL(raw, window.bhdtMenuHomeUrl || window.location.origin).href;
			} catch (error) {}
			raw = raw.replace(/^https?:\/\/[^/]+/i, '').split('#')[0].replace(/\/+$/, '');
			return raw || '/';
		};

		const setSelectByUrl = ($select, value) => {
			const targetKey = normalizeMenuUrl(value);
			$select.val(value || '');
			if (!$select.val() && targetKey) {
				$select.find('option').each(function () {
					if (normalizeMenuUrl(this.value) === targetKey) {
						$select.val(this.value);
						return false;
					}
					return true;
				});
			}
		};

		const originalEdit = inlineEditPost.edit;
		inlineEditPost.edit = function (id) {
			originalEdit.apply(this, arguments);

			let postId = 0;
			if (typeof id === 'object') {
				postId = parseInt(this.getId(id), 10);
			} else {
				postId = parseInt(id, 10);
			}
			if (!postId) {
				return;
			}

			const rowData = (window.bhdtMenuQuickEditMap && window.bhdtMenuQuickEditMap[postId]) || {};
			const editRow = $('#edit-' + postId);
			if (!editRow.length) {
				return;
			}

			window.setTimeout(() => {
				setSelectByUrl(editRow.find('select[name="bhdt_page_menu_block_parent_url"]'), rowData.menu_parent || '');
				setSelectByUrl(editRow.find('select[name="bhdt_page_function_menu_parent_url"]'), rowData.function_parent || '');
			}, 0);
		};
	})(jQuery);
	</script>
	<?php
}
add_action( 'admin_footer-edit.php', 'bhdt_wirecutter_print_page_menu_link_quick_edit_script' );

function bhdt_wirecutter_save_page_menu_link_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	$post_type = get_post_type( $post_id );
	if ( ! in_array( $post_type, bhdt_wirecutter_get_menu_linkable_post_types(), true ) ) {
		return;
	}

	$has_meta_box_nonce = isset( $_POST['bhdt_page_menu_links_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bhdt_page_menu_links_nonce'] ) ), 'bhdt_save_page_menu_links' );
	$has_quick_fields   = isset( $_POST['bhdt_page_menu_block_parent_url'] ) || isset( $_POST['bhdt_page_function_menu_parent_url'] );
	$has_inline_nonce   = isset( $_POST['_inline_edit'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_inline_edit'] ) ), 'inlineeditnonce' );

	if ( ! $has_meta_box_nonce && ! ( $has_quick_fields && $has_inline_nonce ) ) {
		return;
	}
	$is_quick_edit = $has_quick_fields && $has_inline_nonce && ! $has_meta_box_nonce;

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$menu_parent_url = isset( $_POST['bhdt_page_menu_block_parent_url'] ) ? esc_url_raw( wp_unslash( $_POST['bhdt_page_menu_block_parent_url'] ) ) : '';
	$function_parent_url = isset( $_POST['bhdt_page_function_menu_parent_url'] ) ? esc_url_raw( wp_unslash( $_POST['bhdt_page_function_menu_parent_url'] ) ) : '';
	if ( $is_quick_edit ) {
		if ( '' === $menu_parent_url ) {
			$menu_parent_url = (string) get_post_meta( $post_id, '_bhdt_page_menu_block_parent_url', true );
		}
		if ( '' === $function_parent_url ) {
			$function_parent_url = (string) get_post_meta( $post_id, '_bhdt_page_function_menu_parent_url', true );
		}
	}
	$menu_parent_slug = '';
	$function_parent_slug = '';

	if ( '' !== $menu_parent_url ) {
		$menu_parent_slug = sanitize_title( (string) wp_parse_url( $menu_parent_url, PHP_URL_PATH ) );
		$query = (string) wp_parse_url( $menu_parent_url, PHP_URL_QUERY );
		if ( '' !== $query ) {
			parse_str( $query, $menu_query_args );
			if ( isset( $menu_query_args['bhdt_menu_parent'] ) ) {
				$menu_parent_slug = sanitize_title( (string) $menu_query_args['bhdt_menu_parent'] );
			}
		}
	}

	if ( '' !== $function_parent_url ) {
		$function_parent_slug = sanitize_title( (string) wp_parse_url( $function_parent_url, PHP_URL_PATH ) );
		$query = (string) wp_parse_url( $function_parent_url, PHP_URL_QUERY );
		if ( '' !== $query ) {
			parse_str( $query, $function_query_args );
			if ( isset( $function_query_args['bhdt_menu_parent'] ) ) {
				$function_parent_slug = sanitize_title( (string) $function_query_args['bhdt_menu_parent'] );
			}
		}
	}

	if ( '' !== $menu_parent_url ) {
		update_post_meta( $post_id, '_bhdt_page_menu_block_parent_url', $menu_parent_url );
		if ( '' !== $menu_parent_slug ) {
			update_post_meta( $post_id, '_bhdt_page_menu_block_parent_slug', $menu_parent_slug );
		}
	} else {
		delete_post_meta( $post_id, '_bhdt_page_menu_block_parent_url' );
		delete_post_meta( $post_id, '_bhdt_page_menu_block_parent_slug' );
	}

	if ( '' !== $function_parent_url ) {
		update_post_meta( $post_id, '_bhdt_page_function_menu_parent_url', $function_parent_url );
		if ( '' !== $function_parent_slug ) {
			update_post_meta( $post_id, '_bhdt_page_function_menu_parent_slug', $function_parent_slug );
		}
	} else {
		delete_post_meta( $post_id, '_bhdt_page_function_menu_parent_url' );
		delete_post_meta( $post_id, '_bhdt_page_function_menu_parent_slug' );
	}
}
add_action( 'save_post', 'bhdt_wirecutter_save_page_menu_link_meta' );

function bhdt_wirecutter_render_home_category_link_meta_box( $post ) {
	wp_nonce_field( 'bhdt_save_home_category_links', 'bhdt_home_category_links_nonce' );

	$current_key      = (string) get_post_meta( $post->ID, '_bhdt_home_category_block_key', true );
	$current_choice   = bhdt_wirecutter_find_home_category_block_by_key( $current_key );
	$post_type_label   = bhdt_wirecutter_get_post_type_menu_label( $post->post_type );
	$category_choices = bhdt_wirecutter_get_home_category_block_choices();
	?>
	<p><?php echo esc_html( sprintf( __( 'Chọn Khối danh mục để gắn cho %s này.', 'bhdt-wirecutter' ), $post_type_label ) ); ?></p>
	<p>
		<label for="bhdt_home_category_block_key"><strong><?php esc_html_e( 'Khối danh mục', 'bhdt-wirecutter' ); ?></strong></label><br>
		<select name="bhdt_home_category_block_key" id="bhdt_home_category_block_key" style="width:100%;max-width:460px;">
			<option value=""><?php esc_html_e( 'Không liên kết', 'bhdt-wirecutter' ); ?></option>
			<?php foreach ( $category_choices as $choice ) : ?>
				<option value="<?php echo esc_attr( $choice['key'] ); ?>" <?php selected( $current_key === $choice['key'] || ( $current_choice && isset( $current_choice['key'] ) && $current_choice['key'] === $choice['key'] ) ); ?>><?php echo esc_html( $choice['option'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<?php if ( $current_choice ) : ?>
		<p class="description"><?php echo esc_html( sprintf( __( 'Đang gắn vào: %s', 'bhdt-wirecutter' ), $current_choice['option'] ) ); ?></p>
	<?php endif; ?>
	<?php
}

function bhdt_wirecutter_register_home_category_link_meta_box() {
	foreach ( bhdt_wirecutter_get_menu_linkable_post_types() as $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			continue;
		}

		add_meta_box(
			'bhdt_home_category_links',
			__( 'Khối danh mục', 'bhdt-wirecutter' ),
			'bhdt_wirecutter_render_home_category_link_meta_box',
			$post_type,
			'side',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'bhdt_wirecutter_register_home_category_link_meta_box' );

function bhdt_wirecutter_render_home_category_link_quick_edit( $column_name, $post_type ) {
	if ( 'bhdt_home_category_block_summary' !== $column_name || ! in_array( $post_type, bhdt_wirecutter_get_menu_linkable_post_types(), true ) ) {
		return;
	}

	static $printed = array();
	if ( isset( $printed[ $post_type ] ) ) {
		return;
	}
	$printed[ $post_type ] = true;

	$category_choices = bhdt_wirecutter_get_home_category_block_choices();
	$post_type_label   = bhdt_wirecutter_get_post_type_menu_label( $post_type );
	?>
	<fieldset class="inline-edit-col-right bhdt-home-category-links-quick-edit">
		<div class="inline-edit-col">
			<h4><?php echo esc_html( sprintf( __( 'Khối danh mục cho %s', 'bhdt-wirecutter' ), $post_type_label ) ); ?></h4>
			<label class="alignleft">
				<span class="title"><?php esc_html_e( 'Khối danh mục', 'bhdt-wirecutter' ); ?></span>
				<select name="bhdt_home_category_block_key">
					<option value=""><?php esc_html_e( 'Không liên kết', 'bhdt-wirecutter' ); ?></option>
					<?php foreach ( $category_choices as $choice ) : ?>
						<option value="<?php echo esc_attr( $choice['key'] ); ?>"><?php echo esc_html( $choice['option'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>
	</fieldset>
	<?php
}
add_action( 'quick_edit_custom_box', 'bhdt_wirecutter_render_home_category_link_quick_edit', 10, 2 );

function bhdt_wirecutter_print_home_category_link_quick_edit_script() {
	global $pagenow, $typenow, $wp_query;

	if ( 'edit.php' !== $pagenow ) {
		return;
	}

	$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : $typenow;
	if ( '' === $post_type ) {
		$post_type = 'post';
	}

	if ( ! in_array( $post_type, bhdt_wirecutter_get_menu_linkable_post_types(), true ) ) {
		return;
	}

	$map = array();
	if ( isset( $wp_query->posts ) && is_array( $wp_query->posts ) ) {
		foreach ( $wp_query->posts as $item ) {
			if ( ! $item instanceof WP_Post || ! in_array( $item->post_type, bhdt_wirecutter_get_menu_linkable_post_types(), true ) ) {
				continue;
			}

			$category_key = (string) get_post_meta( $item->ID, '_bhdt_home_category_block_key', true );
			$category_choice = bhdt_wirecutter_find_home_category_block_by_key( $category_key );

			$map[ $item->ID ] = array(
				'category_key' => $category_choice['key'] ?? $category_key,
			);
		}
	}
	?>
	<script>
	window.bhdtHomeCategoryQuickEditMap = <?php echo wp_json_encode( $map ); ?>;
	(function ($) {
		if (typeof inlineEditPost === 'undefined') {
			return;
		}

		const originalEdit = inlineEditPost.edit;
		inlineEditPost.edit = function (id) {
			originalEdit.apply(this, arguments);

			let postId = 0;
			if (typeof id === 'object') {
				postId = parseInt(this.getId(id), 10);
			} else {
				postId = parseInt(id, 10);
			}
			if (!postId) {
				return;
			}

			const rowData = (window.bhdtHomeCategoryQuickEditMap && window.bhdtHomeCategoryQuickEditMap[postId]) || {};
			const editRow = $('#edit-' + postId);
			if (!editRow.length) {
				return;
			}

			window.setTimeout(() => {
				editRow.find('select[name="bhdt_home_category_block_key"]').val(rowData.category_key || '');
			}, 0);
		};
	})(jQuery);
	</script>
	<?php
}
add_action( 'admin_footer-edit.php', 'bhdt_wirecutter_print_home_category_link_quick_edit_script' );

function bhdt_wirecutter_save_home_category_link_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	$post_type = get_post_type( $post_id );
	if ( ! in_array( $post_type, bhdt_wirecutter_get_menu_linkable_post_types(), true ) ) {
		return;
	}

	$has_meta_box_nonce = isset( $_POST['bhdt_home_category_links_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bhdt_home_category_links_nonce'] ) ), 'bhdt_save_home_category_links' );
	$has_quick_fields   = isset( $_POST['bhdt_home_category_block_key'] );
	$has_inline_nonce   = isset( $_POST['_inline_edit'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_inline_edit'] ) ), 'inlineeditnonce' );

	if ( ! $has_meta_box_nonce && ! ( $has_quick_fields && $has_inline_nonce ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$category_key = isset( $_POST['bhdt_home_category_block_key'] ) ? sanitize_text_field( wp_unslash( $_POST['bhdt_home_category_block_key'] ) ) : '';

	if ( '' !== $category_key ) {
		$category_choice = bhdt_wirecutter_find_home_category_block_by_key( $category_key );
		$category_key = $category_choice['key'] ?? '';
	}

	if ( '' !== $category_key ) {
		update_post_meta( $post_id, '_bhdt_home_category_block_key', $category_key );
	} else {
		delete_post_meta( $post_id, '_bhdt_home_category_block_key' );
	}
}
add_action( 'save_post', 'bhdt_wirecutter_save_home_category_link_meta' );

function bhdt_wirecutter_page_menu_parent_label_by_url( $url, $rows ) {
	$target_key = bhdt_wirecutter_normalize_menu_url_key( $url );
	if ( '' === $target_key || ! is_array( $rows ) ) {
		return '';
	}

	foreach ( $rows as $row ) {
		$row_url = isset( $row['url'] ) ? (string) $row['url'] : '';
		$row_landing_url = bhdt_wirecutter_build_parent_landing_url( 'function', $row['label_vi'] ?? $row['label'] ?? '' );
		if (
			bhdt_wirecutter_normalize_menu_url_key( $row_url ) === $target_key
			|| bhdt_wirecutter_normalize_menu_url_key( $row_landing_url ) === $target_key
			|| in_array( $target_key, bhdt_wirecutter_parent_alias_url_keys( 'function', $row ), true )
		) {
			if ( isset( $row['label_vi'] ) && '' !== (string) $row['label_vi'] ) {
				return (string) $row['label_vi'];
			}

			if ( isset( $row['label'] ) && '' !== (string) $row['label'] ) {
				return (string) $row['label'];
			}
		}
	}

	return '';
}

function bhdt_wirecutter_add_page_link_summary_column( $columns ) {
	$columns['bhdt_page_menu_link_summary'] = 'Liên kết menu';

	return $columns;
}
add_filter( 'manage_pages_columns', 'bhdt_wirecutter_add_page_link_summary_column' );

function bhdt_wirecutter_render_page_link_summary_column( $column_name, $post_id ) {
	if ( 'bhdt_page_menu_link_summary' !== $column_name ) {
		return;
	}

	$menu_parent_url     = (string) get_post_meta( $post_id, '_bhdt_page_menu_block_parent_url', true );
	$function_parent_url = (string) get_post_meta( $post_id, '_bhdt_page_function_menu_parent_url', true );
	$menu_parents        = bhdt_wirecutter_get_menu_blocks( true );
	$function_parents    = bhdt_wirecutter_get_function_menu_blocks( true );
	$post_type_label     = bhdt_wirecutter_get_post_type_menu_label( get_post_type( $post_id ) );

	$items = array();
	if ( '' !== $menu_parent_url ) {
		$menu_label = bhdt_wirecutter_page_menu_parent_label_by_url( $menu_parent_url, $menu_parents );
		$items[] = 'Menu cha > ' . ( '' !== $menu_label ? $menu_label : $menu_parent_url );
	}

	if ( '' !== $function_parent_url ) {
		$function_label = bhdt_wirecutter_page_menu_parent_label_by_url( $function_parent_url, $function_parents );
		$items[] = 'Menu chức năng > ' . ( '' !== $function_label ? $function_label : $function_parent_url );
	}

	if ( empty( $items ) ) {
		echo '<span aria-hidden="true">-</span>';
		return;
	}

	echo esc_html( $post_type_label . ': ' . implode( ' | ', $items ) );
}
add_action( 'manage_pages_custom_column', 'bhdt_wirecutter_render_page_link_summary_column', 10, 2 );
add_filter( 'manage_posts_columns', 'bhdt_wirecutter_add_page_link_summary_column' );
add_action( 'manage_posts_custom_column', 'bhdt_wirecutter_render_page_link_summary_column', 10, 2 );
add_filter( 'manage_bhdt_review_posts_columns', 'bhdt_wirecutter_add_page_link_summary_column' );
add_action( 'manage_bhdt_review_posts_custom_column', 'bhdt_wirecutter_render_page_link_summary_column', 10, 2 );
add_filter( 'manage_bhdt_comparison_posts_columns', 'bhdt_wirecutter_add_page_link_summary_column' );
add_action( 'manage_bhdt_comparison_posts_custom_column', 'bhdt_wirecutter_render_page_link_summary_column', 10, 2 );

function bhdt_wirecutter_add_home_category_link_summary_column( $columns ) {
	$columns['bhdt_home_category_block_summary'] = 'Khối danh mục';

	return $columns;
}

function bhdt_wirecutter_render_home_category_link_summary_column( $column_name, $post_id ) {
	if ( 'bhdt_home_category_block_summary' !== $column_name ) {
		return;
	}

	$current_key = (string) get_post_meta( $post_id, '_bhdt_home_category_block_key', true );
	if ( '' === $current_key ) {
		echo '<span aria-hidden="true">-</span>';
		return;
	}

	$current_choice = bhdt_wirecutter_find_home_category_block_by_key( $current_key );
	$post_type_label = bhdt_wirecutter_get_post_type_menu_label( get_post_type( $post_id ) );
	if ( ! $current_choice ) {
		echo esc_html( $post_type_label . ': ' . $current_key );
		return;
	}

	echo esc_html( $post_type_label . ': ' . $current_choice['option'] );
}

add_filter( 'manage_pages_columns', 'bhdt_wirecutter_add_home_category_link_summary_column' );
add_action( 'manage_pages_custom_column', 'bhdt_wirecutter_render_home_category_link_summary_column', 10, 2 );
add_filter( 'manage_posts_columns', 'bhdt_wirecutter_add_home_category_link_summary_column' );
add_action( 'manage_posts_custom_column', 'bhdt_wirecutter_render_home_category_link_summary_column', 10, 2 );
add_filter( 'manage_bhdt_review_posts_columns', 'bhdt_wirecutter_add_home_category_link_summary_column' );
add_action( 'manage_bhdt_review_posts_custom_column', 'bhdt_wirecutter_render_home_category_link_summary_column', 10, 2 );
add_filter( 'manage_bhdt_comparison_posts_columns', 'bhdt_wirecutter_add_home_category_link_summary_column' );
add_action( 'manage_bhdt_comparison_posts_custom_column', 'bhdt_wirecutter_render_home_category_link_summary_column', 10, 2 );

function bhdt_wirecutter_current_lang_home_url() {
	if ( function_exists( 'pll_current_language' ) ) {
		$lang = pll_current_language( 'slug' );
		if ( 'en' === $lang ) {
			return home_url( '/en/' );
		}
	}

	return home_url( '/' );
}

function bhdt_wirecutter_polylang_locale( $locale ) {
	if ( is_admin() ) {
		return $locale;
	}

	if ( ! function_exists( 'pll_current_language' ) ) {
		return $locale;
	}

	$lang = pll_current_language( 'slug' );
	if ( 'en' === $lang ) {
		return 'en_US';
	}

	if ( 'vi' === $lang ) {
		return 'vi';
	}

	return $locale;
}
add_filter( 'locale', 'bhdt_wirecutter_polylang_locale', 20 );
add_filter( 'determine_locale', 'bhdt_wirecutter_polylang_locale', 20 );

function bhdt_wirecutter_reload_textdomain_for_polylang() {
	if ( is_admin() || ! function_exists( 'pll_current_language' ) ) {
		return;
	}

	unload_textdomain( 'bhdt-wirecutter' );
	load_theme_textdomain( 'bhdt-wirecutter', get_template_directory() . '/languages' );
}
add_action( 'init', 'bhdt_wirecutter_reload_textdomain_for_polylang', 20 );

function bhdt_wirecutter_redirect_en_home_slug_to_root() {
	if ( is_admin() ) {
		return;
	}

	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( preg_match( '#^en/home(?:-[0-9]+)?$#i', $path ) ) {
		wp_safe_redirect( home_url( '/en/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'bhdt_wirecutter_redirect_en_home_slug_to_root', 1 );

function bhdt_wirecutter_disable_en_home_canonical( $redirect_url, $requested_url ) {
	$path = trim( (string) wp_parse_url( $requested_url, PHP_URL_PATH ), '/' );
	if ( 'en' === $path ) {
		return false;
	}

	return $redirect_url;
}
add_filter( 'redirect_canonical', 'bhdt_wirecutter_disable_en_home_canonical', 10, 2 );

function bhdt_wirecutter_force_en_home_canonical( $canonical ) {
	if ( is_admin() || ! function_exists( 'pll_current_language' ) ) {
		return $canonical;
	}

	if ( 'en' !== pll_current_language( 'slug' ) ) {
		return $canonical;
	}

	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( is_front_page() || preg_match( '#^en(?:/home(?:-[0-9]+)?)?$#i', $path ) ) {
		return home_url( '/en/' );
	}

	return $canonical;
}
add_filter( 'get_canonical_url', 'bhdt_wirecutter_force_en_home_canonical', 20 );
add_filter( 'wpseo_canonical', 'bhdt_wirecutter_force_en_home_canonical', 20 );

function bhdt_wirecutter_setup_en_homepage() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['bhdt_setup_en_home'] ) || '1' !== (string) $_GET['bhdt_setup_en_home'] ) {
		return;
	}

	if ( ! function_exists( 'pll_save_post_translations' ) ) {
		wp_safe_redirect( admin_url( 'options-reading.php?bhdt_home_setup=polylang_missing' ) );
		exit;
	}

	$vi_home_id = 0;
	$sample_page = get_page_by_path( 'sample-page', OBJECT, 'page' );
	if ( $sample_page instanceof WP_Post ) {
		$vi_home_id = (int) $sample_page->ID;
	}

	if ( ! $vi_home_id ) {
		$vi_pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'lang'           => 'vi',
			)
		);
		if ( ! empty( $vi_pages ) ) {
			$vi_home_id = (int) $vi_pages[0]->ID;
		}
	}

	if ( ! $vi_home_id ) {
		$vi_home_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_title'   => 'Trang chủ',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		if ( ! is_wp_error( $vi_home_id ) ) {
			pll_set_post_language( $vi_home_id, 'vi' );
		}
	}

	$translations = pll_get_post_translations( $vi_home_id );
	$en_home_id   = isset( $translations['en'] ) ? (int) $translations['en'] : 0;

	if ( ! $en_home_id ) {
		$en_home_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_title'   => 'Home',
				'post_name'    => 'home',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		if ( ! is_wp_error( $en_home_id ) ) {
			pll_set_post_language( $en_home_id, 'en' );
		}
	}

	if ( ! is_wp_error( $vi_home_id ) && ! is_wp_error( $en_home_id ) ) {
		pll_save_post_translations(
			array(
				'vi' => (int) $vi_home_id,
				'en' => (int) $en_home_id,
			)
		);

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $vi_home_id );
		update_option( 'page_for_posts', 0 );
		flush_rewrite_rules();

		wp_safe_redirect( admin_url( 'options-reading.php?bhdt_home_setup=ok' ) );
		exit;
	}

	wp_safe_redirect( admin_url( 'options-reading.php?bhdt_home_setup=error' ) );
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_setup_en_homepage' );


function bhdt_wirecutter_enqueue_fonts() {
	wp_enqueue_style(
		'bhdt-noto-sans-font',
		'https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&display=swap',
		array(),
		null
	);
}
add_action( 'wp_enqueue_scripts', 'bhdt_wirecutter_enqueue_fonts' );

/**
 * Polylang integration for theme translations
 */
function bhdt_wirecutter_polylang_translate( $translated, $original, $domain ) {
	if ( 'bhdt-wirecutter' !== $domain ) {
		return $translated;
	}
	
	// Get current language
	if ( ! function_exists( 'pll_current_language' ) ) {
		return $translated;
	}
	
	$current_lang = pll_current_language( 'slug' );
	
	// If Vietnamese (default), return original Vietnamese string
	if ( 'vi' === $current_lang ) {
		return $original;
	}
	
	// English translations mapping
	$translations = array(
		'Menu chính' => 'Primary Menu',
		'Cột trái trang chủ' => 'Home Left Column',
		'Thanh bên trái trên trang chủ và các cột biên tập.' => 'Left sidebar on homepage and editorial columns.',
		'Cột phải trang chủ' => 'Home Right Column',
		'Thanh bên phải trên trang chủ cho các khuyến mãi và liên kết.' => 'Right sidebar on homepage for deals and links.',
		'Thanh bên mặc định' => 'Default Sidebar',
		'Thanh bên dự phòng cho bài viết và trang.' => 'Fallback sidebar for posts and pages.',
		'Cột chân trang 1' => 'Footer Column 1',
		'Cột chân trang 2' => 'Footer Column 2',
		'Cột chân trang 3' => 'Footer Column 3',
		'Chủ đề hàng đầu BHDT' => 'BHDT Top Topics',
		'Ưu đãi hàng ngày BHDT' => 'BHDT Daily Deals',
		'Tìm kiếm phổ biến BHDT' => 'BHDT Popular Searches',
		'Một mục mỗi dòng theo định dạng: Nhãn|URL|Ghi chú' => 'One item per line in the format: Label|URL|Short note',
		'Tìm bài viết, hướng dẫn, linh kiện...' => 'Search posts, guides, electronics...',
		'Tìm kiếm:' => 'Search:',
		'Tìm kiếm' => 'Search',
		'Công cụ chuyển ngôn ngữ' => 'Language Switcher',
		'Đăng nhập' => 'Log in',
		'Đăng ký' => 'Subscribe',
		'Bài đánh giá' => 'Reviews',
		'Dự án' => 'Projects',
		'Linh kiện điện tử' => 'Electronics',
		'Công cụ' => 'Tools',
		'Ưu đãi' => 'Deals',
		'Bố cục biên tập gọn gàng, lấy cảm hứng từ Wirecutter' => 'Clean editorial layout inspired by Wirecutter',
		'Hướng dẫn thực tế, đánh giá linh kiện và ghi chú dự án.' => 'Practical guides, electronic reviews, and project notes.',
		'Mới nhất' => 'The Latest',
		'Lưu trữ đánh giá' => 'Review Archive',
		'Chỉ mục đánh giá biên tập với ghi chú có cấu trúc, thông số kỹ thuật chuẩn và luồng đọc rõ ràng.' => 'Editorial review index with structured notes, key specs, and clean reading flow.',
		'%d bài đánh giá được xuất bản' => '%d reviews published',
		'Đánh giá' => 'Review',
		'Đọc bài đánh giá' => 'Read Review',
		'Không có hình ảnh' => 'No image',
		'Lưu trữ dự án' => 'Project Archive',
		'Quy trình dự án, ý tưởng BOM và ghi chú xây dựng được hiển thị trong lưới biên tập rõ ràng.' => 'Project workflows, BOM ideas, and build notes displayed in a clean editorial grid.',
		'Dự án' => 'Project',
		'Đọc dự án' => 'Read Project',
		'Lưu trữ danh mục' => 'Category Archive',
		'Bài viết danh mục' => 'Category Post',
		'Đọc thêm' => 'Read More',
		'Lưu trữ phân loại' => 'Taxonomy Archive',
		'ID thuật ngữ: %d' => 'Term ID: %d',
		'Bài viết' => 'Post',
		'Đánh giá Linh Kiện' => 'Electronics Review',
		'Kết luận' => 'Verdict',
		'Ưu điểm & Nhược điểm' => 'Strengths & Weaknesses',
		'Ưu điểm' => 'Strengths',
		'Nhược điểm' => 'Weaknesses',
		'Ghi chú Người đánh giá / Thông số' => 'Reviewer Notes / Specs',
		'JSON Kỹ thuật' => 'Technical JSON',
		'Xếp hạng / Kết luận' => 'Rating / Verdict',
		'Thông số kỹ thuật' => 'Technical Specs',
		'Dự án DIY' => 'DIY Project',
		'BOM / Danh sách các phần' => 'BOM / Parts List',
		'Thông số Dự án / Ghi chú' => 'Project Specs / Notes',
		'Sử dụng một mục mỗi dòng. Mẫu dự án sẽ nổi bật các trường này.' => 'Use one item per line. Project template will highlight these fields.',
		'Sử dụng khu vực này cho BOM, công cụ, điểm kiểm tra, ghi chú dây điện và liên kết khởi chạy. Bố cục có ý đọc trước vì vậy thêm thông tin dự án có thể được phân lớp mà không bị lộn xộn.' => 'Use this area for BOM, tools, checkpoints, wire notes and startup links. Layout has reading-first intent so additional project info can be layered without clutter.',
		'Ghi chú dự án' => 'Project Notes',
		'Chủ đề linh kiện' => 'Electronics Topics',
		'Hướng dẫn nổi bật' => 'Featured Guide',
		'Cập nhật %s' => 'Updated %s',
		'Ưu đãi hàng ngày' => 'Daily Deals',
		'%d%% GIẢM' => '%d%% OFF',
		'Ưu đãi hàng ngày sẽ hiển thị khi WooCommerce và giá khuyến mãi sẵn sàng.' => 'Daily Deals will display when WooCommerce and sale prices are available.',
		'Menu danh mục kiểu Wirecutter' => 'Wirecutter-style Category Menu',
		'Đồng hồ vạn năng tốt nhất' => 'Best Multimeters',
		'Hướng dẫn ESP32' => 'ESP32 Guides',
		'Mô-đun công suất' => 'Power Modules',
		'Ưu đãi linh kiện hàng ngày' => 'Daily Electronics Deals',
		'Bộ khởi đầu' => 'Starter Kits',
		'Lựa chọn của biên tập viên' => 'Editor\'s Picks',
		'Nhà & Vườn' => 'Home & Garden',
		'Thiết bị hàn hàng đầu' => 'Top Soldering Gear',
		'Công suất bàn làm việc' => 'Bench Power',
		'Quản lý cáp' => 'Cable Management',
		'Nhà bếp' => 'Kitchen',
		'Nhiệt kế tốt nhất' => 'Best Thermometers',
		'Bộ hẹn giờ và cân' => 'Timers and Scales',
		'Công nghệ' => 'Technology',
		'Lưu trữ nhỏ gọn' => 'Compact Storage',
		'Menu chức năng trang' => 'Site Function Menu',
		'Menu Chức Năng' => 'Function Menu',
		'Điều hướng nhanh' => 'Quick Navigation',
		'Robot thông minh' => 'Smart Robots',
		'Thiết bị nhúng' => 'Embedded Devices',
		'Dự án' => 'Projects',
		'Chưa có nội dung mới.' => 'No new content yet.',
		'Sản phẩm nổi bật' => 'Featured Product',
		'Bài phụ %d' => 'Side Story %d',
		'%s - Bài chính' => '%s - Main Story',
		'%s - Bài phụ %d' => '%s - Side Story %d',
		'Khu vực này sẽ hiển thị bài nổi bật của chủ đề khi có dữ liệu sản phẩm.' => 'This area shows the featured story for the topic when product data is available.',
		'Khu vực hiển thị bài phụ theo chiều ngang.' => 'This area displays side stories in a horizontal layout.',
		'Ưu đãi hàng ngày sẽ hiển thị khi WooCommerce và giá khuyến mãi sẵn sàng.' => 'Daily Deals will display when WooCommerce and sale prices are ready.',
		'Bố cục biên tập gọn gàng, lấy cảm hứng từ Wirecutter.' => 'Clean editorial layout inspired by Wirecutter.',
		'Trang chủ' => 'Home',
		'Tốt nhất' => 'Best Of',
		'Menu chính' => 'Main Menu',
	);
	
	return isset( $translations[ $original ] ) ? $translations[ $original ] : $translated;
}

// Register the filter unconditionally; the callback itself safely checks Polylang availability.
add_filter( 'gettext', 'bhdt_wirecutter_polylang_translate', 10, 3 );

function bhdt_wirecutter_register_sidebars() {
	register_sidebar(
		array(
			'name'          => __( 'Cột trái trang chủ', 'bhdt-wirecutter' ),
			'id'            => 'bhdt-home-left',
			'description'   => __( 'Thanh bên trái trên trang chủ và các cột biên tập.', 'bhdt-wirecutter' ),
			'before_widget' => '<section class="bhdt-widget-box %2$s" id="%1$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Cột phải trang chủ', 'bhdt-wirecutter' ),
			'id'            => 'bhdt-home-right',
			'description'   => __( 'Thanh bên phải trên trang chủ cho các khuyến mãi và liên kết.', 'bhdt-wirecutter' ),
			'before_widget' => '<section class="bhdt-widget-box %2$s" id="%1$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Thanh bên mặc định', 'bhdt-wirecutter' ),
			'id'            => 'bhdt-default-sidebar',
			'description'   => __( 'Thanh bên dự phòng cho bài viết và trang.', 'bhdt-wirecutter' ),
			'before_widget' => '<section class="bhdt-widget-box %2$s" id="%1$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Cột chân trang 1', 'bhdt-wirecutter' ),
			'id'            => 'bhdt-footer-1',
			'before_widget' => '<div class="bhdt-footer-widget %2$s" id="%1$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<strong>',
			'after_title'   => '</strong>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Cột chân trang 2', 'bhdt-wirecutter' ),
			'id'            => 'bhdt-footer-2',
			'before_widget' => '<div class="bhdt-footer-widget %2$s" id="%1$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<strong>',
			'after_title'   => '</strong>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Cột chân trang 3', 'bhdt-wirecutter' ),
			'id'            => 'bhdt-footer-3',
			'before_widget' => '<div class="bhdt-footer-widget %2$s" id="%1$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<strong>',
			'after_title'   => '</strong>',
		)
	);
}
add_action( 'widgets_init', 'bhdt_wirecutter_register_sidebars' );

class BHDT_Wirecutter_Top_Topics_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'bhdt_wirecutter_top_topics',
			__( 'Chủ đề hàng đầu BHDT', 'bhdt-wirecutter' ),
			array(
				'description' => __( 'Danh sách chủ đề biên tập nhỏ gọn cho cột trái.', 'bhdt-wirecutter' ),
			)
		);
	}

	public function widget( $args, $instance ) {
		$title  = isset( $instance['title'] ) ? trim( $instance['title'] ) : '';
		$topics = isset( $instance['topics'] ) ? $instance['topics'] : '';
		$items  = bhdt_wirecutter_parse_top_topic_lines( $topics );

		if ( empty( $title ) ) {
			$title = __( 'Chủ đề hàng đầu', 'bhdt-wirecutter' );
		}

		if ( empty( $items ) ) {
			$items = bhdt_wirecutter_top_topics();
		}

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		if ( ! empty( $items ) ) {
			echo '<ul class="bhdt-topics-list">';
			foreach ( $items as $item ) {
				echo '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a></li>';
			}
			echo '</ul>';
		}

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title  = isset( $instance['title'] ) ? $instance['title'] : __( 'Chủ đề hàng đầu', 'bhdt-wirecutter' );
		$topics = isset( $instance['topics'] ) ? $instance['topics'] : "Multimeters|" . home_url( '/?s=multimeter' ) . "|Bench & field tools\nPower modules|" . home_url( '/?s=lm2596' ) . "|Stable DC rails\nESP32 projects|" . home_url( '/?s=esp32' ) . "|IoT & automation\nSoldering|" . home_url( '/?s=soldering' ) . "|Workbench essentials\nStarter kits|" . home_url( '/?s=kit' ) . "|Beginner-friendly picks";
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'bhdt-wirecutter' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'topics' ) ); ?>"><?php esc_html_e( 'Chủ đề', 'bhdt-wirecutter' ); ?></label>
			<textarea class="widefat" rows="8" id="<?php echo esc_attr( $this->get_field_id( 'topics' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'topics' ) ); ?>"><?php echo esc_textarea( $topics ); ?></textarea>
		</p>
		<p class="description"><?php esc_html_e( 'Một mục mỗi dòng theo định dạng: Nhãn|URL|Ghi chú', 'bhdt-wirecutter' ); ?></p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title']  = isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['topics'] = isset( $new_instance['topics'] ) ? sanitize_textarea_field( $new_instance['topics'] ) : '';

		return $instance;
	}
}

function bhdt_wirecutter_parse_top_topic_lines( $text ) {
	$lines  = bhdt_wirecutter_lines_to_list( $text );
	$topics = array();

	foreach ( $lines as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		$label = isset( $parts[0] ) ? $parts[0] : '';
		$url   = isset( $parts[1] ) ? $parts[1] : '';
		$note  = isset( $parts[2] ) ? $parts[2] : '';

		if ( '' === $label || '' === $url ) {
			continue;
		}

		$topics[] = array(
			'label' => $label,
			'url'   => $url,
			'note'  => $note,
		);
	}

	return $topics;
}

function bhdt_wirecutter_parse_link_lines( $text ) {
	$lines = bhdt_wirecutter_lines_to_list( $text );
	$items = array();

	foreach ( $lines as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		$label = isset( $parts[0] ) ? $parts[0] : '';
		$url   = isset( $parts[1] ) ? $parts[1] : '';
		$note  = isset( $parts[2] ) ? $parts[2] : '';

		if ( '' === $label || '' === $url ) {
			continue;
		}

		$items[] = array(
			'label' => $label,
			'url'   => $url,
			'note'  => $note,
		);
	}

	return $items;
}

class BHDT_Wirecutter_Trending_Now_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'bhdt_wirecutter_trending_now',
			__( 'Xu hướng hiện tại BHDT', 'bhdt-wirecutter' ),
			array(
				'description' => __( 'Danh sách xu hướng nhỏ gọn cho cột phải.', 'bhdt-wirecutter' ),
			)
		);
	}

	public function widget( $args, $instance ) {
		$title = isset( $instance['title'] ) ? trim( $instance['title'] ) : '';
		$items = isset( $instance['items'] ) ? $instance['items'] : '';
		$links = bhdt_wirecutter_parse_link_lines( $items );

		if ( empty( $title ) ) {
			$title = __( 'Xu hướng hiện tại', 'bhdt-wirecutter' );
		}

		if ( empty( $links ) ) {
			$links = array(
				array( 'label' => __( 'Xu hướng: xây dựng tự động hóa ESP32', 'bhdt-wirecutter' ),
					'url'   => home_url( '/?s=esp32' ),
					'note'  => __( 'Chủ đề phát triển nhanh nhất tuần này', 'bhdt-wirecutter' ),
				),
				array( 'label' => __( 'Xu hướng: đồng hồ vạn năng nổi bật', 'bhdt-wirecutter' ),
					'url'   => get_post_type_archive_link( 'bhdt_review' ),
					'note'  => __( 'Hướng dẫn mua cho nhu cầu rõ ràng', 'bhdt-wirecutter' ),
				),
				array( 'label' => __( 'Xu hướng: dự án mô-đun công suất', 'bhdt-wirecutter' ),
					'url'   => home_url( '/?s=lm2596' ),
					'note'  => __( 'Phổ biến trong DIY và sửa chữa', 'bhdt-wirecutter' ),
				),
				array( 'label' => __( 'Xu hướng: đồ hàn thiết yếu', 'bhdt-wirecutter' ),
					'url'   => home_url( '/?s=soldering' ),
					'note'  => __( 'Đồ cơ bản cho bàn làm việc', 'bhdt-wirecutter' ),
				),
			);
		}

		echo $args['before_widget'];
		echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		echo '<ul class="bhdt-trending-list">';
		foreach ( $links as $link ) {
			echo '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['label'] ) . '</a></li>';
		}
		echo '</ul>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'Xu hướng hiện tại', 'bhdt-wirecutter' );
		$items = isset( $instance['items'] ) ? $instance['items'] : "ESP32 automation builds|" . home_url( '/?s=esp32' ) . "|Fastest-growing topic this week\nBest multimeter picks|" . get_post_type_archive_link( 'bhdt_review' ) . "|High intent buying guides\nPower module projects|" . home_url( '/?s=lm2596' ) . "|Popular in DIY and repair\nSoldering essentials|" . home_url( '/?s=soldering' ) . "|Workbench staples";
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'bhdt-wirecutter' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'items' ) ); ?>"><?php esc_html_e( 'Items', 'bhdt-wirecutter' ); ?></label>
			<textarea class="widefat" rows="8" id="<?php echo esc_attr( $this->get_field_id( 'items' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'items' ) ); ?>"><?php echo esc_textarea( $items ); ?></textarea>
		</p>
		<p class="description"><?php esc_html_e( 'One item per line in the format: Label|URL|Short note', 'bhdt-wirecutter' ); ?></p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'title' => isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
			'items' => isset( $new_instance['items'] ) ? sanitize_textarea_field( $new_instance['items'] ) : '',
		);
	}
}

class BHDT_Wirecutter_Editor_Picks_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'bhdt_wirecutter_editor_picks',
			__( 'Lựa chọn của biên tập viên BHDT', 'bhdt-wirecutter' ),
			array(
				'description' => __( 'Khối lựa chọn biên tập được làm nổi bật cho cột phải.', 'bhdt-wirecutter' ),
			)
		);
	}

	public function widget( $args, $instance ) {
		$title = isset( $instance['title'] ) ? trim( $instance['title'] ) : '';
		$items = isset( $instance['items'] ) ? $instance['items'] : '';
		$links = bhdt_wirecutter_parse_link_lines( $items );

		if ( empty( $title ) ) {
			$title = __( 'Lựa chọn của biên tập viên', 'bhdt-wirecutter' );
		}

		if ( empty( $links ) ) {
			$links = array(
				array( 'label' => __( 'Tốt nhất: FNIRSI S1', 'bhdt-wirecutter' ), 'url' => get_post_type_archive_link( 'bhdt_review' ), 'note' => __( 'Một đồng hồ vạn năng đáng tin cậy hàng ngày', 'bhdt-wirecutter' ) ),
				array( 'label' => __( 'Lựa chọn tiết kiếm: mô-đun LM2596', 'bhdt-wirecutter' ), 'url' => home_url( '/?s=lm2596' ), 'note' => __( 'Điều chỉnh công suất chi phí thấp', 'bhdt-wirecutter' ) ),
				array( 'label' => __( 'Lựa chọn nâng cấp: ESP32 DevKit', 'bhdt-wirecutter' ), 'url' => home_url( '/?s=esp32' ), 'note' => __( 'WiFi/BLE cho các bản dựng kết nối', 'bhdt-wirecutter' ) ),
			);
		}

		echo $args['before_widget'];
		echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		echo '<ul class="bhdt-editor-picks-list">';
		foreach ( $links as $link ) {
			echo '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['label'] ) . '</a></li>';
		}
		echo '</ul>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'Lựa chọn của biên tập viên', 'bhdt-wirecutter' );
		$items = isset( $instance['items'] ) ? $instance['items'] : "Best overall: FNIRSI S1|" . get_post_type_archive_link( 'bhdt_review' ) . "|A dependable everyday multimeter\nBudget pick: LM2596 module|" . home_url( '/?s=lm2596' ) . "|Low-cost power regulation\nUpgrade pick: ESP32 DevKit|" . home_url( '/?s=esp32' ) . "|WiFi/BLE for connected builds";
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'bhdt-wirecutter' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'items' ) ); ?>"><?php esc_html_e( 'Items', 'bhdt-wirecutter' ); ?></label>
			<textarea class="widefat" rows="8" id="<?php echo esc_attr( $this->get_field_id( 'items' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'items' ) ); ?>"><?php echo esc_textarea( $items ); ?></textarea>
		</p>
		<p class="description"><?php esc_html_e( 'One item per line in the format: Label|URL|Short note', 'bhdt-wirecutter' ); ?></p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'title' => isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
			'items' => isset( $new_instance['items'] ) ? sanitize_textarea_field( $new_instance['items'] ) : '',
		);
	}
}

class BHDT_Wirecutter_Daily_Deals_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'bhdt_wirecutter_daily_deals',
			__( 'Ưu đãi hàng ngày BHDT', 'bhdt-wirecutter' ),
			array(
				'description' => __( 'Danh sách thẻ Ưu đãi hàng ngày nhỏ gọn cho cột phải.', 'bhdt-wirecutter' ),
			)
		);
	}

	public function widget( $args, $instance ) {
		$title = isset( $instance['title'] ) ? trim( $instance['title'] ) : '';
		$items = isset( $instance['items'] ) ? $instance['items'] : '';
		$links = bhdt_wirecutter_parse_link_lines( $items );

		if ( empty( $title ) ) {
			$title = __( 'Ưu đãi hàng ngày', 'bhdt-wirecutter' );
		}

		if ( empty( $links ) ) {
			$links = array_map(
				function ( $item ) {
					return array(
						'label' => $item['title'],
						'url'   => home_url( '/?s=' . rawurlencode( strtolower( $item['title'] ) ) ),
						'note'  => $item['excerpt'],
					);
				},
				bhdt_wirecutter_sample_electronics()
			);
		}

		echo $args['before_widget'];
		echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		echo '<div class="bhdt-deal-card-list">';
		foreach ( $links as $link ) {
				echo '<article class="bhdt-deal-card"><h4>' . esc_html( $link['label'] ) . '</h4><p>' . esc_html( $link['note'] ) . '</p><a class="bhdt-read-more" href="' . esc_url( $link['url'] ) . '">' . esc_html__( 'Mua ngay', 'bhdt-wirecutter' ) . '</a></article>';
		}
		echo '</div>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'Ưu đãi hàng ngày', 'bhdt-wirecutter' );
		$items = isset( $instance['items'] ) ? $instance['items'] : "FNIRSI S1 Digital Multimeter|" . home_url( '/?s=fnirsi' ) . "|Accurate auto-ranging meter for makers and repair work\nLM2596 Buck Converter Module|" . home_url( '/?s=lm2596' ) . "|Small DC-DC module for stable 5V output\nESP32 DevKit V1|" . home_url( '/?s=esp32' ) . "|Compact WiFi/BLE board for IoT projects\nArduino Nano Compatible Board|" . home_url( '/?s=arduino nano' ) . "|Small board for learning circuits";
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'bhdt-wirecutter' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'items' ) ); ?>"><?php esc_html_e( 'Items', 'bhdt-wirecutter' ); ?></label>
			<textarea class="widefat" rows="8" id="<?php echo esc_attr( $this->get_field_id( 'items' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'items' ) ); ?>"><?php echo esc_textarea( $items ); ?></textarea>
		</p>
		<p class="description"><?php esc_html_e( 'One item per line in the format: Label|URL|Short note', 'bhdt-wirecutter' ); ?></p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'title' => isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
			'items' => isset( $new_instance['items'] ) ? sanitize_textarea_field( $new_instance['items'] ) : '',
		);
	}
}

class BHDT_Wirecutter_Popular_Searches_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'bhdt_wirecutter_popular_searches',
			__( 'Tìm kiếm phổ biến BHDT', 'bhdt-wirecutter' ),
			array(
				'description' => __( 'Danh sách tìm kiếm phổ biến dạng chip cho cột phải.', 'bhdt-wirecutter' ),
			)
		);
	}

	public function widget( $args, $instance ) {
		$title = isset( $instance['title'] ) ? trim( $instance['title'] ) : '';
		$items = isset( $instance['items'] ) ? $instance['items'] : '';
		$links = bhdt_wirecutter_parse_link_lines( $items );

		if ( empty( $title ) ) {
			$title = __( 'Tìm kiếm phổ biến', 'bhdt-wirecutter' );
		}

		if ( empty( $links ) ) {
			$links = array(
				array( 'label' => __( 'Máy đo đa năng', 'bhdt-wirecutter' ), 'url' => home_url( '/?s=multimeter' ), 'note' => __( 'Tìm bài hướng dẫn và đánh giá', 'bhdt-wirecutter' ) ),
				array( 'label' => __( 'ESP32', 'bhdt-wirecutter' ), 'url' => home_url( '/?s=esp32' ), 'note' => __( 'Bo mạch và tự động hóa', 'bhdt-wirecutter' ) ),
				array( 'label' => __( 'Nguồn điện', 'bhdt-wirecutter' ), 'url' => home_url( '/?s=power supply' ), 'note' => __( 'Đầu ra ổn định và kiểm tra tải', 'bhdt-wirecutter' ) ),
				array( 'label' => __( 'Hàn', 'bhdt-wirecutter' ), 'url' => home_url( '/?s=soldering' ), 'note' => __( 'Đồ cơ bản cho bàn làm việc', 'bhdt-wirecutter' ) ),
			);
		}

		echo $args['before_widget'];
		echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		echo '<div class="bhdt-chip-list">';
		foreach ( $links as $link ) {
			echo '<a href="' . esc_url( $link['url'] ) . '"><span>' . esc_html( $link['label'] ) . '</span></a>';
		}
		echo '</div>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'Tìm kiếm phổ biến', 'bhdt-wirecutter' );
		$items = isset( $instance['items'] ) ? $instance['items'] : "Multimeter|" . home_url( '/?s=multimeter' ) . "|Find review guides\nESP32|" . home_url( '/?s=esp32' ) . "|Boards and automation\nPower supply|" . home_url( '/?s=power supply' ) . "|Stable output and load testing\nSoldering|" . home_url( '/?s=soldering' ) . "|Workbench essentials";
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'bhdt-wirecutter' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'items' ) ); ?>"><?php esc_html_e( 'Items', 'bhdt-wirecutter' ); ?></label>
			<textarea class="widefat" rows="8" id="<?php echo esc_attr( $this->get_field_id( 'items' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'items' ) ); ?>"><?php echo esc_textarea( $items ); ?></textarea>
		</p>
		<p class="description"><?php esc_html_e( 'One item per line in the format: Label|URL|Short note', 'bhdt-wirecutter' ); ?></p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'title' => isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
			'items' => isset( $new_instance['items'] ) ? sanitize_textarea_field( $new_instance['items'] ) : '',
		);
	}
}

function bhdt_wirecutter_register_widgets() {
	register_widget( 'BHDT_Wirecutter_Top_Topics_Widget' );
	register_widget( 'BHDT_Wirecutter_Trending_Now_Widget' );
	register_widget( 'BHDT_Wirecutter_Editor_Picks_Widget' );
	register_widget( 'BHDT_Wirecutter_Daily_Deals_Widget' );
	register_widget( 'BHDT_Wirecutter_Popular_Searches_Widget' );
}
add_action( 'widgets_init', 'bhdt_wirecutter_register_widgets' );

function bhdt_wirecutter_enqueue_assets() {
	$style_path = get_stylesheet_directory() . '/style.css';
	$style_version = file_exists( $style_path ) ? filemtime( $style_path ) : wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'bhdt-wirecutter-style',
		get_stylesheet_uri(),
		array(),
		$style_version
	);
}
add_action( 'wp_enqueue_scripts', 'bhdt_wirecutter_enqueue_assets' );

function bhdt_wirecutter_post_meta_line() {
	$author = get_the_author();
	$date   = get_the_date();
	$terms  = get_the_category_list( ', ' );

	$pieces = array_filter(
		array(
			$date,
			$author ? sprintf( __( 'Của %s', 'bhdt-wirecutter' ), $author ) : '',
			$terms,
		)
	);

	if ( empty( $pieces ) ) {
		return '';
	}

	return implode( ' · ', array_map( 'wp_strip_all_tags', $pieces ) );
}

function bhdt_wirecutter_excerpt_fallback( $length = 24 ) {
	$excerpt = get_the_excerpt();

	if ( empty( trim( $excerpt ) ) ) {
		$excerpt = get_the_content();
	}

	return wp_trim_words( wp_strip_all_tags( $excerpt ), $length, '...' );
}

function bhdt_wirecutter_register_cpt_meta_boxes() {
	add_meta_box(
		'bhdt_review_editorial_fields',
		__( 'Trường đánh giá BHDT', 'bhdt-wirecutter' ),
		'bhdt_wirecutter_render_review_meta_box',
		'bhdt_review',
		'normal',
		'high'
	);

	add_meta_box(
		'bhdt_project_editorial_fields',
		__( 'Trường dự án BHDT', 'bhdt-wirecutter' ),
		'bhdt_wirecutter_render_project_meta_box',
		'bhdt_project',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'bhdt_wirecutter_register_cpt_meta_boxes' );

function bhdt_wirecutter_render_review_meta_box( $post ) {
	wp_nonce_field( 'bhdt_wirecutter_review_meta', 'bhdt_wirecutter_review_meta_nonce' );

	$pros  = get_post_meta( $post->ID, '_bhdt_review_pros', true );
	$cons  = get_post_meta( $post->ID, '_bhdt_review_cons', true );
	$specs  = get_post_meta( $post->ID, '_bhdt_review_specs', true );
	$rating = get_post_meta( $post->ID, '_bhdt_review_rating', true );
	?>
	<p><?php esc_html_e( 'Sử dụng một mục mỗi dòng. Các trường này sẽ xuất hiện trong mẫu đánh giá đơn.', 'bhdt-wirecutter' ); ?></p>
	<p>
		<label for="bhdt_review_rating"><strong><?php esc_html_e( 'Xếp hạng / Kết luận', 'bhdt-wirecutter' ); ?></strong></label><br>
		<input type="text" id="bhdt_review_rating" name="bhdt_review_rating" value="<?php echo esc_attr( $rating ); ?>" style="width:100%;max-width:420px;">
	</p>
	<p>
		<label for="bhdt_review_pros"><strong><?php esc_html_e( 'Ưu điểm', 'bhdt-wirecutter' ); ?></strong></label><br>
		<textarea id="bhdt_review_pros" name="bhdt_review_pros" rows="4" style="width:100%;"><?php echo esc_textarea( $pros ); ?></textarea>
	</p>
	<p>
		<label for="bhdt_review_cons"><strong><?php esc_html_e( 'Nhược điểm', 'bhdt-wirecutter' ); ?></strong></label><br>
		<textarea id="bhdt_review_cons" name="bhdt_review_cons" rows="4" style="width:100%;"><?php echo esc_textarea( $cons ); ?></textarea>
	</p>
	<p>
		<label for="bhdt_review_specs"><strong><?php esc_html_e( 'Thông số kỹ thuật', 'bhdt-wirecutter' ); ?></strong></label><br>
		<textarea id="bhdt_review_specs" name="bhdt_review_specs" rows="6" style="width:100%;"><?php echo esc_textarea( $specs ); ?></textarea>
	</p>
	<?php
}

function bhdt_wirecutter_render_project_meta_box( $post ) {
	wp_nonce_field( 'bhdt_wirecutter_project_meta', 'bhdt_wirecutter_project_meta_nonce' );

	$bom      = get_post_meta( $post->ID, '_bhdt_project_bom', true );
	$strength = get_post_meta( $post->ID, '_bhdt_project_strengths', true );
	$weakness = get_post_meta( $post->ID, '_bhdt_project_weaknesses', true );
	$specs    = get_post_meta( $post->ID, '_bhdt_project_specs', true );
	?>
	<p><?php esc_html_e( 'Sử dụng một mục mỗi dòng. Mẫu dự án sẽ nổi bật các trường này.', 'bhdt-wirecutter' ); ?></p>
	<p>
		<label for="bhdt_project_bom"><strong><?php esc_html_e( 'BOM / Danh sách các phần', 'bhdt-wirecutter' ); ?></strong></label><br>
		<textarea id="bhdt_project_bom" name="bhdt_project_bom" rows="4" style="width:100%;"><?php echo esc_textarea( $bom ); ?></textarea>
	</p>
	<p>
		<label for="bhdt_project_strengths"><strong><?php esc_html_e( 'Ưu điểm', 'bhdt-wirecutter' ); ?></strong></label><br>
		<textarea id="bhdt_project_strengths" name="bhdt_project_strengths" rows="4" style="width:100%;"><?php echo esc_textarea( $strength ); ?></textarea>
	</p>
	<p>
		<label for="bhdt_project_weaknesses"><strong><?php esc_html_e( 'Nhược điểm', 'bhdt-wirecutter' ); ?></strong></label><br>
		<textarea id="bhdt_project_weaknesses" name="bhdt_project_weaknesses" rows="4" style="width:100%;"><?php echo esc_textarea( $weakness ); ?></textarea>
	</p>
	<p>
		<label for="bhdt_project_specs"><strong><?php esc_html_e( 'Thông số / Ghi chú', 'bhdt-wirecutter' ); ?></strong></label><br>
		<textarea id="bhdt_project_specs" name="bhdt_project_specs" rows="6" style="width:100%;"><?php echo esc_textarea( $specs ); ?></textarea>
	</p>
	<?php
}

function bhdt_wirecutter_save_cpt_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	$post_type = get_post_type( $post_id );
	if ( ! in_array( $post_type, array( 'bhdt_review', 'bhdt_project' ), true ) ) {
		return;
	}

	if ( 'bhdt_review' === $post_type ) {
		if ( ! isset( $_POST['bhdt_wirecutter_review_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bhdt_wirecutter_review_meta_nonce'] ) ), 'bhdt_wirecutter_review_meta' ) ) {
			return;
		}

		update_post_meta( $post_id, '_bhdt_review_rating', isset( $_POST['bhdt_review_rating'] ) ? sanitize_text_field( wp_unslash( $_POST['bhdt_review_rating'] ) ) : '' );
		update_post_meta( $post_id, '_bhdt_review_pros', isset( $_POST['bhdt_review_pros'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bhdt_review_pros'] ) ) : '' );
		update_post_meta( $post_id, '_bhdt_review_cons', isset( $_POST['bhdt_review_cons'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bhdt_review_cons'] ) ) : '' );
		update_post_meta( $post_id, '_bhdt_review_specs', isset( $_POST['bhdt_review_specs'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bhdt_review_specs'] ) ) : '' );
	}

	if ( 'bhdt_project' === $post_type ) {
		if ( ! isset( $_POST['bhdt_wirecutter_project_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bhdt_wirecutter_project_meta_nonce'] ) ), 'bhdt_wirecutter_project_meta' ) ) {
			return;
		}

		update_post_meta( $post_id, '_bhdt_project_bom', isset( $_POST['bhdt_project_bom'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bhdt_project_bom'] ) ) : '' );
		update_post_meta( $post_id, '_bhdt_project_strengths', isset( $_POST['bhdt_project_strengths'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bhdt_project_strengths'] ) ) : '' );
		update_post_meta( $post_id, '_bhdt_project_weaknesses', isset( $_POST['bhdt_project_weaknesses'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bhdt_project_weaknesses'] ) ) : '' );
		update_post_meta( $post_id, '_bhdt_project_specs', isset( $_POST['bhdt_project_specs'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bhdt_project_specs'] ) ) : '' );
	}
}
add_action( 'save_post', 'bhdt_wirecutter_save_cpt_meta' );

function bhdt_wirecutter_lines_to_list( $text ) {
	$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
	$lines = array_filter( array_map( 'trim', $lines ) );

	return $lines;
}

function bhdt_wirecutter_render_list_items( $text, $class = '' ) {
	$items = bhdt_wirecutter_lines_to_list( $text );

	if ( empty( $items ) ) {
		return '';
	}

	$output = '<ul class="' . esc_attr( $class ) . '">';
	foreach ( $items as $item ) {
		$output .= '<li>' . esc_html( $item ) . '</li>';
	}
	$output .= '</ul>';

	return $output;
}

function bhdt_wirecutter_sample_electronics() {
	return array(
		array(
			'title'  => 'FNIRSI S1 Digital Multimeter',
			'kicker' => 'Best overall',
			'excerpt' => 'Accurate auto-ranging meter with a bright display, suitable for makers, repair work, and bench testing.',
			'price'  => 'From 1.050.000 VND',
			'badge'  => 'Pick',
		),
		array(
			'title'  => 'LM2596 Buck Converter Module',
			'kicker' => 'Budget power rail',
			'excerpt' => 'Small, affordable DC-DC module for stable 5V and adjustable output projects.',
			'price'  => 'From 45.000 VND',
			'badge'  => 'Budget',
		),
		array(
			'title'  => 'ESP32 DevKit V1',
			'kicker' => 'Wireless controller',
			'excerpt' => 'A compact WiFi/BLE board for IoT, automation, and prototype electronics.',
			'price'  => 'From 120.000 VND',
			'badge'  => 'Popular',
		),
		array(
			'title'  => 'Arduino Nano Compatible Board',
			'kicker' => 'Starter board',
			'excerpt' => 'Small ATmega board that works well for learning circuits and quick embedded demos.',
			'price'  => 'From 90.000 VND',
			'badge'  => 'Starter',
		),
	);
}

function bhdt_wirecutter_render_sample_products() {
	$items = bhdt_wirecutter_sample_electronics();

	ob_start();
	?>
	<div class="bhdt-sample-products">
		<?php foreach ( $items as $item ) : ?>
			<article class="bhdt-sample-product">
				<p class="bhdt-sample-badge"><?php echo esc_html( $item['badge'] ); ?></p>
				<p class="bhdt-sample-kicker"><?php echo esc_html( $item['kicker'] ); ?></p>
				<h3><?php echo esc_html( $item['title'] ); ?></h3>
				<p><?php echo esc_html( $item['excerpt'] ); ?></p>
				<div class="bhdt-sample-price"><?php echo esc_html( $item['price'] ); ?></div>
			</article>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}

function bhdt_wirecutter_featured_roundups() {
	return array(
		array(
			'title'   => 'Best Multimeters for Bench and Field Work',
			'excerpt' => 'Stable auto-ranging, solid probes, and accurate readouts for electronics repair.',
			'link'    => get_post_type_archive_link( 'bhdt_review' ),
		),
		array(
			'title'   => 'Best Power Modules for DIY Electronics',
			'excerpt' => 'Reliable buck converters, current headroom, and clean wiring layouts.',
			'link'    => get_post_type_archive_link( 'bhdt_project' ),
		),
		array(
			'title'   => 'Editor’s Pick: Starter IoT Boards',
			'excerpt' => 'ESP32 boards and compatible modules for practical automation builds.',
			'link'    => home_url( '/?s=esp32' ),
		),
	);
}

function bhdt_wirecutter_editor_picks() {
	return array(
		array(
			'title'  => 'Best overall: FNIRSI S1',
			'badge'  => 'Best overall',
			'excerpt' => 'A dependable digital multimeter for everyday electronics work.',
		),
		array(
			'title'  => 'Budget pick: LM2596 module',
			'badge'  => 'Budget',
			'excerpt' => 'Low-cost power regulation for hobby projects and prototypes.',
		),
		array(
			'title'  => 'Upgrade pick: ESP32 DevKit',
			'badge'  => 'Upgrade',
			'excerpt' => 'Adds WiFi/BLE capability for connected electronics builds.',
		),
	);
}

function bhdt_wirecutter_render_best_of_block() {
	$items = bhdt_wirecutter_featured_roundups();

	ob_start();
	?>
	<section class="bhdt-best-of-panel">
		<header class="bhdt-section-header">
			<h2><?php esc_html_e( 'Tốt nhất / Lựa chọn của biên tập viên', 'bhdt-wirecutter' ); ?></h2>
			<p><?php esc_html_e( 'Điểm bắt đầu nhanh cho độc giả muốn con đường ngắn nhất đến lựa chọn hữu ích.', 'bhdt-wirecutter' ); ?></p>
		</header>
		<div class="bhdt-best-of-grid">
			<?php foreach ( $items as $item ) : ?>
				<article class="bhdt-best-of-card">
					<p class="bhdt-sample-badge"><?php echo esc_html( $item['title'] ); ?></p>
					<p><?php echo esc_html( $item['excerpt'] ); ?></p>
					<a class="bhdt-read-more" href="<?php echo esc_url( $item['link'] ); ?>"><?php esc_html_e( 'Xem tổng hợp', 'bhdt-wirecutter' ); ?></a>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

function bhdt_wirecutter_mega_menu_sections() {
	$review_archive = get_post_type_archive_link( 'bhdt_review' );
	if ( ! $review_archive ) {
		$review_archive = home_url( '/?post_type=bhdt_review' );
	}

	$comparison_archive = get_post_type_archive_link( 'bhdt_comparison' );
	if ( ! $comparison_archive ) {
		$comparison_archive = home_url( '/?post_type=bhdt_comparison' );
	}

	$review_search = static function ( $keyword ) {
		return add_query_arg(
			array(
				'post_type' => 'bhdt_review',
				's'         => $keyword,
			),
			home_url( '/' )
		);
	};

	$comparison_search = static function ( $keyword ) {
		return add_query_arg(
			array(
				'post_type' => 'bhdt_comparison',
				's'         => $keyword,
			),
			home_url( '/' )
		);
	};

	return array(
		array(
			'label' => __( 'Nguồn & Sạc', 'bhdt-wirecutter' ),
			'url'   => $review_search( 'nguon adapter' ),
			'links' => array(
				array( 'label' => __( 'Review bộ nguồn tổ ong', 'bhdt-wirecutter' ), 'url' => $review_search( 'bo nguon to ong' ) ),
				array( 'label' => __( 'Adapter DC tốt nhất', 'bhdt-wirecutter' ), 'url' => $review_search( 'adapter dc' ) ),
				array( 'label' => __( 'So sánh module sạc pin', 'bhdt-wirecutter' ), 'url' => $comparison_search( 'module sac pin' ) ),
				array( 'label' => __( 'Tổng hợp review mạch nguồn', 'bhdt-wirecutter' ), 'url' => $review_archive ),
			),
		),
		array(
			'label' => __( 'Đo lường & Test', 'bhdt-wirecutter' ),
			'url'   => $review_search( 'dong ho van nang' ),
			'links' => array(
				array( 'label' => __( 'Top đồng hồ vạn năng', 'bhdt-wirecutter' ), 'url' => $review_search( 'dong ho van nang' ) ),
				array( 'label' => __( 'Review máy hiện sóng mini', 'bhdt-wirecutter' ), 'url' => $review_search( 'may hien song' ) ),
				array( 'label' => __( 'So sánh máy đo LCR', 'bhdt-wirecutter' ), 'url' => $comparison_search( 'lcr meter' ) ),
				array( 'label' => __( 'Tổng hợp review thiết bị đo', 'bhdt-wirecutter' ), 'url' => $review_archive ),
			),
		),
		array(
			'label' => __( 'Vi điều khiển', 'bhdt-wirecutter' ),
			'url'   => $review_search( 'esp32 stm32 arduino' ),
			'links' => array(
				array( 'label' => __( 'Review board ESP32', 'bhdt-wirecutter' ), 'url' => $review_search( 'esp32 devkit' ) ),
				array( 'label' => __( 'Review board STM32', 'bhdt-wirecutter' ), 'url' => $review_search( 'stm32' ) ),
				array( 'label' => __( 'So sánh Arduino vs ESP32', 'bhdt-wirecutter' ), 'url' => $comparison_search( 'arduino esp32' ) ),
				array( 'label' => __( 'Tổng hợp review MCU', 'bhdt-wirecutter' ), 'url' => $review_archive ),
			),
		),
		array(
			'label' => __( 'Cảm biến', 'bhdt-wirecutter' ),
			'url'   => $review_search( 'cam bien nhiet do do am' ),
			'links' => array(
				array( 'label' => __( 'Review cảm biến nhiệt độ', 'bhdt-wirecutter' ), 'url' => $review_search( 'cam bien nhiet do' ) ),
				array( 'label' => __( 'Review cảm biến chuyển động', 'bhdt-wirecutter' ), 'url' => $review_search( 'cam bien chuyen dong' ) ),
				array( 'label' => __( 'So sánh cảm biến khí', 'bhdt-wirecutter' ), 'url' => $comparison_search( 'cam bien khi' ) ),
				array( 'label' => __( 'Tổng hợp review cảm biến', 'bhdt-wirecutter' ), 'url' => $review_archive ),
			),
		),
		array(
			'label' => __( 'IoT & Kết nối', 'bhdt-wirecutter' ),
			'url'   => $review_search( 'wifi ble zigbee' ),
			'links' => array(
				array( 'label' => __( 'Review module WiFi', 'bhdt-wirecutter' ), 'url' => $review_search( 'module wifi' ) ),
				array( 'label' => __( 'Review module BLE', 'bhdt-wirecutter' ), 'url' => $review_search( 'bluetooth ble' ) ),
				array( 'label' => __( 'So sánh Zigbee và LoRa', 'bhdt-wirecutter' ), 'url' => $comparison_search( 'zigbee lora' ) ),
				array( 'label' => __( 'Tổng hợp review IoT', 'bhdt-wirecutter' ), 'url' => $review_archive ),
			),
		),
		array(
			'label' => __( 'Hiển thị', 'bhdt-wirecutter' ),
			'url'   => $review_search( 'lcd oled tft' ),
			'links' => array(
				array( 'label' => __( 'Review màn hình OLED', 'bhdt-wirecutter' ), 'url' => $review_search( 'oled' ) ),
				array( 'label' => __( 'Review màn hình TFT', 'bhdt-wirecutter' ), 'url' => $review_search( 'tft' ) ),
				array( 'label' => __( 'So sánh LCD và OLED', 'bhdt-wirecutter' ), 'url' => $comparison_search( 'lcd oled' ) ),
				array( 'label' => __( 'Tổng hợp review hiển thị', 'bhdt-wirecutter' ), 'url' => $review_archive ),
			),
		),
		array(
			'label' => __( 'Âm thanh', 'bhdt-wirecutter' ),
			'url'   => $review_search( 'amp dac loa module' ),
			'links' => array(
				array( 'label' => __( 'Review mạch khuếch đại', 'bhdt-wirecutter' ), 'url' => $review_search( 'mach khuech dai' ) ),
				array( 'label' => __( 'Review module DAC', 'bhdt-wirecutter' ), 'url' => $review_search( 'dac module' ) ),
				array( 'label' => __( 'So sánh loa mini', 'bhdt-wirecutter' ), 'url' => $comparison_search( 'loa mini' ) ),
				array( 'label' => __( 'Tổng hợp review âm thanh', 'bhdt-wirecutter' ), 'url' => $review_archive ),
			),
		),
		array(
			'label' => __( 'Robot & Điều khiển', 'bhdt-wirecutter' ),
			'url'   => $review_search( 'servo step motor driver' ),
			'links' => array(
				array( 'label' => __( 'Review driver động cơ', 'bhdt-wirecutter' ), 'url' => $review_search( 'driver dong co' ) ),
				array( 'label' => __( 'Review servo & step motor', 'bhdt-wirecutter' ), 'url' => $review_search( 'servo step motor' ) ),
				array( 'label' => __( 'So sánh kit robot', 'bhdt-wirecutter' ), 'url' => $comparison_search( 'kit robot' ) ),
				array( 'label' => __( 'Tổng hợp review robot', 'bhdt-wirecutter' ), 'url' => $review_archive ),
			),
		),
		array(
			'label' => __( 'Linh kiện cơ bản', 'bhdt-wirecutter' ),
			'url'   => $review_search( 'dien tro tu dien diode transistor' ),
			'links' => array(
				array( 'label' => __( 'Review tụ điện thông dụng', 'bhdt-wirecutter' ), 'url' => $review_search( 'tu dien' ) ),
				array( 'label' => __( 'Review transistor và MOSFET', 'bhdt-wirecutter' ), 'url' => $review_search( 'transistor mosfet' ) ),
				array( 'label' => __( 'So sánh diode chỉnh lưu', 'bhdt-wirecutter' ), 'url' => $comparison_search( 'diode chinh luu' ) ),
				array( 'label' => __( 'Tổng hợp review linh kiện nền tảng', 'bhdt-wirecutter' ), 'url' => $review_archive ),
			),
		),
		array(
			'label' => __( 'Phụ kiện Maker', 'bhdt-wirecutter' ),
			'url'   => $review_search( 'breadboard day dup kep han' ),
			'links' => array(
				array( 'label' => __( 'Review breadboard', 'bhdt-wirecutter' ), 'url' => $review_search( 'breadboard' ) ),
				array( 'label' => __( 'Review mỏ hàn và phụ kiện hàn', 'bhdt-wirecutter' ), 'url' => $review_search( 'mo han' ) ),
				array( 'label' => __( 'So sánh bộ dây Dupont', 'bhdt-wirecutter' ), 'url' => $comparison_search( 'day dupont' ) ),
				array( 'label' => __( 'Tổng hợp review phụ kiện maker', 'bhdt-wirecutter' ), 'url' => $review_archive ),
			),
		),
        );
}

// Shortcode debug - để test mục con
function bhdt_wirecutter_debug_function_menu_shortcode() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '<p>Bạn không có quyền xem debug.</p>';
	}

	$pages = get_posts( array(
		'post_type'      => 'page',
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => '_bhdt_page_function_menu_parent_url',
				'compare' => 'EXISTS',
			),
		),
	) );

	$output = '<div style="background:#f5f5f5; padding:20px; margin:20px 0; border:1px solid #ddd; font-family:monospace; white-space:pre-wrap;">';
	$output .= "=== FUNCTION MENU DEBUG ===\n\n";
	$output .= "Pages with function menu meta:\n";
	
	foreach ( $pages as $page ) {
		$parent_url = get_post_meta( $page->ID, '_bhdt_page_function_menu_parent_url', true );
		$parent_slug = get_post_meta( $page->ID, '_bhdt_page_function_menu_parent_slug', true );
		$output .= "- ID {$page->ID}: {$page->post_title}\n";
		$output .= "  Parent URL: {$parent_url}\n";
		$output .= "  Parent Slug: {$parent_slug}\n";
		$output .= "  Page URL: " . get_permalink( $page->ID ) . "\n\n";
	}

	if ( function_exists( 'bhdt_wirecutter_get_page_link_children_map' ) ) {
		$map = bhdt_wirecutter_get_page_link_children_map( '_bhdt_page_function_menu_parent_url' );
		$output .= "Linked children map keys:\n";
		foreach ( array_keys( $map ) as $key ) {
			$output .= "- $key\n";
		}
	}

	if ( function_exists( 'bhdt_wirecutter_get_function_menu_blocks' ) ) {
		$blocks = bhdt_wirecutter_get_function_menu_blocks();
		$output .= "\n\nFunction menu blocks with children:\n";
		foreach ( $blocks as $block ) {
			$children_count = count( $block['children'] ?? array() );
			$output .= "- {$block['label_vi']} (children: {$children_count})\n";
			if ( ! empty( $block['children'] ) ) {
				foreach ( $block['children'] as $child ) {
					$output .= "  - {$child['label_vi']} ({$child['url']})\n";
				}
			}
		}
	}

	$output .= '</div>';
	return $output;
}
add_shortcode( 'bhdt_debug_function_menu', 'bhdt_wirecutter_debug_function_menu_shortcode' );

function bhdt_wirecutter_render_mega_menu() {
	$sections = bhdt_wirecutter_get_menu_blocks();

	ob_start();
	?>
	<section class="bhdt-mega-menu" aria-label="<?php esc_attr_e( 'Menu danh mục kiểu Wirecutter', 'bhdt-wirecutter' ); ?>">
		<div class="bhdt-mega-menu-grid">
			<ul class="bhdt-mega-parent-grid">
			<?php foreach ( $sections as $section ) : ?>
					<?php
					$parent_url = bhdt_wirecutter_build_parent_landing_url( 'mega', $section['label_vi'] ?? $section['label'] );
					$child_active = false;
					foreach ( $section['children'] ?? array() as $link ) {
						if ( bhdt_wirecutter_is_menu_url_active( $link['url'] ?? '' ) ) {
							$child_active = true;
							break;
						}
					}
					$parent_active = bhdt_wirecutter_is_menu_url_active( $parent_url ) || $child_active;
					?>
					<li class="bhdt-mega-parent-item <?php echo $parent_active ? 'is-active' : ''; ?>">
						<a class="bhdt-mega-parent-link <?php echo $parent_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $parent_url ); ?>" <?php echo $parent_active ? 'aria-current="page"' : ''; ?>>
							<span><?php echo esc_html( $section['label'] ); ?></span>
						</a>
						<?php if ( ! empty( $section['children'] ) ) : ?>
						<ul class="bhdt-mega-child-list">
						<?php foreach ( $section['children'] as $link ) : ?>
							<?php $link_active = bhdt_wirecutter_is_menu_url_active( $link['url'] ?? '' ); ?>
							<li><a class="<?php echo $link_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $link['url'] ); ?>" <?php echo $link_active ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $link['label'] ); ?></a></li>
						<?php endforeach; ?>
						</ul>
						<?php endif; ?>
					</li>
			<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

function bhdt_wirecutter_site_function_links() {
	$rows = bhdt_wirecutter_get_function_menu_blocks();
	$links = array();

	foreach ( $rows as $row ) {
		$links[] = array(
			'label' => $row['label'],
			'url'   => bhdt_wirecutter_build_parent_landing_url( 'function', $row['label_vi'] ?? $row['label'] ),
		);
	}

	return $links;
}

function bhdt_wirecutter_render_function_menu() {
	$sections = bhdt_wirecutter_get_function_menu_blocks();

	ob_start();
	?>
	<section class="bhdt-function-menu" aria-label="<?php esc_attr_e( 'Menu Chức Năng', 'bhdt-wirecutter' ); ?>">
		<div class="bhdt-function-menu-inner">
			<ul class="bhdt-function-menu-list">
				<?php foreach ( $sections as $section ) : ?>
					<?php
					$parent_url = bhdt_wirecutter_build_parent_landing_url( 'function', $section['label_vi'] ?? $section['label'] );
					$child_active = false;
					foreach ( $section['children'] ?? array() as $link ) {
						if ( bhdt_wirecutter_is_menu_url_active( $link['url'] ?? '' ) ) {
							$child_active = true;
							break;
						}
					}
					$parent_active = bhdt_wirecutter_is_menu_url_active( $parent_url ) || $child_active;
					?>
					<li class="bhdt-function-menu-item <?php echo $parent_active ? 'is-active' : ''; ?>">
						<a class="bhdt-function-menu-link <?php echo $parent_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $parent_url ); ?>" <?php echo $parent_active ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $section['label'] ); ?></a>
						<?php if ( ! empty( $section['children'] ) ) : ?>
						<ul class="bhdt-function-child-list">
						<?php foreach ( $section['children'] as $link ) : ?>
							<?php $link_active = bhdt_wirecutter_is_menu_url_active( $link['url'] ?? '' ); ?>
							<li><a class="<?php echo $link_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $link['url'] ); ?>" <?php echo $link_active ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $link['label'] ); ?></a></li>
						<?php endforeach; ?>
						</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

function bhdt_wirecutter_top_topics() {
	return array(
		array(
			'label' => __( 'Máy đo đa năng', 'bhdt-wirecutter' ),
		),
		array(
			'label' => __( 'Mô-đun công suất', 'bhdt-wirecutter' ),
			'url'   => home_url( '/?s=lm2596' ),
			'note'  => __( 'Các ray DC ổn định', 'bhdt-wirecutter' ),
		),
		array(
			'label' => __( 'Dự án ESP32', 'bhdt-wirecutter' ),
			'url'   => home_url( '/?s=esp32' ),
			'note'  => __( 'IoT & tự động hóa', 'bhdt-wirecutter' ),
		),
		array(
			'label' => __( 'Hàn', 'bhdt-wirecutter' ),
			'url'   => home_url( '/?s=soldering' ),
			'note'  => __( 'Đồ cơ bản cho bàn làm việc', 'bhdt-wirecutter' ),
		),
		array(
			'label' => __( 'Bộ khởi đầu', 'bhdt-wirecutter' ),
			'url'   => home_url( '/?s=kit' ),
			'note'  => __( 'Lựa chọn thân thiện cho người mới bắt đầu', 'bhdt-wirecutter' ),
		),
	);
}

function bhdt_wirecutter_render_top_topics() {
	$topics = bhdt_wirecutter_top_topics();

	ob_start();
	?>
	<section class="bhdt-topics-box">
		<h3><?php esc_html_e( 'Chủ đề hàng đầu', 'bhdt-wirecutter' ); ?></h3>
		<ul class="bhdt-topics-list">
			<?php foreach ( $topics as $topic ) : ?>
				<li>
					<a href="<?php echo esc_url( $topic['url'] ); ?>">
						<span class="bhdt-topics-label"><?php echo esc_html( $topic['label'] ); ?></span>
						<span class="bhdt-topics-note"><?php echo esc_html( $topic['note'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
	return ob_get_clean();
}

function bhdt_wirecutter_trending_now_items() {
	return array(
		array(
			'label' => __( 'Xu hướng: xây dựng tự động hóa ESP32', 'bhdt-wirecutter' ),
			'url'   => home_url( '/?s=esp32' ),
			'note'  => __( 'Chủ đề phát triển nhanh nhất tuần này', 'bhdt-wirecutter' ),
		),
		array(
			'label' => __( 'Xu hướng: đồng hồ vạn năng nổi bật', 'bhdt-wirecutter' ),
			'url'   => get_post_type_archive_link( 'bhdt_review' ),
			'note'  => __( 'Hướng dẫn mua cho nhu cầu rõ ràng', 'bhdt-wirecutter' ),
		),
		array(
			'label' => __( 'Xu hướng: dự án mô-đun công suất', 'bhdt-wirecutter' ),
			'url'   => home_url( '/?s=lm2596' ),
			'note'  => __( 'Phổ biến trong DIY và sửa chữa', 'bhdt-wirecutter' ),
		),
		array(
			'label' => __( 'Xu hướng: đồ hàn thiết yếu', 'bhdt-wirecutter' ),
			'url'   => home_url( '/?s=soldering' ),
			'note'  => __( 'Đồ cơ bản cho bàn làm việc', 'bhdt-wirecutter' ),
		),
	);
}

function bhdt_wirecutter_render_trending_now() {
	$items = bhdt_wirecutter_trending_now_items();

	ob_start();
	?>
	<section class="bhdt-trending-box">
		<h3><?php esc_html_e( 'Xu hướng hiện tại', 'bhdt-wirecutter' ); ?></h3>
		<ul class="bhdt-trending-list">
			<?php foreach ( $items as $item ) : ?>
				<li>
					<a href="<?php echo esc_url( $item['url'] ); ?>">
						<span class="bhdt-trending-label"><?php echo esc_html( $item['label'] ); ?></span>
						<span class="bhdt-trending-note"><?php echo esc_html( $item['note'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
	return ob_get_clean();
}

