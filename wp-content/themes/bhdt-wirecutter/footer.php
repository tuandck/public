<?php
/**
 * Theme footer.
 *
 * @package BHDT_Wirecutter_Clone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bhdt_footer_settings = function_exists( 'bhdt_wirecutter_get_footer_settings' )
	? bhdt_wirecutter_get_footer_settings()
	: array();

$bhdt_footer_defaults = array(
	'use_widgets' => 0,
	'logo_text'   => 'Banhangdientu',
	'logo_image'  => '',
	'description' => __( 'Bố cục biên tập gọn gàng, cảm hứng từ Wirecutter.', 'bhdt-wirecutter' ),
	'middle_text' => __( 'Hướng dẫn thực tế, đánh giá linh kiện, ghi chú dự án.', 'bhdt-wirecutter' ),
	'copyright'   => '© {year} Banhangdientu',
	'right_text'  => '',
	'facebook'    => '',
	'youtube'     => '',
	'tiktok'      => '',
	'zalo'        => '',
	'bg_color'    => '#181818',
	'text_color'  => '#ffffff',
	'muted_color' => '#eeeeee',
);

$bhdt_footer_settings  = array_merge( $bhdt_footer_defaults, is_array( $bhdt_footer_settings ) ? $bhdt_footer_settings : array() );
$bhdt_footer_copyright = str_replace( '{year}', gmdate( 'Y' ), $bhdt_footer_settings['copyright'] );
$bhdt_footer_style     = sprintf(
	'--bhdt-footer-bg:%1$s;--bhdt-footer-text:%2$s;--bhdt-footer-muted:%3$s;',
	esc_attr( $bhdt_footer_settings['bg_color'] ),
	esc_attr( $bhdt_footer_settings['text_color'] ),
	esc_attr( $bhdt_footer_settings['muted_color'] )
);
$bhdt_footer_socials = array(
	'facebook' => 'Facebook',
	'youtube'  => 'YouTube',
	'tiktok'   => 'TikTok',
	'zalo'     => 'Zalo',
);
?>
		</div>
	</div>
	<footer class="bhdt-site-footer" style="<?php echo esc_attr( $bhdt_footer_style ); ?>">
		<div class="bhdt-footer-inner">
			<div class="bhdt-footer-grid">
				<?php if ( ! empty( $bhdt_footer_settings['use_widgets'] ) ) : ?>
					<div>
						<?php if ( is_active_sidebar( 'bhdt-footer-1' ) ) : ?>
							<?php dynamic_sidebar( 'bhdt-footer-1' ); ?>
						<?php endif; ?>
					</div>
					<div>
						<?php if ( is_active_sidebar( 'bhdt-footer-2' ) ) : ?>
							<?php dynamic_sidebar( 'bhdt-footer-2' ); ?>
						<?php endif; ?>
					</div>
					<div>
						<?php if ( is_active_sidebar( 'bhdt-footer-3' ) ) : ?>
							<?php dynamic_sidebar( 'bhdt-footer-3' ); ?>
						<?php endif; ?>
					</div>
				<?php else : ?>
				<div>
					<div class="bhdt-footer-brand">
						<?php if ( ! empty( $bhdt_footer_settings['logo_image'] ) ) : ?>
							<img src="<?php echo esc_url( $bhdt_footer_settings['logo_image'] ); ?>" alt="<?php echo esc_attr( $bhdt_footer_settings['logo_text'] ); ?>">
						<?php elseif ( ! empty( $bhdt_footer_settings['logo_text'] ) ) : ?>
							<strong><?php echo esc_html( $bhdt_footer_settings['logo_text'] ); ?></strong>
						<?php endif; ?>
						<?php if ( ! empty( $bhdt_footer_settings['description'] ) ) : ?>
							<p><?php echo esc_html( $bhdt_footer_settings['description'] ); ?></p>
						<?php endif; ?>
					</div>
				</div>
				<div>
					<?php if ( ! empty( $bhdt_footer_settings['middle_text'] ) ) : ?>
						<p><?php echo esc_html( $bhdt_footer_settings['middle_text'] ); ?></p>
					<?php endif; ?>
				</div>
				<div>
					<?php if ( ! empty( $bhdt_footer_copyright ) ) : ?>
						<p><?php echo esc_html( $bhdt_footer_copyright ); ?></p>
					<?php endif; ?>
					<div class="bhdt-footer-socials">
						<?php foreach ( $bhdt_footer_socials as $bhdt_social_key => $bhdt_social_label ) : ?>
							<?php if ( ! empty( $bhdt_footer_settings[ $bhdt_social_key ] ) ) : ?>
								<a href="<?php echo esc_url( $bhdt_footer_settings[ $bhdt_social_key ] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $bhdt_social_label ); ?></a>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
					<?php if ( ! empty( $bhdt_footer_settings['right_text'] ) ) : ?>
						<p class="bhdt-footer-extra-text"><?php echo nl2br( esc_html( $bhdt_footer_settings['right_text'] ) ); ?></p>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
