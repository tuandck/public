<?php
/**
 * Single template for bhdt_project.
 *
 * @package BHDT_Core_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="bhdt-single-template bhdt-single-project" style="max-width:980px;margin:2rem auto;padding:0 1rem;">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class(); ?>>
				<header style="margin-bottom:1.25rem;">
					<h1><?php the_title(); ?></h1>
				</header>
				<section class="entry-content">
					<?php the_content(); ?>
				</section>
				<footer style="margin-top:1.5rem;color:#666;">
					<p><?php esc_html_e( 'BHDT Project Wireframe Template', 'bhdt-core-system' ); ?></p>
				</footer>
			</article>
		<?php endwhile; ?>
	<?php endif; ?>
</main>
<?php
get_footer();
