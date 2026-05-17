<?php
/**
 * Homepage / index template.
 *
 * @package BHDT_Wirecutter_Clone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$bhdt_has_woo = class_exists( 'WooCommerce' );

$bhdt_latest_args = array(
	'post_type'      => $bhdt_has_woo ? 'product' : 'post',
	'posts_per_page' => 7,
	'post_status'    => 'publish',
	'no_found_rows'  => true,
);

$bhdt_latest_query = new WP_Query( $bhdt_latest_args );

$bhdt_featured_product_id = (int) apply_filters(
	'bhdt_wire_home_featured_product_id',
	(int) get_theme_mod( 'bhdt_wire_home_featured_product_id', 0 )
);

$bhdt_hero_query_args = array(
	'post_type'      => $bhdt_has_woo ? 'product' : 'post',
	'posts_per_page' => 1,
	'post_status'    => 'publish',
	'no_found_rows'  => true,
);

if ( $bhdt_featured_product_id > 0 ) {
	$bhdt_hero_query_args['p'] = $bhdt_featured_product_id;
}

$bhdt_hero_query = new WP_Query( $bhdt_hero_query_args );

$bhdt_category_groups = function_exists( 'bhdt_wirecutter_get_home_category_groups' )
	? bhdt_wirecutter_get_home_category_groups()
	: array();

$bhdt_home_topics = function_exists( 'bhdt_wirecutter_get_hot_keys' )
	? bhdt_wirecutter_get_hot_keys()
	: array();

$bhdt_curated_links = array();

if ( ! empty( $bhdt_category_groups ) ) {
	$bhdt_curated_tax_query = array( 'relation' => 'OR' );
	$bhdt_curated_post_types = array();

	foreach ( $bhdt_category_groups as $bhdt_group ) {
		$bhdt_curated_post_types[] = isset( $bhdt_group['post_type'] ) ? $bhdt_group['post_type'] : 'post';
		if ( ! empty( $bhdt_group['slug'] ) ) {
			$bhdt_curated_tax_query[] = array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => array( $bhdt_group['slug'] ),
			);
		}
	}

	$bhdt_curated_post_types = array_values( array_unique( $bhdt_curated_post_types ) );

	if ( count( $bhdt_curated_tax_query ) > 1 ) {
	$bhdt_curated_query = new WP_Query(
		array(
			'post_type'      => $bhdt_curated_post_types,
			'posts_per_page' => 3,
			'post_status'    => 'publish',
			'no_found_rows'  => true,
			'tax_query'      => $bhdt_curated_tax_query,
		)
	);

	if ( $bhdt_curated_query->have_posts() ) {
		while ( $bhdt_curated_query->have_posts() ) {
			$bhdt_curated_query->the_post();
			$bhdt_curated_links[] = array(
				'label' => get_the_title(),
				'url'   => get_permalink(),
			);
		}
		wp_reset_postdata();
	}
}

}

if ( empty( $bhdt_curated_links ) ) {
	foreach ( $bhdt_home_topics as $bhdt_topic ) {
		$bhdt_curated_links[] = array(
			'label' => $bhdt_topic['label'],
			'url'   => $bhdt_topic['url'],
		);
		if ( count( $bhdt_curated_links ) >= 3 ) {
			break;
		}
	}
}

$bhdt_deals_query = null;
if ( $bhdt_has_woo ) {
	$bhdt_deals_query = new WP_Query(
		array(
			'post_type'      => 'product',
			'posts_per_page' => 8,
			'post_status'    => 'publish',
			'no_found_rows'  => true,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_sale_price',
					'value'   => '',
					'compare' => '!=',
				),
				array(
					'key'     => '_sale_price',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		)
	);
}
?>
<main class="bhdt-wire-home">
	<div class="bhdt-wire-home-topics" aria-label="<?php esc_attr_e( 'Chủ đề linh kiện', 'bhdt-wirecutter' ); ?>">
		<?php foreach ( $bhdt_home_topics as $bhdt_topic ) : ?>
			<a href="<?php echo esc_url( $bhdt_topic['url'] ); ?>"><?php echo esc_html( $bhdt_topic['label'] ); ?></a>
		<?php endforeach; ?>
	</div>

	<div class="bhdt-wire-layout">
		<aside class="bhdt-wire-col bhdt-wire-col-left">
			<section class="bhdt-wire-panel">
				<h2><?php esc_html_e( 'Mới nhất', 'bhdt-wirecutter' ); ?></h2>
				<?php if ( $bhdt_latest_query->have_posts() ) : ?>
					<ul class="bhdt-wire-latest-list">
						<?php while ( $bhdt_latest_query->have_posts() ) : $bhdt_latest_query->the_post(); ?>
							<li>
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></time>
							</li>
						<?php endwhile; ?>
					</ul>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<p><?php esc_html_e( 'Chưa có nội dung mới.', 'bhdt-wirecutter' ); ?></p>
				<?php endif; ?>
			</section>
		</aside>

		<section class="bhdt-wire-col bhdt-wire-col-main">
			<section class="bhdt-wire-hero">
				<?php if ( $bhdt_hero_query->have_posts() ) : ?>
					<?php while ( $bhdt_hero_query->have_posts() ) : $bhdt_hero_query->the_post(); ?>
						<div class="bhdt-wire-hero-media">
							<a href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
								<?php if ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( 'large' ); ?>
								<?php else : ?>
									<div class="bhdt-wire-thumb-fallback"><?php esc_html_e( 'Hình ảnh nổi bật', 'bhdt-wirecutter' ); ?></div>
								<?php endif; ?>
							</a>
						</div>
						<div class="bhdt-wire-hero-copy">
						<p class="bhdt-wire-kicker"><?php esc_html_e( 'Hướng dẫn nổi bật', 'bhdt-wirecutter' ); ?></p>
							<h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28, '...' ) ); ?></p>
							<ul class="bhdt-wire-curated-links">
								<?php foreach ( $bhdt_curated_links as $bhdt_link ) : ?>
									<li><a href="<?php echo esc_url( $bhdt_link['url'] ); ?>"><?php echo esc_html( $bhdt_link['label'] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<div class="bhdt-wire-hero-copy bhdt-wire-panel">
					<h1><?php bloginfo( 'name' ); ?></h1>
					<p><?php bloginfo( 'description' ); ?></p>
					</div>
				<?php endif; ?>
			</section>

			<?php foreach ( $bhdt_category_groups as $bhdt_group ) : ?>
				<?php
				$bhdt_cat_name = $bhdt_group['name'];
				$bhdt_cat_label = $bhdt_group['label'];
				$bhdt_cat_subtitle = isset( $bhdt_group['subtitle'] ) ? $bhdt_group['subtitle'] : '';
				$bhdt_cat_post_type = isset( $bhdt_group['post_type'] ) ? $bhdt_group['post_type'] : 'post';
				$bhdt_term = get_term_by( 'slug', $bhdt_group['slug'], 'category' );
				$bhdt_cat_query = null;

				if ( $bhdt_term && ! is_wp_error( $bhdt_term ) ) {
					$bhdt_cat_query = new WP_Query(
						array(
							'post_type'      => $bhdt_cat_post_type,
							'posts_per_page' => 4,
							'post_status'    => 'publish',
							'no_found_rows'  => true,
							'tax_query'      => array(
								array(
									'taxonomy' => 'category',
									'field'    => 'term_id',
									'terms'    => array( (int) $bhdt_term->term_id ),
								),
							),
						)
					);
				}
				?>
				<section class="bhdt-wire-category-block">
					<header class="bhdt-wire-category-head">
						<h2><?php echo esc_html( $bhdt_cat_label ); ?></h2>
						<?php if ( '' !== trim( (string) $bhdt_cat_subtitle ) ) : ?>
							<p class="bhdt-wire-category-subtitle"><?php echo esc_html( $bhdt_cat_subtitle ); ?></p>
						<?php endif; ?>
					</header>
					<?php if ( $bhdt_cat_query && ! empty( $bhdt_cat_query->posts ) ) : ?>
						<?php
						$bhdt_cat_posts    = $bhdt_cat_query->posts;
						$bhdt_featured_one = array_shift( $bhdt_cat_posts );
						$bhdt_featured_id  = (int) $bhdt_featured_one->ID;
						$bhdt_featured_excerpt = get_post_field( 'post_excerpt', $bhdt_featured_id );
						if ( empty( trim( $bhdt_featured_excerpt ) ) ) {
							$bhdt_featured_excerpt = get_post_field( 'post_content', $bhdt_featured_id );
						}
						?>
						<article class="bhdt-wire-category-lead">
							<a class="bhdt-wire-category-lead-thumb" href="<?php echo esc_url( get_permalink( $bhdt_featured_id ) ); ?>" aria-label="<?php echo esc_attr( get_the_title( $bhdt_featured_id ) ); ?>">
								<?php if ( has_post_thumbnail( $bhdt_featured_id ) ) : ?>
									<?php echo wp_kses_post( get_the_post_thumbnail( $bhdt_featured_id, 'large' ) ); ?>
								<?php else : ?>
									<div class="bhdt-wire-thumb-fallback"><?php esc_html_e( 'No image', 'bhdt-wirecutter' ); ?></div>
								<?php endif; ?>
							</a>
							<div class="bhdt-wire-category-lead-copy">
								<h3><a href="<?php echo esc_url( get_permalink( $bhdt_featured_id ) ); ?>"><?php echo esc_html( get_the_title( $bhdt_featured_id ) ); ?></a></h3>
								<p class="bhdt-wire-meta"><?php echo esc_html( sprintf( __( 'Cập nhật %s', 'bhdt-wirecutter' ), get_the_modified_date( 'M j, Y', $bhdt_featured_id ) ) ); ?></p>
								<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $bhdt_featured_excerpt ), 30, '...' ) ); ?></p>
							</div>
						</article>

						<?php if ( ! empty( $bhdt_cat_posts ) ) : ?>
							<div class="bhdt-wire-sub-grid">
								<?php foreach ( $bhdt_cat_posts as $bhdt_sub_post ) : ?>
									<?php
									$bhdt_sub_id       = (int) $bhdt_sub_post->ID;
									$bhdt_sub_excerpt  = get_post_field( 'post_excerpt', $bhdt_sub_id );
									if ( empty( trim( $bhdt_sub_excerpt ) ) ) {
										$bhdt_sub_excerpt = get_post_field( 'post_content', $bhdt_sub_id );
									}
									?>
									<article class="bhdt-wire-product-card">
										<a class="bhdt-wire-product-thumb" href="<?php echo esc_url( get_permalink( $bhdt_sub_id ) ); ?>" aria-label="<?php echo esc_attr( get_the_title( $bhdt_sub_id ) ); ?>">
											<?php if ( has_post_thumbnail( $bhdt_sub_id ) ) : ?>
												<?php echo wp_kses_post( get_the_post_thumbnail( $bhdt_sub_id, 'medium' ) ); ?>
											<?php else : ?>
														<div class="bhdt-wire-thumb-fallback"><?php esc_html_e( 'Không có hình ảnh', 'bhdt-wirecutter' ); ?></div>
											<?php endif; ?>
										</a>
										<h3><a href="<?php echo esc_url( get_permalink( $bhdt_sub_id ) ); ?>"><?php echo esc_html( get_the_title( $bhdt_sub_id ) ); ?></a></h3>
										<p class="bhdt-wire-meta"><?php echo esc_html( sprintf( __( 'Cập nhật %s', 'bhdt-wirecutter' ), get_the_modified_date( 'M j, Y', $bhdt_sub_id ) ) ); ?></p>
										<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $bhdt_sub_excerpt ), 18, '...' ) ); ?></p>
									</article>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					<?php else : ?>
						<article class="bhdt-wire-category-lead bhdt-wire-placeholder-block">
							<div class="bhdt-wire-category-lead-thumb bhdt-wire-thumb-fallback"><?php esc_html_e( 'Bài nổi bật', 'bhdt-wirecutter' ); ?></div>
							<div class="bhdt-wire-category-lead-copy">
								<h3><?php echo esc_html( sprintf( __( '%s - Bài chính', 'bhdt-wirecutter' ), $bhdt_cat_label ) ); ?></h3>
								<p><?php esc_html_e( 'Khu vực này sẽ hiển thị bài nổi bật của chủ đề khi đã có bài đúng chuyên mục.', 'bhdt-wirecutter' ); ?></p>
							</div>
						</article>
						<div class="bhdt-wire-sub-grid">
							<?php for ( $bhdt_i = 1; $bhdt_i <= 3; $bhdt_i++ ) : ?>
								<article class="bhdt-wire-product-card bhdt-wire-placeholder-block">
										<div class="bhdt-wire-product-thumb bhdt-wire-thumb-fallback"><?php echo esc_html( sprintf( __( 'Bài phụ %d', 'bhdt-wirecutter' ), $bhdt_i ) ); ?></div>
									<h3><?php echo esc_html( sprintf( __( '%s - Bài phụ %d', 'bhdt-wirecutter' ), $bhdt_cat_label, $bhdt_i ) ); ?></h3>
									<p><?php esc_html_e( 'Khu vực hiển thị bài phụ theo chiều ngang.', 'bhdt-wirecutter' ); ?></p>
								</article>
							<?php endfor; ?>
						</div>
					<?php endif; ?>
					<?php wp_reset_postdata(); ?>
				</section>
			<?php endforeach; ?>
		</section>

		<aside class="bhdt-wire-col bhdt-wire-col-right">
			<section class="bhdt-wire-panel">
				<h2><?php esc_html_e( 'Ưu đãi hàng ngày', 'bhdt-wirecutter' ); ?></h2>
				<?php if ( $bhdt_has_woo && $bhdt_deals_query && $bhdt_deals_query->have_posts() ) : ?>
					<div class="bhdt-wire-deals-list">
						<?php while ( $bhdt_deals_query->have_posts() ) : $bhdt_deals_query->the_post(); ?>
							<?php $bhdt_product = wc_get_product( get_the_ID() ); ?>
							<?php if ( ! $bhdt_product ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<?php
							$bhdt_regular = (float) $bhdt_product->get_regular_price();
							$bhdt_sale    = (float) $bhdt_product->get_sale_price();
							$bhdt_off     = 0;
							if ( $bhdt_regular > 0 && $bhdt_sale > 0 && $bhdt_sale < $bhdt_regular ) {
								$bhdt_off = (int) round( ( ( $bhdt_regular - $bhdt_sale ) / $bhdt_regular ) * 100 );
							}
							?>
							<article class="bhdt-wire-deal-item">
								<a class="bhdt-wire-deal-thumb" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
									<?php if ( has_post_thumbnail() ) : ?>
										<?php the_post_thumbnail( 'woocommerce_thumbnail' ); ?>
									<?php else : ?>
										<div class="bhdt-wire-thumb-fallback"><?php esc_html_e( 'No image', 'bhdt-wirecutter' ); ?></div>
									<?php endif; ?>
								</a>
								<div class="bhdt-wire-deal-copy">
									<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
									<p class="bhdt-wire-deal-price-line">
										<span class="bhdt-wire-sale-price"><?php echo wp_kses_post( wc_price( $bhdt_sale ) ); ?></span>
										<?php if ( $bhdt_regular > 0 ) : ?>
											<del><?php echo wp_kses_post( wc_price( $bhdt_regular ) ); ?></del>
										<?php endif; ?>
									</p>
									<?php if ( $bhdt_off > 0 ) : ?>
													<p class="bhdt-wire-off"><?php echo esc_html( sprintf( __( '%d%% GIẢM', 'bhdt-wirecutter' ), $bhdt_off ) ); ?></p>
									<?php endif; ?>
								</div>
							</article>
						<?php endwhile; ?>
					</div>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
				<p><?php esc_html_e( 'Ưu đãi hàng ngày sẽ hiển thị khi WooCommerce và giá khuyến mãi sẵn sàng.', 'bhdt-wirecutter' ); ?></p>
				<?php endif; ?>
			</section>
		</aside>
	</div>
</main>
<?php
get_footer();
