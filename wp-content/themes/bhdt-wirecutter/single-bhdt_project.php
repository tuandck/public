<?php
/**
 * Single template for bhdt_project.
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
						<p class="bhdt-cpt-kicker"><?php esc_html_e( 'Dự án DIY', 'bhdt-wirecutter' ); ?></p>
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
				$bom      = get_post_meta( get_the_ID(), '_bhdt_project_bom', true );
				$strength = get_post_meta( get_the_ID(), '_bhdt_project_strengths', true );
				$weakness = get_post_meta( get_the_ID(), '_bhdt_project_weaknesses', true );
				$specs    = get_post_meta( get_the_ID(), '_bhdt_project_specs', true );
				?>

				<?php if ( ! empty( $bom ) ) : ?>
					<section class="bhdt-single-specs">
						<h2><?php esc_html_e( 'BOM / Danh sách các phần', 'bhdt-wirecutter' ); ?></h2>
						<div class="bhdt-callout"><?php echo wp_kses_post( nl2br( esc_html( $bom ) ) ); ?></div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $strength ) || ! empty( $weakness ) ) : ?>
					<section class="bhdt-single-specs">
						<h2><?php esc_html_e( 'Ưu điểm & Nhược điểm', 'bhdt-wirecutter' ); ?></h2>
						<div class="bhdt-cpt-archive">
							<div class="bhdt-callout">
											<strong><?php esc_html_e( 'Ưu điểm', 'bhdt-wirecutter' ); ?></strong>
								<?php echo wp_kses_post( bhdt_wirecutter_render_list_items( $strength, 'bhdt-spec-list' ) ); ?>
							</div>
							<div class="bhdt-callout">
											<strong><?php esc_html_e( 'Nhược điểm', 'bhdt-wirecutter' ); ?></strong>
								<?php echo wp_kses_post( bhdt_wirecutter_render_list_items( $weakness, 'bhdt-spec-list' ) ); ?>
							</div>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $specs ) ) : ?>
					<section class="bhdt-single-specs">
						<h2><?php esc_html_e( 'Thông số Dự án / Ghi chú', 'bhdt-wirecutter' ); ?></h2>
						<div class="bhdt-callout"><?php echo wp_kses_post( nl2br( esc_html( $specs ) ) ); ?></div>
					</section>
				<?php endif; ?>

				<section class="bhdt-single-specs">
						<h2><?php esc_html_e( 'Ghi chú dự án', 'bhdt-wirecutter' ); ?></h2>
					<div class="bhdt-callout">
							<p><?php esc_html_e( 'Sử dụng khu vực này cho BOM, công cụ, điểm kiểm tra, ghi chú dây điện và liên kết khởi chạy. Bố cục có ý đọc trước vì vậy thêm thông tin dự án có thể được phân lớp mà không bị lộn xộn.', 'bhdt-wirecutter' ); ?></p>
					</div>
				</section>
			</article>
		<?php endwhile; ?>
	<?php endif; ?>
</main>
<?php
get_footer();
