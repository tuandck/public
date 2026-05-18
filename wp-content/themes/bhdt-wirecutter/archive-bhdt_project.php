<?php
/**
 * Archive template for bhdt_project.
 *
 * @package BHDT_Wirecutter_Clone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$post_count = wp_count_posts( 'bhdt_project' );
?>
<main class="bhdt-home-grid">
	<section class="bhdt-archive-hero">
		<p class="bhdt-card-kicker"><?php esc_html_e( 'Lưu trữ dự án', 'bhdt-wirecutter' ); ?></p>
		<h1><?php post_type_archive_title(); ?></h1>
		<p><?php esc_html_e( 'Quy trình dự án, ý tưởng BOM và ghi chú xây dựng được hiển thị trong lưới biên tập rõ ràng.', 'bhdt-wirecutter' ); ?></p>
		<p class="bhdt-card-meta"><?php echo esc_html( sprintf( __( '%d dự án được xuất bản', 'bhdt-wirecutter' ), (int) ( $post_count->publish ?? 0 ) ) ); ?></p>
	</section>

	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'bhdt-card' ); ?>>
				<a href="<?php the_permalink(); ?>" class="bhdt-card-thumb" aria-hidden="true" tabindex="-1">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'large' ); ?>
					<?php else : ?>
						<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-family:'Noto Sans',Arial,sans-serif;color:#888;">
								<?php esc_html_e( 'Không có hình ảnh', 'bhdt-wirecutter' ); ?>
						</div>
					<?php endif; ?>
				</a>
				<div class="bhdt-card-body">
						<p class="bhdt-card-kicker"><?php esc_html_e( 'Dự án', 'bhdt-wirecutter' ); ?></p>
					<h2 class="bhdt-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<p class="bhdt-card-meta"><?php echo esc_html( bhdt_wirecutter_post_meta_line() ); ?></p>
					<p class="bhdt-card-excerpt"><?php echo esc_html( bhdt_wirecutter_excerpt_fallback( 28 ) ); ?></p>
					<?php $bom = get_post_meta( get_the_ID(), '_bhdt_project_bom', true ); ?>
					<?php if ( ! empty( $bom ) ) : ?>
						<p class="bhdt-cpt-meta"><?php echo esc_html( wp_trim_words( str_replace( array( "\r", "\n" ), ' ', $bom ), 12, '...' ) ); ?></p>
					<?php endif; ?>
					<?php $strength = get_post_meta( get_the_ID(), '_bhdt_project_strengths', true ); ?>
					<?php if ( ! empty( $strength ) ) : ?>
							<p class="bhdt-read-more"><?php esc_html_e( 'Có những điểm nổi bật', 'bhdt-wirecutter' ); ?></p>
					<?php endif; ?>
					<a class="bhdt-read-more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Đọc dự án', 'bhdt-wirecutter' ); ?></a>
				</div>
			</article>
		<?php endwhile; ?>
	<?php endif; ?>
</main>
<?php
get_footer();
