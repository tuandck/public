<?php
/**
 * Single post template.
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
					<div class="bhdt-entry-meta">
						<?php echo esc_html( get_the_date() ); ?>
					</div>
				</header>
				<div class="bhdt-entry-content">
					<?php if ( has_post_thumbnail() ) : ?>
						<div style="margin:0 0 1.5rem;">
							<?php the_post_thumbnail( 'large' ); ?>
						</div>
					<?php endif; ?>
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	<?php endif; ?>
</main>
<?php
get_footer();
