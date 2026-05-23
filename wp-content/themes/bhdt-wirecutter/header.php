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
		<div class="bhdt-sticky-header-bar">
			<div class="bhdt-header-inner">
				<div class="bhdt-branding">
					<a href="<?php echo esc_url( bhdt_wirecutter_current_lang_home_url() ); ?>">Banhangdientu</a>
				</div>
				<div class="bhdt-header-utility">
					<?php get_search_form(); ?>
					<div class="bhdt-header-actions">
						<?php if ( function_exists( 'pll_the_languages' ) ) : ?>
							<div class="bhdt-lang-switcher" aria-label="<?php esc_attr_e( 'Công cụ chuyển ngôn ngữ', 'bhdt-wirecutter' ); ?>">
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
					<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Đăng nhập', 'bhdt-wirecutter' ); ?></a>
					<a class="bhdt-header-subscribe" href="#"><?php esc_html_e( 'Đăng ký', 'bhdt-wirecutter' ); ?></a>
					</div>
				</div>
			</div>
		</div>
		<script>
		(function () {
			var toggle = function () {
				document.body.classList.toggle('bhdt-header-is-scrolled', window.scrollY > 24);
			};
			toggle();
			window.addEventListener('scroll', toggle, { passive: true });
		})();
		document.addEventListener('DOMContentLoaded', function () {
			var mobileQuery = window.matchMedia('(max-width: 720px)');
			var items = document.querySelectorAll('.bhdt-function-menu-item, .bhdt-mega-parent-item');

			if (!items.length) {
				return;
			}

			var closeMenus = function (except) {
				items.forEach(function (item) {
					if (item !== except) {
						item.classList.remove('is-touch-open');
					}
				});
			};

			items.forEach(function (item) {
				var link = item.querySelector('.bhdt-function-menu-link, .bhdt-mega-parent-link');
				var submenu = item.querySelector('.bhdt-function-child-list, .bhdt-mega-child-list');

				if (!link || !submenu) {
					return;
				}

				link.addEventListener('click', function (event) {
					if (!mobileQuery.matches) {
						return;
					}

					if (!item.classList.contains('is-touch-open')) {
						event.preventDefault();
						closeMenus(item);
						item.classList.add('is-touch-open');
						submenu.style.top = Math.ceil(link.getBoundingClientRect().bottom + 8) + 'px';
					}
				});
			});

			document.addEventListener('click', function (event) {
				if (!mobileQuery.matches || event.target.closest('.bhdt-function-menu-item, .bhdt-mega-parent-item')) {
					return;
				}

				closeMenus();
			});

			window.addEventListener('resize', function () {
				if (!mobileQuery.matches) {
					closeMenus();
				}
			}, { passive: true });
		});
		<?php if ( ! function_exists( 'bhdt_wirecutter_menu_ant_enabled' ) || bhdt_wirecutter_menu_ant_enabled() ) : ?>
		document.addEventListener('DOMContentLoaded', function () {
			var menu = document.querySelector('.bhdt-function-menu');
			var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
			var desktopQuery = window.matchMedia('(min-width: 721px)');

			if (!menu || reduceMotion.matches || !desktopQuery.matches) {
				return;
			}

			var chatLink = null;
			menu.querySelectorAll('.bhdt-function-menu-link').forEach(function (link) {
				var label = (link.textContent || '').toLowerCase();

				if (!chatLink && (label.indexOf('phòng chat') !== -1 || label.indexOf('phong chat') !== -1)) {
					chatLink = link;
				}
			});

			if (!chatLink) {
				return;
			}

			chatLink.classList.add('bhdt-chat-ant-target');

			var ant = document.createElement('span');
			ant.className = 'bhdt-menu-ant';
			ant.setAttribute('aria-hidden', 'true');
			ant.innerHTML = '<span class="bhdt-menu-ant-antenna"></span><span class="bhdt-menu-ant-midlegs"></span>';
			menu.appendChild(ant);

			var positionAnt = function () {
				if (!desktopQuery.matches) {
					return;
				}

				var menuRect = menu.getBoundingClientRect();
				var linkRect = chatLink.getBoundingClientRect();
				var startX = Math.max(0, linkRect.left - menuRect.left + Math.min(26, linkRect.width * 0.28));
				var endX = Math.max(startX + 120, menuRect.width - 16);
				var y = Math.max(0, linkRect.top - menuRect.top - 11);

				ant.style.setProperty('--bhdt-ant-start-x', startX + 'px');
				ant.style.setProperty('--bhdt-ant-end-x', endX + 'px');
				ant.style.setProperty('--bhdt-ant-y', y + 'px');
			};

			positionAnt();
			window.addEventListener('resize', positionAnt, { passive: true });
		});
		<?php endif; ?>
		</script>
		<div class="bhdt-function-menu-wrap">
			<?php echo wp_kses_post( bhdt_wirecutter_render_function_menu() ); ?>
		</div>
		<div class="bhdt-mega-menu-wrap">
			<?php echo wp_kses_post( bhdt_wirecutter_render_mega_menu() ); ?>
		</div>
	</header>
	<div class="bhdt-content">
		<div class="bhdt-content-inner">
