<?php
/**
 * Generic taxonomy archive template.
 *
 * @package BHDT_Wirecutter_Clone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$term = get_queried_object();
?>
<main class="bhdt-home-grid">
	<section class="bhdt-archive-hero">
		<p class="bhdt-card-kicker"><?php esc_html_e( 'Lưu trữ phân loại', 'bhdt-wirecutter' ); ?></p>
		<h1><?php echo esc_html( single_term_title( '', false ) ); ?></h1>
		<?php if ( ! empty( $term->description ) ) : ?>
			<p><?php echo wp_kses_post( term_description() ); ?></p>
		<?php endif; ?>
		<p class="bhdt-card-meta"><?php echo esc_html( sprintf( __( 'ID thuật ngữ: %d', 'bhdt-wirecutter' ), (int) ( $term->term_id ?? 0 ) ) ); ?></p>
	</section>

	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'bhdt-card' ); ?>>
				<a href="<?php the_permalink(); ?>" class="bhdt-card-thumb" aria-hidden="true" tabindex="-1">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'large' ); ?>
					<?php else : ?>
						<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-family:Arial,sans-serif;color:#888;">
								<?php esc_html_e( 'Không có hình ảnh', 'bhdt-wirecutter' ); ?>
						</div>
					<?php endif; ?>
				</a>
				<div class="bhdt-card-body">
						<p class="bhdt-card-kicker"><?php echo esc_html( get_post_type_object( get_post_type() )->labels->singular_name ?? __( 'Bài viết', 'bhdt-wirecutter' ) ); ?></p>
					<h2 class="bhdt-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<p class="bhdt-card-meta"><?php echo esc_html( bhdt_wirecutter_post_meta_line() ); ?></p>
					<p class="bhdt-card-excerpt"><?php echo esc_html( bhdt_wirecutter_excerpt_fallback( 30 ) ); ?></p>
					<a class="bhdt-read-more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Đọc thêm', 'bhdt-wirecutter' ); ?></a>
				</div>
			</article>
		<?php endwhile; ?>
	<?php endif; ?>
</main>
<?php
get_footer();
