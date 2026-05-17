<?php
/**
 * Theme footer.
 *
 * @package BHDT_Wirecutter_Clone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
		</div>
	</div>
	<footer class="bhdt-site-footer">
		<div class="bhdt-footer-inner">
			<div class="bhdt-footer-grid">
				<div>
					<?php if ( is_active_sidebar( 'bhdt-footer-1' ) ) : ?>
						<?php dynamic_sidebar( 'bhdt-footer-1' ); ?>
					<?php else : ?>
							<p><strong><?php esc_html_e( 'Banhangdientu', 'bhdt-wirecutter' ); ?></strong><br><?php esc_html_e( 'Bố cục biên tập sạch được lấy cảm hứng từ Wirecutter.', 'bhdt-wirecutter' ); ?></p>
					<?php endif; ?>
				</div>
				<div>
					<?php if ( is_active_sidebar( 'bhdt-footer-2' ) ) : ?>
						<?php dynamic_sidebar( 'bhdt-footer-2' ); ?>
					<?php else : ?>
						<p><?php esc_html_e( 'Hướng dẫn thực tế, bài đánh giá điện tử và ghi chú dự án.', 'bhdt-wirecutter' ); ?></p>
					<?php endif; ?>
				</div>
				<div>
					<?php if ( is_active_sidebar( 'bhdt-footer-3' ) ) : ?>
						<?php dynamic_sidebar( 'bhdt-footer-3' ); ?>
					<?php else : ?>
						<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Banhangdientu</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
