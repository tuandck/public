<?php
/**
 * Theme header.
 *
 * @package BHDT_Wirecutter_Clone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="bhdt-site-shell">
	<header class="bhdt-site-header">
		<div class="bhdt-header-inner">
			<div class="bhdt-branding">
				<a href="<?php echo esc_url( bhdt_wirecutter_current_lang_home_url() ); ?>">Banhangdientu</a>
			</div>
			<div class="bhdt-header-utility">
				<?php get_search_form(); ?>
				<div class="bhdt-header-actions">
					<?php if ( function_exists( 'pll_the_languages' ) ) : ?>
						<div class="bhdt-lang-switcher" aria-label="<?php esc_attr_e( 'CÃ´ng cá»¥ chuyá»ƒn ngÃ´n ngá»¯', 'bhdt-wirecutter' ); ?>">
							<?php $bhdt_languages = pll_the_languages( array( 'raw' => 1, 'hide_if_no_translation' => 0, 'hide_if_empty' => 0, 'force_home' => 1 ) ); ?>
							<?php if ( is_array( $bhdt_languages ) ) : ?>
								<?php foreach ( $bhdt_languages as $bhdt_language ) : ?>
									<?php $bhdt_slug = isset( $bhdt_language['slug'] ) ? strtoupper( $bhdt_language['slug'] ) : ''; ?>
									<?php $bhdt_url = isset( $bhdt_language['url'] ) ? $bhdt_language['url'] : '#'; ?>
									<?php if ( 'EN' === $bhdt_slug ) { $bhdt_url = home_url( '/en/' ); } ?>
									<?php $bhdt_active = ! empty( $bhdt_language['current_lang'] ); ?>
									<a class="bhdt-lang-link <?php echo $bhdt_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $bhdt_url ); ?>"><?php echo esc_html( $bhdt_slug ); ?></a>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'ÄÄƒng nháº­p', 'bhdt-wirecutter' ); ?></a>
				<a class="bhdt-header-subscribe" href="#"><?php esc_html_e( 'ÄÄƒng kÃ½', 'bhdt-wirecutter' ); ?></a>
				</div>
			</div>
		</div>
		<div class="bhdt-mega-menu-wrap">
			<?php echo wp_kses_post( bhdt_wirecutter_render_mega_menu() ); ?>
		</div>
		<div class="bhdt-function-menu-wrap">
			<?php echo wp_kses_post( bhdt_wirecutter_render_function_menu() ); ?>
		</div>
	</header>
	<div class="bhdt-content">
		<div class="bhdt-content-inner">
