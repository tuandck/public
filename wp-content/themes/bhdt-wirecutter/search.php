<?php
/**
 * Search results template.
 *
 * @package BHDT_Wirecutter_Clone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bhdt_wirecutter_search_excerpt' ) ) {
	/**
	 * Build a search excerpt around the first keyword match.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $query Search query.
	 * @return string
	 */
	function bhdt_wirecutter_search_excerpt( $post_id, $query ) {
		$text = get_the_excerpt( $post_id );
		if ( '' === trim( $text ) ) {
			$text = get_post_field( 'post_content', $post_id );
		}

		$text = preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $text ) ) );
		$text = trim( (string) $text );
		if ( '' === $text ) {
			return '';
		}

		$terms = preg_split( '/\s+/u', trim( (string) $query ) );
		$term  = '';
		foreach ( (array) $terms as $candidate ) {
			$candidate = trim( $candidate );
			$candidate_length = function_exists( 'mb_strlen' ) ? mb_strlen( $candidate, 'UTF-8' ) : strlen( $candidate );
			if ( $candidate_length >= 2 ) {
				$term = $candidate;
				break;
			}
		}

		$start = 0;
		if ( '' !== $term && function_exists( 'mb_stripos' ) ) {
			$match = mb_stripos( $text, $term, 0, 'UTF-8' );
			if ( false !== $match ) {
				$start = max( 0, $match - 90 );
			}
		}

		if ( function_exists( 'mb_substr' ) ) {
			$snippet = mb_substr( $text, $start, 260, 'UTF-8' );
		} else {
			$snippet = substr( $text, $start, 260 );
		}

		$prefix = $start > 0 ? '...' : '';
		$suffix = strlen( $text ) > $start + strlen( $snippet ) ? '...' : '';

		return $prefix . trim( $snippet ) . $suffix;
	}
}

if ( ! function_exists( 'bhdt_wirecutter_highlight_search_terms' ) ) {
	/**
	 * Highlight search terms in escaped text.
	 *
	 * @param string $text Text.
	 * @param string $query Search query.
	 * @return string
	 */
	function bhdt_wirecutter_highlight_search_terms( $text, $query ) {
		$escaped = esc_html( $text );
		$terms   = array_filter(
			array_map( 'trim', preg_split( '/\s+/u', (string) $query ) ),
			static function ( $term ) {
				$term_length = function_exists( 'mb_strlen' ) ? mb_strlen( $term, 'UTF-8' ) : strlen( $term );
				return $term_length >= 2;
			}
		);

		foreach ( $terms as $term ) {
			$escaped_term = preg_quote( esc_html( $term ), '/' );
			$escaped      = preg_replace( '/(' . $escaped_term . ')/iu', '<mark>$1</mark>', $escaped );
		}

		return wp_kses( $escaped, array( 'mark' => array() ) );
	}
}

get_header();

$search_query = get_search_query();
$search_results_query = null;
$search_results_posts = array();
if ( ! have_posts() && '' !== trim( (string) $search_query ) ) {
	$search_post_types = array( 'post', 'page', 'bhdt_review', 'bhdt_comparison', 'bhdt_project' );
	if ( class_exists( 'WooCommerce' ) ) {
		$search_post_types[] = 'product';
	}

	global $wpdb;
	$like = '%' . $wpdb->esc_like( $search_query ) . '%';
	$post_type_placeholders = implode( ',', array_fill( 0, count( $search_post_types ), '%s' ) );
	$sql = $wpdb->prepare(
		"SELECT ID
		FROM {$wpdb->posts}
		WHERE post_status = 'publish'
			AND post_type IN ($post_type_placeholders)
			AND (
				post_title LIKE %s
				OR post_excerpt LIKE %s
				OR post_content LIKE %s
			)
		ORDER BY post_modified_gmt DESC
		LIMIT 20",
		array_merge( array_values( array_unique( $search_post_types ) ), array( $like, $like, $like ) )
	);
	$fallback_ids = array_map( 'intval', (array) $wpdb->get_col( $sql ) );

	if ( ! empty( $fallback_ids ) ) {
		$search_results_posts = array_values(
			array_filter(
				array_map(
					static function ( $post_id ) use ( $search_post_types ) {
						$post = get_post( (int) $post_id );
						if ( ! $post || 'publish' !== $post->post_status || ! in_array( $post->post_type, $search_post_types, true ) ) {
							return null;
						}

						return $post;
					},
					$fallback_ids
				)
			)
		);
	}
}

