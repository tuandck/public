<?php
/**
 * Page template.
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
					<h1 class="bhdt-entry-title"><?php the_title(); ?></h1>
					<div class="bhdt-entry-meta"><?php echo esc_html( bhdt_wirecutter_post_meta_line() ); ?></div>
				</header>

				<div class="bhdt-entry-content">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	<?php endif; ?>
</main>
<?php
get_footer();
