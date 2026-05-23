<?php
/**
 * Theme functions.
 *
 * @package BHDT_Wirecutter_Clone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bhdt_wirecutter_menu_ant_enabled() {
	return '0' !== (string) get_option( 'bhdt_wire_menu_ant_enabled', '1' );
}

// Debug route for function menu
function bhdt_wirecutter_debug_route() {
	if ( strpos( $_SERVER['REQUEST_URI'], '/debug-function-menu' ) === 0 ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
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

function bhdt_wirecutter_extend_search_post_types( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	$post_types = array( 'post', 'page', 'bhdt_review', 'bhdt_comparison', 'bhdt_project' );
	if ( class_exists( 'WooCommerce' ) ) {
		$post_types[] = 'product';
	}

	$query->set( 'post_type', array_values( array_unique( $post_types ) ) );
	$query->set( 'post_status', 'publish' );
	$query->set( 'lang', '' );
}
add_action( 'pre_get_posts', 'bhdt_wirecutter_extend_search_post_types' );

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
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'comments' ),
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
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'comments' ),
			'taxonomies'         => array( 'category', 'post_tag' ),
			'show_in_nav_menus'  => true,
			'publicly_queryable' => true,
		)
	);
}
add_action( 'init', 'bhdt_wirecutter_register_editorial_post_types', 9 );

function bhdt_wirecutter_default_comments_for_editorial_posts( $data, $postarr ) {
	if ( ! in_array( $data['post_type'] ?? '', array( 'bhdt_review', 'bhdt_comparison' ), true ) ) {
		return $data;
	}

	if ( empty( $postarr['ID'] ) && empty( $postarr['comment_status'] ) ) {
		$data['comment_status'] = 'open';
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'bhdt_wirecutter_default_comments_for_editorial_posts', 10, 2 );

function bhdt_wirecutter_enable_existing_editorial_comments() {
	$version = '2026-05-20-1';
	if ( $version === (string) get_option( 'bhdt_wire_editorial_comments_enabled_version', '' ) ) {
		return;
	}

	global $wpdb;
	$wpdb->query(
		"UPDATE {$wpdb->posts}
		SET comment_status = 'open'
		WHERE post_type IN ('bhdt_review', 'bhdt_comparison')
			AND post_status = 'publish'
			AND comment_status <> 'open'"
	);

	update_option( 'bhdt_wire_editorial_comments_enabled_version', $version, false );
}
add_action( 'init', 'bhdt_wirecutter_enable_existing_editorial_comments', 30 );

function bhdt_wirecutter_get_spam_blacklist() {
	$settings = get_option( 'bhdt_wire_comment_spam_blacklist', array() );
	$settings = is_array( $settings ) ? $settings : array();

	return array(
		'ips'      => bhdt_wirecutter_clean_spam_blacklist_lines( $settings['ips'] ?? '' ),
		'emails'   => bhdt_wirecutter_clean_spam_blacklist_lines( $settings['emails'] ?? '' ),
		'keywords' => bhdt_wirecutter_clean_spam_blacklist_lines( $settings['keywords'] ?? '' ),
	);
}

function bhdt_wirecutter_clean_spam_blacklist_lines( $value ) {
	$lines = is_array( $value ) ? $value : preg_split( '/[\r\n,]+/', (string) $value );
	if ( ! is_array( $lines ) ) {
		return array();
	}

	return array_values(
		array_unique(
			array_filter(
				array_map(
					static function ( $line ) {
						return sanitize_text_field( trim( (string) $line ) );
					},
					$lines
				),
				static function ( $line ) {
					return '' !== $line;
				}
			)
		)
	);
}

function bhdt_wirecutter_spam_blacklist_to_text( $items ) {
	return implode( "\n", bhdt_wirecutter_clean_spam_blacklist_lines( $items ) );
}

function bhdt_wirecutter_comment_matches_spam_blacklist( $commentdata ) {
	$blacklist = bhdt_wirecutter_get_spam_blacklist();
	$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	$email = strtolower( trim( (string) ( $commentdata['comment_author_email'] ?? '' ) ) );
	$content = strtolower(
		trim(
			(string) ( $commentdata['comment_author'] ?? '' ) . ' '
			. (string) ( $commentdata['comment_author_email'] ?? '' ) . ' '
			. (string) ( $commentdata['comment_author_url'] ?? '' ) . ' '
			. (string) ( $commentdata['comment_content'] ?? '' )
		)
	);

	foreach ( $blacklist['ips'] as $blocked_ip ) {
		$blocked_ip = trim( $blocked_ip );
		if ( '' !== $blocked_ip && ( $ip === $blocked_ip || 0 === strpos( $ip, rtrim( $blocked_ip, '*' ) ) ) ) {
			return 'ip';
		}
	}
	foreach ( $blacklist['emails'] as $blocked_email ) {
		$blocked_email = strtolower( trim( $blocked_email ) );
		if ( '' !== $blocked_email && ( $email === $blocked_email || false !== strpos( $email, ltrim( $blocked_email, '@' ) ) ) ) {
			return 'email';
		}
	}
	foreach ( $blacklist['keywords'] as $keyword ) {
		$keyword = strtolower( trim( $keyword ) );
		if ( '' !== $keyword && false !== strpos( $content, $keyword ) ) {
			return 'keyword';
		}
	}

	return '';
}

function bhdt_wirecutter_comment_spam_field() {
	if ( is_user_logged_in() ) {
		return;
	}

	$issued = time();
	?>
	<p class="comment-form-url" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
		<label for="bhdt_comment_company">Company</label>
		<input type="text" id="bhdt_comment_company" name="bhdt_comment_company" value="" tabindex="-1" autocomplete="off">
	</p>
	<input type="hidden" name="bhdt_comment_time" value="<?php echo esc_attr( (string) $issued ); ?>">
	<input type="hidden" name="bhdt_comment_token" value="<?php echo esc_attr( wp_create_nonce( 'bhdt_comment_guard_' . $issued ) ); ?>">
	<?php
}
add_action( 'comment_form_after_fields', 'bhdt_wirecutter_comment_spam_field' );
add_action( 'comment_form_logged_in_after', 'bhdt_wirecutter_comment_spam_field' );

function bhdt_wirecutter_reject_comment_spam( $commentdata ) {
	if ( is_user_logged_in() && current_user_can( 'moderate_comments' ) ) {
		return $commentdata;
	}

	$issued = isset( $_POST['bhdt_comment_time'] ) ? absint( wp_unslash( $_POST['bhdt_comment_time'] ) ) : 0;
	$token = isset( $_POST['bhdt_comment_token'] ) ? sanitize_text_field( wp_unslash( $_POST['bhdt_comment_token'] ) ) : '';
	$honeypot = isset( $_POST['bhdt_comment_company'] ) ? trim( (string) wp_unslash( $_POST['bhdt_comment_company'] ) ) : '';
	$now = time();

	if ( bhdt_wirecutter_comment_matches_spam_blacklist( $commentdata ) ) {
		wp_die( esc_html__( 'Spam comment blocked.', 'bhdt-wirecutter' ), 403 );
	}

	if ( '' !== $honeypot ) {
		wp_die( esc_html__( 'Spam comment blocked.', 'bhdt-wirecutter' ), 403 );
	}

	if ( ! $issued || ! wp_verify_nonce( $token, 'bhdt_comment_guard_' . $issued ) || $now - $issued < 4 || $now - $issued > DAY_IN_SECONDS ) {
		wp_die( esc_html__( 'Comment form expired. Please reload the page and try again.', 'bhdt-wirecutter' ), 403 );
	}

	$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
	$email = sanitize_email( (string) ( $commentdata['comment_author_email'] ?? '' ) );
	$rate_key = 'bhdt_comment_rate_' . md5( $ip . '|' . strtolower( $email ) );
	if ( get_transient( $rate_key ) ) {
		wp_die( esc_html__( 'Please wait before posting another comment.', 'bhdt-wirecutter' ), 429 );
	}
	set_transient( $rate_key, 1, 90 );

	return $commentdata;
}
add_filter( 'preprocess_comment', 'bhdt_wirecutter_reject_comment_spam', 1 );

function bhdt_wirecutter_score_comment_spam( $approved, $commentdata ) {
	if ( is_user_logged_in() && current_user_can( 'moderate_comments' ) ) {
		return $approved;
	}

	$content = trim( (string) ( $commentdata['comment_content'] ?? '' ) );
	$author = trim( (string) ( $commentdata['comment_author'] ?? '' ) );
	$email = trim( (string) ( $commentdata['comment_author_email'] ?? '' ) );
	$url = trim( (string) ( $commentdata['comment_author_url'] ?? '' ) );
	$joined = strtolower( $author . ' ' . $email . ' ' . $url . ' ' . $content );
	$score = 0;

	if ( bhdt_wirecutter_comment_matches_spam_blacklist( $commentdata ) ) {
		return 'spam';
	}

	if ( strlen( $content ) < 12 ) {
		$score += 2;
	}
	if ( preg_match_all( '#https?://|www\.#i', $content . ' ' . $url ) > 1 ) {
		$score += 3;
	}
	if ( preg_match( '/[bcdfghjklmnpqrstvwxyz]{6,}/i', $author . ' ' . $content ) ) {
		$score += 2;
	}
	if ( preg_match( '/casino|crypto|loan|viagra|porn|sex|escort|telegram|whatsapp|betting|free money|seo backlink/i', $joined ) ) {
		$score += 4;
	}
	if ( preg_match( '/^[a-z0-9]{10,}$/i', preg_replace( '/\s+/', '', $author ) ) ) {
		$score += 2;
	}
	if ( '' !== $email && ! is_email( $email ) ) {
		$score += 3;
	}

	if ( $score >= 5 ) {
		return 'spam';
	}
	if ( $score >= 3 ) {
		return 0;
	}

	return $approved;
}
add_filter( 'pre_comment_approved', 'bhdt_wirecutter_score_comment_spam', 10, 2 );

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

function bhdt_wirecutter_normalize_category_label_key( $label ) {
	$label = wp_strip_all_tags( (string) $label );
	$label = trim( preg_replace( '/\s+/', ' ', $label ) );

	if ( '' === $label ) {
		return '';
	}

	return sanitize_title( remove_accents( $label ) );
}

function bhdt_wirecutter_get_category_duplicate_groups() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $terms ) ) {
		return array();
	}

	$groups = array();
	foreach ( $terms as $term ) {
		$key = bhdt_wirecutter_normalize_category_label_key( $term->name );
		if ( '' === $key ) {
			continue;
		}

		if ( ! isset( $groups[ $key ] ) ) {
			$groups[ $key ] = array();
		}

		$groups[ $key ][] = $term;
	}

	return array_filter(
		$groups,
		static function ( $items ) {
			return count( $items ) > 1;
		}
	);
}

function bhdt_wirecutter_find_existing_category_for_label( $label, $preferred_slug = '' ) {
	$preferred_slug = sanitize_title( (string) $preferred_slug );
	if ( '' !== $preferred_slug ) {
		$term = get_term_by( 'slug', $preferred_slug, 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			return $term;
		}
	}

	$label_key = bhdt_wirecutter_normalize_category_label_key( $label );
	if ( '' === $label_key ) {
		return null;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $terms ) ) {
		return null;
	}

	$matches = array();
	foreach ( $terms as $term ) {
		if ( $label_key === bhdt_wirecutter_normalize_category_label_key( $term->name ) ) {
			$matches[] = $term;
		}
	}

	if ( empty( $matches ) ) {
		return null;
	}

	usort(
		$matches,
		static function ( $a, $b ) {
			return ( (int) $b->count <=> (int) $a->count ) ?: strcmp( (string) $a->slug, (string) $b->slug );
		}
	);

	return $matches[0];
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

function bhdt_wirecutter_get_mid_page_ad_widget_settings() {
	$defaults = array(
		'enabled'      => 0,
		'position'     => 'before_category',
		'target_block' => '',
		'html'         => '',
	);
	$widgets = bhdt_wirecutter_get_mid_page_ad_widgets();
	if ( ! empty( $widgets ) ) {
		$first = $widgets[0];
		return wp_parse_args(
			array(
				'enabled'      => (int) ( $first['enabled'] ?? 0 ),
				'position'     => $first['position'] ?? 'before_category',
				'target_block' => $first['target_block'] ?? '',
				'html'         => $first['html'] ?? '',
			),
			$defaults
		);
	}

	return $defaults;
}

function bhdt_wirecutter_normalize_mid_page_ad_widget( $widget ) {
	if ( ! is_array( $widget ) ) {
		$widget = array();
	}

	$id = sanitize_key( (string) ( $widget['id'] ?? '' ) );
	if ( '' === $id ) {
		$id = 'ad_' . wp_generate_password( 8, false, false );
	}

	$position = sanitize_key( (string) ( $widget['position'] ?? 'before_category' ) );
	if ( ! in_array( $position, array( 'before_category', 'site_end' ), true ) ) {
		$position = 'before_category';
	}

	return array(
		'id'           => $id,
		'title'        => sanitize_text_field( (string) ( $widget['title'] ?? '' ) ),
		'enabled'      => ! empty( $widget['enabled'] ) ? 1 : 0,
		'position'     => $position,
		'target_block' => sanitize_title( (string) ( $widget['target_block'] ?? '' ) ),
		'affiliate_url' => esc_url_raw( (string) ( $widget['affiliate_url'] ?? '' ) ),
		'preview_image_url' => esc_url_raw( (string) ( $widget['preview_image_url'] ?? '' ) ),
		'auto_preview' => ! empty( $widget['auto_preview'] ) ? 1 : 0,
		'cta_label'    => sanitize_text_field( (string) ( $widget['cta_label'] ?? '' ) ),
		'html'         => (string) ( $widget['html'] ?? '' ),
	);
}

function bhdt_wirecutter_get_mid_page_ad_widgets() {
	$widgets = get_option( 'bhdt_wire_mid_page_ad_widgets', null );
	if ( is_array( $widgets ) ) {
		return array_values(
			array_map(
				'bhdt_wirecutter_normalize_mid_page_ad_widget',
				$widgets
			)
		);
	}

	$settings = get_option( 'bhdt_wire_mid_page_ad_widget', array() );

	if ( ! is_array( $settings ) ) {
		return array();
	}

	$legacy = bhdt_wirecutter_normalize_mid_page_ad_widget(
		array(
			'id'           => 'legacy_mid_ad',
			'title'        => 'Mid-page ad',
			'enabled'      => $settings['enabled'] ?? 0,
			'position'     => $settings['position'] ?? 'before_category',
			'target_block' => $settings['target_block'] ?? '',
			'affiliate_url' => $settings['affiliate_url'] ?? '',
			'preview_image_url' => $settings['preview_image_url'] ?? '',
			'auto_preview' => $settings['auto_preview'] ?? 0,
			'cta_label'    => $settings['cta_label'] ?? '',
			'html'         => $settings['html'] ?? '',
		)
	);

	return '' !== trim( $legacy['html'] ) || '' !== $legacy['affiliate_url'] || 1 === (int) $legacy['enabled'] ? array( $legacy ) : array();
}

function bhdt_wirecutter_extract_affiliate_target_url( $affiliate_url ) {
	$affiliate_url = trim( (string) $affiliate_url );
	if ( '' === $affiliate_url ) {
		return '';
	}

	$parts = wp_parse_url( $affiliate_url );
	if ( ! empty( $parts['query'] ) ) {
		parse_str( $parts['query'], $query_vars );
		foreach ( array( 'url', 'u', 'target', 'redirect', 'to' ) as $key ) {
			if ( ! empty( $query_vars[ $key ] ) && is_string( $query_vars[ $key ] ) ) {
				$target_url = esc_url_raw( rawurldecode( $query_vars[ $key ] ) );
				if ( '' !== $target_url ) {
					return $target_url;
				}
			}
		}
	}

	return esc_url_raw( $affiliate_url );
}

function bhdt_wirecutter_make_absolute_url( $url, $base_url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}

	if ( preg_match( '#^https?://#i', $url ) ) {
		return esc_url_raw( $url );
	}

	if ( 0 === strpos( $url, '//' ) ) {
		$base_parts = wp_parse_url( $base_url );
		$scheme = ! empty( $base_parts['scheme'] ) ? $base_parts['scheme'] : 'https';
		return esc_url_raw( $scheme . ':' . $url );
	}

	$base_parts = wp_parse_url( $base_url );
	if ( empty( $base_parts['scheme'] ) || empty( $base_parts['host'] ) ) {
		return '';
	}

	$path = '/' === substr( $url, 0, 1 ) ? $url : trailingslashit( dirname( $base_parts['path'] ?? '/' ) ) . $url;
	return esc_url_raw( $base_parts['scheme'] . '://' . $base_parts['host'] . $path );
}

function bhdt_wirecutter_resolve_affiliate_preview_url( $affiliate_url ) {
	$target_url = bhdt_wirecutter_extract_affiliate_target_url( $affiliate_url );
	if ( '' === $target_url ) {
		return '';
	}

	$cache_key = 'bhdt_affiliate_resolved_' . md5( $target_url );
	$cached = get_transient( $cache_key );
	if ( is_string( $cached ) ) {
		return esc_url_raw( $cached );
	}

	$current_url = $target_url;
	for ( $attempt = 0; $attempt < 6; $attempt++ ) {
		$response = wp_remote_head(
			$current_url,
			array(
				'timeout'     => 6,
				'redirection' => 0,
				'user-agent'  => 'Mozilla/5.0 BHDTPreviewBot/1.0',
			)
		);

		if ( is_wp_error( $response ) || 405 === (int) wp_remote_retrieve_response_code( $response ) ) {
			$response = wp_remote_get(
				$current_url,
				array(
					'timeout'     => 6,
					'redirection' => 0,
					'user-agent'  => 'Mozilla/5.0 BHDTPreviewBot/1.0',
				)
			);
		}

		if ( is_wp_error( $response ) ) {
			break;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$location = wp_remote_retrieve_header( $response, 'location' );
		if ( $status_code < 300 || $status_code >= 400 || empty( $location ) ) {
			break;
		}

		$next_url = bhdt_wirecutter_make_absolute_url( is_array( $location ) ? reset( $location ) : $location, $current_url );
		if ( '' === $next_url || $next_url === $current_url ) {
			break;
		}

		$current_url = $next_url;
	}

	set_transient( $cache_key, $current_url, 12 * HOUR_IN_SECONDS );
	return esc_url_raw( $current_url );
}

function bhdt_wirecutter_preview_image_is_bad( $image_url, $title = '', $description = '' ) {
	$image_url = strtolower( (string) $image_url );
	$text = strtolower( wp_strip_all_tags( (string) $title . ' ' . (string) $description ) );

	if ( '' === $image_url ) {
		return true;
	}

	foreach ( array( 'verify to continue', 'captcha', 'robot check', 'access denied', 'just a moment' ) as $bad_text ) {
		if ( false !== strpos( $text, $bad_text ) ) {
			return true;
		}
	}

	foreach ( array( 'favicon', 'apple-touch-icon', 'logo', 'icon', 'sprite', 'tiktok' ) as $bad_image_part ) {
		if ( false !== strpos( $image_url, $bad_image_part ) ) {
			return true;
		}
	}

	return false;
}

function bhdt_wirecutter_pick_better_preview_image( $current_image, $candidate_image, $title = '', $description = '' ) {
	$candidate_image = esc_url_raw( (string) $candidate_image );
	if ( '' === $candidate_image || bhdt_wirecutter_preview_image_is_bad( $candidate_image, '', '' ) ) {
		return esc_url_raw( (string) $current_image );
	}

	if ( bhdt_wirecutter_preview_image_is_bad( $current_image, $title, $description ) ) {
		return $candidate_image;
	}

	return esc_url_raw( (string) $current_image );
}

function bhdt_wirecutter_extract_image_from_json_ld( $json ) {
	$data = json_decode( html_entity_decode( (string) $json, ENT_QUOTES, get_bloginfo( 'charset' ) ), true );
	if ( ! is_array( $data ) ) {
		return '';
	}

	$items = isset( $data[0] ) ? $data : array( $data );
	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$image = $item['image'] ?? '';
		if ( is_string( $image ) ) {
			return esc_url_raw( $image );
		}
		if ( is_array( $image ) ) {
			$first = $image[0] ?? ( $image['url'] ?? '' );
			if ( is_string( $first ) ) {
				return esc_url_raw( $first );
			}
		}
	}

	return '';
}

function bhdt_wirecutter_get_page_image_preview( $target_url ) {
	$target_url = esc_url_raw( (string) $target_url );
	if ( '' === $target_url ) {
		return '';
	}

	$cache_key = 'bhdt_page_image_preview_' . md5( $target_url );
	$cached = get_transient( $cache_key );
	if ( is_string( $cached ) ) {
		return esc_url_raw( $cached );
	}

	$response = wp_remote_get(
		$target_url,
		array(
			'timeout'     => 10,
			'redirection' => 5,
			'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
			'headers'     => array(
				'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		set_transient( $cache_key, '', HOUR_IN_SECONDS );
		return '';
	}

	$html = (string) wp_remote_retrieve_body( $response );
	if ( '' === trim( $html ) ) {
		set_transient( $cache_key, '', HOUR_IN_SECONDS );
		return '';
	}

	$candidates = array();
	if ( preg_match_all( '/<meta[^>]+(?:property|name|itemprop)=["\'](?:og:image|og:image:secure_url|twitter:image|image)["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches ) ) {
		$candidates = array_merge( $candidates, $matches[1] );
	}
	if ( preg_match_all( '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name|itemprop)=["\'](?:og:image|og:image:secure_url|twitter:image|image)["\'][^>]*>/i', $html, $matches ) ) {
		$candidates = array_merge( $candidates, $matches[1] );
	}
	if ( preg_match_all( '/<link[^>]+rel=["\']image_src["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches ) ) {
		$candidates = array_merge( $candidates, $matches[1] );
	}
	if ( preg_match_all( '/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches ) ) {
		foreach ( $matches[1] as $json ) {
			$candidates[] = bhdt_wirecutter_extract_image_from_json_ld( $json );
		}
	}
	if ( preg_match_all( '/<img[^>]+(?:src|data-src|data-original)=["\']([^"\']+)["\'][^>]*>/i', $html, $matches ) ) {
		$candidates = array_merge( $candidates, $matches[1] );
	}

	foreach ( array_unique( array_filter( $candidates ) ) as $candidate ) {
		$image_url = bhdt_wirecutter_make_absolute_url( html_entity_decode( $candidate, ENT_QUOTES, get_bloginfo( 'charset' ) ), $target_url );
		if ( '' !== $image_url && ! bhdt_wirecutter_preview_image_is_bad( $image_url, '', '' ) ) {
			set_transient( $cache_key, $image_url, 12 * HOUR_IN_SECONDS );
			return $image_url;
		}
	}

	set_transient( $cache_key, '', HOUR_IN_SECONDS );
	return '';
}

function bhdt_wirecutter_get_affiliate_microlink_preview( $affiliate_url, $force_refresh = false ) {
	$target_url = bhdt_wirecutter_resolve_affiliate_preview_url( $affiliate_url );
	if ( '' === $target_url ) {
		return array();
	}

	$cache_key = 'bhdt_affiliate_preview_' . md5( $target_url );
	if ( $force_refresh ) {
		delete_transient( $cache_key );
	} else {
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$response = wp_remote_get(
		add_query_arg(
			array(
				'url' => $target_url,
			),
			'https://api.microlink.io/'
		),
		array(
			'timeout' => 8,
		)
	);

	if ( is_wp_error( $response ) ) {
		set_transient( $cache_key, array(), HOUR_IN_SECONDS );
		return array();
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $body ) || 'success' !== ( $body['status'] ?? '' ) || empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
		set_transient( $cache_key, array(), HOUR_IN_SECONDS );
		return array();
	}

	$data = $body['data'];
	$image_url = '';
	if ( ! empty( $data['image']['url'] ) ) {
		$image_url = esc_url_raw( $data['image']['url'] );
	} elseif ( ! empty( $data['logo']['url'] ) ) {
		$image_url = esc_url_raw( $data['logo']['url'] );
	}
	$title = sanitize_text_field( (string) ( $data['title'] ?? '' ) );
	$description = sanitize_text_field( (string) ( $data['description'] ?? '' ) );
	if ( bhdt_wirecutter_preview_image_is_bad( $image_url, $title, $description ) ) {
		$image_url = bhdt_wirecutter_pick_better_preview_image(
			$image_url,
			bhdt_wirecutter_get_page_image_preview( $target_url ),
			$title,
			$description
		);
	}

	$preview = array(
		'target_url'  => $target_url,
		'image_url'   => $image_url,
		'title'       => $title,
		'description' => $description,
	);
	set_transient( $cache_key, $preview, 12 * HOUR_IN_SECONDS );

	return $preview;
}

function bhdt_wirecutter_ajax_fetch_ad_preview_image() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array(
				'message' => 'Không có quyền lấy ảnh preview.',
			),
			403
		);
	}

	check_ajax_referer( 'bhdt_fetch_ad_preview_image', 'nonce' );

	$affiliate_url = isset( $_POST['affiliate_url'] ) ? esc_url_raw( wp_unslash( $_POST['affiliate_url'] ) ) : '';
	if ( '' === $affiliate_url ) {
		wp_send_json_error(
			array(
				'message' => 'Nhập Affiliate URL trước.',
			),
			400
		);
	}

	$preview = bhdt_wirecutter_get_affiliate_microlink_preview( $affiliate_url, true );
	$image_url = esc_url_raw( (string) ( $preview['image_url'] ?? '' ) );
	if ( '' === $image_url ) {
		wp_send_json_error(
			array(
				'message' => 'Chưa tìm thấy ảnh preview từ link này.',
			),
			404
		);
	}

	wp_send_json_success(
		array(
			'image_url'   => $image_url,
			'target_url'  => esc_url_raw( (string) ( $preview['target_url'] ?? '' ) ),
			'title'       => sanitize_text_field( (string) ( $preview['title'] ?? '' ) ),
			'description' => sanitize_text_field( (string) ( $preview['description'] ?? '' ) ),
		)
	);
}
add_action( 'wp_ajax_bhdt_fetch_ad_preview_image', 'bhdt_wirecutter_ajax_fetch_ad_preview_image' );

function bhdt_wirecutter_ad_widget_has_content( $widget ) {
	return '' !== trim( (string) ( $widget['html'] ?? '' ) )
		|| ( ! empty( $widget['auto_preview'] ) && '' !== (string) ( $widget['affiliate_url'] ?? '' ) );
}

function bhdt_wirecutter_render_affiliate_preview_ad( $widget, $context = 'affiliate-preview' ) {
	$affiliate_url = esc_url_raw( (string) ( $widget['affiliate_url'] ?? '' ) );
	if ( empty( $widget['auto_preview'] ) || '' === $affiliate_url ) {
		return '';
	}

	$preview = bhdt_wirecutter_get_affiliate_microlink_preview( $affiliate_url );
	$title = '' !== (string) ( $widget['title'] ?? '' )
		? (string) $widget['title']
		: (string) ( $preview['title'] ?? '' );
	$description = (string) ( $preview['description'] ?? '' );
	$image_url = '' !== (string) ( $widget['preview_image_url'] ?? '' )
		? (string) $widget['preview_image_url']
		: (string) ( $preview['image_url'] ?? '' );
	$cta_label = '' !== (string) ( $widget['cta_label'] ?? '' ) ? (string) $widget['cta_label'] : 'Xem sản phẩm';

	if ( '' === $image_url && '' === $title && '' === $description ) {
		return '';
	}

	ob_start();
	?>
	<a class="bhdt-affiliate-preview-ad" href="<?php echo esc_url( $affiliate_url ); ?>" target="_blank" rel="nofollow sponsored noopener noreferrer" data-bhdt-ad-context="<?php echo esc_attr( $context ); ?>">
		<?php if ( '' !== $image_url ) : ?>
			<span class="bhdt-affiliate-preview-media">
				<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
			</span>
		<?php endif; ?>
		<span class="bhdt-affiliate-preview-copy">
			<?php if ( '' !== $title ) : ?>
				<strong><?php echo esc_html( $title ); ?></strong>
			<?php endif; ?>
			<?php if ( '' !== $description ) : ?>
				<small><?php echo esc_html( wp_trim_words( $description, 22, '...' ) ); ?></small>
			<?php endif; ?>
			<em><?php echo esc_html( $cta_label ); ?></em>
		</span>
	</a>
	<?php
	return ob_get_clean();
}

function bhdt_wirecutter_mid_page_ad_group_key( $group ) {
	if ( ! is_array( $group ) ) {
		return '';
	}

	$key = function_exists( 'bhdt_wirecutter_get_home_category_block_public_slug' )
		? bhdt_wirecutter_get_home_category_block_public_slug( $group )
		: '';

	if ( '' === $key ) {
		$key = sanitize_title( $group['slug'] ?? '' );
	}

	return sanitize_title( $key );
}

function bhdt_wirecutter_mid_page_ad_widget_matches_group( $widget, $group ) {
	if ( 1 !== (int) ( $widget['enabled'] ?? 0 ) || 'before_category' !== ( $widget['position'] ?? '' ) || ! bhdt_wirecutter_ad_widget_has_content( $widget ) ) {
		return false;
	}

	$target = sanitize_title( (string) ( $widget['target_block'] ?? '' ) );
	if ( '' === $target ) {
		return false;
	}

	$group_keys = array_filter(
		array(
			bhdt_wirecutter_mid_page_ad_group_key( $group ),
			sanitize_title( $group['slug'] ?? '' ),
			sanitize_title( $group['label'] ?? '' ),
			sanitize_title( $group['name'] ?? '' ),
		)
	);

	return in_array( $target, array_values( array_unique( $group_keys ) ), true );
}

function bhdt_wirecutter_should_render_mid_page_ad_before_group( $group ) {
	foreach ( bhdt_wirecutter_get_mid_page_ad_widgets() as $widget ) {
		if ( bhdt_wirecutter_mid_page_ad_widget_matches_group( $widget, $group ) ) {
			return true;
		}
	}

	return false;
}

function bhdt_wirecutter_should_render_mid_page_ad_site_end() {
	foreach ( bhdt_wirecutter_get_mid_page_ad_widgets() as $widget ) {
		if ( 1 === (int) $widget['enabled'] && 'site_end' === $widget['position'] && bhdt_wirecutter_ad_widget_has_content( $widget ) ) {
			return true;
		}
	}

	return false;
}

function bhdt_wirecutter_render_mid_page_ad_widget( $context = 'home', $group = null ) {
	static $rendered = array();
	$widgets = bhdt_wirecutter_get_mid_page_ad_widgets();

	foreach ( $widgets as $widget ) {
		if ( isset( $rendered[ $widget['id'] ] ) ) {
			continue;
		}

		$should_render = false;
		if ( 'before-category' === $context ) {
			$should_render = bhdt_wirecutter_mid_page_ad_widget_matches_group( $widget, $group );
		} elseif ( 'site-end' === $context ) {
			$should_render = 1 === (int) $widget['enabled'] && 'site_end' === $widget['position'] && bhdt_wirecutter_ad_widget_has_content( $widget );
		}

		if ( ! $should_render ) {
			continue;
		}

		$rendered[ $widget['id'] ] = true;
		$label = '' !== $widget['title'] ? $widget['title'] : __( 'Quảng cáo', 'bhdt-wirecutter' );
	?>
	<section class="bhdt-mid-page-ad-widget" data-bhdt-ad-context="<?php echo esc_attr( $context ); ?>" data-bhdt-ad-id="<?php echo esc_attr( $widget['id'] ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
		<?php echo bhdt_wirecutter_render_affiliate_preview_ad( $widget, $context ); ?>
		<?php echo $widget['html']; ?>
	</section>
	<?php
	}
}

function bhdt_wirecutter_normalize_in_article_ad_widget( $widget ) {
	if ( ! is_array( $widget ) ) {
		$widget = array();
	}

	$id = sanitize_key( (string) ( $widget['id'] ?? '' ) );
	if ( '' === $id ) {
		$id = 'article_ad_' . wp_generate_password( 8, false, false );
	}

	$post_types = isset( $widget['post_types'] ) && is_array( $widget['post_types'] )
		? $widget['post_types']
		: array( 'post', 'bhdt_review', 'bhdt_comparison', 'bhdt_project' );
	$post_types = array_values(
		array_filter(
			array_map( 'sanitize_key', $post_types ),
			'post_type_exists'
		)
	);

	$insertion_mode = sanitize_key( (string) ( $widget['insertion_mode'] ?? 'after_paragraph' ) );
	if ( ! in_array( $insertion_mode, array( 'after_paragraph', 'before_heading', 'after_heading' ), true ) ) {
		$insertion_mode = 'after_paragraph';
	}

	$heading_filter = sanitize_key( (string) ( $widget['heading_filter'] ?? 'all' ) );
	if ( ! in_array( $heading_filter, array( 'all', 'indexed_upper' ), true ) ) {
		$heading_filter = 'all';
	}

	return array(
		'id'              => $id,
		'title'           => sanitize_text_field( (string) ( $widget['title'] ?? '' ) ),
		'memo_name'       => sanitize_text_field( (string) ( $widget['memo_name'] ?? '' ) ),
		'enabled'         => ! empty( $widget['enabled'] ) ? 1 : 0,
		'auto_insert'     => ! empty( $widget['auto_insert'] ) ? 1 : 0,
		'insertion_mode'  => $insertion_mode,
		'after_paragraph' => max( 1, (int) ( $widget['after_paragraph'] ?? 3 ) ),
		'heading_index'   => max( 1, (int) ( $widget['heading_index'] ?? 1 ) ),
		'heading_filter'  => $heading_filter,
		'post_types'      => empty( $post_types ) ? array( 'post' ) : $post_types,
		'target_slugs'    => sanitize_textarea_field( (string) ( $widget['target_slugs'] ?? '' ) ),
		'affiliate_url'   => esc_url_raw( (string) ( $widget['affiliate_url'] ?? '' ) ),
		'preview_image_url' => esc_url_raw( (string) ( $widget['preview_image_url'] ?? '' ) ),
		'auto_preview'    => ! empty( $widget['auto_preview'] ) ? 1 : 0,
		'cta_label'       => sanitize_text_field( (string) ( $widget['cta_label'] ?? '' ) ),
		'html'            => (string) ( $widget['html'] ?? '' ),
	);
}

function bhdt_wirecutter_get_in_article_ad_widgets() {
	$widgets = get_option( 'bhdt_wire_in_article_ad_widgets', array() );
	if ( ! is_array( $widgets ) ) {
		return array();
	}

	return array_values(
		array_map(
			'bhdt_wirecutter_normalize_in_article_ad_widget',
			$widgets
		)
	);
}

function bhdt_wirecutter_render_in_article_ad_widget( $widget, $context = 'article' ) {
	$widget = bhdt_wirecutter_normalize_in_article_ad_widget( $widget );
	if ( 1 !== (int) $widget['enabled'] || ! bhdt_wirecutter_ad_widget_has_content( $widget ) ) {
		return '';
	}

	$label = '' !== $widget['title'] ? $widget['title'] : __( 'Quảng cáo', 'bhdt-wirecutter' );

	ob_start();
	?>
	<aside class="bhdt-in-article-ad-widget" data-bhdt-ad-context="<?php echo esc_attr( $context ); ?>" data-bhdt-ad-id="<?php echo esc_attr( $widget['id'] ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
		<?php echo bhdt_wirecutter_render_affiliate_preview_ad( $widget, $context ); ?>
		<?php echo $widget['html']; ?>
	</aside>
	<?php
	return ob_get_clean();
}

function bhdt_wirecutter_find_in_article_ad_widget( $id ) {
	$id = sanitize_key( (string) $id );
	foreach ( bhdt_wirecutter_get_in_article_ad_widgets() as $widget ) {
		if ( $id === $widget['id'] ) {
			return $widget;
		}
	}

	return null;
}

function bhdt_wirecutter_in_article_ad_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id' => '',
		),
		(array) $atts,
		'bhdt_article_ad'
	);

	$widget = bhdt_wirecutter_find_in_article_ad_widget( $atts['id'] );
	if ( ! is_array( $widget ) ) {
		return '';
	}

	$post_id = get_the_ID();
	$post_type = $post_id ? get_post_type( $post_id ) : '';
	if ( '' !== $post_type && ! in_array( $post_type, (array) $widget['post_types'], true ) ) {
		return '';
	}

	return bhdt_wirecutter_render_in_article_ad_widget( $widget, 'shortcode' );
}
add_shortcode( 'bhdt_article_ad', 'bhdt_wirecutter_in_article_ad_shortcode' );

function bhdt_wirecutter_add_in_article_ad_meta_box() {
	foreach ( array( 'post', 'bhdt_review', 'bhdt_comparison', 'bhdt_project' ) as $post_type ) {
		if ( post_type_exists( $post_type ) ) {
			add_meta_box(
				'bhdt-in-article-ad-shortcodes',
				'Vị trí widget quảng cáo trong bài',
				'bhdt_wirecutter_render_in_article_ad_meta_box',
				$post_type,
				'normal',
				'high'
			);
		}
	}
}
add_action( 'add_meta_boxes', 'bhdt_wirecutter_add_in_article_ad_meta_box' );

function bhdt_wirecutter_render_in_article_ad_meta_box( $post ) {
	$widgets = array_values(
		array_filter(
			bhdt_wirecutter_get_in_article_ad_widgets(),
			static function ( $widget ) use ( $post ) {
				return in_array( $post->post_type, (array) $widget['post_types'], true );
			}
		)
	);
	$post_slug = sanitize_title( get_post_field( 'post_name', $post->ID ) );
	$matched_widgets = array();
	$unmatched_widgets = array();
	foreach ( $widgets as $widget ) {
		if ( bhdt_wirecutter_in_article_ad_matches_post( $widget, $post->ID ) ) {
			$matched_widgets[] = $widget;
		} else {
			$unmatched_widgets[] = $widget;
		}
	}
	?>
	<p class="description">Dán dòng shortcode vào nội dung bài viết. Widget sẽ hiện đúng tại vị trí đặt dòng này, nên có thể copy rồi dời sang vị trí khác trong editor.</p>
	<?php if ( empty( $widgets ) ) : ?>
		<p>Chưa có widget quảng cáo nào cho loại bài này.</p>
	<?php else : ?>
		<p><strong>Slug bài này:</strong> <code><?php echo esc_html( $post_slug ); ?></code></p>
		<div style="display:grid;gap:10px;">
			<?php foreach ( $matched_widgets as $widget ) : ?>
				<?php
				$shortcode = '[bhdt_article_ad id="' . $widget['id'] . '"]';
				$slug_targets = bhdt_wirecutter_ad_tokenize_targets( $widget['target_slugs'] ?? '' );
				$matches_post = bhdt_wirecutter_in_article_ad_matches_post( $widget, $post->ID );
				$is_ready = 1 === (int) $widget['enabled'] && bhdt_wirecutter_ad_widget_has_content( $widget ) && $matches_post;
				$memo_name = trim( (string) ( $widget['memo_name'] ?? '' ) );
				?>
				<div style="border:1px solid #dcdcde;border-radius:6px;padding:8px;background:#fff;">
					<strong><?php echo esc_html( '' !== $widget['title'] ? $widget['title'] : $widget['id'] ); ?></strong>
					<?php if ( '' !== $memo_name ) : ?>
						<p class="description" style="margin:4px 0 0;"><strong>Tên gợi nhớ:</strong> <?php echo esc_html( $memo_name ); ?></p>
					<?php endif; ?>
					<input type="text" class="widefat code" readonly onclick="this.select();" value="<?php echo esc_attr( $shortcode ); ?>" style="margin-top:6px;">
					<p class="description" style="margin:6px 0 0;">
						<?php if ( $is_ready ) : ?>
							<span style="color:#008a20;font-weight:700;">Sẵn sàng hiển thị tại vị trí shortcode.</span>
						<?php elseif ( 1 !== (int) $widget['enabled'] ) : ?>
							<span style="color:#b32d2e;font-weight:700;">Widget đang tắt.</span>
							Bật widget trước khi shortcode có thể hiển thị.
						<?php elseif ( ! bhdt_wirecutter_ad_widget_has_content( $widget ) ) : ?>
							<span style="color:#b32d2e;font-weight:700;">Widget chưa có nội dung.</span>
							Thêm Affiliate URL, bật Auto preview hoặc nhập HTML.
						<?php elseif ( $matches_post ) : ?>
							<span style="color:#008a20;font-weight:700;">Khớp slug bài này.</span>
						<?php elseif ( ! empty( $slug_targets ) ) : ?>
							<span style="color:#b32d2e;font-weight:700;">Chưa khớp slug bài này.</span>
							Thêm <code><?php echo esc_html( $post_slug ); ?></code> vào ô "Slug bài áp dụng" của widget nếu muốn shortcode này hiển thị trên frontend.
						<?php else : ?>
							<span style="color:#008a20;font-weight:700;">Áp dụng cho mọi slug thuộc loại bài này.</span>
						<?php endif; ?>
					</p>
				</div>
			<?php endforeach; ?>
			<?php if ( ! empty( $unmatched_widgets ) ) : ?>
				<div style="border:1px solid #b32d2e;border-radius:6px;padding:10px;background:#fff8f8;">
					<strong>Widget chưa khớp slug</strong>
					<p class="description" style="margin:6px 0 8px;">Chọn widget để lấy shortcode. Shortcode đặt thủ công trong bài vẫn hiển thị bình thường.</p>
					<select class="widefat" data-bhdt-unmatched-ad-select>
						<option value="">Chọn widget chưa khớp slug...</option>
						<?php foreach ( $unmatched_widgets as $widget ) : ?>
							<?php
							$shortcode = '[bhdt_article_ad id="' . $widget['id'] . '"]';
							$memo_name = trim( (string) ( $widget['memo_name'] ?? '' ) );
							$label = '' !== $memo_name
								? $memo_name
								: ( '' !== (string) ( $widget['title'] ?? '' ) ? $widget['title'] : $widget['id'] );
							$status_parts = array();
							if ( 1 !== (int) $widget['enabled'] ) {
								$status_parts[] = 'đang tắt';
							}
							if ( ! bhdt_wirecutter_ad_widget_has_content( $widget ) ) {
								$status_parts[] = 'chưa có nội dung';
							}
							?>
							<option value="<?php echo esc_attr( $shortcode ); ?>">
								<?php echo esc_html( $label . ( ! empty( $status_parts ) ? ' (' . implode( ', ', $status_parts ) . ')' : '' ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<input type="text" class="widefat code" readonly onclick="this.select();" data-bhdt-unmatched-ad-shortcode value="" placeholder="Shortcode sẽ hiện ở đây sau khi chọn widget" style="margin-top:8px;">
				</div>
				<script>
				(function() {
					var scripts = document.getElementsByTagName('script');
					var script = scripts[scripts.length - 1];
					var box = script ? script.previousElementSibling : null;
					if (!box) return;
					var select = box.querySelector('[data-bhdt-unmatched-ad-select]');
					var output = box.querySelector('[data-bhdt-unmatched-ad-shortcode]');
					if (!select || !output) return;
					select.addEventListener('change', function() {
						output.value = select.value || '';
						if (output.value) output.select();
					});
				})();
				</script>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php
}

function bhdt_wirecutter_ad_tokenize_targets( $value ) {
	$tokens = preg_split( '/[\r\n,]+/', (string) $value );
	if ( ! is_array( $tokens ) ) {
		return array();
	}

	return array_values(
		array_filter(
			array_map(
				static function ( $token ) {
					return trim( (string) $token );
				},
				$tokens
			),
			static function ( $token ) {
				return '' !== $token;
			}
		)
	);
}

function bhdt_wirecutter_in_article_ad_matches_post( $widget, $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( $post_id <= 0 ) {
		return false;
	}

	$slug_targets = bhdt_wirecutter_ad_tokenize_targets( $widget['target_slugs'] ?? '' );
	if ( empty( $slug_targets ) ) {
		return true;
	}

	$post_title = get_the_title( $post_id );
	$post_title_slug = sanitize_title( $post_title );
	$post_slug = sanitize_title( get_post_field( 'post_name', $post_id ) );

	foreach ( $slug_targets as $target ) {
		$target_slug = sanitize_title( $target );
		if ( '' !== $target_slug && in_array( $target_slug, array( $post_slug, $post_title_slug ), true ) ) {
			return true;
		}
	}

	return false;
}

function bhdt_wirecutter_insert_html_after_paragraph( $content, $insertions ) {
	if ( '' === trim( (string) $content ) || empty( $insertions ) ) {
		return $content;
	}

	ksort( $insertions, SORT_NUMERIC );
	$paragraphs = preg_split( '/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! is_array( $paragraphs ) || count( $paragraphs ) < 2 ) {
		return $content . implode( '', $insertions );
	}

	$output = '';
	$paragraph_count = 0;
	for ( $index = 0; $index < count( $paragraphs ); $index++ ) {
		$output .= $paragraphs[ $index ];
		if ( isset( $paragraphs[ $index + 1 ] ) && preg_match( '/^<\/p>$/i', $paragraphs[ $index + 1 ] ) ) {
			$output .= $paragraphs[ $index + 1 ];
			$index++;
			$paragraph_count++;
			if ( isset( $insertions[ $paragraph_count ] ) ) {
				$output .= implode( '', (array) $insertions[ $paragraph_count ] );
			}
		}
	}

	return $output;
}

function bhdt_wirecutter_heading_matches_article_ad_filter( $heading_html, $heading_filter ) {
	if ( 'indexed_upper' !== $heading_filter ) {
		return true;
	}

	$heading_text = trim( wp_strip_all_tags( $heading_html ) );
	return 1 === preg_match( '/^(?:\d+[\.\)]\s*|[A-Z\p{Lu}][\.\)]\s+)/u', $heading_text );
}

function bhdt_wirecutter_insert_html_around_heading( $content, $insertions, $position = 'before' ) {
	if ( '' === trim( (string) $content ) || empty( $insertions ) ) {
		return $content;
	}

	$parts = preg_split( '/(<h[2-4][^>]*>.*?<\/h[2-4]>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! is_array( $parts ) || count( $parts ) < 2 ) {
		return $content;
	}

	$output = '';
	$heading_counts = array_fill( 0, count( $insertions ), 0 );
	foreach ( $parts as $part ) {
		if ( ! preg_match( '/^<h[2-4][^>]*>.*?<\/h[2-4]>$/is', $part ) ) {
			$output .= $part;
			continue;
		}

		$matched_indexes = array();
		foreach ( $insertions as $insertion_index => $insertion ) {
			if ( bhdt_wirecutter_heading_matches_article_ad_filter( $part, $insertion['filter'] ?? 'all' ) ) {
				$matched_indexes[] = $insertion_index;
				if ( 'before' === $position && (int) $insertion['index'] === $heading_counts[ $insertion_index ] + 1 ) {
					$output .= $insertion['html'];
				}
			}
		}

		$output .= $part;

		foreach ( $matched_indexes as $insertion_index ) {
			$heading_counts[ $insertion_index ]++;
			$insertion = $insertions[ $insertion_index ];
			if ( 'after' === $position && (int) $insertion['index'] === $heading_counts[ $insertion_index ] ) {
				$output .= $insertion['html'];
			}
		}
	}

	return $output;
}

function bhdt_wirecutter_append_in_article_ads_to_content( $content ) {
	if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post_type = get_post_type();
	$post_id = get_the_ID();
	$raw_post_content = $post_id ? (string) get_post_field( 'post_content', $post_id ) : '';
	$paragraph_insertions = array();
	$before_heading_insertions = array();
	$after_heading_insertions = array();
	foreach ( bhdt_wirecutter_get_in_article_ad_widgets() as $widget ) {
		if ( 1 !== (int) $widget['enabled'] || 1 !== (int) $widget['auto_insert'] || ! bhdt_wirecutter_ad_widget_has_content( $widget ) ) {
			continue;
		}
		if ( ! in_array( $post_type, (array) $widget['post_types'], true ) ) {
			continue;
		}
		if ( ! bhdt_wirecutter_in_article_ad_matches_post( $widget ) ) {
			continue;
		}
		if ( false !== strpos( $raw_post_content, '[bhdt_article_ad' ) && preg_match( '/\[bhdt_article_ad[^\]]*\bid=["\']?' . preg_quote( $widget['id'], '/' ) . '["\']?[^\]]*\]/i', $raw_post_content ) ) {
			continue;
		}

		$ad_html = bhdt_wirecutter_render_in_article_ad_widget( $widget, 'auto' );
		if ( 'before_heading' === $widget['insertion_mode'] ) {
			$before_heading_insertions[] = array(
				'index'  => max( 1, (int) $widget['heading_index'] ),
				'filter' => $widget['heading_filter'],
				'html'   => $ad_html,
			);
			continue;
		}
		if ( 'after_heading' === $widget['insertion_mode'] ) {
			$after_heading_insertions[] = array(
				'index'  => max( 1, (int) $widget['heading_index'] ),
				'filter' => $widget['heading_filter'],
				'html'   => $ad_html,
			);
			continue;
		}

		$paragraph = max( 1, (int) $widget['after_paragraph'] );
		if ( ! isset( $paragraph_insertions[ $paragraph ] ) ) {
			$paragraph_insertions[ $paragraph ] = array();
		}
		$paragraph_insertions[ $paragraph ][] = $ad_html;
	}

	$content = bhdt_wirecutter_insert_html_around_heading( $content, $before_heading_insertions, 'before' );
	$content = bhdt_wirecutter_insert_html_around_heading( $content, $after_heading_insertions, 'after' );
	return bhdt_wirecutter_insert_html_after_paragraph( $content, $paragraph_insertions );
}
add_filter( 'the_content', 'bhdt_wirecutter_append_in_article_ads_to_content', 18 );

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
		$term = bhdt_wirecutter_find_existing_category_for_label( $label, $slug );
		if ( ! $term || is_wp_error( $term ) ) {
			$term_result = wp_insert_term( $label, 'category', array( 'slug' => $slug ) );
			if ( ! is_wp_error( $term_result ) && ! empty( $term_result['term_id'] ) ) {
				$term = get_term( (int) $term_result['term_id'], 'category' );
			}
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

function bhdt_wirecutter_render_home_post_list( $posts, $group, $show_when_empty = false ) {
	$posts = array_slice( array_values( (array) $posts ), 0, 3 );
	if ( empty( $posts ) && ! $show_when_empty ) {
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
		$list_posts = array_slice( $posts, 2, 3 );
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
			<?php bhdt_wirecutter_render_home_post_list( $list_posts, $group, true ); ?>
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

	add_menu_page(
		'Nhân Viên Web',
		'Nhân Viên Web',
		'manage_options',
		'bhdt-web-staff',
		'bhdt_wirecutter_render_web_staff_tools_admin_page',
		'dashicons-admin-tools',
		59
	);

	add_submenu_page(
		'bhdt-web-staff',
		'Đồng bộ Categories',
		'Đồng bộ Categories',
		'manage_options',
		'bhdt-category-sync',
		'bhdt_wirecutter_render_category_sync_admin_page'
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

	add_theme_page(
		'Daily Deals',
		'Daily Deals',
		'manage_options',
		'bhdt-daily-deals',
		'bhdt_wirecutter_render_daily_deals_admin_page'
	);
}
add_action( 'admin_menu', 'bhdt_wirecutter_admin_home_blocks_page' );

function bhdt_wirecutter_render_web_staff_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập.', 'bhdt-wirecutter' ) );
	}
	?>
	<div class="wrap">
		<h1>Nhân Viên Web</h1>
		<p>Khu vực gom các công cụ dọn dẹp, đồng bộ và trông coi website.</p>
		<?php $pinout_edit_slug = isset( $_GET['pinout_edit'] ) ? sanitize_title( wp_unslash( $_GET['pinout_edit'] ) ) : ''; ?>
		<?php $pinout_edit_item = ( $pinout_edit_slug && isset( $pinout_library[ $pinout_edit_slug ] ) ) ? $pinout_library[ $pinout_edit_slug ] : null; ?>
		<div class="card" style="max-width:1080px;">
			<h2>Đang có</h2>
			<ul>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=bhdt-category-sync' ) ); ?>">Đồng bộ Categories</a></li>
			</ul>
		</div>
	</div>
	<?php
}

function bhdt_wirecutter_parse_traffic_user_agent( $user_agent ) {
	$user_agent = (string) $user_agent;
	$device = 'Desktop';
	if ( preg_match( '/bot|crawl|spider|slurp|facebookexternalhit|telegrambot|discordbot/i', $user_agent ) ) {
		$device = 'Bot';
	} elseif ( preg_match( '/mobile|iphone|android.+mobile/i', $user_agent ) ) {
		$device = 'Mobile';
	} elseif ( preg_match( '/ipad|tablet|android/i', $user_agent ) ) {
		$device = 'Tablet';
	}

	$os = 'Unknown';
	if ( false !== stripos( $user_agent, 'Windows NT' ) ) {
		$os = 'Windows';
	} elseif ( false !== stripos( $user_agent, 'Android' ) ) {
		$os = 'Android';
	} elseif ( preg_match( '/iPhone|iPad|iPod/i', $user_agent ) ) {
		$os = 'iOS';
	} elseif ( false !== stripos( $user_agent, 'Mac OS X' ) ) {
		$os = 'macOS';
	} elseif ( false !== stripos( $user_agent, 'Linux' ) ) {
		$os = 'Linux';
	}

	$browser = 'Unknown';
	if ( preg_match( '/Edg\/([0-9.]+)/i', $user_agent, $match ) ) {
		$browser = 'Edge ' . $match[1];
	} elseif ( preg_match( '/Chrome\/([0-9.]+)/i', $user_agent, $match ) ) {
		$browser = 'Chrome ' . $match[1];
	} elseif ( preg_match( '/Firefox\/([0-9.]+)/i', $user_agent, $match ) ) {
		$browser = 'Firefox ' . $match[1];
	} elseif ( preg_match( '/Version\/([0-9.]+).*Safari/i', $user_agent, $match ) ) {
		$browser = 'Safari ' . $match[1];
	} elseif ( preg_match( '/bot|crawl|spider|slurp/i', $user_agent ) ) {
		$browser = 'Crawler';
	}

	return array(
		'device'  => $device,
		'os'      => $os,
		'browser' => $browser,
	);
}

function bhdt_wirecutter_render_web_staff_tools_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập.', 'bhdt-wirecutter' ) );
	}
	?>
	<div class="wrap">
		<h1>Nhân Viên Web</h1>
		<p>Khu vực gom các công cụ dọn dẹp, đồng bộ và trông coi website.</p>
		<?php $site_report = bhdt_wirecutter_site_check_report(); ?>
		<?php $staff_tab = isset( $_GET['staff_tab'] ) ? sanitize_key( wp_unslash( $_GET['staff_tab'] ) ) : 'tools'; ?>
		<?php $staff_tab = in_array( $staff_tab, array( 'tools', 'traffic', 'spam', 'language', 'seo', 'steward' ), true ) ? $staff_tab : 'tools'; ?>
		<?php $contact_unread_badge = function_exists( 'bhdt_wirecutter_count_unread_contact_submissions' ) ? bhdt_wirecutter_count_unread_contact_submissions() : 0; ?>
		<nav class="nav-tab-wrapper" style="margin-bottom:16px;">
			<a class="nav-tab <?php echo 'tools' === $staff_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=bhdt-web-staff' ) ); ?>">Công cụ</a>
			<a class="nav-tab <?php echo 'traffic' === $staff_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'bhdt-web-staff', 'staff_tab' => 'traffic' ), admin_url( 'admin.php' ) ) ); ?>">Online & traffic</a>
			<a class="nav-tab <?php echo 'spam' === $staff_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'bhdt-web-staff', 'staff_tab' => 'spam' ), admin_url( 'admin.php' ) ) ); ?>">Spam blacklist</a>
			<a class="nav-tab <?php echo 'language' === $staff_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'bhdt-web-staff', 'staff_tab' => 'language' ), admin_url( 'admin.php' ) ) ); ?>">VI/EN checker</a>
			<a class="nav-tab <?php echo 'seo' === $staff_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'bhdt-web-staff', 'staff_tab' => 'seo' ), admin_url( 'admin.php' ) ) ); ?>">SEO</a>
			<a class="nav-tab <?php echo 'steward' === $staff_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'bhdt-web-staff', 'staff_tab' => 'steward' ), admin_url( 'admin.php' ) ) ); ?>">Quản Gia Web<?php if ( $contact_unread_badge > 0 ) : ?> <span style="display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:20px;margin-left:6px;padding:0 7px;border-radius:999px;background:#d63638;color:#fff;font-size:12px;font-weight:800;line-height:1;">(<?php echo esc_html( number_format_i18n( $contact_unread_badge ) ); ?>)</span><?php endif; ?></a>
		</nav>
		<?php if ( isset( $_GET['auto_tags_scanned'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					echo esc_html(
						sprintf(
							'Đã quét %1$d bài publish, cập nhật tags cho %2$d bài, thêm %3$d keyword.',
							(int) $_GET['auto_tags_scanned'],
							(int) ( $_GET['auto_tags_updated'] ?? 0 ),
							(int) ( $_GET['auto_tags_added'] ?? 0 )
						)
					);
					?>
				</p>
			</div>
		<?php endif; ?>
		<?php if ( isset( $_GET['auto_cat_scanned'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					echo esc_html(
						sprintf(
							'Đã quét chuyên mục cho %1$d mục, cập nhật %2$d mục, sửa nhãn cho %3$d chuyên mục. Thiếu term đích: %4$d.',
							(int) $_GET['auto_cat_scanned'],
							(int) ( $_GET['auto_cat_updated'] ?? 0 ),
							(int) ( $_GET['auto_cat_label_fixed'] ?? 0 ),
							(int) ( $_GET['auto_cat_missing_terms'] ?? 0 )
						)
					);
					?>
				</p>
			</div>
		<?php endif; ?>
		<?php if ( isset( $_GET['cleanup_deleted'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					echo esc_html(
						sprintf(
							'Cleanup deleted %1$d scanned runtime items. Failed: %2$d.',
							(int) $_GET['cleanup_deleted'],
							(int) ( $_GET['cleanup_failed'] ?? 0 )
						)
					);
					?>
				</p>
			</div>
		<?php endif; ?>
		<?php if ( isset( $_GET['mid_ad_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Đã lưu widget quảng cáo giữa trang.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['article_ad_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Đã lưu widget quảng cáo trong bài viết.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['spam_blacklist_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Đã lưu Spam blacklist.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['language_fixes_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>VI/EN checker đã áp dụng <?php echo esc_html( number_format_i18n( (int) $_GET['language_fixes_saved'] ) ); ?> chỉnh sửa đã chọn.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['seo_fixes_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>SEO checker đã áp dụng <?php echo esc_html( number_format_i18n( (int) $_GET['seo_fixes_saved'] ) ); ?> chỉnh sửa đã chọn.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['contact_updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Đã cập nhật <?php echo esc_html( number_format_i18n( (int) $_GET['contact_updated'] ) ); ?> nội dung liên hệ.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['steward_page_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Đã lưu nội dung trang Quản Gia Web.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['steward_effects_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Đã lưu cấu hình hiệu ứng giao diện.</p></div>
		<?php endif; ?>
		<?php if ( 'steward' === $staff_tab ) : ?>
			<?php
			$steward_section = isset( $_GET['steward_section'] ) ? sanitize_key( wp_unslash( $_GET['steward_section'] ) ) : 'contact';
			$steward_sections = array(
				'contact' => 'Liên hệ',
				'effects' => 'Hiệu ứng',
				'shortcodes' => 'Shortcode',
				'about'   => 'Giới thiệu',
				'privacy' => 'Chính sách bảo mật',
				'terms'   => 'Điều khoản sử dụng',
			);
			$steward_section = isset( $steward_sections[ $steward_section ] ) ? $steward_section : 'contact';
			?>
			<h2 class="nav-tab-wrapper" style="margin:0 0 16px;">
				<?php foreach ( $steward_sections as $section_key => $section_label ) : ?>
					<a class="nav-tab <?php echo $section_key === $steward_section ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'bhdt-web-staff', 'staff_tab' => 'steward', 'steward_section' => $section_key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $section_label ); ?></a>
				<?php endforeach; ?>
			</h2>
			<?php if ( 'shortcodes' === $steward_section ) : ?>
				<div class="card" style="max-width:1220px;">
					<h2>Shortcode</h2>
					<p>Tổng hợp các shortcode nội bộ đang dùng để chèn nhanh trong bài viết, trang hoặc block Shortcode.</p>
					<div style="max-width:100%;overflow-x:auto;border:1px solid #c3c4c7;border-radius:8px;background:#fff;">
						<table class="widefat striped" style="min-width:980px;border:0;">
							<thead>
								<tr>
									<th style="width:260px;">Shortcode</th>
									<th>Chức năng</th>
									<th style="width:330px;">Ví dụ</th>
									<th>Ghi chú</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( bhdt_wirecutter_registered_shortcode_docs() as $shortcode_doc ) : ?>
									<tr>
										<td><input type="text" class="regular-text code" readonly onclick="this.select();" value="<?php echo esc_attr( $shortcode_doc['shortcode'] ); ?>"></td>
										<td><?php echo esc_html( $shortcode_doc['function'] ); ?></td>
										<td><code><?php echo esc_html( $shortcode_doc['example'] ); ?></code></td>
										<td><?php echo esc_html( $shortcode_doc['note'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<p class="description" style="margin-top:12px;">Mẹo: trong Gutenberg, thêm block Shortcode rồi dán dòng cần dùng. Ô shortcode ở cột đầu có thể click để chọn nhanh.</p>
				</div>
			<?php elseif ( 'effects' === $steward_section ) : ?>
				<div class="card" style="max-width:820px;">
					<h2>Hiệu ứng giao diện</h2>
					<p>Bật hoặc tắt các hiệu ứng trang trí nhỏ trên website. Các hiệu ứng này không ảnh hưởng nội dung bài viết.</p>
					<form method="post">
						<?php wp_nonce_field( 'bhdt_steward_effects_nonce' ); ?>
						<input type="hidden" name="bhdt_steward_effects_save" value="1">
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">Menu Phòng Chat</th>
									<td>
										<label>
											<input type="checkbox" name="menu_ant_enabled" value="1" <?php checked( bhdt_wirecutter_menu_ant_enabled() ); ?>>
											Bật hiệu ứng con kiến bò vào nút “Vào Phòng Chat”
										</label>
										<p class="description">Khi bật, hiệu ứng chỉ chạy trên desktop và tự tắt nếu trình duyệt bật chế độ giảm chuyển động.</p>
									</td>
								</tr>
							</tbody>
						</table>
						<?php submit_button( 'Lưu hiệu ứng' ); ?>
					</form>
				</div>
			<?php elseif ( 'contact' === $steward_section ) : ?>
				<?php $contact_items = bhdt_wirecutter_get_contact_submissions(); ?>
				<?php $contact_unread = count( array_filter( $contact_items, static function ( $item ) { return 'unread' === ( $item['status'] ?? '' ); } ) ); ?>
				<div class="card" style="max-width:1500px;">
					<h2>Liên hệ</h2>
					<p>Danh sách nội dung khách gửi từ form liên hệ ngoài website.</p>
					<div style="display:grid;grid-template-columns:repeat(3,minmax(150px,1fr));gap:10px;margin:12px 0;">
						<div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#f8fafc;">
							<strong style="display:block;font-size:24px;color:#135e96;"><?php echo esc_html( number_format_i18n( count( $contact_items ) ) ); ?></strong>
							<span>Tổng liên hệ</span>
						</div>
						<div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#fff7ed;">
							<strong style="display:block;font-size:24px;color:#b45309;"><?php echo esc_html( number_format_i18n( $contact_unread ) ); ?></strong>
							<span>Chưa đọc</span>
						</div>
						<div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#f0fdf4;">
							<strong style="display:block;font-size:24px;color:#15803d;"><?php echo esc_html( number_format_i18n( max( 0, count( $contact_items ) - $contact_unread ) ) ); ?></strong>
							<span>Đã xử lý</span>
						</div>
					</div>
					<?php if ( empty( $contact_items ) ) : ?>
						<p><strong>Chưa có liên hệ.</strong> Khi khách gửi form ở trang Liên hệ, nội dung sẽ hiện tại đây.</p>
					<?php else : ?>
						<form method="post">
							<?php wp_nonce_field( 'bhdt_contact_admin_nonce' ); ?>
							<p style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:12px 0;">
								<select name="contact_bulk_action">
									<option value="read">Đánh dấu đã xử lý</option>
									<option value="unread">Đánh dấu chưa đọc</option>
									<option value="delete">Xóa</option>
								</select>
								<button type="submit" class="button button-primary" name="bhdt_contact_bulk_update" value="1">Áp dụng</button>
								<label><input type="checkbox" id="bhdt-contact-check-all"> Chọn tất cả</label>
							</p>
							<div style="max-width:100%;overflow-x:auto;border:1px solid #c3c4c7;border-radius:8px;background:#fff;">
								<table class="widefat striped" style="min-width:1420px;border:0;">
									<thead>
										<tr>
											<th style="width:44px;"></th>
											<th>Trạng thái</th>
											<th>Thời gian</th>
											<th>Khách</th>
											<th>Liên hệ</th>
											<th>Chủ đề</th>
											<th style="width:520px;">Nội dung</th>
											<th>IP</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( array_slice( $contact_items, 0, 200 ) as $item ) : ?>
											<tr>
												<td><input type="checkbox" class="bhdt-contact-row-check" name="contact_ids[]" value="<?php echo esc_attr( $item['id'] ?? '' ); ?>"></td>
												<td><code><?php echo 'unread' === ( $item['status'] ?? '' ) ? 'Chưa đọc' : 'Đã xử lý'; ?></code></td>
												<td><?php echo esc_html( $item['created_at'] ?? '' ); ?></td>
												<td><strong><?php echo esc_html( $item['name'] ?? '' ); ?></strong></td>
												<td>
													<?php if ( ! empty( $item['email'] ) ) : ?>
														<a href="mailto:<?php echo esc_attr( $item['email'] ); ?>"><?php echo esc_html( $item['email'] ); ?></a><br>
													<?php endif; ?>
													<?php echo esc_html( $item['phone'] ?? '' ); ?>
												</td>
												<td><?php echo esc_html( $item['subject'] ?? '' ); ?></td>
												<td style="min-width:520px;max-width:720px;white-space:pre-wrap;line-height:1.55;"><?php echo esc_html( $item['message'] ?? '' ); ?></td>
												<td><code><?php echo esc_html( $item['ip'] ?? '' ); ?></code></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</form>
						<script>
						document.addEventListener('change', function (event) {
							if (!event.target || event.target.id !== 'bhdt-contact-check-all') {
								return;
							}
							document.querySelectorAll('.bhdt-contact-row-check').forEach(function (checkbox) {
								checkbox.checked = event.target.checked;
							});
						});
						</script>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<?php
				$page_slug_map = array(
					'about'   => 'gioi-thieu',
					'privacy' => 'chinh-sach-bao-mat',
					'terms'   => 'dieu-khoan-su-dung',
				);
				$managed_slug = $page_slug_map[ $steward_section ] ?? 'gioi-thieu';
				$managed_page = bhdt_wirecutter_get_required_footer_page( $managed_slug );
				$managed_content = $managed_page instanceof WP_Post ? bhdt_wirecutter_clean_steward_page_content( $managed_page->post_content ) : '';
				if ( $managed_page instanceof WP_Post && $managed_content !== (string) $managed_page->post_content && current_user_can( 'edit_post', $managed_page->ID ) ) {
					wp_update_post(
						array(
							'ID'           => (int) $managed_page->ID,
							'post_content' => wp_kses_post( $managed_content ),
						)
					);
					$managed_page = get_post( $managed_page->ID );
				}
				?>
				<div class="card" style="max-width:980px;">
					<h2><?php echo esc_html( $steward_sections[ $steward_section ] ); ?></h2>
					<?php if ( $managed_page instanceof WP_Post ) : ?>
						<p>
							<a class="button button-secondary" href="<?php echo esc_url( get_permalink( $managed_page ) ); ?>" target="_blank" rel="noopener noreferrer">Xem ngoài web</a>
							<a class="button button-secondary" href="<?php echo esc_url( get_edit_post_link( $managed_page->ID, '' ) ); ?>">Mở trình sửa Page</a>
						</p>
						<form method="post">
							<?php wp_nonce_field( 'bhdt_steward_page_nonce' ); ?>
							<input type="hidden" name="bhdt_steward_page_save" value="1">
							<input type="hidden" name="steward_section" value="<?php echo esc_attr( $steward_section ); ?>">
							<input type="hidden" name="page_id" value="<?php echo esc_attr( (string) $managed_page->ID ); ?>">
							<table class="form-table" role="presentation">
								<tbody>
									<tr>
										<th scope="row"><label for="bhdt_steward_page_title">Tiêu đề</label></th>
										<td><input class="regular-text" id="bhdt_steward_page_title" name="page_title" value="<?php echo esc_attr( get_the_title( $managed_page ) ); ?>"></td>
									</tr>
									<tr>
										<th scope="row"><label for="bhdt_steward_page_content">Nội dung</label></th>
										<td>
											<?php
											wp_editor(
												$managed_content,
												'bhdt_steward_page_content',
												array(
													'textarea_name' => 'page_content',
													'textarea_rows' => 18,
													'media_buttons' => true,
													'teeny'         => false,
													'quicktags'     => true,
													'tinymce'       => array(
														'content_style' => 'html, body { background: #ffffff !important; color: #1d2327 !important; } body, p, li, td, th { font-family: "Noto Sans", Arial, sans-serif; font-size: 16px; line-height: 1.7; letter-spacing: 0; color: #1d2327 !important; } h1, h2, h3, h4, h5, h6 { font-family: "Noto Sans", Arial, sans-serif; line-height: 1.25; letter-spacing: 0; color: #1d2327 !important; } a { color: #135e96 !important; }',
													),
												)
											);
											?>
											<script>
											(function () {
												function fixStewardEditorColors() {
													var editor = window.tinymce && window.tinymce.get('bhdt_steward_page_content');
													if (!editor || !editor.getDoc) {
														return;
													}
													var doc = editor.getDoc();
													if (!doc || !doc.head || doc.getElementById('bhdt-steward-editor-color-fix')) {
														return;
													}
													var style = doc.createElement('style');
													style.id = 'bhdt-steward-editor-color-fix';
													style.textContent = 'html,body{background:#fff!important;color:#1d2327!important;} body,body *{color:#1d2327!important;text-shadow:none!important;} body,p,li,td,th{font-family:"Noto Sans",Arial,sans-serif!important;font-size:16px;line-height:1.7;letter-spacing:0;} h1,h2,h3,h4,h5,h6{font-family:"Noto Sans",Arial,sans-serif!important;line-height:1.25;letter-spacing:0;} a{color:#135e96!important;}';
													doc.head.appendChild(style);
												}
												document.addEventListener('tinymce-editor-init', function (event) {
													if (event.detail && event.detail.editor && event.detail.editor.id === 'bhdt_steward_page_content') {
														setTimeout(fixStewardEditorColors, 50);
													}
												});
												setTimeout(fixStewardEditorColors, 300);
												setTimeout(fixStewardEditorColors, 1000);
											})();
											</script>
											<p class="description">Có thể dùng bộ soạn thảo WordPress để thêm heading, link, danh sách, ảnh và định dạng nội dung. Trang Liên hệ dùng form riêng ở tab Liên hệ.</p>
										</td>
									</tr>
								</tbody>
							</table>
							<?php submit_button( 'Lưu nội dung trang', 'primary', 'submit', false ); ?>
						</form>
					<?php else : ?>
						<p>Chưa tìm thấy trang này. Tải lại trang để hệ thống tự tạo trang mặc định.</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
			return;
		endif;
		?>
		<?php if ( 'contact' === $staff_tab ) : ?>
			<?php $contact_items = bhdt_wirecutter_get_contact_submissions(); ?>
			<?php $contact_unread = count( array_filter( $contact_items, static function ( $item ) { return 'unread' === ( $item['status'] ?? '' ); } ) ); ?>
			<div class="card" style="max-width:1220px;">
				<h2>Liên hệ</h2>
				<p>Danh sách nội dung khách gửi từ form liên hệ ngoài website.</p>
				<div style="display:grid;grid-template-columns:repeat(3,minmax(150px,1fr));gap:10px;margin:12px 0;">
					<div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#f8fafc;">
						<strong style="display:block;font-size:24px;color:#135e96;"><?php echo esc_html( number_format_i18n( count( $contact_items ) ) ); ?></strong>
						<span>Tổng liên hệ</span>
					</div>
					<div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#fff7ed;">
						<strong style="display:block;font-size:24px;color:#b45309;"><?php echo esc_html( number_format_i18n( $contact_unread ) ); ?></strong>
						<span>Chưa đọc</span>
					</div>
					<div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#f0fdf4;">
						<strong style="display:block;font-size:24px;color:#15803d;"><?php echo esc_html( number_format_i18n( max( 0, count( $contact_items ) - $contact_unread ) ) ); ?></strong>
						<span>Đã xử lý</span>
					</div>
				</div>
				<?php if ( empty( $contact_items ) ) : ?>
					<p><strong>Chưa có liên hệ.</strong> Khi khách gửi form ở trang Liên hệ, nội dung sẽ hiện tại đây.</p>
				<?php else : ?>
					<form method="post">
						<?php wp_nonce_field( 'bhdt_contact_admin_nonce' ); ?>
						<p style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:12px 0;">
							<select name="contact_bulk_action">
								<option value="read">Đánh dấu đã xử lý</option>
								<option value="unread">Đánh dấu chưa đọc</option>
								<option value="delete">Xóa</option>
							</select>
							<button type="submit" class="button button-primary" name="bhdt_contact_bulk_update" value="1">Áp dụng</button>
							<label><input type="checkbox" id="bhdt-contact-check-all"> Chọn tất cả</label>
						</p>
						<div style="max-width:100%;overflow-x:auto;border:1px solid #c3c4c7;border-radius:8px;background:#fff;">
							<table class="widefat striped" style="min-width:1180px;border:0;">
								<thead>
									<tr>
										<th style="width:44px;"></th>
										<th>Trạng thái</th>
										<th>Thời gian</th>
										<th>Khách</th>
										<th>Liên hệ</th>
										<th>Chủ đề</th>
										<th>Nội dung</th>
										<th>IP</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( array_slice( $contact_items, 0, 200 ) as $item ) : ?>
										<tr>
											<td><input type="checkbox" class="bhdt-contact-row-check" name="contact_ids[]" value="<?php echo esc_attr( $item['id'] ?? '' ); ?>"></td>
											<td><code><?php echo 'unread' === ( $item['status'] ?? '' ) ? 'Chưa đọc' : 'Đã xử lý'; ?></code></td>
											<td><?php echo esc_html( $item['created_at'] ?? '' ); ?></td>
											<td><strong><?php echo esc_html( $item['name'] ?? '' ); ?></strong></td>
											<td>
												<?php if ( ! empty( $item['email'] ) ) : ?>
													<a href="mailto:<?php echo esc_attr( $item['email'] ); ?>"><?php echo esc_html( $item['email'] ); ?></a><br>
												<?php endif; ?>
												<?php echo esc_html( $item['phone'] ?? '' ); ?>
											</td>
											<td><?php echo esc_html( $item['subject'] ?? '' ); ?></td>
											<td style="max-width:420px;"><?php echo nl2br( esc_html( $item['message'] ?? '' ) ); ?></td>
											<td><code><?php echo esc_html( $item['ip'] ?? '' ); ?></code></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</form>
					<script>
					document.addEventListener('change', function (event) {
						if (!event.target || event.target.id !== 'bhdt-contact-check-all') {
							return;
						}
						document.querySelectorAll('.bhdt-contact-row-check').forEach(function (checkbox) {
							checkbox.checked = event.target.checked;
						});
					});
					</script>
				<?php endif; ?>
			</div>
		</div>
		<?php
			return;
		endif;
		?>
		<?php if ( 'seo' === $staff_tab ) : ?>
			<?php $seo_report = bhdt_wirecutter_seo_scan_posts(); ?>
			<?php $seo_stats = $seo_report['stats']; ?>
			<?php $seo_issues = $seo_report['issues']; ?>
			<div class="card" style="max-width:1220px;">
				<h2>SEO checker</h2>
				<p>Quét 120 bài/page đã publish và được cập nhật gần đây: mô tả SEO, độ dài tiêu đề, slug, ảnh đại diện, chuyên mục, tag và nội dung mỏng. Các dòng có ô nhập có thể sửa rồi áp dụng trực tiếp.</p>
				<div style="display:grid;grid-template-columns:repeat(3,minmax(150px,1fr));gap:10px;margin:12px 0;">
					<div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#f8fafc;">
						<strong style="display:block;font-size:24px;color:#135e96;"><?php echo esc_html( number_format_i18n( (int) $seo_stats['checked'] ) ); ?></strong>
						<span>Bài đã kiểm tra</span>
					</div>
					<div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#fff7ed;">
						<strong style="display:block;font-size:24px;color:#b45309;"><?php echo esc_html( number_format_i18n( (int) $seo_stats['issues'] ) ); ?></strong>
						<span>Cảnh báo</span>
					</div>
					<div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#f0fdf4;">
						<strong style="display:block;font-size:24px;color:#15803d;"><?php echo esc_html( number_format_i18n( (int) $seo_stats['fixable'] ) ); ?></strong>
						<span>Có thể sửa nhanh</span>
					</div>
				</div>
				<?php if ( empty( $seo_issues ) ) : ?>
					<p><strong>Tốt rồi.</strong> Chưa thấy cảnh báo SEO nào trong tập đang quét.</p>
				<?php else : ?>
					<form method="post">
						<?php wp_nonce_field( 'bhdt_seo_tool_nonce' ); ?>
						<p style="display:flex;align-items:center;gap:12px;margin:12px 0;">
							<button type="submit" class="button button-primary" name="bhdt_apply_seo_fixes" value="1">Áp dụng các SEO fix đã chọn</button>
							<label><input type="checkbox" id="bhdt-seo-check-all" checked> Chọn tất cả dòng có thể sửa</label>
							<span class="description">Sửa cột gợi ý trước khi áp dụng nếu cần. Các cảnh báo không có ô nhập cần sửa trong bài.</span>
						</p>
						<div style="max-width:100%;overflow-x:auto;border:1px solid #c3c4c7;border-radius:8px;background:#fff;">
							<table class="widefat striped" style="min-width:1240px;border:0;">
								<thead>
									<tr>
										<th style="width:52px;">Áp dụng</th>
										<th>Bài viết</th>
										<th>Loại</th>
										<th>Trường</th>
										<th>Trạng thái</th>
										<th>Hiện tại</th>
										<th style="min-width:320px;">Gợi ý / sửa trước khi áp dụng</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( array_slice( $seo_issues, 0, 250 ) as $issue ) : ?>
										<?php $target = (string) ( $issue['target'] ?? '' ); ?>
										<tr>
											<td>
												<?php if ( '' !== $target && '' !== $issue['suggestion'] ) : ?>
													<input type="checkbox" class="bhdt-seo-row-check" name="seo_fix[<?php echo esc_attr( $target ); ?>][apply]" value="1" checked>
												<?php else : ?>
													<span class="dashicons dashicons-warning" title="Cần sửa thủ công"></span>
												<?php endif; ?>
											</td>
											<td>
												<strong><?php echo esc_html( $issue['title'] ); ?></strong>
												<?php if ( ! empty( $issue['edit_url'] ) ) : ?>
													<br><a href="<?php echo esc_url( $issue['edit_url'] ); ?>">Sửa bài</a>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( $issue['type'] ); ?></td>
											<td><?php echo esc_html( $issue['field'] ); ?></td>
											<td><code><?php echo esc_html( $issue['status'] ); ?></code></td>
											<td style="max-width:320px;"><?php echo '' !== $issue['current'] ? esc_html( wp_trim_words( $issue['current'], 22, '...' ) ) : '<em>Trống</em>'; ?></td>
											<td>
												<?php if ( '' !== $target ) : ?>
													<input type="text" class="regular-text" style="width:100%;max-width:580px;" name="seo_fix[<?php echo esc_attr( $target ); ?>][value]" value="<?php echo esc_attr( $issue['suggestion'] ); ?>" placeholder="Nhập mô tả SEO hoặc slug">
												<?php else : ?>
													<span class="description">Cần sửa trong bài viết.</span>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<p style="margin:12px 0 0;">
							<button type="submit" class="button button-primary" name="bhdt_apply_seo_fixes" value="1">Áp dụng các SEO fix đã chọn</button>
						</p>
					</form>
					<script>
					document.addEventListener('change', function (event) {
						if (!event.target || event.target.id !== 'bhdt-seo-check-all') {
							return;
						}
						document.querySelectorAll('.bhdt-seo-row-check').forEach(function (checkbox) {
							checkbox.checked = event.target.checked;
						});
					});
					</script>
					<?php if ( count( $seo_issues ) > 250 ) : ?>
						<p class="description">Đang hiển thị 250 cảnh báo đầu tiên trong tổng số <?php echo esc_html( number_format_i18n( count( $seo_issues ) ) ); ?> cảnh báo.</p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
			return;
		endif;
		?>
		<?php if ( 'language' === $staff_tab ) : ?>
			<?php $language_report = bhdt_wirecutter_language_tool_scan_rows(); ?>
			<?php $language_stats = $language_report['stats']; ?>
			<?php $language_issues = $language_report['issues']; ?>
			<div class="card" style="max-width:1180px;">
				<h2>VI/EN checker</h2>
				<p>Quét khối trang chủ, từ khóa nóng, Mega menu và Menu chức năng. Tool chỉ tự sửa phần chắc chắn: EN trống, EN copy từ VI, EN còn dấu tiếng Việt, hoặc slug trống.</p>
				<div style="display:grid;grid-template-columns:repeat(3,minmax(150px,1fr));gap:10px;margin:12px 0;">
					<div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#f8fafc;">
						<strong style="display:block;font-size:24px;color:#135e96;"><?php echo esc_html( number_format_i18n( (int) $language_stats['checked'] ) ); ?></strong>
						<span>Trường đã kiểm tra</span>
					</div>
					<div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#fff7ed;">
						<strong style="display:block;font-size:24px;color:#b45309;"><?php echo esc_html( number_format_i18n( (int) $language_stats['needs_fix'] ) ); ?></strong>
						<span>Cần xem lại</span>
					</div>
					<div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#f0fdf4;">
						<strong style="display:block;font-size:24px;color:#15803d;"><?php echo esc_html( number_format_i18n( (int) $language_stats['safe_fixes'] ) ); ?></strong>
						<span>Có thể sửa an toàn</span>
					</div>
				</div>
				<?php if ( empty( $language_issues ) ) : ?>
					<p><strong>Đẹp rồi.</strong> Chưa thấy phần VI/EN nào cần sửa trong các khu vực đang quét.</p>
				<?php else : ?>
					<form method="post">
						<?php wp_nonce_field( 'bhdt_language_tool_nonce' ); ?>
						<p style="display:flex;align-items:center;gap:12px;margin:12px 0;">
							<button type="submit" class="button button-primary" name="bhdt_apply_language_fixes" value="1">Áp dụng các dòng đã chọn</button>
							<label><input type="checkbox" id="bhdt-language-check-all" checked> Chọn tất cả dòng đang hiển thị</label>
							<span class="description">Sửa cột gợi ý nếu chưa sát nghĩa, bỏ tick dòng nào chưa muốn áp dụng.</span>
						</p>
						<div style="max-width:100%;overflow-x:auto;border:1px solid #c3c4c7;border-radius:8px;background:#fff;">
							<table class="widefat striped" style="min-width:1120px;border:0;">
								<thead>
									<tr>
										<th style="width:52px;">Áp dụng</th>
										<th>Khu vực</th>
										<th>Mục</th>
										<th>Trường</th>
										<th>Trạng thái</th>
										<th>Hiện tại</th>
										<th style="min-width:280px;">Gợi ý / sửa trước khi áp dụng</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( array_slice( $language_issues, 0, 200 ) as $issue ) : ?>
										<?php $target = (string) ( $issue['target'] ?? '' ); ?>
										<tr>
											<td>
												<?php if ( '' !== $target && '' !== $issue['suggestion'] ) : ?>
													<input type="checkbox" class="bhdt-language-row-check" name="language_fix[<?php echo esc_attr( $target ); ?>][apply]" value="1" checked>
												<?php else : ?>
													<span class="dashicons dashicons-warning" title="Cần nhập thủ công"></span>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( $issue['area'] ); ?></td>
											<td><?php echo esc_html( $issue['item'] ); ?></td>
											<td><?php echo esc_html( $issue['field'] ); ?></td>
											<td><code><?php echo esc_html( $issue['status'] ); ?></code></td>
											<td><?php echo '' !== $issue['current'] ? esc_html( $issue['current'] ) : '<em>Trống</em>'; ?></td>
											<td>
												<?php if ( '' !== $target ) : ?>
													<input type="text" class="regular-text" style="width:100%;max-width:520px;" name="language_fix[<?php echo esc_attr( $target ); ?>][value]" value="<?php echo esc_attr( $issue['suggestion'] ); ?>" placeholder="Nhập bản dịch EN hoặc slug">
												<?php else : ?>
													<strong><?php echo '' !== $issue['suggestion'] ? esc_html( $issue['suggestion'] ) : 'Cần nhập thủ công'; ?></strong>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<p style="margin:12px 0 0;">
							<button type="submit" class="button button-primary" name="bhdt_apply_language_fixes" value="1">Áp dụng các dòng đã chọn</button>
						</p>
					</form>
					<script>
					document.addEventListener('change', function (event) {
						if (!event.target || event.target.id !== 'bhdt-language-check-all') {
							return;
						}
						document.querySelectorAll('.bhdt-language-row-check').forEach(function (checkbox) {
							checkbox.checked = event.target.checked;
						});
					});
					</script>
					<?php if ( count( $language_issues ) > 200 ) : ?>
						<p class="description">Đang hiển thị 200 dòng đầu tiên trong tổng số <?php echo esc_html( number_format_i18n( count( $language_issues ) ) ); ?> vấn đề.</p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
			return;
		endif;
		?>
		<?php if ( 'spam' === $staff_tab ) : ?>
			<?php $spam_blacklist = bhdt_wirecutter_get_spam_blacklist(); ?>
			<div class="card" style="max-width:920px;">
				<h2>Spam blacklist</h2>
				<p>Nhập mỗi IP, email/domain hoặc keyword một dòng. Comment khớp danh sách này sẽ bị chặn hoặc đưa vào spam.</p>
				<form method="post">
					<?php wp_nonce_field( 'bhdt_spam_blacklist_nonce' ); ?>
					<input type="hidden" name="bhdt_save_spam_blacklist" value="1">
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><label for="bhdt_spam_blacklist_ips">IP blacklist</label></th>
								<td>
									<textarea class="large-text code" id="bhdt_spam_blacklist_ips" name="spam_blacklist_ips" rows="7" placeholder="185.246.188.74&#10;185.246.*"><?php echo esc_textarea( bhdt_wirecutter_spam_blacklist_to_text( $spam_blacklist['ips'] ) ); ?></textarea>
									<p class="description">Có thể nhập IP đầy đủ hoặc prefix dạng <code>185.246.*</code>.</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="bhdt_spam_blacklist_emails">Email/domain blacklist</label></th>
								<td>
									<textarea class="large-text code" id="bhdt_spam_blacklist_emails" name="spam_blacklist_emails" rows="7" placeholder="spammer@example.com&#10;@spam-domain.com"><?php echo esc_textarea( bhdt_wirecutter_spam_blacklist_to_text( $spam_blacklist['emails'] ) ); ?></textarea>
									<p class="description">Nhập email đầy đủ hoặc domain dạng <code>@example.com</code>.</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="bhdt_spam_blacklist_keywords">Keyword blacklist</label></th>
								<td>
									<textarea class="large-text code" id="bhdt_spam_blacklist_keywords" name="spam_blacklist_keywords" rows="8" placeholder="casino&#10;crypto&#10;telegram"><?php echo esc_textarea( bhdt_wirecutter_spam_blacklist_to_text( $spam_blacklist['keywords'] ) ); ?></textarea>
									<p class="description">Khớp trong tên, email, URL hoặc nội dung comment.</p>
								</td>
							</tr>
						</tbody>
					</table>
					<?php submit_button( 'Lưu Spam blacklist', 'primary', 'submit', false ); ?>
				</form>
			</div>
		</div>
		<?php
			return;
		endif;
		?>
		<?php if ( 'traffic' === $staff_tab ) : ?>
		<div class="card" style="max-width:1080px;">
			<h2>Online & traffic</h2>
			<?php if ( class_exists( 'Zenclau_V1_Content_System' ) && method_exists( 'Zenclau_V1_Content_System', 'get_traffic_summary' ) ) : ?>
				<?php $traffic_summary = Zenclau_V1_Content_System::get_traffic_summary(); ?>
				<p class="description">Cập nhật: <?php echo esc_html( $traffic_summary['generated_at'] ?? current_time( 'mysql' ) ); ?>. Online tính trong <?php echo esc_html( (string) round( (int) ( $traffic_summary['online_window'] ?? 300 ) / 60 ) ); ?> phút gần nhất.</p>
				<div style="display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:10px;margin:12px 0;">
					<?php
					$traffic_cards = array(
						'Người online'     => $traffic_summary['human_online'] ?? 0,
						'Bot online'       => $traffic_summary['bot_online'] ?? 0,
						'View hôm nay'     => $traffic_summary['views_today'] ?? 0,
						'View 7 ngày'      => $traffic_summary['views_week'] ?? 0,
						'View 30 ngày'     => $traffic_summary['views_month'] ?? 0,
						'Unique hôm nay'   => $traffic_summary['unique_today'] ?? 0,
						'Bot view hôm nay' => $traffic_summary['bot_views_today'] ?? 0,
						'Tổng view lưu'    => $traffic_summary['all_views'] ?? 0,
					);
					?>
					<?php foreach ( $traffic_cards as $traffic_label => $traffic_value ) : ?>
						<div style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#f8fafc;">
							<strong style="display:block;font-size:24px;color:#135e96;"><?php echo esc_html( number_format_i18n( (int) $traffic_value ) ); ?></strong>
							<span><?php echo esc_html( $traffic_label ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
				<h3>Đang online</h3>
				<?php $traffic_sessions = is_array( $traffic_summary['sessions'] ?? null ) ? $traffic_summary['sessions'] : array(); ?>
				<?php if ( empty( $traffic_sessions ) ) : ?>
					<p>Chưa có visitor online trong vài phút gần nhất. Mở frontend bằng trình duyệt khác rồi refresh trang này để test.</p>
				<?php else : ?>
					<div style="max-width:100%;overflow-x:auto;border:1px solid #c3c4c7;border-radius:8px;background:#fff;">
					<table class="widefat striped" style="min-width:1500px;border:0;">
						<thead>
							<tr>
								<th>IP</th>
								<th>Type</th>
								<th>Country</th>
								<th>Region</th>
								<th>City</th>
								<th>Device</th>
								<th>OS</th>
								<th>Browser</th>
								<th>Language</th>
								<th>URL đang xem</th>
								<th>Views</th>
								<th>Referrer</th>
								<th>First seen</th>
								<th>Last seen</th>
								<th>User agent</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $traffic_sessions as $session ) : ?>
								<?php $agent_info = bhdt_wirecutter_parse_traffic_user_agent( $session['user_agent'] ?? '' ); ?>
								<tr>
									<td><code><?php echo esc_html( $session['ip'] ?? '' ); ?></code></td>
									<td style="white-space:nowrap;"><?php echo ! empty( $session['is_bot'] ) ? 'Bot' : 'Visitor'; ?></td>
									<td style="white-space:nowrap;"><?php echo esc_html( '' !== (string) ( $session['country'] ?? '' ) ? $session['country'] : 'Unknown' ); ?></td>
									<td style="white-space:nowrap;"><?php echo esc_html( '' !== (string) ( $session['region'] ?? '' ) ? $session['region'] : '-' ); ?></td>
									<td style="white-space:nowrap;"><?php echo esc_html( '' !== (string) ( $session['city'] ?? '' ) ? $session['city'] : '-' ); ?></td>
									<td style="white-space:nowrap;"><?php echo esc_html( $agent_info['device'] ); ?></td>
									<td style="white-space:nowrap;"><?php echo esc_html( $agent_info['os'] ); ?></td>
									<td style="white-space:nowrap;"><?php echo esc_html( $agent_info['browser'] ); ?></td>
									<td style="white-space:nowrap;max-width:180px;overflow:hidden;text-overflow:ellipsis;" title="<?php echo esc_attr( $session['language'] ?? '' ); ?>"><?php echo esc_html( '' !== (string) ( $session['language'] ?? '' ) ? $session['language'] : '-' ); ?></td>
									<td style="white-space:nowrap;max-width:260px;overflow:hidden;text-overflow:ellipsis;" title="<?php echo esc_attr( $session['path'] ?? '/' ); ?>"><?php echo esc_html( $session['path'] ?? '/' ); ?></td>
									<td><?php echo esc_html( number_format_i18n( (int) ( $session['views'] ?? 0 ) ) ); ?></td>
									<td style="white-space:nowrap;max-width:260px;overflow:hidden;text-overflow:ellipsis;" title="<?php echo esc_attr( $session['referrer'] ?? '' ); ?>"><?php echo esc_html( '' !== (string) ( $session['referrer'] ?? '' ) ? $session['referrer'] : '-' ); ?></td>
									<td style="white-space:nowrap;"><?php echo esc_html( ! empty( $session['first_seen'] ) ? wp_date( 'Y-m-d H:i:s', (int) $session['first_seen'] ) : '' ); ?></td>
									<td style="white-space:nowrap;"><?php echo esc_html( ! empty( $session['last_seen'] ) ? wp_date( 'Y-m-d H:i:s', (int) $session['last_seen'] ) : '' ); ?></td>
									<td style="white-space:nowrap;max-width:560px;overflow:hidden;text-overflow:ellipsis;" title="<?php echo esc_attr( $session['user_agent'] ?? '' ); ?>"><?php echo esc_html( $session['user_agent'] ?? '' ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					</div>
				<?php endif; ?>
				<h3>Lượt view gần đây</h3>
				<table class="widefat striped">
					<thead>
						<tr>
							<th>Ngày</th>
							<th>Views</th>
							<th>Unique</th>
							<th>Bot views</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( (array) ( $traffic_summary['days'] ?? array() ) as $day ) : ?>
							<tr>
								<td><?php echo esc_html( $day['date'] ?? '' ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) ( $day['views'] ?? 0 ) ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) ( $day['unique'] ?? 0 ) ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) ( $day['bot_views'] ?? 0 ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="notice notice-warning inline"><p>Chưa tải được module traffic của BHDT Core System.</p></div>
			<?php endif; ?>
		</div>
	</div>
	<?php
			return;
		endif;
		?>
		<div class="card" style="max-width:920px;">
			<h2>Site check</h2>
			<p>Quick deploy checklist for content, config and local runtime files.</p>
			<?php if ( empty( $site_report['issues'] ) ) : ?>
				<div class="notice notice-success inline"><p>No obvious deploy blockers found.</p></div>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th style="width:110px;">Level</th>
							<th>Issue</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $site_report['issues'] as $issue ) : ?>
							<tr>
								<td><strong><?php echo esc_html( ucfirst( $issue['level'] ?? 'notice' ) ); ?></strong></td>
								<td><?php echo esc_html( $issue['text'] ?? '' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<p><strong>Info:</strong> <?php echo esc_html( implode( ' | ', (array) $site_report['info'] ) ); ?></p>
			<form method="post">
				<?php wp_nonce_field( 'bhdt_site_check_nonce' ); ?>
				<input type="hidden" name="bhdt_site_check" value="1">
				<?php submit_button( 'Refresh site check', 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<div class="card" style="max-width:720px;">
			<h2>Đang có</h2>
			<ul>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=bhdt-category-sync' ) ); ?>">Đồng bộ Categories</a></li>
			</ul>
		</div>
		<div class="card" style="max-width:720px;">
			<h2>Tự gắn keyword tags</h2>
			<p>Quét các bài đã publish thuộc Post, Review và So sánh. Bài nào chưa có tags hoặc có ít hơn 3 tags sẽ được tự thêm keyword dựa theo tiêu đề.</p>
			<form method="post">
				<?php wp_nonce_field( 'bhdt_scan_auto_tags_nonce' ); ?>
				<input type="hidden" name="bhdt_scan_auto_tags" value="1">
				<?php submit_button( 'Quét và gắn tags cho bài thiếu keyword', 'primary', 'submit', false ); ?>
			</form>
		</div>
		<div class="card" style="max-width:720px;">
			<h2>Tự gắn chuyên mục</h2>
			<p>Tự gắn chuyên mục phù hợp hơn cho Post/Review/So sánh/Dự án đã publish nhưng chưa có chuyên mục, đồng thời sửa một số lỗi tên chuyên mục đã biết.</p>
			<form method="post">
				<?php wp_nonce_field( 'bhdt_auto_categories_nonce' ); ?>
				<input type="hidden" name="bhdt_auto_categories" value="1">
				<?php submit_button( 'Tự gắn chuyên mục còn thiếu', 'primary', 'submit', false ); ?>
			</form>
			<p class="description">Các chuyên mục đã gắn thủ công sẽ được giữ nguyên.</p>
		</div>
		<?php
		$mid_ad_widgets = bhdt_wirecutter_get_mid_page_ad_widgets();
		$mid_ad_widgets[] = array(
			'id'           => '',
			'title'        => '',
			'enabled'      => 0,
			'position'     => 'before_category',
			'target_block' => '',
			'affiliate_url' => '',
			'preview_image_url' => '',
			'auto_preview' => 0,
			'cta_label'    => '',
			'html'         => '',
		);
		$mid_ad_choices = bhdt_wirecutter_get_home_category_block_choices();
		?>
		<div class="card" style="max-width:920px;">
			<h2>Widget quảng cáo giữa trang</h2>
			<p>Chèn nhiều banner, HTML hoặc đoạn chữ chạy vào giữa các khối danh mục trang chủ. Điền khung trống cuối cùng để tạo widget mới.</p>
			<form method="post">
				<?php wp_nonce_field( 'bhdt_mid_page_ad_widget_nonce' ); ?>
				<input type="hidden" name="bhdt_save_mid_page_ad_widget" value="1">
				<?php foreach ( $mid_ad_widgets as $mid_ad_index => $mid_ad_widget ) : ?>
					<?php $mid_ad_open = empty( $_GET['mid_ad_saved'] ) && empty( $mid_ad_widget['id'] ); ?>
					<details <?php echo $mid_ad_open ? 'open' : ''; ?> style="border:1px solid #dcdcde;border-radius:8px;padding:12px;margin:0 0 12px;background:#fff;">
						<summary style="cursor:pointer;font-weight:700;">
							<?php
							echo esc_html(
								'' !== (string) ( $mid_ad_widget['title'] ?? '' )
									? $mid_ad_widget['title']
									: ( '' !== (string) ( $mid_ad_widget['id'] ?? '' ) ? 'Widget quảng cáo' : 'Thêm widget quảng cáo mới' )
							);
							?>
						</summary>
						<input type="hidden" name="mid_ad_widgets[<?php echo esc_attr( (string) $mid_ad_index ); ?>][id]" value="<?php echo esc_attr( $mid_ad_widget['id'] ?? '' ); ?>">
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">Bật widget</th>
									<td>
										<label>
											<input type="checkbox" name="mid_ad_widgets[<?php echo esc_attr( (string) $mid_ad_index ); ?>][enabled]" value="1" <?php checked( 1, (int) ( $mid_ad_widget['enabled'] ?? 0 ) ); ?>>
											Hiển thị widget này trên trang chủ
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="mid_ad_title_<?php echo esc_attr( (string) $mid_ad_index ); ?>">Tên gợi nhớ</label></th>
									<td><input class="regular-text" id="mid_ad_title_<?php echo esc_attr( (string) $mid_ad_index ); ?>" name="mid_ad_widgets[<?php echo esc_attr( (string) $mid_ad_index ); ?>][title]" value="<?php echo esc_attr( $mid_ad_widget['title'] ?? '' ); ?>" placeholder="Banner PCBA, Banner footer..."></td>
								</tr>
								<tr>
									<th scope="row"><label for="mid_ad_position_<?php echo esc_attr( (string) $mid_ad_index ); ?>">Vị trí</label></th>
									<td>
										<select id="mid_ad_position_<?php echo esc_attr( (string) $mid_ad_index ); ?>" name="mid_ad_widgets[<?php echo esc_attr( (string) $mid_ad_index ); ?>][position]">
											<option value="before_category" <?php selected( $mid_ad_widget['position'] ?? '', 'before_category' ); ?>>Nằm phía trên một khối danh mục</option>
											<option value="site_end" <?php selected( $mid_ad_widget['position'] ?? '', 'site_end' ); ?>>Nằm phía cuối cột nội dung trang chủ</option>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="mid_ad_target_block_<?php echo esc_attr( (string) $mid_ad_index ); ?>">Khối danh mục mục tiêu</label></th>
									<td>
										<select id="mid_ad_target_block_<?php echo esc_attr( (string) $mid_ad_index ); ?>" name="mid_ad_widgets[<?php echo esc_attr( (string) $mid_ad_index ); ?>][target_block]">
											<option value="">-- Chọn khối danh mục --</option>
											<?php foreach ( $mid_ad_choices as $mid_ad_choice ) : ?>
												<option value="<?php echo esc_attr( $mid_ad_choice['key'] ?? '' ); ?>" <?php selected( $mid_ad_widget['target_block'] ?? '', $mid_ad_choice['key'] ?? '' ); ?>>
													<?php echo esc_html( $mid_ad_choice['option'] ?? ( $mid_ad_choice['label'] ?? '' ) ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description">Chỉ dùng khi vị trí là "Nằm phía trên một khối danh mục".</p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="mid_ad_affiliate_url_<?php echo esc_attr( (string) $mid_ad_index ); ?>">Affiliate URL</label></th>
									<td>
										<input class="large-text" id="mid_ad_affiliate_url_<?php echo esc_attr( (string) $mid_ad_index ); ?>" name="mid_ad_widgets[<?php echo esc_attr( (string) $mid_ad_index ); ?>][affiliate_url]" value="<?php echo esc_url( $mid_ad_widget['affiliate_url'] ?? '' ); ?>" placeholder="https://shorten.asia/MyH7EVFx">
										<p class="description">Nếu link có tham số url=, hệ thống sẽ tách URL thật để lấy ảnh preview qua Microlink. Link click vẫn là affiliate URL gốc.</p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="mid_ad_preview_image_url_<?php echo esc_attr( (string) $mid_ad_index ); ?>">Ảnh preview riêng</label></th>
									<td>
										<input class="large-text" id="mid_ad_preview_image_url_<?php echo esc_attr( (string) $mid_ad_index ); ?>" name="mid_ad_widgets[<?php echo esc_attr( (string) $mid_ad_index ); ?>][preview_image_url]" value="<?php echo esc_url( $mid_ad_widget['preview_image_url'] ?? '' ); ?>" placeholder="https://.../product.jpg">
										<p>
											<button type="button" class="button" data-bhdt-fetch-preview-image data-bhdt-affiliate-url-field="mid_ad_affiliate_url_<?php echo esc_attr( (string) $mid_ad_index ); ?>" data-bhdt-preview-image-field="mid_ad_preview_image_url_<?php echo esc_attr( (string) $mid_ad_index ); ?>">Lấy ảnh từ link</button>
											<span class="description" data-bhdt-preview-image-status></span>
										</p>
										<p class="description">Nếu Microlink lấy nhầm ảnh verify, bấm nút lấy ảnh hoặc điền trực tiếp URL ảnh sản phẩm vào đây.</p>
									</td>
								</tr>
								<tr>
									<th scope="row">Auto preview</th>
									<td>
										<label>
											<input type="checkbox" name="mid_ad_widgets[<?php echo esc_attr( (string) $mid_ad_index ); ?>][auto_preview]" value="1" <?php checked( 1, (int) ( $mid_ad_widget['auto_preview'] ?? 0 ) ); ?>>
											Tự lấy ảnh sản phẩm từ Affiliate URL
										</label>
										<label style="margin-left:12px;">
											Nút CTA
											<input type="text" name="mid_ad_widgets[<?php echo esc_attr( (string) $mid_ad_index ); ?>][cta_label]" value="<?php echo esc_attr( $mid_ad_widget['cta_label'] ?? '' ); ?>" placeholder="Xem sản phẩm" style="width:150px;">
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="mid_ad_html_<?php echo esc_attr( (string) $mid_ad_index ); ?>">Nội dung HTML</label></th>
									<td>
										<textarea class="large-text code" id="mid_ad_html_<?php echo esc_attr( (string) $mid_ad_index ); ?>" name="mid_ad_widgets[<?php echo esc_attr( (string) $mid_ad_index ); ?>][html]" rows="8" placeholder="&lt;a href=&quot;#&quot;&gt;&lt;img src=&quot;...&quot; alt=&quot;&quot;&gt;&lt;/a&gt;"><?php echo esc_textarea( $mid_ad_widget['html'] ?? '' ); ?></textarea>
										<p class="description">Có thể để trống HTML nếu đã bật Auto preview.</p>
									</td>
								</tr>
							</tbody>
						</table>
						<p style="display:flex;gap:8px;align-items:center;margin:12px 0 0;">
							<?php if ( ! empty( $mid_ad_widget['id'] ) ) : ?>
								<button type="submit" class="button button-primary" name="mid_ad_update_widget" value="<?php echo esc_attr( (string) $mid_ad_index ); ?>">Cập nhật widget này</button>
								<button type="submit" class="button button-link-delete" name="mid_ad_delete_widget" value="<?php echo esc_attr( (string) $mid_ad_index ); ?>" onclick="return confirm('Xóa widget quảng cáo này?');">Xóa widget này</button>
							<?php else : ?>
								<button type="submit" class="button button-primary" name="mid_ad_update_widget" value="<?php echo esc_attr( (string) $mid_ad_index ); ?>">Thêm widget mới</button>
							<?php endif; ?>
						</p>
					</details>
				<?php endforeach; ?>
				<p class="description">Admin có quyền unfiltered_html có thể lưu script/iframe. Tài khoản không có quyền này sẽ bị lọc HTML an toàn.</p>
				<?php submit_button( 'Lưu tất cả widget quảng cáo', 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
		$article_ad_widgets = bhdt_wirecutter_get_in_article_ad_widgets();
		$article_ad_widgets[] = array(
			'id'              => '',
			'title'           => '',
			'memo_name'       => '',
			'enabled'         => 0,
			'auto_insert'     => 0,
			'insertion_mode'  => 'after_paragraph',
			'after_paragraph' => 3,
			'heading_index'   => 1,
			'heading_filter'  => 'all',
			'post_types'      => array( 'post', 'bhdt_review', 'bhdt_comparison', 'bhdt_project' ),
			'target_slugs'    => '',
			'affiliate_url'   => '',
			'preview_image_url' => '',
			'auto_preview'    => 0,
			'cta_label'       => '',
			'html'            => '',
		);
		$article_ad_post_types = array(
			'post'            => 'Post',
			'bhdt_review'     => 'Review',
			'bhdt_comparison' => 'So sánh',
			'bhdt_project'    => 'Dự án',
		);
		?>
		<div class="card" style="max-width:920px;">
			<h2>Widget quảng cáo trong bài viết</h2>
			<p>Chèn khung HTML vào giữa nội dung bài viết. Có thể tự động chèn sau đoạn văn số N hoặc chèn thủ công bằng shortcode.</p>
			<form method="post">
				<?php wp_nonce_field( 'bhdt_in_article_ad_widget_nonce' ); ?>
				<input type="hidden" name="bhdt_save_in_article_ad_widget" value="1">
				<?php foreach ( $article_ad_widgets as $article_ad_index => $article_ad_widget ) : ?>
					<?php $article_ad_open = empty( $_GET['article_ad_saved'] ) && empty( $article_ad_widget['id'] ); ?>
					<details <?php echo $article_ad_open ? 'open' : ''; ?> style="border:1px solid #dcdcde;border-radius:8px;padding:12px;margin:0 0 12px;background:#fff;">
						<summary style="cursor:pointer;font-weight:700;">
							<?php
							echo esc_html(
								'' !== (string) ( $article_ad_widget['memo_name'] ?? '' )
									? $article_ad_widget['memo_name']
									: (
										'' !== (string) ( $article_ad_widget['title'] ?? '' )
											? $article_ad_widget['title']
											: ( '' !== (string) ( $article_ad_widget['id'] ?? '' ) ? 'Widget quảng cáo trong bài' : 'Thêm widget quảng cáo trong bài' )
									)
							);
							?>
						</summary>
						<input type="hidden" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][id]" value="<?php echo esc_attr( $article_ad_widget['id'] ?? '' ); ?>">
						<?php if ( ! empty( $article_ad_widget['id'] ) ) : ?>
							<p class="description">Shortcode: <code>[bhdt_article_ad id="<?php echo esc_html( $article_ad_widget['id'] ); ?>"]</code></p>
						<?php endif; ?>
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">Bật widget</th>
									<td>
										<label>
											<input type="checkbox" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][enabled]" value="1" <?php checked( 1, (int) ( $article_ad_widget['enabled'] ?? 0 ) ); ?>>
											Cho phép hiển thị widget này
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="article_ad_title_<?php echo esc_attr( (string) $article_ad_index ); ?>">Tên gợi nhớ</label></th>
									<td><input class="regular-text" id="article_ad_title_<?php echo esc_attr( (string) $article_ad_index ); ?>" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][title]" value="<?php echo esc_attr( $article_ad_widget['title'] ?? '' ); ?>" placeholder="Top pick, Banner giữa bài..."></td>
								</tr>
								<tr>
									<th scope="row"><label for="article_ad_memo_name_<?php echo esc_attr( (string) $article_ad_index ); ?>">Tên gợi nhớ nội dung</label></th>
									<td>
										<input class="regular-text" id="article_ad_memo_name_<?php echo esc_attr( (string) $article_ad_index ); ?>" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][memo_name]" value="<?php echo esc_attr( $article_ad_widget['memo_name'] ?? '' ); ?>" placeholder="Máy hàn mini, bộ Arduino starter, banner Shopee...">
										<p class="description">Chỉ dùng trong admin/meta box để nhận ra widget này quảng cáo nội dung gì.</p>
									</td>
								</tr>
								<tr>
									<th scope="row">Tự động chèn</th>
									<td>
										<label>
											<input type="checkbox" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][auto_insert]" value="1" <?php checked( 1, (int) ( $article_ad_widget['auto_insert'] ?? 0 ) ); ?>>
											Tự động chèn vào bài viết
										</label>
										<label style="margin-left:12px;">
											Vị trí
											<select name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][insertion_mode]">
												<option value="after_paragraph" <?php selected( $article_ad_widget['insertion_mode'] ?? 'after_paragraph', 'after_paragraph' ); ?>>Sau đoạn văn</option>
												<option value="before_heading" <?php selected( $article_ad_widget['insertion_mode'] ?? '', 'before_heading' ); ?>>Trước tiêu đề/chỉ mục</option>
												<option value="after_heading" <?php selected( $article_ad_widget['insertion_mode'] ?? '', 'after_heading' ); ?>>Sau tiêu đề/chỉ mục</option>
											</select>
										</label>
										<label style="margin-left:12px;">
											Sau đoạn
											<input type="number" min="1" max="50" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][after_paragraph]" value="<?php echo esc_attr( (string) ( $article_ad_widget['after_paragraph'] ?? 3 ) ); ?>" style="width:70px;">
										</label>
										<label style="margin-left:12px;">
											Tiêu đề số
											<input type="number" min="1" max="50" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][heading_index]" value="<?php echo esc_attr( (string) ( $article_ad_widget['heading_index'] ?? 1 ) ); ?>" style="width:70px;">
										</label>
										<label style="display:block;margin-top:8px;">
											<input type="checkbox" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][heading_filter]" value="indexed_upper" <?php checked( $article_ad_widget['heading_filter'] ?? 'all', 'indexed_upper' ); ?>>
											Chỉ đếm tiêu đề/chỉ mục bắt đầu bằng số hoặc chữ in hoa như "1.", "A."
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row">Loại bài áp dụng</th>
									<td>
										<?php foreach ( $article_ad_post_types as $article_post_type => $article_label ) : ?>
											<?php if ( ! post_type_exists( $article_post_type ) ) { continue; } ?>
											<label style="display:inline-block;margin-right:14px;">
												<input type="checkbox" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][post_types][]" value="<?php echo esc_attr( $article_post_type ); ?>" <?php checked( in_array( $article_post_type, (array) ( $article_ad_widget['post_types'] ?? array() ), true ) ); ?>>
												<?php echo esc_html( $article_label ); ?>
											</label>
										<?php endforeach; ?>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="article_ad_target_slugs_<?php echo esc_attr( (string) $article_ad_index ); ?>">Slug bài áp dụng</label></th>
									<td>
										<textarea class="large-text code" id="article_ad_target_slugs_<?php echo esc_attr( (string) $article_ad_index ); ?>" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][target_slugs]" rows="3" placeholder="bom-centroid-file&#10;pick-and-place-machine"><?php echo esc_textarea( $article_ad_widget['target_slugs'] ?? '' ); ?></textarea>
										<p class="description">Dùng slug để mỗi bài có widget riêng. Có thể nhập nhiều slug, mỗi dòng một slug. Để trống nếu widget này áp dụng cho tất cả bài thuộc loại đã chọn.</p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="article_ad_affiliate_url_<?php echo esc_attr( (string) $article_ad_index ); ?>">Affiliate URL</label></th>
									<td>
										<input class="large-text" id="article_ad_affiliate_url_<?php echo esc_attr( (string) $article_ad_index ); ?>" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][affiliate_url]" value="<?php echo esc_url( $article_ad_widget['affiliate_url'] ?? '' ); ?>" placeholder="https://shorten.asia/MyH7EVFx">
										<p class="description">Nếu link có tham số url=, hệ thống sẽ tách URL thật để lấy ảnh preview qua Microlink. Link click vẫn là affiliate URL gốc.</p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="article_ad_preview_image_url_<?php echo esc_attr( (string) $article_ad_index ); ?>">Ảnh preview riêng</label></th>
									<td>
										<input class="large-text" id="article_ad_preview_image_url_<?php echo esc_attr( (string) $article_ad_index ); ?>" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][preview_image_url]" value="<?php echo esc_url( $article_ad_widget['preview_image_url'] ?? '' ); ?>" placeholder="https://.../product.jpg">
										<p>
											<button type="button" class="button" data-bhdt-fetch-preview-image data-bhdt-affiliate-url-field="article_ad_affiliate_url_<?php echo esc_attr( (string) $article_ad_index ); ?>" data-bhdt-preview-image-field="article_ad_preview_image_url_<?php echo esc_attr( (string) $article_ad_index ); ?>">Lấy ảnh từ link</button>
											<span class="description" data-bhdt-preview-image-status></span>
										</p>
										<p class="description">Ưu tiên ảnh này trước Microlink, dùng để bỏ qua ảnh verify/captcha của link rút gọn.</p>
									</td>
								</tr>
								<tr>
									<th scope="row">Auto preview</th>
									<td>
										<label>
											<input type="checkbox" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][auto_preview]" value="1" <?php checked( 1, (int) ( $article_ad_widget['auto_preview'] ?? 0 ) ); ?>>
											Tự lấy ảnh sản phẩm từ Affiliate URL
										</label>
										<label style="margin-left:12px;">
											Nút CTA
											<input type="text" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][cta_label]" value="<?php echo esc_attr( $article_ad_widget['cta_label'] ?? '' ); ?>" placeholder="Xem sản phẩm" style="width:150px;">
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="article_ad_html_<?php echo esc_attr( (string) $article_ad_index ); ?>">Nội dung HTML</label></th>
									<td>
										<textarea class="large-text code" id="article_ad_html_<?php echo esc_attr( (string) $article_ad_index ); ?>" name="article_ad_widgets[<?php echo esc_attr( (string) $article_ad_index ); ?>][html]" rows="9" placeholder="&lt;div class=&quot;ad-box&quot;&gt;...&lt;/div&gt;"><?php echo esc_textarea( $article_ad_widget['html'] ?? '' ); ?></textarea>
										<p class="description">Có thể để trống HTML nếu đã bật Auto preview.</p>
									</td>
								</tr>
							</tbody>
						</table>
						<p style="display:flex;gap:8px;align-items:center;margin:12px 0 0;">
							<?php if ( ! empty( $article_ad_widget['id'] ) ) : ?>
								<button type="submit" class="button button-primary" name="article_ad_update_widget" value="<?php echo esc_attr( (string) $article_ad_index ); ?>">Cập nhật widget này</button>
								<button type="submit" class="button button-link-delete" name="article_ad_delete_widget" value="<?php echo esc_attr( (string) $article_ad_index ); ?>" onclick="return confirm('Xóa widget quảng cáo trong bài này?');">Xóa widget này</button>
							<?php else : ?>
								<button type="submit" class="button button-primary" name="article_ad_update_widget" value="<?php echo esc_attr( (string) $article_ad_index ); ?>">Thêm widget mới</button>
							<?php endif; ?>
						</p>
					</details>
				<?php endforeach; ?>
				<p class="description">Nếu không bật tự động chèn, dùng shortcode hiển thị trên từng widget để đặt chính xác trong nội dung bài viết.</p>
				<?php submit_button( 'Lưu tất cả widget quảng cáo trong bài', 'secondary', 'submit', false ); ?>
			</form>
			<script>
			document.addEventListener('click', function (event) {
				var button = event.target.closest('[data-bhdt-fetch-preview-image]');
				if (!button) {
					return;
				}

				var affiliateInput = document.getElementById(button.getAttribute('data-bhdt-affiliate-url-field'));
				var imageInput = document.getElementById(button.getAttribute('data-bhdt-preview-image-field'));
				var status = button.parentNode ? button.parentNode.querySelector('[data-bhdt-preview-image-status]') : null;
				if (!affiliateInput || !imageInput) {
					return;
				}

				var affiliateUrl = affiliateInput.value.trim();
				if (!affiliateUrl) {
					if (status) {
						status.textContent = 'Nhập Affiliate URL trước.';
					}
					return;
				}

				var originalText = button.textContent;
				button.disabled = true;
				button.textContent = 'Đang lấy...';
				if (status) {
					status.textContent = '';
				}

				var body = new URLSearchParams();
				body.append('action', 'bhdt_fetch_ad_preview_image');
				body.append('nonce', '<?php echo esc_js( wp_create_nonce( 'bhdt_fetch_ad_preview_image' ) ); ?>');
				body.append('affiliate_url', affiliateUrl);

				fetch(ajaxurl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
					},
					body: body.toString()
				}).then(function (response) {
					return response.json();
				}).then(function (payload) {
					if (!payload || !payload.success || !payload.data || !payload.data.image_url) {
						throw new Error(payload && payload.data && payload.data.message ? payload.data.message : 'Không lấy được ảnh.');
					}

					imageInput.value = payload.data.image_url;
					if (status) {
						status.textContent = 'Đã điền ảnh preview.';
					}
				}).catch(function (error) {
					if (status) {
						status.textContent = error.message || 'Không lấy được ảnh.';
					}
				}).finally(function () {
					button.disabled = false;
					button.textContent = originalText;
				});
			});
			</script>
		</div>
		<div class="card" style="max-width:720px;">
			<h2>Cleanup runtime files</h2>
			<p>Step 1 scans known local-only files and shows exactly what would be deleted. Step 2 deletes only the scanned list after confirmation.</p>
			<form method="post">
				<?php wp_nonce_field( 'bhdt_cleanup_runtime_scan_nonce' ); ?>
				<input type="hidden" name="bhdt_cleanup_runtime_scan" value="1">
				<?php submit_button( '1. Analyze runtime files', 'secondary', 'submit', false ); ?>
			</form>
			<?php
			$cleanup_token = isset( $_GET['cleanup_scan'] ) ? sanitize_text_field( wp_unslash( $_GET['cleanup_scan'] ) ) : '';
			$cleanup_scan  = $cleanup_token ? bhdt_wirecutter_get_cleanup_runtime_scan( $cleanup_token ) : array();
			?>
			<?php if ( ! empty( $cleanup_scan ) ) : ?>
				<hr>
				<p>
					<strong><?php echo esc_html( (string) count( $cleanup_scan['files'] ?? array() ) ); ?></strong>
					files found,
					<strong><?php echo esc_html( size_format( (int) ( $cleanup_scan['total_size'] ?? 0 ), 2 ) ); ?></strong>
					total.
				</p>
				<?php if ( empty( $cleanup_scan['files'] ) ) : ?>
					<div class="notice notice-success inline"><p>No cleanup candidates found.</p></div>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th>File</th>
								<th style="width:120px;">Size</th>
								<th>Reason</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $cleanup_scan['files'] as $cleanup_file ) : ?>
								<tr>
									<td><code><?php echo esc_html( $cleanup_file['relative_path'] ?? '' ); ?></code></td>
									<td><?php echo esc_html( size_format( (int) ( $cleanup_file['size'] ?? 0 ), 2 ) ); ?></td>
									<td><?php echo esc_html( $cleanup_file['reason'] ?? '' ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<form method="post" style="margin-top:12px;" onsubmit="return confirm('Delete only the scanned runtime files shown above?');">
						<?php wp_nonce_field( 'bhdt_cleanup_runtime_confirm_nonce' ); ?>
						<input type="hidden" name="bhdt_cleanup_runtime_confirm" value="1">
						<input type="hidden" name="cleanup_token" value="<?php echo esc_attr( $cleanup_token ); ?>">
						<?php submit_button( '2. Confirm clean scanned files', 'delete', 'submit', false ); ?>
					</form>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php $pinout_library = bhdt_wirecutter_get_pinout_library(); ?>
		<?php $pinout_edit_slug = isset( $_GET['pinout_edit'] ) ? sanitize_title( wp_unslash( $_GET['pinout_edit'] ) ) : ''; ?>
		<?php $pinout_edit_item = ( $pinout_edit_slug && isset( $pinout_library[ $pinout_edit_slug ] ) ) ? $pinout_library[ $pinout_edit_slug ] : null; ?>
		<?php if ( isset( $_GET['pinout_scanned'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html( sprintf( 'Đã quét pinout: thêm %1$d placeholder mới. Thư viện hiện có %2$d mục.', (int) ( $_GET['pinout_added'] ?? 0 ), count( $pinout_library ) ) ); ?></p>
			</div>
		<?php endif; ?>
		<div class="card" style="max-width:720px;">
			<h2>Pinout Library</h2>
			<p>Quét tags và tiêu đề bài viết để tự tạo placeholder linh kiện cho Pinout Finder. Các mục này sẽ hiện trong tool, chờ mình bổ sung ảnh pinout/datasheet sau.</p>
			<form method="post">
				<?php wp_nonce_field( 'bhdt_scan_pinout_library_nonce' ); ?>
				<input type="hidden" name="bhdt_scan_pinout_library" value="1">
				<?php submit_button( 'Quét bài viết và tạo Pinout placeholder', 'secondary', 'submit', false ); ?>
			</form>
			<p><strong><?php echo esc_html( (string) count( $pinout_library ) ); ?></strong> mục pinout tự tạo.</p>
			<?php if ( isset( $_GET['pinout_saved'] ) ) : ?>
				<div class="notice notice-success inline"><p>Pinout saved.</p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['pinout_deleted'] ) ) : ?>
				<div class="notice notice-success inline"><p>Pinout deleted.</p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['pinout_sourced'] ) ) : ?>
				<div class="notice notice-success inline"><p>Auto source suggestions added. Review links before marking verified.</p></div>
			<?php endif; ?>
			<hr>
			<details <?php echo $pinout_edit_item ? 'open' : ''; ?> style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#fff;">
				<summary style="cursor:pointer;font-weight:700;font-size:15px;"><?php echo $pinout_edit_item ? 'Edit pinout' : 'Add pinout'; ?></summary>
			<form method="post" style="margin-top:12px;">
				<?php wp_nonce_field( 'bhdt_save_pinout_item_nonce' ); ?>
				<input type="hidden" name="bhdt_save_pinout_item" value="1">
				<input type="hidden" name="old_slug" value="<?php echo esc_attr( $pinout_edit_slug ); ?>">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="pinout_name">Tên</label></th>
							<td><input class="regular-text" id="pinout_name" name="name" value="<?php echo esc_attr( $pinout_edit_item['name'] ?? '' ); ?>" required></td>
						</tr>
						<tr>
							<th scope="row"><label for="pinout_slug">Slug</label></th>
							<td><input class="regular-text" id="pinout_slug" name="slug" value="<?php echo esc_attr( $pinout_edit_item['slug'] ?? '' ); ?>" placeholder="tự tạo từ tên"></td>
						</tr>
						<tr>
							<th scope="row"><label for="pinout_status">Trạng thái</label></th>
							<td>
								<?php $pinout_status = $pinout_edit_item['status'] ?? 'placeholder'; ?>
								<select id="pinout_status" name="status">
									<option value="placeholder" <?php selected( $pinout_status, 'placeholder' ); ?>>Chờ bổ sung</option>
									<option value="need_review" <?php selected( $pinout_status, 'need_review' ); ?>>Cần xem lại</option>
									<option value="verified" <?php selected( $pinout_status, 'verified' ); ?>>Đã xác minh</option>
									<option value="hidden" <?php selected( $pinout_status, 'hidden' ); ?>>Ẩn</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="pinout_summary">Tóm tắt</label></th>
							<td><textarea class="large-text" id="pinout_summary" name="summary" rows="3"><?php echo esc_textarea( $pinout_edit_item['summary'] ?? '' ); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"><label for="pinout_filename">Tên file ảnh pinout</label></th>
							<td><input class="regular-text" id="pinout_filename" name="filename" value="<?php echo esc_attr( $pinout_edit_item['filename'] ?? '' ); ?>" placeholder="esp32-pinout.webp"></td>
						</tr>
						<tr>
							<th scope="row"><label for="pinout_image_url">Image URL</label></th>
							<td><input class="large-text" id="pinout_image_url" name="image_url" value="<?php echo esc_url( $pinout_edit_item['image_url'] ?? '' ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="pinout_datasheet_url">Datasheet URL</label></th>
							<td><input class="large-text" id="pinout_datasheet_url" name="datasheet_url" value="<?php echo esc_url( $pinout_edit_item['datasheet_url'] ?? '' ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="pinout_official_url">Official docs URL</label></th>
							<td><input class="large-text" id="pinout_official_url" name="official_url" value="<?php echo esc_url( $pinout_edit_item['official_url'] ?? '' ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="pinout_aliases">Aliases</label></th>
							<td><textarea class="large-text" id="pinout_aliases" name="aliases" rows="2" placeholder="one alias per line"><?php echo esc_textarea( implode( "\n", (array) ( $pinout_edit_item['aliases'] ?? array() ) ) ); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"><label for="pinout_specs">Specs</label></th>
							<td><textarea class="large-text" id="pinout_specs" name="specs" rows="3" placeholder="one spec per line"><?php echo esc_textarea( implode( "\n", (array) ( $pinout_edit_item['specs'] ?? array() ) ) ); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"><label for="pinout_notes">Notes / pins</label></th>
							<td><textarea class="large-text" id="pinout_notes" name="notes" rows="5" placeholder="one pin/note per line"><?php echo esc_textarea( implode( "\n", (array) ( $pinout_edit_item['notes'] ?? array() ) ) ); ?></textarea></td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( $pinout_edit_item ? 'Save pinout' : 'Add pinout', 'primary', 'submit', false ); ?>
				<?php if ( $pinout_edit_item ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bhdt-web-staff' ) ); ?>">Cancel edit</a>
				<?php endif; ?>
			</form>
			</details>
			<hr>
			<?php if ( ! empty( $pinout_library ) ) : ?>
				<details <?php echo $pinout_edit_item ? 'open' : ''; ?> style="border:1px solid #dcdcde;border-radius:8px;padding:12px;background:#fff;">
					<summary style="cursor:pointer;font-weight:700;font-size:15px;">View pinout library (<?php echo esc_html( (string) count( $pinout_library ) ); ?> items)</summary>
					<div style="margin-top:12px;max-height:520px;overflow:auto;">
				<table class="widefat striped">
					<thead><tr><th>Tên</th><th>Slug</th><th>Nguồn</th></tr></thead>
					<tbody>
						<?php foreach ( $pinout_library as $item ) : ?>
							<tr>
								<td><?php echo esc_html( $item['name'] ?? '' ); ?></td>
								<td><code><?php echo esc_html( $item['slug'] ?? '' ); ?></code></td>
								<td><?php echo esc_html( $item['status'] ?? 'placeholder' ); ?></td>
								<td>
									<?php if ( ! empty( $item['datasheet_url'] ) ) : ?>
										<a href="<?php echo esc_url( $item['datasheet_url'] ); ?>" target="_blank" rel="noopener">datasheet</a>
									<?php endif; ?>
									<?php if ( ! empty( $item['official_url'] ) ) : ?>
										<a href="<?php echo esc_url( $item['official_url'] ); ?>" target="_blank" rel="noopener">official</a>
									<?php endif; ?>
								</td>
								<td>
									<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'bhdt-web-staff', 'pinout_edit' => $item['slug'] ?? '' ), admin_url( 'admin.php' ) ) ); ?>">Edit</a>
									<form method="post" style="display:inline;">
										<?php wp_nonce_field( 'bhdt_suggest_pinout_sources_nonce' ); ?>
										<input type="hidden" name="bhdt_suggest_pinout_sources" value="1">
										<input type="hidden" name="slug" value="<?php echo esc_attr( $item['slug'] ?? '' ); ?>">
										<button class="button button-small" type="submit">Find sources</button>
									</form>
									<form method="post" style="display:inline;" onsubmit="return confirm('Delete this pinout item?');">
										<?php wp_nonce_field( 'bhdt_delete_pinout_item_nonce' ); ?>
										<input type="hidden" name="bhdt_delete_pinout_item" value="1">
										<input type="hidden" name="slug" value="<?php echo esc_attr( $item['slug'] ?? '' ); ?>">
										<button class="button button-small" type="submit">Delete</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
					</div>
				</details>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

function bhdt_wirecutter_update_home_category_references_after_merge( $source_slug, $target_slug ) {
	$source_slug = sanitize_title( (string) $source_slug );
	$target_slug = sanitize_title( (string) $target_slug );

	if ( '' === $source_slug || '' === $target_slug || $source_slug === $target_slug ) {
		return;
	}

	$rows = get_option( 'bhdt_wire_home_category_groups', array() );
	if ( is_array( $rows ) ) {
		$changed = false;
		foreach ( $rows as &$row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$row_slug = isset( $row['slug'] ) ? sanitize_title( $row['slug'] ) : '';
			$aliases  = bhdt_wirecutter_clean_home_block_slug_aliases( $row['slug_aliases'] ?? array() );

			if ( $source_slug === $row_slug ) {
				$row['slug'] = $target_slug;
				$aliases[]   = $source_slug;
				$changed     = true;
			} elseif ( in_array( $source_slug, $aliases, true ) && ! in_array( $target_slug, $aliases, true ) ) {
				$aliases[] = $target_slug;
				$changed   = true;
			}

			$row['slug_aliases'] = array_values( array_diff( bhdt_wirecutter_clean_home_block_slug_aliases( $aliases ), array( $target_slug ) ) );
		}
		unset( $row );

		if ( $changed ) {
			update_option( 'bhdt_wire_home_category_groups', $rows, false );
		}
	}

	$linked_posts = get_posts(
		array(
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_bhdt_home_category_block_key',
					'value'   => $source_slug,
					'compare' => '=',
				),
			),
		)
	);

	foreach ( $linked_posts as $post_id ) {
		update_post_meta( (int) $post_id, '_bhdt_home_category_block_key', $target_slug );
	}
}

function bhdt_wirecutter_merge_category_terms( $source_id, $target_id ) {
	$source_id = (int) $source_id;
	$target_id = (int) $target_id;

	if ( $source_id <= 0 || $target_id <= 0 || $source_id === $target_id ) {
		return new WP_Error( 'invalid_category_merge', 'Category merge is invalid.' );
	}

	$source = get_term( $source_id, 'category' );
	$target = get_term( $target_id, 'category' );
	if ( ! $source || is_wp_error( $source ) || ! $target || is_wp_error( $target ) ) {
		return new WP_Error( 'missing_category_merge_term', 'Category merge terms were not found.' );
	}

	$object_ids = get_objects_in_term( $source_id, 'category' );
	if ( is_wp_error( $object_ids ) ) {
		return $object_ids;
	}

	foreach ( array_map( 'intval', (array) $object_ids ) as $object_id ) {
		$current_terms = wp_get_object_terms( $object_id, 'category', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $current_terms ) ) {
			continue;
		}

		$new_terms = array_values( array_unique( array_diff( array_map( 'intval', $current_terms ), array( $source_id ) ) ) );
		$new_terms[] = $target_id;
		wp_set_object_terms( $object_id, array_values( array_unique( $new_terms ) ), 'category', false );
	}

	$children = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'parent'     => $source_id,
		)
	);

	if ( ! is_wp_error( $children ) ) {
		foreach ( $children as $child ) {
			wp_update_term( (int) $child->term_id, 'category', array( 'parent' => $target_id ) );
		}
	}

	bhdt_wirecutter_update_home_category_references_after_merge( $source->slug, $target->slug );

	return wp_delete_term( $source_id, 'category' );
}

function bhdt_wirecutter_handle_category_sync_actions() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-category-sync' !== $_GET['page'] || ! isset( $_POST['bhdt_merge_categories'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_category_sync_nonce' );

	$target_id = isset( $_POST['target_category_id'] ) ? (int) $_POST['target_category_id'] : 0;
	$source_ids = isset( $_POST['source_category_ids'] ) && is_array( $_POST['source_category_ids'] )
		? array_map( 'intval', wp_unslash( $_POST['source_category_ids'] ) )
		: array();

	$merged = 0;
	foreach ( array_unique( array_filter( $source_ids ) ) as $source_id ) {
		if ( $source_id === $target_id ) {
			continue;
		}

		$result = bhdt_wirecutter_merge_category_terms( $source_id, $target_id );
		if ( ! is_wp_error( $result ) && false !== $result ) {
			$merged++;
		}
	}

	wp_safe_redirect( admin_url( 'admin.php?page=bhdt-category-sync&merged=' . $merged ) );
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_category_sync_actions' );

function bhdt_wirecutter_auto_tag_stop_words() {
	return array(
		'ban', 'bang', 'bai', 'bo', 'cac', 'cach', 'cai', 'can', 'cho', 'chuan', 'chi', 'chi-tiet', 'co', 'cua',
		'de', 'den', 'duoc', 'dung', 'gi', 'giua', 'hay', 'hien', 'hoat', 'hon', 'khi', 'la', 'lam', 'moi',
		'mot', 'nhieu', 'nhung', 'phan', 'pho', 'qua', 'sang', 'so', 'so-sanh', 'tai', 'the', 'the-nao', 'thiet',
		'toi', 'trong', 'tu', 'tung', 'va', 've', 'voi',
	);
}

function bhdt_wirecutter_normalize_auto_tag_text( $text ) {
	$text = wp_strip_all_tags( (string) $text );
	$text = html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );
	$text = remove_accents( $text );
	$text = preg_replace( '/[^A-Za-z0-9+#.\- ]+/', ' ', $text );
	$text = preg_replace( '/\s+/', ' ', $text );

	return trim( (string) $text );
}

function bhdt_wirecutter_build_auto_tags_from_title( $title ) {
	$normalized = bhdt_wirecutter_normalize_auto_tag_text( $title );
	if ( '' === $normalized ) {
		return array();
	}

	$tags = array();
	if ( preg_match_all( '/\b(?:[A-Z]{2,}[A-Z0-9+#.\-]*|[A-Za-z]+[0-9]+[A-Za-z0-9+#.\-]*|[0-9]+[A-Za-z]+[A-Za-z0-9+#.\-]*)\b/', $normalized, $matches ) ) {
		foreach ( $matches[0] as $match ) {
			$match = trim( $match, " \t\n\r\0\x0B-." );
			if ( strlen( $match ) >= 2 ) {
				$tags[] = $match;
			}
		}
	}

	$words = preg_split( '/\s+/', strtolower( $normalized ) );
	$stop_words = array_fill_keys( bhdt_wirecutter_auto_tag_stop_words(), true );
	$keyword_words = array();
	foreach ( (array) $words as $word ) {
		$word = trim( $word, " \t\n\r\0\x0B-." );
		if ( strlen( $word ) < 3 || isset( $stop_words[ $word ] ) || is_numeric( $word ) ) {
			continue;
		}

		$keyword_words[] = $word;
	}

	$keyword_words = array_values( array_unique( $keyword_words ) );
	foreach ( $keyword_words as $word ) {
		$tags[] = $word;
	}

	for ( $i = 0, $total = count( $keyword_words ); $i < $total - 1; $i++ ) {
		$phrase = $keyword_words[ $i ] . ' ' . $keyword_words[ $i + 1 ];
		if ( strlen( $phrase ) >= 8 ) {
			$tags[] = $phrase;
		}
	}

	$clean_tags = array();
	foreach ( $tags as $tag ) {
		$tag = trim( preg_replace( '/\s+/', ' ', (string) $tag ) );
		if ( strlen( $tag ) < 2 || strlen( $tag ) > 60 ) {
			continue;
		}

		$key = sanitize_title( $tag );
		if ( '' === $key || isset( $clean_tags[ $key ] ) ) {
			continue;
		}

		$clean_tags[ $key ] = $tag;
		if ( count( $clean_tags ) >= 8 ) {
			break;
		}
	}

	return array_values( $clean_tags );
}

function bhdt_wirecutter_auto_add_title_tags( $post_id, $post, $update ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status || ! in_array( $post->post_type, array( 'post', 'bhdt_review', 'bhdt_comparison' ), true ) ) {
		return;
	}

	if ( ! is_object_in_taxonomy( $post->post_type, 'post_tag' ) ) {
		return;
	}

	$tags = bhdt_wirecutter_build_auto_tags_from_title( $post->post_title );
	if ( empty( $tags ) ) {
		return;
	}

	wp_set_object_terms( $post_id, $tags, 'post_tag', true );
}
add_action( 'save_post', 'bhdt_wirecutter_auto_add_title_tags', 30, 3 );

function bhdt_wirecutter_scan_and_add_title_tags( $minimum_tags = 3 ) {
	$minimum_tags = max( 0, (int) $minimum_tags );
	$post_ids = get_posts(
		array(
			'post_type'        => array( 'post', 'bhdt_review', 'bhdt_comparison' ),
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		)
	);

	$stats = array(
		'scanned' => 0,
		'updated' => 0,
		'added'   => 0,
	);

	foreach ( array_map( 'intval', (array) $post_ids ) as $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || ! is_object_in_taxonomy( $post->post_type, 'post_tag' ) ) {
			continue;
		}

		$stats['scanned']++;
		$current_tags = wp_get_object_terms( $post_id, 'post_tag', array( 'fields' => 'all' ) );
		if ( is_wp_error( $current_tags ) ) {
			continue;
		}

		if ( count( $current_tags ) >= $minimum_tags ) {
			continue;
		}

		$existing_slugs = array();
		foreach ( $current_tags as $term ) {
			$existing_slugs[ sanitize_title( $term->name ) ] = true;
			$existing_slugs[ sanitize_title( $term->slug ) ] = true;
		}

		$new_tags = array();
		foreach ( bhdt_wirecutter_build_auto_tags_from_title( $post->post_title ) as $tag ) {
			$slug = sanitize_title( $tag );
			if ( '' === $slug || isset( $existing_slugs[ $slug ] ) ) {
				continue;
			}

			$new_tags[] = $tag;
			$existing_slugs[ $slug ] = true;
		}

		if ( empty( $new_tags ) ) {
			continue;
		}

		$result = wp_set_object_terms( $post_id, $new_tags, 'post_tag', true );
		if ( is_wp_error( $result ) ) {
			continue;
		}

		$stats['updated']++;
		$stats['added'] += count( $new_tags );
	}

	return $stats;
}

function bhdt_wirecutter_handle_auto_tags_scan() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-web-staff' !== $_GET['page'] || ! isset( $_POST['bhdt_scan_auto_tags'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_scan_auto_tags_nonce' );
	$stats = bhdt_wirecutter_scan_and_add_title_tags( 3 );
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'              => 'bhdt-web-staff',
				'auto_tags_scanned' => (int) $stats['scanned'],
				'auto_tags_updated' => (int) $stats['updated'],
				'auto_tags_added'   => (int) $stats['added'],
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_auto_tags_scan' );

function bhdt_wirecutter_site_check_report() {
	$issues = array();
	$info   = array();

	$info[] = 'WordPress ' . get_bloginfo( 'version' );
	$info[] = 'Theme: ' . wp_get_theme()->get( 'Name' );
	$info[] = 'Home URL: ' . home_url( '/' );

	if ( defined( 'WP_ENVIRONMENT_TYPE' ) && 'local' === WP_ENVIRONMENT_TYPE ) {
		$issues[] = array(
			'level' => 'warning',
			'text'  => 'WP_ENVIRONMENT_TYPE is local. Do not upload this wp-config.php to production.',
		);
	}

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$issues[] = array(
			'level' => 'warning',
			'text'  => 'WP_DEBUG is enabled. Turn it off on production unless actively debugging.',
		);
	}

	foreach ( array( 'home', 'siteurl' ) as $option_name ) {
		$value = (string) get_option( $option_name, '' );
		if ( false !== strpos( $value, '.local' ) || false !== strpos( $value, 'localhost' ) ) {
			$issues[] = array(
				'level' => 'warning',
				'text'  => sprintf( '%s still points to a local URL: %s', $option_name, $value ),
			);
		}
	}

	$uncategorized = get_category_by_slug( 'uncategorized' );
	if ( $uncategorized && (int) $uncategorized->count > 0 ) {
		$issues[] = array(
			'level' => 'warning',
			'text'  => sprintf( '%d published items are still in Uncategorized.', (int) $uncategorized->count ),
		);
	}

	$typo = get_term_by( 'slug', 'dat-mach-mach-in-pcb', 'category' );
	if ( $typo && ! is_wp_error( $typo ) && false !== stripos( (string) $typo->name, 'Mạh' ) ) {
		$issues[] = array(
			'level' => 'warning',
			'text'  => 'Category name looks misspelled: ' . $typo->name,
		);
	}

	$publish_counts = wp_count_posts( 'post' );
	$info[] = 'Published posts: ' . (int) ( $publish_counts->publish ?? 0 );

	$missing_thumbs = get_posts(
		array(
			'post_type'      => array( 'post', 'bhdt_review', 'bhdt_comparison' ),
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'NOT EXISTS',
				),
			),
			'no_found_rows'  => true,
		)
	);
	if ( ! empty( $missing_thumbs ) ) {
		$issues[] = array(
			'level' => 'notice',
			'text'  => sprintf( '%d bài Post/Review/So sánh đã publish đang thiếu ảnh đại diện.', count( $missing_thumbs ) ),
		);
	}

	$pending_reviews = wp_count_posts( 'bhdt_review' );
	if ( ! empty( $pending_reviews->pending ) ) {
		$issues[] = array(
			'level' => 'notice',
			'text'  => sprintf( '%d bài review đang chờ duyệt.', (int) $pending_reviews->pending ),
		);
	}

	$risky_files = array(
		WP_CONTENT_DIR . '/bhdt-import.log',
		WP_CONTENT_DIR . '/function_menu_debug.log',
		WP_CONTENT_DIR . '/plugins/bhdt-core-system/.env',
		WP_CONTENT_DIR . '/plugins/bhdt-core-system/zenclau_worker_v1.log',
		WP_CONTENT_DIR . '/plugins/bhdt-core-system/__pycache__',
		WP_CONTENT_DIR . '/plugins/bhdt-core-system.zip',
	);
	foreach ( $risky_files as $path ) {
		if ( file_exists( $path ) ) {
			$issues[] = array(
				'level' => 'warning',
				'text'  => 'Local/runtime file should not be deployed: ' . str_replace( ABSPATH, '', $path ),
			);
		}
	}

	return array(
		'issues' => $issues,
		'info'   => $info,
	);
}

function bhdt_wirecutter_category_rules() {
	return array(
		'dat-mach-mach-in-pcb' => array( 'pcb', 'pcba', 'dfm', 'panelization', 'anten', 'rf', 'mach in', 'smd', 'driver dong co', 'cong suat' ),
		'module-cam-bien'     => array( 'cam bien', 'sensor', 'imu', 'mpu6050', 'tof', 'sieu am', 'quang hoc', 'trong luong' ),
		'thiet-bi-nhung'      => array( 'arduino', 'esp32', 'risc-v', 'arm', 'iot', 'tinyml', 'lilygo', 'm5stack', 'cardputer', 'beepberry' ),
		'the-gioi-in-3d'      => array( 'in 3d', 'fdm', 'resin' ),
		'robot-thong-minh'    => array( 'robot', 'emo', 'vector', 'looi', 'eilik', 'xiaozhi', 'chatbot ai' ),
		'thiet-bi'            => array( 'oscilloscope', 'fnirsi', 'usb tester', 'dong ho', 'may do', 'tram chan doan' ),
		'dien-tu-ung-dung'    => array( 'smart home', 'tuoi cay', 'tram thoi tiet', 'ung dung' ),
		'linh-kien'           => array( 'linh kien', 'module', 'ic', 'mcu', 'board' ),
	);
}

function bhdt_wirecutter_pick_category_slug_for_post( $post ) {
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$text = remove_accents( strtolower( wp_strip_all_tags( $post->post_title . ' ' . $post->post_excerpt . ' ' . wp_trim_words( $post->post_content, 80, '' ) ) ) );
	foreach ( bhdt_wirecutter_category_rules() as $slug => $needles ) {
		foreach ( $needles as $needle ) {
			if ( false !== strpos( $text, remove_accents( strtolower( $needle ) ) ) ) {
				return $slug;
			}
		}
	}

	return 'linh-kien';
}

function bhdt_wirecutter_ensure_category_label_fixes() {
	$fixed = 0;
	$term  = get_term_by( 'slug', 'dat-mach-mach-in-pcb', 'category' );
	if ( $term && ! is_wp_error( $term ) && 'Đặt Mạch In PCB' !== $term->name ) {
		$result = wp_update_term(
			(int) $term->term_id,
			'category',
			array(
				'name' => 'Đặt Mạch In PCB',
				'slug' => 'dat-mach-mach-in-pcb',
			)
		);
		if ( ! is_wp_error( $result ) ) {
			$fixed++;
		}
	}

	return $fixed;
}

function bhdt_wirecutter_auto_assign_categories() {
	$default_id = (int) get_option( 'default_category', 0 );
	$post_ids   = get_posts(
		array(
			'post_type'        => array( 'post', 'bhdt_review', 'bhdt_comparison', 'bhdt_project' ),
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		)
	);

	$stats = array(
		'scanned'       => 0,
		'updated'       => 0,
		'label_fixed'   => bhdt_wirecutter_ensure_category_label_fixes(),
		'missing_terms' => 0,
	);

	foreach ( array_map( 'intval', (array) $post_ids ) as $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || ! is_object_in_taxonomy( $post->post_type, 'category' ) ) {
			continue;
		}

		$stats['scanned']++;
		$current = wp_get_object_terms( $post_id, 'category', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $current ) ) {
			continue;
		}

		$current = array_values( array_map( 'intval', (array) $current ) );
		$needs_category = empty( $current ) || ( 1 === count( $current ) && $default_id > 0 && $current[0] === $default_id );
		if ( ! $needs_category ) {
			continue;
		}

		$slug = bhdt_wirecutter_pick_category_slug_for_post( $post );
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( ! $term || is_wp_error( $term ) ) {
			$stats['missing_terms']++;
			continue;
		}

		$result = wp_set_object_terms( $post_id, array( (int) $term->term_id ), 'category', false );
		if ( is_wp_error( $result ) ) {
			continue;
		}

		$stats['updated']++;
	}

	return $stats;
}

function bhdt_wirecutter_handle_auto_category_scan() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-web-staff' !== $_GET['page'] || ! isset( $_POST['bhdt_auto_categories'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_auto_categories_nonce' );
	$stats = bhdt_wirecutter_auto_assign_categories();
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                    => 'bhdt-web-staff',
				'auto_cat_scanned'        => (int) $stats['scanned'],
				'auto_cat_updated'        => (int) $stats['updated'],
				'auto_cat_label_fixed'    => (int) $stats['label_fixed'],
				'auto_cat_missing_terms'  => (int) $stats['missing_terms'],
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_auto_category_scan' );

function bhdt_wirecutter_handle_mid_page_ad_widget_save() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-web-staff' !== $_GET['page'] || ! isset( $_POST['bhdt_save_mid_page_ad_widget'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_mid_page_ad_widget_nonce' );

	$submitted_widgets = isset( $_POST['mid_ad_widgets'] ) && is_array( $_POST['mid_ad_widgets'] )
		? wp_unslash( $_POST['mid_ad_widgets'] )
		: array();
	$widgets = array();
	$delete_widget_index = isset( $_POST['mid_ad_delete_widget'] ) ? (string) absint( wp_unslash( $_POST['mid_ad_delete_widget'] ) ) : null;

	foreach ( $submitted_widgets as $submitted_index => $submitted_widget ) {
		if ( ! is_array( $submitted_widget ) ) {
			continue;
		}
		if ( null !== $delete_widget_index && (string) $submitted_index === $delete_widget_index ) {
			continue;
		}
		if ( ! empty( $submitted_widget['id'] ) && ! empty( $submitted_widget['delete'] ) ) {
			continue;
		}

		$html = (string) ( $submitted_widget['html'] ?? '' );
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$html = wp_kses_post( $html );
		}

		$widget = bhdt_wirecutter_normalize_mid_page_ad_widget(
			array(
				'id'           => $submitted_widget['id'] ?? '',
				'title'        => $submitted_widget['title'] ?? '',
				'enabled'      => isset( $submitted_widget['enabled'] ) ? 1 : 0,
				'position'     => $submitted_widget['position'] ?? 'before_category',
				'target_block' => $submitted_widget['target_block'] ?? '',
				'affiliate_url' => $submitted_widget['affiliate_url'] ?? '',
				'preview_image_url' => $submitted_widget['preview_image_url'] ?? '',
				'auto_preview' => isset( $submitted_widget['auto_preview'] ) ? 1 : 0,
				'cta_label'    => $submitted_widget['cta_label'] ?? '',
				'html'         => $html,
			)
		);

		if ( '' === trim( $widget['html'] ) && '' === trim( $widget['title'] ) && '' === $widget['affiliate_url'] && 0 === (int) $widget['enabled'] ) {
			continue;
		}

		$widgets[] = $widget;
	}

	update_option( 'bhdt_wire_mid_page_ad_widgets', $widgets, false );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'         => 'bhdt-web-staff',
				'mid_ad_saved' => 1,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_mid_page_ad_widget_save' );

function bhdt_wirecutter_handle_in_article_ad_widget_save() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-web-staff' !== $_GET['page'] || ! isset( $_POST['bhdt_save_in_article_ad_widget'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_in_article_ad_widget_nonce' );

	$submitted_widgets = isset( $_POST['article_ad_widgets'] ) && is_array( $_POST['article_ad_widgets'] )
		? wp_unslash( $_POST['article_ad_widgets'] )
		: array();
	$widgets = array();
	$delete_widget_index = isset( $_POST['article_ad_delete_widget'] ) ? (string) absint( wp_unslash( $_POST['article_ad_delete_widget'] ) ) : null;

	foreach ( $submitted_widgets as $submitted_index => $submitted_widget ) {
		if ( ! is_array( $submitted_widget ) ) {
			continue;
		}
		if ( null !== $delete_widget_index && (string) $submitted_index === $delete_widget_index ) {
			continue;
		}
		if ( ! empty( $submitted_widget['id'] ) && ! empty( $submitted_widget['delete'] ) ) {
			continue;
		}

		$html = (string) ( $submitted_widget['html'] ?? '' );
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$html = wp_kses_post( $html );
		}

		$widget = bhdt_wirecutter_normalize_in_article_ad_widget(
			array(
				'id'              => $submitted_widget['id'] ?? '',
				'title'           => $submitted_widget['title'] ?? '',
				'memo_name'       => $submitted_widget['memo_name'] ?? '',
				'enabled'         => isset( $submitted_widget['enabled'] ) ? 1 : 0,
				'auto_insert'     => isset( $submitted_widget['auto_insert'] ) ? 1 : 0,
				'insertion_mode'  => $submitted_widget['insertion_mode'] ?? 'after_paragraph',
				'after_paragraph' => $submitted_widget['after_paragraph'] ?? 3,
				'heading_index'   => $submitted_widget['heading_index'] ?? 1,
				'heading_filter'  => isset( $submitted_widget['heading_filter'] ) ? 'indexed_upper' : 'all',
				'post_types'      => isset( $submitted_widget['post_types'] ) && is_array( $submitted_widget['post_types'] ) ? $submitted_widget['post_types'] : array(),
				'target_slugs'    => $submitted_widget['target_slugs'] ?? '',
				'affiliate_url'   => $submitted_widget['affiliate_url'] ?? '',
				'preview_image_url' => $submitted_widget['preview_image_url'] ?? '',
				'auto_preview'    => isset( $submitted_widget['auto_preview'] ) ? 1 : 0,
				'cta_label'       => $submitted_widget['cta_label'] ?? '',
				'html'            => $html,
			)
		);

		if ( '' === trim( $widget['html'] ) && '' === trim( $widget['title'] ) && '' === trim( $widget['memo_name'] ?? '' ) && '' === $widget['affiliate_url'] && 0 === (int) $widget['enabled'] ) {
			continue;
		}

		$widgets[] = $widget;
	}

	update_option( 'bhdt_wire_in_article_ad_widgets', $widgets, false );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'             => 'bhdt-web-staff',
				'article_ad_saved' => 1,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_in_article_ad_widget_save' );

function bhdt_wirecutter_handle_spam_blacklist_save() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-web-staff' !== $_GET['page'] || ! isset( $_POST['bhdt_save_spam_blacklist'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_spam_blacklist_nonce' );

	update_option(
		'bhdt_wire_comment_spam_blacklist',
		array(
			'ips'      => bhdt_wirecutter_clean_spam_blacklist_lines( isset( $_POST['spam_blacklist_ips'] ) ? wp_unslash( $_POST['spam_blacklist_ips'] ) : '' ),
			'emails'   => bhdt_wirecutter_clean_spam_blacklist_lines( isset( $_POST['spam_blacklist_emails'] ) ? wp_unslash( $_POST['spam_blacklist_emails'] ) : '' ),
			'keywords' => bhdt_wirecutter_clean_spam_blacklist_lines( isset( $_POST['spam_blacklist_keywords'] ) ? wp_unslash( $_POST['spam_blacklist_keywords'] ) : '' ),
		),
		false
	);

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                 => 'bhdt-web-staff',
				'staff_tab'            => 'spam',
				'spam_blacklist_saved' => 1,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_spam_blacklist_save' );

function bhdt_wirecutter_language_tool_dictionary() {
	return array(
		'bai danh gia' => 'Reviews',
		'bai so sanh' => 'Comparisons',
		'cam bien' => 'Sensors',
		'cong cu' => 'Tools',
		'cong nghe' => 'Technology',
		'du an' => 'Projects',
		'du an diy' => 'DIY projects',
		'dien tu' => 'Electronics',
		'firmware' => 'Firmware',
		'giai phap' => 'Solutions',
		'he thong nhung' => 'Embedded systems',
		'linh kien' => 'Components',
		'linh kien dien tu' => 'Electronic components',
		'may do' => 'Measuring instruments',
		'module' => 'Modules',
		'nguon' => 'Power supplies',
		'phu kien' => 'Accessories',
		'robot thong minh' => 'Smart robots',
		'sac' => 'Chargers',
		'so sanh' => 'Comparisons',
		'thiet bi' => 'Devices',
		'thiet bi dien tu' => 'Electronic devices',
		'thiet bi nhung' => 'Embedded devices',
		'tin tuc' => 'News',
		'tu dong hoa' => 'Automation',
		'vi dieu khien' => 'Microcontrollers',
		'vi dieu khien firmware va giai phap nhung toi uu hieu nang' => 'Microcontrollers, firmware, and performance-focused embedded solutions',
		'linh kien module va thiet bi do cho du an thuc te' => 'Recommended components, modules, and measuring tools for real projects',
		'cac thiet bi dien tu' => 'Electronic devices',
		'cac giai phap y tuong thuc tien ap dung cong nghe' => 'Practical technology solutions and ideas',
	);
}

function bhdt_wirecutter_language_tool_normalize_text( $text ) {
	$text = strtolower( remove_accents( wp_strip_all_tags( (string) $text ) ) );
	$text = preg_replace( '/[^a-z0-9]+/', ' ', $text );
	return trim( preg_replace( '/\s+/', ' ', (string) $text ) );
}

function bhdt_wirecutter_language_tool_has_vietnamese_marks( $text ) {
	$text = (string) $text;
	return '' !== $text && remove_accents( $text ) !== $text;
}

function bhdt_wirecutter_language_tool_guess_en( $vi, $slug = '' ) {
	$base = bhdt_wirecutter_language_tool_normalize_text( $vi );
	if ( '' === $base ) {
		$base = bhdt_wirecutter_language_tool_normalize_text( str_replace( '-', ' ', (string) $slug ) );
	}
	if ( '' === $base ) {
		return '';
	}

	$dictionary = bhdt_wirecutter_language_tool_dictionary();
	if ( isset( $dictionary[ $base ] ) ) {
		return $dictionary[ $base ];
	}

	uksort(
		$dictionary,
		static function ( $a, $b ) {
			return strlen( $b ) <=> strlen( $a );
		}
	);

	$guess = $base;
	foreach ( $dictionary as $vi_key => $en_value ) {
		$guess = preg_replace( '/\b' . preg_quote( $vi_key, '/' ) . '\b/', strtolower( $en_value ), $guess );
	}
	$guess = trim( preg_replace( '/\s+/', ' ', (string) $guess ) );
	if ( '' === $guess ) {
		return '';
	}
	if ( preg_match( '/\b(cac|cua|cho|dang|de|duoc|la|nhung|tai|trong|va|voi)\b/', $guess ) ) {
		return '';
	}

	return ucwords( $guess );
}

function bhdt_wirecutter_language_tool_should_fix_en( $vi, $en ) {
	$vi = trim( (string) $vi );
	$en = trim( (string) $en );
	if ( '' === $vi ) {
		return false;
	}
	if ( '' === $en ) {
		return true;
	}
	if ( bhdt_wirecutter_language_tool_normalize_text( $vi ) === bhdt_wirecutter_language_tool_normalize_text( $en ) ) {
		return true;
	}
	return bhdt_wirecutter_language_tool_has_vietnamese_marks( $en );
}

function bhdt_wirecutter_language_tool_add_issue( &$issues, $area, $item, $field, $current, $suggestion, $status, $target = '' ) {
	$issues[] = array(
		'area'       => $area,
		'item'       => $item,
		'field'      => $field,
		'current'    => (string) $current,
		'suggestion' => (string) $suggestion,
		'status'     => $status,
		'target'     => (string) $target,
	);
}

function bhdt_wirecutter_language_tool_scan_rows() {
	$issues = array();
	$stats  = array(
		'checked'     => 0,
		'needs_fix'   => 0,
		'safe_fixes'  => 0,
	);

	$scan_en = static function ( $area, $item, $field, $vi, $en, $slug = '', $target = '' ) use ( &$issues, &$stats ) {
		$stats['checked']++;
		if ( ! bhdt_wirecutter_language_tool_should_fix_en( $vi, $en ) ) {
			return;
		}
		$suggestion = bhdt_wirecutter_language_tool_guess_en( $vi, $slug );
		$status = '' === trim( (string) $en ) ? 'Thiếu EN' : ( bhdt_wirecutter_language_tool_has_vietnamese_marks( $en ) ? 'EN còn dấu tiếng Việt' : 'EN trùng VI' );
		$stats['needs_fix']++;
		if ( '' !== $suggestion ) {
			$stats['safe_fixes']++;
		}
		bhdt_wirecutter_language_tool_add_issue( $issues, $area, $item, $field, $en, $suggestion, $status, $target );
	};

	$home_rows = get_option( 'bhdt_wire_home_category_groups', array() );
	if ( ! is_array( $home_rows ) || empty( $home_rows ) ) {
		$home_rows = bhdt_wirecutter_default_home_category_groups();
	}
	foreach ( (array) $home_rows as $index => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$item = $row['name_vi'] ?? ( $row['label'] ?? 'Khối trang chủ #' . ( $index + 1 ) );
		$scan_en( 'Khối trang chủ', $item, 'Tiêu đề EN', $row['name_vi'] ?? '', $row['name_en'] ?? '', $row['slug'] ?? '', 'home:' . $index . ':name_en' );
		$scan_en( 'Khối trang chủ', $item, 'Mô tả EN', $row['desc_vi'] ?? '', $row['desc_en'] ?? '', $row['slug'] ?? '', 'home:' . $index . ':desc_en' );
		if ( '' === sanitize_title( $row['slug'] ?? '' ) ) {
			$stats['needs_fix']++;
			$stats['safe_fixes']++;
			bhdt_wirecutter_language_tool_add_issue( $issues, 'Khối trang chủ', $item, 'Slug', $row['slug'] ?? '', sanitize_title( $row['name_vi'] ?? $item ), 'Thiếu slug', 'home:' . $index . ':slug' );
		}
	}

	foreach ( bhdt_wirecutter_get_hot_keys( true ) as $index => $row ) {
		$item = $row['label_vi'] ?? 'Từ khóa nóng #' . ( $index + 1 );
		$scan_en( 'Từ khóa nóng', $item, 'Nhãn EN', $row['label_vi'] ?? '', $row['label_en'] ?? '', $row['keyword'] ?? '', 'hot:' . $index . ':label_en' );
	}

	$menu_sources = array(
		'Mega menu'     => array( 'option' => 'bhdt_wire_menu_blocks', 'rows' => bhdt_wirecutter_get_menu_blocks( true, false ) ),
		'Menu chức năng' => array( 'option' => 'bhdt_wire_function_menu_blocks', 'rows' => bhdt_wirecutter_get_function_menu_blocks( true, false ) ),
	);
	foreach ( $menu_sources as $area => $source ) {
		$rows = $source['rows'];
		$option = $source['option'];
		foreach ( $rows as $index => $row ) {
			$item = $row['label_vi'] ?? $area . ' #' . ( $index + 1 );
			$scan_en( $area, $item, 'Mục cha EN', $row['label_vi'] ?? '', $row['label_en'] ?? '', $row['url'] ?? '', 'menu:' . $option . ':' . $index . ':label_en' );
			foreach ( (array) ( $row['children'] ?? array() ) as $child_index => $child ) {
				$child_item = ( $row['label_vi'] ?? $area ) . ' > ' . ( $child['label_vi'] ?? 'Mục con #' . ( $child_index + 1 ) );
				$scan_en( $area, $child_item, 'Mục con EN', $child['label_vi'] ?? '', $child['label_en'] ?? '', $child['url'] ?? '', 'menu:' . $option . ':' . $index . ':child:' . $child_index . ':label_en' );
			}
		}
	}

	return array(
		'issues' => $issues,
		'stats'  => $stats,
	);
}

function bhdt_wirecutter_language_tool_apply_fixes( $requested_fixes = null ) {
	$fixed = 0;
	$requested_fixes = is_array( $requested_fixes ) ? $requested_fixes : null;

	$get_requested_value = static function ( $target ) use ( $requested_fixes ) {
		if ( null === $requested_fixes ) {
			return null;
		}
		$target = (string) $target;
		if ( '' === $target || ! isset( $requested_fixes[ $target ] ) || empty( $requested_fixes[ $target ]['apply'] ) ) {
			return false;
		}
		$value = sanitize_text_field( wp_unslash( $requested_fixes[ $target ]['value'] ?? '' ) );
		return '' !== trim( $value ) ? $value : false;
	};

	$home_rows = get_option( 'bhdt_wire_home_category_groups', array() );
	if ( ! is_array( $home_rows ) || empty( $home_rows ) ) {
		$home_rows = bhdt_wirecutter_default_home_category_groups();
	}
	foreach ( $home_rows as $row_index => &$row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$name_value = $get_requested_value( 'home:' . $row_index . ':name_en' );
		if ( false !== $name_value && null !== $name_value ) {
			$row['name_en'] = $name_value;
			$fixed++;
		} elseif ( null === $requested_fixes && bhdt_wirecutter_language_tool_should_fix_en( $row['name_vi'] ?? '', $row['name_en'] ?? '' ) ) {
			$suggestion = bhdt_wirecutter_language_tool_guess_en( $row['name_vi'] ?? '', $row['slug'] ?? '' );
			if ( '' !== $suggestion ) {
				$row['name_en'] = $suggestion;
				$fixed++;
			}
		}
		$desc_value = $get_requested_value( 'home:' . $row_index . ':desc_en' );
		if ( false !== $desc_value && null !== $desc_value ) {
			$row['desc_en'] = $desc_value;
			$fixed++;
		} elseif ( null === $requested_fixes && bhdt_wirecutter_language_tool_should_fix_en( $row['desc_vi'] ?? '', $row['desc_en'] ?? '' ) ) {
			$suggestion = bhdt_wirecutter_language_tool_guess_en( $row['desc_vi'] ?? '', $row['slug'] ?? '' );
			if ( '' !== $suggestion ) {
				$row['desc_en'] = $suggestion;
				$fixed++;
			}
		}
		$slug_value = $get_requested_value( 'home:' . $row_index . ':slug' );
		if ( false !== $slug_value && null !== $slug_value ) {
			$row['slug'] = sanitize_title( $slug_value );
			$fixed++;
		} elseif ( null === $requested_fixes && '' === sanitize_title( $row['slug'] ?? '' ) && '' !== (string) ( $row['name_vi'] ?? '' ) ) {
			$row['slug'] = sanitize_title( $row['name_vi'] );
			$fixed++;
		}
	}
	unset( $row );
	update_option( 'bhdt_wire_home_category_groups', $home_rows, false );

	$hot_keys = get_option( 'bhdt_wire_hot_keys', array() );
	if ( ! is_array( $hot_keys ) || empty( $hot_keys ) ) {
		$hot_keys = bhdt_wirecutter_default_hot_keys();
	}
	foreach ( $hot_keys as $row_index => &$row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$label_value = $get_requested_value( 'hot:' . $row_index . ':label_en' );
		if ( false !== $label_value && null !== $label_value ) {
			$row['label_en'] = $label_value;
			$fixed++;
		} elseif ( null === $requested_fixes && bhdt_wirecutter_language_tool_should_fix_en( $row['label_vi'] ?? '', $row['label_en'] ?? '' ) ) {
			$suggestion = bhdt_wirecutter_language_tool_guess_en( $row['label_vi'] ?? '', $row['keyword'] ?? '' );
			if ( '' !== $suggestion ) {
				$row['label_en'] = $suggestion;
				$fixed++;
			}
		}
	}
	unset( $row );
	update_option( 'bhdt_wire_hot_keys', bhdt_wirecutter_clean_hot_keys( $hot_keys ), false );

	$menu_options = array(
		'bhdt_wire_menu_blocks'          => array( 'default' => 'bhdt_wirecutter_default_menu_blocks', 'clean' => 'bhdt_wirecutter_clean_menu_blocks' ),
		'bhdt_wire_function_menu_blocks' => array( 'default' => 'bhdt_wirecutter_default_function_menu_blocks', 'clean' => 'bhdt_wirecutter_clean_function_menu_blocks' ),
	);
	foreach ( $menu_options as $option => $callbacks ) {
		$rows = get_option( $option, array() );
		if ( ! is_array( $rows ) || empty( $rows ) ) {
			$rows = call_user_func( $callbacks['default'] );
		}
		foreach ( $rows as $row_index => &$row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label_value = $get_requested_value( 'menu:' . $option . ':' . $row_index . ':label_en' );
			if ( false !== $label_value && null !== $label_value ) {
				$row['label_en'] = $label_value;
				$fixed++;
			} elseif ( null === $requested_fixes && bhdt_wirecutter_language_tool_should_fix_en( $row['label_vi'] ?? '', $row['label_en'] ?? '' ) ) {
				$suggestion = bhdt_wirecutter_language_tool_guess_en( $row['label_vi'] ?? '', $row['url'] ?? '' );
				if ( '' !== $suggestion ) {
					$row['label_en'] = $suggestion;
					$fixed++;
				}
			}
			if ( isset( $row['children'] ) && is_array( $row['children'] ) ) {
				foreach ( $row['children'] as $child_index => &$child ) {
					if ( ! is_array( $child ) ) {
						continue;
					}
					$child_value = $get_requested_value( 'menu:' . $option . ':' . $row_index . ':child:' . $child_index . ':label_en' );
					if ( false !== $child_value && null !== $child_value ) {
						$child['label_en'] = $child_value;
						$fixed++;
					} elseif ( null === $requested_fixes && bhdt_wirecutter_language_tool_should_fix_en( $child['label_vi'] ?? '', $child['label_en'] ?? '' ) ) {
						$suggestion = bhdt_wirecutter_language_tool_guess_en( $child['label_vi'] ?? '', $child['url'] ?? '' );
						if ( '' !== $suggestion ) {
							$child['label_en'] = $suggestion;
							$fixed++;
						}
					}
				}
				unset( $child );
			}
		}
		unset( $row );
		update_option( $option, call_user_func( $callbacks['clean'], $rows ), false );
	}

	return $fixed;
}

function bhdt_wirecutter_handle_language_tool_actions() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-web-staff' !== $_GET['page'] || ! isset( $_POST['bhdt_apply_language_fixes'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_language_tool_nonce' );
	$requested_fixes = isset( $_POST['language_fix'] ) && is_array( $_POST['language_fix'] ) ? wp_unslash( $_POST['language_fix'] ) : array();
	$fixed = bhdt_wirecutter_language_tool_apply_fixes( $requested_fixes );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                 => 'bhdt-web-staff',
				'staff_tab'            => 'language',
				'language_fixes_saved' => (int) $fixed,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_language_tool_actions' );

function bhdt_wirecutter_seo_post_types() {
	return array_values(
		array_filter(
			array( 'post', 'page', 'bhdt_review', 'bhdt_comparison', 'bhdt_project' ),
			'post_type_exists'
		)
	);
}

function bhdt_wirecutter_get_post_seo_description( $post_id ) {
	foreach ( array( '_bhdt_seo_meta_description', '_yoast_wpseo_metadesc', 'rank_math_description' ) as $meta_key ) {
		$value = trim( (string) get_post_meta( $post_id, $meta_key, true ) );
		if ( '' !== $value ) {
			return $value;
		}
	}
	return '';
}

function bhdt_wirecutter_suggest_post_seo_description( $post ) {
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$text = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( (string) $text ) ) ) );
	if ( '' === $text ) {
		$text = get_the_title( $post );
	}

	return sanitize_text_field( wp_trim_words( $text, 26, '' ) );
}

function bhdt_wirecutter_seo_add_issue( &$issues, &$stats, $post, $field, $status, $current, $suggestion = '', $target = '', $severity = 'warning' ) {
	$stats['issues']++;
	if ( '' !== $target && '' !== $suggestion ) {
		$stats['fixable']++;
	}

	$issues[] = array(
		'post_id'    => (int) $post->ID,
		'title'      => get_the_title( $post ),
		'edit_url'   => get_edit_post_link( $post->ID, '' ),
		'type'       => function_exists( 'bhdt_wirecutter_get_post_type_menu_label' ) ? bhdt_wirecutter_get_post_type_menu_label( get_post_type( $post ) ) : get_post_type( $post ),
		'field'      => $field,
		'status'     => $status,
		'current'    => (string) $current,
		'suggestion' => (string) $suggestion,
		'target'     => (string) $target,
		'severity'   => $severity,
	);
}

function bhdt_wirecutter_seo_scan_posts() {
	$issues = array();
	$stats = array(
		'checked' => 0,
		'issues'  => 0,
		'fixable' => 0,
	);

	$posts = get_posts(
		array(
			'post_type'        => bhdt_wirecutter_seo_post_types(),
			'post_status'      => 'publish',
			'posts_per_page'   => 120,
			'orderby'          => 'modified',
			'order'            => 'DESC',
			'suppress_filters' => true,
		)
	);

	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		$stats['checked']++;

		$title = get_the_title( $post );
		$title_len = function_exists( 'mb_strlen' ) ? mb_strlen( $title ) : strlen( $title );
		if ( $title_len < 25 ) {
			bhdt_wirecutter_seo_add_issue( $issues, $stats, $post, 'Tiêu đề', 'Tiêu đề quá ngắn', $title, '', '', 'warning' );
		} elseif ( $title_len > 70 ) {
			bhdt_wirecutter_seo_add_issue( $issues, $stats, $post, 'Tiêu đề', 'Tiêu đề quá dài', $title, '', '', 'warning' );
		}

		$desc = bhdt_wirecutter_get_post_seo_description( $post->ID );
		$desc_len = function_exists( 'mb_strlen' ) ? mb_strlen( $desc ) : strlen( $desc );
		if ( '' === $desc ) {
			bhdt_wirecutter_seo_add_issue( $issues, $stats, $post, 'Mô tả SEO', 'Thiếu mô tả SEO', $desc, bhdt_wirecutter_suggest_post_seo_description( $post ), 'post:' . $post->ID . ':meta_description', 'error' );
		} elseif ( $desc_len < 90 || $desc_len > 170 ) {
			bhdt_wirecutter_seo_add_issue( $issues, $stats, $post, 'Mô tả SEO', $desc_len < 90 ? 'Mô tả SEO quá ngắn' : 'Mô tả SEO quá dài', $desc, bhdt_wirecutter_suggest_post_seo_description( $post ), 'post:' . $post->ID . ':meta_description', 'warning' );
		}

		$slug = (string) $post->post_name;
		if ( '' === $slug || strlen( $slug ) > 80 || preg_match( '/[^a-z0-9\-]/', $slug ) ) {
			bhdt_wirecutter_seo_add_issue( $issues, $stats, $post, 'Slug', '' === $slug ? 'Thiếu slug' : 'Slug cần xem lại', $slug, sanitize_title( $title ), 'post:' . $post->ID . ':slug', 'warning' );
		}

		$content_text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ) ) );
		$word_count = str_word_count( remove_accents( $content_text ) );
		if ( $word_count < 250 && 'page' !== $post->post_type ) {
			bhdt_wirecutter_seo_add_issue( $issues, $stats, $post, 'Nội dung', 'Nội dung mỏng', $word_count . ' từ', '', '', 'warning' );
		}

		if ( post_type_supports( $post->post_type, 'thumbnail' ) && ! has_post_thumbnail( $post ) ) {
			bhdt_wirecutter_seo_add_issue( $issues, $stats, $post, 'Ảnh', 'Thiếu ảnh đại diện', '', '', '', 'warning' );
		}

		if ( is_object_in_taxonomy( $post->post_type, 'category' ) ) {
			$categories = wp_get_object_terms( $post->ID, 'category', array( 'fields' => 'ids' ) );
			if ( empty( $categories ) || is_wp_error( $categories ) ) {
				bhdt_wirecutter_seo_add_issue( $issues, $stats, $post, 'Chuyên mục', 'Thiếu chuyên mục', '', '', '', 'warning' );
			}
		}

		if ( is_object_in_taxonomy( $post->post_type, 'post_tag' ) ) {
			$tags = wp_get_object_terms( $post->ID, 'post_tag', array( 'fields' => 'ids' ) );
			if ( empty( $tags ) || is_wp_error( $tags ) ) {
				bhdt_wirecutter_seo_add_issue( $issues, $stats, $post, 'Tag', 'Thiếu tag', '', '', '', 'notice' );
			}
		}
	}

	return array(
		'issues' => $issues,
		'stats'  => $stats,
	);
}

function bhdt_wirecutter_apply_seo_fixes( $requested_fixes ) {
	$fixed = 0;
	if ( ! is_array( $requested_fixes ) ) {
		return $fixed;
	}

	foreach ( $requested_fixes as $target => $fix ) {
		if ( empty( $fix['apply'] ) ) {
			continue;
		}
		$value = sanitize_text_field( wp_unslash( $fix['value'] ?? '' ) );
		if ( '' === trim( $value ) || ! preg_match( '/^post:(\d+):(meta_description|slug)$/', (string) $target, $matches ) ) {
			continue;
		}
		$post_id = (int) $matches[1];
		$field = $matches[2];
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || ! current_user_can( 'edit_post', $post_id ) ) {
			continue;
		}

		if ( 'meta_description' === $field ) {
			update_post_meta( $post_id, '_bhdt_seo_meta_description', $value );
			update_post_meta( $post_id, '_yoast_wpseo_metadesc', $value );
			update_post_meta( $post_id, 'rank_math_description', $value );
			$fixed++;
			continue;
		}

		if ( 'slug' === $field ) {
			$slug = sanitize_title( $value );
			if ( '' !== $slug && $slug !== $post->post_name ) {
				wp_update_post(
					array(
						'ID'        => $post_id,
						'post_name' => $slug,
					)
				);
				$fixed++;
			}
		}
	}

	return $fixed;
}

function bhdt_wirecutter_handle_seo_tool_actions() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-web-staff' !== $_GET['page'] || ! isset( $_POST['bhdt_apply_seo_fixes'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_seo_tool_nonce' );
	$requested_fixes = isset( $_POST['seo_fix'] ) && is_array( $_POST['seo_fix'] ) ? wp_unslash( $_POST['seo_fix'] ) : array();
	$fixed = bhdt_wirecutter_apply_seo_fixes( $requested_fixes );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'            => 'bhdt-web-staff',
				'staff_tab'       => 'seo',
				'seo_fixes_saved' => (int) $fixed,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_seo_tool_actions' );

function bhdt_wirecutter_print_basic_seo_meta() {
	if ( is_admin() || ! is_singular() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return;
	}
	$description = bhdt_wirecutter_get_post_seo_description( $post_id );
	if ( '' === $description ) {
		$post = get_post( $post_id );
		$description = bhdt_wirecutter_suggest_post_seo_description( $post );
	}
	if ( '' !== $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'bhdt_wirecutter_print_basic_seo_meta', 2 );

function bhdt_wirecutter_cleanup_candidate_paths() {
	return array(
		WP_CONTENT_DIR . '/bhdt-import.log',
		WP_CONTENT_DIR . '/function_menu_debug.log',
		WP_CONTENT_DIR . '/plugins/bhdt-core-system/zenclau_worker_v1.log',
		WP_CONTENT_DIR . '/plugins/bhdt-core-system/__pycache__',
		WP_CONTENT_DIR . '/plugins/bhdt-core-system.zip',
	);
}

function bhdt_wirecutter_cleanup_runtime_scan_key( $token ) {
	return 'bhdt_runtime_cleanup_scan_' . sanitize_key( (string) $token );
}

function bhdt_wirecutter_cleanup_runtime_file_row( $path ) {
	$path = wp_normalize_path( (string) $path );
	if ( '' === $path || ! file_exists( $path ) ) {
		return array();
	}

	$real = wp_normalize_path( realpath( $path ) );
	if ( ! $real ) {
		return array();
	}
	if ( '.env' === basename( $real ) ) {
		return array();
	}

	$type = is_dir( $real ) ? 'directory' : 'file';
	$size = is_dir( $real ) ? bhdt_wirecutter_directory_size( $real ) : (int) filesize( $real );

	return array(
		'path'          => $real,
		'relative_path' => bhdt_wirecutter_cleanup_relative_path( $real ),
		'type'          => $type,
		'size'          => $size,
		'reason'        => bhdt_wirecutter_cleanup_reason_for_path( $real ),
	);
}

function bhdt_wirecutter_directory_size( $dir ) {
	$total = 0;
	if ( ! is_dir( $dir ) ) {
		return $total;
	}

	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ( $items as $item ) {
		if ( $item->isFile() ) {
			$total += (int) $item->getSize();
		}
	}

	return $total;
}

function bhdt_wirecutter_cleanup_relative_path( $path ) {
	$path  = wp_normalize_path( (string) $path );
	$roots = array(
		'wp-content' => wp_normalize_path( WP_CONTENT_DIR ),
		'theme'      => wp_normalize_path( get_template_directory() ),
	);

	foreach ( $roots as $label => $root ) {
		if ( 0 === strpos( $path, $root ) ) {
			return $label . '/' . ltrim( substr( $path, strlen( $root ) ), '/\\' );
		}
	}

	return basename( $path );
}

function bhdt_wirecutter_cleanup_reason_for_path( $path ) {
	$path = wp_normalize_path( (string) $path );

	if ( false !== strpos( $path, '__pycache__' ) ) {
		return 'Python cache directory generated at runtime.';
	}
	if ( preg_match( '/\.(log|zip)$/i', $path ) ) {
		return 'Local runtime log or package artifact.';
	}

	return 'Known local runtime artifact.';
}

function bhdt_wirecutter_scan_cleanup_runtime_files() {
	$files      = array();
	$total_size = 0;

	foreach ( bhdt_wirecutter_cleanup_candidate_paths() as $path ) {
		$row = bhdt_wirecutter_cleanup_runtime_file_row( $path );
		if ( empty( $row ) ) {
			continue;
		}

		$total_size += (int) $row['size'];
		$files[] = $row;
	}

	$token = wp_generate_password( 20, false, false );
	$scan  = array(
		'token'      => $token,
		'files'      => $files,
		'total_size' => $total_size,
		'scanned_at' => current_time( 'mysql' ),
	);
	set_transient( bhdt_wirecutter_cleanup_runtime_scan_key( $token ), $scan, 10 * MINUTE_IN_SECONDS );

	return $scan;
}

function bhdt_wirecutter_get_cleanup_runtime_scan( $token ) {
	$scan = get_transient( bhdt_wirecutter_cleanup_runtime_scan_key( $token ) );
	if ( ! is_array( $scan ) ) {
		return array();
	}

	$files = array();
	$total_size = 0;
	foreach ( (array) ( $scan['files'] ?? array() ) as $file ) {
		$path = wp_normalize_path( (string) ( $file['path'] ?? '' ) );
		if ( '.env' === basename( $path ) ) {
			continue;
		}

		$total_size += (int) ( $file['size'] ?? 0 );
		$files[] = $file;
	}

	$scan['files'] = $files;
	$scan['total_size'] = $total_size;
	return $scan;
}

function bhdt_wirecutter_delete_path_safely( $path ) {
	$path = wp_normalize_path( (string) $path );
	if ( '' === $path || ! file_exists( $path ) ) {
		return false;
	}

	$real = wp_normalize_path( realpath( $path ) );
	if ( ! $real ) {
		return false;
	}
	if ( '.env' === basename( $real ) ) {
		return false;
	}

	$allowed_roots = array(
		wp_normalize_path( WP_CONTENT_DIR ),
		wp_normalize_path( get_template_directory() ),
		wp_normalize_path( WP_CONTENT_DIR . '/plugins/bhdt-core-system' ),
	);

	$allowed = false;
	foreach ( $allowed_roots as $root ) {
		if ( 0 === strpos( $real, trailingslashit( $root ) ) || $real === $root ) {
			$allowed = true;
			break;
		}
	}

	if ( ! $allowed ) {
		return false;
	}

	if ( is_dir( $real ) ) {
		$items = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $real, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $items as $item ) {
			$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
		}
		return rmdir( $real );
	}

	return unlink( $real );
}

function bhdt_wirecutter_cleanup_runtime_files( $token ) {
	$scan = bhdt_wirecutter_get_cleanup_runtime_scan( $token );
	$stats = array(
		'checked' => 0,
		'deleted' => 0,
		'failed'  => 0,
	);

	if ( empty( $scan ) ) {
		return $stats;
	}

	foreach ( (array) ( $scan['files'] ?? array() ) as $file ) {
		$path = $file['path'] ?? '';
		$stats['checked']++;
		if ( file_exists( $path ) && bhdt_wirecutter_delete_path_safely( $path ) ) {
			$stats['deleted']++;
		} else {
			$stats['failed']++;
		}
	}

	delete_transient( bhdt_wirecutter_cleanup_runtime_scan_key( $token ) );

	return $stats;
}

function bhdt_wirecutter_handle_cleanup_runtime_files() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-web-staff' !== $_GET['page'] || ( ! isset( $_POST['bhdt_cleanup_runtime_scan'] ) && ! isset( $_POST['bhdt_cleanup_runtime_confirm'] ) ) ) {
		return;
	}

	if ( isset( $_POST['bhdt_cleanup_runtime_scan'] ) ) {
		check_admin_referer( 'bhdt_cleanup_runtime_scan_nonce' );
		$scan = bhdt_wirecutter_scan_cleanup_runtime_files();
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => 'bhdt-web-staff',
					'cleanup_scan' => $scan['token'],
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	check_admin_referer( 'bhdt_cleanup_runtime_confirm_nonce' );
	$token = isset( $_POST['cleanup_token'] ) ? sanitize_text_field( wp_unslash( $_POST['cleanup_token'] ) ) : '';
	$stats = bhdt_wirecutter_cleanup_runtime_files( $token );
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'            => 'bhdt-web-staff',
				'cleanup_checked' => (int) $stats['checked'],
				'cleanup_deleted' => (int) $stats['deleted'],
				'cleanup_failed'  => (int) $stats['failed'],
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_cleanup_runtime_files' );

function bhdt_wirecutter_pinout_lines_from_text( $value ) {
	$lines = preg_split( '/\r\n|\r|\n|,/', (string) $value );
	$lines = array_map( 'sanitize_text_field', (array) $lines );
	return array_values( array_unique( array_filter( array_map( 'trim', $lines ) ) ) );
}

function bhdt_wirecutter_unique_pinout_slug( $slug, $old_slug = '' ) {
	$slug     = sanitize_title( $slug );
	$old_slug = sanitize_title( $old_slug );
	$library  = bhdt_wirecutter_get_pinout_library();

	if ( '' === $slug ) {
		$slug = 'pinout';
	}

	if ( $slug === $old_slug || ! isset( $library[ $slug ] ) ) {
		return $slug;
	}

	$base = $slug;
	$i    = 2;
	while ( isset( $library[ $base . '-' . $i ] ) ) {
		$i++;
	}

	return $base . '-' . $i;
}

function bhdt_wirecutter_sanitize_pinout_item_from_post( $post_data ) {
	$name = isset( $post_data['name'] ) ? sanitize_text_field( wp_unslash( $post_data['name'] ) ) : '';
	$slug = isset( $post_data['slug'] ) ? sanitize_title( wp_unslash( $post_data['slug'] ) ) : sanitize_title( $name );
	if ( '' === $name ) {
		return null;
	}

	$old_slug = isset( $post_data['old_slug'] ) ? sanitize_title( wp_unslash( $post_data['old_slug'] ) ) : '';
	$slug     = bhdt_wirecutter_unique_pinout_slug( $slug ? $slug : $name, $old_slug );
	$status   = isset( $post_data['status'] ) ? sanitize_key( wp_unslash( $post_data['status'] ) ) : 'placeholder';
	if ( ! in_array( $status, array( 'placeholder', 'need_review', 'verified', 'hidden' ), true ) ) {
		$status = 'placeholder';
	}

	$filename = isset( $post_data['filename'] ) ? sanitize_file_name( wp_unslash( $post_data['filename'] ) ) : '';
	if ( '' === $filename ) {
		$filename = $slug . '-pinout-placeholder.webp';
	}

	$aliases = isset( $post_data['aliases'] ) ? bhdt_wirecutter_pinout_lines_from_text( wp_unslash( $post_data['aliases'] ) ) : array();
	$aliases = array_values( array_unique( array_filter( array_merge( $aliases, array( strtolower( $name ), $slug ) ) ) ) );

	return array(
		'slug'          => $slug,
		'name'          => $name,
		'status'        => $status,
		'filename'      => $filename,
		'image_url'     => isset( $post_data['image_url'] ) ? esc_url_raw( wp_unslash( $post_data['image_url'] ) ) : '',
		'datasheet_url' => isset( $post_data['datasheet_url'] ) ? esc_url_raw( wp_unslash( $post_data['datasheet_url'] ) ) : '',
		'official_url'  => isset( $post_data['official_url'] ) ? esc_url_raw( wp_unslash( $post_data['official_url'] ) ) : '',
		'summary'       => isset( $post_data['summary'] ) ? sanitize_textarea_field( wp_unslash( $post_data['summary'] ) ) : '',
		'specs'         => isset( $post_data['specs'] ) ? bhdt_wirecutter_pinout_lines_from_text( wp_unslash( $post_data['specs'] ) ) : array(),
		'notes'         => isset( $post_data['notes'] ) ? bhdt_wirecutter_pinout_lines_from_text( wp_unslash( $post_data['notes'] ) ) : array(),
		'aliases'       => $aliases,
		'source'        => 'manual',
		'updated_at'    => current_time( 'mysql' ),
	);
}

function bhdt_wirecutter_get_pinout_library() {
	$items = get_option( 'bhdt_pinout_library', array() );
	return is_array( $items ) ? $items : array();
}

function bhdt_wirecutter_pinout_known_terms() {
	return array(
		'ams1117', 'arduino', 'atmega328p', 'ble', 'eeprom', 'esp32', 'esp8266', 'flash', 'i2c', 'lcd', 'lm2596',
		'lora', 'mosfet', 'oled', 'relay', 'servo', 'spi', 'sram', 'stm32', 'transistor', 'uart', 'wifi', 'zigbee',
	);
}

function bhdt_wirecutter_is_pinout_candidate( $name ) {
	$normalized = strtolower( bhdt_wirecutter_normalize_auto_tag_text( $name ) );
	if ( strlen( $normalized ) < 3 || strlen( $normalized ) > 48 ) {
		return false;
	}

	if ( preg_match( '/[0-9]/', $normalized ) || preg_match( '/\b[A-Z]{2,}\b/', (string) $name ) ) {
		return true;
	}

	return in_array( $normalized, bhdt_wirecutter_pinout_known_terms(), true );
}

function bhdt_wirecutter_make_pinout_placeholder( $name, $source = 'auto' ) {
	$name = trim( preg_replace( '/\s+/', ' ', (string) $name ) );
	$slug = sanitize_title( $name );
	if ( '' === $name || '' === $slug ) {
		return null;
	}

	return array(
		'slug'     => $slug,
		'name'     => $name,
		'status'   => 'placeholder',
		'filename' => $slug . '-pinout-placeholder.webp',
		'summary'  => 'Pinout placeholder được tạo tự động từ tags hoặc tiêu đề bài viết. Hãy bổ sung sơ đồ chân, datasheet và ghi chú đấu nối khi có nguồn.',
		'specs'    => array( 'Auto placeholder', 'Cần kiểm tra datasheet' ),
		'notes'    => array( 'Bổ sung chân nguồn VIN/VCC/GND.', 'Bổ sung nhóm tín hiệu I/O hoặc giao tiếp.', 'Kiểm tra điện áp logic trước khi đấu nối.' ),
		'aliases'  => array_values( array_unique( array_filter( array( strtolower( $name ), $slug ) ) ) ),
		'source'   => $source,
	);
}

function bhdt_wirecutter_scan_pinout_library_placeholders() {
	$library = bhdt_wirecutter_get_pinout_library();
	$existing_slugs = array_fill_keys( array( 'esp32', 'lm2596', 'ams1117', 'arduino', 'relay' ), true );
	foreach ( $library as $item ) {
		if ( ! empty( $item['slug'] ) ) {
			$existing_slugs[ sanitize_title( $item['slug'] ) ] = true;
		}
	}

	$post_ids = get_posts(
		array(
			'post_type'        => array( 'post', 'bhdt_review', 'bhdt_comparison' ),
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		)
	);

	$added = 0;
	foreach ( array_map( 'intval', (array) $post_ids ) as $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$candidates = bhdt_wirecutter_build_auto_tags_from_title( $post->post_title );
		$terms = wp_get_object_terms( $post_id, 'post_tag', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $terms ) ) {
			$candidates = array_merge( $candidates, (array) $terms );
		}

		foreach ( array_unique( array_filter( $candidates ) ) as $candidate ) {
			if ( ! bhdt_wirecutter_is_pinout_candidate( $candidate ) ) {
				continue;
			}

			$item = bhdt_wirecutter_make_pinout_placeholder( $candidate, 'post:' . $post_id );
			if ( ! $item || isset( $existing_slugs[ $item['slug'] ] ) ) {
				continue;
			}

			$library[ $item['slug'] ] = $item;
			$existing_slugs[ $item['slug'] ] = true;
			$added++;
			if ( $added >= 80 ) {
				break 2;
			}
		}
	}

	if ( $added > 0 ) {
		update_option( 'bhdt_pinout_library', $library, false );
	}

	return array(
		'added' => $added,
		'total' => count( $library ),
	);
}

function bhdt_wirecutter_handle_pinout_library_scan() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-web-staff' !== $_GET['page'] || ! isset( $_POST['bhdt_scan_pinout_library'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_scan_pinout_library_nonce' );
	$stats = bhdt_wirecutter_scan_pinout_library_placeholders();
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'            => 'bhdt-web-staff',
				'pinout_scanned'  => 1,
				'pinout_added'    => (int) $stats['added'],
				'pinout_total'    => (int) $stats['total'],
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_pinout_library_scan' );

function bhdt_wirecutter_pinout_source_suggestions( $item ) {
	$name  = trim( (string) ( $item['name'] ?? '' ) );
	$slug  = sanitize_title( $item['slug'] ?? $name );
	$query = rawurlencode( $name . ' pinout datasheet official' );
	$links = array(
		'datasheet_url' => 'https://www.google.com/search?q=' . $query . '+filetype%3Apdf',
		'official_url'  => 'https://www.google.com/search?q=' . $query,
	);

	if ( false !== strpos( $slug, 'esp32' ) ) {
		$links['datasheet_url'] = 'https://www.espressif.com/sites/default/files/documentation/esp32_datasheet_en.pdf';
		$links['official_url']  = 'https://docs.espressif.com/projects/esp-idf/en/latest/esp32/hw-reference/index.html';
	} elseif ( false !== strpos( $slug, 'esp8266' ) ) {
		$links['datasheet_url'] = 'https://www.espressif.com/sites/default/files/documentation/0a-esp8266ex_datasheet_en.pdf';
		$links['official_url']  = 'https://docs.espressif.com/projects/esp8266-rtos-sdk/en/latest/';
	} elseif ( false !== strpos( $slug, 'arduino' ) ) {
		$links['official_url']  = 'https://docs.arduino.cc/hardware/';
		$links['datasheet_url'] = 'https://docs.arduino.cc/resources/datasheets/';
	} elseif ( false !== strpos( $slug, 'stm32' ) ) {
		$links['official_url']  = 'https://www.st.com/en/microcontrollers-microprocessors/stm32-32-bit-arm-cortex-mcus.html';
		$links['datasheet_url'] = 'https://www.st.com/content/st_com/en/search.html#q=' . rawurlencode( $name ) . '-t=resources-page=1';
	} elseif ( false !== strpos( $slug, 'lm2596' ) ) {
		$links['datasheet_url'] = 'https://www.ti.com/lit/ds/symlink/lm2596.pdf';
		$links['official_url']  = 'https://www.ti.com/product/LM2596';
	} elseif ( false !== strpos( $slug, 'ams1117' ) ) {
		$links['datasheet_url'] = 'https://www.advanced-monolithic.com/pdf/ds1117.pdf';
		$links['official_url']  = 'https://www.google.com/search?q=' . rawurlencode( 'AMS1117 datasheet official' );
	} elseif ( false !== strpos( $slug, 'raspberry' ) ) {
		$links['official_url']  = 'https://www.raspberrypi.com/documentation/computers/raspberry-pi.html';
		$links['datasheet_url'] = 'https://www.raspberrypi.com/documentation/';
	}

	return $links;
}

function bhdt_wirecutter_handle_pinout_item_actions() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-web-staff' !== $_GET['page'] ) {
		return;
	}

	$library = bhdt_wirecutter_get_pinout_library();

	if ( isset( $_POST['bhdt_save_pinout_item'] ) ) {
		check_admin_referer( 'bhdt_save_pinout_item_nonce' );
		$item = bhdt_wirecutter_sanitize_pinout_item_from_post( $_POST );
		if ( $item ) {
			$old_slug = isset( $_POST['old_slug'] ) ? sanitize_title( wp_unslash( $_POST['old_slug'] ) ) : '';
			if ( $old_slug && $old_slug !== $item['slug'] ) {
				unset( $library[ $old_slug ] );
			}
			$library[ $item['slug'] ] = $item;
			ksort( $library );
			update_option( 'bhdt_pinout_library', $library, false );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=bhdt-web-staff&pinout_saved=1' ) );
		exit;
	}

	if ( isset( $_POST['bhdt_delete_pinout_item'] ) ) {
		check_admin_referer( 'bhdt_delete_pinout_item_nonce' );
		$slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		if ( $slug && isset( $library[ $slug ] ) ) {
			unset( $library[ $slug ] );
			update_option( 'bhdt_pinout_library', $library, false );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=bhdt-web-staff&pinout_deleted=1' ) );
		exit;
	}

	if ( isset( $_POST['bhdt_suggest_pinout_sources'] ) ) {
		check_admin_referer( 'bhdt_suggest_pinout_sources_nonce' );
		$slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		if ( $slug && isset( $library[ $slug ] ) ) {
			$suggestions = bhdt_wirecutter_pinout_source_suggestions( $library[ $slug ] );
			foreach ( $suggestions as $key => $url ) {
				if ( empty( $library[ $slug ][ $key ] ) ) {
					$library[ $slug ][ $key ] = esc_url_raw( $url );
				}
			}
			$library[ $slug ]['status']     = 'need_review';
			$library[ $slug ]['updated_at'] = current_time( 'mysql' );
			update_option( 'bhdt_pinout_library', $library, false );
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'bhdt-web-staff', 'pinout_sourced' => 1, 'pinout_edit' => $slug ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_pinout_item_actions' );

function bhdt_wirecutter_render_category_sync_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập.', 'bhdt-wirecutter' ) );
	}

	$duplicate_groups = bhdt_wirecutter_get_category_duplicate_groups();
	?>
	<div class="wrap">
		<h1>Đồng bộ Categories</h1>
		<?php if ( isset( $_GET['merged'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Đã gộp <?php echo esc_html( (string) (int) $_GET['merged'] ); ?> category phụ vào category chính.</p></div>
		<?php endif; ?>
		<p>Trang này dò các Chuyên mục có tên hiển thị giống nhau. Khi gộp, bài viết sẽ được chuyển sang category chính, category phụ sẽ bị xóa, và liên kết Khối danh mục của theme sẽ được cập nhật theo slug mới.</p>

		<?php if ( empty( $duplicate_groups ) ) : ?>
			<div class="notice notice-success"><p>Không phát hiện Categories trùng tên. Danh mục đang sạch.</p></div>
		<?php else : ?>
			<?php foreach ( $duplicate_groups as $terms ) : ?>
				<?php
				usort(
					$terms,
					static function ( $a, $b ) {
						return ( (int) $b->count <=> (int) $a->count ) ?: strcmp( (string) $a->slug, (string) $b->slug );
					}
				);
				$default_target = (int) $terms[0]->term_id;
				?>
				<div class="postbox" style="padding:16px;margin-top:16px;">
					<h2 style="margin-top:0;">Nhóm trùng: <?php echo esc_html( $terms[0]->name ); ?></h2>
					<form method="post">
						<?php wp_nonce_field( 'bhdt_category_sync_nonce' ); ?>
						<input type="hidden" name="bhdt_merge_categories" value="1">
						<table class="widefat striped">
							<thead>
								<tr>
									<th style="width:90px;">Gộp</th>
									<th style="width:110px;">Giữ lại</th>
									<th>Tên</th>
									<th>Slug</th>
									<th>Số bài</th>
									<th>ID</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $terms as $term ) : ?>
									<tr>
										<td><input type="checkbox" name="source_category_ids[]" value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php checked( (int) $term->term_id !== $default_target ); ?>></td>
										<td><input type="radio" name="target_category_id" value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php checked( (int) $term->term_id, $default_target ); ?>></td>
										<td><a href="<?php echo esc_url( get_edit_term_link( (int) $term->term_id, 'category' ) ); ?>"><?php echo esc_html( $term->name ); ?></a></td>
										<td><code><?php echo esc_html( $term->slug ); ?></code></td>
										<td><?php echo esc_html( (string) (int) $term->count ); ?></td>
										<td><?php echo esc_html( (string) (int) $term->term_id ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<p>
							<button type="submit" class="button button-primary">Gộp category đã chọn</button>
							<span class="description">Nên giữ category có slug đẹp hoặc số bài nhiều nhất.</span>
						</p>
					</form>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
}

function bhdt_wirecutter_default_daily_deals_settings() {
	return array(
		'title'         => 'Ưu đãi hàng ngày',
		'empty_text'    => 'Ưu đãi hàng ngày sẽ hiển thị khi có deal đang bật.',
		'display_count' => 4,
		'sort_by'       => 'newest',
		'deals'         => array(),
	);
}

function bhdt_wirecutter_clean_price_number( $value ) {
	$value = preg_replace( '/[^0-9]/', '', (string) $value );
	return '' !== $value ? (float) $value : 0.0;
}

function bhdt_wirecutter_clean_daily_deals_settings( $settings ) {
	$defaults = bhdt_wirecutter_default_daily_deals_settings();
	$settings = is_array( $settings ) ? $settings : array();

	$clean = array(
		'title'         => isset( $settings['title'] ) ? sanitize_text_field( wp_unslash( $settings['title'] ) ) : $defaults['title'],
		'empty_text'    => isset( $settings['empty_text'] ) ? sanitize_textarea_field( wp_unslash( $settings['empty_text'] ) ) : $defaults['empty_text'],
		'display_count' => isset( $settings['display_count'] ) ? max( 1, min( 24, (int) $settings['display_count'] ) ) : $defaults['display_count'],
		'sort_by'       => isset( $settings['sort_by'] ) ? sanitize_key( wp_unslash( $settings['sort_by'] ) ) : $defaults['sort_by'],
		'deals'         => array(),
	);

	$allowed_sorts = array( 'newest', 'oldest', 'price_low', 'price_high', 'source' );
	if ( ! in_array( $clean['sort_by'], $allowed_sorts, true ) ) {
		$clean['sort_by'] = $defaults['sort_by'];
	}

	$rows = isset( $settings['deals'] ) && is_array( $settings['deals'] ) ? $settings['deals'] : array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$name = isset( $row['name'] ) ? sanitize_text_field( wp_unslash( $row['name'] ) ) : '';
		$link = isset( $row['link'] ) ? esc_url_raw( wp_unslash( $row['link'] ) ) : '';
		if ( '' === $name && '' === $link ) {
			continue;
		}

		$created_at = isset( $row['created_at'] ) ? sanitize_text_field( wp_unslash( $row['created_at'] ) ) : '';
		if ( '' === $created_at ) {
			$created_at = current_time( 'mysql' );
		}

		$clean['deals'][] = array(
			'enabled'       => ! empty( $row['enabled'] ) ? 1 : 0,
			'name'          => $name,
			'image'         => isset( $row['image'] ) ? esc_url_raw( wp_unslash( $row['image'] ) ) : '',
			'regular_price' => isset( $row['regular_price'] ) ? sanitize_text_field( wp_unslash( $row['regular_price'] ) ) : '',
			'sale_price'    => isset( $row['sale_price'] ) ? sanitize_text_field( wp_unslash( $row['sale_price'] ) ) : '',
			'link'          => $link,
			'source'        => isset( $row['source'] ) ? sanitize_text_field( wp_unslash( $row['source'] ) ) : '',
			'created_at'    => $created_at,
			'order'         => isset( $row['order'] ) ? (int) $row['order'] : 0,
		);
	}

	return $clean;
}

function bhdt_wirecutter_get_daily_deals_settings() {
	$saved = get_option( 'bhdt_wire_daily_deals', array() );
	return array_merge(
		bhdt_wirecutter_default_daily_deals_settings(),
		bhdt_wirecutter_clean_daily_deals_settings( is_array( $saved ) ? $saved : array() )
	);
}

function bhdt_wirecutter_get_active_daily_deals() {
	$settings = bhdt_wirecutter_get_daily_deals_settings();
	$deals = array_values(
		array_filter(
			(array) $settings['deals'],
			static function ( $deal ) {
				return ! empty( $deal['enabled'] ) && ! empty( $deal['name'] ) && ! empty( $deal['link'] );
			}
		)
	);

	usort(
		$deals,
		static function ( $a, $b ) use ( $settings ) {
			switch ( $settings['sort_by'] ) {
				case 'oldest':
					return strtotime( $a['created_at'] ?? '' ) <=> strtotime( $b['created_at'] ?? '' );
				case 'price_low':
					return bhdt_wirecutter_clean_price_number( $a['sale_price'] ?? '' ) <=> bhdt_wirecutter_clean_price_number( $b['sale_price'] ?? '' );
				case 'price_high':
					return bhdt_wirecutter_clean_price_number( $b['sale_price'] ?? '' ) <=> bhdt_wirecutter_clean_price_number( $a['sale_price'] ?? '' );
				case 'source':
					return strcasecmp( (string) ( $a['source'] ?? '' ), (string) ( $b['source'] ?? '' ) );
				case 'newest':
				default:
					return strtotime( $b['created_at'] ?? '' ) <=> strtotime( $a['created_at'] ?? '' );
			}
		}
	);

	return array_slice( $deals, 0, (int) $settings['display_count'] );
}

function bhdt_wirecutter_handle_daily_deals_save() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-daily-deals' !== $_GET['page'] ) {
		return;
	}

	if ( ! isset( $_POST['bhdt_save_daily_deals'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_save_daily_deals_nonce' );
	update_option( 'bhdt_wire_daily_deals', bhdt_wirecutter_clean_daily_deals_settings( $_POST ), false );
	wp_safe_redirect( admin_url( 'themes.php?page=bhdt-daily-deals&updated=1' ) );
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_daily_deals_save' );

function bhdt_wirecutter_render_daily_deals_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập.', 'bhdt-wirecutter' ) );
	}

	$settings = bhdt_wirecutter_get_daily_deals_settings();
	$deals = ! empty( $settings['deals'] ) ? $settings['deals'] : array(
		array(
			'enabled'       => 1,
			'name'          => '',
			'image'         => '',
			'regular_price' => '',
			'sale_price'    => '',
			'link'          => '',
			'source'        => '',
			'created_at'    => current_time( 'mysql' ),
			'order'         => 0,
		),
	);
	?>
	<div class="wrap">
		<h1>Daily Deals</h1>
		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Đã lưu Daily Deals.</p></div>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'bhdt_save_daily_deals_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="bhdt-daily-deals-title">Tiêu đề khối</label></th>
					<td><input class="regular-text" id="bhdt-daily-deals-title" name="title" type="text" value="<?php echo esc_attr( $settings['title'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="bhdt-daily-deals-empty-text">Nội dung khi chưa có deal</label></th>
					<td>
						<textarea class="large-text" rows="3" id="bhdt-daily-deals-empty-text" name="empty_text"><?php echo esc_textarea( $settings['empty_text'] ); ?></textarea>
						<p class="description">Dòng này hiển thị khi chưa có deal nào đang bật và không có WooCommerce sale để fallback.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bhdt-daily-deals-count">Số deal hiển thị</label></th>
					<td><input id="bhdt-daily-deals-count" name="display_count" type="number" min="1" max="24" value="<?php echo esc_attr( (int) $settings['display_count'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="bhdt-daily-deals-sort">Sắp xếp</label></th>
					<td>
						<select id="bhdt-daily-deals-sort" name="sort_by">
							<option value="newest" <?php selected( $settings['sort_by'], 'newest' ); ?>>Mới nhất</option>
							<option value="oldest" <?php selected( $settings['sort_by'], 'oldest' ); ?>>Cũ nhất</option>
							<option value="price_low" <?php selected( $settings['sort_by'], 'price_low' ); ?>>Giá thấp trước</option>
							<option value="price_high" <?php selected( $settings['sort_by'], 'price_high' ); ?>>Giá cao trước</option>
							<option value="source" <?php selected( $settings['sort_by'], 'source' ); ?>>Theo nguồn</option>
						</select>
					</td>
				</tr>
			</table>

			<h2>Danh sách deal</h2>
			<table class="widefat striped" id="bhdt-daily-deals-table">
				<thead>
					<tr>
						<th>Bật</th>
						<th>Tên deal</th>
						<th>Ảnh URL</th>
						<th>Giá cũ</th>
						<th>Giá mới</th>
						<th>Link mua</th>
						<th>Nguồn</th>
						<th>Thời gian</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $deals as $index => $deal ) : ?>
						<tr>
							<td><input name="deals[<?php echo esc_attr( $index ); ?>][enabled]" type="checkbox" value="1" <?php checked( ! empty( $deal['enabled'] ) ); ?>></td>
							<td><input name="deals[<?php echo esc_attr( $index ); ?>][name]" type="text" value="<?php echo esc_attr( $deal['name'] ?? '' ); ?>" style="width:180px;"></td>
							<td><input name="deals[<?php echo esc_attr( $index ); ?>][image]" type="url" value="<?php echo esc_url( $deal['image'] ?? '' ); ?>" style="width:170px;"></td>
							<td><input name="deals[<?php echo esc_attr( $index ); ?>][regular_price]" type="text" value="<?php echo esc_attr( $deal['regular_price'] ?? '' ); ?>" style="width:90px;"></td>
							<td><input name="deals[<?php echo esc_attr( $index ); ?>][sale_price]" type="text" value="<?php echo esc_attr( $deal['sale_price'] ?? '' ); ?>" style="width:90px;"></td>
							<td><input name="deals[<?php echo esc_attr( $index ); ?>][link]" type="url" value="<?php echo esc_url( $deal['link'] ?? '' ); ?>" style="width:180px;"></td>
							<td><input name="deals[<?php echo esc_attr( $index ); ?>][source]" type="text" value="<?php echo esc_attr( $deal['source'] ?? '' ); ?>" placeholder="Shopee" style="width:95px;"></td>
							<td><input name="deals[<?php echo esc_attr( $index ); ?>][created_at]" type="datetime-local" value="<?php echo esc_attr( date( 'Y-m-d\TH:i', strtotime( $deal['created_at'] ?? current_time( 'mysql' ) ) ) ); ?>" style="width:155px;"></td>
							<td><button type="button" class="button bhdt-remove-daily-deal">Xóa</button></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p><button type="button" class="button" id="bhdt-add-daily-deal">Thêm deal</button></p>
			<?php submit_button( 'Lưu Daily Deals', 'primary', 'bhdt_save_daily_deals' ); ?>
		</form>
	</div>
	<script>
	(function () {
		const table = document.getElementById('bhdt-daily-deals-table');
		const add = document.getElementById('bhdt-add-daily-deal');
		if (!table || !add) return;
		const tbody = table.querySelector('tbody');
		const now = () => new Date().toISOString().slice(0, 16);
		const rowHtml = (index) => `
			<tr>
				<td><input name="deals[${index}][enabled]" type="checkbox" value="1" checked></td>
				<td><input name="deals[${index}][name]" type="text" style="width:180px;"></td>
				<td><input name="deals[${index}][image]" type="url" style="width:170px;"></td>
				<td><input name="deals[${index}][regular_price]" type="text" style="width:90px;"></td>
				<td><input name="deals[${index}][sale_price]" type="text" style="width:90px;"></td>
				<td><input name="deals[${index}][link]" type="url" style="width:180px;"></td>
				<td><input name="deals[${index}][source]" type="text" placeholder="Shopee" style="width:95px;"></td>
				<td><input name="deals[${index}][created_at]" type="datetime-local" value="${now()}" style="width:155px;"></td>
				<td><button type="button" class="button bhdt-remove-daily-deal">Xóa</button></td>
			</tr>`;
		add.addEventListener('click', () => {
			tbody.insertAdjacentHTML('beforeend', rowHtml(tbody.querySelectorAll('tr').length));
		});
		tbody.addEventListener('click', (event) => {
			if (event.target.classList.contains('bhdt-remove-daily-deal')) {
				event.target.closest('tr').remove();
			}
		});
	})();
	</script>
	<?php
}

function bhdt_wirecutter_default_footer_settings() {
	return array(
		'use_widgets' => 0,
		'logo_text'   => 'Banhangdientu',
		'logo_image'  => '',
		'description' => 'Bố cục biên tập gọn gàng, cảm hứng từ Wirecutter.',
		'middle_text' => 'Hướng dẫn thực tế, đánh giá linh kiện, ghi chú dự án.',
		'copyright'   => '© {year} Banhangdientu',
		'right_text'  => '',
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

	$clean['use_widgets'] = ! empty( $settings['use_widgets'] ) ? 1 : 0;

	foreach ( array( 'logo_text', 'copyright' ) as $key ) {
		$clean[ $key ] = isset( $settings[ $key ] ) ? sanitize_text_field( wp_unslash( $settings[ $key ] ) ) : $defaults[ $key ];
	}

	foreach ( array( 'description', 'middle_text', 'right_text' ) as $key ) {
		$clean[ $key ] = isset( $settings[ $key ] ) ? sanitize_textarea_field( wp_unslash( $settings[ $key ] ) ) : $defaults[ $key ];
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

function bhdt_wirecutter_required_footer_pages() {
	return array(
		'gioi-thieu' => array(
			'title'   => 'Giới thiệu',
			'content' => '<h2>Giới thiệu</h2><p>Banhangdientu là website chia sẻ thông tin, hướng dẫn, đánh giá và gợi ý sản phẩm trong lĩnh vực linh kiện điện tử, thiết bị đo lường, dự án DIY và công nghệ ứng dụng.</p><p>Nội dung trên website được biên tập nhằm hỗ trợ người đọc tham khảo trước khi lựa chọn sản phẩm hoặc triển khai dự án. Chúng tôi luôn cố gắng cập nhật thông tin rõ ràng, thực tế và hữu ích.</p>',
		),
		'lien-he' => array(
			'title'   => 'Liên hệ',
			'content' => '<h2>Liên hệ</h2><p>Nếu bạn cần trao đổi về nội dung, hợp tác, quảng cáo hoặc góp ý cho website, vui lòng gửi thông tin qua biểu mẫu bên dưới.</p><p>Chúng tôi sẽ phản hồi trong thời gian sớm nhất có thể.</p>[bhdt_contact_form]',
		),
		'chinh-sach-bao-mat' => array(
			'title'   => 'Chính sách bảo mật',
			'content' => '<h2>Chính sách bảo mật</h2><p>Chúng tôi tôn trọng quyền riêng tư của người dùng. Website có thể thu thập một số dữ liệu kỹ thuật cơ bản như địa chỉ IP, trình duyệt, thiết bị, trang đã xem và cookie để vận hành, phân tích lưu lượng và cải thiện trải nghiệm.</p><p>Nếu website hiển thị quảng cáo hoặc liên kết tiếp thị liên kết, các đối tác bên thứ ba có thể sử dụng cookie theo chính sách riêng của họ. Người dùng có thể quản lý cookie trong trình duyệt của mình.</p><p>Chúng tôi không bán thông tin cá nhân của người dùng. Các thông tin gửi qua bình luận hoặc liên hệ chỉ được sử dụng để phản hồi và quản trị website.</p>',
		),
		'dieu-khoan-su-dung' => array(
			'title'   => 'Điều khoản sử dụng',
			'content' => '<h2>Điều khoản sử dụng</h2><p>Khi truy cập và sử dụng website, bạn đồng ý tuân thủ các điều khoản này. Nội dung trên website chỉ nhằm mục đích tham khảo, không thay thế tư vấn kỹ thuật, pháp lý hoặc chuyên môn riêng cho từng trường hợp.</p><p>Người dùng không được đăng tải nội dung spam, vi phạm pháp luật, xâm phạm quyền sở hữu trí tuệ hoặc gây ảnh hưởng đến hoạt động của website.</p><p>Chúng tôi có thể cập nhật nội dung, chính sách và điều khoản khi cần thiết để phù hợp với hoạt động của website.</p>',
		),
	);
}

function bhdt_wirecutter_find_page_by_slug_or_title( $slug, $title ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $page instanceof WP_Post ) {
		return $page;
	}

	$pages = get_posts(
		array(
			'post_type'        => 'page',
			'post_status'      => array( 'publish', 'draft', 'private' ),
			'title'            => $title,
			'posts_per_page'   => 1,
			'suppress_filters' => true,
		)
	);

	return ! empty( $pages[0] ) && $pages[0] instanceof WP_Post ? $pages[0] : null;
}

function bhdt_wirecutter_ensure_required_footer_pages() {
	if ( wp_installing() ) {
		return;
	}

	$page_ids = get_option( 'bhdt_required_footer_page_ids', array() );
	$page_ids = is_array( $page_ids ) ? $page_ids : array();
	$changed = false;

	foreach ( bhdt_wirecutter_required_footer_pages() as $slug => $page_data ) {
		$page = null;
		if ( ! empty( $page_ids[ $slug ] ) ) {
			$stored_page = get_post( (int) $page_ids[ $slug ] );
			if ( $stored_page instanceof WP_Post && 'page' === $stored_page->post_type && 'trash' !== $stored_page->post_status ) {
				$page = $stored_page;
			}
		}
		if ( ! $page ) {
			$page = bhdt_wirecutter_find_page_by_slug_or_title( $slug, $page_data['title'] );
		}
		if ( ! $page ) {
			$page_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $page_data['title'],
					'post_name'    => $slug,
					'post_content' => $page_data['content'],
				),
				true
			);
			if ( ! is_wp_error( $page_id ) && $page_id ) {
				$page_ids[ $slug ] = (int) $page_id;
				$changed = true;
			}
			continue;
		}

		$page_ids[ $slug ] = (int) $page->ID;
		$changed = true;

		if ( 'lien-he' === $slug && false === strpos( (string) $page->post_content, '[bhdt_contact_form]' ) ) {
			wp_update_post(
				array(
					'ID'           => (int) $page->ID,
					'post_content' => rtrim( (string) $page->post_content ) . "\n\n[bhdt_contact_form]",
				)
			);
		}
	}

	if ( ! empty( $page_ids['chinh-sach-bao-mat'] ) ) {
		update_option( 'wp_page_for_privacy_policy', (int) $page_ids['chinh-sach-bao-mat'] );
	}
	if ( $changed ) {
		update_option( 'bhdt_required_footer_page_ids', $page_ids, false );
	}
}
add_action( 'init', 'bhdt_wirecutter_ensure_required_footer_pages', 30 );

function bhdt_wirecutter_get_contact_submissions() {
	$items = get_option( 'bhdt_contact_submissions', array() );
	return is_array( $items ) ? array_values( $items ) : array();
}

function bhdt_wirecutter_count_unread_contact_submissions() {
	return count(
		array_filter(
			bhdt_wirecutter_get_contact_submissions(),
			static function ( $item ) {
				return 'unread' === ( $item['status'] ?? '' );
			}
		)
	);
}

function bhdt_wirecutter_save_contact_submissions( $items ) {
	$items = is_array( $items ) ? array_values( $items ) : array();
	usort(
		$items,
		static function ( $a, $b ) {
			return (int) ( $b['created_ts'] ?? 0 ) <=> (int) ( $a['created_ts'] ?? 0 );
		}
	);
	update_option( 'bhdt_contact_submissions', array_slice( $items, 0, 300 ), false );
}

function bhdt_wirecutter_contact_page_url() {
	$page_ids = get_option( 'bhdt_required_footer_page_ids', array() );
	$page_id = is_array( $page_ids ) ? (int) ( $page_ids['lien-he'] ?? 0 ) : 0;
	$page = $page_id ? get_post( $page_id ) : bhdt_wirecutter_find_page_by_slug_or_title( 'lien-he', 'Liên hệ' );

	return $page instanceof WP_Post ? get_permalink( $page ) : home_url( '/lien-he/' );
}

function bhdt_wirecutter_render_contact_form_shortcode() {
	$sent = isset( $_GET['contact_sent'] ) ? sanitize_key( wp_unslash( $_GET['contact_sent'] ) ) : '';
	ob_start();
	?>
	<section class="bhdt-contact-form-wrap">
		<?php if ( '1' === $sent ) : ?>
			<div class="bhdt-contact-notice bhdt-contact-notice-success">Cảm ơn bạn. Nội dung liên hệ đã được gửi.</div>
		<?php elseif ( 'error' === $sent ) : ?>
			<div class="bhdt-contact-notice bhdt-contact-notice-error">Chưa gửi được liên hệ. Vui lòng kiểm tra lại thông tin và thử lại.</div>
		<?php endif; ?>
		<form class="bhdt-contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="bhdt_contact_submit">
			<input type="hidden" name="bhdt_contact_redirect" value="<?php echo esc_url( get_permalink() ?: bhdt_wirecutter_contact_page_url() ); ?>">
			<?php wp_nonce_field( 'bhdt_contact_submit_nonce', 'bhdt_contact_nonce' ); ?>
			<p class="bhdt-contact-hp" aria-hidden="true">
				<label for="bhdt_contact_company">Công ty</label>
				<input type="text" id="bhdt_contact_company" name="bhdt_contact_company" value="" tabindex="-1" autocomplete="off">
			</p>
			<div class="bhdt-contact-grid">
				<label>
					<span>Họ tên</span>
					<input type="text" name="contact_name" required maxlength="120" autocomplete="name">
				</label>
				<label>
					<span>Email</span>
					<input type="email" name="contact_email" required maxlength="160" autocomplete="email">
				</label>
				<label>
					<span>Số điện thoại</span>
					<input type="text" name="contact_phone" maxlength="60" autocomplete="tel">
				</label>
				<label>
					<span>Chủ đề</span>
					<input type="text" name="contact_subject" maxlength="180">
				</label>
			</div>
			<label>
				<span>Nội dung</span>
				<textarea name="contact_message" rows="7" required maxlength="5000"></textarea>
			</label>
			<button type="submit">Gửi liên hệ</button>
		</form>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'bhdt_contact_form', 'bhdt_wirecutter_render_contact_form_shortcode' );

function bhdt_wirecutter_handle_contact_submit() {
	$redirect = isset( $_POST['bhdt_contact_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['bhdt_contact_redirect'] ) ) : bhdt_wirecutter_contact_page_url();
	$error_url = add_query_arg( 'contact_sent', 'error', $redirect );

	if ( ! isset( $_POST['bhdt_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bhdt_contact_nonce'] ) ), 'bhdt_contact_submit_nonce' ) ) {
		wp_safe_redirect( $error_url );
		exit;
	}

	$honeypot = isset( $_POST['bhdt_contact_company'] ) ? trim( (string) wp_unslash( $_POST['bhdt_contact_company'] ) ) : '';
	$name = isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '';
	$email = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';
	$phone = isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '';
	$subject = isset( $_POST['contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_subject'] ) ) : '';
	$message = isset( $_POST['contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ) ) : '';

	if ( '' !== $honeypot || '' === $name || '' === $email || ! is_email( $email ) || '' === $message ) {
		wp_safe_redirect( $error_url );
		exit;
	}

	$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
	$rate_key = 'bhdt_contact_rate_' . md5( $ip . '|' . strtolower( $email ) );
	if ( get_transient( $rate_key ) ) {
		wp_safe_redirect( $error_url );
		exit;
	}
	set_transient( $rate_key, 1, 2 * MINUTE_IN_SECONDS );

	$items = bhdt_wirecutter_get_contact_submissions();
	$items[] = array(
		'id'         => wp_generate_uuid4(),
		'created_ts' => time(),
		'created_at' => current_time( 'mysql' ),
		'status'     => 'unread',
		'name'       => $name,
		'email'      => $email,
		'phone'      => $phone,
		'subject'    => $subject,
		'message'    => $message,
		'ip'         => $ip,
		'user_agent' => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ),
	);
	bhdt_wirecutter_save_contact_submissions( $items );

	wp_safe_redirect( add_query_arg( 'contact_sent', '1', $redirect ) );
	exit;
}
add_action( 'admin_post_nopriv_bhdt_contact_submit', 'bhdt_wirecutter_handle_contact_submit' );
add_action( 'admin_post_bhdt_contact_submit', 'bhdt_wirecutter_handle_contact_submit' );

function bhdt_wirecutter_handle_contact_admin_actions() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-web-staff' !== $_GET['page'] || ! isset( $_POST['bhdt_contact_bulk_update'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_contact_admin_nonce' );
	$action = isset( $_POST['contact_bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['contact_bulk_action'] ) ) : 'read';
	$ids = isset( $_POST['contact_ids'] ) && is_array( $_POST['contact_ids'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['contact_ids'] ) ) : array();
	$ids = array_fill_keys( array_filter( $ids ), true );
	$updated = 0;

	if ( empty( $ids ) ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'bhdt-web-staff', 'staff_tab' => 'steward', 'steward_section' => 'contact', 'contact_updated' => 0 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	$items = bhdt_wirecutter_get_contact_submissions();
	$next_items = array();
	foreach ( $items as $item ) {
		$id = (string) ( $item['id'] ?? '' );
		if ( '' === $id || ! isset( $ids[ $id ] ) ) {
			$next_items[] = $item;
			continue;
		}

		$updated++;
		if ( 'delete' === $action ) {
			continue;
		}

		$item['status'] = 'unread' === $action ? 'unread' : 'read';
		$next_items[] = $item;
	}
	bhdt_wirecutter_save_contact_submissions( $next_items );

	wp_safe_redirect( add_query_arg( array( 'page' => 'bhdt-web-staff', 'staff_tab' => 'steward', 'steward_section' => 'contact', 'contact_updated' => $updated ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_contact_admin_actions' );

function bhdt_wirecutter_get_required_footer_page( $slug ) {
	$slug = sanitize_title( (string) $slug );
	$pages = bhdt_wirecutter_required_footer_pages();
	if ( ! isset( $pages[ $slug ] ) ) {
		return null;
	}

	$page_ids = get_option( 'bhdt_required_footer_page_ids', array() );
	$page_id = is_array( $page_ids ) ? (int) ( $page_ids[ $slug ] ?? 0 ) : 0;
	$page = $page_id ? get_post( $page_id ) : null;
	if ( $page instanceof WP_Post && 'page' === $page->post_type && 'trash' !== $page->post_status ) {
		return $page;
	}

	return bhdt_wirecutter_find_page_by_slug_or_title( $slug, $pages[ $slug ]['title'] );
}

function bhdt_wirecutter_clean_steward_page_content( $content ) {
	$content = (string) $content;

	for ( $i = 0; $i < 3 && false !== strpos( $content, '&lt;' ); $i++ ) {
		$content = wp_specialchars_decode( $content, ENT_QUOTES );
	}

	$content = preg_replace( '/<span\b[^>]*(?:data-mce-type=["\']bookmark["\']|mce_SELRES_(?:start|end))[^>]*>\s*<\/span>/i', '', $content );
	$content = preg_replace( '/<span\b[^>]*(?:data-mce-type=&quot;bookmark&quot;|mce_SELRES_(?:start|end))[^>]*>\s*<\/span>/i', '', $content );
	$content = preg_replace( '/\sdata-(?:path-to-node|index-in-node|mce-[a-z0-9_-]+)="[^"]*"/i', '', $content );
	$content = preg_replace( "/\sdata-(?:path-to-node|index-in-node|mce-[a-z0-9_-]+)='[^']*'/i", '', $content );
	$content = preg_replace( '/\sdata-(?:path-to-node|index-in-node|mce-[a-z0-9_-]+)=&quot;[^&]*&quot;/i', '', $content );
	$content = preg_replace( '/\sclass="[^"]*\bmce_SELRES_(?:start|end)\b[^"]*"/i', '', $content );
	$content = preg_replace( "/\sclass='[^']*\bmce_SELRES_(?:start|end)\b[^']*'/i", '', $content );
	$content = preg_replace( '/\sclass=&quot;[^&]*\bmce_SELRES_(?:start|end)\b[^&]*&quot;/i', '', $content );
	$content = preg_replace( '/\sstyle="display:\s*inline-block;\s*width:\s*0px;\s*overflow:\s*hidden;\s*line-height:\s*0;?"/i', '', $content );
	$content = preg_replace( '/\sstyle=&quot;display:\s*inline-block;\s*width:\s*0px;\s*overflow:\s*hidden;\s*line-height:\s*0;?&quot;/i', '', $content );
	$content = preg_replace( '/<span\b[^>]*>\s*<\/span>/i', '', $content );

	return trim( (string) $content );
}

function bhdt_wirecutter_handle_steward_effects_save() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-web-staff' !== $_GET['page'] || ! isset( $_POST['bhdt_steward_effects_save'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_steward_effects_nonce' );
	update_option( 'bhdt_wire_menu_ant_enabled', isset( $_POST['menu_ant_enabled'] ) ? '1' : '0', false );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                  => 'bhdt-web-staff',
				'staff_tab'             => 'steward',
				'steward_section'       => 'effects',
				'steward_effects_saved' => 1,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_steward_effects_save' );

function bhdt_wirecutter_handle_steward_page_save() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['page'] ) || 'bhdt-web-staff' !== $_GET['page'] || ! isset( $_POST['bhdt_steward_page_save'] ) ) {
		return;
	}

	check_admin_referer( 'bhdt_steward_page_nonce' );
	$section = isset( $_POST['steward_section'] ) ? sanitize_key( wp_unslash( $_POST['steward_section'] ) ) : 'about';
	$page_id = isset( $_POST['page_id'] ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0;
	$title = isset( $_POST['page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['page_title'] ) ) : '';
	$content = isset( $_POST['page_content'] ) ? bhdt_wirecutter_clean_steward_page_content( wp_unslash( $_POST['page_content'] ) ) : '';
	$content = wp_kses_post( $content );

	if ( $page_id && current_user_can( 'edit_post', $page_id ) ) {
		wp_update_post(
			array(
				'ID'           => $page_id,
				'post_title'   => '' !== $title ? $title : get_the_title( $page_id ),
				'post_content' => $content,
			)
		);
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                => 'bhdt-web-staff',
				'staff_tab'           => 'steward',
				'steward_section'     => $section,
				'steward_page_saved'  => 1,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'bhdt_wirecutter_handle_steward_page_save' );

function bhdt_wirecutter_get_required_footer_links() {
	$page_ids = get_option( 'bhdt_required_footer_page_ids', array() );
	$page_ids = is_array( $page_ids ) ? $page_ids : array();
	$links = array();

	foreach ( bhdt_wirecutter_required_footer_pages() as $slug => $page_data ) {
		$page = ! empty( $page_ids[ $slug ] ) ? get_post( (int) $page_ids[ $slug ] ) : null;
		if ( ! $page instanceof WP_Post || 'trash' === $page->post_status ) {
			$page = bhdt_wirecutter_find_page_by_slug_or_title( $slug, $page_data['title'] );
		}

		$links[] = array(
			'label' => $page_data['title'],
			'url'   => $page instanceof WP_Post ? get_permalink( $page ) : home_url( '/' . $slug . '/' ),
		);
	}

	return $links;
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
					<th scope="row">Nguồn nội dung footer</th>
					<td>
						<label>
							<input name="use_widgets" type="checkbox" value="1" <?php checked( ! empty( $settings['use_widgets'] ) ); ?>>
							Dùng Widgets footer thay cho nội dung Footer Settings
						</label>
						<p class="description">Khi bật, footer sẽ lấy nội dung từ Giao diện &gt; Widgets: Cột chân trang 1, 2, 3. Logo, mô tả, copyright và social links bên dưới sẽ không render ra trang.</p>
					</td>
				</tr>
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
					<th scope="row"><label for="bhdt-footer-right-text">Noi dung cot phai</label></th>
					<td>
						<textarea class="large-text" rows="4" id="bhdt-footer-right-text" name="right_text" placeholder="Email, so dien thoai, dia chi, ghi chu lien he..."><?php echo esc_textarea( $settings['right_text'] ); ?></textarea>
						<p class="description">Noi dung nay hien thi duoi copyright va social links o cot 3.</p>
					</td>
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

function bhdt_wirecutter_menu_landing_key( $type, $parent_label, $label, $level ) {
	return sanitize_key( $type ) . '|' . sanitize_key( $level ) . '|' . sanitize_title( (string) $parent_label ) . '|' . sanitize_title( (string) $label );
}

function bhdt_wirecutter_menu_landing_slug_conflicts( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug ) {
		return false;
	}

	$post_types = get_post_types( array( 'public' => true ), 'names' );
	if ( get_page_by_path( $slug, OBJECT, array_values( $post_types ) ) ) {
		return true;
	}

	return false;
}

function bhdt_wirecutter_unique_menu_landing_slug( $base_slug, &$reserved_slugs ) {
	$base_slug = sanitize_title( (string) $base_slug );
	if ( '' === $base_slug ) {
		$base_slug = 'menu';
	}

	$slug   = $base_slug;
	$suffix = 2;
	while ( isset( $reserved_slugs[ $slug ] ) || bhdt_wirecutter_menu_landing_slug_conflicts( $slug ) ) {
		$slug = $base_slug . '-' . $suffix;
		$suffix++;
	}

	$reserved_slugs[ $slug ] = true;
	return $slug;
}

function bhdt_wirecutter_get_menu_landing_slug_map() {
	static $map = null;

	if ( null !== $map ) {
		return $map;
	}

	$map = array();
	$reserved_slugs = array();
	$sources = array(
		'mega'     => array(
			'option'   => 'bhdt_wire_menu_blocks',
			'default'  => 'bhdt_wirecutter_default_menu_blocks',
			'clean'    => 'bhdt_wirecutter_clean_menu_blocks',
		),
		'function' => array(
			'option'   => 'bhdt_wire_function_menu_blocks',
			'default'  => 'bhdt_wirecutter_default_function_menu_blocks',
			'clean'    => 'bhdt_wirecutter_clean_function_menu_blocks',
		),
	);

	foreach ( $sources as $type => $source ) {
		$rows = get_option( $source['option'], array() );
		if ( ( ! is_array( $rows ) || empty( $rows ) ) && is_callable( $source['default'] ) ) {
			$rows = call_user_func( $source['default'] );
		}
		if ( is_callable( $source['clean'] ) ) {
			$rows = call_user_func( $source['clean'], $rows );
		}

		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$parent_label = $row['label_vi'] ?? ( $row['label'] ?? '' );
			if ( '' === (string) $parent_label ) {
				continue;
			}

			$parent_key = bhdt_wirecutter_menu_landing_key( $type, $parent_label, $parent_label, 'parent' );
			$map[ $parent_key ] = bhdt_wirecutter_unique_menu_landing_slug( $parent_label, $reserved_slugs );

			foreach ( $row['children'] ?? array() as $child ) {
				if ( ! is_array( $child ) ) {
					continue;
				}

				$child_label = $child['label_vi'] ?? ( $child['label'] ?? '' );
				if ( '' === (string) $child_label ) {
					continue;
				}

				$child_key = bhdt_wirecutter_menu_landing_key( $type, $parent_label, $child_label, 'child' );
				$map[ $child_key ] = bhdt_wirecutter_unique_menu_landing_slug( $child_label, $reserved_slugs );
			}
		}
	}

	return $map;
}

function bhdt_wirecutter_get_menu_landing_slug( $type, $parent_label, $label, $level, $fallback = 'menu' ) {
	$key = bhdt_wirecutter_menu_landing_key( $type, $parent_label, $label, $level );
	$map = bhdt_wirecutter_get_menu_landing_slug_map();
	if ( isset( $map[ $key ] ) ) {
		return $map[ $key ];
	}

	$slug = sanitize_title( (string) $label );
	if ( '' === $slug ) {
		$slug = sanitize_title( (string) $fallback );
	}

	return '' !== $slug ? $slug : 'menu';
}

function bhdt_wirecutter_build_parent_landing_url( $type, $label ) {
	$slug = bhdt_wirecutter_get_menu_landing_slug( $type, $label, $label, 'parent', 'menu' );

	return home_url( '/' . $slug . '/' );
}

function bhdt_wirecutter_build_child_landing_url( $type, $parent_label, $child_label ) {
	$slug = bhdt_wirecutter_get_menu_landing_slug( $type, $parent_label, $child_label, 'child', $parent_label );

	return home_url( '/' . $slug . '/' );
}

function bhdt_wirecutter_get_menu_child_landing_url( $type, $section, $child ) {
	if ( isset( $child['source'] ) && 'content_link' === $child['source'] ) {
		return (string) ( $child['url'] ?? '#' );
	}

	return bhdt_wirecutter_build_child_landing_url(
		$type,
		$section['label_vi'] ?? ( $section['label'] ?? '' ),
		$child['label_vi'] ?? ( $child['label'] ?? '' )
	);
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
	if ( is_admin() ) {
		return;
	}

	$post_id = get_queried_object_id();
	$edit_url = ( is_singular() && $post_id > 0 && current_user_can( 'edit_post', $post_id ) )
		? get_edit_post_link( $post_id, '' )
		: '';
	if ( ! $edit_url ) {
		return;
	}
	?>
	<a class="bhdt-front-edit-button" href="<?php echo esc_url( $edit_url ); ?>">Sửa chữa</a>
	<?php
}
add_action( 'wp_footer', 'bhdt_wirecutter_render_front_edit_button' );

function bhdt_wirecutter_render_scroll_top_button() {
	if ( is_admin() || wp_is_mobile() ) {
		return;
	}
	?>
	<style>
		.bhdt-scroll-top-button { display: none; }
		@media (min-width: 1025px) {
			.bhdt-scroll-top-button { display: inline-flex; }
		}
	</style>
	<script>
	(function () {
		var desktopQuery = window.matchMedia('(min-width: 1025px)');
		var label = <?php echo wp_json_encode( __( 'Scroll to top', 'bhdt-wirecutter' ) ); ?>;
		var button = null;

		function getViewportWidth() {
			var widths = [
				window.innerWidth || 0,
				document.documentElement ? document.documentElement.clientWidth : 0,
				window.visualViewport ? window.visualViewport.width : 0
			].filter(function (width) {
				return width > 0;
			});

			return widths.length ? Math.min.apply(Math, widths) : 0;
		}

		function isDesktopViewport() {
			return desktopQuery.matches && getViewportWidth() > 1024;
		}

		function removeButton() {
			document.querySelectorAll('.bhdt-scroll-top-button').forEach(function (existingButton) {
				existingButton.remove();
			});
			button = null;
		}

		function syncButton() {
			if (!isDesktopViewport()) {
				removeButton();
				return;
			}

			if (button && button.isConnected) {
				return;
			}

			button = document.createElement('button');
			button.className = 'bhdt-scroll-top-button';
			button.type = 'button';
			button.setAttribute('aria-label', label);
			button.textContent = '↑';
			document.body.appendChild(button);

			button.addEventListener('click', function () {
				window.scrollTo({ top: 0, behavior: 'smooth' });
			});
		}

		syncButton();

		if (desktopQuery.addEventListener) {
			desktopQuery.addEventListener('change', syncButton);
		} else if (desktopQuery.addListener) {
			desktopQuery.addListener(syncButton);
		}

		window.addEventListener('resize', syncButton, { passive: true });
		window.addEventListener('orientationchange', syncButton, { passive: true });
		if (window.visualViewport) {
			window.visualViewport.addEventListener('resize', syncButton, { passive: true });
		}
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'bhdt_wirecutter_render_scroll_top_button' );

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
	$slug_from_path = false;

	if ( '' === $slug ) {
		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
		if ( '' !== $path && false === strpos( $path, '/' ) ) {
			$slug           = sanitize_title( $path );
			$slug_from_path = true;
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
	$linked_child_maps = array(
		'mega'     => bhdt_wirecutter_get_content_link_children_map( '_bhdt_page_menu_block_parent_url', bhdt_wirecutter_get_menu_linkable_post_types() ),
		'function' => bhdt_wirecutter_get_content_link_children_map( '_bhdt_page_function_menu_parent_url', bhdt_wirecutter_get_menu_linkable_post_types() ),
	);

	foreach ( $sources as $source_type => $sections ) {
		if ( '' !== $menu_type && $source_type !== $menu_type ) {
			continue;
		}
		foreach ( $sections as $section ) {
			$section_label = $section['label_vi'] ?? ( $section['label'] ?? '' );
			$section_slug  = bhdt_wirecutter_get_menu_landing_slug( $source_type, $section_label, $section_label, 'parent', 'menu' );
			if ( $section_slug === $slug ) {
				$children = isset( $section['children'] ) && is_array( $section['children'] ) ? $section['children'] : array();
				foreach ( $children as $child_index => $child ) {
					$children[ $child_index ]['url'] = bhdt_wirecutter_get_menu_child_landing_url( $source_type, $section, $child );
				}
				$found_sections[] = array(
					'title'    => $section['label'] ?? ( $section['label_vi'] ?? '' ),
					'children' => $children,
				);
			}

			foreach ( $section['children'] ?? array() as $child ) {
				if ( isset( $child['source'] ) && 'content_link' === $child['source'] ) {
					continue;
				}

				$child_label = $child['label_vi'] ?? ( $child['label'] ?? '' );
				$child_slug  = bhdt_wirecutter_get_menu_landing_slug( $source_type, $section_label, $child_label, 'child', $section_label );
				if ( $child_slug !== $slug ) {
					continue;
				}

				$child_url      = bhdt_wirecutter_build_child_landing_url( $source_type, $section_label, $child_label );
				$linked_map     = $linked_child_maps[ $source_type ] ?? array();
				$linked_key     = bhdt_wirecutter_normalize_menu_url_key( $child_url );
				$linked_slug_key = 'slug:' . $child_slug;
				$linked_children = array();
				if ( isset( $linked_map[ $linked_key ] ) ) {
					$linked_children = array_merge( $linked_children, $linked_map[ $linked_key ] );
				}
				if ( isset( $linked_map[ $linked_slug_key ] ) ) {
					$linked_children = array_merge( $linked_children, $linked_map[ $linked_slug_key ] );
				}

				$unique_children = array();
				foreach ( $linked_children as $linked_child ) {
					$unique_key = (string) ( $linked_child['url'] ?? '' ) . '|' . (string) ( $linked_child['label_vi'] ?? $linked_child['label'] ?? '' );
					$unique_children[ $unique_key ] = $linked_child;
				}

				$found_sections[] = array(
					'title'    => $child['label'] ?? $child_label,
					'children' => array_values( $unique_children ),
				);
			}
		}
	}

	if ( empty( $found_sections ) ) {
		if ( $slug_from_path && ! is_404() ) {
			return;
		}

		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		return;
	}

	global $wp_query;
	$wp_query->is_404 = false;
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

function bhdt_wirecutter_get_menu_link_options( $type, $sections ) {
	$options = array();

	foreach ( (array) $sections as $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}

		$section_label = $section['label_vi'] ?? ( $section['label'] ?? '' );
		if ( '' === (string) $section_label ) {
			continue;
		}

		$options[] = array(
			'label' => $section_label,
			'url'   => bhdt_wirecutter_build_parent_landing_url( $type, $section_label ),
		);

		foreach ( $section['children'] ?? array() as $child ) {
			if ( ! is_array( $child ) || ( isset( $child['source'] ) && 'content_link' === $child['source'] ) ) {
				continue;
			}

			$child_label = $child['label_vi'] ?? ( $child['label'] ?? '' );
			if ( '' === (string) $child_label ) {
				continue;
			}

			$options[] = array(
				'label' => trim( $section_label . ' > ' . $child_label, ' >' ),
				'url'   => bhdt_wirecutter_build_child_landing_url( $type, $section_label, $child_label ),
			);
		}
	}

	return $options;
}

function bhdt_wirecutter_render_page_menu_link_meta_box( $post ) {
	wp_nonce_field( 'bhdt_save_page_menu_links', 'bhdt_page_menu_links_nonce' );

	$current_menu_parent     = (string) get_post_meta( $post->ID, '_bhdt_page_menu_block_parent_url', true );
	$current_function_parent = (string) get_post_meta( $post->ID, '_bhdt_page_function_menu_parent_url', true );
	$current_menu_parent_key     = bhdt_wirecutter_normalize_menu_url_key( $current_menu_parent );
	$current_function_parent_key = bhdt_wirecutter_normalize_menu_url_key( $current_function_parent );
	$menu_parents                = bhdt_wirecutter_get_menu_blocks( true );
	$function_parents            = bhdt_wirecutter_get_function_menu_blocks( true );
	$menu_link_options           = bhdt_wirecutter_get_menu_link_options( 'mega', $menu_parents );
	$function_link_options       = bhdt_wirecutter_get_menu_link_options( 'function', $function_parents );
	$post_type_label             = bhdt_wirecutter_get_post_type_menu_label( $post->post_type );
	?>
	<p><?php echo esc_html( sprintf( __( 'Chọn mục cha để tự động thêm %s này thành mục con hover.', 'bhdt-wirecutter' ), $post_type_label ) ); ?></p>
	<p>
		<label for="bhdt_page_menu_block_parent_url"><strong><?php esc_html_e( 'Menu cha', 'bhdt-wirecutter' ); ?></strong></label><br>
		<select name="bhdt_page_menu_block_parent_url" id="bhdt_page_menu_block_parent_url" style="width:100%;max-width:460px;">
			<option value="">Không liên kết vào menu cha</option>
			<?php foreach ( $menu_link_options as $option ) : ?>
				<option value="<?php echo esc_attr( $option['url'] ); ?>" <?php selected( $current_menu_parent_key, bhdt_wirecutter_normalize_menu_url_key( $option['url'] ) ); ?>><?php echo esc_html( $option['label'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="bhdt_page_function_menu_parent_url"><strong><?php esc_html_e( 'Menu chức năng', 'bhdt-wirecutter' ); ?></strong></label><br>
		<select name="bhdt_page_function_menu_parent_url" id="bhdt_page_function_menu_parent_url" style="width:100%;max-width:460px;">
			<option value="">Không liên kết vào menu chức năng</option>
			<?php foreach ( $function_link_options as $option ) : ?>
				<option value="<?php echo esc_attr( $option['url'] ); ?>" <?php selected( $current_function_parent_key, bhdt_wirecutter_normalize_menu_url_key( $option['url'] ) ); ?>><?php echo esc_html( $option['label'] ); ?></option>
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
	$menu_link_options = bhdt_wirecutter_get_menu_link_options( 'mega', $menu_parents );
	$function_link_options = bhdt_wirecutter_get_menu_link_options( 'function', $function_parents );
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
					<?php foreach ( $menu_link_options as $option ) : ?>
						<option value="<?php echo esc_attr( $option['url'] ); ?>"><?php echo esc_html( $option['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="alignleft" style="margin-top:8px;">
				<span class="title"><?php esc_html_e( 'Menu chức năng', 'bhdt-wirecutter' ); ?></span>
				<select name="bhdt_page_function_menu_parent_url">
					<option value="">Không liên kết</option>
					<?php foreach ( $function_link_options as $option ) : ?>
						<option value="<?php echo esc_attr( $option['url'] ); ?>"><?php echo esc_html( $option['label'] ); ?></option>
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

function bhdt_wirecutter_share_box_post_types() {
	return array_values(
		array_filter(
			apply_filters(
				'bhdt_wirecutter_share_box_post_types',
				array( 'post', 'bhdt_review', 'bhdt_comparison', 'bhdt_project' )
			),
			'post_type_exists'
		)
	);
}

function bhdt_wirecutter_register_share_box_meta_box() {
	foreach ( bhdt_wirecutter_share_box_post_types() as $post_type ) {
		add_meta_box(
			'bhdt_share_box_settings',
			'Chia sẻ bài viết',
			'bhdt_wirecutter_render_share_box_meta_box',
			$post_type,
			'side',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'bhdt_wirecutter_register_share_box_meta_box' );

function bhdt_wirecutter_render_share_box_meta_box( $post ) {
	wp_nonce_field( 'bhdt_share_box_settings_nonce', 'bhdt_share_box_settings_nonce' );

	$enabled = get_post_meta( $post->ID, '_bhdt_share_box_enabled', true );
	$title   = get_post_meta( $post->ID, '_bhdt_share_box_title', true );
	if ( '' === $enabled ) {
		$enabled = '1';
	}
	if ( '' === $title ) {
		$title = 'Chia sẻ bài viết này';
	}
	?>
	<p>
		<label>
			<input type="checkbox" name="bhdt_share_box_enabled" value="1" <?php checked( '1', $enabled ); ?>>
			Hiển thị cuối bài
		</label>
	</p>
	<p>
		<label for="bhdt_share_box_title"><strong>Tiêu đề</strong></label>
		<input type="text" id="bhdt_share_box_title" name="bhdt_share_box_title" value="<?php echo esc_attr( $title ); ?>" style="width:100%;">
	</p>
	<p class="description">Frontend sẽ có nút copy link và chia sẻ Facebook.</p>
	<?php
}

function bhdt_wirecutter_save_share_box_meta( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! in_array( get_post_type( $post_id ), bhdt_wirecutter_share_box_post_types(), true ) ) {
		return;
	}

	if ( ! isset( $_POST['bhdt_share_box_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bhdt_share_box_settings_nonce'] ) ), 'bhdt_share_box_settings_nonce' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_bhdt_share_box_enabled', isset( $_POST['bhdt_share_box_enabled'] ) ? '1' : '0' );
	update_post_meta( $post_id, '_bhdt_share_box_title', isset( $_POST['bhdt_share_box_title'] ) ? sanitize_text_field( wp_unslash( $_POST['bhdt_share_box_title'] ) ) : '' );
}
add_action( 'save_post', 'bhdt_wirecutter_save_share_box_meta' );

function bhdt_wirecutter_render_share_box( $post_id = 0 ) {
	$post_id = $post_id ? absint( $post_id ) : get_the_ID();
	if ( ! $post_id || '0' === get_post_meta( $post_id, '_bhdt_share_box_enabled', true ) ) {
		return '';
	}

	$url          = get_permalink( $post_id );
	$title        = get_the_title( $post_id );
	$share_title  = get_post_meta( $post_id, '_bhdt_share_box_title', true );
	$share_title  = '' !== $share_title ? $share_title : 'Chia sẻ bài viết này';
	$facebook_url = add_query_arg( 'u', rawurlencode( $url ), 'https://www.facebook.com/sharer/sharer.php' );

	ob_start();
	?>
	<section class="bhdt-share-box" aria-label="<?php echo esc_attr( 'Chia sẻ: ' . $title ); ?>">
		<h2><?php echo esc_html( $share_title ); ?></h2>
		<div class="bhdt-share-actions">
			<button type="button" class="bhdt-share-button" data-bhdt-copy-link="<?php echo esc_url( $url ); ?>">Copy link</button>
			<a class="bhdt-share-button bhdt-share-facebook" href="<?php echo esc_url( $facebook_url ); ?>" target="_blank" rel="noopener noreferrer">Share Facebook</a>
		</div>
		<p class="bhdt-share-status" data-bhdt-share-status aria-live="polite"></p>
	</section>
	<?php
	return ob_get_clean();
}

function bhdt_wirecutter_facebook_share_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'label' => 'Chia sẻ Facebook',
			'url'   => '',
			'class' => '',
		),
		(array) $atts,
		'bhdt_facebook_share'
	);

	$post_id = get_the_ID();
	$url = '' !== trim( (string) $atts['url'] ) ? esc_url_raw( (string) $atts['url'] ) : '';
	if ( '' === $url && $post_id ) {
		$url = get_permalink( $post_id );
	}
	if ( '' === $url ) {
		$url = home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) );
	}

	$label = '' !== trim( (string) $atts['label'] ) ? sanitize_text_field( (string) $atts['label'] ) : 'Chia sẻ Facebook';
	$class = trim( 'bhdt-facebook-share-shortcode ' . sanitize_html_class( (string) $atts['class'] ) );
	$facebook_url = add_query_arg( 'u', rawurlencode( $url ), 'https://www.facebook.com/sharer/sharer.php' );

	return sprintf(
		'<a class="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:8px;min-height:40px;padding:10px 14px;border-radius:999px;background:#1877f2;color:#fff;text-decoration:none;font-family:\'Noto Sans\',Arial,sans-serif;font-weight:800;line-height:1;"><span aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%%;background:#fff;color:#1877f2;font-size:16px;font-weight:900;">f</span><span>%3$s</span></a>',
		esc_attr( $class ),
		esc_url( $facebook_url ),
		esc_html( $label )
	);
}
add_shortcode( 'bhdt_facebook_share', 'bhdt_wirecutter_facebook_share_shortcode' );

function bhdt_wirecutter_facebook_message_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'label' => 'Nhắn tin Facebook',
			'url'   => 'https://m.me/review.dientu',
			'class' => '',
		),
		(array) $atts,
		'bhdt_facebook_message'
	);

	$url = esc_url_raw( (string) $atts['url'] );
	if ( '' === $url ) {
		$url = 'https://m.me/review.dientu';
	}

	$label = '' !== trim( (string) $atts['label'] ) ? sanitize_text_field( (string) $atts['label'] ) : 'Nhắn tin Facebook';
	$class = trim( 'bhdt-facebook-message-shortcode ' . sanitize_html_class( (string) $atts['class'] ) );

	return sprintf(
		'<a class="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:8px;min-height:40px;padding:10px 14px;border-radius:999px;background:#0084ff;color:#fff;text-decoration:none;font-family:\'Noto Sans\',Arial,sans-serif;font-weight:800;line-height:1;"><span aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%%;background:#fff;color:#0084ff;font-size:14px;font-weight:900;">💬</span><span>%3$s</span></a>',
		esc_attr( $class ),
		esc_url( $url ),
		esc_html( $label )
	);
}
add_shortcode( 'bhdt_facebook_message', 'bhdt_wirecutter_facebook_message_shortcode' );

function bhdt_wirecutter_registered_shortcode_docs() {
	return array(
		array(
			'shortcode' => '[bhdt_facebook_share]',
			'function'  => 'Nút chia sẻ Facebook tự lấy link bài/trang hiện tại.',
			'example'   => '[bhdt_facebook_share label="Chia sẻ bài này"]',
			'note'      => 'Có thể thêm url="..." nếu muốn share một link cố định.',
		),
		array(
			'shortcode' => '[bhdt_facebook_message]',
			'function'  => 'Nút mở chat Messenger với trang Facebook review.dientu.',
			'example'   => '[bhdt_facebook_message label="Chat với Banhangdientu"]',
			'note'      => 'Mặc định mở https://m.me/review.dientu. Có thể đổi bằng url="https://www.facebook.com/review.dientu/".',
		),
		array(
			'shortcode' => '[bhdt_contact_form]',
			'function'  => 'Form liên hệ lưu nội dung vào tab Quản Gia Web > Liên hệ.',
			'example'   => '[bhdt_contact_form]',
			'note'      => 'Dùng cho trang Liên hệ hoặc chèn vào nội dung cần nhận phản hồi.',
		),
		array(
			'shortcode' => '[bhdt_pinout_finder]',
			'function'  => 'Công cụ tra nhanh sơ đồ chân/module pinout.',
			'example'   => '[bhdt_pinout_finder]',
			'note'      => 'Có ô tìm kiếm và preset ESP32, LM2596, Arduino Nano, relay; dùng dữ liệu/assets từ BHDT Core System.',
		),
		array(
			'shortcode' => '[bhdt_resistor_calculator]',
			'function'  => 'Máy tính điện trở theo vòng màu và tra mã tụ.',
			'example'   => '[bhdt_resistor_calculator]',
			'note'      => 'Hỗ trợ 4 vòng/5 vòng, nhập Ohm để auto-map màu, nhập mã tụ như 104.',
		),
		array(
			'shortcode' => '[bhdt_voltage_divider]',
			'function'  => 'Máy tính cầu chia áp.',
			'example'   => '[bhdt_voltage_divider]',
			'note'      => 'Tính Vout, dòng qua cầu chia áp, công suất điện trở; có ô Vout mục tiêu để gợi ý tỉ lệ.',
		),
		array(
			'shortcode' => '[bhdt_project_hub]',
			'function'  => 'Khối hub quy trình lắp ráp dự án DIY.',
			'example'   => '[bhdt_project_hub]',
			'note'      => 'Hiển thị checklist workflow và CTA mua BOM affiliate mẫu.',
		),
		array(
			'shortcode' => '[bhdt_pcb_affiliate]',
			'function'  => 'Khối tải Gerber/BOM và link đặt PCB.',
			'example'   => '[bhdt_pcb_affiliate]',
			'note'      => 'Có link mẫu JLCPCB/PCBWay và placeholder download Gerber/BOM.',
		),
		array(
			'shortcode' => '[bhdt_wirecutter_pick]',
			'function'  => 'Box lựa chọn sản phẩm kiểu Wirecutter.',
			'example'   => '[bhdt_wirecutter_pick badge="Best pick" title="ESP32 DevKit" price="120.000đ" link="https://example.com" pros="Rẻ|Dễ dùng" cons="Không kèm cáp"]',
			'note'      => 'Thuộc tính: badge, title, price, link, pros, cons. Ngăn cách pros/cons bằng dấu |.',
		),
		array(
			'shortcode' => '[bhdt_article_ad id="..."]',
			'function'  => 'Chèn widget quảng cáo trong bài viết tại đúng vị trí đặt shortcode.',
			'example'   => '[bhdt_article_ad id="ad_xxxxxxxx"]',
			'note'      => 'ID lấy trong phần Công cụ > widget quảng cáo trong bài.',
		),
		array(
			'shortcode' => '[bhdt_debug_function_menu]',
			'function'  => 'Debug menu chức năng và các liên kết con.',
			'example'   => '[bhdt_debug_function_menu]',
			'note'      => 'Chỉ dùng khi cần kiểm tra kỹ thuật, không nên đặt ở nội dung public lâu dài.',
		),
	);
}

function bhdt_wirecutter_append_share_box_to_content( $content ) {
	if ( is_admin() || ! is_singular( bhdt_wirecutter_share_box_post_types() ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	return $content . bhdt_wirecutter_render_share_box( get_the_ID() );
}
add_filter( 'the_content', 'bhdt_wirecutter_append_share_box_to_content', 20 );

function bhdt_wirecutter_print_share_box_assets() {
	if ( ! is_singular( bhdt_wirecutter_share_box_post_types() ) ) {
		return;
	}
	?>
	<style>
		.bhdt-share-box {
			margin-top: 2rem;
			padding: 1rem;
			border: 1px solid var(--bhdt-line);
			border-radius: 12px;
			background: #ffffff;
		}

		.bhdt-share-box h2 {
			margin: 0 0 0.75rem;
			font-size: 1.15rem;
			line-height: 1.25;
		}

		.bhdt-share-actions {
			display: flex;
			flex-wrap: wrap;
			gap: 0.55rem;
		}

		.bhdt-share-button {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			min-height: 2.5rem;
			padding: 0.55rem 0.85rem;
			border: 1px solid #111111;
			border-radius: 999px;
			background: #111111;
			color: #ffffff;
			font-family: 'Noto Sans', Arial, sans-serif;
			font-size: 0.92rem;
			font-weight: 800;
			line-height: 1;
			text-decoration: none;
			cursor: pointer;
		}

		.bhdt-share-button:hover,
		.bhdt-share-button:focus-visible {
			background: var(--bhdt-accent-dark);
			border-color: var(--bhdt-accent-dark);
			color: #ffffff;
			text-decoration: none;
		}

		.bhdt-share-status {
			min-height: 1.3rem;
			margin: 0.55rem 0 0;
			color: var(--bhdt-muted);
			font-size: 0.9rem;
		}
	</style>
	<script>
	document.addEventListener('click', function (event) {
		var button = event.target.closest('[data-bhdt-copy-link]');
		if (!button) {
			return;
		}

		var shareBox = button.closest('.bhdt-share-box');
		var status = shareBox ? shareBox.querySelector('[data-bhdt-share-status]') : null;
		var url = button.getAttribute('data-bhdt-copy-link') || '';
		var done = function (message) {
			if (status) {
				status.textContent = message;
			}
		};

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(url).then(function () {
				done('Đã copy link.');
			}).catch(function () {
				done(url);
			});
			return;
		}

		done(url);
	});
	</script>
	<?php
}
add_action( 'wp_footer', 'bhdt_wirecutter_print_share_box_assets' );

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
			'name'          => __( 'Sidebar bài viết bên trái', 'bhdt-wirecutter' ),
			'id'            => 'bhdt-article-left',
			'description'   => __( 'Cột widget/quảng cáo nằm bên trái nội dung bài viết.', 'bhdt-wirecutter' ),
			'before_widget' => '<section class="bhdt-widget-box bhdt-article-rail-widget %2$s" id="%1$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Sidebar bài viết bên phải', 'bhdt-wirecutter' ),
			'id'            => 'bhdt-article-right',
			'description'   => __( 'Cột widget/quảng cáo nằm bên phải nội dung bài viết.', 'bhdt-wirecutter' ),
			'before_widget' => '<section class="bhdt-widget-box bhdt-article-rail-widget %2$s" id="%1$s">',
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
						$link_url = bhdt_wirecutter_get_menu_child_landing_url( 'mega', $section, $link );
						if ( bhdt_wirecutter_is_menu_url_active( $link_url ) ) {
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
							<?php
							$link_url    = bhdt_wirecutter_get_menu_child_landing_url( 'mega', $section, $link );
							$link_active = bhdt_wirecutter_is_menu_url_active( $link_url );
							?>
							<li><a class="<?php echo $link_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $link_url ); ?>" <?php echo $link_active ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $link['label'] ); ?></a></li>
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
						$link_url = bhdt_wirecutter_get_menu_child_landing_url( 'function', $section, $link );
						if ( bhdt_wirecutter_is_menu_url_active( $link_url ) ) {
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
							<?php
							$link_url    = bhdt_wirecutter_get_menu_child_landing_url( 'function', $section, $link );
							$link_active = bhdt_wirecutter_is_menu_url_active( $link_url );
							?>
							<li><a class="<?php echo $link_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $link_url ); ?>" <?php echo $link_active ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $link['label'] ); ?></a></li>
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