$active_search_query = $search_results_query instanceof WP_Query ? $search_results_query : $GLOBALS['wp_query'];
$result_count = ! empty( $search_results_posts ) ? count( $search_results_posts ) : (int) $active_search_query->found_posts;
?>
<main class="bhdt-home-grid bhdt-search-results-page">
	<section class="bhdt-archive-hero">
		<p class="bhdt-card-kicker"><?php esc_html_e( 'Tìm kiếm', 'bhdt-wirecutter' ); ?></p>
		<h1><?php echo esc_html( sprintf( __( 'Kết quả cho: %s', 'bhdt-wirecutter' ), $search_query ) ); ?></h1>
		<p class="bhdt-card-meta"><?php echo esc_html( sprintf( __( '%d kết quả được tìm thấy', 'bhdt-wirecutter' ), $result_count ) ); ?></p>
	</section>

	<?php if ( ! empty( $search_results_posts ) || $active_search_query->have_posts() ) : ?>
		<div class="bhdt-search-results-list">
			<?php while ( ! empty( $search_results_posts ) || $active_search_query->have_posts() ) : ?>
				<?php
				if ( ! empty( $search_results_posts ) ) {
					$post = array_shift( $search_results_posts );
					setup_postdata( $post );
				} else {
					$active_search_query->the_post();
				}
				?>
				<?php
				$post_type_object = get_post_type_object( get_post_type() );
				$type_label       = $post_type_object ? $post_type_object->labels->singular_name : __( 'Bài viết', 'bhdt-wirecutter' );
				$permalink        = get_permalink();
				$snippet          = bhdt_wirecutter_search_excerpt( get_the_ID(), $search_query );
				$result_classes   = has_post_thumbnail() ? 'bhdt-search-result' : 'bhdt-search-result bhdt-search-result-no-thumb';
				?>
				<article <?php post_class( $result_classes ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<a class="bhdt-search-result-thumb" href="<?php echo esc_url( $permalink ); ?>" aria-hidden="true" tabindex="-1">
							<?php the_post_thumbnail( 'thumbnail' ); ?>
						</a>
					<?php endif; ?>
					<div class="bhdt-search-result-body">
						<p class="bhdt-card-kicker"><?php echo esc_html( $type_label ); ?></p>
						<h2 class="bhdt-card-title"><a href="<?php echo esc_url( $permalink ); ?>"><?php the_title(); ?></a></h2>
						<a class="bhdt-search-result-url" href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $permalink ); ?></a>
						<?php if ( '' !== $snippet ) : ?>
							<p class="bhdt-card-excerpt"><?php echo bhdt_wirecutter_highlight_search_terms( $snippet, $search_query ); ?></p>
						<?php endif; ?>
						<p class="bhdt-card-meta"><?php echo esc_html( bhdt_wirecutter_post_meta_line() ); ?></p>
					</div>
				</article>
			<?php endwhile; ?>
		</div>

		<?php if ( ! $search_results_query instanceof WP_Query ) : ?>
			<?php the_posts_pagination(); ?>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	<?php else : ?>
		<section class="bhdt-archive-hero">
			<h2><?php esc_html_e( 'Không tìm thấy kết quả', 'bhdt-wirecutter' ); ?></h2>
			<p><?php esc_html_e( 'Hãy thử từ khóa ngắn hơn hoặc tìm theo tên linh kiện, module, dự án.', 'bhdt-wirecutter' ); ?></p>
			<?php get_search_form(); ?>
		</section>
	<?php endif; ?>
</main>
<?php
get_footer();
