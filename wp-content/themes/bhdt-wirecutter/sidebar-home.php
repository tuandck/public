<?php
/**
 * Home sidebar.
 *
 * @package BHDT_Wirecutter_Clone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<aside class="bhdt-home-sidebar">
	<?php if ( is_active_sidebar( 'bhdt-home-left' ) ) : ?>
		<?php dynamic_sidebar( 'bhdt-home-left' ); ?>
	<?php else : ?>
		<section class="bhdt-widget-box">
			<h3><?php esc_html_e( 'Mới nhất', 'bhdt-wirecutter' ); ?></h3>
			<ul class="bhdt-widget-list">
				<?php $latest = get_posts( array( 'post_type' => array( 'post', 'bhdt_review' ), 'posts_per_page' => 6 ) ); ?>
				<?php foreach ( $latest as $item ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $item ) ); ?>"><?php echo esc_html( get_the_title( $item ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
</aside>
