<?php
/**
 * Single template for bhdt_review.
 *
 * @package BHDT_Wirecutter_Clone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="bhdt-entry">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class(); ?>>
				<header class="bhdt-entry-header">
						<p class="bhdt-cpt-kicker"><?php esc_html_e( 'Đánh giá Linh Kiện', 'bhdt-wirecutter' ); ?></p>
					<h1 class="bhdt-entry-title"><?php the_title(); ?></h1>
					<div class="bhdt-entry-meta"><?php echo esc_html( bhdt_wirecutter_post_meta_line() ); ?></div>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<div style="margin:0 0 1.5rem;">
						<?php the_post_thumbnail( 'large' ); ?>
					</div>
				<?php endif; ?>

				<div class="bhdt-entry-content">
					<?php the_content(); ?>
				</div>

				<?php
				$specs   = get_post_meta( get_the_ID(), 'bhdt_specs', true );
				$rating  = get_post_meta( get_the_ID(), '_bhdt_review_rating', true );
				$pros    = get_post_meta( get_the_ID(), '_bhdt_review_pros', true );
				$cons    = get_post_meta( get_the_ID(), '_bhdt_review_cons', true );
				$fields  = get_post_meta( get_the_ID(), '_bhdt_review_specs', true );
				?>
				<?php if ( ! empty( $rating ) ) : ?>
					<section class="bhdt-single-specs">
						<h2><?php esc_html_e( 'Kết luận', 'bhdt-wirecutter' ); ?></h2>
						<div class="bhdt-callout"><strong><?php echo esc_html( $rating ); ?></strong></div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $pros ) || ! empty( $cons ) ) : ?>
					<section class="bhdt-single-specs">
						<h2><?php esc_html_e( 'Ưu điểm & Nhược điểm', 'bhdt-wirecutter' ); ?></h2>
						<div class="bhdt-cpt-archive">
							<div class="bhdt-callout">
											<strong><?php esc_html_e( 'Ưu điểm', 'bhdt-wirecutter' ); ?></strong>
								<?php echo wp_kses_post( bhdt_wirecutter_render_list_items( $pros, 'bhdt-spec-list' ) ); ?>
							</div>
							<div class="bhdt-callout">
											<strong><?php esc_html_e( 'Nhược điểm', 'bhdt-wirecutter' ); ?></strong>
								<?php echo wp_kses_post( bhdt_wirecutter_render_list_items( $cons, 'bhdt-spec-list' ) ); ?>
							</div>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $fields ) ) : ?>
					<section class="bhdt-single-specs">
						<h2><?php esc_html_e( 'Ghi chú Người đánh giá / Thông số', 'bhdt-wirecutter' ); ?></h2>
						<div class="bhdt-callout">
							<?php echo wp_kses_post( nl2br( esc_html( $fields ) ) ); ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $specs ) ) : ?>
					<section class="bhdt-single-specs">
						<h2><?php esc_html_e( 'JSON Kỹ thuật', 'bhdt-wirecutter' ); ?></h2>
						<div class="bhdt-callout">
							<pre style="margin:0;white-space:pre-wrap;word-break:break-word;"><?php echo esc_html( $specs ); ?></pre>
						</div>
					</section>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>
	<?php endif; ?>
</main>
<?php
get_footer();
