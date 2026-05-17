<?php
/**
 * Single template for bhdt_review.
 *
 * @package BHDT_Core_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="bhdt-single-template bhdt-single-review" style="max-width:980px;margin:2rem auto;padding:0 1rem;">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class(); ?>>
				<header style="margin-bottom:1.25rem;">
					<h1><?php the_title(); ?></h1>
				</header>
				<section class="entry-content">
					<?php the_content(); ?>
				</section>
				<section style="margin-top:1.5rem;">
					<h2><?php esc_html_e( 'Thong so ky thuat', 'bhdt-core-system' ); ?></h2>
					<pre style="white-space:pre-wrap;background:#f7f7f7;padding:1rem;border:1px solid #e5e5e5;"><?php echo esc_html( (string) get_post_meta( get_the_ID(), 'bhdt_specs', true ) ); ?></pre>
				</section>
			</article>
		<?php endwhile; ?>
	<?php endif; ?>
</main>
<?php
get_footer();
